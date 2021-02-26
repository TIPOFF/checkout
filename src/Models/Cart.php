<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Exceptions\MultipleLocationException;
use Tipoff\Checkout\Models\Traits\IsItemContainer;
use Tipoff\Checkout\Objects\CartPricingDetail;
use Tipoff\Checkout\Services\Cart\ApplyCode;
use Tipoff\Checkout\Services\Cart\ApplyCredits;
use Tipoff\Checkout\Services\Cart\ApplyDiscounts;
use Tipoff\Checkout\Services\Cart\ApplyTaxes;
use Tipoff\Checkout\Services\Cart\CompletePurchase;
use Tipoff\Checkout\Services\Cart\VerifyPurchasable;
use Tipoff\Checkout\Services\CartItem\AddToCart;
use Tipoff\Checkout\Services\CartItem\UpdateInCart;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Events\Checkout\CartUpdated;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Objects\DiscountableValue;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property int credits
 * // Relations
 * @property Order|null order
 * @property Collection cartItems
 */
class Cart extends BaseModel implements CartInterface
{
    use HasPackageFactory;
    use SoftDeletes;
    use IsItemContainer;

    protected $casts = [
        'id' => 'integer',
        'shipping' => \Tipoff\Support\Casts\DiscountableValue::class,
        'item_amount' => \Tipoff\Support\Casts\DiscountableValue::class,
        'discounts' => 'integer',
        'credits' => 'integer',
        'tax' => 'integer',
        'user_id' => 'integer',
        'location_id' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function (Cart $cart) {
            Assert::lazy()
                ->that($cart->user_id, 'user_id')->notEmpty('A cart must belong to a user.')
                ->verifyNow();
        });

        static::deleting(function (Cart $cart) {
            $cart->cartItems()->isRootItem()->get()->each->delete();
        });
    }

    //region RELATIONSHIPS

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    //endregion

    //region SCOPES

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereDoesntHave('cartItems', function (Builder $query) {
            $query->expired();
        });
    }

    //endregion

    //region SERVICE WRAPPERS

    public function verifyPurchasable(): self
    {
        return app(VerifyPurchasable::class)($this);
    }

    public function completePurchase(): Order
    {
        return app(CompletePurchase::class)($this);
    }

    //endregion

    //region PRICING

    public function getBalanceDue(): int
    {
        return $this->getPricingDetail()->getBalanceDue();
    }

    public function getPricingDetail(): CartPricingDetail
    {
        return new CartPricingDetail($this);
    }

    protected function getCartTotal(): DiscountableValue
    {
        // Cart total includes cart discounts, but not cart credits
        // Shipping and taxes are also not included
        return $this->getItemAmount()
            ->addDiscounts($this->getDiscounts());
    }

    public function updatePricing(): self
    {
        // Calculate ALl - order is important!
        $this->calculateDiscounts()
            ->calculateTaxes()
            ->calculateCredits();

        return $this->saveAll();
    }

    protected function calculateDiscounts(): self
    {
        app(ApplyDiscounts::class)($this);

        return $this->updateItemAmount();
    }

    protected function calculateTaxes(): self
    {
        app(ApplyTaxes::class)($this);

        return $this->updateTax();
    }

    protected function calculateCredits(): self
    {
        app(ApplyCredits::class)($this);

        return $this;
    }

    //endregion

    //region INTERFACE IMPLEMENTATION

    public static function activeCart(int $userId): CartInterface
    {
        $cart = Cart::query()
            ->where('user_id', $userId)
            ->active()
            ->orderByDesc('id')
            ->first();

        return $cart ?: static::create([
            'user_id' => $userId,
        ]);
    }

    public static function createItem(Sellable $sellable, string $itemId, $amount, int $quantity = 1): CartItemInterface
    {
        // Model instance is required for morph
        if ($sellable instanceof Model) {
            $item = (new CartItem([
                'item_id' => $itemId,
                'description' => $sellable->getDescription(),
                'quantity' => $quantity,
            ]))->setAmount($amount);

            $item->sellable()->associate($sellable);

            return $item;
        }

        throw new \InvalidArgumentException();
    }

    public function upsertItem(CartItemInterface $cartItem): CartItemInterface
    {
        if ($cartItem instanceof CartItem) {
            if ($cartItem->getCart()) {
                return app(UpdateInCart::class)($cartItem, $this);
            }

            return app(AddToCart::class)($cartItem, $this);
        }

        throw new CartNotValidException();
    }

    public function findItem(Sellable $sellable, string $itemId): ?CartItemInterface
    {
        /** @var CartItem|null $result */
        $result = $this->cartItems()->bySellableId($sellable, $itemId)->first();

        return $result;
    }

    public function removeItem(Sellable $sellable, string $itemId): CartInterface
    {
        $this->cartItems()->bySellableId($sellable, $itemId)->get()->each->delete();

        $this->load('cartItems');
        $this->updatePricing();

        CartUpdated::dispatch($this);

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->cartItems;
    }

    public function setShipping($shipping): CartInterface
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function addDiscounts(int $value): CartInterface
    {
        $total = $this->getCartTotal();

        // Ensure total cart discount never exceeds discounted total
        $maxDiscount = $total->getDiscountedAmount();

        $this->discounts = max(0, min($maxDiscount, $this->discounts + $value));

        // Ensure credit remains valid for possible change in cart discount
        return $this->addCredits(0);
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function addCredits(int $value): CartInterface
    {
        // For credit calculations, include tax amount owed
        $total = $this->getCartTotal()
            ->add(new DiscountableValue($this->getTax()));

        // Ensure total cart credit never exceeds discounted total
        $maxCredit = $total->getDiscountedAmount();

        $this->credits = max(0, min($maxCredit, $this->credits + $value));

        return $this;
    }

    public function setLocationId(?int $locationId): self
    {
        if ($locationId) {
            if ($this->location_id && ! $this->location_id != $locationId) {
                throw new MultipleLocationException();
            }

            $this->location_id = $locationId;
        }

        return $this;
    }

    public function applyCode(string $code): CartInterface
    {
        app(ApplyCode::class)($this, $code);

        return $this->updatePricing();
    }

    //endregion

    //region PROTECTED HELPERS

    protected function saveAll(): self
    {
        $this->cartItems->each->save();
        $this->save();

        return $this;
    }

    //endregion
}

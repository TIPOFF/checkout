<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Exceptions\MultipleLocationException;
use Tipoff\Checkout\Filters\ItemFilter;
use Tipoff\Checkout\Models\Traits\IsItemContainer;
use Tipoff\Checkout\Services\Cart\ActiveAdjustments;
use Tipoff\Checkout\Services\Cart\ApplyCode;
use Tipoff\Checkout\Services\Cart\ApplyCredits;
use Tipoff\Checkout\Services\Cart\ApplyDiscounts;
use Tipoff\Checkout\Services\Cart\ApplyTaxes;
use Tipoff\Checkout\Services\Cart\CompletePurchase;
use Tipoff\Checkout\Services\Cart\Purchase;
use Tipoff\Checkout\Services\Cart\VerifyPurchasable;
use Tipoff\Checkout\Services\CartItem\AddToCart;
use Tipoff\Checkout\Services\CartItem\UpdateInCart;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;
use Tipoff\Support\Contracts\Checkout\OrderInterface;
use Tipoff\Support\Contracts\Models\UserInterface;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Events\Checkout\CartUpdated;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Objects\DiscountableValue;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property Carbon|null deleted_at
 * // Relations
 * @property EmailAddress emailAddress
 * @property Order|null order
 * @property Collection cartItems
 * @property int|null order_id
 * @property int|null email_address_id
 */
class Cart extends BaseModel implements CartInterface
{
    use HasPackageFactory;
    use SoftDeletes;
    use IsItemContainer;

    protected $guarded = [
        'id',
        'item_amount_total',
        'item_amount_total_discounts',
        'tax',
    ];

    protected $casts = [
        'id' => 'integer',
        'shipping' => \Tipoff\Support\Casts\DiscountableValue::class,
        'item_amount_total' => \Tipoff\Support\Casts\DiscountableValue::class,
        'discounts' => 'integer',
        'credits' => 'integer',
        'tax' => 'integer',
        'email_address_id' => 'integer',
        'order_id' => 'integer',
        'location_id' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function (Cart $cart) {
            Assert::lazy()
                ->that($cart->email_address_id, 'email_address_id')->notEmpty('A cart must have an email address.')
                ->verifyNow();
        });

        static::saved(function (Cart $cart) {
            /** @psalm-suppress TooManyArguments */
            CartUpdated::dispatch($cart);
        });

        static::deleting(function (Cart $cart) {
            $cart->cartItems()->isRootItem()->get()->each->delete();
        });
    }

    //region RELATIONSHIPS

    public function emailAddress()
    {
        return $this->belongsTo(app('email_address'));
    }

    public function cartItems()
    {
        return $this->hasMany(app('cart_item'));
    }

    public function order()
    {
        return $this->belongsTo(app('order'));
    }

    public function discounts()
    {
        return $this->belongsToMany(app('discount'));
    }

    public function vouchers()
    {
        return $this->belongsToMany(app('voucher'));
    }

    //endregion

    //region SCOPES

    public function scopeIsAbandoned(Builder $query): Builder
    {
        return $query->whereNull('order_id')
            ->whereHas('cartItems', function (Builder $query) {
                $query->expired();
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('order_id')
            ->whereDoesntHave('cartItems', function (Builder $query) {
                $query->expired();
            });
    }

    //endregion

    //region PERMISSIONS

    public function scopeVisibleByEmailAddressId(Builder $query, int $emailAddressId): Builder
    {
        return $query->where('email_address_id', '=', $emailAddressId);
    }

    public function isOwnerEmailAddressId(int $emailAddressId): bool
    {
        return $this->email_address_id === $emailAddressId;
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

    protected function getCartTotal(): DiscountableValue
    {
        // Cart total includes cart discounts, but not cart credits
        // Shipping and taxes are also not included
        return $this->getItemAmountTotal()
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

        return $this->updateItemAmountTotal();
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

    public function getExpiresAt(): ?Carbon
    {
        return $this->cartItems->min->expires_at;
    }

    public function isEmpty(): bool
    {
        return $this->cartItems->isEmpty();
    }

    //region INTERFACE IMPLEMENTATION

    public static function activeCart(int $emailAddressId): CartInterface
    {
        $cart = static::query()
            ->where('email_address_id', $emailAddressId)
            ->active()
            ->orderByDesc('id')
            ->first();

        return $cart ?: static::create([
            'email_address_id' => $emailAddressId,
        ]);
    }

    public static function abandonedCarts(?string $createdAfter = null, ?string $createdBefore = null): Collection
    {
        return static::query()
            ->isAbandoned()
            ->where(function (Builder $query) use ($createdBefore, $createdAfter) {
                if ($createdAfter) {
                    $query->where('created_at', '>=', Carbon::parse($createdAfter));
                }
                if ($createdBefore) {
                    $query->where('created_at', '<=', Carbon::parse($createdBefore));
                }
            })
            ->get();
    }

    public function getUser(): UserInterface
    {
        return $this->emailAddress->user;
    }

    public static function itemFilter(): ItemFilter
    {
        return new ItemFilter(CartItem::query());
    }

    public static function createItem(Sellable $sellable, string $itemId, $amount, int $quantity = 1): CartItemInterface
    {
        // Model instance is required for morph
        if ($sellable instanceof Model) {
            $item = new CartItem;

            $item->item_id = $itemId;
            $item->setDescription($sellable->getDescription())
                ->setQuantity($quantity)
                ->setAmountEach($amount)
                ->setSellable($sellable);
            $item->updateCalculatedValues();

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
            if ($this->location_id && $this->location_id != $locationId) {
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

    public function purchase($paymentMethod): OrderInterface
    {
        return app(Purchase::class)($this, $paymentMethod);
    }

    //endregion

    //region PROTECTED HELPERS

    public function getCodes(): array
    {
        return (new ActiveAdjustments())()
            ->reduce(function (array $codes, CodedCartAdjustment $deduction) {
                return array_merge($codes, $deduction::getCodesForCart($this));
            }, []);
    }

    protected function saveAll(): self
    {
        $this->cartItems->each->save();
        $this->save();

        return $this;
    }

    //endregion
}

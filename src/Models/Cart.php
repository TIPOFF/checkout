<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Contracts\Models\DiscountInterface;
use Tipoff\Checkout\Contracts\Models\VoucherInterface;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Exceptions\MultipleLocationException;
use Tipoff\Checkout\Objects\CartPricingDetail;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Contracts\Models\BaseModelInterface;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Events\Checkout\CartItemCreated;
use Tipoff\Support\Events\Checkout\CartItemPurchaseVerification;
use Tipoff\Support\Events\Checkout\CartItemUpdated;
use Tipoff\Support\Events\Checkout\CartUpdated;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Objects\DiscountableValue;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasPackageFactory;
use Tipoff\Support\Traits\HasUpdater;

/**
 * @property int|null id
 * @property DiscountableValue $shipping
 * @property int cart_discounts
 * @property int cart_credits
 * @property DiscountableValue $item_amount
 * @property int tax
 * @property Carbon created_at
 * @property Carbon updated_at
 * // Raw Relation ID
 * @property int|null user_id
 * @property int|null location_id
 * @property int|null creator_id
 * @property int|null updater_id
 */
class Cart extends BaseModel implements CartInterface
{
    use HasPackageFactory;
    use SoftDeletes;
    use HasCreator;
    use HasUpdater;

    protected $casts = [
        'id' => 'integer',
        'shipping' => \Tipoff\Support\Casts\DiscountableValue::class,
        'item_amount' => \Tipoff\Support\Casts\DiscountableValue::class,
        'cart_discounts' => 'integer',
        'cart_credits' => 'integer',
        'tax' => 'integer',
        'user_id' => 'integer',
        'location_id' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
    ];

    private static array $deductionTypes = [
        VoucherInterface::class,
        DiscountInterface::class,
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function (Cart $cart) {
            $cart->cartItems()->isRootItem()->get()->each->delete();
        });
    }

    //region RELATIONSHIPS

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function location()
    {
        return $this->belongsTo(app('location'));
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

    //region PURCHASING

    /**
     * Performs final verification that cart is purchasable.  This includes
     * - basic validation of cart and item expirations
     * - dispatch of CartItemPurchased event allowing Sellable ability to abort
     * - confirmation application of discounts and credits results in identical total
     *
     * @return $this
     */
    public function verifyPurchasable(): self
    {
        DB::transaction(function () {
            // Must have at least one item
            if ($this->cartItems->isEmpty()) {
                throw new CartNotValidException();
            }

            // All items must be active
            if ($this->cartItems->first->isExpired()) {
                throw new CartNotValidException();
            }

            // Ensure all items remain purchasable
            $this->cartItems->each(function (CartItem $cartItem) {
                CartItemPurchaseVerification::dispatch($cartItem);
            });

            // Validate all discounts and vouchers remain valid
            $originalTotal = $this->getPricingDetail();
            $this->updatePricing();
            if (! $originalTotal->isEqual($this->getPricingDetail())) {
                throw new CartNotValidException();
            }
        });

        return $this;
    }

    public function completePurchase(?BaseModelInterface $payment): Order
    {
        DB::transaction(function () {
            /**
             * - Create order from cart summary
             * - Create orderitem for each cartitem, dispatch OrderItemCreated
             * - Dispatch OrderCreated event
             */
        });

        // TODO - placeholder to keep psalm happy
        return new Order();
    }

    //endregion

    //region PRICING

    public function getBalanceDue(): int
    {
        return $this->getPricingDetail()->getBalanceDue();
    }

    protected function getPricingDetail(): CartPricingDetail
    {
        return new CartPricingDetail($this);
    }

    protected function getCartTotal(): DiscountableValue
    {
        // Cart total includes cart discounts, but not cart credits
        // Shipping and taxes are also not included
        return $this->getItemAmount()
            ->addDiscounts($this->cart_discounts);
    }

    public function updatePricing(): self
    {
        // Reset All
        $this->resetDiscounts()
            ->resetTaxes()
            ->resetCredits();

        // Calculate ALl - order is important!
        $this->calculateDiscounts()
            ->calculateTaxes()
            ->calculateCredits();

        return $this->saveAll();
    }

    protected function resetDiscounts(): self
    {
        $this->cartItems->each(function (CartItem $cartItem) {
            $cartItem->setAmount($cartItem->getAmount()->reset());
        });
        $this->setShipping($this->getShipping()->reset());
        $this->cart_discounts = 0;

        return $this;
    }

    protected function resetTaxes(): self
    {
        $this->cartItems->each(function (CartItem $cartItem) {
            $cartItem->tax = 0;
        });
        $this->tax = 0;

        return $this;
    }

    protected function resetCredits(): self
    {
        $this->cart_credits = 0;

        return $this;
    }

    protected function calculateDiscounts(): self
    {
        /*
        if ($service = findService(DiscountInterface::class)) {
            // TBD
        }
        */

        $this->item_amount = $this->cartItems->reduce(function (DiscountableValue $itemAmount, CartItem $cartItem) {
            return $itemAmount->add($cartItem->getAmount());
        }, new DiscountableValue(0));

        return $this;
    }

    protected function calculateTaxes(): self
    {
        /**
        if ($service = findService(TaxRequest::class)) {
            $taxRequest = $service::createTaxRequest();

            $this->cartItems->each(function (CartItem $cartItem) use ($taxRequest) {
                $taxRequest->createTaxRequestItem($cartItem->getId(), $cartItem->getLocation()->getId(), $cartItem->getTaxCode(), $cartItem->getAmount()->getDiscountedAmount());
            });

            $taxRequest->calculateTax();

            $this->cartItems->each(function (CartItem $cartItem) use ($taxRequest) {
                $taxRequest = $taxRequest->getTaxRequestItem($cartItem->getId());
                $cartItem->tax = $taxRequest ? $taxRequest->getTax() : 0;
            });
        }
        */

        $this->tax = $this->cartItems->sum->tax;

        return $this;
    }

    protected function calculateCredits(): self
    {
        /*
        if ($service = findService(VoucherInterface::class)) {
            // TBD
        }
        */

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
                return $this->updateItem($cartItem);
            }

            return $this->insertItem($cartItem);
        }

        throw new CartNotValidException();
    }

    protected function insertItem(CartItem $cartItem): CartItem
    {
        // Ensure item is unique
        if ($this->findItem($cartItem->getSellable(), $cartItem->getItemId())) {
            throw new CartNotValidException();
        }

        // Validate location is allowed
        $this->setLocationId($cartItem->getLocationId());

        $this->cartItems()->save($cartItem);

        CartItemCreated::dispatch($cartItem);
        $cartItem->save();

        $this->load('cartItems');
        $this->updatePricing();

        CartUpdated::dispatch($this);

        return $cartItem->load('cart');
    }

    protected function updateItem(CartItem $cartItem): CartItem
    {
        // Validate item already exists in is in this cart
        if (! $cartItem->getCart() || ($cartItem->getCart()->getId() !== $this->id)) {
            throw new CartNotValidException();
        }

        // Validate location is allowed
        $this->setLocationId($cartItem->getLocationId());

        CartItemUpdated::dispatch($cartItem);
        $cartItem->save();

        $this->load('cartItems');
        $this->updatePricing();

        CartUpdated::dispatch($this);

        return $cartItem->load('cart');
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

    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function getItemAmount(): DiscountableValue
    {
        return $this->item_amount;
    }

    public function getTax(): int
    {
        return $this->tax;
    }

    public function setShipping($shipping): CartInterface
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getShipping(): DiscountableValue
    {
        return $this->shipping;
    }

    public function getCartDiscounts(): int
    {
        return $this->cart_discounts;
    }

    public function addCartDiscounts(int $value): CartInterface
    {
        $total = $this->getCartTotal();

        // Ensure total cart discount never exceeds discounted total
        $maxDiscount = $total->getDiscountedAmount();

        $this->cart_discounts = max($maxDiscount, $this->cart_discounts + $value);

        // Ensure credit remains valid for possible change in cart discount
        return $this->addCartCredits(0);
    }

    public function getCartCredits(): int
    {
        return $this->cart_credits;
    }

    public function addCartCredits(int $value): CartInterface
    {
        // For credit calculations, include tax amount owed
        $total = $this->getCartTotal()
            ->add(new DiscountableValue($this->tax));

        // Ensure total cart credit never exceeds discounted total
        $maxCredit = $total->getDiscountedAmount();

        $this->cart_credits = max($maxCredit, $this->cart_credits + $value);

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

    public function getLocationId(): ?int
    {
        return $this->location_id;
    }

    public function applyCode(string $code): CartInterface
    {
        // TODO: Implement applyCode() method.
        return $this;
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

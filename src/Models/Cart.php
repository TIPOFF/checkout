<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Exceptions\InvalidDeductionCodeException;
use Tipoff\Checkout\Exceptions\MultipleLocationException;
use Tipoff\Checkout\Models\Traits\IsItemContainer;
use Tipoff\Checkout\Objects\CartPricingDetail;
use Tipoff\Checkout\Services\Cart\CompletePurchase;
use Tipoff\Checkout\Services\Cart\VerifyPurchasable;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Contracts\Taxes\TaxRequest;
use Tipoff\Support\Events\Checkout\CartItemCreated;
use Tipoff\Support\Events\Checkout\CartItemUpdated;
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

    private static array $deductionTypes = [
        VoucherInterface::class,
        DiscountInterface::class,
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
        if (findService(DiscountInterface::class)) {
            $this->cartItems->each(function (CartItem $cartItem) {
                $cartItem->setAmount($cartItem->getAmount()->reset());
            });
            $this->setShipping($this->getShipping()->reset());
            $this->discounts = 0;
        }

        return $this->updateItemAmount();
    }

    protected function resetTaxes(): self
    {
        if (findService(TaxRequest::class)) {
            $this->cartItems->each(function (CartItem $cartItem) {
                $cartItem->tax = 0;
            });
        }

        return $this->updateTax();
    }

    protected function resetCredits(): self
    {
        if (findService(VoucherInterface::class)) {
            $this->credits = 0;
        }

        return $this;
    }

    protected function calculateDiscounts(): self
    {
        if ($service = findService(DiscountInterface::class)) {
            /** @var DiscountInterface $service */
            $service::calculateAdjustments($this);
        }

        return $this->updateItemAmount();
    }

    protected function calculateTaxes(): self
    {
        if ($service = findService(TaxRequest::class)) {
            /** @var TaxRequest $service */
            $taxRequest = $service::createTaxRequest();

            $this->cartItems->each(function (CartItem $cartItem) use ($taxRequest) {
                $taxRequest->createTaxRequestItem(
                    $cartItem->getId(),
                    $cartItem->getLocationId(),
                    $cartItem->getTaxCode(),
                    $cartItem->getAmount()->getDiscountedAmount()
                );
            });

            $taxRequest->calculateTax();

            $this->cartItems->each(function (CartItem $cartItem) use ($taxRequest) {
                $taxRequest = $taxRequest->getTaxRequestItem($cartItem->getId());
                $cartItem->setTax($taxRequest ? $taxRequest->getTax() : 0);
            });
        }

        return $this->updateTax();
    }

    protected function calculateCredits(): self
    {
        if ($service = findService(VoucherInterface::class)) {
            /** @var VoucherInterface $service */
            $service::calculateAdjustments($this);
        }

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
        $deduction = $this->findDeductionByCode($code);

        if (empty($deduction)) {
            throw new InvalidDeductionCodeException($code);
        }

        $deduction->applyToCart($this);

        return $this->updatePricing();
    }

    //endregion

    //region PROTECTED HELPERS

    protected static function activeDeductions(): Collection
    {
        return collect(static::$deductionTypes)
            ->filter(function (string $type) {
                return app()->has($type);
            })
            ->map(function (string $type) {
                return app($type);
            });
    }

    protected function findDeductionByCode(string $code): ?CodedCartAdjustment
    {
        return static::activeDeductions()
            ->first(function (CodedCartAdjustment $deduction) use ($code) {
                return $deduction::findByCode($code);
            });
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

    protected function saveAll(): self
    {
        $this->cartItems->each->save();
        $this->save();

        return $this;
    }

    //endregion
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Tipoff\Checkout\Models\Traits\IsItem;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Events\Checkout\CartItemCreated;
use Tipoff\Support\Events\Checkout\CartItemRemoved;
use Tipoff\Support\Events\Checkout\CartItemUpdated;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property Carbon expires_at
 * @property Cart cart
 * // Raw Relation ID
 * @property int|null cart_id
 */
class CartItem extends BaseModel implements CartItemInterface
{
    use HasPackageFactory;
    use IsItem;

    protected $touches = [
        'cart',
    ];

    protected $guarded = [
        'id',
        'amount_total',
        'amount_total_discounts',
    ];

    protected $casts = [
        'id' => 'integer',
        'amount_each' => \Tipoff\Support\Casts\DiscountableValue::class,
        'amount_total' => \Tipoff\Support\Casts\DiscountableValue::class,
        'quantity' => 'integer',
        'tax' => 'integer',
        'meta_data' => 'json',
        'expires_at' => 'datetime',
        // Raw relation id access
        'sellable_id' => 'integer',
        'cart_id' => 'integer',
        'parent_id' => 'integer',
        'location_id' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (CartItem $item) {
            $item->expires_at = $item->expires_at ?? Carbon::now()->addMonths(3);    // TODO - move default to const or config

            /** @psalm-suppress TooManyArguments */
            CartItemCreated::dispatch($item);
        });

        static::updating(function (CartItem $item) {
            /** @psalm-suppress TooManyArguments */
            CartItemUpdated::dispatch($item);
        });

        static::saved(function (CartItem $item) {
            $item->load('cart');
            optional($item->cart)->touch();
        });

        static::deleting(function (CartItem $item) {
            /** @psalm-suppress TooManyArguments */
            CartItemRemoved::dispatch($item);
        });

        static::deleted(function (CartItem $item) {
            $item->load('cart');
            optional($item->cart)->touch();
        });
    }

    //region RELATIONSHIPS

    public function cart()
    {
        return $this->belongsTo(app('cart'));
    }

    //endregion

    //region SCOPES

    public function scopeActive(Builder $query, bool $isActive = true): Builder
    {
        return $query->where('expires_at', $isActive ? '>' : '<=', Carbon::now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $this->scopeActive($query, false);
    }

    //endregion

    //region PERMISSIONS

    public function scopeVisibleByEmailAddressId(Builder $query, int $emailAddressId): Builder
    {
        return $query->whereHas('cart', function (Builder $q) use ($emailAddressId) {
            $q->visibleByEmailAddressId($emailAddressId);
        });
    }

    public function isOwnerByEmailAddressId(int $emailAddressId): bool
    {
        $cart = Cart::activeCart($emailAddressId);

        return $this->cart_id === $cart->getId();
    }

    //endregion

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    //region INTERFACE IMPLEMENTATION

    public function getCart(): ?CartInterface
    {
        return $this->cart;
    }

    public function setQuantity(int $quantity): CartItemInterface
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function setDescription(string $description): CartItemInterface
    {
        $this->description = $description;

        return $this;
    }

    public function setAmountEach($amount): self
    {
        $this->amount_each = $amount;

        return $this->updateAmountTotal();
    }

    public function setTax(int $tax): CartItemInterface
    {
        $this->tax = $tax;

        return $this;
    }

    public function setLocationId(?int $locationId): CartItemInterface
    {
        if ($cart = $this->getCart()) {
            $cart->setLocationId($locationId);
        }

        $this->location_id = $locationId;

        return $this;
    }

    public function setTaxCode(?string $taxCode): CartItemInterface
    {
        $this->tax_code = $taxCode;

        return $this;
    }

    public function getExpiresAt(): Carbon
    {
        return $this->expires_at;
    }

    public function setExpiresAt(Carbon $expiresAt): CartItemInterface
    {
        Assert::that($expiresAt->isFuture())->true();
        $this->expires_at = $expiresAt;

        return $this;
    }

    public function setParentItem(?CartItemInterface $parent): CartItemInterface
    {
        Assert::that($parent->getId())->notNull();

        $this->parent()->associate($parent);

        return $this;
    }

    //endregion
}

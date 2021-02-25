<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Tipoff\Checkout\Traits\IsItem;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Events\Checkout\CartItemRemoved;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property Carbon expires_at
 * // Raw Relation ID
 * @property int|null cart_id
 * @property int|null order_item_id
 */
class CartItem extends BaseModel implements CartItemInterface
{
    use HasPackageFactory;
    use IsItem;

    protected $fillable = [
        'item_id',
        'description',
        'quantity',
        'expires_at',
    ];

    protected $touches = [
        'cart',
    ];

    protected $casts = [
        'id' => 'integer',
        'amount' => \Tipoff\Support\Casts\DiscountableValue::class,
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

        static::deleting(function (CartItem $item) {
            CartItemRemoved::dispatch($item);
        });
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct(array_merge([
            'expires_at' => Carbon::now()->addMonths(3),    // TODO - move default to const or config
        ], $attributes));
    }

    //region RELATIONSHIPS

    public function cart()
    {
        return $this->belongsTo(Cart::class);
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

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    //region INTERFACE IMPLEMENTATION

    public function getCart(): ?CartInterface
    {
        return $this->cart;
    }

    public function getRootItem(): ?CartItemInterface
    {
        $rootItem = $parentItem = $this->getParentItem();
        while ($parentItem && $parentItem->getParentItem()) {
            $rootItem = $parentItem;
            $parentItem = $parentItem->getParentItem();
        }

        return $rootItem;
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

    public function setAmount($amount): self
    {
        $this->amount = $amount;

        return $this;
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

    public function getParentItem(): ?CartItemInterface
    {
        return $this->parent;
    }

    public function setParentItem(?CartItemInterface $parent): CartItemInterface
    {
        Assert::that($parent->getId())->notNull();

        $this->parent()->associate($parent);

        return $this;
    }

    //endregion
}

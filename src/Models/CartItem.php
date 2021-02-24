<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Events\Checkout\CartItemRemoved;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Objects\DiscountableValue;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasPackageFactory;
use Tipoff\Support\Traits\HasUpdater;

/**
 * @property int|null id
 * @property string item_id
 * @property Sellable sellable
 * @property string description
 * @property int quantity
 * @property DiscountableValue amount
 * @property int tax
 * @property string|null tax_code
 * @property array|null meta_data
 * @property Carbon expires_at
 * @property Carbon created_at
 * @property Carbon updated_at
 * // Raw Relation ID
 * @property int|null cart_id
 * @property int|null parent_id
 * @property int|null sellable_id
 * @property string|null sellable_type
 * @property int|null order_item_id
 * @property int|null location_id
 * @property int|null creator_id
 * @property int|null updater_id
 */
class CartItem extends BaseModel implements CartItemInterface
{
    use HasPackageFactory;
    use HasCreator;
    use HasUpdater;

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
        'order_item_id' => 'integer',
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

    public function parent()
    {
        return $this->belongsTo(CartItem::class);
    }

    public function children()
    {
        return $this->hasMany(CartItem::class, 'parent_id', 'id');
    }

    public function orderItem()
    {
        return $this->hasOne(OrderItem::class);
    }

    public function sellable()
    {
        return $this->morphTo();
    }

    //endregion

    //region SCOPES

    public function scopeBySellableId(Builder $query, Sellable $sellable, string $itemId): Builder
    {
        return $query->where(function ($query) use ($sellable, $itemId) {
            $query->where('sellable_type', '=', $sellable->getMorphClass());
            $query->where('item_id', '=', $itemId);
        });
    }

    public function scopeActive(Builder $query, bool $isActive = true): Builder
    {
        return $query->where('expires_at', $isActive ? '>' : '<=', Carbon::now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $this->scopeActive($query, false);
    }

    public function scopeIsRootItem(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeIsChildItem(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    //endregion

    public function delete()
    {
        // Delete children - this is recursive
        $this->children()->get()->each->delete();

        return parent::delete();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    //region CartItemInterface Implementation

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

    public function getSellable(): Sellable
    {
        return $this->sellable;
    }

    public function getItemId(): string
    {
        return $this->item_id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): CartItemInterface
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): CartItemInterface
    {
        $this->description = $description;

        return $this;
    }

    public function getAmount(): DiscountableValue
    {
        return $this->amount;
    }

    public function setAmount($amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getLocationId(): ?int
    {
        return $this->location_id;
    }

    public function setLocationId(?int $locationId): CartItemInterface
    {
        if ($cart = $this->getCart()) {
            $cart->setLocationId($locationId);
        }

        $this->location_id = $locationId;

        return $this;
    }

    public function getTaxCode(): ?string
    {
        return $this->tax_code;
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

    public function getMetaData(?string $key, $default)
    {
        return Arr::get($this->meta_data, $key, $default);
    }

    public function setMetaData(?string $key, $value): CartItemInterface
    {
        $metaData = $this->meta_data ?? [];
        $this->meta_data = Arr::set($metaData, $key, $value);

        return $this;
    }
    //endregion
}

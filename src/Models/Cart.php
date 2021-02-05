<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tipoff\Checkout\Contracts\Model\CartInterface;
use Tipoff\Checkout\Contracts\Service\CheckoutService;
use Tipoff\Checkout\Contracts\Service\VouchersService;
use Tipoff\Checkout\Events\BookingOrderProcessed;
use Tipoff\Support\Models\BaseModel;

/**
 * @property int|null id
 * @property string code
 * @property int amount
 * @property int total_taxes
 * @property int total_fees
 * @property int total_deductions
 * @property Carbon expires_at
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
    use HasFactory;
    use SoftDeletes;

    /**
     * Voucher type used in partial redemptions.
     */
    const PARTIAL_REDEMPTION_VOUCHER_TYPE_ID = 7;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($cart) {
            $cart->generatePricing();

            if (empty($cart->total_deductions)) {
                $cart->total_deductions = 0;
            }
        });

        static::deleting(function ($cart) {
            $cart->releaseItemsHolds();
        });
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalAmountAttribute(): int
    {
        if ($this->total_deductions > $this->amount + $this->total_taxes + $this->total_fees) {
            return 0;
        }

        return $this->amount + $this->total_taxes + $this->total_fees - $this->total_deductions;
    }

    public function processOrder(Payment $payment): Order
    {
        if (! $this->canConvert()) {
            throw new \Exception('Cart not valid.');
        }

        // TODO - Transaction wrapper (either here or outer)
        $order = Order::create([
            'customer_id' => $payment->customer_id,
            'location_id' => $this->location_id,
            'amount' => $this->amount,
            'total_taxes' => $this->total_taxes,
            'total_fees' => $this->total_fees,
        ]);

        $this->order_id = $order->id;
        $this->save();

        foreach ($this->cartItems()->get() as $cartItem) {
            $cartItem->createBooking();
        }

        $payment->order_id = $order->id;
        $payment->save();

        app(VouchersService::class)->markVouchersAsUsed($this, $order->id);
        $amount = ($this->total_deductions - ($this->amount + $this->total_taxes + $this->total_fees));
        if ($amount > 0) {
            $order->partial_redemption_voucher_id = app(VouchersService::class)->issuePartialRedemptionVoucher($this, $amount);
            $order->save();
        }

        $order->refresh();

        $this->delete();

        event(new BookingOrderProcessed($order));
        // Event Listeners send confirmation email, update slot, block slot if private game. Will also need to send notification to staff.

        return $order;
    }

    public function updateTotalCartDeductions(): self
    {
        app(CheckoutService::class)->updateTotalCartDeductions($this);

        return $this;
    }

    public function getLocationId(): ?int
    {
        return $this->location_id;
    }

    public function getUserId(): ?int
    {
        return $this->location_id;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function canConvert(): bool
    {
        if (empty($this->amount)) {
            return false;
        }

        if (! $this->cartItems()->exists()) {
            return false;
        }

        if ($this->hasExpired()) {
            return false;
        }

        return true;
    }

    public function hasExpired()
    {
        $now = Carbon::now();

        return $this->expires_at->lt($now);
    }

    public function hasInRoomMonitors()
    {
        return $this->cartItems->contains(function (CartItem $item, $key) {
            return ($item->room->theme_id == 1 ||
                $item->room->theme_id == 2);
        });
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        if (empty($filters)) {
            return $query;
        }

        foreach ($filters as $filterKey => $filterValue) {
            switch ($filterKey) {
                case '':
                    // $query->where('', "");
                    break;
            }
        }

        return $query;
    }

    public function generatePricing(): self
    {
        $amount = 0;
        $fees = 0;
        $taxes = 0;

        if (! empty($this->cartItems()->exists())) {
            foreach ($this->cartItems()->get() as $item) {
                $amount += $item->amount;
                $fees += $item->total_fees;
                $taxes += $item->total_taxes;
            }
        }

        $this->amount = ($amount > 0) ? $amount : 0;
        $this->total_fees = $fees;
        $this->total_taxes = $taxes;

        return $this;
    }

    public function hasSlot(string $slotNumber): bool
    {
        return $this->cartItems()
            ->where('slot_number', $slotNumber)
            ->exists();
    }

    public function addItem(CartItem $cartItem): self
    {
        if (! empty($this->cartItems)) {
            $this->location_id = $cartItem->room->location_id;
        } else {
            if ($this->location_id = $cartItem->room->location_id) {
                throw new \Exception('Cust must contain items from single location.');
            }
        }
        $cartItem->cart()->associate($this);
        $cartItem->save();
        $this->save();

        return $this;
    }

    public function releaseItemsHolds(): self
    {
        $this->cartItems->each(function ($item) {
            $item->releaseHold();
        });

        return $this;
    }

    public function getExpiresIn(): int
    {
        return Carbon::now()->diffInSeconds($this->expires_at);
    }

    public function updateItemsHolds(): self
    {
        // TODO -- dependency on slot config!!!
        $this->expires_at = now()->addSeconds(config('services.slot.hold.lifetime', 600));

        $this->cartItems->each(function ($item) {
            $item->setHold($this->user_id);
        });

        return $this;
    }

    public function addSlot(string $slotNumber, int $participants, bool $isPrivate): CartItem
    {
        $item = CartItem::makeFromSlot($slotNumber, $participants, $isPrivate);
        $this->updateItemsHolds();

        $this->addItem($item);

        return $item;
    }

    public function removeSlot($slotNumber): bool
    {
        return $this->cartItems()
            ->where('slot_number', $slotNumber)
            ->first()
            ->delete();
    }

    public function isEmpty(): bool
    {
        return ! $this->cartItems()->exists();
    }

    public function scopeVisibleBy(Builder $query, $user)
    {
        return $query;
    }

    public function scopeActive(Builder $query)
    {
        return $query->whereDate('expires_at', '>', now())
            ->orWhere(function ($query) {
                $query->whereDate('expires_at', now())
                    ->whereTime('expires_at', '>', now());
            });
    }

    public function getTotalParticipants(): int
    {
        return (int) $this->cartItems()->sum('participants');
    }
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Tipoff\Support\Traits\HasPackageFactory;

class CartItem extends Model
{
    use HasPackageFactory;

    protected $fillable = [
        'amount',
        'total_taxes',
        'total_fees',
        'total_deductions',
        'slot_number',
        'participants',
        'is_private',
        'room_id',
        'rate_id',
        'tax_id',
        'fee_id',
    ];


    protected $touches = [
        'cart',
    ];

    protected $casts = [];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (CartItem $item) {
            if ($item->hasSlot()) {
                $item->setHold($item->cart->user_id);
            }

            if (empty($item->total_deductions)) {
                $item->total_deductions = 0;
            }

            $item->generatePricing();
        });

        static::deleting(function ($item) {
            $item->releaseHold();
        });

        static::deleted(function ($item) {
            tap($item->cart, function ($cart) {
                $cart->generatePricing();
                $cart->save();
            });
        });
    }

    /**
     * Create booking from item.
     *
     * @return Model
     */
    public function createBooking()
    {
        $slot = $this->createSlot();

        /** @psalm-suppress UndefinedMethod */
        return app('booking')::create([
            'order_id' => $this->cart->order_id,
            'slot_id' => $slot->id,
            'participants' => $this->participants,
            'is_private' => $this->is_private,
            'amount' => $this->amount,
            'total_taxes' => $this->total_taxes,
            'total_fees' => $this->total_fees,
            'rate_id' => $this->rate_id,
            'tax_id' => $this->tax_id,
            'fee_id' => $this->fee_id,
        ]);
    }

    /**
     * Get amount per participant.
     *
     * @return int
     */
    public function getAmountPerParticipant()
    {
        return (int) floor($this->amount / $this->participants);
    }

    /**
     * Scope a query to apply filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $filters array
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $filters)
    {
        if (empty($filters)) {
            return $query;
        }

        /**
         * TODO - implement or kill
        foreach ($filters as $filterKey => $filterValue) {
            switch ($filterKey) {
                case '':
                    // $query->where('', "");
                    break;
            }
        }
         */

        return $query;
    }

    public function room()
    {
        return $this->belongsTo(app('room'));
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function fee()
    {
        return $this->belongsTo(app('fee'));
    }

    public function rate()
    {
        return $this->belongsTo(app('rate'));
    }

    public function tax()
    {
        return $this->belongsTo(app('tax'));
    }

    public function getStartAtAttribute()
    {
        return $this->getSlot()->start_at;
    }

    public function getFormattedStartAttribute()
    {
        return $this->getSlot()->formatted_start;
    }

    /**
     * Check if slot has locks.
     *
     * @return bool
     */
    public function hasHold()
    {
        return $this->getSlot()->hasHold();
    }

    /**
     * Get hold for slot.
     *
     * @return object|null
     */
    public function getHold()
    {
        return $this->getSlot()->getHold();
    }

    /**
     * Relese slot hold.
     *
     * @return self
     */
    public function releaseHold()
    {
        $this->getSlot()->releaseHold();

        return $this;
    }

    /**
     * Create hold.
     *
     * @param int $userId
     * @param Carbon|null $expiresAt
     * @return self
     */
    public function setHold($userId, $expiresAt = null)
    {
        return $this->getSlot()->setHold($userId, $expiresAt);
    }

    /**
     * Check if cart item has slot.
     *
     * @return bool
     */
    public function hasSlot()
    {
        return ! empty($this->slot_number);
    }

    /**
     * Get slot model.
     */
    public function getSlot()
    {
        // TODO - move to services
        /** @psalm-suppress UndefinedMethod */
        return app('slot')::resolveSlot($this->slot_number);
    }

    /**
     * Generate amount, total_taxes and total_fees.
     *
     * @return self
     */
    public function generatePricing()
    {
        // TODO - move to services
        $this->amount = method_exists($this->rate, 'getAmount') ?  $this->rate->getAmount($this->participants, $this->is_private) : 0;
        $this->total_fees = method_exists($this->fee, 'generateTotalFeesByCartItem') ? $this->fee->generateTotalFeesByCartItem($this) : 0;
        $this->total_taxes = method_exists($this->rate, 'generateTotalTaxesByCartItem') ?   $this->tax->generateTotalTaxesByCartItem($this) : 0;

        return $this;
    }

    /**
     * Turn virtual slot to slot.
     */
    public function createSlot()
    {
        $slot = $this->getSlot();
        $slot->save();

        return $slot;
    }

    /**
     * Make item from slot number.
     *
     * @param string $slotNumber
     * @param int $participants
     * @param bool $isPrivate
     * @return self
     */
    public static function makeFromSlot($slotNumber, int $participants, bool $isPrivate)
    {
        // TODO - move to services
        $slot = call_user_func(class_basename(app('slot')), 'resolveSlot', $slotNumber);
        $rate = $slot->getRate();
        $tax = $slot->getTax();
        $fee = $slot->getFee();

        return self::make([
            'slot_number' => $slotNumber,
            'participants' => $participants,
            'is_private' => $isPrivate,
            'room_id' => $slot->room_id,
            'rate_id' => $rate->id,
            'tax_id' => $tax->id,
            'fee_id' => $fee->id,
        ]);
    }

    /**
     * Scope a query to rows visible by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $user array
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleBy($query, $user)
    {
        return $query;
    }
}

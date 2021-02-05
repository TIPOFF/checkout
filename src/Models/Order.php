<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int|null id
 * @property string order_number
 * @property int amount
 * @property int total_taxes
 * @property int total_fees
 * @property Carbon created_at
 * @property Carbon updated_at
 * // Raw Relation ID
 * @property int|null customer_id
 * @property int|null location_id
 * @property int|null creator_id
 */
class Order extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
        'order_number',
    ];

    protected $casts = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (auth()->check()) {
                $order->creator_id = auth()->id();
            }
            $order->generateOrderNumber();
        });

        static::saving(function ($order) {
            Assert::lazy()
                ->that($order->customer_id)->notEmpty('An order must belong to a customer.')
                ->that($order->location_id)->notEmpty('An order must belong to a location.')
                ->verifyNow();
        });
    }

    public function generateOrderNumber()
    {
        do {
            $token = Str::of(Carbon::now('America/New_York')->format('ymdB'))->substr(1, 7) . Str::upper(Str::random(2));
        } while (self::where('order_number', $token)->first()); //check if the token already exists and if it does, try again

        $this->order_number = $token;
    }

    public function getTotalAttribute()
    {
        $charge = ($this->amount + $this->total_fees + $this->total_taxes) / 100;

        return '$' . number_format($charge, 2, '.', ',');
    }

    public function hasPartialRedemptionVoucher()
    {
        return ! empty($this->partial_redemption_voucher_id);
    }

    public function customer()
    {
        return $this->belongsTo(config('checkout.model_class.customer'));
    }

    public function location()
    {
        return $this->belongsTo(config('checkout.model_class.location'));
    }

    public function bookings()
    {
        return $this->hasMany(config('checkout.model_class.booking'));
    }

    public function invoices()
    {
        return $this->hasMany(config('checkout.model_class.invoice'));
    }

    public function payments()
    {
        return $this->hasMany(config('checkout.model_class.payment'));
    }

    public function creator()
    {
        return $this->belongsTo(config('checkout.model_class.user'), 'creator_id');
    }

    public function notes()
    {
        return $this->morphMany(config('checkout.model_class.node'), 'noteable');
    }
}

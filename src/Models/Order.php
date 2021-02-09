<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasPackageFactory;

class Order extends BaseModel
{
    use HasPackageFactory;
    use HasCreator;

    protected $guarded = [
        'id',
        'order_number',
    ];

    protected $casts = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->generateOrderNumber();
        });

        static::saving(function ($order) {
            if (empty($order->customer_id)) {
                throw new \Exception('An order must belong to a customer.');
            }
            if (empty($order->location_id)) {
                throw new \Exception('An order must belong to a location.');
            }
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
        return $this->belongsTo(app('customer'));
    }

    public function location()
    {
        return $this->belongsTo(app('location'));
    }

    public function partialRedemptionVoucher()
    {
        return $this->belongsTo(app('voucher'), 'partial_redemption_voucher_id');
    }

    public function bookings()
    {
        return $this->hasMany(app('booking'));
    }

    public function purchasedVouchers()
    {
        return $this->hasMany(app('voucher'), 'purchase_order_id');
    }

    public function invoices()
    {
        return $this->hasMany(app('invoice'));
    }

    public function payments()
    {
        return $this->hasMany(app('payment'));
    }

    public function vouchers()
    {
        return $this->hasMany(app('voucher'));
    }

    public function discounts()
    {
        return $this->belongsToMany(app('discount'));
    }

    public function notes()
    {
        return $this->morphMany(app('note'), 'noteable');
    }
}

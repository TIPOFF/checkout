<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tipoff\Support\Contracts\Models\CustomerInterface;
use Tipoff\Support\Contracts\Models\LocationInterface;
use Tipoff\Support\Contracts\Models\OrderInterface;
use Tipoff\Support\Contracts\Models\VoucherInterface;
use Tipoff\Support\Contracts\Services\VoucherService;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property int|null id
 * @property string order_number
 * @property int amount
 * @property int total_taxes
 * @property int total_fees
 * @property Carbon created_at
 * @property Carbon updated_at
 * // Raw Relation ID
 * @property int|null partial_redemption_voucher_id
 * @property int|null customer_id
 * @property int|null location_id
 * @property int|null creator_id
 */
class Order extends BaseModel implements OrderInterface
{
    use HasPackageFactory;

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
            Assert::lazy()
                ->that($order->customer_id, 'customer_id')->notEmpty('An order must belong to a customer.')
                ->that($order->location_id, 'location_id')->notEmpty('An order must belong to a location.')
                ->verifyNow();
        });
    }

    public function generateOrderNumber(): self
    {
        do {
            $token = Str::of(Carbon::now('America/New_York')->format('ymdB'))->substr(1, 7) . Str::upper(Str::random(2));
        } while (self::where('order_number', $token)->first()); //check if the token already exists and if it does, try again

        $this->order_number = $token;

        return $this;
    }

    public function getTotalAttribute()
    {
        $charge = ($this->amount + $this->total_fees + $this->total_taxes) / 100;

        return '$' . number_format($charge, 2, '.', ',');
    }

    public function hasPartialRedemptionVoucher(): bool
    {
        return ! empty($this->partial_redemption_voucher_id);
    }

    public function getCustomer(): ?CustomerInterface
    {
        return $this->customer;
    }

    public function getLocation(): ?LocationInterface
    {
        return $this->location;
    }

    public function getPartialRedemptionVoucher(): ?VoucherInterface
    {
        return app(VoucherService::class)->getVoucher($this->partial_redemption_voucher_id);
    }

    public function customer()
    {
        return $this->belongsTo(app('customer'));
    }

    public function location()
    {
        return $this->belongsTo(app('location'));
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

    public function creator()
    {
        return $this->belongsTo(app('user'), 'creator_id');
    }

    public function notes()
    {
        return $this->morphMany(app('note'), 'noteable');
    }
}

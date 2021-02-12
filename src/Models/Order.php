<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tipoff\Checkout\Contracts\Models\OrderInterface;
use Tipoff\Checkout\Contracts\Models\VoucherInterface;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasCreator;
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
 * @property int|null customer_id
 * @property int|null location_id
 * @property int|null partial_redemption_voucher_id
 * @property int|null creator_id
 */
class Order extends BaseModel implements OrderInterface
{
    use HasPackageFactory;
    use HasCreator;

    protected $guarded = [
        'id',
        'order_number',
    ];

    protected $casts = [
        'id' => 'integer',
        'amount' => 'integer',
        'total_taxes' => 'integer',
        'total_fees' => 'integer',
        'customer_id' => 'integer',
        'location_id' => 'integer',
        'partial_redemption_voucher_id' => 'integer',
        'creator_id' => 'integer',
    ];

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

    public function getPartialRedemptionVoucher(): ?VoucherInterface
    {
        /** @var VoucherInterface|null $result */
        $result = findModel(VoucherInterface::class, $this->partial_redemption_voucher_id);

        return $result;
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

    /******************************
     * OrderInterface Implementation
     ******************************/

    public function getCustomerId(): ?int
    {
        return $this->customer_id;
    }

    public function getOrderNumber(): string
    {
        return $this->order_number;
    }

    public function getLocation()
    {
        return $this->location;
    }
}

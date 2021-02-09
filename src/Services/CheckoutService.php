<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services;

use Illuminate\Database\Eloquent\Model;
use Tipoff\Checkout\Models\Cart;

class CheckoutService
{
    /* Tax & fee application methods */
    const APPLICATION_ORDER = 'order';
    const APPLICATION_EACH = 'each';
    const APPLICATION_PRODUCT = 'product';
    const APPLICATION_BOOKING = 'booking';
    const APPLICATION_PARTICIPANT = 'participant';

    /**
     * Check if discount/voucher code is valid.
     *
     * @param string $code
     * @return mixed
     * @psalm-suppress UndefinedMethod
     */
    public function findDeductionByCode($code)
    {
        if (strlen($code) < 4 && strlen($code) > 16) {
            return false;
        }

        if ($voucher = app('voucher')::validAt()->where('code', $code)->first()) {
            return $voucher;
        }

        if ($discount = app('discount')::available()->where('code', $code)->first()) {
            return $discount;
        }

        return false;
    }

    /**
     * Apply discount or voucher code to cart.
     *
     * @param string $code
     * @return Cart
     */
    public function applyCodeToCart($code, $cart)
    {
        $deduction = $this->findDeductionByCode($code);

        if (empty($deduction)) {
            throw new \Exception("Code {$code} is invalid.");
        }

        $cart->applyDeduction($deduction);

        return $cart;
    }

    /**
     * Apply deduction to cart.
     *
     * @param Model $deduction
     * @param Cart $cart
     * @return Cart
     */
    public function applyDeductionToCart($deduction, $cart)
    {
        // TODO - temporary ugliness, will be moved into discounts/vouchers services
        if (is_a($deduction, app('discount'))) {
            $cart->applyDiscount($deduction);
        }

        if (is_a($deduction, app('voucher'))) {
            $cart->applyVoucher($deduction);
        }

        return $cart;
    }

    /**
     * Apply discount to cart.
     *
     * @param Model $discount
     * @param Cart $cart
     * @return Cart
     */
    public function applyDiscountToCart($discount, $cart)
    {
        if (! in_array($discount->applies_to, [self::APPLICATION_ORDER, self::APPLICATION_PARTICIPANT])) {
            return $cart;
        }

        $cart->discounts()->syncWithoutDetaching([$discount->id]);
        $cart->total_deductions = $this->calculateCartDeductions($cart);
        $cart->save();

        return $cart;
    }

    /**
     * Issue partial redemption voucher when total discounts are higher than total amount.
     *
     * @param  Cart $cart
     * @return Model
     */
    public function issueCartPartialRedemptionVoucher($cart)
    {
        $order = $cart->order;

        /** @psalm-suppress UndefinedMethod */
        $voucher = app('voucher')::create([
            'location_id' => $cart->location_id,
            'customer_id' => $cart->user_id,
            'voucher_type_id' => Cart::PARTIAL_REDEMPTION_VOUCHER_TYPE_ID,
            'redeemable_at' => now(),
            'amount' => $cart->total_deductions - ($cart->amount + $cart->total_taxes + $cart->total_fees),
            'expires_at' => $cart->vouchers()->first()->expires_at,
            'creator_id' => $cart->user_id,
            'updater_id' => $cart->user_id,
        ]);

        $order->partial_redemption_voucher_id = $voucher->id;
        $order->save();

        return $voucher;
    }

    /**
     * Calculate all cart deductions including items deductions.
     *
     * @return int
     */
    public function calculateCartDeductions($cart)
    {
        $totalDeductions = 0;

        foreach ($cart->cartItems as $cartItem) {
            $totalDeductions += $cartItem->total_deductions;
        }

        $totalDeductions += $this->calculateCartVouchers($cart);
        $totalDeductions += $this->calculateCartDiscounts($cart);

        return $totalDeductions;
    }

    /**
     * Calculate total amount of cart vouchers (not counting cart items).
     *
     * @param Cart $cart
     * @return int
     */
    public function calculateCartVouchers($cart)
    {
        $total = 0;

        foreach ($cart->vouchers()->get() as $voucher) {
            if ($voucher->amount > 0) {
                $total += $voucher->amount;
            }
        }

        return $total;
    }

    /**
     * Calculate total amount of cart discounts (not counting cart items).
     *
     * @param Cart $cart
     * @return int
     */
    public function calculateCartDiscounts($cart)
    {
        $total = 0;

        foreach ($cart->discounts()->get() as $discount) {
            if ($discount->amount > 0) {
                switch ($discount->applies_to) {
                    case self::APPLICATION_ORDER:
                        $discount = $discount->amount;

                        break;
                    case self::APPLICATION_PARTICIPANT:
                        $discount = $discount->amount * $cart->getTotalParticipants();

                        break;
                }
            }

            $total += $discount;
        }

        return $total;
    }

    /**
     * Apply voucher to cart.
     *
     * @param Model $voucher
     * @param Cart $cart
     * @return Cart
     */
    public function applyVoucherToCart($voucher, $cart)
    {
        if ($voucher->participants > 0) {
            throw new \Exception('Participants vouchers not supported yet.');
        }

        if (! empty($voucher->redeemed_at)) {
            throw new \Exception('Voucher already used.');
        }

        if ($voucher->amount > 0) {
            $cart->vouchers()->syncWithoutDetaching([$voucher->id]);
            $cart->total_deductions = $this->calculateCartDeductions($cart);
            $cart->save();
        }

        return $cart;
    }

    /**
     * Add percentage to amount.
     *
     * Ex. 20%, 200 => 240
     *
     * @param float $percentage
     * @param int $amount
     * @return int
     */
    public function addPercentageToAmount($percentage, $amount)
    {
        return $amount + ((int)  ($amount * ($percentage / 100)));
    }

    /**
     * Increase amount by value and multiply.
     *
     * @param int $amount
     * @param int $units
     * @param int $value
     * @return int
     */
    public function increaseAmountMultiplied($amount, $units, $value)
    {
        return $amount + ($value * $units);
    }
}

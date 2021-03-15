<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Exceptions\PaymentNotAvailableException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Contracts\Payment\PaymentInterface;

class Purchase
{
    public function __invoke(Cart $cart, $paymentMethod): Order
    {

        /** @var PaymentInterface $service */
        $service = findService(PaymentInterface::class);
        throw_unless($service, PaymentNotAvailableException::class);

        return DB::transaction(function () use ($service, $cart, $paymentMethod) {
            $cart->verifyPurchasable();

            $payment = $service::createPayment(
                $cart->getLocationId(),
                $cart->getUser(),
                $cart->getBalanceDue(),
                $paymentMethod,
                'online'
            );

            $order = $cart->completePurchase();
            $payment->attachOrder($order);

            return $order;
        });
    }
}

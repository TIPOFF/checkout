<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\View\Components\Order;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Objects\DiscountableValue;

class OrderTotalTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function order_with_values()
    {
        $order = \Mockery::mock(Order::class)->makePartial();
        $order->shouldReceive('getFeeTotal')
            ->once()
            ->andReturn(new DiscountableValue(234));
        $order->tax = 123;
        $order->item_amount_total = new DiscountableValue(3345);

        $view = $this->blade(
            '<x-tipoff-order-total :order="$order" />',
            ['order' => $order]
        );

        $view->assertSee('Taxes: $1.23');
        $view->assertSee('Fees: $2.34');
        $view->assertSee('Total: $34.68');
    }
}

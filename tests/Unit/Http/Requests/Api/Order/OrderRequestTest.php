<?php


declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Requests\Api\Order;

use Tipoff\Checkout\Http\Requests\Api\Order\OrderRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Checkout\Models\Order;

class OrderRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected $orderRequest;

    public function setUp(): void
    {
        parent::setUp();

        $this->orderRequest = $this->getMockForAbstractClass(OrderRequest::class);
    }

    public function test_get_model_class()
    {
        $this->assertEquals(Order::class, $this->orderRequest->getModelClass());
    }
}

<?php


declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Requests\Api\OrderItem;

use Tipoff\Checkout\Http\Requests\Api\OrderItem\OrderItemRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\TestCase;

class OrderItemRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected $orderItemRequest;

    public function setUp(): void
    {
        parent::setUp();

        $this->orderItemRequest = $this->getMockForAbstractClass(OrderItemRequest::class);
    }

    public function test_get_model_class()
    {
        $this->assertEquals(OrderItem::class, $this->orderItemRequest->getModelClass());
    }
}

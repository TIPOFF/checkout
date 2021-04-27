<?php


declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Requests\Api\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Http\Requests\Api\Cart\CartRequest;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\TestCase;

class CartRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected $cartRequest;

    public function setUp(): void
    {
        parent::setUp();

        $this->cartRequest = $this->getMockForAbstractClass(CartRequest::class);
    }

    public function test_get_model_class()
    {
        $this->assertEquals(Cart::class, $this->cartRequest->getModelClass());
    }
}

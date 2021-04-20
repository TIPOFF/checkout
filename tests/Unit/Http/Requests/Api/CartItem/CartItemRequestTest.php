<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Requests\Api\CartItem;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Checkout\Http\Requests\Api\CartItem\CartItemRequest;

class CartItemRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected $cartItemRequest;

    public function setUp(): void
    {
        parent::setUp();

        $this->cartItemRequest = $this->getMockForAbstractClass(CartItemRequest::class);
    }

    public function test_get_model_class()
    {
        $this->assertEquals(CartItem::class, $this->cartItemRequest->getModelClass());
    }

    public function test_get_email_address_id_return_null()
    {
        $this->assertNull($this->cartItemRequest->getEmailAddressId());
    }
}

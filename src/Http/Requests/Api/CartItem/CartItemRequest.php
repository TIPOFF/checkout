<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Api\CartItem;

use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Http\Requests\BaseApiRequest;

abstract class CartItemRequest extends BaseApiRequest
{
    public function getModelClass(): string
    {
        return CartItem::class;
    }

    public function rules()
    {
        return [];
    }
}

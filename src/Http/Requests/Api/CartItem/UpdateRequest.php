<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Api\CartItem;

class UpdateRequest extends CartItemRequest
{
    public function rules()
    {
        return [
            'quantity' => [ 'required', 'integer', 'min:1' ],
        ];
    }
}

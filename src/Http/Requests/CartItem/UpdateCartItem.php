<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\CartItem;

class UpdateCartItem extends CartItemRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->authorizeAction('update');
    }
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\CartItem;

class StoreCartItem extends CartItemRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => [
                'required',
                'in:bookings',
            ],
            'slot_number' => [
                'required',
            ],
            'participants' => [
                'required',
            ],
            'is_private' => [
                'required',
            ],
        ];
    }
}

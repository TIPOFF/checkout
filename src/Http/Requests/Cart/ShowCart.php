<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Cart;

class ShowCart extends CartRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user();
    }
}

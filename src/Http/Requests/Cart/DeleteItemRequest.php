<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Tipoff\Authorization\Traits\UsesTipoffAuthentication;

class DeleteItemRequest extends FormRequest
{
    use UsesTipoffAuthentication;

    public function rules()
    {
        return [
            'id' => 'required|exists:cart_items',
        ];
    }
}

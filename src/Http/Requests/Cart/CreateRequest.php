<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email',
        ];
    }
}

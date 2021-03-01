<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Cart;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Http\Requests\BaseApiRequest;

abstract class CartRequest extends BaseApiRequest
{
    public function getModelClass(): string
    {
        return Cart::class;
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }
}

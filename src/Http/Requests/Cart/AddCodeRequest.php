<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Tipoff\Authorization\Traits\UsesTipoffAuthentication;
use Tipoff\Checkout\Rules\AdjustmentCode;

class AddCodeRequest extends FormRequest
{
    use UsesTipoffAuthentication;

    public function rules()
    {
        return [
            'code' => ['required', 'string', new AdjustmentCode(),]
        ];
    }
}

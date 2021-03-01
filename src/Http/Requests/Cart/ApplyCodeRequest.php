<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Cart;

use Tipoff\Checkout\Rules\AdjustmentCode;

/**
 * @property string code
 */
class ApplyCodeRequest extends CartRequest
{
    public function rules()
    {
        return [
            'code' => [ 'required', 'string', new AdjustmentCode()],
        ];
    }
}

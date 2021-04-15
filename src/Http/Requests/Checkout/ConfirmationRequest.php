<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Tipoff\Authorization\Traits\UsesTipoffAuthentication;

class ConfirmationRequest extends FormRequest
{
    public function rules()
    {
        return [];
    }
}

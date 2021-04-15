<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Tipoff\Authorization\Traits\UsesTipoffAuthentication;

class ShowRequest extends FormRequest
{
    public function rules()
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Api\Order;

use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Http\Requests\BaseApiRequest;

abstract class OrderRequest extends BaseApiRequest
{
    public function getModelClass(): string
    {
        return Order::class;
    }

    public function rules()
    {
        return [];
    }
}

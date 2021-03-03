<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Api\OrderItem;

use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Support\Http\Requests\BaseApiRequest;

abstract class OrderItemRequest extends BaseApiRequest
{
    public function getModelClass(): string
    {
        return OrderItem::class;
    }

    public function rules()
    {
        return [];
    }
}

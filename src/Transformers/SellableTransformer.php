<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Transformers\BaseTransformer;

class SellableTransformer extends BaseTransformer
{
    protected $defaultIncludes = [
    ];

    protected $availableIncludes = [
    ];

    public function transform(Sellable $sellable)
    {
        return [
            'id' => $sellable->getId(),
            'class' => $sellable->getMorphClass(),
            'description' => $sellable->getDescription(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Rules;

use Illuminate\Contracts\Validation\Rule;
use Tipoff\Checkout\Services\Cart\FindAdjustmentCode;

class AdjustmentCode implements Rule
{
    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        return app(FindAdjustmentCode::class)($value) !== null;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'Code not found';
    }
}

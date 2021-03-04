<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Api\CartItem;

use Tipoff\Support\Contracts\Sellable\Sellable;

class StoreRequest extends CartItemRequest
{
    public function rules()
    {
        return [
            'sellable_type' => [ 'required', 'string' ],
            'sellable_id' => [ 'required', 'integer' ],
            'item_id' => [ 'required', 'string', ],
            'amount' => [ 'required', 'integer', 'min:1' ],
            'quantity' => [ 'nullable', 'integer', 'min:1' ],
            'location_d' => [ 'nullable', 'integer'],
            'tax_code' => [ 'nullable', 'string'],
            'expires_at' => [ 'nullable', 'date', 'after:now'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sellable = $this->sellable_type;
            if (! class_exists($sellable)) {
                $validator->errors()->add('sellable_type', 'Sellable type is not defined.');

                return;
            }

            if (! (new \ReflectionClass($sellable))->implementsInterface(Sellable::class)) {
                $validator->errors()->add('sellable_type', 'Type is not sellable.');

                return;
            }

            if (! $sellable::query()->find($this->sellable_id)) {
                $validator->errors()->add('sellable_id', 'Sellable item not found.');

                return;
            }
        });
    }
}

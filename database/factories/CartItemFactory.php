<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tipoff\Checkout\Models\CartItem;

class CartItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
        ];
    }
}

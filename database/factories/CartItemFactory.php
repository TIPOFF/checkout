<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tipoff\Checkout\Models\Cart;
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
            'cart_id' => randomOrCreate(Cart::class),
            'slot_number' => $this->faker->word,
            'participants' => $this->faker->numberBetween(1, 10),
            'is_private' => $this->faker->boolean,
            'room_id' => randomOrCreate(app('room')),
            'rate_id' => randomOrCreate(app('rate')),
            'tax_id' => randomOrCreate(app('tax')),
            'fee_id' => randomOrCreate(app('fee')),
            'creator_id' => randomOrCreate(app('user')),
            'updater_id' => randomOrCreate(app('user')),
        ];
    }
}

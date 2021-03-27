<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tipoff\Checkout\Models\Cart;

class CartFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email_address_id'    => randomOrCreate(app('email_address')),
            'creator_id' => randomOrCreate(app('user')),
            'updater_id' => randomOrCreate(app('user')),
        ];
    }
}

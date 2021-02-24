<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Contracts\Sellable\Sellable;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'customer_id'  => randomOrCreate(app('customer')),
            'location_id'  => randomOrCreate(app('location')),
            'amount'       => $this->faker->numberBetween(100, 40000),
            'total_taxes'  => $this->faker->numberBetween(10, 5000),
            'total_fees'   => $this->faker->numberBetween(10, 5000),
            'creator_id'   => randomOrCreate(app('user')),
            'updater_id'   => randomOrCreate(app('user')),
        ];
    }
}

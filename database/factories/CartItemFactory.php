<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Objects\DiscountableValue;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition()
    {
        return [
            'cart_id' => randomOrCreate(Cart::class),
            'item_id' => $this->faker->unique()->asciify('********'),
            'description' => $this->faker->words(3, true),
            'quantity' => $this->faker->optional(0.5, 1)->numberBetween(1, 5),
            'amount_each' => (new DiscountableValue($this->faker->numberBetween(1000, 5000)))
                ->addDiscounts($this->faker->optional(0.5, 0)->numberBetween(500, 1000)),
            'tax' => $this->faker->optional(0.5, 0)->numberBetween(500, 1000),
            'location_id' => $this->faker->optional()->passthrough(randomOrCreate(app('location'))),
            'tax_code' => $this->faker->optional()->asciify('********'),
            'creator_id' => randomOrCreate(app('user')),
            'updater_id' => randomOrCreate(app('user')),
        ];
    }

    public function withSellable(Model $sellable): self
    {
        return $this->state(function (array $attributes) use ($sellable) {
            return [
                'sellable_type' => $sellable->getMorphClass(),
                'sellable_id' => $sellable->getKey(),
            ];
        });
    }

    public function active(bool $isActive = true): self
    {
        return $this->state(function (array $attributes) use ($isActive) {
            return [
                'expires_at' => $isActive ? Carbon::now()->addDays(3) : Carbon::now()->subDays(3),
            ];
        });
    }
}

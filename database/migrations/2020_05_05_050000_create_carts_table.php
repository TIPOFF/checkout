<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tipoff\Checkout\Models\Order;

class CreateCartsTable extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(app('user'));

            $table->foreignIdFor(Order::class)->nullable();

            // Pair is handled as cast to `DiscountableValue`
            $table->unsignedInteger('shipping')->default(0);
            $table->unsignedInteger('shipping_discounts')->default(0);

            // Additional cart-level discounts not reflected in cart item discounts
            $table->unsignedInteger('discounts')->default(0);

            // Accumulated cart-level credits from vouchers pending redemption
            $table->unsignedInteger('credits')->default(0);

            // Direct calculation from sum(amount_total) sum(amount_total_discounts) in cart_items
            $table->unsignedInteger('item_amount_total')->default(0);
            $table->unsignedInteger('item_amount_total_discounts')->default(0);

            // Direct calculation from sum(tax) in cart_items
            $table->unsignedInteger('tax')->default(0);

            // Direct calculation as unique(location_id) from cart_items
            $table->unsignedInteger('location_id')->nullable();
            $table->foreignIdFor(app('user'), 'creator_id')->nullable();
            $table->foreignIdFor(app('user'), 'updater_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}

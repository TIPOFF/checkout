<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->index()->unique(); // Generated by system. This is identifier used to communicate with customers about their order.
            $table->foreignIdFor(app('user'));

            // TODO - TBD - how to handle linkage for shipping address
            // TODO - TBD - how to integrate w/statuses

            $table->unsignedInteger('shipping')->default(0);
            $table->unsignedInteger('shipping_discounts')->default(0);

            // Additional order-level discounts not reflected in order item discounts
            $table->unsignedInteger('discounts')->default(0);

            // Direct calculation from sum(amount)/sum(amount_discounts) in order_items
            $table->unsignedInteger('item_amount')->default(0);
            $table->unsignedInteger('item_amount_discounts')->default(0);

            // Direct calculation from sum(tax) in order_items
            $table->unsignedInteger('tax')->default(0);

            $table->unsignedInteger('location_id')->nullable(); // Due to accounting constraints when using tipoff/locations package, orders must be at a location and cannot have bookings for multiple locations.
            $table->foreignIdFor(app('user'), 'creator_id');
            $table->foreignIdFor(app('user'), 'updater_id');
            $table->timestamps();
        });
    }
}

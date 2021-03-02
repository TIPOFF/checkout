<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;

class CreateOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Order::class);
            $table->foreignIdFor( OrderItem::class, 'parent_id')->nullable();

            $table->morphs('sellable');

            // Opaque item identifier provided by Sellable
            $table->string('item_id');
            // User friendly description for cart line item
            $table->string('description');
            $table->unsignedInteger('quantity');

            // Pair is handled as cast to `DiscountableValue`
            $table->unsignedInteger('amount_each')->default(0);
            $table->unsignedInteger('amount_each_discounts')->default(0);

            $table->unsignedInteger('amount_total')->default(0);
            $table->unsignedInteger('amount_total_discounts')->default(0);

            $table->unsignedInteger('tax')->default(0);

            $table->unsignedInteger('location_id')->nullable();
            $table->string('tax_code')->nullable();
            $table->json('meta_data')->nullable();

            $table->foreignIdFor(app('user'), 'creator_id');
            $table->foreignIdFor(app('user'), 'updater_id');
            $table->timestamps();
        });
    }
}

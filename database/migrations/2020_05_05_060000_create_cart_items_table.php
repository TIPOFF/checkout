<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tipoff\Checkout\Models\Cart;

class CreateCartItemsTable extends Migration
{
    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cart::class);
            $table->string('slot_number'); // Not a foreignId field since it can reference slot_number on virtual slots not yet stored in database
            $table->unsignedTinyInteger('participants');
            $table->boolean('is_private')->default(false);
            $table->unsignedInteger('amount'); // Amount is in cents.
            $table->unsignedInteger('total_taxes'); // Taxes are in cents.
            $table->unsignedInteger('total_fees'); // Processing Fees are in cents.
            $table->unsignedInteger('total_deductions'); // Deductions are in cents.
            $table->foreignIdFor(config('checkout.model_class.room'));
            $table->foreignIdFor(config('checkout.model_class.rate'));
            $table->foreignIdFor(config('checkout.model_class.tax'));
            $table->foreignIdFor(config('checkout.model_class.fee'));
            $table->timestamps();
        });
    }
}

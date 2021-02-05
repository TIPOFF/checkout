<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(config('checkout.model_class.user'));
            $table->unsignedInteger('order_id')->nullable();
            $table->foreignIdFor(config('checkout.model_class.location'))->nullable();
            $table->unsignedInteger('amount'); // Amount is in cents.
            $table->unsignedInteger('total_taxes'); // Taxes is in cents.
            $table->unsignedInteger('total_fees'); // Processing Fees are in cents.
            $table->unsignedInteger('total_deductions'); // Deduction Amounts are in cents.
            $table->dateTime('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}

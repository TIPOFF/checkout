<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Tipoff\Checkout\Enums\OrderStatus;
use Tipoff\Statuses\Models\Status;

class AddOrderStatuses extends Migration
{
    public function up()
    {
        Status::publishStatuses(OrderStatus::statusType(), OrderStatus::getValues());
    }
}

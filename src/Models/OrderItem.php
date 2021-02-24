<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasPackageFactory;
use Tipoff\Support\Traits\HasUpdater;

class OrderItem extends BaseModel
{
    use HasPackageFactory;
    use HasCreator;
    use HasUpdater;
}

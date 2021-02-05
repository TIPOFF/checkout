<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Tipoff\Support\Models\TestModelStub;

class Tax extends Model
{
    use TestModelStub;

    protected $guarded = [
        'id',
    ];
}

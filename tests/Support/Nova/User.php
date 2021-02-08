<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Support\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Resource;

class User extends Resource
{
    public static $model = \App\Model\User::class;

    public function fields(Request $request)
    {
        // TODO: Implement fields() method.
    }
}

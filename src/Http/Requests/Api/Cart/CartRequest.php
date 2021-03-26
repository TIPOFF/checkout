<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Api\Cart;

use Illuminate\Support\Facades\Auth;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Http\Requests\BaseApiRequest;

abstract class CartRequest extends BaseApiRequest
{
    public function getModelClass(): string
    {
        return Cart::class;
    }

    public function getEmailAddressId(): ?int
    {
        if (Auth::guard('email')->check()) {
            return (int) Auth::guard('email')->id();
        }

        if (Auth::guard('web')->check()) {
            /** @var User $user */
            $user = Auth::guard('web')->user();

            $emailAddress = $user->getPrimaryEmailAddress();

            return $emailAddress ? $emailAddress->id : null;
        }

        return null;
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Contracts\Models\UserInterface;

class CartItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(UserInterface $user): bool
    {
        return true;
    }

    public function view(UserInterface $user, CartItem $cartItem): bool
    {
        return $cartItem->isOwner($user) || ($user->hasPermissionTo('view cart items') ? true : false);
    }

    public function create(UserInterface $user): bool
    {
        return true;
    }

    public function update(UserInterface $user, CartItem $cartItem): bool
    {
        return $cartItem->isOwner($user) || ($user->hasPermissionTo('update cart items') ? true : false);
    }

    public function delete(UserInterface $user, CartItem $cartItem): bool
    {
        return $cartItem->isOwner($user) || ($user->hasPermissionTo('delete cart items') ? true : false);
    }

    public function restore(UserInterface $user, CartItem $cartItem): bool
    {
        return false;
    }

    public function forceDelete(UserInterface $user, CartItem $cartItem): bool
    {
        return false;
    }
}

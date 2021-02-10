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
        return true;
    }

    public function create(UserInterface $user): bool
    {
        return true;
    }

    public function update(UserInterface $user, CartItem $cartItem): bool
    {
        return true;
    }

    public function delete(UserInterface $user, CartItem $cartItem): bool
    {
        return true;
    }

    public function restore(UserInterface $user, CartItem $cartItem): bool
    {
        return true;
    }

    public function forceDelete(UserInterface $user, CartItem $cartItem): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Contracts\Authorization\UserInterface;

class CartPolicy
{
    use HandlesAuthorization;

    public function viewAny(UserInterface $user): bool
    {
        return false;
    }

    public function view(UserInterface $user, Cart $cart): bool
    {
        return true;
    }

    public function create(UserInterface $user): bool
    {
        return true;
    }

    public function update(UserInterface $user, Cart $cart): bool
    {
        return false;
    }

    public function delete(UserInterface $user, Cart $cart): bool
    {
        return true;
    }

    public function restore(UserInterface $user, Cart $cart): bool
    {
        return false;
    }

    public function forceDelete(UserInterface $user, Cart $cart): bool
    {
        return false;
    }
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Locations\Traits\HasLocationPermissions;
use Tipoff\Support\Contracts\Models\UserInterface;

class CartPolicy
{
    use HandlesAuthorization;
    use HasLocationPermissions;

    public function viewAny(UserInterface $user): bool
    {
        return $user->hasPermissionTo('view carts') ? true : false;
    }

    public function view(UserInterface $user, Cart $cart): bool
    {
        /** @psalm-suppress  UndefinedMagicPropertyFetch */
        return $cart->isOwner($user) || $this->hasLocationPermission($user, 'view carts', $cart->location_id);
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

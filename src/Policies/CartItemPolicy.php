<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Locations\Traits\HasLocationPermissions;
use Tipoff\Support\Contracts\Models\UserInterface;

class CartItemPolicy
{
    use HandlesAuthorization;
    use HasLocationPermissions;

    public function viewAny(UserInterface $user): bool
    {
        return true;
    }

    public function view(UserInterface $user, CartItem $cartItem): bool
    {
        return $cartItem->isOwner($user) || $this->hasLocationPermission($user, 'view cart items', $cartItem->location_id);
    }

    public function create(UserInterface $user): bool
    {
        return true;
    }

    public function update(UserInterface $user, CartItem $cartItem): bool
    {
        return $cartItem->isOwner($user);
    }

    public function delete(UserInterface $user, CartItem $cartItem): bool
    {
        return $cartItem->isOwner($user);
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

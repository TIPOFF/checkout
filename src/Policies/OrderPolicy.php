<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tipoff\Checkout\Models\Order;
use Tipoff\Locations\Traits\HasLocationPermissions;
use Tipoff\Support\Contracts\Models\UserInterface;

class OrderPolicy
{
    use HandlesAuthorization;
    use HasLocationPermissions;

    public function viewAny(UserInterface $user): bool
    {
        return true;
    }

    public function view(UserInterface $user, Order $order): bool
    {
        /** @psalm-suppress  UndefinedMagicPropertyFetch */
        return $order->isOwner($user) || $this->hasLocationPermission($user, 'view orders', $order->location_id);
    }

    public function create(UserInterface $user): bool
    {
        return false;
    }

    public function update(UserInterface $user, Order $order): bool
    {
        return false;
    }

    public function delete(UserInterface $user, Order $order): bool
    {
        return false;
    }

    public function restore(UserInterface $user, Order $order): bool
    {
        return false;
    }

    public function forceDelete(UserInterface $user, Order $order): bool
    {
        return false;
    }
}

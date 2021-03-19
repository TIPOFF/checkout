<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Locations\Traits\HasLocationPermissions;
use Tipoff\Support\Contracts\Models\UserInterface;

class OrderItemPolicy
{
    use HandlesAuthorization;
    use HasLocationPermissions;

    public function viewAny(UserInterface $user): bool
    {
        return true;
    }

    public function view(UserInterface $user, OrderItem $orderItem): bool
    {
        return $orderItem->isOwner($user) || $this->hasLocationPermission($user, 'view order items', $orderItem->location_id);
    }

    public function create(UserInterface $user): bool
    {
        return false;
    }

    public function update(UserInterface $user, OrderItem $orderItem): bool
    {
        return false;
    }

    public function delete(UserInterface $user, OrderItem $orderItem): bool
    {
        return false;
    }

    public function restore(UserInterface $user, OrderItem $orderItem): bool
    {
        return false;
    }

    public function forceDelete(UserInterface $user, OrderItem $orderItem): bool
    {
        return false;
    }
}

<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Contracts\Models\UserInterface;

class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(UserInterface $user): bool
    {
        return $user->hasPermissionTo('view orders') ? true : false;
    }

    public function view(UserInterface $user, Order $order): bool
    {
        return $order->isOwner($user) || ($user->hasPermissionTo('view orders') ? true : false);
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

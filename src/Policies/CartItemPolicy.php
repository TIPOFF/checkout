<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Authorization\Models\User;
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
        return $this->hasLocationPermission($user, 'view cart items', $cartItem->location_id);
    }

    public function create(UserInterface $user): bool
    {
        return true;
    }

    public function update($userOrEmailAddress, CartItem $cartItem): bool
    {
        return $cartItem->isOwnerByEmailAddressId($this->getEmailAddressId($userOrEmailAddress));
    }

    public function delete($userOrEmailAddress, CartItem $cartItem): bool
    {
        return $cartItem->isOwnerByEmailAddressId($this->getEmailAddressId($userOrEmailAddress));
    }

    public function restore(UserInterface $user, CartItem $cartItem): bool
    {
        return false;
    }

    public function forceDelete(UserInterface $user, CartItem $cartItem): bool
    {
        return false;
    }

    private function getEmailAddressId($userOrEmailAddress): ?int
    {
        if ($userOrEmailAddress instanceof User) {
            return $userOrEmailAddress->email_addresses->id ?? null;
        }

        if ($userOrEmailAddress instanceof EmailAddress) {
            return $userOrEmailAddress->id;
        }

        return null;
    }
}

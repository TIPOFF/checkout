<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Model;

interface CartInterface
{
    public function getId(): ?int;

    public function getLocationId(): ?int;

    public function getUserId(): ?int;

    public function getTotalParticipants(): int;

    public function updateTotalCartDeductions(): CartInterface;
}

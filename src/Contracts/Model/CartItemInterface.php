<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Model;

interface CartItemInterface
{
    public function getRateId(): ?int;

    public function getFeeId(): ?int;

    public function getRoomId(): ?int;

    public function getTaxId(): ?int;
}

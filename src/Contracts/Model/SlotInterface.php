<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Model;

use Carbon\Carbon;

interface SlotInterface
{
    public function getId(): int;

    public function getRoomId(): ?int;

    public function getRateId(): ?int;

    public function getTaxId(): ?int;

    public function getFeeId(): ?int;

    public function setHold(int $userId, ?Carbon $expiresAt = null): void;

    public function releaseHold(): void;

    public function getHold(): ?object;

    public function hasHold(): bool;

    public function getStartAt(): Carbon;

    public function getFormattedStart(): string;
}

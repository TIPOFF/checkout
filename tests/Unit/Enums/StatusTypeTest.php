<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Enums;

use ReflectionClass;
use Tipoff\Checkout\Enums\StatusType;
use Tipoff\Checkout\Tests\TestCase;

class StatusTypeTest extends TestCase
{
    /** @test */
    public function order_status_has_constats()
    {
        $statusType = new ReflectionClass(StatusType::class);

        $this->assertArrayHasKey('ORDER', $statusType->getConstants());
        $this->assertEquals('order', $statusType->getConstant('ORDER'), 'Test that the status type ORDER was not changed');
    }
}

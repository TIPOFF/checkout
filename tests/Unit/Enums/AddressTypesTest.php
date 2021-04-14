<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Enums;

use Tipoff\Checkout\Enums\AddressTypes;
use Tipoff\Checkout\Tests\TestCase;
use ReflectionClass;

class AddressTypesTest extends TestCase
{
    /** @test */
    public function address_types_has_constats()
    {
        $addressTypes = new ReflectionClass(AddressTypes::class);

        $this->assertArrayHasKey('SHIPPING', $addressTypes->getConstants());
        $this->assertArrayHasKey('BILLING', $addressTypes->getConstants());
        $this->assertEquals('shipping', $addressTypes->getConstant('SHIPPING'), 'Test that the Address type SHIPPING was not changed');
        $this->assertEquals('billing', $addressTypes->getConstant('BILLING'), 'Test that the Address type BILLING  was not changed');
    }
}

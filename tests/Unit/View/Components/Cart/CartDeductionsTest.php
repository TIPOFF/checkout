<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\View\Components\Cart;

use Illuminate\Support\Facades\Blade;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Checkout\View\Components\BaseDeductionComponent;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;

class CartDeductionsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('view:clear')->run();

        // Dynamic Component has static data, so need to ensure this gets included
        Blade::component('custom-deduction', CustomDeduction::class);
    }

    /** @test */
    public function no_deductions()
    {
        $view = $this->blade(
            '<x-tipoff-cart-deductions :deductions="$deductions" />',
            ['deductions' => []]
        );

        $this->assertEquals('', (string) $view);
    }

    /** @test */
    public function single_adjustment()
    {
        $view = $this->blade(
            '<x-tipoff-cart-deductions :deductions="$deductions" />',
            ['deductions' => [
                $this->mockAdjustment('ABCD', 1234),
            ]]
        );

        $view->assertSee('Code ABCD');
        $view->assertSee('Deduction: $12.34');
    }

    /** @test */
    public function multiple_adjustments()
    {
        $view = $this->blade(
            '<x-tipoff-cart-deductions :deductions="$deductions" />',
            ['deductions' => [
                $this->mockAdjustment('ABCD', 1234),
                $this->mockAdjustment('HIJK', 234),
            ]]
        );

        $view->assertSee('Code ABCD');
        $view->assertSee('Deduction: $12.34');
        $view->assertSee('Code HIJK');
        $view->assertSee('Deduction: $2.34');
    }

    /** @test */
    public function dynamic_component()
    {
        $view = $this->blade(
            '<x-tipoff-cart-deductions :deductions="$deductions" />',
            ['deductions' => [
                $this->mockAdjustment('ABCD', 1234, 'custom-deduction'),
                $this->mockAdjustment('HIJK', 234),
            ]]
        );

        $view->assertSee('I am custom!');
        $view->assertSee('Code HIJK');
        $view->assertSee('Deduction: $2.34');
    }

    private function mockAdjustment(string $code, int $amount, ?string $component = null): CodedCartAdjustment
    {
        $adjustment = \Mockery::mock(CodedCartAdjustment::class);
        $adjustment->shouldReceive('getViewComponent')->andReturn($component);
        $adjustment->shouldReceive('getCode')->andReturn($code);
        $adjustment->shouldReceive('getAmount')->andReturn($amount);

        return $adjustment;
    }
}

class CustomDeduction extends BaseDeductionComponent
{
    public function render()
    {
        return '<div>I am custom!</div>';
    }
}

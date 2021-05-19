<?php

namespace Tipoff\Checkout\Tests\Unit\Models\Traits;

use Tipoff\Checkout\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Tipoff\Checkout\Models\Traits\IsItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IsItemTest extends TestCase
{
    use RefreshDatabase;

    protected $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = new class extends Model {
            use IsItem;
            protected $table = 'test_table';
        };
    }

    /** @test */
    public function is_child_item()
    {
        $this->assertInstanceOf(Builder::class, $this->model->isChildItem());
    }
}
<?php

namespace Tipoff\Checkout\Tests\Unit\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tipoff\Checkout\Models\Traits\IsItem;
use Tipoff\Checkout\Tests\TestCase;

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

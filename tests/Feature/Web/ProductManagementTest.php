<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_planner_creates_product_with_bom_and_routing(): void
    {
        $oak = Material::factory()->create();
        $workCenter = WorkCenter::factory()->create();

        $this->actingAs(User::factory()->planner()->create())
            ->post(route('products.store'), [
                'sku' => 'fg-bench-01',
                'name' => 'Oak bench',
                'price' => 420,
                'is_active' => 1,
                'bom' => [['material_id' => $oak->id, 'quantity' => 1.2]],
                'routing' => [
                    ['work_center_id' => $workCenter->id, 'name' => 'Cut', 'planned_minutes_per_unit' => 20, 'consumes_materials' => 1],
                    ['work_center_id' => $workCenter->id, 'name' => 'Assemble', 'planned_minutes_per_unit' => 30, 'consumes_materials' => 0],
                ],
            ])
            ->assertRedirect();

        $product = Product::where('sku', 'FG-BENCH-01')->firstOrFail();
        $this->assertCount(1, $product->bomItems);
        $this->assertCount(2, $product->routingSteps);
    }

    public function test_bom_rejects_finished_goods_and_duplicate_materials(): void
    {
        $finished = Product::factory()->create()->material;
        $raw = Material::factory()->create();
        $workCenter = WorkCenter::factory()->create();

        $this->actingAs(User::factory()->planner()->create())
            ->post(route('products.store'), [
                'sku' => 'FG-X', 'name' => 'X', 'price' => 1,
                'bom' => [
                    ['material_id' => $finished->id, 'quantity' => 1],
                    ['material_id' => $raw->id, 'quantity' => 1],
                    ['material_id' => $raw->id, 'quantity' => 2],
                ],
                'routing' => [['work_center_id' => $workCenter->id, 'name' => 'Build', 'planned_minutes_per_unit' => 5]],
            ])
            ->assertSessionHasErrors(['bom.0.material_id', 'bom.1.material_id', 'bom.2.material_id']);
    }

    public function test_routing_needs_at_least_one_step(): void
    {
        $this->actingAs(User::factory()->planner()->create())
            ->post(route('products.store'), ['sku' => 'FG-Y', 'name' => 'Y', 'price' => 1, 'routing' => []])
            ->assertSessionHasErrors('routing');
    }

    public function test_product_pages_render_with_bom_and_routing(): void
    {
        $product = Product::factory()->withBomAndRouting()->create();
        $planner = User::factory()->planner()->create();

        $this->actingAs($planner)->get(route('products.index'))->assertOk()->assertSee($product->name);
        $this->actingAs($planner)->get(route('products.show', $product))->assertOk()->assertSee('Consumes materials');
        $this->actingAs($planner)->get(route('products.edit', $product))->assertOk();
    }

    public function test_sales_can_view_but_not_create_products(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)->get(route('products.index'))->assertOk();
        $this->actingAs($sales)->get(route('products.create'))->assertForbidden();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Domain\Production\ProductionTestHelpers;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use ProductionTestHelpers, RefreshDatabase;

    public function test_results_are_grouped_by_kind(): void
    {
        // The product's finished-good stock item carries its name, so it is found under materials too.
        $customer = Customer::factory()->create(['name' => 'Walnut House Interiors']);
        Product::factory()->create(['name' => 'Walnut sideboard']);
        Material::factory()->create(['name' => 'Walnut panel 20 mm']);
        Product::factory()->create(['name' => 'Oak table']);

        $this->actingAs(User::factory()->sales()->create())
            ->get(route('search', ['q' => 'walnut']))
            ->assertOk()
            ->assertSee('4 results for “walnut”', false)
            ->assertSeeInOrder(['Customers', $customer->name, 'Products', 'Walnut sideboard', 'Materials', 'Walnut panel 20 mm', 'Walnut sideboard'])
            ->assertDontSee('Oak table');
    }

    public function test_an_exact_document_number_opens_the_record(): void
    {
        $order = SalesOrder::factory()->create();
        $productionOrder = $this->releaseProductionOrder();
        $material = Material::factory()->create(['sku' => 'RM-EXACT-1']);
        $user = User::factory()->planner()->create();

        $this->actingAs($user)->get(route('search', ['q' => $order->number]))->assertRedirect(route('sales-orders.show', $order));
        $this->actingAs($user)->get(route('search', ['q' => " {$productionOrder->number} "]))->assertRedirect(route('production-orders.show', $productionOrder));
        $this->actingAs($user)->get(route('search', ['q' => 'RM-EXACT-1']))->assertRedirect(route('materials.show', $material));
    }

    public function test_results_respect_what_the_role_may_see(): void
    {
        $order = SalesOrder::factory()->create(['customer_id' => Customer::factory()->create(['name' => 'Hidden Customer Ltd'])]);

        $this->actingAs(User::factory()->operator()->create())
            ->get(route('search', ['q' => 'Hidden Customer']))
            ->assertOk()
            ->assertDontSee('Hidden Customer Ltd')
            ->assertSee('Nothing matches');

        // Operators cannot open sales orders, so the number is not a shortcut for them either.
        $this->actingAs(User::factory()->operator()->create())
            ->get(route('search', ['q' => $order->number]))
            ->assertOk()
            ->assertDontSee($order->number.'</span>', false);
    }

    public function test_a_too_short_query_asks_for_more(): void
    {
        $this->actingAs(User::factory()->planner()->create())
            ->get(route('search', ['q' => 'a']))
            ->assertOk()
            ->assertSee('Type at least 2 characters.');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('search', ['q' => 'oak']))->assertRedirect(route('login'));
    }
}

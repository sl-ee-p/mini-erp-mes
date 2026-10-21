<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_can_search_customers_with_pagination(): void
    {
        Customer::factory()->create(['name' => 'Lakeside Interiors', 'city' => 'Como']);
        Customer::factory()->count(25)->create();

        $this->actingAs(User::factory()->sales()->create())
            ->get(route('customers.index', ['search' => 'Como']))
            ->assertOk()
            ->assertSee('Lakeside Interiors')
            ->assertViewHas('customers', fn ($customers) => $customers->total() === 1);
    }

    public function test_sales_can_create_a_customer_with_normalised_code(): void
    {
        $this->actingAs(User::factory()->sales()->create())
            ->post(route('customers.store'), [
                'code' => 'c-lake',
                'name' => 'Lakeside Interiors',
                'email' => 'orders@lakeside.test',
                'country' => 'it',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['code' => 'C-LAKE', 'country' => 'IT']);
    }

    public function test_customer_code_must_be_unique(): void
    {
        $existing = Customer::factory()->create();

        $this->actingAs(User::factory()->sales()->create())
            ->post(route('customers.store'), ['code' => $existing->code, 'name' => 'Dup', 'country' => 'IT'])
            ->assertSessionHasErrors('code');
    }

    public function test_sales_can_update_and_delete_a_customer(): void
    {
        $sales = User::factory()->sales()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($sales)
            ->put(route('customers.update', $customer), [...$customer->only(['code', 'country']), 'name' => 'Renamed Ltd'])
            ->assertRedirect(route('customers.show', $customer));
        $this->assertSame('Renamed Ltd', $customer->fresh()?->name);

        $this->actingAs($sales)->delete(route('customers.destroy', $customer))->assertRedirect(route('customers.index'));
        $this->assertModelMissing($customer);
    }

    public function test_planner_can_view_but_not_edit_customers(): void
    {
        $planner = User::factory()->planner()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($planner)->get(route('customers.show', $customer))->assertOk()->assertDontSee('Delete');
        $this->actingAs($planner)->get(route('customers.edit', $customer))->assertForbidden();
        $this->actingAs($planner)->put(route('customers.update', $customer), ['code' => 'X', 'name' => 'X', 'country' => 'IT'])->assertForbidden();
    }

    public function test_operator_cannot_access_customers(): void
    {
        $this->actingAs(User::factory()->operator()->create())
            ->get(route('customers.index'))
            ->assertForbidden();
    }

    public function test_customer_page_summarises_the_order_book(): void
    {
        $customer = Customer::factory()->create();
        SalesOrder::factory()->for($customer)->status(SalesOrderStatus::Shipped)->create(['total' => 1000]);
        SalesOrder::factory()->for($customer)->status(SalesOrderStatus::Confirmed)->create(['total' => 250.5]);
        SalesOrder::factory()->for($customer)->create(['total' => 99]);

        $this->actingAs(User::factory()->sales()->create())
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSeeInOrder(['Orders placed', '3', 'Open order value', '€ 250.50', 'Shipped to date', '€ 1,000.00']);
    }

    public function test_deleting_a_customer_that_just_received_an_order_is_a_flash_error_not_a_crash(): void
    {
        $customer = Customer::factory()->create();

        // Simulate an order being created between the "has orders" check and the delete.
        Customer::deleting(function (Customer $deleting): void {
            SalesOrder::factory()->create(['customer_id' => $deleting->id]);
        });

        $this->actingAs(User::factory()->sales()->create())
            ->from(route('customers.show', $customer))
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.show', $customer))
            ->assertSessionHas('error', 'Customers with sales orders cannot be deleted.');

        $this->assertModelExists($customer);
    }
}

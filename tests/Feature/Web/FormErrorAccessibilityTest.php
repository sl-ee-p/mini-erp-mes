<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormErrorAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_input_is_flagged_and_linked_to_its_error_message(): void
    {
        $existing = Customer::factory()->create();

        $this->actingAs(User::factory()->sales()->create())
            ->from(route('customers.create'))
            ->followingRedirects()
            ->post(route('customers.store'), ['code' => $existing->code, 'name' => 'Dup', 'country' => 'IT'])
            ->assertSee('id="code"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="code-error"', false)
            ->assertSee('id="code-error"', false);
    }

    public function test_valid_fields_are_not_flagged(): void
    {
        $existing = Customer::factory()->create();

        $this->actingAs(User::factory()->sales()->create())
            ->from(route('customers.create'))
            ->followingRedirects()
            ->post(route('customers.store'), ['code' => $existing->code, 'name' => 'Dup', 'country' => 'IT'])
            ->assertDontSee('id="name-error"', false);
    }

    public function test_over_long_customer_notes_show_their_error(): void
    {
        $this->actingAs(User::factory()->sales()->create())
            ->from(route('customers.create'))
            ->followingRedirects()
            ->post(route('customers.store'), ['code' => 'C-NOTES', 'name' => 'Notes', 'country' => 'IT', 'notes' => str_repeat('x', 2001)])
            ->assertSee('aria-describedby="notes-error"', false)
            ->assertSee('id="notes-error"', false)
            ->assertSee('The notes field must not be greater than 2000 characters.');
    }

    public function test_over_long_sales_order_notes_show_their_error(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->sales()->create())
            ->from(route('sales-orders.create'))
            ->followingRedirects()
            ->post(route('sales-orders.store'), [
                'customer_id' => $customer->id,
                'due_date' => today()->addWeek()->toDateString(),
                'notes' => str_repeat('x', 2001),
                'lines' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '']],
            ])
            ->assertSee('id="notes-error"', false)
            ->assertSee('The notes field must not be greater than 2000 characters.');
    }

    public function test_over_long_product_description_shows_its_error(): void
    {
        $this->actingAs(User::factory()->planner()->create())
            ->from(route('products.create'))
            ->followingRedirects()
            ->post(route('products.store'), ['sku' => 'FG-X', 'name' => 'X', 'price' => 1, 'description' => str_repeat('x', 2001)])
            ->assertSee('id="description-error"', false)
            ->assertSee('The description field must not be greater than 2000 characters.');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Jobs\GenerateSalesOrderPdf;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalesOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = User::factory()->sales()->create();
    }

    public function test_sales_creates_a_draft_order(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 500]);

        $response = $this->actingAs($this->sales)->post(route('sales-orders.store'), [
            'customer_id' => $customer->id,
            'due_date' => today()->addWeeks(2)->toDateString(),
            'lines' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '']],
        ]);

        $order = SalesOrder::sole();
        $response->assertRedirect(route('sales-orders.show', $order));
        $this->assertSame(1000.0, $order->total);
        $this->assertSame(SalesOrderStatus::Draft, $order->status);
    }

    public function test_order_validation(): void
    {
        $inactive = Product::factory()->inactive()->create();

        $this->actingAs($this->sales)
            ->post(route('sales-orders.store'), [
                'customer_id' => 999,
                'due_date' => today()->subDay()->toDateString(),
                'lines' => [['product_id' => $inactive->id, 'quantity' => 0]],
            ])
            ->assertSessionHasErrors(['customer_id', 'due_date', 'lines.0.product_id', 'lines.0.quantity']);
    }

    public function test_index_filters_late_orders(): void
    {
        $late = SalesOrder::factory()->status(SalesOrderStatus::Confirmed)->overdue()->create();
        $onTime = SalesOrder::factory()->status(SalesOrderStatus::Confirmed)->create();

        $this->actingAs($this->sales)
            ->get(route('sales-orders.index', ['late' => 1]))
            ->assertOk()
            ->assertSee($late->number)
            ->assertDontSee($onTime->number);
    }

    public function test_show_page_offers_actions_matching_the_status(): void
    {
        $draft = SalesOrder::factory()->withLines()->create();

        $this->actingAs($this->sales)
            ->get(route('sales-orders.show', $draft))
            ->assertOk()
            ->assertSee('Confirm order')
            ->assertSee('Edit')
            ->assertDontSee('Ship order');
    }

    public function test_confirmed_orders_cannot_be_edited(): void
    {
        $order = SalesOrder::factory()->status(SalesOrderStatus::Confirmed)->withLines()->create();

        $this->actingAs($this->sales)
            ->get(route('sales-orders.edit', $order))
            ->assertRedirect(route('sales-orders.show', $order));

        $this->actingAs($this->sales)
            ->put(route('sales-orders.update', $order), [
                'customer_id' => $order->customer_id,
                'due_date' => today()->addWeek()->toDateString(),
                'lines' => [['product_id' => $order->lines->first()?->product_id, 'quantity' => 1]],
            ])
            ->assertSessionHas('error');
    }

    public function test_confirm_endpoint_and_business_errors_are_flashed(): void
    {
        $order = SalesOrder::factory()->withLines()->create();

        $this->actingAs($this->sales)->post(route('sales-orders.confirm', $order))->assertSessionHas('success');
        $this->assertSame(SalesOrderStatus::Confirmed, $order->fresh()?->status);

        $this->actingAs($this->sales)
            ->from(route('sales-orders.show', $order))
            ->post(route('sales-orders.confirm', $order))
            ->assertRedirect(route('sales-orders.show', $order))
            ->assertSessionHas('error');
    }

    public function test_planner_can_confirm_but_not_create_or_cancel(): void
    {
        $planner = User::factory()->planner()->create();
        $order = SalesOrder::factory()->withLines()->create();

        $this->actingAs($planner)->get(route('sales-orders.create'))->assertForbidden();
        $this->actingAs($planner)->post(route('sales-orders.cancel', $order))->assertForbidden();
        $this->actingAs($planner)->post(route('sales-orders.confirm', $order))->assertSessionHas('success');
    }

    public function test_operator_has_no_access_to_sales_orders(): void
    {
        $this->actingAs(User::factory()->operator()->create())
            ->get(route('sales-orders.index'))
            ->assertForbidden();
    }

    public function test_pdf_is_generated_in_the_background_and_downloadable(): void
    {
        Storage::fake('local');
        $order = SalesOrder::factory()->withLines()->create();

        Queue::fake();
        $this->actingAs($this->sales)->post(route('sales-orders.pdf.store', $order))->assertSessionHas('success');
        Queue::assertPushed(GenerateSalesOrderPdf::class);

        (new GenerateSalesOrderPdf($order))->handle();

        $order->refresh();
        $this->assertTrue($order->hasCurrentPdf());
        Storage::disk('local')->assertExists("sales-orders/{$order->number}.pdf");

        $this->actingAs($this->sales)
            ->get(route('sales-orders.pdf.show', $order))
            ->assertOk()
            ->assertDownload("{$order->number}.pdf");
    }

    public function test_pdf_download_is_404_before_generation(): void
    {
        $this->actingAs($this->sales)
            ->get(route('sales-orders.pdf.show', SalesOrder::factory()->create()))
            ->assertNotFound();
    }

    public function test_customer_with_orders_cannot_be_deleted(): void
    {
        $order = SalesOrder::factory()->create();

        $this->actingAs($this->sales)
            ->delete(route('customers.destroy', $order->customer))
            ->assertSessionHas('error');

        $this->assertModelExists($order->customer);
    }
}

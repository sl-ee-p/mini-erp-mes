<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Production\Actions\StartStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Domain\Production\ProductionTestHelpers;
use Tests\TestCase;

class ShopFloorTest extends TestCase
{
    use ProductionTestHelpers, RefreshDatabase;

    public function test_queue_can_be_filtered_by_work_center(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        $cuttingId = $productionOrder->steps[0]->work_center_id;
        $otherWorkCenterId = $productionOrder->steps[1]->work_center_id;

        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)->get(route('shop-floor.index', ['work_center' => $cuttingId]))
            ->assertOk()
            ->assertSee($productionOrder->number);

        $this->actingAs($operator)->get(route('shop-floor.index', ['work_center' => $otherWorkCenterId]))
            ->assertOk()
            ->assertDontSee($productionOrder->number);
    }

    public function test_operator_sees_their_running_step_highlighted(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        $operator = User::factory()->operator()->create();
        app(StartStep::class)->execute($productionOrder->steps[0], $operator);

        $this->actingAs($operator)->get(route('shop-floor.index'))
            ->assertOk()
            ->assertSee('You are working on')
            ->assertSee('Pause')
            ->assertSee('Complete');
    }

    public function test_colleagues_cannot_stop_a_step_another_operator_is_running(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        $step = $productionOrder->steps[0];
        app(StartStep::class)->execute($step, User::factory()->operator()->create(['name' => 'Marta Rossi']));
        $colleague = User::factory()->operator()->create();

        $this->actingAs($colleague)->get(route('shop-floor.index'))
            ->assertOk()
            ->assertSee('In progress by Marta Rossi')
            ->assertDontSee(route('production-steps.pause', $step))
            ->assertDontSee(route('production-steps.complete', $step));

        $this->actingAs($colleague)
            ->from(route('shop-floor.index'))
            ->post(route('production-steps.pause', $step))
            ->assertRedirect(route('shop-floor.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'being worked on by Marta Rossi'));

        $this->actingAs(User::factory()->planner()->create())->get(route('production-orders.show', $productionOrder))
            ->assertOk()
            ->assertSee(route('production-steps.pause', $step));
    }

    public function test_business_rule_violations_are_shown_to_the_operator(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->from(route('shop-floor.index'))
            ->post(route('production-steps.start', $productionOrder->steps[1]))
            ->assertRedirect(route('shop-floor.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'cannot start before'));
    }

    public function test_scrap_must_be_a_non_negative_integer(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        $operator = User::factory()->operator()->create();
        app(StartStep::class)->execute($productionOrder->steps[0], $operator);

        $this->actingAs($operator)
            ->post(route('production-steps.complete', $productionOrder->steps[0]), ['scrap_qty' => -1])
            ->assertSessionHasErrors('scrap_qty');
    }

    public function test_sales_cannot_operate_steps(): void
    {
        $productionOrder = $this->releaseProductionOrder();

        $this->actingAs(User::factory()->sales()->create())
            ->post(route('production-steps.start', $productionOrder->steps[0]))
            ->assertForbidden();
    }

    public function test_production_order_pages_render_for_every_role(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        app(StartStep::class)->execute($productionOrder->steps[0], User::factory()->operator()->create());

        foreach (['planner', 'operator', 'sales'] as $role) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)->get(route('production-orders.index'))->assertOk()->assertSee($productionOrder->number);
            $this->actingAs($user)->get(route('production-orders.show', $productionOrder))->assertOk()->assertSee($productionOrder->steps[0]->name);
        }
    }

    public function test_the_running_step_banner_has_its_own_controls(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        $operator = User::factory()->operator()->create();
        $step = $productionOrder->steps[0];
        app(StartStep::class)->execute($step, $operator);

        // The banner and the queue card each carry a completion dialog, with distinct field ids.
        $this->actingAs($operator)->get(route('shop-floor.index'))
            ->assertOk()
            ->assertSee('id="current-scrap-'.$step->id.'"', false)
            ->assertSee('id="scrap-'.$step->id.'"', false)
            ->assertSee('Open '.$productionOrder->number);
    }
}

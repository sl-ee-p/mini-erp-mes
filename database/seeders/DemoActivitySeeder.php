<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Inventory\Actions\AdjustStockToCount;
use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Production\Actions\CompleteStep;
use App\Domain\Production\Actions\PauseStep;
use App\Domain\Production\Actions\StartStep;
use App\Domain\Sales\Actions\CancelSalesOrder;
use App\Domain\Sales\Actions\ConfirmSalesOrder;
use App\Domain\Sales\Actions\CreateSalesOrder;
use App\Domain\Sales\Actions\ShipSalesOrder;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderStep;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * A few weeks of workshop history, so every screen has something to show: orders in each status, production at
 * different stages (one late, steps running and paused), goods receipts, a stock count and a material shortage.
 *
 * Everything is booked through the domain actions with the clock moved back, exactly as the users would have done
 * it, so stock, time logs, document numbers and the audit trail are all consistent. Dates are relative to today.
 */
class DemoActivitySeeder extends Seeder
{
    private User $sales;

    private User $planner;

    /** @var list<User> */
    private array $operators;

    /** The real moment the seeder runs; every simulated date is relative to it. */
    private Carbon $now;

    public function __construct(
        private readonly CreateSalesOrder $createSalesOrder,
        private readonly ConfirmSalesOrder $confirmSalesOrder,
        private readonly CancelSalesOrder $cancelSalesOrder,
        private readonly ShipSalesOrder $shipSalesOrder,
        private readonly StartStep $startStep,
        private readonly PauseStep $pauseStep,
        private readonly CompleteStep $completeStep,
        private readonly RecordStockMovement $recordStockMovement,
        private readonly AdjustStockToCount $adjustStockToCount,
    ) {}

    public function run(): void
    {
        // Demo history belongs in an empty workshop; never mix it into one that is already taking orders.
        if (SalesOrder::query()->exists()) {
            $this->command->warn('Sales orders already exist; skipping the demo history.');

            return;
        }

        $this->sales = User::query()->where('email', 'sales@oakline.test')->firstOrFail();
        $this->planner = User::query()->where('email', 'planner@oakline.test')->firstOrFail();
        $this->operators = User::query()->whereIn('email', ['operator@oakline.test', 'kenji@oakline.test', 'ines@oakline.test'])->orderBy('id')->get()->all();
        $this->now = Carbon::now();
        $frozenClock = Carbon::getTestNow();

        // The history starts six weeks back, so the opening balances booked by MaterialSeeder are dated before it.
        StockMovement::query()->where('reason', 'Opening balance')->update(['created_at' => $this->now->copy()->subDays(42)->setTime(7, 0)]);

        try {
            $this->history();
        } finally {
            Carbon::setTestNow($frozenClock);
            Auth::forgetUser();
        }
    }

    private function history(): void
    {
        [$tomas, $kenji, $ines] = $this->operators;

        // Delivered: produced over a week and shipped before the due date.
        $serena = $this->order('C-SEREN', ['FG-NST-OAK' => 2, 'FG-BEN-ASH-120' => 1], orderedDaysAgo: 40, dueInDays: -12);
        $this->produce($serena, startDaysAgo: 34);
        $this->ship($serena, daysAgo: 20);

        $lumen = $this->order('C-LUMEN', ['FG-TBL-OAK-180' => 1, 'FG-CHR-OAK-UPH' => 4], orderedDaysAgo: 30, dueInDays: -5);
        $this->produce($lumen, startDaysAgo: 24);
        $this->ship($lumen, daysAgo: 8);

        // Supplier deliveries and a stock count in between.
        $this->at(daysAgo: 21, hour: 8, user: $this->planner);
        $this->receive('RM-OAK-LEG', 40, 'Supplier delivery DN-4471');
        $this->receive('RM-SAND-120', 400, 'Supplier delivery DN-4471');
        $this->at(daysAgo: 12, hour: 16, user: $this->planner);
        $this->adjustStockToCount->execute($this->material('RM-DWL-08'), 5750, 'Monthly cycle count', $this->planner);
        $this->at(daysAgo: 4, hour: 8, user: $this->planner);
        $this->receive('RM-FAB-LIN', 25, 'Supplier delivery DN-4602');

        // Produced and waiting for the truck.
        $bergblick = $this->order('C-BERGB', ['FG-BEN-ASH-120' => 2], orderedDaysAgo: 18, dueInDays: 3);
        $this->produce($bergblick, startDaysAgo: 6);

        // Cancelled before production started.
        $marea = $this->order('C-MAREA', ['FG-BEN-ASH-120' => 2], orderedDaysAgo: 15, dueInDays: 10);
        $this->at(daysAgo: 14, hour: 11, user: $this->sales);
        $this->cancelSalesOrder->execute($marea);

        // In production and already late: the walnut sideboard is in finishing.
        $nordhaus = $this->order('C-NORDH', ['FG-SBD-WAL-3' => 1], orderedDaysAgo: 25, dueInDays: -2);
        $this->produce($nordhaus, startDaysAgo: 3, steps: 2);
        $this->startNow($nordhaus, $kenji, minutesAgo: 35);

        // In production: desks sanded halfway, paused for the night.
        $holmberg = $this->order('C-HOLMB', ['FG-DSK-BIR-140' => 3], orderedDaysAgo: 10, dueInDays: 9);
        $this->produce($holmberg, startDaysAgo: 2, steps: 1);
        $this->at(daysAgo: 1, hour: 15, user: $tomas);
        $step = $this->nextStep($holmberg);
        $this->startStep->execute($step, $tomas);
        $this->at(daysAgo: 1, hour: 16, minute: 10, user: $tomas);
        $this->pauseStep->execute($step, $tomas);

        // In production: chairs for a restaurant, being cut right now.
        $alpenrose = $this->order('C-ALPEN', ['FG-CHR-OAK-UPH' => 6], orderedDaysAgo: 8, dueInDays: 12);
        $this->startNow($alpenrose, $ines, minutesAgo: 50);

        // Confirmed, waiting for the shop floor. The hotel's 36 nightstands need more drawer runners than are in stock.
        $this->order('C-FERRO', ['FG-BSH-WAL-5' => 2], orderedDaysAgo: 3, dueInDays: 20);
        $this->order('C-SEREN', ['FG-NST-OAK' => 36], orderedDaysAgo: 2, dueInDays: 35, notes: 'Room refurbishment, wing B. Deliver in two drops if needed.');
        $this->order('C-PRIV1', ['FG-TBL-OAK-180' => 1, 'FG-CHR-OAK-UPH' => 6], orderedDaysAgo: 1, dueInDays: 28);

        // Drafts still being negotiated.
        $this->order('C-OLIVA', ['FG-CHR-OAK-UPH' => 4, 'FG-TBL-OAK-180' => 1], orderedDaysAgo: 1, dueInDays: 30, confirm: false);
        $this->order('C-QUIET', ['FG-DSK-BIR-140' => 4], orderedDaysAgo: 0, dueInDays: 21, confirm: false, prices: ['FG-DSK-BIR-140' => 520.00], notes: 'Volume price agreed by phone.');
    }

    /**
     * Creates an order on the given day and, unless it stays a draft, confirms it two hours later.
     *
     * @param  array<string, int>  $lines  product SKU => quantity
     * @param  array<string, float>  $prices  negotiated unit prices by SKU
     */
    private function order(string $customer, array $lines, int $orderedDaysAgo, int $dueInDays, bool $confirm = true, array $prices = [], ?string $notes = null): SalesOrder
    {
        $this->at(daysAgo: $orderedDaysAgo, hour: 9, user: $this->sales);

        $products = Product::query()->whereIn('sku', array_keys($lines))->pluck('id', 'sku');

        $order = $this->createSalesOrder->execute([
            'customer_id' => Customer::query()->where('code', $customer)->value('id'),
            'order_date' => $this->now->copy()->subDays($orderedDaysAgo)->toDateString(),
            'due_date' => $this->now->copy()->addDays($dueInDays)->toDateString(),
            'notes' => $notes,
            'lines' => array_map(fn (string $sku, int $quantity): array => [
                'product_id' => $products[$sku],
                'quantity' => $quantity,
                'unit_price' => $prices[$sku] ?? null,
            ], array_keys($lines), $lines),
        ], $this->sales);

        if ($confirm) {
            $this->at(daysAgo: $orderedDaysAgo, hour: 11, user: $this->sales);
            $this->confirmSalesOrder->execute($order);
        }

        return $order;
    }

    /**
     * Works the routing of every production order of a sales order, one step after the other in working hours,
     * rotating the operators. Each step takes a little more or less than planned. Stops after $steps steps if given.
     */
    private function produce(SalesOrder $order, int $startDaysAgo, ?int $steps = null): void
    {
        $clock = $this->now->copy()->subDays($startDaysAgo)->setTime(7, 30);

        foreach ($order->productionOrders()->with('steps')->orderBy('id')->get() as $index => $productionOrder) {
            foreach ($productionOrder->steps->take($steps ?? PHP_INT_MAX) as $stepIndex => $step) {
                $operator = $this->operators[($index + $stepIndex) % count($this->operators)];
                $minutes = (int) round($step->planned_minutes * [0.92, 1.08, 1.0, 1.15, 0.97][($index + $stepIndex) % 5]);

                if ($clock->hour >= 16) {
                    $clock = $clock->copy()->addDay()->setTime(7, 30);
                }

                $this->travelTo($clock, $operator);
                $this->startStep->execute($step, $operator);

                $clock = $clock->copy()->addMinutes($minutes);
                $this->travelTo($clock, $operator);
                $this->completeStep->execute($step->refresh(), $operator);

                $clock = $clock->copy()->addMinutes(20);
            }
        }
    }

    private function startNow(SalesOrder $order, User $operator, int $minutesAgo): void
    {
        $this->travelTo($this->now->copy()->subMinutes($minutesAgo), $operator);
        $this->startStep->execute($this->nextStep($order), $operator);
    }

    private function nextStep(SalesOrder $order): ProductionOrderStep
    {
        /** @var ProductionOrder $productionOrder */
        $productionOrder = $order->productionOrders()->orderBy('id')->firstOrFail();

        /** @var ProductionOrderStep */
        return $productionOrder->steps()->where('status', '!=', 'completed')->firstOrFail();
    }

    private function ship(SalesOrder $order, int $daysAgo): void
    {
        $this->at(daysAgo: $daysAgo, hour: 14, user: $this->sales);
        $this->shipSalesOrder->execute($order, $this->sales);
    }

    private function receive(string $sku, int $quantity, string $reason): void
    {
        $this->recordStockMovement->execute($this->material($sku), StockMovementType::In, $quantity, $reason, user: $this->planner);
    }

    private function material(string $sku): Material
    {
        return Material::query()->where('sku', $sku)->firstOrFail();
    }

    private function at(int $daysAgo, int $hour, User $user, int $minute = 0): void
    {
        $this->travelTo($this->now->copy()->subDays($daysAgo)->setTime($hour, $minute), $user);
    }

    /**
     * Moves the clock and signs in the user, so timestamps and the audit trail read as if they had done it then.
     */
    private function travelTo(Carbon $moment, User $user): void
    {
        // Seeding early in the morning must not stamp anything in the future.
        Carbon::setTestNow($moment->lt($this->now) ? $moment : $this->now);
        Auth::setUser($user);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Models\Material;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised once when a material's balance crosses below its minimum stock level.
 */
class StockBelowMinimum implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Material $material) {}
}

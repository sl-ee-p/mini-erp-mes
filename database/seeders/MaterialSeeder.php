<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\MaterialType;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Raw materials of a small solid-wood furniture workshop. Opening stock is booked through the ledger.
     *
     * Columns: sku, name, unit, unit cost, minimum stock, opening stock.
     *
     * @var list<array{string, string, UnitOfMeasure, float, int|float, int|float}>
     */
    private const MATERIALS = [
        ['RM-OAK-20', 'Oak panel 20 mm', UnitOfMeasure::SquareMeter, 48.00, 20, 64],
        ['RM-OAK-40', 'Oak panel 40 mm', UnitOfMeasure::SquareMeter, 85.00, 10, 28],
        ['RM-WAL-20', 'Walnut panel 20 mm', UnitOfMeasure::SquareMeter, 96.00, 8, 22],
        ['RM-ASH-20', 'Ash panel 20 mm', UnitOfMeasure::SquareMeter, 42.00, 10, 35],
        ['RM-BIR-18', 'Birch plywood 18 mm', UnitOfMeasure::SquareMeter, 24.00, 25, 80],
        ['RM-MDF-12', 'MDF back panel 12 mm', UnitOfMeasure::SquareMeter, 9.00, 30, 18],
        ['RM-OAK-LEG', 'Oak leg blank 70×70', UnitOfMeasure::Meter, 18.00, 30, 96],
        ['RM-WAL-LEG', 'Walnut leg blank 50×50', UnitOfMeasure::Meter, 29.00, 12, 40],
        ['RM-ASH-RAIL', 'Ash rail 25×60', UnitOfMeasure::Meter, 7.50, 40, 150],
        ['RM-SCR-440', 'Wood screw 4×40', UnitOfMeasure::Piece, 0.04, 2000, 9500],
        ['RM-SCR-560', 'Wood screw 5×60', UnitOfMeasure::Piece, 0.07, 1000, 4200],
        ['RM-DWL-08', 'Beech dowel Ø8', UnitOfMeasure::Piece, 0.03, 1500, 6000],
        ['RM-HNG-35', 'Concealed hinge 35 mm', UnitOfMeasure::Piece, 2.10, 100, 260],
        ['RM-RUN-450', 'Soft-close drawer runner 450 mm (pair)', UnitOfMeasure::Piece, 8.90, 40, 30],
        ['RM-KNB-OAK', 'Turned oak knob', UnitOfMeasure::Piece, 3.20, 60, 140],
        ['RM-BRK-L', 'Steel corner bracket', UnitOfMeasure::Piece, 0.65, 300, 1100],
        ['RM-FELT', 'Felt glide pad', UnitOfMeasure::Piece, 0.08, 500, 2400],
        ['RM-LEV', 'Adjustable levelling foot', UnitOfMeasure::Piece, 0.90, 100, 320],
        ['RM-OIL-HW', 'Hardwax oil, natural', UnitOfMeasure::Liter, 38.00, 10, 26],
        ['RM-LAC-MAT', 'Water-based matt lacquer', UnitOfMeasure::Liter, 22.00, 10, 7],
        ['RM-GLUE-PVA', 'PVA wood glue D3', UnitOfMeasure::Kilogram, 6.50, 15, 42],
        ['RM-SAND-120', 'Sanding disc P120', UnitOfMeasure::Piece, 0.45, 200, 900],
        ['RM-SAND-240', 'Sanding disc P240', UnitOfMeasure::Piece, 0.45, 200, 750],
        ['RM-FAB-LIN', 'Linen upholstery fabric', UnitOfMeasure::Meter, 19.00, 20, 55],
        ['RM-FOAM-40', 'Seat foam 40 mm', UnitOfMeasure::SquareMeter, 14.00, 5, 16],
        ['RM-BOX-L', 'Shipping carton, large', UnitOfMeasure::Piece, 4.80, 30, 85],
        ['RM-BOX-M', 'Shipping carton, medium', UnitOfMeasure::Piece, 2.90, 40, 120],
        ['RM-CRN', 'Foam corner protector', UnitOfMeasure::Piece, 0.25, 200, 640],
        ['RM-WRAP', 'Stretch film', UnitOfMeasure::Meter, 0.06, 500, 2200],
    ];

    public function run(RecordStockMovement $recordStockMovement): void
    {
        foreach (self::MATERIALS as [$sku, $name, $unit, $cost, $minimum, $opening]) {
            $material = Material::create([
                'sku' => $sku,
                'name' => $name,
                'type' => MaterialType::Raw,
                'unit' => $unit,
                'unit_cost' => $cost,
                'min_stock' => $minimum,
            ]);

            $recordStockMovement->execute($material, StockMovementType::In, $opening, 'Opening balance');
        }
    }
}

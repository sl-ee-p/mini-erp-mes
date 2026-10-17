<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Actions\SaveProduct;
use App\Models\Material;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(SaveProduct $saveProduct): void
    {
        $materials = Material::pluck('id', 'sku');
        $workCenters = WorkCenter::pluck('id', 'code');

        foreach ($this->products() as $product) {
            $saveProduct->execute([
                'sku' => $product['sku'],
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'is_active' => $product['active'] ?? true,
                'bom' => array_map(fn (string $sku, float|int $quantity) => [
                    'material_id' => $materials[$sku],
                    'quantity' => (float) $quantity,
                ], array_keys($product['bom']), $product['bom']),
                'routing' => array_map(fn (array $step) => [
                    'work_center_id' => $workCenters[$step[0]],
                    'name' => $step[1],
                    'planned_minutes_per_unit' => (float) $step[2],
                    'consumes_materials' => $step[3] ?? false,
                ], $product['routing']),
            ]);
        }
    }

    /**
     * @return list<array{sku: string, name: string, description: string, price: float, active?: bool, bom: array<string, float|int>, routing: list<array{0: string, 1: string, 2: int, 3?: bool}>}>
     */
    private function products(): array
    {
        $standardFinish = [['SAN', 'Sand P120 / P240', 25], ['FIN', 'Hardwax oil, two coats', 30]];

        return [
            [
                'sku' => 'FG-TBL-OAK-180', 'name' => 'Lago oak dining table 180', 'price' => 1490.00,
                'description' => 'Solid oak dining table, 180 × 90 cm, 40 mm top, oiled finish. Seats six.',
                'bom' => ['RM-OAK-40' => 1.7, 'RM-OAK-LEG' => 3.0, 'RM-ASH-RAIL' => 5.2, 'RM-SCR-560' => 24, 'RM-BRK-L' => 8, 'RM-GLUE-PVA' => 0.3, 'RM-OIL-HW' => 0.4, 'RM-SAND-120' => 6, 'RM-SAND-240' => 4, 'RM-FELT' => 4, 'RM-BOX-L' => 2, 'RM-CRN' => 8],
                'routing' => [['CUT', 'Cut top, legs and rails', 35, true], ...$standardFinish, ['ASM', 'Assemble frame and fix top', 40], ['PCK', 'Flat-pack and wrap', 15]],
            ],
            [
                'sku' => 'FG-BSH-WAL-5', 'name' => 'Brera walnut bookshelf, 5 shelves', 'price' => 980.00,
                'description' => 'Walnut bookshelf with five fixed shelves, 90 × 32 × 190 cm, MDF back.',
                'bom' => ['RM-WAL-20' => 2.6, 'RM-MDF-12' => 1.7, 'RM-DWL-08' => 40, 'RM-SCR-440' => 30, 'RM-GLUE-PVA' => 0.25, 'RM-OIL-HW' => 0.35, 'RM-SAND-120' => 5, 'RM-SAND-240' => 4, 'RM-LEV' => 4, 'RM-BOX-L' => 2, 'RM-CRN' => 8],
                'routing' => [['CUT', 'CNC panels and dowel holes', 30, true], ...$standardFinish, ['ASM', 'Glue-up carcass', 45], ['PCK', 'Pack', 12]],
            ],
            [
                'sku' => 'FG-NST-OAK', 'name' => 'Nido oak nightstand with drawer', 'price' => 340.00,
                'description' => 'Compact oak nightstand, one soft-close drawer, turned oak knob.',
                'bom' => ['RM-OAK-20' => 0.75, 'RM-OAK-LEG' => 1.6, 'RM-BIR-18' => 0.2, 'RM-RUN-450' => 1, 'RM-KNB-OAK' => 1, 'RM-SCR-440' => 16, 'RM-GLUE-PVA' => 0.1, 'RM-OIL-HW' => 0.15, 'RM-SAND-120' => 2, 'RM-SAND-240' => 2, 'RM-FELT' => 4, 'RM-BOX-M' => 1, 'RM-CRN' => 4],
                'routing' => [['CUT', 'Cut panels and legs', 18, true], ['SAN', 'Sand', 12], ['ASM', 'Assemble body and drawer', 28], ['FIN', 'Hardwax oil', 15], ['PCK', 'Pack', 8]],
            ],
            [
                'sku' => 'FG-BEN-ASH-120', 'name' => 'Riva ash bench 120', 'price' => 390.00,
                'description' => 'Ash bench with tapered legs, 120 × 35 cm. Indoor use.',
                'bom' => ['RM-ASH-20' => 0.5, 'RM-ASH-RAIL' => 2.4, 'RM-DWL-08' => 12, 'RM-GLUE-PVA' => 0.1, 'RM-LAC-MAT' => 0.15, 'RM-SAND-120' => 2, 'RM-SAND-240' => 2, 'RM-FELT' => 4, 'RM-WRAP' => 6],
                'routing' => [['CUT', 'Cut seat and legs', 15, true], ['SAN', 'Sand', 10], ['ASM', 'Dowel and glue', 20], ['FIN', 'Matt lacquer', 18], ['PCK', 'Wrap', 5]],
            ],
            [
                'sku' => 'FG-DSK-BIR-140', 'name' => 'Studio birch plywood desk 140', 'price' => 560.00,
                'description' => 'Birch plywood desk, 140 × 70 cm, cable slot, lacquered edges.',
                'bom' => ['RM-BIR-18' => 2.1, 'RM-SCR-440' => 20, 'RM-BRK-L' => 6, 'RM-GLUE-PVA' => 0.15, 'RM-LAC-MAT' => 0.3, 'RM-SAND-120' => 3, 'RM-SAND-240' => 3, 'RM-LEV' => 4, 'RM-BOX-L' => 1, 'RM-CRN' => 8],
                'routing' => [['CUT', 'CNC nesting', 20, true], ['SAN', 'Edge sanding', 15], ['FIN', 'Lacquer edges', 20], ['ASM', 'Pre-assemble and check', 15], ['PCK', 'Flat-pack', 10]],
            ],
            [
                'sku' => 'FG-CHR-OAK-UPH', 'name' => 'Lago oak chair, linen seat', 'price' => 260.00,
                'description' => 'Oak dining chair with upholstered linen seat. Pairs with the Lago table.',
                'bom' => ['RM-OAK-LEG' => 1.9, 'RM-ASH-RAIL' => 1.6, 'RM-BIR-18' => 0.2, 'RM-FOAM-40' => 0.2, 'RM-FAB-LIN' => 0.6, 'RM-DWL-08' => 14, 'RM-SCR-440' => 6, 'RM-GLUE-PVA' => 0.08, 'RM-OIL-HW' => 0.08, 'RM-SAND-240' => 2, 'RM-FELT' => 4, 'RM-BOX-M' => 1],
                'routing' => [['CUT', 'Cut and shape frame', 22, true], ['SAN', 'Sand', 15], ['ASM', 'Assemble frame', 25], ['FIN', 'Oil frame', 12], ['UPH', 'Upholster seat', 20], ['PCK', 'Pack', 6]],
            ],
            [
                'sku' => 'FG-SBD-WAL-3', 'name' => 'Brera walnut sideboard, 3 doors', 'price' => 1850.00,
                'description' => 'Walnut sideboard, 180 × 45 × 80 cm, three doors with concealed hinges.',
                'bom' => ['RM-WAL-20' => 3.4, 'RM-WAL-LEG' => 1.2, 'RM-MDF-12' => 1.5, 'RM-HNG-35' => 6, 'RM-KNB-OAK' => 3, 'RM-DWL-08' => 36, 'RM-SCR-440' => 40, 'RM-GLUE-PVA' => 0.35, 'RM-OIL-HW' => 0.5, 'RM-SAND-120' => 6, 'RM-SAND-240' => 5, 'RM-BOX-L' => 2, 'RM-CRN' => 8],
                'routing' => [['CUT', 'CNC carcass and doors', 45, true], ...$standardFinish, ['ASM', 'Assemble, hang doors', 70], ['PCK', 'Crate and wrap', 20]],
            ],
            [
                'sku' => 'FG-CTB-OAK-90', 'name' => 'Lago oak coffee table 90 (discontinued)', 'price' => 520.00, 'active' => false,
                'description' => 'Round oak coffee table. Replaced by the 2027 collection.',
                'bom' => ['RM-OAK-40' => 0.65, 'RM-OAK-LEG' => 1.8, 'RM-SCR-560' => 8, 'RM-OIL-HW' => 0.2, 'RM-SAND-120' => 2, 'RM-BOX-M' => 1],
                'routing' => [['CUT', 'Cut top and legs', 20, true], ['SAN', 'Sand', 12], ['ASM', 'Assemble', 15], ['FIN', 'Oil', 12], ['PCK', 'Pack', 6]],
            ],
        ];
    }
}

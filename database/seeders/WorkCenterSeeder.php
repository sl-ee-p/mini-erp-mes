<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WorkCenter;
use Illuminate\Database\Seeder;

class WorkCenterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'CUT' => 'Cutting & CNC',
            'SAN' => 'Sanding',
            'ASM' => 'Assembly',
            'FIN' => 'Finishing',
            'UPH' => 'Upholstery',
            'PCK' => 'Packing',
        ] as $code => $name) {
            WorkCenter::create(['code' => $code, 'name' => $name]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

/**
 * Fictional customers only — any resemblance to real businesses is coincidental.
 */
class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['C-LUMEN', 'Casa Lumen Arredamenti', 'Milano', 'IT'],
            ['C-NORDH', 'Nordhaus Wohnen GmbH', 'München', 'DE'],
            ['C-BRUME', 'Atelier Brume', 'Lyon', 'FR'],
            ['C-FERRO', 'Studio Ferro & Legno', 'Torino', 'IT'],
            ['C-SEREN', 'Villa Serena Boutique Hotels', 'Bellagio', 'IT'],
            ['C-BERGB', 'Bergblick Chalets AG', 'Davos', 'CH'],
            ['C-QUIET', 'Quiet Room Co-working', 'Verona', 'IT'],
            ['C-LINDE', 'Lindenhof Einrichtungen', 'Innsbruck', 'AT'],
            ['C-OLIVA', 'Oliva Kitchen & Living', 'Bologna', 'IT'],
            ['C-PIETR', 'Pietra Viva Interior Design', 'Firenze', 'IT'],
            ['C-ALPEN', 'Alpenrose Gasthaus', 'Bolzano', 'IT'],
            ['C-MAREA', 'Marea Beach Club', 'Rimini', 'IT'],
            ['C-HOLMB', 'Holmberg Showroom', 'Zürich', 'CH'],
            ['C-PRIV1', 'Giulia Martinelli (private)', 'Bergamo', 'IT'],
            ['C-PRIV2', 'Daniel Okafor (private)', 'Lugano', 'CH'],
        ];

        foreach ($customers as [$code, $name, $city, $country]) {
            Customer::factory()->create([
                'code' => $code,
                'name' => $name,
                'city' => $city,
                'country' => $country,
                'email' => strtolower(str_replace('C-', '', $code)).'@example.test',
            ]);
        }
    }
}

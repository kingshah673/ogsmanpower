<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class PakistanCitySeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('resources/seed-data/pakistan-cities.json');
        if (! is_file($path)) {
            return;
        }

        $cities = json_decode(file_get_contents($path), true) ?: [];

        foreach ($cities as $row) {
            City::updateOrCreate(
                ['id' => $row['id']],
                [
                    'name' => $row['name'],
                    'long' => $row['long'] ?? null,
                    'lat' => $row['lat'] ?? null,
                    'state_id' => $row['state_id'],
                ]
            );
        }
    }
}

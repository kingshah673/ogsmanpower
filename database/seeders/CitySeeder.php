<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Prefers full cities.json (gitignored — download via `php artisan location:seed-world --download`).
     * Falls back to truncated citixes.json if the full file is missing.
     */
    public function run(): void
    {
        Model::unguard();

        if (City::query()->exists()) {
            return;
        }

        $path = $this->resolvePath();
        if (! $path) {
            $this->command?->warn('No cities seed file found. Run: php artisan location:seed-world --download --force');

            return;
        }

        @ini_set('memory_limit', '1024M');

        $list = json_decode(file_get_contents($path), true) ?: [];
        if (! $list) {
            return;
        }

        $rows = [];
        foreach ($list as $item) {
            $rows[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'long' => $item['longitude'] ?? $item['long'] ?? null,
                'lat' => $item['latitude'] ?? $item['lat'] ?? null,
                'state_id' => $item['state_id'],
            ];
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            City::insert($chunk);
        }
    }

    protected function resolvePath(): ?string
    {
        $full = base_path('resources/seed-data/cities.json');
        if (is_file($full) && filesize($full) > 1_000_000) {
            return $full;
        }

        $legacy = base_path('resources/seed-data/citixes.json');

        return is_file($legacy) ? $legacy : null;
    }
}

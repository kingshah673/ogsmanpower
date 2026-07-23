<?php

namespace Database\Seeders;

use App\Models\State;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguard();

        if (State::query()->exists()) {
            return;
        }

        $path = base_path('resources/seed-data/states.json');
        if (! is_file($path)) {
            return;
        }

        $list = json_decode(file_get_contents($path), true) ?: [];
        $now = Carbon::now();
        $rows = [];

        foreach ($list as $item) {
            $rows[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'country_id' => $item['country_id'],
                'long' => $item['longitude'] ?? $item['long'] ?? null,
                'lat' => $item['latitude'] ?? $item['lat'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            State::insert($chunk);
        }
    }
}

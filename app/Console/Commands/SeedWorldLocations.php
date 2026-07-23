<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\SearchCountry;
use App\Models\State;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SeedWorldLocations extends Command
{
    protected $signature = 'location:seed-world
                            {--download : Download latest countries/states/cities from CSC GitHub release}
                            {--force : Truncate and reload even if data already exists}';

    protected $description = 'Seed full world countries, states, and cities for location dropdowns (dr5hn CSC dataset)';

    private const CITIES_URL = 'https://github.com/dr5hn/countries-states-cities-database/releases/latest/download/json-cities.json.gz';

    private const COUNTRIES_URL = 'https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/countries.json';

    private const STATES_URL = 'https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/states.json';

    public function handle(): int
    {
        $seedDir = base_path('resources/seed-data');
        if (! is_dir($seedDir)) {
            mkdir($seedDir, 0755, true);
        }

        if ($this->option('download')) {
            $this->info('Downloading latest CSC location data…');
            if (! $this->downloadDataset($seedDir)) {
                return self::FAILURE;
            }
        }

        $countriesPath = $seedDir.DIRECTORY_SEPARATOR.'search_countries.json';
        $statesPath = $seedDir.DIRECTORY_SEPARATOR.'states.json';
        $citiesPath = $this->resolveCitiesPath($seedDir);

        if (! is_file($countriesPath) || ! is_file($statesPath) || ! $citiesPath) {
            $this->error('Missing seed files. Run with --download first.');
            $this->line('Expected: resources/seed-data/search_countries.json, states.json, and cities.json (or citixes.json)');

            return self::FAILURE;
        }

        $existingCities = City::query()->count();
        if ($existingCities > 0 && ! $this->option('force')) {
            $this->warn("Cities already present ({$existingCities}). Re-run with --force to replace.");

            return self::SUCCESS;
        }

        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $this->info('Loading JSON…');
        $countries = json_decode(file_get_contents($countriesPath), true) ?: [];
        $states = json_decode(file_get_contents($statesPath), true) ?: [];
        $cities = json_decode(file_get_contents($citiesPath), true) ?: [];

        if (! $countries || ! $states || ! $cities) {
            $this->error('Failed to parse one or more JSON files.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Seeding %d countries, %d states, %d cities…',
            count($countries),
            count($states),
            count($cities)
        ));

        Schema::disableForeignKeyConstraints();

        try {
            DB::table('cities')->truncate();
            DB::table('states')->truncate();
            DB::table('search_countries')->truncate();

            $now = now();
            $countryRows = [];
            foreach ($countries as $row) {
                $countryRows[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'short_name' => $row['iso2'] ?? $row['short_name'] ?? $row['name'],
                    'long' => $row['longitude'] ?? $row['long'] ?? null,
                    'lat' => $row['latitude'] ?? $row['lat'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($countryRows, 500) as $chunk) {
                SearchCountry::insert($chunk);
            }
            $this->info('Countries done.');

            $stateRows = [];
            foreach ($states as $row) {
                $stateRows[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'country_id' => $row['country_id'],
                    'long' => $row['longitude'] ?? $row['long'] ?? null,
                    'lat' => $row['latitude'] ?? $row['lat'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($stateRows, 500) as $chunk) {
                State::insert($chunk);
            }
            $this->info('States done.');

            $bar = $this->output->createProgressBar(count($cities));
            $bar->start();
            $cityBuffer = [];
            foreach ($cities as $row) {
                $cityBuffer[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'long' => $row['longitude'] ?? $row['long'] ?? null,
                    'lat' => $row['latitude'] ?? $row['lat'] ?? null,
                    'state_id' => $row['state_id'],
                ];
                if (count($cityBuffer) >= 1000) {
                    City::insert($cityBuffer);
                    $bar->advance(count($cityBuffer));
                    $cityBuffer = [];
                }
            }
            if ($cityBuffer) {
                City::insert($cityBuffer);
                $bar->advance(count($cityBuffer));
            }
            $bar->finish();
            $this->newLine();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info(sprintf(
            'Done. DB now has %d countries, %d states, %d cities.',
            SearchCountry::count(),
            State::count(),
            City::count()
        ));

        return self::SUCCESS;
    }

    private function resolveCitiesPath(string $seedDir): ?string
    {
        $full = $seedDir.DIRECTORY_SEPARATOR.'cities.json';
        if (is_file($full) && filesize($full) > 1_000_000) {
            return $full;
        }

        $legacy = $seedDir.DIRECTORY_SEPARATOR.'citixes.json';
        if (is_file($legacy)) {
            $this->warn('Using truncated citixes.json — run with --download for the full city list.');

            return $legacy;
        }

        return null;
    }

    private function downloadDataset(string $seedDir): bool
    {
        try {
            $this->line('→ countries.json');
            $countriesRes = Http::timeout(120)->withOptions(['sink' => $seedDir.'/countries_download.json'])->get(self::COUNTRIES_URL);
            if (! $countriesRes->successful() && ! is_file($seedDir.'/countries_download.json')) {
                $this->error('Failed to download countries.json');

                return false;
            }

            $this->line('→ states.json');
            $statesRes = Http::timeout(180)->withOptions(['sink' => $seedDir.'/states_download.json'])->get(self::STATES_URL);
            if (! $statesRes->successful() && ! is_file($seedDir.'/states_download.json')) {
                $this->error('Failed to download states.json');

                return false;
            }

            $this->line('→ json-cities.json.gz (~25MB)');
            $gzPath = $seedDir.'/cities.json.gz';
            $citiesRes = Http::timeout(600)->withOptions(['sink' => $gzPath])->get(self::CITIES_URL);
            if (! is_file($gzPath) || filesize($gzPath) < 1_000_000) {
                $this->error('Failed to download cities archive');

                return false;
            }

            $this->line('Decompressing cities…');
            $citiesOut = $seedDir.'/cities.json';
            $gz = gzopen($gzPath, 'rb');
            if ($gz === false) {
                $this->error('Could not open cities.json.gz');

                return false;
            }
            $out = fopen($citiesOut, 'wb');
            while (! gzeof($gz)) {
                fwrite($out, gzread($gz, 1024 * 512));
            }
            gzclose($gz);
            fclose($out);

            $countries = json_decode(file_get_contents($seedDir.'/countries_download.json'), true) ?: [];
            $states = json_decode(file_get_contents($seedDir.'/states_download.json'), true) ?: [];

            $slimCountries = array_map(static fn ($c) => [
                'id' => $c['id'],
                'name' => $c['name'],
                'iso2' => $c['iso2'] ?? null,
                'iso3' => $c['iso3'] ?? null,
                'latitude' => $c['latitude'] ?? null,
                'longitude' => $c['longitude'] ?? null,
            ], $countries);

            $slimStates = array_map(static fn ($s) => [
                'id' => $s['id'],
                'name' => $s['name'],
                'country_id' => $s['country_id'],
                'latitude' => $s['latitude'] ?? null,
                'longitude' => $s['longitude'] ?? null,
            ], $states);

            file_put_contents(
                $seedDir.'/search_countries.json',
                json_encode($slimCountries, JSON_UNESCAPED_UNICODE)
            );
            file_put_contents(
                $seedDir.'/states.json',
                json_encode($slimStates, JSON_UNESCAPED_UNICODE)
            );

            @unlink($seedDir.'/countries_download.json');
            @unlink($seedDir.'/states_download.json');

            $this->info(sprintf(
                'Downloaded: %d countries, %d states, cities file %s MB',
                count($slimCountries),
                count($slimStates),
                round(filesize($citiesOut) / 1048576, 1)
            ));

            return true;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return false;
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\JobRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent sync of job roles from database/seeders/data/job_roles.json
 * (exported from a complete local catalog). Safe to re-run on production.
 *
 * Usage: php artisan db:seed --class=JobRoleCatalogSeeder --force
 */
class JobRoleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/job_roles.json');
        if (! is_file($path)) {
            $this->command?->error('Missing '.$path);

            return;
        }

        $names = json_decode((string) file_get_contents($path), true);
        if (! is_array($names) || $names === []) {
            $this->command?->error('job_roles.json is empty or invalid.');

            return;
        }

        $languages = loadLanguage();
        $localeCodes = collect($languages)->pluck('code')->filter()->unique()->values()->all();
        if ($localeCodes === []) {
            $localeCodes = ['en'];
        }

        $existing = [];
        if (Schema::hasTable('job_role_translations')) {
            $existing = DB::table('job_role_translations')
                ->where('locale', 'en')
                ->pluck('job_role_id', 'name')
                ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => (int) $id])
                ->all();
        }

        $created = 0;
        $skipped = 0;

        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);
            if (isset($existing[$key])) {
                $skipped++;
                continue;
            }

            $role = new JobRole();
            $role->save();

            foreach ($localeCodes as $code) {
                $role->translateOrNew($code)->name = $name;
            }
            $role->save();

            $existing[$key] = $role->id;
            $created++;
        }

        $this->command?->info("Job roles sync done. Created: {$created}, already present: {$skipped}, catalog size: ".count($names));
    }
}

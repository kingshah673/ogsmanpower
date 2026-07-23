<?php

namespace Database\Seeders;

use App\Models\JobRole;
use Illuminate\Database\Seeder;

class JobRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Prefer full catalog sync when data file exists; otherwise seed a small default set.
     */
    public function run()
    {
        $catalog = database_path('seeders/data/job_roles.json');
        if (is_file($catalog)) {
            $this->call(JobRoleCatalogSeeder::class);

            return;
        }

        if (! config('templatecookie.testing_mode')) {
            $job_roles = [
                'Team Leader', 'Manager', 'Assistant Manager', 'Executive', 'Director', 'Administrator',
            ];
        } else {
            $job_roles = [
                'Team Leader', 'Manager',
            ];
        }

        $languages = loadLanguage();

        foreach ($job_roles as $data) {
            $translation = new JobRole();
            $translation->save();

            foreach ($languages as $language) {
                $translation->translateOrNew($language->code)->name = $data;
            }

            $translation->save();
        }
    }
}

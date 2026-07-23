<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sync full attribute catalogs used by company job create/edit Select2 lookups.
 *
 * php artisan db:seed --class=JobAttributeCatalogSeeder --force
 */
class JobAttributeCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            JobRoleCatalogSeeder::class,
            IndustryCatalogSeeder::class,
            TagCatalogSeeder::class,
            SkillCatalogSeeder::class,
        ]);
    }
}

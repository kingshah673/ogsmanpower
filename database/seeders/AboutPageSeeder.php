<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    /**
     * Seeds / refreshes About page CMS from production OGS content.
     * Admins can edit afterwards at /admin/about.
     */
    public function run(): void
    {
        $this->call(OgsAboutProductionContentSeeder::class);
    }
}

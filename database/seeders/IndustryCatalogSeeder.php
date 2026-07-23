<?php

namespace Database\Seeders;

use App\Models\IndustryType;

/**
 * Usage: php artisan db:seed --class=IndustryCatalogSeeder --force
 */
class IndustryCatalogSeeder extends AbstractNameCatalogSeeder
{
    protected function jsonFile(): string
    {
        return 'industries.json';
    }

    protected function translationTable(): string
    {
        return 'industry_type_translations';
    }

    protected function foreignKey(): string
    {
        return 'industry_type_id';
    }

    protected function modelClass(): string
    {
        return IndustryType::class;
    }

    protected function label(): string
    {
        return 'Industries';
    }
}

<?php

namespace Database\Seeders;

use App\Models\Skill;

/**
 * Usage: php artisan db:seed --class=SkillCatalogSeeder --force
 */
class SkillCatalogSeeder extends AbstractNameCatalogSeeder
{
    protected function jsonFile(): string
    {
        return 'skills.json';
    }

    protected function translationTable(): string
    {
        return 'skill_translations';
    }

    protected function foreignKey(): string
    {
        return 'skill_id';
    }

    protected function modelClass(): string
    {
        return Skill::class;
    }

    protected function label(): string
    {
        return 'Skills';
    }
}

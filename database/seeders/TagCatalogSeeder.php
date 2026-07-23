<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;

/**
 * Usage: php artisan db:seed --class=TagCatalogSeeder --force
 */
class TagCatalogSeeder extends AbstractNameCatalogSeeder
{
    protected function jsonFile(): string
    {
        return 'tags.json';
    }

    protected function translationTable(): string
    {
        return 'tag_translations';
    }

    protected function foreignKey(): string
    {
        return 'tag_id';
    }

    protected function modelClass(): string
    {
        return Tag::class;
    }

    protected function label(): string
    {
        return 'Tags';
    }

    protected function beforeCreate(Model $model): void
    {
        $model->show_popular_list = 0;
    }
}

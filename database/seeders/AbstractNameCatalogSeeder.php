<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared idempotent name-catalog sync for translatable attribute models.
 */
abstract class AbstractNameCatalogSeeder extends Seeder
{
    abstract protected function jsonFile(): string;

    abstract protected function translationTable(): string;

    abstract protected function foreignKey(): string;

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    abstract protected function label(): string;

    protected function beforeCreate(Model $model): void
    {
        //
    }

    public function run(): void
    {
        $path = database_path('seeders/data/'.$this->jsonFile());
        if (! is_file($path)) {
            $this->command?->error('Missing '.$path);

            return;
        }

        $names = json_decode((string) file_get_contents($path), true);
        if (! is_array($names) || $names === []) {
            $this->command?->error($this->jsonFile().' is empty or invalid.');

            return;
        }

        $languages = loadLanguage();
        $localeCodes = collect($languages)->pluck('code')->filter()->unique()->values()->all();
        if ($localeCodes === []) {
            $localeCodes = ['en'];
        }

        $existing = [];
        $table = $this->translationTable();
        $fk = $this->foreignKey();
        if (Schema::hasTable($table)) {
            $existing = DB::table($table)
                ->where('locale', 'en')
                ->pluck($fk, 'name')
                ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => (int) $id])
                ->all();
        }

        $modelClass = $this->modelClass();
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

            /** @var Model $model */
            $model = new $modelClass();
            $this->beforeCreate($model);
            $model->save();

            foreach ($localeCodes as $code) {
                $model->translateOrNew($code)->name = $name;
            }
            $model->save();

            $existing[$key] = $model->id;
            $created++;
        }

        $this->command?->info($this->label()." sync done. Created: {$created}, already present: {$skipped}, catalog size: ".count($names));
    }
}

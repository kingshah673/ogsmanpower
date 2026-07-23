<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add any columns declared in config/schema_ensure.php that are missing from the DB.
 * Safe to re-run. Does not drop or alter existing columns.
 *
 * Usage:
 *   php artisan schema:ensure
 *   php artisan schema:ensure --table=applied_jobs
 *   php artisan schema:ensure --dry-run
 */
class SchemaEnsureCommand extends Command
{
    protected $signature = 'schema:ensure
                            {--table= : Only ensure this table}
                            {--dry-run : List missing columns without altering the DB}';

    protected $description = 'Add missing columns declared in config/schema_ensure.php (from controllers/models)';

    public function handle(): int
    {
        $map = config('schema_ensure', []);
        if (! is_array($map) || $map === []) {
            $this->error('config/schema_ensure.php is empty or missing.');

            return self::FAILURE;
        }

        $only = $this->option('table');
        $dry = (bool) $this->option('dry-run');
        $added = 0;
        $missingTables = 0;

        foreach ($map as $table => $columns) {
            if ($only && $table !== $only) {
                continue;
            }

            if (! Schema::hasTable($table)) {
                $this->error("MISSING TABLE: {$table}");
                $missingTables++;
                continue;
            }

            $toAdd = [];
            foreach ($columns as $name => $def) {
                if (! Schema::hasColumn($table, $name)) {
                    $toAdd[$name] = $def;
                }
            }

            if ($toAdd === []) {
                $this->line("OK  {$table} — all declared columns present");
                continue;
            }

            $this->warn(($dry ? '[dry-run] ' : '')."{$table}: adding ".implode(', ', array_keys($toAdd)));

            if ($dry) {
                $added += count($toAdd);
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($toAdd) {
                foreach ($toAdd as $name => $def) {
                    $this->addColumnToBlueprint($blueprint, $name, $def);
                }
            });

            $added += count($toAdd);
            $this->info("    → done");
        }

        $this->newLine();
        if ($dry) {
            $this->info("Dry-run complete. {$added} column(s) would be added.");
        } else {
            $this->info("Ensure complete. {$added} column(s) added.");
        }

        return $missingTables > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function addColumnToBlueprint(Blueprint $table, string $name, array $def): void
    {
        $type = $def['type'] ?? 'string';
        $nullable = (bool) ($def['nullable'] ?? true);
        $default = array_key_exists('default', $def) ? $def['default'] : null;
        $length = $def['length'] ?? null;

        $col = match ($type) {
            'unsignedBigInteger' => $table->unsignedBigInteger($name),
            'bigInteger' => $table->bigInteger($name),
            'unsignedInteger' => $table->unsignedInteger($name),
            'integer' => $table->integer($name),
            'smallInteger' => $table->smallInteger($name),
            'boolean' => $table->boolean($name),
            'text' => $table->text($name),
            'longText' => $table->longText($name),
            'date' => $table->date($name),
            'dateTime', 'timestamp' => $table->timestamp($name),
            'double' => $table->double($name),
            'json' => $table->json($name),
            default => $length ? $table->string($name, $length) : $table->string($name),
        };

        if ($nullable) {
            $col->nullable();
        }

        if ($default !== null) {
            $col->default($default);
        }
    }
}
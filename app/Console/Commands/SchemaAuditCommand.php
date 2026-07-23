<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SchemaAuditCommand extends Command
{
    protected $signature = 'schema:audit
                            {--export= : Write current DB schema JSON to this path}
                            {--compare= : Compare current DB against a previously exported schema JSON}
                            {--tables= : Comma-separated table names to check only}
                            {--critical : Only report tables/columns referenced by Job/Agency pivots and common breaks}
                            {--ignore-types : Hide TYPE DIFF noise (int vs tinyint, etc.)}';

    protected $description = 'Export or compare MySQL schema (tables/columns) between local and server';

    /** Known hotspots that have broken production before */
    private array $criticalTables = [
        'jobs',
        'job_agencies',
        'job_sub_agencies',
        'job_agents',
        'job_tag',
        'job_benefit',
        'job_skills',
        'agencies',
        'companies',
        'candidates',
        'candidate_resumes',
        'applied_jobs',
        'application_groups',
        'experiences',
        'experience_translations',
        'users',
        'search_countries',
        'states',
        'cities',
        'brokers',
        'broker_demands',
        'agent_invites',
        'professions',
        'industry_types',
        'job_roles',
    ];

    public function handle(): int
    {
        $schema = $this->captureSchema();

        if ($export = $this->option('export')) {
            $path = $this->resolvePath($export);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info("Exported schema for {$schema['table_count']} tables → {$path}");
            $this->line('Copy this file to the server (or commit it), then run:');
            $this->line('  php artisan schema:audit --compare='.$export);

            return self::SUCCESS;
        }

        if ($compare = $this->option('compare')) {
            $path = $this->resolvePath($compare);
            if (! is_file($path)) {
                $this->error("Baseline not found: {$path}");
                $this->line('On local first: php artisan schema:audit --export=storage/app/schema-baseline.json');

                return self::FAILURE;
            }

            $baseline = json_decode(File::get($path), true);
            if (! is_array($baseline) || empty($baseline['tables'])) {
                $this->error('Invalid baseline JSON.');

                return self::FAILURE;
            }

            return $this->compareSchemas($baseline, $schema);
        }

        // Default: print summary of current DB (+ critical gaps)
        $this->info("Current database: {$schema['database']} ({$schema['table_count']} tables)");
        $this->newLine();
        $this->warn('No --export / --compare given. Checking critical tables only…');
        $this->newLine();

        $missing = [];
        foreach ($this->criticalTables as $table) {
            if (! isset($schema['tables'][$table])) {
                $missing[] = $table;
                $this->error("MISSING TABLE: {$table}");
            } else {
                $cols = count($schema['tables'][$table]['columns']);
                $this->line("OK  {$table} ({$cols} columns)");
            }
        }

        if ($missing) {
            $this->newLine();
            $this->error(count($missing).' critical table(s) missing on this database.');
            $this->line('Tip: export from local, then compare here:');
            $this->line('  php artisan schema:audit --export=storage/app/schema-baseline.json');
            $this->line('  php artisan schema:audit --compare=storage/app/schema-baseline.json');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Critical tables present. For a full local↔server diff, use --export / --compare.');

        return self::SUCCESS;
    }

    private function captureSchema(): array
    {
        $database = DB::getDatabaseName();
        $only = $this->option('tables')
            ? array_filter(array_map('trim', explode(',', (string) $this->option('tables'))))
            : null;

        $tableNames = Schema::getTableListing();
        sort($tableNames);

        $tables = [];
        foreach ($tableNames as $table) {
            if ($only && ! in_array($table, $only, true)) {
                continue;
            }
            if ($this->option('critical') && ! in_array($table, $this->criticalTables, true)) {
                continue;
            }

            $columns = [];
            foreach (Schema::getColumns($table) as $col) {
                $columns[$col['name']] = [
                    'type' => $col['type_name'] ?? $col['type'] ?? null,
                    'nullable' => (bool) ($col['nullable'] ?? false),
                    'default' => $col['default'] ?? null,
                ];
            }
            ksort($columns);

            $tables[$table] = [
                'columns' => $columns,
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'database' => $database,
            'connection' => config('database.default'),
            'table_count' => count($tables),
            'tables' => $tables,
        ];
    }

    private function compareSchemas(array $baseline, array $current): int
    {
        $this->info("Baseline DB: {$baseline['database']} ({$baseline['table_count']} tables) @ ".($baseline['generated_at'] ?? '?'));
        $this->info("Current  DB: {$current['database']} ({$current['table_count']} tables)");
        $this->newLine();

        $baselineTables = $baseline['tables'];
        $currentTables = $current['tables'];

        if ($this->option('critical')) {
            $baselineTables = array_intersect_key($baselineTables, array_flip($this->criticalTables));
            $currentTables = array_intersect_key($currentTables, array_flip($this->criticalTables));
        }

        $missingTables = array_diff(array_keys($baselineTables), array_keys($currentTables));
        $extraTables = array_diff(array_keys($currentTables), array_keys($baselineTables));

        $missingColumns = [];
        $extraColumns = [];
        $typeMismatches = [];

        $allTables = array_unique(array_merge(array_keys($baselineTables), array_keys($currentTables)));
        sort($allTables);

        foreach ($allTables as $table) {
            $baseCols = $baselineTables[$table]['columns'] ?? [];
            $curCols = $currentTables[$table]['columns'] ?? [];

            if (isset($baselineTables[$table]) && isset($currentTables[$table])) {
                foreach ($baseCols as $col => $def) {
                    if (! isset($curCols[$col])) {
                        $missingColumns[] = "{$table}.{$col}";
                        continue;
                    }
                    $baseType = strtolower((string) ($def['type'] ?? ''));
                    $curType = strtolower((string) ($curCols[$col]['type'] ?? ''));
                    if ($baseType && $curType && $baseType !== $curType) {
                        $typeMismatches[] = "{$table}.{$col} (baseline={$baseType}, current={$curType})";
                    }
                }
                foreach ($curCols as $col => $def) {
                    if (! isset($baseCols[$col])) {
                        $extraColumns[] = "{$table}.{$col}";
                    }
                }
            }
        }

        $rows = [];
        foreach ($missingTables as $t) {
            $rows[] = ['MISSING TABLE', $t, 'in baseline, not on current DB — add via migrate on current'];
        }
        foreach ($missingColumns as $c) {
            $rows[] = ['MISSING COLUMN', $c, 'in baseline, not on current DB — add via migrate on current'];
        }
        foreach ($extraTables as $t) {
            $rows[] = ['EXTRA TABLE', $t, 'on current only — missing from baseline (add to local if needed)'];
        }
        foreach ($extraColumns as $c) {
            $rows[] = ['EXTRA COLUMN', $c, 'on current only — missing from baseline (add to local if needed)'];
        }
        if (! $this->option('ignore-types')) {
            foreach ($typeMismatches as $m) {
                $rows[] = ['TYPE DIFF', $m, 'usually safe (int/tinyint, varchar/text)'];
            }
        }

        if (! $rows) {
            $this->info('Schemas match for compared tables/columns.');

            return self::SUCCESS;
        }

        $this->table(['Issue', 'Name', 'Notes'], $rows);
        $this->newLine();
        $this->line('Legend:');
        $this->line('  MISSING = exists in baseline, absent on this DB');
        $this->line('  EXTRA   = exists on this DB, absent in baseline');
        $this->newLine();
        $this->warn(sprintf(
            '%d missing table(s), %d missing column(s), %d extra table(s), %d extra column(s), %d type diff(s).',
            count($missingTables),
            count($missingColumns),
            count($extraTables),
            count($extraColumns),
            $this->option('ignore-types') ? 0 : count($typeMismatches)
        ));

        if (count($missingTables) || count($missingColumns)) {
            $this->error('Current DB is behind baseline — run migrations on this environment.');

            return self::FAILURE;
        }

        if (count($extraTables) || count($extraColumns)) {
            $this->warn('Current DB has fields baseline does not — sync those onto the other environment if needed.');
        }

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
            return $path;
        }

        return base_path($path);
    }
}

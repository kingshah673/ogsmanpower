<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports the master Job Titles list (provided as Excel in the project root)
 * into the `professions` table, which backs the "Job Title" and "Profession"
 * dropdowns on the candidate/seeker settings page.
 *
 * Idempotent: only titles not already present (matched by English name,
 * case-insensitive) are added — existing professions and candidate links are
 * left untouched. Safe to re-run after the Excel is updated.
 */
class JobTitlesProfessionSeeder extends Seeder
{
    public function run(): void
    {
        // Prefer the "Updated" file; fall back to the combined one.
        $candidates = ['Job_Titles_Updated.xlsx', 'Industries_JobTitles_Final.xlsx'];
        $path = null;
        foreach ($candidates as $f) {
            if (is_file(base_path($f))) { $path = base_path($f); break; }
        }
        if (! $path) {
            $this->command->error('Job titles Excel not found in project root ('.implode(', ', $candidates).').');
            return;
        }

        // Read titles from the "Job Titles" sheet (first column, skip header row).
        $spreadsheet = IOFactory::load($path);
        $names = $spreadsheet->getSheetNames();
        $sheet = in_array('Job Titles', $names, true)
            ? $spreadsheet->getSheetByName('Job Titles')
            : $spreadsheet->getSheet(0);

        $rows = $sheet->toArray(null, true, false, false);
        $titles = [];
        for ($i = 1; $i < count($rows); $i++) {
            $v = trim((string) ($rows[$i][0] ?? ''));
            if ($v !== '') {
                $titles[mb_strtolower($v)] = $v; // dedupe by lowercase key
            }
        }

        // Existing professions, matched by their English name.
        $existing = [];
        foreach (DB::table('profession_translations')->where('locale', 'en')->pluck('name') as $n) {
            $existing[mb_strtolower(trim((string) $n))] = true;
        }

        // Populate the same locales the data already uses (fallback to en).
        $locales = DB::table('profession_translations')->distinct()->pluck('locale')->all();
        if (empty($locales)) {
            $locales = ['en'];
        }

        $toAdd = [];
        foreach ($titles as $key => $name) {
            if (! isset($existing[$key])) {
                $toAdd[] = $name;
            }
        }

        if (empty($toAdd)) {
            $this->command->info('All Excel job titles are already present — nothing to add.');
            return;
        }

        $now = now();
        $added = 0;
        DB::transaction(function () use ($toAdd, $locales, $now, &$added) {
            foreach ($toAdd as $name) {
                $professionId = DB::table('professions')->insertGetId([
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $translations = [];
                foreach ($locales as $locale) {
                    $translations[] = [
                        'profession_id' => $professionId,
                        'locale'        => $locale,
                        'name'          => $name,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
                DB::table('profession_translations')->insert($translations);
                $added++;
            }
        });

        $this->command->info("Added {$added} new professions (job titles).");
    }
}

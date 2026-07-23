<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Company applications eager-load experience:id,years — production was missing experiences.years.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('schema:ensure');

        // Best-effort: derive years from translated names like "3 Years" when still 0
        if (Schema::hasTable('experiences') && Schema::hasColumn('experiences', 'years')
            && Schema::hasTable('experience_translations')) {
            $rows = DB::table('experience_translations')
                ->select('experience_id', 'name')
                ->where('locale', 'en')
                ->get();

            foreach ($rows as $row) {
                if (preg_match('/(\d+)/', (string) $row->name, $m)) {
                    DB::table('experiences')
                        ->where('id', $row->experience_id)
                        ->where(function ($q) {
                            $q->whereNull('years')->orWhere('years', 0);
                        })
                        ->update(['years' => (int) $m[1]]);
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive
    }
};

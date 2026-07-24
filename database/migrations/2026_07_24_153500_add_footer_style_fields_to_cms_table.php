<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms')) {
            return;
        }

        Schema::table('cms', function (Blueprint $table) {
            if (! Schema::hasColumn('cms', 'footer_bg_color')) {
                $table->string('footer_bg_color', 20)->nullable()->after('footer_youtube_link');
            }
            if (! Schema::hasColumn('cms', 'footer_text_color')) {
                $table->string('footer_text_color', 20)->nullable();
            }
            if (! Schema::hasColumn('cms', 'footer_accent_color')) {
                $table->string('footer_accent_color', 20)->nullable();
            }
            if (! Schema::hasColumn('cms', 'footer_copyright')) {
                $table->string('footer_copyright', 500)->nullable();
            }
            if (! Schema::hasColumn('cms', 'footer_powered_by')) {
                $table->string('footer_powered_by', 255)->nullable();
            }
            if (! Schema::hasColumn('cms', 'footer_badge_image')) {
                $table->string('footer_badge_image')->nullable();
            }
            if (! Schema::hasColumn('cms', 'footer_badge_enabled')) {
                $table->boolean('footer_badge_enabled')->default(true);
            }
            if (! Schema::hasColumn('cms', 'footer_badge_position')) {
                $table->string('footer_badge_position', 20)->nullable()->default('right');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cms')) {
            return;
        }

        Schema::table('cms', function (Blueprint $table) {
            foreach ([
                'footer_bg_color',
                'footer_text_color',
                'footer_accent_color',
                'footer_copyright',
                'footer_powered_by',
                'footer_badge_image',
                'footer_badge_enabled',
                'footer_badge_position',
            ] as $column) {
                if (Schema::hasColumn('cms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

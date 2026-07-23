<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('about_hero')) {
        Schema::create('about_hero', function (Blueprint $table) {
            $table->id();
            $table->string('badge_text')->nullable();
            $table->string('headline', 500)->nullable();
            $table->text('subheadline')->nullable();
            $table->string('pill_1')->nullable();
            $table->string('pill_2')->nullable();
            $table->string('pill_3')->nullable();
            $table->string('stat_1_val', 50)->nullable();
            $table->string('stat_1_lbl')->nullable();
            $table->string('stat_2_val', 50)->nullable();
            $table->string('stat_2_lbl')->nullable();
            $table->string('stat_3_val', 50)->nullable();
            $table->string('stat_3_lbl')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
        }

        if (! Schema::hasTable('about_story')) {
        Schema::create('about_story', function (Blueprint $table) {
            $table->id();
            $table->string('section_label')->nullable();
            $table->string('headline', 500)->nullable();
            $table->text('quote')->nullable();
            $table->text('body_1')->nullable();
            $table->text('body_2')->nullable();
            $table->text('body_3')->nullable();
            $table->string('license_text')->nullable();
            $table->string('card_1_num', 50)->nullable();
            $table->string('card_1_lbl')->nullable();
            $table->string('card_1_desc')->nullable();
            $table->string('card_2_num', 50)->nullable();
            $table->string('card_2_lbl')->nullable();
            $table->string('card_2_desc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
        }

        if (! Schema::hasTable('about_features')) {
        Schema::create('about_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('icon_emoji', 20)->nullable();
            $table->string('icon_svg_url')->nullable();
            $table->string('icon_bg_color', 20)->default('#E8F5E9');
            $table->string('title');
            $table->string('teaser', 500)->nullable();
            $table->text('modal_body')->nullable();
            $table->string('badge_tags', 500)->nullable();
            $table->string('cta_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('about_metrics')) {
        Schema::create('about_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('value', 50)->nullable();
            $table->string('label')->nullable();
            $table->string('icon', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
        }

        if (! Schema::hasTable('about_industries')) {
        Schema::create('about_industries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('icon', 20)->nullable();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
        }

        if (! Schema::hasTable('about_ceo')) {
        Schema::create('about_ceo', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('location')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('experience', 50)->nullable();
            $table->text('quote')->nullable();
            $table->text('bio')->nullable();
            $table->string('tags', 500)->nullable();
            $table->text('creds')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
        }

        if (! Schema::hasTable('about_videos')) {
        Schema::create('about_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title');
            $table->string('description', 500)->nullable();
            $table->string('video_type', 20)->default('youtube');
            $table->string('video_url', 500);
            $table->string('thumbnail', 500)->nullable();
            $table->string('duration', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('about_offices')) {
        Schema::create('about_offices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('flag', 20)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
        }

        if (! Schema::hasTable('about_social_links')) {
        Schema::create('about_social_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('platform');
            $table->string('icon', 20)->nullable();
            $table->string('url', 500)->default('#');
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
        }

        if (! Schema::hasTable('about_config')) {
        Schema::create('about_config', function (Blueprint $table) {
            $table->string('cfg_key')->primary();
            $table->text('cfg_value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('about_config');
        Schema::dropIfExists('about_social_links');
        Schema::dropIfExists('about_offices');
        Schema::dropIfExists('about_videos');
        Schema::dropIfExists('about_ceo');
        Schema::dropIfExists('about_industries');
        Schema::dropIfExists('about_metrics');
        Schema::dropIfExists('about_features');
        Schema::dropIfExists('about_story');
        Schema::dropIfExists('about_hero');
    }
};

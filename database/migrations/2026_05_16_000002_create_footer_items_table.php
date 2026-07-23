<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An "item" is a single piece of content inside a panel.
 * Supported types:
 *   - link    : a menu link  (label + url)
 *   - heading : a sub-heading inside the panel
 *   - text    : a free text / paragraph block
 *   - image   : an uploaded image, optionally clickable
 *
 * Items can be reordered by drag & drop, and dragged between panels.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('footer_panel_id')
                  ->constrained('footer_panels')
                  ->cascadeOnDelete();
            $table->enum('type', ['link', 'heading', 'text', 'image'])->default('link');
            $table->string('label')->nullable();      // link text / heading text / image alt
            $table->string('url', 2048)->nullable();  // link target / image link target
            $table->text('content')->nullable();      // paragraph text
            $table->string('image_path')->nullable(); // stored upload path
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_items');
    }
};

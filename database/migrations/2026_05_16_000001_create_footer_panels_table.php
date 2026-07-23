<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "panel" is one column/block of the footer (e.g. "Quick Links", "Contact").
 * You can create as many as you want and reorder them by drag & drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_panels', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();          // column heading shown on the site
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_panels');
    }
};

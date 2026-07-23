<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bookmark_candidate_agency')) {
            Schema::create('bookmark_candidate_agency', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id');
                $table->unsignedBigInteger('candidate_id');
                $table->timestamps();
                $table->unique(['agency_id', 'candidate_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmark_candidate_agency');
    }
};

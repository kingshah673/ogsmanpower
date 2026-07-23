<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_plans')) {
            Schema::create('candidate_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('price', 10, 2)->default(0);
                $table->unsignedInteger('duration')->default(30)->comment('Duration in days');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('candidate_plans') && DB::table('candidate_plans')->count() === 0) {
            DB::table('candidate_plans')->insert([
                'name' => 'Featured Profile',
                'price' => 0,
                'duration' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_plans');
    }
};

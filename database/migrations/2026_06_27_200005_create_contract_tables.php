<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->id();
                $table->string('contract_no')->nullable();
                $table->string('title');
                $table->longText('content');
                $table->string('status')->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // No DB-level FKs: production may already have a legacy `contracts`
        // table (engine/type mismatch → errno 150). App enforces relations.
        if (! Schema::hasTable('contract_parties')) {
            Schema::create('contract_parties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contract_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('role');
                $table->boolean('is_signed')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contract_signatures')) {
            Schema::create('contract_signatures', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contract_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('otp')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contract_templates')) {
            Schema::create('contract_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->longText('content');
                $table->string('category')->nullable();
                $table->boolean('status')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');
        Schema::dropIfExists('contract_parties');
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('contracts');
    }
};

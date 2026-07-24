<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // Shared hosts often already have a legacy `contracts` table whose `id`
        // is INT (signed) / different engine — FK to bigint then fails (errno 150).
        $canFk = $this->contractsIdSupportsBigintFk();

        if (! Schema::hasTable('contract_parties')) {
            Schema::create('contract_parties', function (Blueprint $table) use ($canFk) {
                $table->id();
                $table->unsignedBigInteger('contract_id');
                $table->unsignedBigInteger('user_id');
                $table->string('role');
                $table->boolean('is_signed')->default(false);
                $table->timestamps();
                $table->index('contract_id');
                if ($canFk) {
                    $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
                }
            });
        }

        if (! Schema::hasTable('contract_signatures')) {
            Schema::create('contract_signatures', function (Blueprint $table) use ($canFk) {
                $table->id();
                $table->unsignedBigInteger('contract_id');
                $table->unsignedBigInteger('user_id');
                $table->string('otp')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->index('contract_id');
                if ($canFk) {
                    $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
                }
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

    private function contractsIdSupportsBigintFk(): bool
    {
        if (! Schema::hasTable('contracts')) {
            return false;
        }

        $row = DB::selectOne(
            'SELECT DATA_TYPE AS data_type, COLUMN_TYPE AS column_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['contracts', 'id']
        );

        if (! $row) {
            return false;
        }

        $columnType = strtolower((string) ($row->column_type ?? ''));

        // Laravel $table->id() => bigint unsigned
        return str_contains($columnType, 'bigint') && str_contains($columnType, 'unsigned');
    }
};

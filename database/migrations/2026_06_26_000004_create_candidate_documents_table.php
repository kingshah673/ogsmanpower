<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create the table if it does not exist yet (fresh install)
        if (!Schema::hasTable('candidate_documents')) {
            Schema::create('candidate_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->string('file_reference', 255)->nullable();
                $table->enum('document_type', ['cv', 'passport', 'certificate', 'license', 'other'])->nullable();
                $table->enum('source_channel', ['web', 'whatsapp', 'api'])->default('web');
                $table->string('original_name', 255)->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->string('message_id', 255)->nullable()->unique();
                $table->tinyInteger('is_primary')->default(0);
                // Legacy single-row document image columns
                $table->string('passport_image')->nullable();
                $table->string('cnic_front')->nullable();
                $table->string('cnic_back')->nullable();
                $table->string('police_character_certificate')->nullable();
                $table->string('medical')->nullable();
                $table->string('navtec_report')->nullable();
                $table->string('license_image')->nullable();
                $table->timestamps();
            });
        } else {
            // Table already exists — add any missing columns
            Schema::table('candidate_documents', function (Blueprint $table) {
                $cols = [
                    'file_reference'              => fn($t) => $t->string('file_reference', 255)->nullable()->after('candidate_id'),
                    'document_type'               => fn($t) => $t->enum('document_type', ['cv', 'passport', 'certificate', 'license', 'other'])->nullable()->after('file_reference'),
                    'source_channel'              => fn($t) => $t->enum('source_channel', ['web', 'whatsapp', 'api'])->default('web')->after('document_type'),
                    'original_name'               => fn($t) => $t->string('original_name', 255)->nullable()->after('source_channel'),
                    'mime_type'                   => fn($t) => $t->string('mime_type', 100)->nullable()->after('original_name'),
                    'file_size'                   => fn($t) => $t->unsignedInteger('file_size')->nullable()->after('mime_type'),
                    'message_id'                  => fn($t) => $t->string('message_id', 255)->nullable()->unique()->after('file_size'),
                    'is_primary'                  => fn($t) => $t->tinyInteger('is_primary')->default(0)->after('message_id'),
                    'passport_image'              => fn($t) => $t->string('passport_image')->nullable(),
                    'cnic_front'                  => fn($t) => $t->string('cnic_front')->nullable(),
                    'cnic_back'                   => fn($t) => $t->string('cnic_back')->nullable(),
                    'police_character_certificate'=> fn($t) => $t->string('police_character_certificate')->nullable(),
                    'medical'                     => fn($t) => $t->string('medical')->nullable(),
                    'navtec_report'               => fn($t) => $t->string('navtec_report')->nullable(),
                    'license_image'               => fn($t) => $t->string('license_image')->nullable(),
                ];

                foreach ($cols as $col => $definition) {
                    if (!Schema::hasColumn('candidate_documents', $col)) {
                        $definition($table);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_documents');
    }
};

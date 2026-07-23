<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sync columns that exist on local (complete) but were missing on production.
 * Safe: only adds missing columns; does not change type diffs (int/tinyint, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agencies')) {
            Schema::table('agencies', function (Blueprint $table) {
                if (! Schema::hasColumn('agencies', 'logo')) {
                    $table->string('logo')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'banner')) {
                    $table->string('banner')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'website')) {
                    $table->string('website')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'bio')) {
                    $table->text('bio')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'vision')) {
                    $table->text('vision')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'establishment_date')) {
                    $table->date('establishment_date')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'industry_type_id')) {
                    $table->unsignedBigInteger('industry_type_id')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'organization_type_id')) {
                    $table->unsignedBigInteger('organization_type_id')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'team_size_id')) {
                    $table->unsignedBigInteger('team_size_id')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'parent_agency_id')) {
                    $table->unsignedBigInteger('parent_agency_id')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'is_profile_verified')) {
                    $table->boolean('is_profile_verified')->default(false);
                }
                if (! Schema::hasColumn('agencies', 'document_verified_at')) {
                    $table->timestamp('document_verified_at')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'profile_completion')) {
                    $table->boolean('profile_completion')->default(false);
                }
                if (! Schema::hasColumn('agencies', 'question_feature_enable')) {
                    $table->boolean('question_feature_enable')->default(true);
                }
                if (! Schema::hasColumn('agencies', 'visibility')) {
                    $table->boolean('visibility')->default(true);
                }
                if (! Schema::hasColumn('agencies', 'total_views')) {
                    $table->unsignedBigInteger('total_views')->default(0);
                }
                if (! Schema::hasColumn('agencies', 'exact_location')) {
                    $table->string('exact_location')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'neighborhood')) {
                    $table->string('neighborhood')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'locality')) {
                    $table->string('locality')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'place')) {
                    $table->string('place')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'district')) {
                    $table->string('district')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'postcode')) {
                    $table->string('postcode')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'region')) {
                    $table->string('region')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'lat')) {
                    $table->double('lat')->nullable();
                }
                if (! Schema::hasColumn('agencies', 'long')) {
                    $table->double('long')->nullable();
                }
            });
        }

        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                if (! Schema::hasColumn('candidates', 'admin_id')) {
                    $table->unsignedBigInteger('admin_id')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'agency_id')) {
                    $table->unsignedBigInteger('agency_id')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'owner_id')) {
                    $table->unsignedBigInteger('owner_id')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'owner_type')) {
                    $table->enum('owner_type', ['agent', 'agency', 'company', 'public', 'admin'])->nullable();
                }
                if (! Schema::hasColumn('candidates', 'ats_score')) {
                    $table->integer('ats_score')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'ats_report')) {
                    $table->text('ats_report')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'cnic_document')) {
                    $table->string('cnic_document')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'driving_license')) {
                    $table->string('driving_license')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'other_document')) {
                    $table->string('other_document')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'passport_file')) {
                    $table->string('passport_file')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'expected_location')) {
                    $table->string('expected_location')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'expected_salary')) {
                    $table->string('expected_salary')->nullable();
                }
                if (! Schema::hasColumn('candidates', 'industry_type')) {
                    $table->string('industry_type')->nullable();
                }
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('jobs', 'admin_id')) {
                    $table->unsignedBigInteger('admin_id')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'repost_job_id')) {
                    $table->unsignedBigInteger('repost_job_id')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'assigned_agency')) {
                    $table->longText('assigned_agency')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'industry_type')) {
                    $table->string('industry_type')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'is_restricted')) {
                    $table->boolean('is_restricted')->default(false)->nullable();
                }
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'otp_sent_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('otp_sent_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Non-destructive: keep columns on rollback (safe for production).
    }
};

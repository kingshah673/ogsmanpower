<?php

use App\Models\Company;
use App\Models\CompanyVerificationDocumentAssignment;
use App\Models\EmployerVerificationDocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employer_verification_document_types')) {
            return;
        }

        $defaults = [
            [
                'slug' => 'trade_license',
                'label' => 'Trading / Trade License',
                'help_text' => 'Upload a clear copy of your valid trading or trade license (PDF or image).',
                'is_required' => true,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'company_registration',
                'label' => 'Company Registration / CR Certificate',
                'help_text' => 'Upload your company registration certificate or CR document (PDF or image).',
                'is_required' => true,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($defaults as $row) {
            EmployerVerificationDocumentType::query()->updateOrCreate(
                ['slug' => $row['slug']],
                $row
            );
        }

        $typeIds = EmployerVerificationDocumentType::query()->pluck('id', 'slug');

        Company::query()->select('id')->chunkById(100, function ($companies) use ($typeIds) {
            foreach ($companies as $company) {
                foreach ($typeIds as $slug => $typeId) {
                    CompanyVerificationDocumentAssignment::query()->firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'document_type_id' => $typeId,
                        ],
                        [
                            'is_required' => true,
                            'sort_order' => $slug === 'trade_license' ? 1 : 2,
                        ]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employer_verification_document_types')) {
            return;
        }

        EmployerVerificationDocumentType::query()
            ->whereIn('slug', ['trade_license', 'company_registration'])
            ->delete();
    }
};

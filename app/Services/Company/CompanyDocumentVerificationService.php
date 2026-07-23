<?php

namespace App\Services\Company;

use App\Models\Admin;
use App\Models\Company;
use App\Models\CompanyVerificationDocumentAssignment;
use App\Models\EmployerVerificationDocumentType;
use App\Notifications\SendProfileVerificationDocumentSubmittedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CompanyDocumentVerificationService
{
    public const STATUS_UPLOAD_REQUIRED = 'upload_required';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_RESUBMIT_REQUIRED = 'resubmit_required';

    public const MEDIA_COLLECTION = 'verification_documents';

    public static function acceptedMimeTypes(): string
    {
        return 'mimes:jpg,jpeg,png,gif,webp,bmp,pdf';
    }

    /**
     * @return array<int, string|\Illuminate\Validation\Rules\File>
     */
    public static function acceptedFileRules(): array
    {
        return [
            'file',
            'mimes:jpg,jpeg,png,gif,webp,bmp,pdf',
            'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,application/pdf,application/x-pdf',
            'max:8192',
        ];
    }

    /**
     * @return array<string, array{label: string, help: string, is_required: bool, type_id: int|null}>
     */
    public static function assignedDocumentTypes(Company $company): array
    {
        $assignments = $company->verificationDocumentAssignments()
            ->with('documentType')
            ->whereHas('documentType', fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($assignments->isEmpty()) {
            return self::fallbackDocumentTypes();
        }

        $types = [];

        foreach ($assignments as $assignment) {
            $documentType = $assignment->documentType;

            if (! $documentType) {
                continue;
            }

            $types[$documentType->slug] = [
                'label' => $documentType->label,
                'help' => $documentType->help_text ?? '',
                'is_required' => (bool) $assignment->is_required,
                'type_id' => $documentType->id,
            ];
        }

        return $types;
    }

    /**
     * @return array<string, array{label: string, help: string, is_required: bool, type_id: int|null}>
     */
    protected static function fallbackDocumentTypes(): array
    {
        $types = [];

        foreach (EmployerVerificationDocumentType::query()->active()->ordered()->get() as $documentType) {
            $types[$documentType->slug] = [
                'label' => $documentType->label,
                'help' => $documentType->help_text ?? '',
                'is_required' => (bool) $documentType->is_required,
                'type_id' => $documentType->id,
            ];
        }

        if ($types !== []) {
            return $types;
        }

        return [
            'trade_license' => [
                'label' => __('employer_doc_trade_license'),
                'help' => __('employer_doc_trade_license_help'),
                'is_required' => true,
                'type_id' => null,
            ],
            'company_registration' => [
                'label' => __('employer_doc_company_registration'),
                'help' => __('employer_doc_company_registration_help'),
                'is_required' => true,
                'type_id' => null,
            ],
        ];
    }

    /**
     * @deprecated Use assignedDocumentTypes() — kept for backward compatibility.
     *
     * @return array<string, array{label: string, help: string}>
     */
    public static function requiredDocumentTypes(?Company $company = null): array
    {
        if ($company) {
            return collect(self::assignedDocumentTypes($company))
                ->map(fn ($meta) => [
                    'label' => $meta['label'],
                    'help' => $meta['help'],
                ])
                ->all();
        }

        return collect(self::fallbackDocumentTypes())
            ->map(fn ($meta) => [
                'label' => $meta['label'],
                'help' => $meta['help'],
            ])
            ->all();
    }

    /**
     * @return array<string, array{label: string, help: string, is_required: bool, uploaded: bool, media: mixed}>
     */
    public static function documentUploadSummary(Company $company): array
    {
        $summary = [];

        foreach (self::assignedDocumentTypes($company) as $slug => $meta) {
            $summary[$slug] = array_merge($meta, [
                'uploaded' => self::hasUploadedType($company, $slug),
                'media' => self::getMediaForType($company, $slug),
            ]);
        }

        return $summary;
    }

    public static function hasUploadedType(Company $company, string $type): bool
    {
        return self::getMediaForType($company, $type) !== null;
    }

    public static function getMediaForType(Company $company, string $type)
    {
        $fromUnified = $company->getMedia(self::MEDIA_COLLECTION)
            ->first(fn ($media) => $media->getCustomProperty('document_type') === $type);

        if ($fromUnified) {
            return $fromUnified;
        }

        if ($company->hasMedia($type)) {
            return $company->getFirstMedia($type);
        }

        return $type === 'trade_license' && $company->hasMedia('document')
            ? $company->getFirstMedia('document')
            : null;
    }

    public static function missingDocumentTypes(Company $company): array
    {
        return array_keys(self::missingDocumentDetails($company));
    }

    /**
     * @return array<string, array{label: string, help: string, is_required: bool, type_id: int|null}>
     */
    public static function missingDocumentDetails(Company $company): array
    {
        $missing = [];

        foreach (self::assignedDocumentTypes($company) as $slug => $meta) {
            if ($meta['is_required'] && ! self::hasUploadedType($company, $slug)) {
                $missing[$slug] = $meta;
            }
        }

        return $missing;
    }

    public static function hasAllRequiredDocuments(?Company $company): bool
    {
        return $company && self::missingDocumentTypes($company) === [];
    }

    public static function isApproved(?Company $company): bool
    {
        if (! $company) {
            return false;
        }

        return (bool) $company->is_profile_verified
            && $company->document_verified_at
            && ! $company->documents_resubmit_required;
    }

    public static function status(?Company $company): ?string
    {
        if (! $company) {
            return self::STATUS_UPLOAD_REQUIRED;
        }

        if ($company->documents_resubmit_required) {
            return self::STATUS_RESUBMIT_REQUIRED;
        }

        if (! self::hasAllRequiredDocuments($company)) {
            return self::STATUS_UPLOAD_REQUIRED;
        }

        if (! self::isApproved($company)) {
            return self::STATUS_PENDING_APPROVAL;
        }

        return null;
    }

    public static function verifyDocumentsRoute(): string
    {
        return route('company.verify.documents.index');
    }

    public static function redirectIfBlocked(?Company $company)
    {
        $status = self::status($company);

        if ($status === self::STATUS_RESUBMIT_REQUIRED) {
            flashWarning(__('employer_documents_resubmit_required'));

            return redirect()->to(self::verifyDocumentsRoute());
        }

        if ($status === self::STATUS_UPLOAD_REQUIRED) {
            flashWarning(__('employer_documents_upload_required'));

            return redirect()->to(self::verifyDocumentsRoute());
        }

        if ($status === self::STATUS_PENDING_APPROVAL) {
            flashWarning(__('employer_documents_pending_approval'));

            return redirect()->to(self::verifyDocumentsRoute());
        }

        return null;
    }

    public static function isValidDocumentSlugForCompany(Company $company, string $slug): bool
    {
        return array_key_exists($slug, self::assignedDocumentTypes($company));
    }

    public static function storeDocument(Company $company, string $type, $file): void
    {
        $company->getMedia(self::MEDIA_COLLECTION)
            ->filter(fn ($media) => $media->getCustomProperty('document_type') === $type)
            ->each->delete();

        if ($company->hasMedia($type)) {
            $company->clearMediaCollection($type);
        }

        if ($type === 'trade_license' && $company->hasMedia('document')) {
            $company->clearMediaCollection('document');
        }

        $company->addMedia($file)
            ->withCustomProperties(['document_type' => $type])
            ->toMediaCollection(self::MEDIA_COLLECTION, 'public');

        $company->unsetRelation('media');
    }

    public static function markSubmittedForReview(Company $company): void
    {
        $company->update([
            'documents_resubmit_required' => false,
            'document_verified_at' => null,
            'is_profile_verified' => false,
        ]);
    }

    public static function assignDefaultDocumentTypes(Company $company): void
    {
        $defaults = EmployerVerificationDocumentType::query()
            ->active()
            ->where('is_default', true)
            ->ordered()
            ->get();

        if ($defaults->isEmpty()) {
            $defaults = EmployerVerificationDocumentType::query()->active()->ordered()->get();
        }

        foreach ($defaults as $index => $documentType) {
            CompanyVerificationDocumentAssignment::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'document_type_id' => $documentType->id,
                ],
                [
                    'is_required' => $documentType->is_required,
                    'sort_order' => $documentType->sort_order ?: ($index + 1),
                ]
            );
        }
    }

    public static function syncCompanyAssignments(Company $company, array $assignments): void
    {
        $company->verificationDocumentAssignments()->delete();

        foreach ($assignments as $index => $assignment) {
            if (empty($assignment['document_type_id'])) {
                continue;
            }

            CompanyVerificationDocumentAssignment::query()->create([
                'company_id' => $company->id,
                'document_type_id' => $assignment['document_type_id'],
                'is_required' => (bool) ($assignment['is_required'] ?? true),
                'sort_order' => (int) ($assignment['sort_order'] ?? ($index + 1)),
            ]);
        }
    }

    public static function allActiveDocumentTypes(): Collection
    {
        return EmployerVerificationDocumentType::query()->active()->ordered()->get();
    }

    public static function documentPreviewUrl(Company $company, string $type, string $context = 'admin'): ?string
    {
        if (! self::getMediaForType($company, $type)) {
            return null;
        }

        if ($context === 'employer') {
            return route('company.verify.documents.preview', ['fileType' => $type]);
        }

        return route('admin.company.documents.preview', ['company' => $company, 'fileType' => $type]);
    }

    public static function notifyAdmins(Company $company): void
    {
        try {
            $admins = Admin::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'superadmin')
                        ->where('guard_name', 'admin');
                })
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new SendProfileVerificationDocumentSubmittedNotification($company));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins about company document upload', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

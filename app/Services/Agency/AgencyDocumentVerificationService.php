<?php

namespace App\Services\Agency;

use App\Models\Agency;

/**
 * Mirrors App\Services\Company\CompanyDocumentVerificationService for the
 * simpler agency verification model: a single "document" media collection
 * plus is_profile_verified / document_verified_at on the agencies table.
 */
class AgencyDocumentVerificationService
{
    public const STATUS_UPLOAD_REQUIRED = 'upload_required';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const MEDIA_COLLECTION = 'document';

    public static function hasUploadedDocument(?Agency $agency): bool
    {
        return (bool) $agency?->hasMedia(self::MEDIA_COLLECTION);
    }

    /**
     * `is_profile_verified` is the single authoritative flag admins already
     * toggle via Admin\AgencyController::toggle()/profileVerificationChange()
     * (which also stamps document_verified_at on approval). We key off this
     * flag alone — not document_verified_at — so agencies verified before the
     * document_verified_at column existed (legacy data) are not retroactively
     * locked out of a dashboard they already had access to.
     */
    public static function isApproved(?Agency $agency): bool
    {
        return (bool) $agency?->is_profile_verified;
    }

    public static function status(?Agency $agency): ?string
    {
        if (! $agency) {
            return self::STATUS_UPLOAD_REQUIRED;
        }

        if (self::isApproved($agency)) {
            return null;
        }

        if (! self::hasUploadedDocument($agency)) {
            return self::STATUS_UPLOAD_REQUIRED;
        }

        return self::STATUS_PENDING_APPROVAL;
    }

    public static function verifyDocumentsRoute(): string
    {
        return route('agency.verify.documents.index');
    }

    public static function redirectIfBlocked(?Agency $agency)
    {
        $status = self::status($agency);

        if ($status === self::STATUS_UPLOAD_REQUIRED) {
            flashWarning(__('agency_documents_upload_required'));

            return redirect()->to(self::verifyDocumentsRoute());
        }

        if ($status === self::STATUS_PENDING_APPROVAL) {
            flashWarning(__('agency_documents_pending_approval'));

            return redirect()->to(self::verifyDocumentsRoute());
        }

        return null;
    }
}

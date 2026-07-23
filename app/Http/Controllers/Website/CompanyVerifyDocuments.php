<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Company\CompanyDocumentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CompanyVerifyDocuments extends Controller
{
    public function index()
    {
        try {
            $company = authUser()?->company?->load('media');

            if (! $company) {
                flashError(__('employer_company_profile_missing'));

                return redirect()->route('company.setting');
            }

            return view('frontend.pages.company.verify-documents', [
                'company' => $company,
                'documentStatus' => CompanyDocumentVerificationService::status($company),
                'documentSummary' => CompanyDocumentVerificationService::documentUploadSummary($company),
                'missingDocuments' => CompanyDocumentVerificationService::missingDocumentTypes($company),
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    public function store(Request $request)
    {
        $company = authUser()?->company;

        if (! $company) {
            flashError(__('employer_company_profile_missing'));

            return redirect()->route('company.setting');
        }

        $company = $company->load('media');

        if (CompanyDocumentVerificationService::isApproved($company)) {
            flashWarning(__('employer_documents_already_approved'));

            return redirect()->back();
        }

        $assignedTypes = CompanyDocumentVerificationService::assignedDocumentTypes($company);
        $rules = [];

        foreach (array_keys($assignedTypes) as $type) {
            $rules[$type] = array_merge(['nullable'], CompanyDocumentVerificationService::acceptedFileRules());
        }

        try {
            $request->validate($rules);
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        $uploaded = false;
        $uploadedNames = [];

        foreach (array_keys($assignedTypes) as $type) {
            if ($request->hasFile($type)) {
                $file = $request->file($type);
                CompanyDocumentVerificationService::storeDocument($company, $type, $file);
                $uploaded = true;
                $uploadedNames[] = ($assignedTypes[$type]['label'] ?? $type).': '.$file->getClientOriginalName();
            }
        }

        if (! $uploaded) {
            flashWarning(__('employer_documents_select_at_least_one'));

            return redirect()->back();
        }

        $company = $company->fresh(['media']);

        try {
            CompanyDocumentVerificationService::markSubmittedForReview($company);
            CompanyDocumentVerificationService::notifyAdmins($company);
        } catch (\Throwable $e) {
            report($e);
        }

        flashSuccess(__('employer_documents_uploaded_files', [
            'files' => implode('; ', $uploadedNames),
        ]));

        return redirect()->route('company.verify.documents.index');
    }

    public function storeSingle(Request $request, string $fileType)
    {
        $company = authUser()?->company;

        if (! $company) {
            flashError(__('employer_company_profile_missing'));

            return redirect()->route('company.setting');
        }

        $company = $company->load('media');

        if (CompanyDocumentVerificationService::isApproved($company)) {
            flashWarning(__('employer_documents_already_approved'));

            return redirect()->back();
        }

        abort_unless(CompanyDocumentVerificationService::isValidDocumentSlugForCompany($company, $fileType), 404);

        $assignedTypes = CompanyDocumentVerificationService::assignedDocumentTypes($company);
        $label = $assignedTypes[$fileType]['label'] ?? $fileType;

        try {
            $request->validate([
                $fileType => array_merge(['required'], CompanyDocumentVerificationService::acceptedFileRules()),
            ]);
        } catch (ValidationException $e) {
            return redirect()
                ->to(route('company.verify.documents.index').'#doc-'.$fileType)
                ->withErrors($e->errors());
        }

        $file = $request->file($fileType);
        CompanyDocumentVerificationService::storeDocument($company, $fileType, $file);

        try {
            CompanyDocumentVerificationService::markSubmittedForReview($company->fresh(['media']));
            CompanyDocumentVerificationService::notifyAdmins($company->fresh(['media']));
        } catch (\Throwable $e) {
            report($e);
        }

        flashSuccess(__('employer_document_saved_single', [
            'label' => $label,
            'file' => $file->getClientOriginalName(),
        ]));

        return redirect()->to(route('company.verify.documents.index').'#doc-'.$fileType);
    }

    public function preview(string $fileType)
    {
        $company = authUser()?->company;

        abort_unless($company, 403);
        abort_unless(CompanyDocumentVerificationService::isValidDocumentSlugForCompany($company, $fileType), 404);

        $media = CompanyDocumentVerificationService::getMediaForType($company, $fileType);
        abort_unless($media && file_exists($media->getPath()), 404);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
        ]);
    }
}

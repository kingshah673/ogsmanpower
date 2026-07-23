<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyCreateFormRequest;
use App\Http\Requests\CompanyUpdateFormRequest;
use App\Models\Company;
use App\Models\CompanyAttribute;
use App\Models\IndustryType;
use App\Models\OrganizationType;
use App\Models\Setting;
use App\Models\TeamSize;
use App\Models\User;
use App\Notifications\SendCompanyDocumentResubmitNotification;
use App\Notifications\SendProfileVerifiedNotification;
use App\Services\Registration\EmployerRegistrationEmailService;
use App\Services\Admin\Company\CompanyCreateService;
use App\Services\Admin\Company\CompanyListService;
use App\Services\Admin\Company\CompanyUpdateService;
use App\Services\Company\CompanyDocumentVerificationService;
use Illuminate\Http\Request;
use Modules\Location\Entities\Country;

class CompanyController extends Controller
{
    public function dynamic_inputs()
    {
        abort_if(
            ! auth()->user()->hasRole('superadmin')
            && ! userCan('company.view')
            && ! userCan('company.update')
            && ! userCan('company.create'),
            403
        );

        $sections = \App\Services\DynamicFieldService::employerSections();
        $attributes = \App\Services\DynamicFieldService::employerDefinitions();
        $groupedSections = \App\Services\DynamicFieldService::groupBySections($attributes, $sections, 'job_post');

        return view('backend.company.dynamic-inputs', compact('attributes', 'sections', 'groupedSections'));
    }
    public function index(Request $request)
    {
        try {
            abort_if(! userCan('company.view'), 403);

            $companies = (new CompanyListService)->execute($request);
            $industry_types = IndustryType::all()->sortBy('name');
            $organization_types = OrganizationType::all()->sortBy('name');
            $employer_corporate_email_required = EmployerRegistrationEmailService::corporateEmailRequired();

            return view('backend.company.index', compact(
                'companies',
                'industry_types',
                'organization_types',
                'employer_corporate_email_required'
            ));
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            abort_if(! userCan('company.create'), 403);

            $data['countries'] = Country::all();
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            $data['organization_types'] = OrganizationType::all()->sortBy('name');
            $data['team_sizes'] = TeamSize::all();

            return view('backend.company.create', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyCreateFormRequest $request)
    {
        try {
            abort_if(! userCan('company.create'), 403);

            (new CompanyCreateService)->execute($request);

            flashSuccess(__('company_created_successfully'));

            return redirect()->route('company.index');
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            abort_if(! userCan('company.view'), 403);

            $company = Company::with([
                'jobs.appliedJobs',
                'user.socialInfo',
                'user.contactInfo',
                'media',
                'verificationDocumentAssignments.documentType',
                'jobs' => function ($job) {
                    return $job->latest()->with('category', 'role', 'job_type', 'salary_type');
                },
            ])->findOrFail($id);

            return view('backend.company.show', compact('company'));
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return redirect()->route('company.index');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            abort_if(! userCan('company.update'), 403);

            $data['company'] = Company::findOrFail($id);
            $data['user'] = $data['company']->user->load('socialInfo');
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            $data['organization_types'] = OrganizationType::all()->sortBy('name');
            $data['team_sizes'] = TeamSize::all();
            $data['socials'] = $data['company']->user->socialInfo;

            return view('backend.company.edit', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyUpdateFormRequest $request, Company $company)
    {
        try {
            abort_if(! userCan('company.update'), 403);

            (new CompanyUpdateService)->execute($request, $company);

            flashSuccess(__('company_updated_successfully'));

            return redirect()->route('company.index');
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            abort_if(! userCan('company.delete'), 403);

            $company = Company::findOrFail($id);

            // company image delete
            deleteFile($company->logo);
            deleteFile($company->banner);
            deleteFile($company->user->image);

            // company cv view items delete
            $company->cv_views()->delete();
            $company->user->delete();
            $company->delete();

            flashSuccess(__('company_deleted_successfully'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    public function documents(Company $company)
    {
        try {
            $company = $company->load(['media', 'verificationDocumentAssignments.documentType']);

            return view('backend.company.document', [
                'company' => $company->load(['media', 'verificationDocumentAssignments.documentType']),
                'allDocumentTypes' => CompanyDocumentVerificationService::allActiveDocumentTypes(),
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    public function previewDocument(Company $company, string $fileType)
    {
        try {
            abort_if(! userCan('company.view'), 403);
            abort_unless(CompanyDocumentVerificationService::isValidDocumentSlugForCompany($company, $fileType), 404);

            $media = CompanyDocumentVerificationService::getMediaForType($company, $fileType);
            abort_unless($media && file_exists($media->getPath()), 404);

            return response()->file($media->getPath(), [
                'Content-Type' => $media->mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
            ]);
        } catch (\Exception $e) {
            abort(404);
        }
    }

    public function downloadDocument(Request $request, Company $company)
    {
        try {
            $request->validate([
                'file_type' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            ]);

            $fileType = $request->get('file_type');
            abort_unless(CompanyDocumentVerificationService::isValidDocumentSlugForCompany($company, $fileType), 404);

            $media = CompanyDocumentVerificationService::getMediaForType($company, $fileType);

            if (! $media) {
                flashError(__('employer_documents_not_uploaded_yet'));

                return back();
            }

            return response()->download($media->getPath(), $media->file_name);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    public function syncDocumentAssignments(Request $request, Company $company)
    {
        try {
            abort_if(! userCan('company.update'), 403);

            $validated = $request->validate([
                'assignments' => ['required', 'array', 'min:1'],
                'assignments.*.document_type_id' => ['required', 'integer', 'exists:employer_verification_document_types,id'],
                'assignments.*.is_required' => ['nullable', 'boolean'],
                'assignments.*.sort_order' => ['nullable', 'integer', 'min:0'],
            ]);

            CompanyDocumentVerificationService::syncCompanyAssignments($company, $validated['assignments']);

            return responseSuccess(__('employer_document_assignments_saved'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle employer registration corporate email requirement.
     */
    public function corporateEmailRequirementChange(Request $request)
    {
        try {
            abort_if(! userCan('company.update'), 403);

            $setting = Setting::first();

            if (! $setting) {
                return response()->json(['message' => __('setting_not_found')], 404);
            }

            $required = (bool) $request->boolean('status');

            $setting->update([
                'employer_corporate_email_required' => $required,
            ]);

            return responseSuccess(
                $required
                    ? __('employer_corporate_email_required_enabled')
                    : __('employer_corporate_email_required_disabled')
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Change company status
     *
     * @return void
     */
    public function statusChange(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);

            $user->update(['status' => $request->status]);

            if ($request->status == 1) {
                return responseSuccess(__('company_activated_successfully'));
            } else {
                return responseSuccess(__('company_deactivated_successfully'));
            }
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Change company verification status
     *
     * @return void
     */
    public function verificationChange(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);

            if ($request->status) {
                $user->update(['email_verified_at' => now()]);
                $message = __('email_verified_successfully');
            } else {
                $user->update(['email_verified_at' => null]);
                $message = __('email_unverified_successfully');
            }

            return responseSuccess($message);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Change company profile verification status
     *
     * @return void
     */
    public function profileVerificationChange(Request $request)
    {
        try {
            $company = Company::findOrFail($request->id);

            if ($request->status) {

                $company->document_verified_at = now();
                $company->update(['is_profile_verified' => true]);
                $company->user->notify(new SendProfileVerifiedNotification);
                $message = __('profile_verified_successfully');
            } else {

                $company->document_verified_at = null;
                $company->update(['is_profile_verified' => false]);
                $message = __('profile_unverified_successfully');
            }

            return responseSuccess($message);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Change company document verification status
     *
     * @param  Request  $request
     * @return void
     */
    public function toggle(Company $company)
    {
        try {
            if (! CompanyDocumentVerificationService::hasAllRequiredDocuments($company)) {
                return response()->json([
                    'success' => false,
                    'message' => __('employer_documents_missing_cannot_approve'),
                ], 422);
            }

            if ($company->document_verified_at) {
                $company->update([
                    'is_profile_verified' => false,
                    'document_verified_at' => null,
                    'documents_resubmit_required' => false,
                    'document_review_note' => null,
                ]);
                $message = __('unverified').' '.__('successfully');
            } else {
                $company->update([
                    'document_verified_at' => now(),
                    'is_profile_verified' => true,
                    'documents_resubmit_required' => false,
                    'document_review_note' => null,
                ]);
                $company->user?->notify(new SendProfileVerifiedNotification);
                $message = __('verified').' '.__('successfully');
            }

            return responseSuccess($message);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    public function requestDocumentResubmit(Request $request, Company $company)
    {
        try {
            abort_if(! userCan('company.update'), 403);

            $request->validate([
                'document_review_note' => ['required', 'string', 'max:2000'],
            ]);

            $company->update([
                'documents_resubmit_required' => true,
                'document_review_note' => $request->document_review_note,
                'document_verified_at' => null,
                'is_profile_verified' => false,
            ]);

            if ($company->user) {
                $company->user->notify(new SendCompanyDocumentResubmitNotification($company));
            }

            return responseSuccess(__('employer_documents_resubmit_requested'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AffiliateSettingsController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\BenefitController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\CandidateLanguageController;
use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompanyInputController;
use App\Http\Controllers\Admin\EmployerVerificationDocumentTypeController;
use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AgencyInputController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\IndustryTypeController;
use App\Http\Controllers\Admin\JobCategoryController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\JobRoleController;
use App\Http\Controllers\Admin\JobTypeController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrganizationTypeController;
use App\Http\Controllers\Admin\DynamicInputController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProfessionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SalaryTypeController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\SocialiteController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TeamSizeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\BrokerController;
use App\Http\Controllers\Admin\BrokerDemandController;
use App\Http\Controllers\Admin\RoleOtpMethodController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\SearchCountryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\Website\WebsiteSettingController;
use App\Http\Controllers\Admin\ContractTemplateController;
use App\Http\Controllers\Admin\ChatLeadController;
use App\Http\Controllers\AboutPageController;
use App\Http\Controllers\Admin\AdminAboutController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\JobAiTemplateController;
use App\Http\Controllers\Admin\VisaProcessingFlowController;
use App\Http\Controllers\Admin\VisaProcessingCaseController;
use App\Http\Controllers\Admin\NominatedWorkerController as AdminNominatedWorkerController;

use App\Http\Controllers\Admin\AIKnowledgeBaseController;
use App\Http\Controllers\Admin\AISettingsController;
use App\Http\Controllers\Admin\AIKnowledgeController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CopilotController;
use App\Http\Controllers\Admin\AIChatController;
use App\Http\Controllers\Admin\WhatsAppChatController;
use App\Http\Controllers\Admin\AIHandoverController;
use App\Http\Controllers\Admin\AINotificationController;
use App\Http\Controllers\Admin\FooterPanelController;
use App\Http\Controllers\Admin\FooterItemController;

Route::prefix('admin')->middleware('portal_user_admin_guard')->group(function () {
    /**
     * Auth routes
     */
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.admin')->middleware('prevent_cache');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login')->middleware('prevent_cache');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');
    Route::get('view_cv/{candidate}', [CandidateController::class, 'view_cv'])->name('admin.view_cv');
    


    Route::middleware(['guest:admin'])->group(function () {
        Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('admin.password.email');
        Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('admin.password.request');
        Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('admin.password.update');
        Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('admin.password.reset');
    });

    Route::middleware(['auth:admin', 'otp.verified', 'profile.approved'])->group(function () {
        //Dashboard Route
        Route::get('/', [AdminController::class, 'dashboard'])->middleware('prevent_cache');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard')->middleware('prevent_cache');

        // Notification Route
        Route::post('/notifications/read', [AdminController::class, 'notificationRead'])->name('admin.notification.read');
        Route::get('/notifications', [AdminController::class, 'allNotifications'])->name('admin.all.notification');
        Route::get('/contract/edit', [AdminController::class, 'edit'])->name('contracts.edit');
        Route::put('/contract/update/{contract}', [AdminController::class, 'update'])->name('contracts.update');

        // Roles Route
        Route::resource('role', RolesController::class);

        // Role OTP Methods Routes
        Route::controller(RoleOtpMethodController::class)
            ->prefix('roles')
            ->name('roles.')
            ->group(function () {
                Route::get('/otp-methods', 'index')->name('otp-methods.index');
                Route::post('/otp-settings', 'saveOtpSettings')->name('otp-settings.save');
                Route::get('/{role}/otp-methods/edit', 'edit')->name('otp-methods.edit');
                Route::put('/{role}/otp-methods', 'update')->name('otp-methods.update');
            });

        //Users Route
        Route::resource('user', UserController::class)->only(['dashboard', 'index', 'create', 'store', 'edit', 'update', 'destroy']);

        Route::get('/company/{company}/documents', [CompanyController::class, 'documents'])->name('admin.company.documents');
        Route::get('/company/{company}/documents/{fileType}/preview', [CompanyController::class, 'previewDocument'])->name('admin.company.documents.preview');
        Route::get('/company/{company}/documents/change', [CompanyController::class, 'toggle'])->name('admin.document.verify.change');
        Route::post('/company/{company}/documents/request-resubmit', [CompanyController::class, 'requestDocumentResubmit'])->name('admin.company.documents.request_resubmit');
        Route::post('/company/{company}/documents/assignments', [CompanyController::class, 'syncDocumentAssignments'])->name('admin.company.documents.assignments');

        Route::post('/company/{company}/documents', [CompanyController::class, 'downloadDocument'])->name('company.verify.documents.download');
        
        Route::get('/agency/{agency}/documents', [AgencyController::class, 'documents'])->name('admin.agency.documents');
        Route::get('/agency/{agency}/documents/change', [AgencyController::class, 'toggle'])->name('admin.agency.document.verify.change');

        Route::post('/agency/{agency}/documents', [AgencyController::class, 'downloadDocument'])->name('agency.verify.documents.download');
        // Company dynamic inputs — MUST be registered before Route::resource('company')
        // or /admin/company/dynamic-inputs is resolved as company show with id "dynamic-inputs".
        Route::get('company/dynamic-inputs', [CompanyController::class, 'dynamic_inputs'])->name('admin.company.dynamic_inputs');
        Route::get('company/verification-document-types', [EmployerVerificationDocumentTypeController::class, 'index'])->name('admin.company.verification_document_types.index');
        Route::post('company/verification-document-types', [EmployerVerificationDocumentTypeController::class, 'store'])->name('admin.company.verification_document_types.store');
        Route::put('company/verification-document-types/{documentType}', [EmployerVerificationDocumentTypeController::class, 'update'])->name('admin.company.verification_document_types.update');
        Route::delete('company/verification-document-types/{documentType}', [EmployerVerificationDocumentTypeController::class, 'destroy'])->name('admin.company.verification_document_types.destroy');
        Route::post('company/verification-document-types/{documentType}/toggle-active', [EmployerVerificationDocumentTypeController::class, 'toggleActive'])->name('admin.company.verification_document_types.toggle_active');
        Route::post('company/add_dynamic_input', [CompanyInputController::class, 'store'])->name('company.add_dynamic_input');
        Route::delete('company/delete_dynamic_input/{id}', [CompanyInputController::class, 'destroy'])->name('company.delete_dynamic_input');
        Route::post('company/toggle_active', [CompanyInputController::class, 'toggleActive'])->name('company.toggle_active');
        Route::post('company/toggle_required', [CompanyInputController::class, 'toggleRequired'])->name('company.toggle_required');

        Route::resource('company', CompanyController::class);
        Route::get('/company/change/status', [CompanyController::class, 'statusChange'])->name('company.status.change');
        Route::get('/company/registration/corporate-email', [CompanyController::class, 'corporateEmailRequirementChange'])->name('company.registration.corporate_email.change');
        Route::get('/company/verify/status', [CompanyController::class, 'verificationChange'])->name('company.verify.change');
        Route::get('/company/profile/verify/status', [CompanyController::class, 'profileVerificationChange'])->name('company.profile.verify.change');
        
        //Agency Route resource

        Route::resource('agency', AgencyController::class);
        Route::get('/agency/change/status', [AgencyController::class, 'statusChange'])->name('agency.status.change');
        Route::get('/agency/verify/status', [AgencyController::class, 'verificationChange'])->name('agency.verify.change');
        Route::get('/agency/profile/verify/status', [AgencyController::class, 'profileVerificationChange'])->name('agency.profile.verify.change');

        Route::get('dynamic_inputs', [AgencyController::class, 'dynamic_inputs'])->name('admin.agency.dynamic_inputs');
        Route::post('/agency/add_dynamic_input', [AgencyInputController::class, 'store'])->name('agency.add_dynamic_input');
        Route::delete('/agency/delete_dynamic_input/{id}', [AgencyInputController::class, 'destroy'])->name('agency.delete_dynamic_input');
        Route::post('/agency/toggle_active', [AgencyInputController::class, 'toggleActive'])->name('agency.toggle_active');
        Route::post('/agency/toggle_required', [AgencyInputController::class, 'toggleRequired'])->name('agency.toggle_required');

        // Agency commissions
        Route::get('/commissions', [\App\Http\Controllers\Admin\CommissionController::class, 'index'])->name('commissions.index');
        Route::patch('/commissions/{commission}/status', [\App\Http\Controllers\Admin\CommissionController::class, 'updateStatus'])->name('commissions.update-status');

        // Broker / Middleman
        Route::get('broker/demands', [BrokerDemandController::class, 'index'])->name('broker.demands.index');
        Route::get('broker/demands/{id}', [BrokerDemandController::class, 'show'])->name('broker.demands.show');
        Route::put('broker/demands/{id}', [BrokerDemandController::class, 'update'])->name('broker.demands.update');
        Route::resource('broker', BrokerController::class);
        Route::get('/broker/change/status', [BrokerController::class, 'statusChange'])->name('broker.status.change');
        Route::get('/broker/verify/status', [BrokerController::class, 'verificationChange'])->name('broker.verify.change');
        Route::get('/broker/profile/verify/status', [BrokerController::class, 'profileVerificationChange'])->name('broker.profile.verify.change');

        // Agent / Facilitator status toggles
        Route::get('/agent/change/status', [AgentController::class, 'statusChange'])->name('agent.status.change');
        Route::get('/agent/verify/status', [AgentController::class, 'verificationChange'])->name('agent.verify.change');

        // Seeker dynamic field definitions (global) — before candidate resource
        Route::get('candidate/dynamic-inputs', [CandidateController::class, 'seekerDynamicInputs'])->name('admin.candidate.dynamic_inputs');
        Route::get('candidate/{candidate}/dynamic-inputs', [CandidateController::class, 'dynamic_inputs'])->name('admin.candidate.candidate_dynamic_inputs');
        Route::get('dynamic_input/{id}', [CandidateController::class, 'dynamic_input'])->name('admin.candidate.dyanmic_inputs');
        Route::post('/candidate/add_dynamic_input', [DynamicInputController::class, 'store'])->name('candidate.add_dynamic_input');
        Route::delete('/candidate/delete_dynamic_input/{id}', [DynamicInputController::class, 'destroy'])->name('candidate.delete_dynamic_input');
        Route::post('/candidate/toggle_active', [DynamicInputController::class, 'toggleActive'])->name('candidate.toggle_active');
        Route::post('/candidate/toggle_required', [DynamicInputController::class, 'toggleRequired'])->name('candidate.toggle_required');

        // Candidate Route
        Route::resource('candidate', CandidateController::class);
        Route::get('/candidate/change/status', [CandidateController::class, 'statusChange'])->name('candidate.status.change');

        Route::get('/candidate/change/is_featured', [CandidateController::class, 'is_candidate_featured'])->name('candidate.is_featured.change');

        Route::get('/candidate/export/{type}', [CandidateController::class, 'candidateExport'])->name('candidate.export');
        Route::post('viewResume/{candidate}', [CandidateController::class, 'viewResume'])->name('admin.viewResume');

        Route::get('lang/home', [CandidateController::class, 'index']);
        Route::get('lang/change', [CandidateController::class, 'change_lang'])->name('changeLang');
        Route::get('candidate_status', [CandidateController::class, 'candidate_status'])->name('candidate-status');
        Route::post('/assign-agent/{candidate}', [CandidateController::class, 'assignAgent'])->name('assign.agent');
        Route::get('assign_candidate_status', [CandidateController::class, 'assignCandidateDetails'])->name('assign-candidate-status');
        Route::post('addCandidateDetails', [CandidateController::class, 'addCandidateDetails'])->name('addCandidateDetails');
        Route::post('/candidates/{id}/approve', [CandidateController::class, 'approveCandidate'])->name('candidates.approve');
        Route::post('/candidates/{id}/disapprove', [CandidateController::class, 'disapproveCandidate'])->name('candidates.disapprove');
        Route::delete('/candidates/{id}', [CandidateController::class, 'deleteCandidate'])->name('candidates.delete');
        Route::get('whatsapp_candidate', [CandidateController::class, 'whatsappCandidate'])->name('whatsapp.candidate');
        Route::post('/send-messages', [CandidateController::class, 'sendMessages'])->name('send.messages');
        Route::get('/edit/plan', [CandidateController::class, 'editPlan'])->name('edit.plan');
        Route::post('/store-or-update-plan', [CandidateController::class, 'storeOrUpdatePlan'])->name('storeOrUpdatePlan');
        Route::put('/approve-resume-subscriptions/{id}/approve', [CandidateController::class, 'approveResumeSubscription'])->name('approve.resume.subscription');
        Route::delete('/delete-resume-subscriptions/{id}', [CandidateController::class, 'deleteResumeSubscription'])->name('delete.resume.subscription');
        Route::get('/resume-subscription/{id}', [CandidateController::class, 'resumeSubscription'])->name('candidate.resume.subscription');

        // Passport OCR Review
        Route::get('/passport-ocr', [\App\Http\Controllers\Admin\PassportOcrController::class, 'index'])->name('admin.passport-ocr.index');
        Route::post('/passport-ocr/{log}/confirm', [\App\Http\Controllers\Admin\PassportOcrController::class, 'confirm'])->name('admin.passport-ocr.confirm');
        Route::get('/passport-ocr/{log}/reject', [\App\Http\Controllers\Admin\PassportOcrController::class, 'reject'])->name('admin.passport-ocr.reject');

        // Route::post('viewResume/$candidate->id', 'viewResume')->name('viewResume');

        //JobCategory Route resource
        Route::resource('jobCategory', JobCategoryController::class)->except('show');
        Route::post('/job/category/bulk/import', [JobCategoryController::class, 'bulkImport'])->name('admin.job.category.bulk.import');

        //job Route resource
        Route::resource('job', JobController::class);
        Route::get('/jobs/delete-selected', [JobController::class, 'deleteSelected'])->name('jobs.deleteSelected');
        Route::get('applied/jobs', [JobController::class, 'appliedJobs'])->name('applied.jobs');

        // Visa Processing (new product)
        Route::get('visa-flows', [VisaProcessingFlowController::class, 'index'])->name('admin.visa-flows.index');
        Route::get('visa-flows/create', [VisaProcessingFlowController::class, 'create'])->name('admin.visa-flows.create');
        Route::post('visa-flows', [VisaProcessingFlowController::class, 'store'])->name('admin.visa-flows.store');
        Route::get('visa-flows/{visa_flow}/edit', [VisaProcessingFlowController::class, 'edit'])->name('admin.visa-flows.edit');
        Route::put('visa-flows/{visa_flow}', [VisaProcessingFlowController::class, 'update'])->name('admin.visa-flows.update');
        Route::post('visa-flows/{visa_flow}/publish', [VisaProcessingFlowController::class, 'publish'])->name('admin.visa-flows.publish');
        Route::post('visa-flows/{visa_flow}/draft', [VisaProcessingFlowController::class, 'markDraft'])->name('admin.visa-flows.draft');
        Route::post('visa-flows/{visa_flow}/steps', [VisaProcessingFlowController::class, 'storeStep'])->name('admin.visa-flows.steps.store');
        Route::put('visa-steps/{step}', [VisaProcessingFlowController::class, 'updateStep'])->name('admin.visa-steps.update');
        Route::post('visa-steps/{step}/move', [VisaProcessingFlowController::class, 'moveStep'])->name('admin.visa-steps.move');
        Route::post('visa-steps/{step}/deactivate', [VisaProcessingFlowController::class, 'deactivateStep'])->name('admin.visa-steps.deactivate');
        Route::delete('visa-steps/{step}', [VisaProcessingFlowController::class, 'destroyStep'])->name('admin.visa-steps.destroy');
        Route::post('visa-steps/{step}/requirements', [VisaProcessingFlowController::class, 'storeRequirement'])->name('admin.visa-steps.requirements.store');
        Route::put('visa-requirements/{requirement}', [VisaProcessingFlowController::class, 'updateRequirement'])->name('admin.visa-requirements.update');
        Route::post('visa-requirements/{requirement}/deactivate', [VisaProcessingFlowController::class, 'deactivateRequirement'])->name('admin.visa-requirements.deactivate');
        Route::delete('visa-requirements/{requirement}', [VisaProcessingFlowController::class, 'destroyRequirement'])->name('admin.visa-requirements.destroy');

        Route::get('visa-cases', [VisaProcessingCaseController::class, 'index'])->name('admin.visa-cases.index');
        Route::get('visa-cases/{vp_case}', [VisaProcessingCaseController::class, 'show'])->name('admin.visa-cases.show');
        Route::post('visa-cases/{vp_case}/cancel', [VisaProcessingCaseController::class, 'cancel'])->name('admin.visa-cases.cancel');
        Route::get('visa-cases/{vp_case}/file/{fileId}', [VisaProcessingCaseController::class, 'downloadFile'])->name('admin.visa-cases.file');

        Route::get('nominated-batches', [AdminNominatedWorkerController::class, 'indexBatches'])->name('admin.nominated-batches.index');
        Route::get('nominated-batches/{batch}', [AdminNominatedWorkerController::class, 'showBatch'])->name('admin.nominated-batches.show');
        Route::post('nominated-batches/{batch}/approve', [AdminNominatedWorkerController::class, 'approveBatch'])->name('admin.nominated-batches.approve');
        Route::post('nominated-batches/{batch}/return', [AdminNominatedWorkerController::class, 'returnBatch'])->name('admin.nominated-batches.return');
        Route::get('nominated-workers', [AdminNominatedWorkerController::class, 'index'])->name('admin.nominated-workers.index');
        Route::get('nominated-workers/{worker}', [AdminNominatedWorkerController::class, 'show'])->name('admin.nominated-workers.show');
        Route::post('nominated-workers/documents/{document}/rematch', [AdminNominatedWorkerController::class, 'rematch'])->name('admin.nominated-workers.rematch');
        Route::post('nominated-workers/documents/{document}/confirm', [AdminNominatedWorkerController::class, 'confirmMatch'])->name('admin.nominated-workers.confirm');
        Route::get('applied/jobs/{applied_job}', [JobController::class, 'appliedJobsShow'])->name('applied.job.show');
        Route::post('/job/bulk/import', [JobController::class, 'bulkImport'])->name('admin.job.bulk.import');
        Route::put('job/change/status/{job}', [JobController::class, 'jobStatusChange'])->name('admin.job.status.change');
        Route::get('job/clone/{job:slug}', [JobController::class, 'clone'])->name('admin.job.clone');
        Route::get('edited/job/list', [JobController::class, 'editedJobList'])->name('admin.job.edited.index');
        Route::get('edited/job/show/{job:slug}', [JobController::class, 'editedShow'])->name('admin.job.edited.show');
        Route::put('edited/job/approved/{job:slug}', [JobController::class, 'editedApproved'])->name('admin.job.edited.approved');
        Route::get('/admin/hire-requests', [JobController::class, 'hiring_requests'])->name('admin.hire.requests');
        Route::post('/send-mail/{id}', [JobController::class, 'sendHireMail'])->name('send.hire.mail');
        Route::post('/job/{id}/assign-roles', [JobController::class, 'assignRoles'])->name('job.assign-roles');
        Route::get('applyJob', [JobController::class, 'applyJob'])->name('job.applyJob');
        Route::post('save-job', [JobController::class, 'saveJob'])->name('save.job');
        Route::get('my-job', [JobController::class, 'myJob'])->name('my.job');
        Route::get('/job/{job}/featured',[JobController::class, 'makeFeatured'])->name('job.featured');
        Route::get('/job/{job}/highlight',[JobController::class, 'makeHighlight'])->name('job.highlight');
        Route::post('/jobs/store',[JobController::class, 'store'])->middleware('feature:active_job_posting_limit');





        // job role route resource
        Route::resource('jobRole', JobRoleController::class)->except('show', 'create');
        Route::post('/job/role/bulk/import', [JobRoleController::class, 'bulkImport'])->name('admin.job.role.bulk.import');
        Route::post('/job/role/bulk/import', [JobRoleController::class, 'bulkImport'])->name('admin.job.role.bulk.import');


        // industry type route resource
        Route::resource('industryType', IndustryTypeController::class)->except('show', 'create');
        Route::post('/industry/type/bulk/import', [IndustryTypeController::class, 'bulkImport'])->name('admin.industry.type.bulk.import');
        Route::post('/industry/toggle-visibility', [IndustryTypeController::class, 'toggleVisibility'])->name('industry.toggleVisibility');


        // Organization Type route resource
        Route::resource('organizationType', OrganizationTypeController::class)->except('show', 'create');
        Route::post('/organization/type/bulk/import', [OrganizationTypeController::class, 'bulkImport'])->name('admin.organization.type.bulk.import');

        // Salary Type  route resource
        Route::resource('salaryType', SalaryTypeController::class)->except('show', 'create');
        Route::post('/salary/type/bulk/import', [SalaryTypeController::class, 'bulkImport'])->name('admin.salary.type.bulk.import');

        // profession route resource
        Route::resource('profession', ProfessionController::class)->except('show', 'create');
        Route::post('/profession/bulk/import', [ProfessionController::class, 'bulkImport'])->name('admin.profession.bulk.import');

        // skills route resource
        Route::resource('skill', SkillController::class)->except('show', 'create');
        Route::post('/skill/bulk/import', [SkillController::class, 'bulkImport'])->name('admin.skill.bulk.import');

        // benefit route resource
        Route::resource('benefit', BenefitController::class)->except('show', 'create');

        //  education route resource
        Route::resource('education', EducationController::class)->except('show', 'create');

        //  experience route resource
        Route::resource('experience', ExperienceController::class)->except('show', 'create');

        //  team size route resource
        Route::resource('teamSize', TeamSizeController::class)->except('show', 'create');

        //  job type route resource
        Route::resource('jobType', JobTypeController::class)->except('show', 'create');
        //  job type route resource
        
       

Route::middleware(['auth'])->group(function () {
    Route::get('/ai-dashboard',[\App\Http\Controllers\Admin\AIDashboardController::class,'index'])->name('admin.ai.dashboard');
    Route::get('/live-chat',[\App\Http\Controllers\Admin\LiveChatController::class,'index'] );
    Route::get('/live-chat/{id}',[\App\Http\Controllers\Admin\LiveChatController::class,'show']);
    Route::post('/live-chat/{id}/reply',[\App\Http\Controllers\Admin\LiveChatController::class,'reply']);
    Route::get('/ai-recruiter-dashboard', [\App\Http\Controllers\Admin\AIRecruiterDashboardController::class,'index'])->name('admin.ai.recruiter.dashboard');
    Route::get('/ai-copilot',[CopilotController::class,'index'])->name('admin.ai.copilot');
    Route::post('/ai-copilot/search',[CopilotController::class,'search'])->name('admin.ai.copilot.search');
     Route::get('/analytics', [AnalyticsController::class,'index'])->name('admin.analytics');
    Route::get('/ai-settings',[AISettingsController::class,'index'])->name('admin.ai.settings');
    Route::post('/ai-settings/update',[AISettingsController::class,'update'])->name('admin.ai.settings.update');
    Route::get('/ai-knowledge',[AIKnowledgeController::class,'index'])->name('admin.ai.knowledge.index');
Route::get('/ai-knowledge/create',[AIKnowledgeController::class,'create'])->name('admin.ai.knowledge.create');
Route::post('/ai-knowledge/store',[AIKnowledgeController::class,'store'])->name('admin.ai.knowledge.store');
Route::get('/ai-knowledge/{id}/show', [AIKnowledgeController::class,'show'])->name('admin.ai.knowledge.show');
Route::get('/ai-knowledge/{id}/edit',[AIKnowledgeController::class,'edit'])->name('admin.ai.knowledge.edit');
Route::post('/ai-knowledge/{id}/update',[AIKnowledgeController::class,'update'])->name('admin.ai.knowledge.update');
Route::delete('/ai-knowledge/{id}/delete',[AIKnowledgeController::class,'destroy'])->name('admin.ai.knowledge.destroy');

Route::get('/ai-chat',[AIChatController::class,'index'])->name('admin.ai.chat.index');
Route::get('/ai-chat/{session}',[AIChatController::class,'show'])->name('admin.ai.chat.show');
Route::get('/ai-handover',[AIHandoverController::class,'index'])->name('admin.ai.handover.index');
Route::post('/ai-handover/{id}/reply',[AIHandoverController::class,'reply'])->name('admin.ai.handover.reply');
Route::get('/ai-notifications',[AINotificationController::class,'index'])->name('admin.ai.notifications.index');
Route::post('/ai-chat/{session}/reply',[AIChatController::class,'reply'])->name('admin.ai.chat.reply');

Route::get('/whatsapp-chat',[WhatsAppChatController::class,'index'])->name('admin.whatsapp.chat');
Route::get('/whatsapp-chat/{phone}',[WhatsAppChatController::class,'show'])->name('admin.whatsapp.chat.show');
Route::post('/whatsapp-chat/{phone}/reply',[WhatsAppChatController::class,'reply'])->name('admin.whatsapp.chat.reply');
Route::post('/whatsapp-chat/{phone}/human-mode',[WhatsAppChatController::class,'human.mode'])->name('admin.whatsapp.chat.human.mode');
Route::get('/admin/whatsapp-chat/{phone}/messages',[WhatsAppChatController::class,'messages'])->name('admin.whatsapp.chat.messages');


});


Route::middleware(['auth'])->group(function () {

        Route::get('/chat-leads', [ChatLeadController::class, 'index'])->name('admin.chatleads.index');
        Route::post('/chat-leads/{id}/update',[ChatLeadController::class, 'update'])->name('admin.chatleads.update');
        Route::get('/chat-leads-export',[ChatLeadController::class, 'export'])->name('admin.chatleads.export');
        Route::get('/chat-leads',[ChatLeadController::class, 'index'])->name('admin.chatleads.index');
        Route::delete('/chat-leads/{id}',[ChatLeadController::class, 'destroy'])->name('admin.chatleads.destroy');
        
});
    
    Route::middleware(['auth'])->group(function () {
    Route::get('/job-ai', [JobAiTemplateController::class, 'index'])->name('admin.job_ai.index');
    Route::get('/job-ai/create', [JobAiTemplateController::class, 'create'])->name('admin.job_ai.create');
    Route::post('/job-ai/store', [JobAiTemplateController::class, 'store'])->name('admin.job_ai.store');
    Route::get('/job-ai/edit/{id}', [JobAiTemplateController::class, 'edit'])->name('admin.job_ai.edit');
    Route::post('/job-ai/update/{id}', [JobAiTemplateController::class, 'update'])->name('admin.job_ai.update');
    Route::delete('/job-ai/delete/{id}', [JobAiTemplateController::class, 'destroy'])->name('admin.job_ai.delete');


});
    

        // tags route resource
        Route::resource('tags', TagController::class);
        Route::post('tags/status/change/{tag}', [TagController::class, 'statusChange'])->name('tags.status.change');
        Route::post('/tags/bulk/import', [TagController::class, 'bulkImport'])->name('admin.tags.bulk.import');

        // menu settings
        Route::post('menu-settings/status-update/{menuSetting}', [MenuController::class, 'statusChange'])->name('menu-setting.status.change');
        Route::resource('settings/menu-settings', MenuController::class);
        Route::post('settings/menu-settings/sort', [MenuController::class, 'sortAble'])->name('menu-setting.sort-able');

        // About Page
        Route::prefix('about')->name('admin.about.')->middleware(['web', 'auth:admin'])->group(function () {
        Route::get('/', [AdminAboutController::class, 'dashboard'])->name('dashboard');
        Route::post('/hero',[AdminAboutController::class, 'updateHero'])->name('hero.update');
        Route::post('/story',[AdminAboutController::class, 'updateStory'])->name('story.update');
        Route::post('/features',[AdminAboutController::class, 'storeFeature'])->name('features.store');
        Route::put('/features/{id}',[AdminAboutController::class, 'updateFeature'])->name('features.update');
        Route::delete('/features/{id}',[AdminAboutController::class, 'deleteFeature'])->name('features.destroy');
        Route::post('/metrics',[AdminAboutController::class, 'updateMetrics'])->name('metrics.update');
        Route::put('/industries/{id}',[AdminAboutController::class, 'updateIndustry'])->name('industries.update');
        Route::post('/ceo',[AdminAboutController::class, 'updateCeo'])->name('ceo.update');
        Route::post('/videos', [AdminAboutController::class, 'storeVideo'])->name('videos.store');
        Route::delete('/videos/{id}',[AdminAboutController::class, 'deleteVideo'])->name('videos.destroy');
        Route::post('/social',[AdminAboutController::class, 'updateSocial'])->name('social.update');
        Route::post('/config',[AdminAboutController::class, 'updateConfig'])->name('config.update');
        Route::post('/cache/clear',[AdminAboutController::class, 'clearCacheManual'])->name('cache.clear');
});

        //Dashboard Route
        Route::controller(AdminController::class)->group(function () {
            Route::get('/', 'dashboard');
            Route::get('/dashboard', 'dashboard')->name('admin.dashboard');
            Route::post('/admin/search', 'search')->name('admin.search');
            Route::post('/admin/download/transaction/invoice/{transaction}', 'downloadTransactionInvoice')->name('admin.transaction.invoice.download');
            Route::post('/view/transaction/invoice/{transaction}', 'viewTransactionInvoice')->name('admin.transaction.invoice.view');
        });

        //Profile Route

        // Order Route
        Route::controller(OrderController::class)->group(function () {
            Route::get('/orders', 'index')->name('order.index');
            Route::get('/order/create', 'create')->name('order.create');
            Route::post('/order/store', 'store')->name('order.store');
            Route::get('/orders/{id}', 'show')->name('order.show');

            Route::get('/order/user/plan/{earning}', 'updateUserPlan')->name('order.user.plan.update');
            Route::put('/user/plan/update/{user}', 'UserPlanUpdate')->name('user.plan.update');
        });

        // ========================================================
        // ====================Setting=============================
        // ========================================================

        // Website Setting Route
        Route::put('settings/terms/conditions/update', [CmsController::class, 'termsConditionsUpdate'])->name('admin.privacy.terms.update');
        Route::controller(CmsController::class)
            ->prefix('settings')
            ->name('settings.')
            ->group(function () {
                Route::put('home/{cms}', 'home')->name('home.update');
                Route::put('aboutupdate', 'aboutupdate')->name('aboutupdate');
                Route::get('about-logo/delete/{name}', 'aboutLogoDelete')->name('aboutLogo.delete');
                Route::put('auth/{cms}', 'auth')->name('auth.update');
                Route::put('errorpage/{cms}', 'updateErrorPages')->name('errorpage.update');
                Route::put('comingsoon/{cms}', 'comingsoon')->name('comingsoon.update');
                Route::put('maintenance/mode', 'maintenanceModeUpdate')->name('maintenance.mode.update');
                Route::put('paymentupdate', 'paymentupdate')->name('paymentupdate');
                Route::get('payment-logo/delete/{name}', 'paymentLogoDelete')->name('paymentLogo.delete');
                Route::put('others', 'othersupdate')->name('others.update');
            });
        Route::controller(WebsiteSettingController::class)
            ->prefix('settings')
            ->name('settings.')
            ->group(function () {
                Route::get('/websitesetting', 'website_setting')->name('websitesetting');
                Route::post('/session/terms-privacy', 'sessionUpdateTermsPrivacy')->name('session.update.tems-privacy');
                Route::delete('/cms/content', 'cmsContentDestroy')->name('cms.content.destroy');
            });

        // Admin Setting Route
        Route::controller(SettingsController::class)
            ->prefix('settings')
            ->name('settings.')
            ->group(function () {
                Route::get('general', 'general')->name('general');
                Route::put('general', 'generalUpdate')->name('general.update');
                Route::put('preference', 'preferenceUpdate')->name('preference.update');
                Route::get('layout', 'layout')->name('layout');
                Route::put('layout', 'layoutUpdate')->name('layout.update');
                Route::put('mode', 'modeUpdate')->name('mode.update');
                Route::get('theme', 'theme')->name('theme');
                Route::put('theme', 'colorUpdate')->name('theme.update');
                Route::get('custom', 'custom')->name('custom');
                Route::put('custom', 'custumCSSJSUpdate')->name('custom.update');
                Route::get('email', 'email')->name('email');
                Route::put('email', 'emailUpdate')->name('email.update');
                Route::get('tiwilio', 'tiwilio')->name('tiwilio');
                Route::post('tiwilio', 'tiwilioUpdate')->name('tiwilio.update');
                Route::post('test-email', 'testEmailSent')->name('email.test');

                // system update
                Route::get('system', 'system')->name('system');
                Route::put('system/update', 'systemUpdate')->name('system.update');
                Route::put('system/mode/update', 'systemModeUpdate')->name('system.mode.update');
                Route::put('system/jobdeadline/update', 'systemJobdeadlineUpdate')->name('system.jobdeadline.update');

                // system update end
                Route::put('search/indexing', 'searchIndexing')->name('search.indexing');
                Route::put('google-analytics', 'googleAnalytics')->name('google.analytics');
                Route::put('allowLangChanging', 'allowLaguageChanage')->name('allow.langChange');
                Route::put('change/timezone', 'timezone')->name('change.timezone');

                // cookies routes
                Route::get('cookies', 'cookies')->name('cookies');
                Route::put('cookies/update', 'cookiesUpdate')->name('cookies.update');

                // seo
                Route::get('seo/index', 'seoIndex')->name('seo.index');
                Route::get('seo/edit/{page}', 'seoEdit')->name('seo.edit');
                Route::put('seo/update/{content}', 'seoUpdate')->name('seo.update');
                Route::get('generate/sitemap', 'generateSitemap')->name('generateSitemap');

                // database backup end
                Route::put('working-process/update', 'workingProcessUpdate')->name('working.process.update');

                // pwa option Update
                Route::put('pwa/update', 'pwaUpdate')->name('pwa.update');

                // recaptcha Update
                Route::put('recaptcha/update', 'recaptchaUpdate')->name('recaptcha.update');

                // pusher Update
                Route::put('pusher/update', 'pusherUpdate')->name('pusher.update');

                // analytics Update
                Route::put('analytics/update', 'analyticsUpdate')->name('analytics.update');

                // payperjob Update
                Route::put('payperjob/update', 'payperjobUpdate')->name('payperjob.update');

                // upgrade application
                Route::get('upgrade', 'upgrade')->name('upgrade');
                Route::post('upgrade/apply', 'upgradeApply')->name('upgrade.apply');

                // systemInfo
                Route::get('/system/info', 'systemInfo')->name('systemInfo');

                // landing page
                Route::put('landing-page', 'landingPageUpdate')->name('landingPage.update');

                Route::get('/system/ad_setting', 'ad_setting')->name('ad_setting');
                Route::put('/update_ad_info', 'update_ad_info')->name('adinfo.update');
                Route::put('/update_ad_status', 'update_ad_status')->name('adstatus.update');
            });

        // Affiliate Settings Route
        Route::controller(AffiliateSettingsController::class)
            ->prefix('settings/affiliate')
            ->name('settings.')
            ->group(function () {
                Route::get('/', 'index')->name('affiliate.index');
                Route::put('careerjet/update', 'careerjetUpdate')->name('careerjet.update');
                Route::put('indeed/update', 'indeedUpdate')->name('indeed.update');
                Route::post('set/default/affiliate', 'setDefaultJob')->name('affiliate.default');
            });

        // Email Template Route
        Route::group(['prefix' => 'settings/email-templates', 'as' => 'settings.email-templates.'], function () {
            Route::get('/', [EmailTemplateController::class, 'index'])->name('list');
            Route::post('/save', [EmailTemplateController::class, 'save'])->name('save');
        });

        Route::controller(PageController::class)->prefix('settings/pages')->name('settings.')->group(function () {
            Route::get('/', 'index')->name('pages.index');
            Route::get('/create', 'create')->name('pages.create');
            Route::post('/create', 'store')->name('pages.store');
            Route::get('/edit/{page}', 'edit')->name('pages.edit');
            Route::put('/update/{page}', 'update')->name('pages.update');
            Route::delete('/delete/{page}', 'delete')->name('pages.delete');
            Route::get('/status/showinheader', 'changeShowInheader')->name('pages.header.status');
            Route::get('/status/showinfooter', 'changeShowInFooter')->name('pages.footer.status');
        });
        // Socialite Route
        Route::controller(SocialiteController::class)->group(function () {
            Route::get('settings/social-login', 'index')->name('settings.social.login');
            Route::put('settings/social-login', 'update')->name('settings.social.login.update');
            Route::post('settings/social-login/status', 'updateStatus')->name('settings.social.login.status.update');
        });

        // Payment Route
        Route::controller(PaymentController::class)
            ->prefix('settings/payment')
            ->name('settings.')
            ->group(function () {
                // Automatic Payment
                Route::get('/auto', 'autoPayment')->name('payment');
                Route::put('/', 'update')->name('payment.update');

                // Manual Payment
                Route::get('/manual', 'manualPayment')->name('payment.manual');
                Route::post('/manual/store', 'manualPaymentStore')->name('payment.manual.store');
                Route::get('/manual/{manual_payment}/edit', 'manualPaymentEdit')->name('payment.manual.edit');
                Route::put('/manual/{manual_payment}/update', 'manualPaymentUpdate')->name('payment.manual.update');
                Route::delete('/manual/{manual_payment}/delete', 'manualPaymentDelete')->name('payment.manual.delete');
                Route::get('/manual/status/change', 'manualPaymentStatus')->name('payment.manual.status');
            });

        // candidate language
        Route::resource('candidate/language/index', CandidateLanguageController::class, ['names' => 'admin.candidate.language']);
        Route::controller(SearchCountryController::class)->prefix('settings/location/country')->name('location.country.')->group(function () {
            Route::get('/', 'index')->name('country');
            Route::get('/add', 'create')->name('create');
            Route::post('/add', 'store')->name('store');
            Route::get('/edit/{id}', 'edit')->name('edit');
            Route::put('/edit/{id}', 'update')->name('update');
            Route::delete('/delete/{id}', 'destroy')->name('destroy');
        });
        Route::controller(StateController::class)->prefix('settings/location/state')->name('location.state.')->group(function () {
            Route::get('/', 'index')->name('state');
            Route::get('/add', 'create')->name('create');
            Route::post('/add', 'store')->name('store');
            Route::get('/edit/{id}', 'edit')->name('edit');
            Route::put('/edit/{id}', 'update')->name('update');
            Route::delete('/delete/{id}', 'destroy')->name('destroy');
        });
        Route::controller(CityController::class)->prefix('settings/location/city')->name('location.city.')->group(function () {
            Route::get('/', 'index')->name('city');
            Route::get('/add', 'create')->name('create');
            Route::post('/add', 'store')->name('store');
            Route::get('/edit/{id}', 'edit')->name('edit');
            Route::put('/edit/{id}', 'update')->name('update');
            Route::delete('/delete/{id}', 'destroy')->name('destroy');
        });
    });
    Route::get('agent/{id}/candidates', 
    [App\Http\Controllers\Admin\AgentController::class, 'candidates']
)->name('agent.candidates');

    Route::name('admin.')->group(function () {

    Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('agents/{id}', [AgentController::class, 'show'])->name('agents.show');
    Route::get('agents/{id}/candidates', [AgentController::class, 'candidates'])->name('agents.candidates');
    Route::post('agents/{id}/status', [AgentController::class, 'status'])->name('agents.status');
    
    Route::get('/hr-templates', [ContractTemplateController::class, 'index'])->name('hrtemplates.index');
    Route::get('/hr-templates/create', [ContractTemplateController::class, 'create'])->name('hrtemplates.create');
    Route::post('/hr-templates/store', [ContractTemplateController::class, 'store'])->name('hrtemplates.store');
    Route::get('/hr-templates/edit/{id}', [ContractTemplateController::class, 'edit'])->name('hrtemplates.edit');
    Route::post('/hr-templates/update/{id}', [ContractTemplateController::class, 'update'])->name('hrtemplates.update');
    Route::delete('/hr-templates/delete/{id}', [ContractTemplateController::class, 'destroy'])->name('hrtemplates.delete');
    // Footer CMS (must use auth:admin — parent name group already prefixes admin.)
    Route::middleware(['auth:admin', 'otp.verified'])->prefix('footer')->name('footer.')->group(function () {
        Route::get('/', [FooterPanelController::class, 'index'])->name('index');
        Route::put('/settings', [FooterPanelController::class, 'updateSettings'])->name('settings.update');
        Route::post('/panels', [FooterPanelController::class, 'store'])->name('panels.store');
        Route::put('/panels/{panel}', [FooterPanelController::class, 'update'])->name('panels.update');
        Route::delete('/panels/{panel}', [FooterPanelController::class, 'destroy'])->name('panels.destroy');
        Route::post('/panels/reorder', [FooterPanelController::class, 'reorder'])->name('panels.reorder');
        Route::post('/items', [FooterItemController::class, 'store'])->name('items.store');
        Route::put('/items/{item}', [FooterItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [FooterItemController::class, 'destroy'])->name('items.destroy');
        Route::post('/items/reorder', [FooterItemController::class, 'reorder'])->name('items.reorder');
    });

            });
    Route::middleware(['auth:admin', 'otp.verified'])->group(function () {
        Route::controller(ProfileController::class)->group(function () {
            Route::get('/profile/settings', 'setting')->name('profile.setting');
            Route::get('/profile', 'profile')->name('profile');
            Route::put('/profile', 'profile_update')->name('profile.update');
            Route::resource('agent', AgentController::class);
            Route::get('contract.form', [AgentController::class, 'contractForm'])->name('contract.form');
            Route::get('download-agreement', [AgentController::class, 'downloadAgreement'])->name('download.agreement');
            Route::post('save-agreement', [AgentController::class, 'saveAgreement'])->name('save.agreement');
            Route::post('approve-contract/{id}', [AgentController::class, 'approvedContract'])->name('approved.contract');
        });
    });
});

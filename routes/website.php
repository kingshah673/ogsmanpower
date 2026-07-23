<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Controllers
use App\Http\Controllers\Website\WebsiteController;
use App\Http\Controllers\Website\CompanyController;
use App\Http\Controllers\Website\AgencyController as WebsiteAgencyController;
use App\Http\Controllers\Admin\AgencyController as AdminAgencyController;
use App\Http\Controllers\Website\LocationLookupController;
use App\Http\Controllers\Website\CandidateController;
use App\Http\Controllers\Website\OTPController;
use App\Http\Controllers\Website\CompanyVerifyDocuments;
use App\Http\Controllers\Website\AgencyVerifyDocuments;
use App\Http\Controllers\Website\GlobalController;
use App\Http\Controllers\Website\MessengerController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Payment\PayPalController;
use App\Http\Controllers\Payment\StripeController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\BrokerController;
use App\Http\Controllers\Api\CompanyController as ApiCompanyController;
use App\Http\Controllers\Api\AgencyController as ApiAgencyController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\JobController;          // FIXED casing (was lowercase \admin\)
use App\Http\Controllers\ContractController;
use App\Http\Controllers\Website\ChatbotController;
use App\Http\Controllers\Website\CompanyVisaProcessingController;
use App\Http\Controllers\Website\CandidateVisaProcessingController;
use App\Http\Controllers\Website\CompanyNominatedWorkerController;
use App\Http\Controllers\Website\AgencyVisaProcessingController;
use App\Http\Controllers\Website\AgencyProtectorController;
use App\Http\Controllers\Website\AgencyReportController;
use App\Http\Controllers\Website\AgencyAIController;
use App\Http\Controllers\Website\AgencyNominatedWorkerController;

use App\Http\Controllers\AIController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\AI\AIWhatsAppController;
use App\Http\Controllers\Website\VisaCaseController;          // ADDED (was missing)
use App\Models\State;
use App\Models\City;
use App\Http\Controllers\AI\WebChatController;

/*
|--------------------------------------------------------------------------
| Chatbot
|--------------------------------------------------------------------------
*/
Route::prefix('chatbot')->group(function () {
    Route::post('/send', [ChatbotController::class, 'send'])->name('chatbot.send');
    Route::get('/history', [ChatbotController::class, 'history'])->name('chatbot.history');
    Route::post('/upload', [ChatbotController::class, 'upload'])->name('chatbot.upload');
    Route::post('/handover', [ChatbotController::class, 'handover'])->name('chatbot.handover');
});

Route::get('/ai/context',[WebChatController::class,'context']);

Route::post('/ai/chat',[WebChatController::class,'chat']);

Route::get('/refresh-csrf', function () {
    return response()->json(['token' => csrf_token()]);
})->name('refresh.csrf');

Route::post('/ai/transcribe',[WebChatController::class,'transcribe']);

Route::get('/ai/chat/replies/{session}',[WebChatController::class,'replies']);

Route::get('/ai/chat/human-mode/{session}',[WebChatController::class,'humanMode']);
Route::match(['GET','POST'],'/webhooks/whatsapp',[WhatsAppWebhookController::class,'handle']);




/*
|--------------------------------------------------------------------------
| WhatsApp Webhook
|--------------------------------------------------------------------------
*/
//Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify']);
//Route::post('/webhook/whatsapp', [WhatsAppController::class, 'receive']);


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('prevent_cache')->group(function () {
    if (! app()->runningInConsole()) {
        Auth::routes(['verify' => setting('email_verification')]);
    } else {
        Auth::routes(['verify' => false]);
    }
});

// Email verification
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    if (authUser()->role == 'company') return redirect()->route('company.dashboard', ['verified' => true]);
    if (authUser()->role == 'agency')  return redirect()->route('agency.dashboard', ['verified' => true]);
    return redirect()->route('candidate.dashboard', ['verified' => true]);
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/email/verify/update/{id}/{newEmail}', function (EmailVerificationUpdateRequest $request, $id, $newEmail) {
    if (! $request->hasValidSignature()) abort(401);
    $request->fulfill($newEmail);
    if (authUser()->role == 'company') return redirect()->route('company.dashboard', ['verified' => true]);
    if (authUser()->role == 'agency')  return redirect()->route('agency.dashboard', ['verified' => true]);
    return redirect()->route('candidate.dashboard', ['verified' => true]);
})->middleware(['auth', 'signed'])->name('email.verification.update.verify');

// Social
Route::controller(SocialLoginController::class)->group(function () {
    Route::post('/auth/social/register', 'register')->name('social.register');
    Route::get('/auth/{provider}/redirect', 'redirect')->name('social.login');
    Route::get('/auth/{provider}/callback', 'callback');
});

/*
|--------------------------------------------------------------------------
| Public / Guest
|--------------------------------------------------------------------------
*/
// Original OGS About page (not CareerWorkforce AboutPageController)
Route::get('/about', [WebsiteController::class, 'about'])->name('website.about');

// Legacy alias for company/agency application "Download CV" links
Route::get('/downloadCv/{resume}', [WebsiteController::class, 'candidateDownloadCv'])
    ->middleware('auth')
    ->name('downloadCv');

Route::controller(WebsiteController::class)->name('website.')->group(function () {
    Route::get('/', 'index')->name('home')->middleware('prevent_cache');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/plans', 'pricing')->name('plan');
    Route::get('/plans/{label}', 'planDetails')->name('plan.details');
    Route::post('/candidate-plans', 'CandidateplanDetails')->name('candidate.plan.details');
    Route::get('/faq', 'faq')->name('faq');
    Route::get('/terms-condition', 'termsCondition')->name('termsCondition');
    Route::get('/privacy-policy', 'privacyPolicy')->name('privacyPolicy');
    Route::get('/refund-policy', 'refundPolicy')->name('refundPolicy');
    Route::get('/coming-soon', 'comingSoon')->name('comingsoon');
    Route::get('/careerjet/jobs', 'careerjetJobs')->name('careerjet.job');
    Route::get('/indeed/jobs', 'indeedJobs')->name('indeed.job');
    Route::get('/jobs', 'jobs')->name('job');
    Route::get('/loadmore', 'loadmore');
    Route::get('/jobs/category/{category}', 'jobsCategory')->name('job.category.slug');
    Route::get('job/autocomplete', 'jobAutocomplete')->name('job.autocomplete');
    Route::get('/job/{job:slug}', 'jobDetails')->name('job.details');
    Route::get('/job/hr/{job:slug}', 'jobDetailsHr')->name('job.details.hr');
    Route::get('/jobs/{job:slug}/bookmark', 'toggleBookmarkJob')->name('job.bookmark')->middleware('user_active');
    Route::post('/jobs/apply', 'toggleApplyJob')->name('job.apply')->middleware('user_active');
    Route::get('/candidates', 'candidates')->name('candidate');
    Route::get('/candidates/{candidate:username}', 'candidateDetails')->name('candidate.details');
    Route::get('/candidate/profile/details', 'candidateProfileDetails')->name('candidate.profile.details');
    Route::get('/candidate/application/profile/details', 'candidateApplicationProfileDetails')->name('candidate.application.profile.details');
    Route::get('/candidates/download/cv/{resume}', 'candidateDownloadCv')->name('candidate.download.cv');
    Route::get('/employers', 'employees')->name('company');
    Route::get('/employer/{user:username}', 'employersDetails')->name('employe.details');
    Route::get('/posts', 'posts')->name('posts');
    Route::get('/post/{post:slug}', 'post')->name('post');
    Route::post('/comment/{post:slug}/add', 'comment')->name('comment');
    Route::post('/markasread/single/notification', 'markReadSingleNotification')->name('markread.notification');
    Route::post('/set/session', 'setSession')->name('set.session');
    Route::get('/selected/country', 'setSelectedCountry')->name('set.country');
    Route::get('/selected/country/remove', 'removeSelectedCountry')->name('remove.country');
    Route::post('/job/benefits/create', 'jobBenefitCreate')->name('job.benefit.create');
    // REMOVED: Route::get('success-transaction', 'successTransaction')->name('paypal.successTransaction');
    //          Method doesn't exist on WebsiteController; the real one is in the PayPal group below.
    Route::get('filterJobSeeker', 'filterJobSeeker')->name('filterJobSeeker');
    Route::get('filterJobs', 'filterJobs')->name('filterJobs');
    Route::get('/download-cv/{id}', 'downloadcv')->name('download.cv');
    Route::get('/candidates-by-country/{country}', 'candidateByCountry')->name('candidates.by.country');
    Route::get('/jobs-by-country/{country}', 'jobsByCountry')->name('jobs.by.country');
    Route::get('/candidates-by-industry/{industry}', 'candidateByIndustry')->name('candidates.by.industry');
    Route::get('/jobs-by-industry/{industry}', 'jobsByIndustry')->name('jobs.by.industry');
    Route::get('/download-applicant-cv/{id}', 'downloadApplicantCv')->name('download.applicant.cv');
});

Route::get('/stripe/success', [StripeController::class, 'success'])->name('stripe.success');
Route::get('/stripe/cancel', [StripeController::class, 'cancel'])->name('stripe.cancel');

/*
|--------------------------------------------------------------------------
| Visa Cases (legacy → new visa-processing)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/visa-cases', function () {
        $role = authUser()?->role;
        if ($role === 'company') {
            return redirect()->route('company.visa-processing.index');
        }
        if ($role === 'agency') {
            return redirect()->route('agency.visa-processing.index');
        }
        if ($role === 'candidate') {
            return redirect()->route('candidate.visa-processing.index');
        }

        return redirect()->route('website.home');
    })->name('visa.index');

    Route::get('/visa-dashboard', function () {
        return redirect()->route('visa.index');
    })->name('visa.dashboard');

    Route::get('/visa-create', function () {
        return redirect()->route('visa.index');
    })->name('visa.create');

    Route::get('/visa-cases/{id}', function ($id) {
        $role = authUser()?->role;
        if ($role === 'company') {
            return redirect()->route('company.visa-processing.show', $id);
        }
        if ($role === 'agency') {
            return redirect()->route('agency.visa-processing.show', $id);
        }
        if ($role === 'candidate') {
            return redirect()->route('candidate.visa-processing.index');
        }

        return redirect()->route('website.home');
    })->name('visa.show');

    // Soft-disable remaining legacy write endpoints
    Route::post('/visa-cases', fn () => redirect()->route('visa.index')->with('error', 'Please use Visa Processing.'))->name('visa.store');
    Route::post('/visa-cases/{id}/move-stage', fn () => redirect()->route('visa.index'))->name('visa.move.stage');
    Route::post('/upload-document', fn () => redirect()->route('visa.index'));
    Route::post('/verify-document/{id}', fn () => redirect()->route('visa.index'));
    Route::get('/visa-dashboard-stats', fn () => response()->json(['legacy' => true, 'redirect' => route('visa.index')]));
    Route::get('/visa-case-modal/{id}', fn () => redirect()->route('visa.index'))->name('visa.modal');
    Route::post('/complete-task/{id}', function ($id) {
        \App\Services\TaskService::complete($id);

        return back();
    });
});

/*
|--------------------------------------------------------------------------
| AI
|--------------------------------------------------------------------------
*/
Route::post('/ai/parse-cv', [AIController::class, 'parseCV'])->name('ai.parse.cv');
Route::post('/ai/parse-job-posting', [AIController::class, 'parseJobPosting'])->name('ai.parse.job');
Route::post('/ai/parse-passport', [AIController::class, 'parsePassport'])->name('ai.parse.passport');
Route::post('/ai/ats-score', [AIController::class, 'calculateATS'])->name('ai.ats.score');
Route::post('/ai/save-cv-data', [AIController::class, 'saveCVData'])->name('ai.save.cv')->middleware('auth');
Route::post('/ai/generate-bio', [AIController::class, 'generateBio'])->name('ai.generate.bio')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated User
|--------------------------------------------------------------------------
*/
Route::middleware('auth:user', 'verified', 'otp.verified')->group(function () {

    Route::get('/user/dashboard', [WebsiteController::class, 'dashboard'])
        ->name('user.dashboard')->middleware('prevent_cache');
    Route::post('/user/notification/read', [WebsiteController::class, 'notificationRead'])
        ->name('user.notification.read');

    // Candidate
    Route::controller(CandidateController::class)
        ->prefix('candidate')->middleware('candidate')->name('candidate.')->group(function () {

        Route::get('dashboard', 'dashboard')->name('dashboard')->middleware('prevent_cache');
        Route::get('applied-jobs', 'appliedjobs')->name('appliedjob');
        Route::get('bookmarks', 'bookmarks')->name('bookmark');
        Route::get('settings', 'setting')->name('setting');
        Route::get('settings/lookup/{type}', 'settingLookup')->name('setting.lookup');
        Route::get('additionl-settings', 'additionlSetting')->name('additionlSetting');
        Route::post('additionl-settings/save-values', 'saveAttributeValues')->name('saveAttributeValues');
        Route::post('dashboard/quick-update', 'dashboardUpdate')->name('dashboardUpdate');
        Route::get('candidate-cv', 'candidateCV')->name('view.cv');
        Route::get('candidate-document', 'candidateDocument')->name('document');
        Route::put('settings/update', 'settingUpdate')->name('settingUpdate');
        Route::post('settings/profile-photo', 'updateProfilePhoto')->name('profilePhoto');
        Route::post('settings/attachment-image', 'updateAttachmentImage')->name('attachmentImage');
        Route::post('settings/attachment-image/delete', 'deleteAttachmentImage')->name('attachmentImage.delete');
        Route::delete('attachment/{id}', [CandidateController::class, 'deleteAttachment'])->name('attachment.delete');
        Route::get('/all/notifications', 'allNotification')->name('allNotification');
        Route::get('/job/alerts', 'jobAlerts')->name('job.alerts');
        Route::post('/resume/store', 'resumeStore')->name('resume.store');
        Route::post('/resume/store/ajax', 'resumeStoreAjax')->name('resume.store.ajax');
        Route::post('/get/resume/ajax', 'getResumeAjax')->name('get.resume.ajax');
        Route::post('/resume/update', 'resumeUpdate')->name('resume.update');
        Route::delete('/resume/delete/{resume}', 'resumeDelete')->name('resume.delete');
        Route::post('/experiences/store', 'experienceStore')->name('experiences.store');
        Route::put('/experiences/update', 'experienceUpdate')->name('experiences.update');
        Route::delete('/experiences/{experience}', 'experienceDelete')->name('experiences.destroy');
        Route::post('/educations/store', 'educationStore')->name('educations.store');
        Route::put('/educations/update', 'educationUpdate')->name('educations.update');
        Route::delete('/educations/{education}', 'educationDelete')->name('educations.destroy');
        Route::post('/cv/show', 'cvShow')->name('cv.show');                   // kept once
        Route::post('viewResume', 'viewResume')->name('viewResume');
        // REMOVED duplicate: Route::post('/cv/show', 'cvShow')->name('cv.show');
        Route::get('plan', 'plan')->name('plan');
        Route::get('edit/plan', 'editPlan')->name('edit.plan');
        Route::get('/get-states', [CandidateController::class, 'getStates'])->name('getStates');
        Route::get('/get-cities', [CandidateController::class, 'getCities'])->name('getCities');
        Route::post('/contract/{id}/accept',[CandidateController::class, 'acceptContract'])->name('contract.accept');
        Route::post('/contract/{id}/reject',[CandidateController::class, 'rejectContract'])->name('contract.reject');
        Route::get('profile-view', 'profileView')->name('profile.view');
        Route::get('/get-states-by-name', 'getStatesByName')->name('getStatesByName');
        Route::get('/get-cities-by-name', 'getCitiesByName')->name('getCitiesByName');

        Route::get('visa-processing', [CandidateVisaProcessingController::class, 'index'])->name('visa-processing.index');
        Route::post('visa-processing/{vp_case}/submit', [CandidateVisaProcessingController::class, 'submitStep'])->name('visa-processing.submit');
    });

    Route::post('job/bulk-assign', [JobController::class, 'bulkAssign'])->name('job.bulk.assign');

    // Company
    Route::controller(CompanyController::class)
        ->prefix('company')->middleware(['company'])->name('company.')->group(function () {

        Route::get('verify-documents', [CompanyVerifyDocuments::class, 'index'])->name('verify.documents.index');
        Route::post('verify-documents', [CompanyVerifyDocuments::class, 'store'])->name('verify.documents.store');
        Route::post('verify-documents/{fileType}', [CompanyVerifyDocuments::class, 'storeSingle'])->name('verify.documents.store.single');
        Route::get('verify-documents/{fileType}/preview', [CompanyVerifyDocuments::class, 'preview'])->name('verify.documents.preview');

        // Profile settings must stay reachable without verified docs (else verify↔settings redirect loop when company row is missing)
        Route::get('settings', 'setting')->name('setting');
        Route::put('settings/update', 'settingUpdateInformation')->name('settingUpdateInformation');

        Route::middleware('company.documents')->group(function () {

        Route::middleware('company.profile', 'has_plan')->group(function () {
            Route::get('dashboard', 'dashboard')->name('dashboard')->middleware('prevent_cache');
            Route::get('mobile_dashboard', 'mobile_dashboard')->name('mobile_dashboard')->middleware('prevent_cache');
            Route::get('plans', 'plan')->name('plan')->middleware('user_active');
            Route::post('download/transaction/invoice/{transaction}', 'downloadTransactionInvoice')->name('transaction.invoice.download');
            Route::get('view/transaction/invoice/{transaction:order_id}', 'viewTransactionInvoice')->name('transaction.invoice.view');
            Route::get('my-jobs', 'myjobs')->name('myjob')->withoutMiddleware('has_plan');
            Route::get('pending-edited-jobs', 'pendingEditedJobs')->name('pending.edited.jobs');
            Route::get('create/pay-per-job', 'payPerJob')->name('job.payPerJobCreate')->withoutMiddleware('has_plan');
            Route::post('/store/payper/job', 'storePayPerJob')->name('payperjob.store')->withoutMiddleware('has_plan');
            Route::get('create/job', 'createJob')->name('job.create')->middleware('user_active');
            Route::get('job-form/lookup/{type}', 'jobFormLookup')->name('job.lookup');
            Route::post('/store/job', 'storeJob')->name('job.store');
            Route::post('/store/parsed-jobs', 'storeParsedJobs')->name('job.store.parsed');
            Route::get('/job/payment', 'payPerJobPayment')->name('payperjob.payment')->withoutMiddleware('has_plan');
            Route::get('/promote/job/{job:slug}', 'showPromoteJob')->name('job.promote.show');
            Route::get('/promote/{job:slug}', 'jobPromote')->name('promote');
            Route::get('/clone/{job:slug}', 'jobClone')->name('clone');
            Route::post('/promote/job/{jobCreated}', 'promoteJob')->name('job.promote');
            Route::get('edit/{job:slug}/job', 'editJob')->name('job.edit')->withoutMiddleware('has_plan');
            Route::post('make/job/expire/{job}', 'makeJobExpire')->name('job.make.expire');
            Route::post('make/job/active/{job}', 'makeJobActive')->name('job.make.active');
            Route::delete('job/{job}', 'destroyJob')->name('job.destroy');
            Route::post('jobs/delete-selected', 'destroyJobs')->name('jobs.destroy.selected');
            Route::put('/update/{job:slug}/job', 'updateJob')->name('job.update')->withoutMiddleware('has_plan');
            Route::get('job/applications', 'jobApplications')->name('job.application');
            Route::get('applicants', 'applicants')->name('applicants');
            Route::get('interviews', 'interviews')->name('interviews');
            Route::post('/update-interview', 'updateInterview')->name('update.interview');

            Route::get('visa-processing', [CompanyVisaProcessingController::class, 'index'])->name('visa-processing.index');
            Route::get('visa-processing/{vp_case}', [CompanyVisaProcessingController::class, 'show'])->name('visa-processing.show');
            Route::post('visa-processing/start', [CompanyVisaProcessingController::class, 'start'])->name('visa-processing.start');
            Route::post('visa-processing/{vp_case}/submit', [CompanyVisaProcessingController::class, 'submitStep'])->name('visa-processing.submit');
            Route::post('visa-processing/{vp_case}/verify', [CompanyVisaProcessingController::class, 'verify'])->name('visa-processing.verify');
            Route::post('visa-processing/{vp_case}/send-back', [CompanyVisaProcessingController::class, 'sendBack'])->name('visa-processing.send-back');
            Route::post('visa-processing/{vp_case}/restart', [CompanyVisaProcessingController::class, 'restart'])->name('visa-processing.restart');
            Route::post('visa-processing/{vp_case}/mark-deployed', [CompanyVisaProcessingController::class, 'markDeployed'])->name('visa-processing.mark-deployed');
            Route::get('visa-processing/{vp_case}/file/{fileId}', [CompanyVisaProcessingController::class, 'downloadFile'])->name('visa-processing.file');
            Route::get('visa-processing/{vp_case}/file/{fileId}/view', [CompanyVisaProcessingController::class, 'viewFile'])->name('visa-processing.file.view');
            Route::post('visa-processing/{vp_case}/requirements/{requirement}/review', [CompanyVisaProcessingController::class, 'reviewRequirement'])->name('visa-processing.requirements.review');

            Route::get('nominated-workers', [CompanyNominatedWorkerController::class, 'index'])->name('nominated-workers.index');
            Route::get('nominated-workers/batches/create', [CompanyNominatedWorkerController::class, 'createBatch'])->name('nominated-workers.batches.create');
            Route::post('nominated-workers/batches', [CompanyNominatedWorkerController::class, 'storeBatch'])->name('nominated-workers.batches.store');
            Route::get('nominated-workers/batches/{batch}', [CompanyNominatedWorkerController::class, 'showBatch'])->name('nominated-workers.batches.show');
            Route::post('nominated-workers/batches/{batch}/submit', [CompanyNominatedWorkerController::class, 'submitBatch'])->name('nominated-workers.batches.submit');
            Route::post('nominated-workers', [CompanyNominatedWorkerController::class, 'store'])->name('nominated-workers.store');
            Route::post('nominated-workers/import', [CompanyNominatedWorkerController::class, 'import'])->name('nominated-workers.import');
            Route::post('nominated-workers/documents', [CompanyNominatedWorkerController::class, 'uploadDocuments'])->name('nominated-workers.documents');
            Route::post('nominated-workers/documents/{document}/rematch', [CompanyNominatedWorkerController::class, 'rematch'])->name('nominated-workers.rematch');
            Route::get('nominated-workers/{worker}', [CompanyNominatedWorkerController::class, 'show'])->name('nominated-workers.show');
            Route::post('nominated-workers/{worker}/visa-step', [CompanyNominatedWorkerController::class, 'submitVisaStep'])->name('nominated-workers.visa-step');

            Route::put('applications/sync', 'applicationsSync')->name('application.sync');
            Route::post('applications/column/store', 'applicationColumnStore')->name('applications.column.store');
            Route::delete('applications/group/delete/{group}', 'applicationColumnDelete')->name('applications.column.delete');
            Route::delete('applications/column/destroy/{group}', 'applicationColumnDelete')->name('applications.column.destroy');
            Route::put('applications/group/update', 'applicationColumnUpdate')->name('applications.column.update');
            Route::delete('delete/{job:id}/application', 'destroyApplication')->name('application.delete');
            Route::get('bookmarks', 'bookmarks')->name('bookmark');
            // settings live outside company.documents middleware
            Route::get('/all/notifications', 'allNotification')->name('allNotification');
            Route::post('applications/group/store', 'applicationsGroupStore')->name('applications.group.store');
            Route::put('applications/group/update/{group}', 'applicationsGroupUpdate')->name('applications.group.update');
            Route::delete('applications/group/destroy/{group}', 'applicationsGroupDestroy')->name('applications.group.destroy');
            Route::post('/questions', 'storeQuestion')->name('questions.store');
            Route::get('/questions', 'manageQuestion')->name('questions.manage');
            Route::post('/questions/featureToggle', 'featureToggle')->name('questions.featureToggle');
            Route::delete('/questions/{question}', 'deleteQuestion')->name('questions.delete');
            Route::get('candidate_status', 'candidate_status')->name('candidate-status');
            Route::get('approvedCandidateStatus', 'approvedCandidateStatus')->name('approvedCandidateStatus');
            Route::post('/update-application-status', 'updateApplicationStatus')->name('update.application.status');
            Route::get('/download-applicant-resume/{candidate_id}/{job_id}', 'downloadApplicantResume')->name('download.applicant.resume');
            Route::get('/applicant-cv/{candidate_id}/{job_id}', 'applicantCv')->name('applicant.cv');
            Route::post('/view-applicant-resume', 'viewApplicantResume')->name('view.applicant.resume');
            Route::get('/application-detail/{candidate_id}/{job_id}', 'applicationDetail')->name('application.detail');
            Route::post('/forward-candidate-email', 'forwardCandidateEmail')->name('forward.candidate.email');
            Route::post('/hire-request', 'hire_request')->name('hire-request');
            Route::get('/job/{job}/assign-agency', [CompanyController::class, 'assignAgency'])->name('job.assign.agency');
            Route::post('/job/{job}/assign-agency', [CompanyController::class, 'storeAssignAgency']) ->name('job.assign.agency.store');

            Route::post('/pipeline/shortlist', [CompanyController::class, 'shortlistCandidate'])->name('pipeline.shortlist');
            Route::post('/pipeline/interview',[CompanyController::class, 'interviewCandidate'])->name('pipeline.interview');
            Route::post('/pipeline/reject',[CompanyController::class, 'rejectCandidate'])->name('pipeline.reject');
            Route::post('/contract/store',[CompanyController::class, 'storeContract'])->name('contract.store');
            Route::post('/pipeline/status/{id}', [CompanyController::class, 'updatePipelineStatus'])->name('pipeline.status');
        });

        Route::post('/company/bookmark/{candidate}', 'companyBookmarkCandidate')->name('companybookmarkcandidate')->middleware('user_active');
        Route::get('account-progress', 'accountProgress')->name('account-progress');
        Route::put('/profile/complete/{id}', 'profileCompleteProgress')->name('profile.complete');
        Route::get('location/states-by-name', [LocationLookupController::class, 'statesByName'])->name('location.statesByName');
        Route::get('location/cities-by-name', [LocationLookupController::class, 'citiesByName'])->name('location.citiesByName');
        Route::get('/bookmark/categories', 'bookmarkCategories')->name('bookmark.category.index');
        Route::post('/bookmark/categories/store', 'bookmarkCategoriesStore')->name('bookmark.category.store');
        Route::get('/bookmark/categories/edit/{category}', 'bookmarkCategoriesEdit')->name('bookmark.category.edit');
        Route::put('/bookmark/categories/update/{category}', 'bookmarkCategoriesUpdate')->name('bookmark.category.update');
        Route::delete('/bookmark/categories/destroy/{category}', 'bookmarkCategoriesDestroy')->name('bookmark.category.destroy');
        Route::post('username/change', 'usernameUpdate')->name('username.change');
        Route::get('/pipeline',[CompanyController::class, 'pipeline'])->name('pipeline');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Company Contracts
|--------------------------------------------------------------------------
*/
Route::prefix('company')->middleware(['auth','company'])->name('company.')->group(function () {
    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('contracts/{id}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('contracts/pdf/{id}', [ContractController::class, 'downloadPdf'])->name('contracts.pdf');
});



/*
|--------------------------------------------------------------------------
| Messenger (consolidated — was duplicated across two blocks)
|--------------------------------------------------------------------------
*/
Route::controller(MessengerController::class)
    ->middleware('auth:user', 'verified', 'otp.verified')->group(function () {

    // Company-specific
    Route::get('/company/messages', 'companyMessages')->name('company.messages')->middleware('company');
    Route::post('/company/message/candidate', 'messageSendCandidate')->name('company.message.candidate');

    // Agency-specific
    Route::get('/agency/messages', 'agencyMessages')->name('agency.messages')->middleware('agency');
    Route::post('/agency/message/candidate', 'messageSendCandidate')->name('agency.message.candidate');

    // Candidate
    Route::get('/candidate/messages', 'candidateMessages')->name('candidate.messages')->middleware('candidate');

    // Shared (defined once)
    Route::get('/get/messages/{username}', 'fetchMessages');
    Route::post('/send/message', 'sendMessage');
    Route::post('message/markas/read/{username}', 'messageMarkasRead')->name('message.markas.read');
    Route::get('/get/users', 'filterUsers');
    Route::get('/sync/user-list', 'syncUserList');
    Route::get('/load-unread-count', 'loadUnreadMessageCount')->name('load.unread.count');
});

/*
|--------------------------------------------------------------------------
| Agent
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'agent'])
    ->controller(AgentController::class)
    ->prefix('agent')->name('agent.')->group(function () {

    Route::get('dashboard', 'dashboard')->name('dashboard');
    Route::get('settings', 'setting')->name('setting');
    Route::put('settings/update', 'settingUpdateInformation')->name('setting.update');
    Route::get('jobs', 'jobs')->name('jobs');
    Route::post('cv-ocr', 'cvOCR')->name('cv.ocr');
    Route::get('account-progress', 'accountprogress')->name('progress');
    Route::get('notifications', 'notifications')->name('notifications');

    Route::get('candidates', 'candidates')->name('candidates.index');
    Route::get('candidates/create', 'createCandidate')->name('candidates.create');
    Route::post('candidates/store', 'storeCandidate')->name('candidates.store');
    Route::get('candidates/edit/{id}', 'editCandidate')->name('candidates.edit');
    Route::post('candidates/update/{id}', 'updateCandidate')->name('candidates.update');
    Route::get('candidates/delete/{id}', 'deleteCandidate')->name('candidates.delete');
    Route::post('apply-candidate', 'applyCandidate')->name('apply.candidate');
    Route::get('applications', 'applications')->name('applications');
    Route::post('applications/status/{id}', 'updateStatus')->name('applications.status');
    Route::post('applications/interview/{id}', 'setInterview')->name('interview');
    Route::post('applications/visa/{id}', 'updateVisa')->name('visa');
    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('contracts/{id}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('contracts/pdf/{id}', [ContractController::class, 'downloadPdf'])->name('contracts.pdf');
    // NOTE: these become /agent/agent/get-states & /agent/agent/get-cities — kept as-is.
    Route::get('get-states', 'getStates')->name('get.states');
    Route::get('get-cities', 'getCities')->name('get.cities');
    Route::get('pipeline', 'pipeline')->name('pipeline');
    Route::post('contract/{id}/accept', 'acceptContract')->name('contract.accept');
    Route::post('contract/{id}/reject', 'rejectContract')->name('contract.reject');
});

/*
|--------------------------------------------------------------------------
| Broker / Middleman (Demand Partner) — manual §9
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'broker'])
    ->controller(BrokerController::class)
    ->prefix('broker')->name('broker.')->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('demands', 'demands')->name('demands');
        Route::get('demands/create', 'createDemand')->name('demands.create');
        Route::post('demands', 'storeDemand')->name('demands.store');
        Route::post('demands/{id}/route', 'routeDemand')->name('demands.route');
        Route::get('settings', 'setting')->name('setting');
        Route::put('settings', 'settingUpdate')->name('setting.update');
    });

/*
|--------------------------------------------------------------------------
| Generic Contracts
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('contracts')->name('contracts.')->group(function () {
    Route::get('/', [ContractController::class, 'index'])->name('index');
    Route::get('/create', [ContractController::class, 'create'])->name('create');
    Route::post('/store', [ContractController::class, 'store'])->name('store');
    Route::get('/pdf/{id}', [ContractController::class, 'downloadPdf'])->name('pdf');
    Route::get('/{id}', [ContractController::class, 'show'])->name('show');
    Route::post('/send/{id}', [ContractController::class, 'send'])->name('send');
    Route::post('/accept/{id}', [ContractController::class, 'accept'])->name('accept');
    Route::post('/send-otp/{id}', [ContractController::class, 'sendOtp'])->name('sendOtp');
    Route::post('/verify-otp/{id}', [ContractController::class, 'verifyOtp'])->name('verifyOtp');
    Route::delete('/{id}', [ContractController::class, 'destroy'])->name('delete');
});

/*
|--------------------------------------------------------------------------
| Agency
|--------------------------------------------------------------------------
*/
Route::controller(WebsiteAgencyController::class)
    ->prefix('agency')
    ->middleware(['auth', 'agency', 'has_plan'])
    ->name('agency.')
    ->group(function () {

        // Profile settings reachable without verified docs
        Route::get('settings', 'setting')->name('setting')->withoutMiddleware('has_plan');
        Route::put('settings/update', 'settingUpdateInformation')->name('settingUpdateInformation')->withoutMiddleware('has_plan');

        Route::middleware('agency.documents')->group(function () {

        Route::middleware('agency.profile', 'has_plan')->group(function () {
            // NOTE: this URL becomes /agency/agency — kept as-is.
            Route::get('/agency', [WebsiteAgencyController::class, 'dashboard'])->name('agency.index');
            Route::get('/available-jobs', 'availableJobs')->name('available.jobs');
            Route::post('apply-candidate', 'applyCandidate')->name('apply.candidate');     // kept once (was duplicated below)
            Route::get('/job/{id}/candidates', 'jobCandidates')->name('job.candidates');
            Route::post('/submit-candidates', 'submitCandidates')->name('submit.candidates');
            Route::get('dashboard', 'dashboard')->name('dashboard')->middleware('prevent_cache');
            Route::get('mobile_dashboard', 'mobile_dashboard')->name('mobile_dashboard')->middleware('prevent_cache');
            Route::get('plans', 'plan')->name('plan')->middleware('user_active');
            Route::post('download/transaction/invoice/{transaction}', 'downloadTransactionInvoice')->name('transaction.invoice.download');
            Route::get('view/transaction/invoice/{transaction:order_id}', 'viewTransactionInvoice')->name('transaction.invoice.view');
            Route::get('my-jobs', 'myjobs')->name('myjob')->withoutMiddleware('has_plan');
            Route::get('pending-edited-jobs', 'pendingEditedJobs')->name('pending.edited.jobs');
            Route::get('create/pay-per-job', 'payPerJob')->name('job.payPerJobCreate')->withoutMiddleware('has_plan');
            Route::post('/store/payper/job', 'storePayPerJob')->name('payperjob.store')->withoutMiddleware('has_plan');
            Route::get('create/job', 'createJob')->name('job.create')->middleware('user_active');
            Route::get('job-form/lookup/{type}', 'jobFormLookup')->name('job.lookup');
            Route::post('/store/job', 'storeJob')->name('job.store');
            Route::get('/job/payment', 'payPerJobPayment')->name('payperjob.payment')->withoutMiddleware('has_plan');
            Route::get('/promote/job/{job:slug}', 'showPromoteJob')->name('job.promote.show');
            Route::get('/promote/{job:slug}', 'jobPromote')->name('promote');
            Route::get('/clone/{job:slug}', 'jobClone')->name('clone');
            Route::post('/promote/job/{jobCreated}', 'promoteJob')->name('job.promote');
            Route::get('edit/{job:slug}/job', 'editJob')->name('job.edit')->withoutMiddleware('has_plan');
            Route::post('make/job/expire/{job}', 'makeJobExpire')->name('job.make.expire');
            Route::post('make/job/active/{job}', 'makeJobActive')->name('job.make.active');
            Route::put('/update/{job:slug}/job', 'updateJob')->name('job.update')->withoutMiddleware('has_plan');
            Route::get('job/applications', 'jobApplications')->name('job.application');
            Route::get('interviews', 'interviews')->name('interviews');
            Route::post('/update-interview', 'updateInterview')->name('update.interview');
            Route::put('applications/sync', 'applicationsSync')->name('application.sync');
            Route::post('applications/column/store', 'applicationColumnStore')->name('applications.column.store');
            Route::delete('applications/group/delete/{group}', 'applicationColumnDelete')->name('applications.column.delete');
            Route::delete('applications/column/destroy/{group}', 'applicationColumnDelete')->name('applications.column.destroy');
            Route::put('applications/group/update', 'applicationColumnUpdate')->name('applications.column.update');
            Route::delete('delete/{job:id}/application', 'destroyApplication')->name('application.delete');
            Route::get('bookmarks', 'bookmarks')->name('bookmark');
            // agency settings live outside agency.documents middleware
            Route::get('/all/notifications', 'allNotification')->name('allNotification');
            Route::post('applications/group/store', 'applicationsGroupStore')->name('applications.group.store');
            Route::put('applications/group/update/{group}', 'applicationsGroupUpdate')->name('applications.group.update');
            Route::delete('applications/group/destroy/{group}', 'applicationsGroupDestroy')->name('applications.group.destroy');
            Route::post('/questions', 'storeQuestion')->name('questions.store');
            Route::get('/questions', 'manageQuestion')->name('questions.manage');
            Route::post('/questions/featureToggle', 'featureToggle')->name('questions.featureToggle');
            Route::delete('/questions/{question}', 'deleteQuestion')->name('questions.delete');
            Route::get('candidate_status', 'candidate_status')->name('candidate-status');
            Route::get('approvedCandidateStatus', 'approvedCandidateStatus')->name('approvedCandidateStatus');
            Route::post('/update-application-status', 'updateApplicationStatus')->name('update.application.status');
            Route::get('/download-applicant-resume/{candidate_id}/{job_id}', 'downloadApplicantResume')->name('download.applicant.resume');
            Route::get('/application-detail/{candidate_id}/{job_id}', 'applicationDetail')->name('application.detail');
            Route::post('/forward-candidate-email', 'forwardCandidateEmail')->name('forward.candidate.email');
            Route::post('/hire-request', 'hire_request')->name('hire-request');
            Route::post('/applications/bulk-status', 'bulkUpdateApplicationStatus')->name('applications.bulk.status');
            Route::post('/job/{job}/respond', [WebsiteAgencyController::class, 'respondToJobAssignment'])->name('job.respond');
            Route::post('/assign/subagency',[WebsiteAgencyController::class, 'assignSubAgency'])->name('assign.subagency');
            Route::post('/assign/agent',[WebsiteAgencyController::class, 'assignAgent'])->name('assign.agent');
            Route::get('/pipeline',[WebsiteAgencyController::class, 'candidatePipeline'])->name('pipeline');
            Route::post('/pipeline/{id}/approve',[WebsiteAgencyController::class, 'approveCandidate'])->name('pipeline.approve');
            Route::post('/agency/pipeline/status/{id}',[WebsiteAgencyController::class, 'updateCandidateStatus'])->name('agency.pipeline.status');
            Route::post('/agency/contract/{id}/accept',[WebsiteAgencyController::class, 'acceptContract'])->name('agency.contract.accept');
            Route::post('/agency/contract/{id}/reject',[WebsiteAgencyController::class, 'rejectContract'])->name('agency.contract.reject');

            Route::get('visa-processing', [AgencyVisaProcessingController::class, 'index'])->name('visa-processing.index');
            Route::get('visa-processing/{vp_case}', [AgencyVisaProcessingController::class, 'show'])->name('visa-processing.show');
            Route::post('visa-processing/start', [AgencyVisaProcessingController::class, 'start'])->name('visa-processing.start');
            Route::post('visa-processing/{vp_case}/submit', [AgencyVisaProcessingController::class, 'submitStep'])->name('visa-processing.submit');
            Route::post('visa-processing/{vp_case}/verify', [AgencyVisaProcessingController::class, 'verify'])->name('visa-processing.verify');
            Route::post('visa-processing/{vp_case}/send-back', [AgencyVisaProcessingController::class, 'sendBack'])->name('visa-processing.send-back');
            Route::post('visa-processing/{vp_case}/restart', [AgencyVisaProcessingController::class, 'restart'])->name('visa-processing.restart');
            Route::post('visa-processing/{vp_case}/mark-deployed', [AgencyVisaProcessingController::class, 'markDeployed'])->name('visa-processing.mark-deployed');
            Route::get('visa-processing/{vp_case}/file/{fileId}', [AgencyVisaProcessingController::class, 'downloadFile'])->name('visa-processing.file');
            Route::get('visa-processing/{vp_case}/file/{fileId}/view', [AgencyVisaProcessingController::class, 'viewFile'])->name('visa-processing.file.view');
            Route::post('visa-processing/{vp_case}/requirements/{requirement}/review', [AgencyVisaProcessingController::class, 'reviewRequirement'])->name('visa-processing.requirements.review');

            Route::get('protector', [AgencyProtectorController::class, 'index'])->name('protector.index');
            Route::post('protector', [AgencyProtectorController::class, 'store'])->name('protector.store');
            Route::post('protector/{id}/update', [AgencyProtectorController::class, 'update'])->name('protector.update');

            Route::get('nominated-workers', [AgencyNominatedWorkerController::class, 'index'])->name('nominated-workers.index');
            Route::get('nominated-workers/batches/{batch}', [AgencyNominatedWorkerController::class, 'showBatch'])->name('nominated-workers.batches.show');
            Route::post('nominated-workers/batches/{batch}/respond', [AgencyNominatedWorkerController::class, 'respondBatch'])->name('nominated-workers.batches.respond');
            Route::post('nominated-workers/documents', [AgencyNominatedWorkerController::class, 'uploadDocuments'])->name('nominated-workers.documents');
            Route::post('nominated-workers/documents/{document}/rematch', [AgencyNominatedWorkerController::class, 'rematch'])->name('nominated-workers.rematch');
            Route::get('nominated-workers/{worker}', [AgencyNominatedWorkerController::class, 'show'])->name('nominated-workers.show');
            Route::post('nominated-workers/{worker}/visa-step', [AgencyNominatedWorkerController::class, 'submitVisaStep'])->name('nominated-workers.visa-step');

            Route::get('commissions', [WebsiteAgencyController::class, 'commissions'])->name('commissions.index');
            Route::get('commissions/export', [WebsiteAgencyController::class, 'exportCommissions'])->name('commissions.export');
            Route::get('commissions/{id}/receipt', [WebsiteAgencyController::class, 'downloadCommissionReceipt'])->name('commissions.receipt');

            Route::get('reports', [AgencyReportController::class, 'index'])->name('reports.index');
            Route::get('reports/{type}', [AgencyReportController::class, 'show'])->name('reports.show');
            Route::get('reports/{type}/export', [AgencyReportController::class, 'export'])->name('reports.export');

            Route::get('ai/summary', [AgencyAIController::class, 'summary'])->name('ai.summary');
            Route::post('ai/summary/generate', [AgencyAIController::class, 'generateSummary'])->name('ai.summary.generate');
            Route::get('ai/candidate-matcher/{job}', [AgencyAIController::class, 'candidateMatcher'])->name('ai.candidate-matcher');
            Route::get('ai/visa-delay-forecast', [AgencyAIController::class, 'visaDelayForecast'])->name('ai.visa-delay-forecast');


        });

        }); // end agency.documents group

        Route::post('/agency/bookmark/{candidate}', 'agencyBookmarkCandidate')->name('agencybookmarkcandidate')->middleware('user_active');
        Route::get('account-progress', 'accountProgress')->name('account-progress');
        Route::put('/profile/complete/{id}', 'profileCompleteProgress')->name('profile.complete');
        Route::get('location/states-by-name', [LocationLookupController::class, 'statesByName'])->name('location.statesByName');
        Route::get('location/cities-by-name', [LocationLookupController::class, 'citiesByName'])->name('location.citiesByName');
        Route::get('/bookmark/categories', 'bookmarkCategories')->name('bookmark.category.index');
        Route::post('/bookmark/categories/store', 'bookmarkCategoriesStore')->name('bookmark.category.store');
        Route::get('/bookmark/categories/edit/{category}', 'bookmarkCategoriesEdit')->name('bookmark.category.edit');
        Route::put('/bookmark/categories/update/{category}', 'bookmarkCategoriesUpdate')->name('bookmark.category.update');
        Route::delete('/bookmark/categories/destroy/{category}', 'bookmarkCategoriesDestroy')->name('bookmark.category.destroy');
        Route::post('username/change', 'usernameUpdate')->name('username.change');
    });

Route::middleware(['auth'])->group(function () {

    Route::get(
        '/agency/invitations',[WebsiteAgencyController::class, 'agencyInvitations'])->name('agency.invitations');

    Route::post('/agency/send-company-invitation',[WebsiteAgencyController::class, 'sendCompanyInvitation'])->name('agency.send.company.invitation');
    Route::get('/company/invitation/{token}',[WebsiteAgencyController::class, 'companyInvitationPage'])->name('company.invitation.page');

    Route::get('/location/states-by-name', [LocationLookupController::class, 'statesByName'])->name('location.statesByName');
    Route::get('/location/cities-by-name', [LocationLookupController::class, 'citiesByName'])->name('location.citiesByName');

});

// Phase 10: Agent sub-account invite flow
Route::middleware(['auth', 'agency'])->group(function () {
    Route::get('/agency/my-agents', [WebsiteAgencyController::class, 'myAgents'])->name('agency.my.agents');
    Route::post('/agency/send-agent-invite', [WebsiteAgencyController::class, 'sendAgentInvite'])->name('agency.send.agent.invite');
    Route::post('/agency/agents/{id}/toggle-status', [WebsiteAgencyController::class, 'toggleAgentStatus'])->name('agency.agent.toggle_status');
});
// Public: invited agent accepts (no auth required — they may not have an account yet)
Route::get('/agent/invite/{token}', [WebsiteAgencyController::class, 'agentInviteAcceptPage'])->name('agency.agent.invite.accept');





Route::prefix('agency')->middleware(['agency', 'has_plan'])->group(function () {
    Route::get('verify-documents', [AgencyVerifyDocuments::class, 'index'])->name('agency.verify.documents.index');
    Route::post('verify-documents', [AgencyVerifyDocuments::class, 'store'])->name('agency.verify.documents.store');
});

Route::prefix('agency')->middleware(['auth', 'agency'])->name('agency.')->group(function () {
    Route::get('/candidates', [WebsiteAgencyController::class, 'candidates'])->name('candidates.index');
    Route::get('/candidates/create', [WebsiteAgencyController::class, 'createCandidate'])->name('candidates.create');
    Route::post('/candidates/store', [WebsiteAgencyController::class, 'storeCandidate'])->name('candidates.store');
    Route::get('/candidates/edit/{id}', [WebsiteAgencyController::class, 'editCandidate'])->name('candidates.edit');
    Route::post('/candidates/update/{id}', [WebsiteAgencyController::class, 'updateCandidate'])->name('candidates.update');
    Route::get('/candidates/delete/{id}', [WebsiteAgencyController::class, 'deleteCandidate'])->name('candidates.delete');
    Route::get('/candidates/{id}/documents', [WebsiteAgencyController::class, 'candidateDocuments'])->name('candidates.documents');
    Route::post('/candidates/{id}/documents/status', [WebsiteAgencyController::class, 'updateCandidateDocumentStatus'])->name('candidates.documents.status');
    // REMOVED duplicate: Route::post('/apply-candidate', ...)->name('apply.candidate');
    //                    already registered above (inside the protected agency block).
    Route::get('/applications', [WebsiteAgencyController::class, 'applications'])->name('applications');
});

Route::get('agency/pending', function () {
    return view('frontend.pages.agency.pending');
})->name('agency.pending');

/*
|--------------------------------------------------------------------------
| Candidate Contracts
|--------------------------------------------------------------------------
*/
Route::prefix('candidate')->middleware(['auth','candidate'])->name('candidate.')->group(function () {
    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('contracts/{id}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('contracts/pdf/{id}', [ContractController::class, 'downloadPdf'])->name('contracts.pdf');
});

/*
|--------------------------------------------------------------------------
| Misc Authenticated
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::post('/agency/job/repost', [WebsiteAgencyController::class, 'repostJob'])->name('agency.job.repost');
});

/*
|--------------------------------------------------------------------------
| Global / Artisan
|--------------------------------------------------------------------------
*/
Route::controller(GlobalController::class)->group(function () {
    Route::get('/check/username/{name}', 'checkUsername');
    Route::get('/translated/texts', 'fetchCurrentTranslatedText');
    Route::get('/lang/{lang}', 'changeLanguage');
    Route::post('/ckeditor/upload', 'ckeditorImageUpload')->name('ckeditor.upload');
});

// Admin-only utility routes — must be authenticated as admin
Route::controller(GlobalController::class)->middleware(['auth:admin'])->group(function () {
    Route::get('/migrate/data', 'migrateData');
    Route::get('/optimize-clear', 'optimizeClear')->name('app.optimize-clear');
});

/*
|--------------------------------------------------------------------------
| Payment
|--------------------------------------------------------------------------
*/
Route::get('/payment-from-app/{label}', [ApiCompanyController::class, 'payment']);
Route::get('/agency-payment-from-app/{label}', [ApiAgencyController::class, 'payment']);

Route::controller(PayPalController::class)->group(function () {
    Route::post('paypal/payment', 'processTransaction')->name('paypal.post');
    Route::get('success-transaction', 'successTransaction')->name('paypal.successTransaction');
    Route::get('cancel-transaction', 'cancelTransaction')->name('paypal.cancelTransaction');
});

/*
|--------------------------------------------------------------------------
| OTP
|--------------------------------------------------------------------------
*/
Route::get('/otp/verify', [OTPController::class, 'showVerifyForm'])->name('otp.verify');
Route::post('/send-otp', [OTPController::class, 'sendOTP'])->name('send.otp')->middleware('throttle:5,10');
Route::post('/verify-otp', [OTPController::class, 'verifyOTP'])->name('verify.otp')->middleware('throttle:10,5');

/*
|--------------------------------------------------------------------------
| Fallback Custom Pages — MUST be the LAST route in the file.
|--------------------------------------------------------------------------
*/
Route::get('/{slug}', [PageController::class, 'showCustomPage'])->name('showCustomPage');

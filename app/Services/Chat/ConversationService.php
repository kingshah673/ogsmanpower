<?php

namespace App\Services\Chat;

use App\Models\User;
use App\Models\Candidate;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use App\Services\Auth\OTPService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\WhatsAppVerificationService;
use App\Services\Jobs\JobSearchService;
use App\Services\Jobs\JobApplyService;
use App\Services\Jobs\ApplicationStatusService;
use App\Services\Jobs\JobPostingService;

class ConversationService
{
    protected $state;

    protected $session;

    protected $emailVerification;

    protected $whatsappVerification;

    protected $otp;

    protected $jobSearch;

    protected $jobApply;

    protected $applicationStatus;

    protected $jobPosting;

    protected $handover;

    public function __construct(

        StateService $state,

        SessionService $session,

        EmailVerificationService $emailVerification,

        WhatsAppVerificationService $whatsappVerification,

        OTPService $otp,

        JobSearchService $jobSearch,

        ApplicationStatusService $applicationStatus,

        JobPostingService $jobPosting,

        HandoverService $handover,

        JobApplyService $jobApply

    ) {

        $this->state = $state;

        $this->session = $session;

        $this->emailVerification = $emailVerification;

        $this->whatsappVerification = $whatsappVerification;

        $this->otp = $otp;

        $this->jobSearch = $jobSearch;

        $this->jobApply = $jobApply;

        $this->applicationStatus = $applicationStatus;

        $this->jobPosting = $jobPosting;

        $this->handover = $handover;
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN HANDLE
    |--------------------------------------------------------------------------
    */

    public function handle(
        $chatSession,
        $message
    ) {

        /*
        |--------------------------------------------------------------------------
        | APPLY JOB FLOW
        |--------------------------------------------------------------------------
        */

        if (
            $chatSession->intent === 'apply_job'
        ) {

            return
                $this->handleApplyJob(
                    $chatSession,
                    $message
                );
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK APPLICATION STATUS
        |--------------------------------------------------------------------------
        */

        if (
            $chatSession->intent
            === 'check_application_status'
        ) {

            return
                $this->handleApplicationStatus(
                    $chatSession
                );
        }

        /*
        |--------------------------------------------------------------------------
        | POST JOB FLOW
        |--------------------------------------------------------------------------
        */

        if (
            $chatSession->intent
            === 'post_job'
        ) {

            return
                $this->handlePostJob(
                    $chatSession,
                    $message
                );
        }

        /*
        |--------------------------------------------------------------------------
        | HUMAN HANDOVER
        |--------------------------------------------------------------------------
        */

        if (
            $chatSession->intent
            === 'human_handover'
        ) {

            return
                $this->handleHandover(
                    $chatSession,
                    $message
                );
        }

        /*
        |--------------------------------------------------------------------------
        | REGISTRATION FLOW
        |--------------------------------------------------------------------------
        */

        return
            $this->handleRegistration(
                $chatSession,
                $message
            );
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTRATION FLOW
    |--------------------------------------------------------------------------
    */

    protected function handleRegistration(
        $chatSession,
        $message
    ) {
        
        \Log::info('REGISTRATION FLOW', [
    'step' => $chatSession->current_step,
    'message' => $message,
]);

        $step = $chatSession->current_step;

        /*
        |--------------------------------------------------------------------------
        | START
        |--------------------------------------------------------------------------
        */

        /*
|--------------------------------------------------------------------------
| START
|--------------------------------------------------------------------------
*/

if (!$step) {

    $this->state->set(
        $chatSession,
        'main_menu'
    );

    return

        "👋 Welcome to Career Workforce AI Assistant\n\n"

        .

        "I can help you with:\n\n"

        .

        "1️⃣ Find Jobs\n"

        .

        "2️⃣ Hire Staff\n"

        .

        "3️⃣ Visa Services\n"

        .

        "4️⃣ Register Account\n"

        .

        "5️⃣ Check Application Status\n"

        .

        "6️⃣ Talk to Consultant\n\n"

        .

        "💡 You can also ask me a question directly.\n\n"

        .

        "Please reply with a number.";
}

/*
|--------------------------------------------------------------------------
| MAIN MENU
|--------------------------------------------------------------------------
*/

if ($step === 'main_menu') {

    if ($step === 'main_menu') {

    \Log::info('MAIN MENU HIT', [
        'message' => $message
    ]);

    switch (trim($message)) {

        case '1':

            $this->state->set(
                $chatSession,
                'job_search'
            );

            return

                "🌍 What type of job are you looking for?\n\n"

                .

                "Examples:\n"

                .

                "• Driver\n"

                .

                "• Electrician\n"

                .

                "• Security Guard\n"

                .

                "• Warehouse Worker\n"

                .

                "• Nurse";

        case '2':

            $this->state->set(
                $chatSession,
                'hire_staff'
            );

            return

                "🏢 Please tell us:\n\n"

                .

                "• Position\n"

                .

                "• Country\n"

                .

                "• Number of Workers Required";

        case '3':

            $this->state->set(
                $chatSession,
                'visa_menu'
            );

            return

                "✈️ Visa Services\n\n"

                .

                "1️⃣ Study Visa\n"

                .

                "2️⃣ Visit Visa\n"

                .

                "3️⃣ Work Visa\n"

                .

                "4️⃣ Immigration";

        case '4':

            $this->state->set(
                $chatSession,
                'select_role'
            );

            return

                "Please select account type:\n\n"

                .

                "1️⃣ Candidate\n"

                .

                "2️⃣ Company\n"

                .

                "3️⃣ Agent\n"

                .

                "4️⃣ Agency";

        case '5':

            return

                "📋 Application status requires registration.\n\n"

                .

                "Please select:\n\n"

                .

                "1️⃣ Candidate\n"

                .

                "2️⃣ Company\n"

                .

                "3️⃣ Agent\n"

                .

                "4️⃣ Agency";

        case '6':

            $chatSession->update([
                'intent' => 'human_handover'
            ]);

            return

                "👨‍💼 Please type your message.\n\n"

                .

                "Our consultant will contact you shortly.";

        default:

            return

                "Please choose:\n\n"

                .

                "1️⃣ Find Jobs\n"

                .

                "2️⃣ Hire Staff\n"

                .

                "3️⃣ Visa Services\n"

                .

                "4️⃣ Register Account\n"

                .

                "5️⃣ Check Application Status\n"

                .

                "6️⃣ Talk to Consultant";
    }
}

/*
|--------------------------------------------------------------------------
| ROLE SELECTION
|--------------------------------------------------------------------------
*/

if ($step === 'select_role') {

    $roles = [

        '1' => 'candidate',

        '2' => 'company',

        '3' => 'agent',

        '4' => 'agency'

    ];

    if (!isset($roles[trim($message)])) {

        return

            "Please select:\n\n"

            .

            "1️⃣ Candidate\n"

            .

            "2️⃣ Company\n"

            .

            "3️⃣ Agent\n"

            .

            "4️⃣ Agency";
    }

    $this->session->saveData(

        $chatSession,

        [

            'role' => $roles[trim($message)]

        ]

    );
    
    

    $this->state->set(

        $chatSession,

        'ask_name'

    );

    return

        "Please enter your full name.";
}

/*
|--------------------------------------------------------------------------
| JOB SEARCH
|--------------------------------------------------------------------------
*/

if ($step === 'job_search') {

    \Log::info('STEP DEBUG', [
    'step' => $step,
    'equals' => ($step === 'job_search')
]);

    try {

        $jobs =
            $this->jobSearch
                ->search($message);

        \Log::info('JOB SEARCH RESULT', [
            'count' => $jobs->count()
        ]);

    } catch (\Exception $e) {

        \Log::error('JOB SEARCH ERROR', [
            'error' => $e->getMessage()
        ]);

        return 'Job search is temporarily unavailable.';
    }

    if (!$jobs->count()) {

        return
            "No matching jobs found.\n\nTry another keyword.";
    }

    $response =
        "🌍 Available Jobs:\n\n";

    foreach ($jobs as $job) {

        $response .=
            $job->id .
            '. ' .
            $job->title .
            "\n";
    }

    $response .=
        "\nReply with Job ID for details.";

    return $response;
}

        /*
        |--------------------------------------------------------------------------
        | ASK NAME
        |--------------------------------------------------------------------------
        */

        if ($step === 'ask_name') {

            $this->session->saveData(
                $chatSession,
                [
                    'name' => trim($message)
                ]
            );

            $this->state->set(
                $chatSession,
                'ask_email'
            );

            return
                'Please enter your email address';
        }

        /*
        |--------------------------------------------------------------------------
        | ASK EMAIL
        |--------------------------------------------------------------------------
        */

        if ($step === 'ask_email') {

            if (
                !filter_var(
                    $message,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                return
                    'Please enter valid email address';
            }

            /*
            |--------------------------------------------------------------------------
            | CHECK EXISTING USER
            |--------------------------------------------------------------------------
            */

            $existingUser =
                User::where(
                    'email',
                    trim($message)
                )->first();

            if (
                $existingUser &&
                $existingUser->role
            ) {

                return
                    'This email is already registered.';
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE EMAIL
            |--------------------------------------------------------------------------
            */

            $this->session->saveData(
                $chatSession,
                [
                    'email' => trim($message)
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | TEMP USER
            |--------------------------------------------------------------------------
            */

            $tempUser =
                User::updateOrCreate(

                    [
                        'email'
                            => trim($message)
                    ],

                    [
                        'name'
                            => 'Temporary User',

                        'password'
                            => bcrypt(rand()),

                        'status'
                            => 0
                    ]
                );

            /*
            |--------------------------------------------------------------------------
            | SEND EMAIL OTP
            |--------------------------------------------------------------------------
            */

            $this->emailVerification
                ->send($tempUser);

            /*
            |--------------------------------------------------------------------------
            | SAVE TEMP USER
            |--------------------------------------------------------------------------
            */

            $this->session->saveData(
                $chatSession,
                [
                    'temp_user_id'
                        => $tempUser->id
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | NEXT STEP
            |--------------------------------------------------------------------------
            */

            $this->state->set(
                $chatSession,
                'verify_email_otp'
            );

            return
                'ðŸ“§ Verification code sent to your email. Please enter OTP.';
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFY EMAIL OTP
        |--------------------------------------------------------------------------
        */

        if (
            $step === 'verify_email_otp'
        ) {

            $data =
                $this->getSessionData(
                    $chatSession
                );

            $user =
                User::find(
                    $data['temp_user_id']
                        ?? null
                );

            if (!$user) {

                return
                    'User not found.';
            }

            $verified =
                $this->otp->verify(
                    $user,
                    trim($message)
                );

            if (!$verified) {

                return
                    'Invalid or expired OTP';
            }

            $this->state->set(
                $chatSession,
                'ask_whatsapp'
            );

            return
                'âœ… Email verified successfully. Please enter your WhatsApp number.';
        }

        /*
        |--------------------------------------------------------------------------
        | ASK WHATSAPP
        |--------------------------------------------------------------------------
        */

        if ($step === 'ask_whatsapp') {

            $phone =
                preg_replace(
                    '/[^0-9]/',
                    '',
                    $message
                );

            if (
                strlen($phone) < 10
            ) {

                return
                    'Please enter valid WhatsApp number';
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE PHONE
            |--------------------------------------------------------------------------
            */

            $this->session->saveData(
                $chatSession,
                [
                    'whatsapp'
                        => $phone
                ]
            );

            $data =
                $this->getSessionData(
                    $chatSession
                );

            $user =
                User::find(
                    $data['temp_user_id']
                        ?? null
                );

            if (!$user) {

                return
                    'User not found.';
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE PHONE
            |--------------------------------------------------------------------------
            */

            $user->update([
                'whatsapp'
                    => $phone
            ]);

            /*
            |--------------------------------------------------------------------------
            | SEND WHATSAPP OTP
            |--------------------------------------------------------------------------
            */

            $this->whatsappVerification
                ->send($user);

            /*
            |--------------------------------------------------------------------------
            | NEXT STEP
            |--------------------------------------------------------------------------
            */

            $this->state->set(
                $chatSession,
                'verify_whatsapp_otp'
            );

            $user->update([
    'otp_sent_at' => now()
]);

return
    "📱 WhatsApp verification code sent.\n\n"
    .
    "Please enter OTP.\n\n"
    .
    "If you don't receive it, type:\n"
    .
    "'resend otp'\n"
    .
    "after 60 seconds.";
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFY WHATSAPP OTP
        |--------------------------------------------------------------------------
        */

if ($step === 'verify_whatsapp_otp') {

    $data = $this->getSessionData($chatSession);

    $user = User::find(
        $data['temp_user_id'] ?? null
    );

    if (!$user) {

        return 'User not found.';
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND OTP
    |--------------------------------------------------------------------------
    */

    if (
        strtolower(trim($message)) === 'resend otp'
    ) {

        if (
            $user->otp_sent_at &&
            now()->diffInSeconds(
                $user->otp_sent_at
            ) < 60
        ) {

            $remaining =
                60 -
                now()->diffInSeconds(
                    $user->otp_sent_at
                );

            return
                "Please wait {$remaining} seconds before requesting a new OTP.";
        }

        $otp = $this->otp->generate($user);

return
    "📱 Your verification code is: {$otp}\n\nPlease enter OTP.";
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY OTP
    |--------------------------------------------------------------------------
    */

    $verified =
        $this->otp->verify(
            $user,
            trim($message)
        );

    if (!$verified) {

        return
            "Invalid or expired OTP.\n\nType 'resend otp' to get a new code.";
    }

    $this->state->set(
        $chatSession,
        'ask_password'
    );

    return
        '✅ WhatsApp verified successfully. Please enter your password.';
}

        /*
        |--------------------------------------------------------------------------
        | ASK PASSWORD
        |--------------------------------------------------------------------------
        */

        if ($step === 'ask_password') {

            if (
                strlen($message) < 6
            ) {

                return
                    'Password must be at least 6 characters.';
            }

            $data =
                $this->getSessionData(
                    $chatSession
                );

            $user =
                User::find(
                    $data['temp_user_id']
                        ?? null
                );

            if (!$user) {

                return
                    'User not found.';
            }

            /*
            |--------------------------------------------------------------------------
            | FINAL USER UPDATE
            |--------------------------------------------------------------------------
            */

            $user->update([

                'name'
                    => $data['name']
                        ?? null,

                'email'
                    => $data['email']
                        ?? null,

                'whatsapp'
                    => $data['whatsapp']
                        ?? null,

                'password'
                    => Hash::make($message),

                'role'
                    => 'candidate',

                'status'
                    => 1,

                'is_otp_verified'
                    => 1,

                'email_verified_at'
                    => now()
            ]);

            /*
            |--------------------------------------------------------------------------
            | CREATE CANDIDATE
            |--------------------------------------------------------------------------
            */

            Candidate::firstOrCreate([

                'user_id'
                    => $user->id
            ]);

            /*
            |--------------------------------------------------------------------------
            | COMPLETE
            |--------------------------------------------------------------------------
            */

            $this->state->complete(
                $chatSession
            );

            return
                'âœ… Registration completed successfully.';
        }

        return null;
    }
    }

    /*
    |--------------------------------------------------------------------------
    | POST JOB FLOW
    |--------------------------------------------------------------------------
    */

    protected function handlePostJob(
        $chatSession,
        $message
    ) {

        $step =
            $chatSession->current_step;

        $user =
            auth()->user();

        if (!$user) {

            return
                'Please login first.';
        }

        if (
            $user->role !== 'company'
        ) {

            return
                'Only companies can post jobs.';
        }

        $company =
            Company::where(
                'user_id',
                $user->id
            )->first();

        if (!$company) {

            return
                'Company profile not found.';
        }

        /*
        |--------------------------------------------------------------------------
        | START FLOW
        |--------------------------------------------------------------------------
        */

        if (!$step) {

            $this->state->set(
                $chatSession,
                'post_job_title'
            );

            return
                'Please enter job title';
        }

        /*
        |--------------------------------------------------------------------------
        | JOB TITLE
        |--------------------------------------------------------------------------
        */

        if (
            $step === 'post_job_title'
        ) {

            $this->session->saveData(
                $chatSession,
                [
                    'title' => $message
                ]
            );

            $this->state->set(
                $chatSession,
                'post_job_salary'
            );

            return
                'Please enter salary';
        }

        /*
        |--------------------------------------------------------------------------
        | SALARY
        |--------------------------------------------------------------------------
        */

        if (
            $step === 'post_job_salary'
        ) {

            $this->session->saveData(
                $chatSession,
                [
                    'salary' => $message
                ]
            );

            $this->state->set(
                $chatSession,
                'post_job_country'
            );

            return
                'Please enter country';
        }

        /*
        |--------------------------------------------------------------------------
        | COUNTRY
        |--------------------------------------------------------------------------
        */

        if (
            $step === 'post_job_country'
        ) {

            $this->session->saveData(
                $chatSession,
                [
                    'country' => $message
                ]
            );

            $this->state->set(
                $chatSession,
                'post_job_quantity'
            );

            return
                'How many workers required?';
        }

        /*
        |--------------------------------------------------------------------------
        | QUANTITY
        |--------------------------------------------------------------------------
        */

        if (
            $step === 'post_job_quantity'
        ) {

            $this->session->saveData(
                $chatSession,
                [
                    'quantity' => $message
                ]
            );

            $this->state->set(
                $chatSession,
                'post_job_deadline'
            );

            return
                'Please enter application deadline';
        }

        /*
        |--------------------------------------------------------------------------
        | DEADLINE
        |--------------------------------------------------------------------------
        */

        if (
            $step === 'post_job_deadline'
        ) {

            $this->session->saveData(
                $chatSession,
                [
                    'deadline' => $message
                ]
            );

            $data =
                $this->getSessionData(
                    $chatSession
                );

            /*
            |--------------------------------------------------------------------------
            | CREATE JOB
            |--------------------------------------------------------------------------
            */

            $job =
                $this->jobPosting
                    ->create(
                        $company,
                        $data
                    );

            /*
            |--------------------------------------------------------------------------
            | COMPLETE
            |--------------------------------------------------------------------------
            */

            $this->state->complete(
                $chatSession
            );

            return

                "âœ… Job posted successfully.\n\n"

                .

                "Job ID: "

                .

                $job->id;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | APPLICATION STATUS
    |--------------------------------------------------------------------------
    */

    protected function handleApplicationStatus(
        $chatSession
    ) {

        $user =
            auth()->user();

        if (!$user) {

            return
                'Please login first.';
        }

        $candidate =
            Candidate::where(
                'user_id',
                $user->id
            )->first();

        if (!$candidate) {

            return
                'Candidate profile not found.';
        }

        $applications =
            $this->applicationStatus
                ->get(
                    $candidate->id
                );

        if (
            !$applications->count()
        ) {

            return
                'No job applications found.';
        }

        $response =
            "ðŸ“‹ Your Application Status:\n\n";

        foreach ($applications as $application) {

            $response .=

                "ðŸ”¹ "

                .

                ($application->job->title
                    ?? 'Job')

                .

                " - "

                .

                ucfirst(
                    $application->status
                )

                .

                "\n";
        }

        return $response;
    }

    /*
    |--------------------------------------------------------------------------
    | APPLY JOB FLOW
    |--------------------------------------------------------------------------
    */

    protected function handleApplyJob(
        $chatSession,
        $message
    ) {

        $step =
            $chatSession->current_step;

        /*
        |--------------------------------------------------------------------------
        | SEARCH JOBS
        |--------------------------------------------------------------------------
        */

        if (

            !$step
            ||
            $step === 'search_job'

        ) {

            $jobs =
                $this->jobSearch
                    ->search($message);

            if (
                !$jobs->count()
            ) {

                return
                    'No matching jobs found.';
            }

            $response =
                "Available Jobs:\n\n";

            foreach ($jobs as $job) {

                $response .=

                    $job->id
                    .
                    '. '
                    .
                    $job->title
                    .
                    "\n";
            }

            $response .=
                "\nPlease enter Job ID to apply.";

            $this->state->set(
                $chatSession,
                'apply_job_select'
            );

            return $response;
        }

        /*
        |--------------------------------------------------------------------------
        | APPLY JOB
        |--------------------------------------------------------------------------
        */

        if (
            $step === 'apply_job_select'
        ) {

            $jobId =
                (int) trim($message);

            $user =
                auth()->user();

            if (!$user) {

                return
                    'Please login first.';
            }

            $candidate =
                Candidate::where(
                    'user_id',
                    $user->id
                )->first();

            if (!$candidate) {

                return
                    'Candidate profile not found.';
            }

            $apply =
                $this->jobApply
                    ->apply(
                        $jobId,
                        $candidate->id
                    );

            $this->state->complete(
                $chatSession
            );

            return
                $apply['message'];
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | HANDOVER
    |--------------------------------------------------------------------------
    */

    protected function handleHandover(
        $chatSession,
        $message
    ) {

        $this->handover
            ->create(

                $chatSession,

                $message
            );

        $this->state->complete(
            $chatSession
        );

        return
            'âœ… Your request has been forwarded to our consultant. We will contact you shortly.';
    }

    /*
    |--------------------------------------------------------------------------
    | SESSION DATA HELPER
    |--------------------------------------------------------------------------
    */

    protected function getSessionData(
        $chatSession
    ) {

        $data =
            $chatSession->data;

        if (
            is_string($data)
        ) {

            $data =
                json_decode(
                    $data,
                    true
                );
        }

        return $data ?: [];
    }
}
<?php

namespace App\Services\AI;

use App\Models\Admin;
use App\Models\AIChatMessage;
use App\Models\AIHandoverRequest;
use App\Models\ChatLead;
use App\Models\User;
use App\Services\Chat\SeekerOnboardingService;
use App\Services\Chat\EmployerOnboardingService;
use App\Services\Chat\AgencyOnboardingService;
use App\Services\Chat\AgentAccountOnboardingService;
use App\Services\Chat\BrokerOnboardingService;
use App\Services\Company\CompanyDocumentVerificationService;
use Illuminate\Support\Facades\Log;

class SophiaContextService
{
    /**
     * Bootstrap payload for the Sophia widget (role, menu, profile gaps).
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        try {
            $admin = auth('admin')->user();

            if ($admin instanceof Admin) {
                return $this->forAdmin($admin);
            }

            $user = authUser();

            if ($user instanceof User) {
                return match ($user->role) {
                    'candidate' => $this->forCandidate($user),
                    'company' => $this->forEmployer($user),
                    'agency' => $this->forAgency($user),
                    'agent' => $this->forAgent($user),
                    'broker' => $this->forBroker($user),
                    default => $this->forGuest(),
                };
            }

            return $this->forGuest();
        } catch (\Throwable $e) {
            Log::warning('[Sophia] context build failed: '.$e->getMessage());

            return $this->forGuest();
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function safeRoute(string $name, array $parameters = []): ?string
    {
        try {
            return route($name, $parameters);
        } catch (\Throwable $e) {
            Log::warning("[Sophia] missing route [{$name}]: ".$e->getMessage());

            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     * @return array<int, array<string, mixed>>
     */
    protected function filterActions(array $actions): array
    {
        return array_values(array_filter($actions, function (array $action) {
            if (($action['type'] ?? '') !== 'link') {
                return true;
            }

            return ! empty($action['url']);
        }));
    }

    protected function forGuest(): array
    {
        $ctx = [
            'mode' => 'guest',
            'role' => 'guest',
            'label' => 'Guest',
            'assistant' => 'Sophia',
            'terminology_note' => 'Sophia is the AI chatbot. Agent / Facilitator is a separate portal user role (not AI).',
            'greeting' => "Hi, I'm **Sophia** — your Career WorkForce AI assistant.\n\nTell me **who you are** so I can guide you, or choose an option below.",
            'show_role_picker' => true,
            'actions' => [
                ['key' => 'seeker_register', 'label' => 'Create seeker account (AI)', 'type' => 'role', 'value' => 'seeker'],
                ['key' => 'employer_register', 'label' => 'Create employer account (AI)', 'type' => 'role', 'value' => 'employer'],
                ['key' => 'agency_register', 'label' => 'Create agency account (AI)', 'type' => 'role', 'value' => 'agency'],
                ['key' => 'agent_register', 'label' => 'Agent / Facilitator (invite)', 'type' => 'role', 'value' => 'agent'],
                ['key' => 'broker_register', 'label' => 'Create broker account (AI)', 'type' => 'role', 'value' => 'broker'],
                ['key' => 'find_jobs', 'label' => 'Find jobs', 'type' => 'message', 'value' => 'find jobs'],
                ['key' => 'visa_help', 'label' => 'Visa help', 'type' => 'message', 'value' => 'visa help'],
            ],
        ];

        $seeker = app(SeekerOnboardingService::class);
        if ($seeker->isActive()) {
            $ctx['seeker_active'] = true;
            $ctx['seeker_step'] = $seeker->currentStep();
            $ctx['accepts_documents'] = true;
            $ctx['show_role_picker'] = false;
            $ctx['document_hint'] = match ($seeker->currentStep()) {
                'seeker_cv' => 'Attach your CV with the paperclip button below.',
                'seeker_passport' => 'Attach your passport image with the paperclip button below.',
                default => 'Attach documents with the paperclip button below.',
            };
        }

        $employer = app(EmployerOnboardingService::class);
        if ($employer->isActive()) {
            $ctx['employer_active'] = true;
            $ctx['employer_step'] = $employer->currentStep();
            $ctx['seeker_active'] = false;
            $ctx['accepts_documents'] = true;
            $ctx['show_role_picker'] = false;
            $ctx['document_hint'] = match ($employer->currentStep()) {
                'employer_trade_license' => 'Attach your trade license with the paperclip button below.',
                default => 'Type your answer or attach documents with the paperclip button below.',
            };
        }

        $agency = app(AgencyOnboardingService::class);
        if ($agency->isActive()) {
            $ctx['agency_active'] = true;
            $ctx['agency_step'] = $agency->currentStep();
            $ctx['seeker_active'] = false;
            $ctx['employer_active'] = false;
            $ctx['accepts_documents'] = true;
            $ctx['show_role_picker'] = false;
            $ctx['document_hint'] = match ($agency->currentStep()) {
                'agency_license' => 'Enter your license number and/or attach a license scan.',
                default => 'Type your answer or attach documents with the paperclip button below.',
            };
        }

        $agentAccount = app(AgentAccountOnboardingService::class);
        if ($agentAccount->isActive()) {
            $ctx['agent_account_active'] = true;
            $ctx['agent_account_step'] = $agentAccount->currentStep();
            $ctx['show_role_picker'] = false;
            $ctx['accepts_documents'] = false;
        }

        $broker = app(BrokerOnboardingService::class);
        if ($broker->isActive()) {
            $ctx['broker_active'] = true;
            $ctx['broker_step'] = $broker->currentStep();
            $ctx['show_role_picker'] = false;
            $ctx['accepts_documents'] = false;
        }

        return $ctx;
    }

    protected function forCandidate(User $user): array
    {
        $candidate = $user->candidate;
        $completion = $candidate ? (int) $candidate->calculateProfileCompletion() : 0;
        $missing = ($candidate && $completion < 100) ? $candidate->profileCompletionMissing() : [];

        $shortlistedCount = 0;
        if ($candidate) {
            $shortlistedCount = (int) \App\Models\AppliedJob::query()
                ->where('candidate_id', $candidate->id)
                ->where('status', 'shortlisted')
                ->count();
        }

        $actions = $this->filterActions([
            ['key' => 'my_profile', 'label' => 'Complete my profile', 'type' => 'link', 'url' => $this->safeRoute('candidate.setting')],
            ['key' => 'find_jobs', 'label' => 'Find jobs', 'type' => 'link', 'url' => $this->safeRoute('website.job')],
            ['key' => 'applications', 'label' => 'My applications', 'type' => 'link', 'url' => $this->safeRoute('candidate.appliedjob')],
            ['key' => 'upload_cv', 'label' => 'Upload CV', 'type' => 'link', 'url' => $this->safeRoute('candidate.setting') ? $this->safeRoute('candidate.setting').'#cv' : null],
        ]);

        if ($shortlistedCount > 0) {
            array_unshift($actions, [
                'key' => 'shortlisted',
                'label' => 'My shortlisted jobs',
                'type' => 'message',
                'value' => 'which jobs am i shortlisted for',
            ]);
        }

        if ($completion < 100) {
            array_unshift($actions, [
                'key' => 'missing_info',
                'label' => 'What am I missing?',
                'type' => 'message',
                'value' => 'what is missing from my profile',
            ]);
        }

        $greeting = "Welcome back, **{$user->name}**!\n\n";
        $greeting .= "You're signed in as a **Job Seeker**";
        $greeting .= $completion > 0 ? " — profile **{$completion}%** complete." : '.';

        if ($shortlistedCount > 0) {
            $greeting .= "\n\n🎉 You're **shortlisted** for **{$shortlistedCount}** "
                .($shortlistedCount === 1 ? 'job' : 'jobs')
                .'. Ask me **which jobs am I shortlisted for** and I\'ll name them.';
        }

        if ($missing !== []) {
            $greeting .= "\n\nI can help you finish: **".implode('**, **', array_slice($missing, 0, 4)).'**.';
        }

        return [
            'mode' => 'portal',
            'role' => 'candidate',
            'label' => 'Job Seeker',
            'user_id' => $user->id,
            'greeting' => $greeting,
            'show_role_picker' => false,
            'profile_completion' => $completion,
            'missing_fields' => $missing,
            'actions' => $actions,
            'accepts_documents' => true,
            'document_hint' => 'Attach your CV or passport and I will help update your profile.',
        ];
    }

    protected function forEmployer(User $user): array
    {
        $company = $user->company;
        $docStatus = $company ? CompanyDocumentVerificationService::status($company) : null;
        $missingDocs = $company ? CompanyDocumentVerificationService::missingDocumentDetails($company) : [];

        $activeJobs = 0;
        $pendingApplicants = 0;
        $shortlistedApplicants = 0;
        if ($company) {
            $companyId = (int) $company->id;
            $activeJobs = (int) \App\Models\Job::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->count();
            $applicantBase = \App\Models\AppliedJob::query()
                ->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                        ->orWhereHas('job', fn ($jq) => $jq->where('company_id', $companyId));
                })
                ->whereNotNull('candidate_id');
            $pendingApplicants = (int) (clone $applicantBase)->where('status', 'pending')->count();
            $shortlistedApplicants = (int) (clone $applicantBase)->where('status', 'shortlisted')->count();
        }

        $actions = $this->filterActions([
            ['key' => 'dashboard', 'label' => 'Go to dashboard', 'type' => 'link', 'url' => $this->safeRoute('company.dashboard')],
            ['key' => 'my_jobs_q', 'label' => 'My jobs summary', 'type' => 'message', 'value' => 'show my jobs'],
            ['key' => 'applicants_q', 'label' => 'My applicants', 'type' => 'message', 'value' => 'show my applicants'],
            ['key' => 'shortlist_q', 'label' => 'Shortlisted applicants', 'type' => 'message', 'value' => 'who is shortlisted'],
            ['key' => 'post_job', 'label' => 'Post a job', 'type' => 'link', 'url' => $this->safeRoute('company.job.create')],
            ['key' => 'post_job_ad', 'label' => 'Upload job advertisement', 'type' => 'action', 'value' => 'start_job_posting'],
            ['key' => 'post_job_qa', 'label' => 'Post job (guided Q&A)', 'type' => 'action', 'value' => 'start_job_posting_qa'],
            ['key' => 'applicants', 'label' => 'Applicants page', 'type' => 'link', 'url' => $this->safeRoute('company.applicants')],
            ['key' => 'settings', 'label' => 'Company settings', 'type' => 'link', 'url' => $this->safeRoute('company.setting')],
        ]);

        if ($docStatus && $docStatus !== CompanyDocumentVerificationService::STATUS_PENDING_APPROVAL) {
            $actions[] = [
                'key' => 'upload_docs',
                'label' => 'Upload verification documents',
                'type' => 'action',
                'value' => 'start_employer_documents',
            ];
        }

        $greeting = "Welcome back, **{$user->name}**!\n\nYou're signed in as an **Employer**.";
        $greeting .= "\n\nSnapshot: **{$activeJobs}** active jobs · **{$pendingApplicants}** pending applicants · **{$shortlistedApplicants}** shortlisted.";
        $greeting .= "\nAsk me about your **jobs**, **applicants**, **shortlists**, or **verification** — I'll answer from your account.";

        if ($missingDocs !== []) {
            $labels = array_map(fn ($meta) => $meta['label'] ?? 'Document', $missingDocs);
            $greeting .= "\n\nStill needed for verification: **".implode('**, **', $labels).'**.';
            $greeting .= "\n\nUse **Upload verification documents** or attach files here.";
        } elseif ($company && $company->is_profile_verified) {
            $greeting .= "\n\nYour company documents are verified.";
        }

        return [
            'mode' => 'portal',
            'role' => 'employer',
            'label' => 'Employer',
            'user_id' => $user->id,
            'greeting' => $greeting,
            'show_role_picker' => false,
            'document_status' => $docStatus,
            'missing_documents' => collect($missingDocs)->map(fn ($meta, $slug) => [
                'slug' => $slug,
                'label' => $meta['label'] ?? 'Document',
                'help' => $meta['help'] ?? '',
            ])->values()->all(),
            'actions' => $actions,
            'accepts_documents' => true,
            'document_hint' => 'Attach a job advertisement (PDF/image) or verification documents.',
        ];
    }

    protected function forAgency(User $user): array
    {
        $agency = $user->agency;

        return [
            'mode' => 'portal',
            'role' => 'agency',
            'label' => 'Recruitment Agency',
            'user_id' => $user->id,
            'greeting' => "Welcome back, **{$user->name}**!\n\nYou're signed in as a **Recruitment Agency**."
                .($agency && ! $agency->is_profile_verified ? "\n\nPlease complete agency profile verification in settings." : '')
                ."\n\nI can help you **post jobs**, upload **verification documents**, or **invite an Agent / Facilitator**.",
            'show_role_picker' => false,
            'actions' => $this->filterActions([
                ['key' => 'dashboard', 'label' => 'Agency dashboard', 'type' => 'link', 'url' => $this->safeRoute('agency.dashboard')],
                ['key' => 'post_job_ad', 'label' => 'Upload job advertisement', 'type' => 'action', 'value' => 'start_job_posting'],
                ['key' => 'post_job_qa', 'label' => 'Post job (guided Q&A)', 'type' => 'action', 'value' => 'start_job_posting_qa'],
                ['key' => 'post_job', 'label' => 'Post a job (form)', 'type' => 'link', 'url' => $this->safeRoute('agency.job.create')],
                ['key' => 'invite_agent', 'label' => 'Invite Agent / Facilitator', 'type' => 'action', 'value' => 'start_agency_invite'],
                ['key' => 'upload_docs', 'label' => 'Upload verification documents', 'type' => 'action', 'value' => 'start_agency_documents'],
                ['key' => 'settings', 'label' => 'Agency settings', 'type' => 'link', 'url' => $this->safeRoute('agency.setting')],
            ]),
            'accepts_documents' => true,
            'document_hint' => 'Attach a job advertisement or agency verification documents.',
        ];
    }

    protected function forAgent(User $user): array
    {
        $agentId = $user->id;
        $workers = \App\Models\Candidate::query()->where('agent_id', $agentId)->count();
        $selected = \App\Models\Candidate::query()->where('agent_id', $agentId)->where('status', 'selected')->count();
        $pending = \App\Models\Candidate::query()->where('agent_id', $agentId)
            ->whereIn('status', ['submitted', 'shortlisted', 'interview'])->count();

        $parentAgency = $user->parentAgencyUser;

        $greeting = "Welcome back, **{$user->name}**!\n\nYou're signed in as an **Agent / Facilitator** (portal role — not Sophia AI).";
        if ($parentAgency) {
            $greeting .= "\n\nParent agency: **{$parentAgency->name}**.";
        }
        $greeting .= "\n\nYour workers: **{$workers}** registered · **{$selected}** selected · **{$pending}** in pipeline.";
        $greeting .= "\n\nUse **Sophia** to register new workers in the field with CV + passport upload.";

        return [
            'mode' => 'portal',
            'role' => 'agent',
            'label' => 'Agent / Facilitator',
            'user_id' => $user->id,
            'agency_id' => $user->agency_id,
            'greeting' => $greeting,
            'show_role_picker' => false,
            'worker_stats' => compact('workers', 'selected', 'pending'),
            'actions' => $this->filterActions([
                ['key' => 'register_worker', 'label' => 'Register worker (AI)', 'type' => 'action', 'value' => 'start_agent_worker'],
                ['key' => 'candidates', 'label' => 'My candidates', 'type' => 'link', 'url' => $this->safeRoute('agent.candidates.index')],
                ['key' => 'jobs', 'label' => 'Assigned jobs', 'type' => 'link', 'url' => $this->safeRoute('agent.jobs')],
                ['key' => 'pipeline', 'label' => 'Pipeline', 'type' => 'link', 'url' => $this->safeRoute('agent.pipeline')],
                ['key' => 'applications', 'label' => 'Applications', 'type' => 'link', 'url' => $this->safeRoute('agent.applications')],
                ['key' => 'visa', 'label' => 'Visa cases', 'type' => 'link', 'url' => $this->safeRoute('visa.dashboard')],
                ['key' => 'settings', 'label' => 'Settings', 'type' => 'link', 'url' => $this->safeRoute('agent.setting')],
            ]),
            'accepts_documents' => true,
            'document_hint' => 'Attach worker CV or passport to register via AI, or use quick actions below.',
        ];
    }

    protected function forBroker(User $user): array
    {
        $broker = $user->broker;
        $open = $broker ? $broker->demands()->where('status', 'open')->count() : 0;
        $routed = $broker ? $broker->demands()->where('status', 'routed')->count() : 0;

        return [
            'mode' => 'portal',
            'role' => 'broker',
            'label' => 'Broker / Middleman',
            'user_id' => $user->id,
            'greeting' => "Welcome back, **{$user->name}**!\n\nYou're signed in as a **Broker / Middleman** (Demand Partner).\n\n"
                ."Open demands: **{$open}** · Routed: **{$routed}**.\n\n"
                .'I can help you **create**, **route**, or **track** demand requests to Recruitment Agencies.',
            'show_role_picker' => false,
            'actions' => $this->filterActions([
                ['key' => 'dashboard', 'label' => 'Broker dashboard', 'type' => 'link', 'url' => $this->safeRoute('broker.dashboard')],
                ['key' => 'create_demand', 'label' => 'Create demand', 'type' => 'action', 'value' => 'start_broker_demand'],
                ['key' => 'list_demands', 'label' => 'My demands', 'type' => 'action', 'value' => 'list_broker_demands'],
                ['key' => 'demands_page', 'label' => 'Demands page', 'type' => 'link', 'url' => $this->safeRoute('broker.demands')],
                ['key' => 'settings', 'label' => 'Settings', 'type' => 'link', 'url' => $this->safeRoute('broker.setting')],
            ]),
            'accepts_documents' => false,
        ];
    }

    protected function forAdmin(Admin $admin): array
    {
        $pendingHandovers = AIHandoverRequest::query()->where('status', 'pending')->count();
        $newLeads = ChatLead::query()->where('status', 'new')->count();
        $recentChats = AIChatMessage::query()->where('created_at', '>=', now()->subDay())->count();

        $actions = [
            ['key' => 'ai_chats', 'label' => 'AI Chats inbox', 'type' => 'link', 'url' => $this->safeRoute('admin.ai.chat.index')],
            ['key' => 'chat_leads', 'label' => 'Chat Leads CRM', 'type' => 'link', 'url' => $this->safeRoute('admin.chatleads.index')],
            ['key' => 'handover', 'label' => 'Handover queue', 'type' => 'link', 'url' => $this->safeRoute('admin.ai.handover.index')],
        ];

        if ($admin->hasRole('superadmin') || $admin->can('company.view')) {
            $actions[] = ['key' => 'companies', 'label' => 'Companies', 'type' => 'link', 'url' => $this->safeRoute('company.index')];
        }

        return [
            'mode' => 'admin',
            'role' => 'admin',
            'label' => 'Admin',
            'admin_id' => $admin->id,
            'greeting' => "Hi **{$admin->name}** — Sophia admin assistant.\n\n"
                ."Pending handovers: **{$pendingHandovers}** · New chat leads: **{$newLeads}** · Messages (24h): **{$recentChats}**.\n\n"
                ."Ask me about **candidates**, **companies**, **jobs**, **plans**, or **all-time stats** for this month.",
            'show_role_picker' => false,
            'actions' => $this->filterActions($actions),
            'accepts_documents' => false,
        ];
    }
}

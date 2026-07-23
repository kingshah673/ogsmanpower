<?php

namespace App\Services\AI;

use App\Models\User;
use App\Services\Company\CompanyDocumentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PortalAssistantService
{
  public function __construct(
    protected SophiaContextService $context,
    protected SophiaPortalAIService $portalAi,
  ) {}

  protected function formatReply(string $reply): string
  {
    return SophiaMessageFormatter::toHtml($reply);
  }

  public function shouldHandle(Request $request): bool
  {
    if (auth('admin')->check()) {
      return true;
    }

    return authUser() instanceof User;
  }

  /**
   * @return array{reply: string, actions?: array, redirect?: string}
   */
  public function handle(Request $request, ?string $attachmentPath = null): array
  {
    if (auth('admin')->check()) {
      return $this->handleAdminMessage($request);
    }

    $user = authUser();
    if (! $user) {
      return ['reply' => 'Please log in to continue.'];
    }

    $message = strtolower(trim((string) $request->input('message', '')));
    $action = (string) $request->input('portal_action', '');

    if ($action === 'start_employer_documents' || str_contains($message, 'upload document')) {
      return $this->startEmployerDocumentFlow($user);
    }

    if ($user->role === 'agent') {
      if ($action === 'start_agent_worker' || str_contains($message, 'register worker')) {
        $worker = app(\App\Services\Chat\AgentWorkerOnboardingService::class);

        return [
          'reply' => $worker->start(),
          'actions' => $this->context->build()['actions'] ?? [],
          'agent_worker_active' => true,
          'accepts_documents' => true,
        ];
      }
    }

    if ($user->role === 'company') {
      $rawMessageEarly = trim((string) $request->input('message', ''));
      if ($rawMessageEarly !== '' && $action === '' && ! $attachmentPath) {
        $facts = $this->portalAi->employerFactsFor($user);
        $direct = SophiaEmployerReplyBuilder::build($rawMessageEarly, $facts);
        if ($direct) {
          return [
            'reply' => $this->formatReply($direct),
            'actions' => $this->context->build()['actions'] ?? [],
          ];
        }
      }

      $jobChat = app(EmployerJobPostingChatService::class);
      $rawMessage = trim((string) $request->input('message', ''));

      if ($action === 'start_job_posting_qa' && ! $jobChat->isQaActive()) {
        return [
          'reply' => $this->formatReply($jobChat->startQa()),
          'actions' => $this->context->build()['actions'] ?? [],
        ];
      }

      if ($action === 'confirm_job_posting_qa' || $jobChat->isQaActive()) {
        $qaResult = $jobChat->handleQa($action === 'confirm_job_posting_qa' ? 'confirm' : $rawMessage);
        if ($qaResult) {
          $qaResult['reply'] = $this->formatReply($qaResult['reply']);

          return $qaResult;
        }
      }

      if ($action === 'start_job_posting' && ! $attachmentPath && ! $jobChat->isActive()) {
        $ctx = $this->context->build();

        return [
          'reply' => $this->formatReply(
            "📄 **Upload a job advertisement** (PDF or clear image) using the 📎 button below.\n\n"
            ."I'll extract all positions and publish them — same as the **Post a job** page AI upload."
          ),
          'actions' => $ctx['actions'] ?? [],
        ];
      }

      if (
        $action === 'start_job_posting'
        || $action === 'confirm_job_posting'
        || $jobChat->isActive()
        || ($attachmentPath && $jobChat->looksLikeJobIntent($rawMessage !== '' ? $rawMessage : 'job upload', $attachmentPath))
        || ($rawMessage !== '' && $jobChat->looksLikeJobIntent($rawMessage))
      ) {
        $confirmMessage = $action === 'confirm_job_posting' ? 'confirm' : $rawMessage;
        $jobResult = $jobChat->handle($user, $confirmMessage, $attachmentPath);
        if ($jobResult) {
          $jobResult['reply'] = $this->formatReply($jobResult['reply']);

          return $jobResult;
        }
      }

      $verificationIntent = str_contains($message, 'document')
        || str_contains($message, 'verify')
        || str_contains($message, 'license')
        || $action === 'start_employer_documents';

      if ($attachmentPath && ! $verificationIntent) {
        $jobResult = $jobChat->handle($user, $rawMessage ?: 'job upload', $attachmentPath);
        if ($jobResult) {
          return $jobResult;
        }
      }

      if ($attachmentPath && $verificationIntent) {
        $stored = $this->storeEmployerAttachment($user, $attachmentPath);
        if ($stored) {
          return $stored;
        }
      } elseif ($attachmentPath) {
        $stored = $this->storeEmployerAttachment($user, $attachmentPath);
        if ($stored) {
          return $stored;
        }
      }
    } elseif ($user->role === 'agency') {
      return $this->handleAgencyPortal($user, $request, $message, $action, $attachmentPath);
    } elseif ($user->role === 'broker') {
      return $this->handleBrokerPortal($user, $request, $message, $action);
    } elseif ($attachmentPath && $user->role === 'candidate') {
      return [
        'reply' => "📎 I received your file.\n\n"
          ."For CV and passport uploads that auto-fill your profile, open **Settings** → upload there, "
          ."or tell me **create account** if you're new and I'll guide registration.\n\n"
          ."👉 <a href='".route('candidate.setting')."'>Open seeker settings</a>",
      ];
    }

    $rawMessage = trim((string) $request->input('message', ''));

    if ($rawMessage !== '' && $user->role === 'candidate') {
      $facts = $this->portalAi->candidateFactsFor($user);
      $direct = SophiaCandidateReplyBuilder::build($rawMessage, $facts);
      if ($direct) {
        $ctx = $this->context->build();

        return [
          'reply' => $this->formatReply($direct),
          'actions' => $ctx['actions'] ?? [],
        ];
      }
    }

    if ($rawMessage !== '' && $user->role === 'company') {
      $facts = $this->portalAi->employerFactsFor($user);
      $direct = SophiaEmployerReplyBuilder::build($rawMessage, $facts);
      if ($direct) {
        $ctx = $this->context->build();

        return [
          'reply' => $this->formatReply($direct),
          'actions' => $ctx['actions'] ?? [],
        ];
      }
    }

    if ($rawMessage !== '' && $this->portalAi->isAvailable()) {
      $aiReply = $this->portalAi->replyForPortalUser($user, $rawMessage, session()->getId());
      if ($aiReply) {
        $ctx = $this->context->build();

        return [
          'reply' => $this->formatReply($aiReply),
          'actions' => $ctx['actions'] ?? [],
        ];
      }
    }

    return match ($user->role) {
      'candidate' => $this->handleCandidate($user, $message),
      'company' => $this->handleEmployer($user, $message),
      'agency' => $this->handleAgency($user, $message),
      'agent' => $this->handleAgent($user, $message),
      'broker' => $this->handleBroker($user, $message),
      default => $this->genericPortalReply($user),
    };
  }

  protected function handleBroker(User $user, string $message): array
  {
    $ctx = $this->context->build();

    if (str_contains($message, 'demand') || str_contains($message, 'create') || str_contains($message, 'route')) {
      return [
        'reply' => $this->formatReply(
          "📋 I can help you **create** or **track** demand requests.\n\n"
          ."👉 <a href='".route('broker.demands.create')."'>Create demand</a>"
        ),
        'actions' => $ctx['actions'],
      ];
    }

    return $this->genericPortalReply($user);
  }

  /**
   * @return array{reply: string, actions?: array}
   */
  protected function handleBrokerPortal(User $user, Request $request, string $message, string $action): array
  {
    $demandChat = app(BrokerDemandChatService::class);
    $rawMessage = trim((string) $request->input('message', ''));

    if (
      $action === 'start_broker_demand'
      || $action === 'list_broker_demands'
      || $demandChat->isActive()
      || str_contains($message, 'create demand')
      || str_contains($message, 'my demands')
    ) {
      $result = $demandChat->handle(
        $user,
        $rawMessage,
        $action !== '' ? $action : (str_contains($message, 'my demand') ? 'list_broker_demands' : '')
      );
      if ($result) {
        $result['reply'] = $this->formatReply($result['reply']);

        return $result;
      }
    }

    if ($rawMessage !== '' && $this->portalAi->isAvailable()) {
      $aiReply = $this->portalAi->replyForPortalUser($user, $rawMessage, session()->getId());
      if ($aiReply) {
        return [
          'reply' => $this->formatReply($aiReply),
          'actions' => $this->context->build()['actions'] ?? [],
        ];
      }
    }

    return $this->handleBroker($user, $message);
  }

  protected function handleAgent(User $user, string $message): array
  {
    $ctx = $this->context->build();

    if (str_contains($message, 'worker') || str_contains($message, 'register') || str_contains($message, 'candidate')) {
      return [
        'reply' => $this->formatReply(
          "👷 I can register a worker from their **CV + passport** right here.\n\n"
          ."Tap **Register worker (AI)** or upload documents with 📎."
        ),
        'actions' => $ctx['actions'],
        'accepts_documents' => true,
      ];
    }

    if (str_contains($message, 'job')) {
      return [
        'reply' => "📋 View jobs assigned to you by your agency.\n\n"
          ."👉 <a href='".route('agent.jobs')."'>Assigned jobs</a>",
        'actions' => $ctx['actions'],
      ];
    }

    if (str_contains($message, 'pipeline') || str_contains($message, 'status')) {
      $stats = $ctx['worker_stats'] ?? [];

      return [
        'reply' => "📊 Your pipeline: **".($stats['workers'] ?? 0)."** workers · "
          ."**".($stats['pending'] ?? 0)."** pending · **".($stats['selected'] ?? 0)."** selected.\n\n"
          ."👉 <a href='".route('agent.pipeline')."'>Open pipeline</a>",
        'actions' => $ctx['actions'],
      ];
    }

    return $this->genericPortalReply($user);
  }

  protected function handleAdminMessage(Request $request): array
  {
    $message = trim((string) $request->input('message', ''));
    $ctx = $this->context->build();

    if ($message === '' || $request->input('portal_action')) {
      return $this->menuReply($ctx);
    }

    if ($this->portalAi->isAvailable()) {
      $admin = auth('admin')->user();
      $aiReply = $this->portalAi->replyForAdmin($admin, $message, session()->getId());

      if ($aiReply && trim(strip_tags($aiReply)) !== '') {
        return [
          'reply' => $this->formatReply($aiReply),
          'actions' => $ctx['actions'] ?? [],
        ];
      }
    }

    return $this->menuReply($ctx, $message);
  }

  /**
   * @param  array<string, mixed>  $ctx
   * @return array{reply: string, actions: array}
   */
  protected function menuReply(array $ctx, ?string $message = null): array
  {
    $reply = $ctx['greeting'] ?? "Hi! How can I help?";

    if ($message) {
      $reply .= "\n\nI couldn't reach the AI service right now. Pick a shortcut below or try again in a moment.";
    } else {
      $reply .= "\n\nAsk me anything about your dashboard, or pick a shortcut below.";
    }

    return [
      'reply' => $this->formatReply($reply),
      'actions' => $ctx['actions'] ?? [],
    ];
  }

  protected function handleCandidate(User $user, string $message): array
  {
    $candidate = $user->candidate;
    $ctx = $this->context->build();

    $facts = $this->portalAi->candidateFactsFor($user);
    $direct = SophiaCandidateReplyBuilder::build($message, $facts);
    if ($direct) {
      return [
        'reply' => $this->formatReply($direct),
        'actions' => $ctx['actions'],
      ];
    }

    if (
      str_contains($message, 'missing')
      || str_contains($message, 'complete')
      || str_contains($message, 'profile')
    ) {
      if (! $candidate) {
        return ['reply' => 'Your candidate profile is not set up yet. Please contact support.'];
      }

      $missing = $candidate->profileCompletionMissing();
      $pct = (int) $candidate->calculateProfileCompletion();

      if ($missing === []) {
        return [
          'reply' => "🎉 Your profile is **{$pct}%** complete — great job!\n\n"
            ."👉 <a href='".route('website.job')."'>Browse jobs</a>",
          'actions' => $ctx['actions'],
        ];
      }

      $list = implode("\n• ", $missing);

      return [
        'reply' => "Your profile is **{$pct}%** complete.\n\nPlease add:\n• {$list}\n\n"
          ."👉 <a href='".route('candidate.setting')."'>Open settings to complete these</a>",
        'actions' => $ctx['actions'],
      ];
    }

    if (str_contains($message, 'job') || str_contains($message, 'work')) {
      return [
        'reply' => "🔍 Browse verified overseas jobs on our job board.\n\n"
          ."👉 <a href='".route('website.job')."'>Find jobs</a>",
        'actions' => $ctx['actions'],
      ];
    }

    return $this->genericPortalReply($user);
  }

  protected function handleEmployer(User $user, string $message): array
  {
    $company = $user->company;
    $ctx = $this->context->build();

    if (! $company) {
      return [
        'reply' => 'Company profile not found. Please complete registration first.',
        'actions' => $ctx['actions'],
      ];
    }

    $facts = $this->portalAi->employerFactsFor($user);
    $direct = SophiaEmployerReplyBuilder::build($message, $facts);
    if ($direct) {
      return [
        'reply' => $this->formatReply($direct),
        'actions' => $ctx['actions'],
      ];
    }

    if (str_contains($message, 'document') || str_contains($message, 'verify') || str_contains($message, 'license')) {
      return $this->startEmployerDocumentFlow($user);
    }

    if (str_contains($message, 'job') || str_contains($message, 'post')) {
      return [
        'reply' => "📋 Post a new job from your employer dashboard.\n\n"
          ."👉 <a href='".route('company.job.create')."'>Post a job</a>",
        'actions' => $ctx['actions'],
      ];
    }

    $missing = CompanyDocumentVerificationService::missingDocumentTypes($company);
    if ($missing !== []) {
      return $this->startEmployerDocumentFlow($user);
    }

    return $this->genericPortalReply($user);
  }

  protected function handleAgency(User $user, string $message): array
  {
    $ctx = $this->context->build();

    if (str_contains($message, 'invite') || str_contains($message, 'facilitator') || str_contains($message, 'agent')) {
      return $this->startAgencyInviteFlow($user);
    }

    if (str_contains($message, 'document') || str_contains($message, 'verify') || str_contains($message, 'license')) {
      return $this->startAgencyDocumentFlow($user);
    }

    if (str_contains($message, 'job') || str_contains($message, 'post')) {
      return [
        'reply' => $this->formatReply(
          "📋 I can post jobs from a **job advertisement** upload, or guide you with Q&A.\n\n"
          ."👉 <a href='".route('agency.job.create')."'>Post a job</a>"
        ),
        'actions' => $ctx['actions'],
        'accepts_documents' => true,
      ];
    }

    return $this->genericPortalReply($user);
  }

  protected function genericPortalReply(User $user): array
  {
    $ctx = $this->context->build();

    return [
      'reply' => $ctx['greeting']."\n\nPick an option below or type your question.",
      'actions' => $ctx['actions'],
    ];
  }

  /**
   * @return array{reply: string, actions?: array}
   */
  protected function startEmployerDocumentFlow(User $user): array
  {
    $company = $user->company;
    if (! $company) {
      return ['reply' => 'Company record not found.'];
    }

    $missing = CompanyDocumentVerificationService::missingDocumentDetails($company);

    if ($missing === []) {
      return [
        'reply' => "✅ All required verification documents are uploaded.\n\n"
          ."Admin will review and approve your company.\n\n"
          ."👉 <a href='".route('company.verify.documents.index')."'>View documents</a>",
      ];
    }

    $firstKey = array_key_first($missing);
    $first = $missing[$firstKey];

    Cache::put($this->employerDocCacheKey(), $firstKey, now()->addHours(2));

    return [
      'reply' => "📄 Next document: **".($first['label'] ?? 'Document')."**\n\n"
        .($first['help'] ?? 'Upload a clear scan or photo (PDF/JPG/PNG).')
        ."\n\nAttach the file using 📎 below, or upload on the verification page.\n\n"
        ."👉 <a href='".route('company.verify.documents.index')."'>Open verify documents</a>",
      'actions' => [
        ['key' => 'verify_page', 'label' => 'Open verify documents', 'type' => 'link', 'url' => route('company.verify.documents.index')],
      ],
    ];
  }

  /**
   * @return array{reply: string}|null
   */
  protected function storeEmployerAttachment(User $user, string $attachmentPath): ?array
  {
    $company = $user->company;
    if (! $company) {
      return null;
    }

    $slug = Cache::get($this->employerDocCacheKey());
    if (! $slug) {
      $missing = CompanyDocumentVerificationService::missingDocumentDetails($company);
      $slug = $missing ? array_key_first($missing) : null;
    }

    if (! $slug) {
      return null;
    }

    try {
      $fullPath = storage_path('app/public/'.$attachmentPath);
      if (! is_file($fullPath)) {
        return ['reply' => '⚠️ Could not read the uploaded file. Please try again.'];
      }

      $uploaded = new \Illuminate\Http\UploadedFile(
        $fullPath,
        basename($fullPath),
        mime_content_type($fullPath) ?: null,
        null,
        true
      );

      CompanyDocumentVerificationService::storeDocument($company, $slug, $uploaded);

      Cache::forget($this->employerDocCacheKey());

      $stillMissing = CompanyDocumentVerificationService::missingDocumentTypes($company->fresh());

      if ($stillMissing !== []) {
        return $this->startEmployerDocumentFlow($user);
      }

      CompanyDocumentVerificationService::markSubmittedForReview($company->fresh());

      return [
        'reply' => "✅ Document saved and submitted for admin review!\n\n"
          ."👉 <a href='".route('company.dashboard')."'>Go to dashboard</a>",
      ];
    } catch (\Throwable $e) {
      \Log::error('[Sophia] employer doc upload: '.$e->getMessage());

      return ['reply' => '⚠️ Upload failed: '.$e->getMessage()];
    }
  }

  /**
   * @return array{reply: string, actions?: array, accepts_documents?: bool, redirect?: string}
   */
  protected function handleAgencyPortal(User $user, Request $request, string $message, string $action, ?string $attachmentPath): array
  {
    $rawMessage = trim((string) $request->input('message', ''));
    $jobChat = app(AgencyJobPostingChatService::class);
    $ctx = $this->context->build();

    if ($action === 'start_agency_invite' || Cache::has($this->agencyInviteCacheKey())) {
      $inviteResult = $this->handleAgencyInviteStep($user, $action, $rawMessage);
      if ($inviteResult) {
        return $inviteResult;
      }
    }

    if ($action === 'start_agency_documents') {
      return $this->startAgencyDocumentFlow($user);
    }

    if ($action === 'start_job_posting_qa' && ! $jobChat->isQaActive()) {
      return [
        'reply' => $this->formatReply($jobChat->startQa()),
        'actions' => $ctx['actions'] ?? [],
      ];
    }

    if ($action === 'confirm_job_posting_qa' || $jobChat->isQaActive()) {
      $qaResult = $jobChat->handleQa($action === 'confirm_job_posting_qa' ? 'confirm' : $rawMessage);
      if ($qaResult) {
        $qaResult['reply'] = $this->formatReply($qaResult['reply']);

        return $qaResult;
      }
    }

    if ($action === 'start_job_posting' && ! $attachmentPath && ! $jobChat->isActive()) {
      return [
        'reply' => $this->formatReply(
          "📄 **Upload a job advertisement** (PDF or clear image) using the 📎 button below.\n\n"
          ."I'll extract positions and publish them for your agency."
        ),
        'actions' => $ctx['actions'] ?? [],
        'accepts_documents' => true,
      ];
    }

    if (
      $action === 'start_job_posting'
      || $action === 'confirm_job_posting'
      || $jobChat->isActive()
      || ($attachmentPath && $jobChat->looksLikeJobIntent($rawMessage !== '' ? $rawMessage : 'job upload', $attachmentPath))
      || ($rawMessage !== '' && $jobChat->looksLikeJobIntent($rawMessage))
    ) {
      $confirmMessage = $action === 'confirm_job_posting' ? 'confirm' : $rawMessage;
      $jobResult = $jobChat->handle($user, $confirmMessage, $attachmentPath);
      if ($jobResult) {
        $jobResult['reply'] = $this->formatReply($jobResult['reply']);

        return $jobResult;
      }
    }

    $docIntent = str_contains($message, 'document')
      || str_contains($message, 'verify')
      || str_contains($message, 'license')
      || $action === 'start_agency_documents';

    if ($attachmentPath && $docIntent) {
      $stored = $this->storeAgencyAttachment($user, $attachmentPath);
      if ($stored) {
        return $stored;
      }
    }

    if ($attachmentPath) {
      $jobResult = $jobChat->handle($user, $rawMessage ?: 'job upload', $attachmentPath);
      if ($jobResult) {
        $jobResult['reply'] = $this->formatReply($jobResult['reply']);

        return $jobResult;
      }

      $stored = $this->storeAgencyAttachment($user, $attachmentPath);
      if ($stored) {
        return $stored;
      }
    }

    // Fall through to AI / keyword handlers below by returning empty sentinel — caller continues.
    // Instead re-run the shared AI path here:
    if ($rawMessage !== '' && $this->portalAi->isAvailable()) {
      $aiReply = $this->portalAi->replyForPortalUser($user, $rawMessage, session()->getId());
      if ($aiReply) {
        return [
          'reply' => $this->formatReply($aiReply),
          'actions' => $ctx['actions'] ?? [],
        ];
      }
    }

    return $this->handleAgency($user, $message);
  }

  protected function startAgencyDocumentFlow(User $user): array
  {
    $agency = $user->agency;
    if (! $agency) {
      return ['reply' => 'Agency profile not found.'];
    }

    Cache::put($this->agencyDocCacheKey(), 1, now()->addHours(2));

    return [
      'reply' => $this->formatReply(
        "📄 Upload your **agency registration / license document** with 📎 below.\n\n"
        ."Or open the verification page.\n\n"
        ."👉 <a href='".route('agency.verify.documents.index')."'>Open verify documents</a>"
      ),
      'actions' => [
        ['key' => 'verify_page', 'label' => 'Open verify documents', 'type' => 'link', 'url' => route('agency.verify.documents.index')],
      ],
      'accepts_documents' => true,
    ];
  }

  /**
   * @return array{reply: string}|null
   */
  protected function storeAgencyAttachment(User $user, string $attachmentPath): ?array
  {
    $agency = $user->agency;
    if (! $agency) {
      return null;
    }

    try {
      $fullPath = storage_path('app/public/'.$attachmentPath);
      if (! is_file($fullPath)) {
        return ['reply' => '⚠️ Could not read the uploaded file. Please try again.'];
      }

      $agency->addMedia($fullPath)->toMediaCollection('document');
      Cache::forget($this->agencyDocCacheKey());

      return [
        'reply' => $this->formatReply(
          "✅ Document uploaded for agency verification.\n\n"
          ."👉 <a href='".route('agency.verify.documents.index')."'>View documents</a>"
        ),
      ];
    } catch (\Throwable $e) {
      \Log::error('[Sophia] agency doc upload: '.$e->getMessage());

      return ['reply' => '⚠️ Upload failed: '.$e->getMessage()];
    }
  }

  protected function startAgencyInviteFlow(User $user): array
  {
    Cache::put($this->agencyInviteCacheKey(), ['step' => 'name'], now()->addHours(2));

    return [
      'reply' => $this->formatReply(
        "👥 **Invite an Agent / Facilitator**\n\n"
        ."**Step 1:** What is their **full name**?"
      ),
      'actions' => $this->context->build()['actions'] ?? [],
    ];
  }

  /**
   * @return array{reply: string, actions?: array}|null
   */
  protected function handleAgencyInviteStep(User $user, string $action, string $rawMessage): ?array
  {
    if ($action === 'start_agency_invite') {
      return $this->startAgencyInviteFlow($user);
    }

    $state = Cache::get($this->agencyInviteCacheKey());
    if (! is_array($state)) {
      return null;
    }

    $lower = strtolower(trim($rawMessage));
    if (in_array($lower, ['cancel', 'stop'], true)) {
      Cache::forget($this->agencyInviteCacheKey());

      return [
        'reply' => '🔙 Invite cancelled.',
        'actions' => $this->context->build()['actions'] ?? [],
      ];
    }

    $step = $state['step'] ?? 'name';

    if ($step === 'name') {
      if (mb_strlen(trim($rawMessage)) < 2) {
        return ['reply' => 'Please type the Agent / Facilitator **full name**.'];
      }
      $state['name'] = trim($rawMessage);
      $state['step'] = 'email';
      Cache::put($this->agencyInviteCacheKey(), $state, now()->addHours(2));

      return ['reply' => $this->formatReply('**Step 2:** What is their **email address**?')];
    }

    if ($step === 'email') {
      if (! filter_var(trim($rawMessage), FILTER_VALIDATE_EMAIL)) {
        return ['reply' => 'Please type a valid **email address**.'];
      }

      $email = strtolower(trim($rawMessage));
      $name = $state['name'] ?? 'Agent';

      $existing = \App\Models\AgentInvite::where('agency_user_id', $user->id)
        ->where('agent_email', $email)
        ->whereNull('accepted_at')
        ->where('expires_at', '>', now())
        ->first();

      if ($existing) {
        Cache::forget($this->agencyInviteCacheKey());

        return ['reply' => 'ℹ️ A pending invite for that email already exists.'];
      }

      $token = \Illuminate\Support\Str::random(64);
      \App\Models\AgentInvite::create([
        'agency_user_id' => $user->id,
        'agent_name' => $name,
        'agent_email' => $email,
        'token' => $token,
        'expires_at' => now()->addDays(7),
      ]);

      $link = route('agency.agent.invite.accept', $token);
      $agencyName = $user->name;

      try {
        \Illuminate\Support\Facades\Mail::raw(
          "Hi {$name},\n\n"
          ."{$agencyName} has invited you to join their recruitment team on Career Workforce as an Agent / Facilitator.\n\n"
          ."Click the link below to accept and create your account:\n\n"
          ."{$link}\n\n"
          ."This link expires in 7 days.",
          function ($mail) use ($email, $agencyName) {
            $mail->to($email)->subject("You're invited to join {$agencyName} on Career Workforce");
          }
        );
      } catch (\Throwable $e) {
        \Log::warning('[Sophia] agency invite mail: '.$e->getMessage());
      }

      Cache::forget($this->agencyInviteCacheKey());

      return [
        'reply' => $this->formatReply(
          "✅ Invitation sent to **{$name}** (*{$email}*).\n\n"
          ."They can also register via Sophia with the invite link.\n\n"
          ."👉 <a href='".route('agency.my.agents')."'>Manage Agent / Facilitators</a>"
        ),
        'actions' => $this->context->build()['actions'] ?? [],
      ];
    }

    return null;
  }

  protected function agencyDocCacheKey(): string
  {
    return 'sophia_agency_doc_'.session()->getId();
  }

  protected function agencyInviteCacheKey(): string
  {
    return 'sophia_agency_invite_'.session()->getId();
  }

  protected function employerDocCacheKey(): string
  {
    return 'sophia_employer_doc_'.session()->getId();
  }
}

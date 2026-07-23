<?php
namespace App\Http\Controllers\Website;

use App\Models\VisaCase;
use App\Services\VisaCaseService;
use Illuminate\Http\Request;
use App\Models\VisaDocument;
use App\Models\CaseTask;
use Illuminate\Support\Facades\DB;
use App\Models\AppliedJob;
use App\Http\Controllers\Controller; // ✅ MUST ADD
use App\Services\WorkflowService;



class VisaCaseController extends Controller
{
    /* ===================== LAYOUT ===================== */

    private function getLayout()
{
    $role = auth()->user()->role ?? null;

    switch ($role) {
        case 'company':
            return 'components.website.company.layout.app';

        case 'agency':
            return 'components.website.agency.layout.app';

        case 'agent':
            return 'components.website.agent.layout.app';

        case 'candidate':
            return 'components.website.candidate.layout.app';

        default:
            return 'components.website.layout.app'; // ✅ ALWAYS RETURN
    }
}

    protected function role()
    {
        return auth()->user()->role; // adjust if using spatie
    }

    /**
     * LIST CASES (ROLE BASED)
     */
    public function index()
{
    $user = auth()->user();
    $role = $this->role();

    $query = \App\Models\VisaCase::query();

    if ($role == 'company') {
        $query->where('company_id', $user->company_id);
    } elseif ($role == 'agency') {
        $query->where('agency_id', $user->agency_id);
    } elseif ($role == 'agent') {
        $query->where('agent_id', $user->id);
    } elseif ($role == 'candidate') {
        $query->where('candidate_id', $user->id);
    }
    
    
    $cases = $query->latest()->get();

    $stages = WorkflowService::stages();
    $layout = $this->getLayout();
    return view('frontend.pages.visa.index', compact('cases', 'stages', 'role', 'layout'));
}

    public function dashboard()
{
    $layout = $this->getLayout();
    $role   = $this->role();

    $user = auth()->user();
    $companyId = $user->company_id ?? null;

    /*
    |--------------------------------------------------------------------------
    | GET VISA CASES (ROLE BASED)
    |--------------------------------------------------------------------------
    */

    $query = VisaCase::query();

    if ($role == 'company') {
        $query->where('company_id', $companyId);
    } elseif ($role == 'agency') {
        $query->where('agency_id', $user->agency_id);
    } elseif ($role == 'agent') {
        $query->where('agent_id', $user->id);
    } elseif ($role == 'candidate') {
        $query->where('candidate_id', $user->id);
    }

    $cases = $query->get();

    /*
    |--------------------------------------------------------------------------
    | STATS
    |--------------------------------------------------------------------------
    */

    $totalCases     = $cases->count();
    $completedCases = $cases->where('current_stage_key', 'deployed')->count();
    $inProgress     = $cases->where('current_stage_key', '!=', 'deployed')->count();

    $pendingTasks = CaseTask::where('assigned_to', $user->id)
        ->where('is_completed', 0)
        ->count();

    /*
    |--------------------------------------------------------------------------
    | GET APPLIED CANDIDATES (FOR COMPANY CREATE CASE)
    |--------------------------------------------------------------------------
    */

    $applications = [];

    if ($role == 'company' && $companyId) {

        $applications = AppliedJob::with(['candidate.user', 'job'])
            ->where('company_id', $companyId)
            ->get()
            ->unique('candidate_id')
            ->values(); // reset index
    }

    /*
    |--------------------------------------------------------------------------
    | RETURN VIEW
    |--------------------------------------------------------------------------
    */

    return view('frontend.pages.visa.dashboard', compact(
        'layout',
        'role',
        'totalCases',
        'completedCases',
        'inProgress',
        'pendingTasks',
        'applications'
    ));
}

public function show($id)
{
    $case = \App\Models\VisaCase::with(['documents', 'tasks'])->findOrFail($id);

    $stages = WorkflowService::stages();

    return view('frontend.pages.visa.show', compact('case', 'stages'));
}
public function modal($id)
{
    $case = \App\Models\VisaCase::with([
        'documents',
        'tasks',
        'logs.user' // 🔥 IMPORTANT
    ])->findOrFail($id);

    return view('frontend.pages.visa.partials.modal', compact('case'));
}

    /**
     * CREATE CASE (ONLY ADMIN / COMPANY)
     */
    public function store(Request $request)
    {
        $role = $this->role();

        if (!in_array($role, ['admin', 'company'])) {
            abort(403);
        }

        VisaCaseService::createCase([
            'candidate_id' => $request->candidate_id,
            'company_id' => $request->company_id,
            'agency_id' => $request->agency_id,
            'agent_id' => $request->agent_id,
            'created_by' => auth()->id(),
            'country' => $request->country,
            'visa_type' => $request->visa_type,
            'current_stage_key' => 'job_posted'
        ]);

        return back()->with('success', 'Case Created');
    }

    /**
     * MOVE STAGE (ALL ROLES WITH CONTROL)
     */
    public function moveStage($id)
    {
        $case = VisaCase::findOrFail($id);
        $role = $this->role();

        // CONTROL WHO CAN MOVE
        if (!$this->canMoveStage($case, $role)) {
            abort(403);
        }

        VisaCaseService::moveToNextStage($case);

        return back()->with('success', 'Stage Updated');
    }

    /**
     * ROLE PERMISSION LOGIC
     */
    private function canMoveStage($case, $role)
    {
        $stage = $case->current_stage_key;

        return match ($stage) {
            'job_posted' => in_array($role, ['admin', 'company']),
            'agency_assigned' => in_array($role, ['admin']),
            'candidates_applied' => in_array($role, ['agency', 'agent']),
            'interview' => in_array($role, ['company']),
            'documents' => in_array($role, ['agent', 'candidate']),
            'visa' => in_array($role, ['admin']),
            default => false,
        };
    }
    public function uploadDocument(Request $request)
{
    $file = $request->file('file')->store('visa_docs');

    $doc = VisaDocument::create([
        'case_id' => $request->case_id,
        'document_name' => $request->document_name,
        'file_path' => $file,
        'uploaded_by' => auth()->id(),
    ]);

    // Create verification task for agent/admin
    $case = \App\Models\VisaCase::find($request->case_id);

    CaseTask::create([
        'case_id' => $case->id,
        'assigned_to' => $case->agent_id ?? $case->created_by,
        'role' => 'agent',
        'title' => 'Verify Document: '.$request->document_name,
    ]);

    return back()->with('success', 'Document Uploaded');
}
public function verifyDocument(Request $request, $id)
{
    $doc = \App\Models\VisaDocument::findOrFail($id);

    $doc->update([
        'status' => $request->status,
        'remarks' => $request->remarks,
        'verified_by' => auth()->id(),
    ]);

    // COMPLETE TASK
    \App\Models\CaseTask::where('case_id', $doc->case_id)
        ->where('title', 'like', '%'.$doc->document_name.'%')
        ->update([
            'is_completed' => 1,
            'completed_at' => now()
        ]);

    // 🔥 AUTO WORKFLOW TRIGGER
    VisaCaseService::autoMoveIfReady($doc->case);

    return back()->with('success', 'Document Verified');
}
public function completeTask($id)
{
    $task = \App\Services\TaskService::complete($id);

    // 🔥 AUTO WORKFLOW TRIGGER
    \App\Services\VisaCaseService::autoMoveIfReady($task->case);

    return back()->with('success', 'Task Completed');
}
public function stats()
{
    $user = auth()->user();
    $role = $this->role();

    $query = \App\Models\VisaCase::query();

    if ($role == 'company') {
        $query->where('company_id', $user->company_id);
    } elseif ($role == 'agency') {
        $query->where('agency_id', $user->agency_id);
    } elseif ($role == 'agent') {
        $query->where('agent_id', $user->id);
    } elseif ($role == 'candidate') {
        $query->where('candidate_id', $user->id);
    }

    $totalCases = $query->count();

    $completedCases = (clone $query)
        ->where('current_stage_key', 'deployed')
        ->count();

    $inProgress = (clone $query)
        ->where('current_stage_key', '!=', 'deployed')
        ->count();

    $pendingTasks = CaseTask::where('assigned_to', auth()->id())
        ->where('is_completed', 0)
        ->count();

    return response()->json([
        'totalCases' => $totalCases,
        'completedCases' => $completedCases,
        'inProgress' => $inProgress,
        'pendingTasks' => $pendingTasks
    ]);
}
}
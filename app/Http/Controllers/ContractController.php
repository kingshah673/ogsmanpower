<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\ContractSignature;
use App\Models\ContractOtp;
use App\Models\ContractLog;
use App\Models\ContractTemplate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractController extends Controller
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
            return 'components.website.layout.app';
    }
}

    /* ===================== LIST ===================== */

    public function index()
    {
        $user = auth()->user();

        $contracts = Contract::with('parties.user')
            ->where(function ($q) use ($user) {
                $q->whereHas('parties', fn ($p) => $p->where('user_id', $user->id))
                    ->orWhere('created_by', $user->id);
            })
            ->latest()
            ->get();

        return view('frontend.pages.contracts.index', [
            'contracts' => $contracts,
            'layout' => $this->getLayout()
        ]);
    }

    /* ===================== SHOW ===================== */

    public function show($id)
    {
        $contract = Contract::with('parties.user','logs')->findOrFail($id);

        // security check
        $allowed = $contract->parties->pluck('user_id')->contains(auth()->id());

        if (!$allowed && $contract->created_by != auth()->id()) {
            abort(403);
        }

        return view('frontend.pages.contracts.show', [
            'contract' => $contract,
            'layout' => $this->getLayout()
        ]);
    }

    /* ===================== CREATE ===================== */

    public function create(Request $request)
{
    abort_if((auth()->user()->role ?? null) === 'candidate', 403, 'Candidates cannot create contracts.');

    $templates = \App\Models\ContractTemplate::all();

    $candidates = \App\Models\User::where('role','candidate')->get();
    $companies  = \App\Models\User::where('role','company')->get();

    $agencies = \App\Models\User::where('role', 'agency')->get();

    $role = auth()->user()->role ?? null;
    $selectedAgencyId = $request->integer('agency_id') ?: null;
    $selectedCompanyId = $request->integer('company_id') ?: null;

    // Default the initiator's own party so they don't have to find themselves in the list
    if (! $selectedAgencyId && $role === 'agency') {
        $selectedAgencyId = auth()->id();
    }
    if (! $selectedCompanyId && $role === 'company') {
        $selectedCompanyId = auth()->id();
    }

    return view('frontend.pages.contracts.create', [
        'templates'  => $templates,
        'candidates' => $candidates,
        'companies'  => $companies,
        'agencies'   => $agencies,
        'layout'     => $this->getLayout(), // ✅ THIS WAS MISSING
        'selectedCandidateId' => $request->integer('candidate_id') ?: null,
        'selectedCompanyId' => $selectedCompanyId,
        'selectedAgencyId' => $selectedAgencyId,
    ]);
}

/*==================store method====================*/
public function store(Request $request)
{
    abort_if((auth()->user()->role ?? null) === 'candidate', 403, 'Candidates cannot create contracts.');

    // Validate
    $request->validate([
        'title' => 'required|string|max:255',
        'template_id' => 'required|exists:contract_templates,id',
    ]);

    // Get template
    $template = \App\Models\ContractTemplate::findOrFail($request->template_id);

    // Create contract
    $contract = \App\Models\Contract::create([
        'title' => $request->title,
        'content' => $template->content,
        'created_by' => auth()->id(),
        'status' => 'draft',
    ]);

    // Attach parties (example)
    if ($request->candidate_id) {
        \App\Models\ContractParty::create([
            'contract_id' => $contract->id,
            'user_id' => $request->candidate_id,
            'role' => 'candidate',
            'status' => 'pending',
        ]);
    }

    if ($request->company_id) {
        \App\Models\ContractParty::create([
            'contract_id' => $contract->id,
            'user_id' => $request->company_id,
            'role' => 'company',
            'status' => 'pending',
        ]);
    }

    if ($request->agency_id) {
        \App\Models\ContractParty::create([
            'contract_id' => $contract->id,
            'user_id' => $request->agency_id,
            'role' => 'agency',
            'status' => 'pending',
        ]);
    }

    return redirect()->route('contracts.index')
        ->with('success', 'Contract created successfully');
}

    /* ===================== DELETE ===================== */

    public function destroy($id)
    {
        $contract = Contract::findOrFail($id);

        if ($contract->created_by != auth()->id()) {
            abort(403);
        }

        if ($contract->status !== 'draft') {
            return back()->with('error', 'Only draft contracts can be deleted.');
        }

        $contract->parties()->delete();
        $contract->signatures()->delete();
        $contract->logs()->delete();
        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Contract deleted.');
    }

    /* ===================== SEND ===================== */

    public function send($id)
    {
        $contract = Contract::findOrFail($id);

        if ($contract->created_by != auth()->id()) {
            abort(403);
        }

        $contract->update(['status'=>'sent']);

        ContractLog::create([
            'contract_id'=>$id,
            'user_id'=>auth()->id(),
            'action'=>'sent',
            'description'=>'Contract sent'
        ]);

        return back()->with('success','Contract sent');
    }

    /* ===================== ACCEPT ===================== */

    public function accept($id)
    {
        ContractParty::where([
            'contract_id'=>$id,
            'user_id'=>auth()->id()
        ])->update(['status'=>'accepted']);

        ContractLog::create([
            'contract_id'=>$id,
            'user_id'=>auth()->id(),
            'action'=>'accepted'
        ]);

        return back()->with('success','Accepted');
    }

    /* ===================== OTP ===================== */

    public function sendOtp($id)
    {
        $otp = rand(100000,999999);

        ContractOtp::create([
            'contract_id'=>$id,
            'user_id'=>auth()->id(),
            'otp'=>$otp,
            'expires_at'=>now()->addMinutes(5)
        ]);

        return response()->json(['message'=>'OTP sent']);
    }

    /* ===================== SIGN ===================== */

    public function verifyOtp(Request $request, $id)
    {
        $otp = ContractOtp::where([
            'contract_id'=>$id,
            'user_id'=>auth()->id(),
            'otp'=>$request->otp
        ])->latest()->first();

        if(!$otp || now()->gt($otp->expires_at)){
            return back()->with('error','Invalid or expired OTP');
        }

        ContractSignature::create([
            'contract_id'=>$id,
            'user_id'=>auth()->id(),
            'signature'=>$request->signature,
            'signed_at'=>now()
        ]);

        ContractParty::where([
            'contract_id'=>$id,
            'user_id'=>auth()->id()
        ])->update([
            'status'=>'signed',
            'signed_at'=>now()
        ]);

        ContractLog::create([
            'contract_id'=>$id,
            'user_id'=>auth()->id(),
            'action'=>'signed'
        ]);

        // auto complete contract
        $remaining = ContractParty::where('contract_id',$id)
            ->where('status','!=','signed')
            ->count();

        if($remaining == 0){
            Contract::where('id',$id)->update(['status'=>'signed']);
        }

        return back()->with('success','Contract signed successfully');
    }

    /* ===================== PDF ===================== */

    public function downloadPdf($id)
    {
        $contract = Contract::with('parties.user','signatures')->findOrFail($id);

        $pdf = Pdf::loadView('frontend.pages.contracts.pdf', compact('contract'));

        return $pdf->download('contract-'.$contract->id.'.pdf');
    }

    /* ===================== PREVIEW ===================== */

    public function preview(Request $request)
    {
        $template = ContractTemplate::findOrFail($request->template_id);

        $candidate = User::findOrFail($request->candidate_id);
        $company = User::findOrFail($request->company_id);

        $data = [
            'candidate_name' => $candidate->name,
            'company_name' => $company->name,
            'job_title' => 'Worker',
            'salary' => '5000',
            'start_date' => now()->format('d M Y')
        ];

        $content = $template->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        return view('frontend.pages.contracts.preview', compact(
            'content','template','candidate','company'
        ));
    }
}
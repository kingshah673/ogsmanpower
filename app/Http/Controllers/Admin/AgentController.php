<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Contract;
use App\Models\ContractAgreement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        abort_if(! userCan('agent.view'), 403);

        try {
            $query = User::query()
                ->where('role', 'agent')
                ->with(['parentAgencyUser:id,name,email'])
                ->withCount('candidates');

            if ($request->filled('ev_status')) {
                if ($request->ev_status === 'true') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }

            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('email', 'LIKE', "%{$keyword}%")
                        ->orWhere('username', 'LIKE', "%{$keyword}%");
                });
            }

            $request->sort_by === 'oldest'
                ? $query->oldest()
                : $query->latest();

            $agents = $query->paginate(15)->withQueryString();
            $agencies = User::query()->where('role', 'agency')->orderBy('name')->get(['id', 'name']);

            return view('backend.agent.index', compact('agents', 'agencies'));
        } catch (\Exception $e) {
            flashError($e->getMessage());

            return back();
        }
    }

    public function create()
    {
        abort_if(! userCan('agent.create'), 403);

        $agencies = User::query()->where('role', 'agency')->orderBy('name')->get(['id', 'name', 'email']);

        return view('backend.agent.create', compact('agencies'));
    }

    public function store(Request $request)
    {
        abort_if(! userCan('agent.create'), 403);

        try {
            $data = $request->validate([
                'name' => 'required|string|max:150',
                'email' => 'required|email|unique:users,email',
                'username' => 'nullable|string|max:100|unique:users,username',
                'password' => 'required|string|min:6',
                'agency_id' => [
                    'nullable',
                    Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'agency')),
                ],
                'status' => 'nullable|boolean',
            ]);

            $username = $data['username'] ?? Str::slug($data['name']).'_'.Str::lower(Str::random(4));

            User::create([
                'name' => $data['name'],
                'username' => $username,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'agent',
                'agency_id' => $data['agency_id'] ?? null,
                'status' => $request->boolean('status', true) ? 1 : 0,
                'email_verified_at' => now(),
                'is_otp_verified' => 1,
            ]);

            flashSuccess('Agent / Facilitator created successfully');

            return redirect()->route('agent.index');
        } catch (\Exception $e) {
            flashError($e->getMessage());

            return back()->withInput();
        }
    }

    public function show($id)
    {
        abort_if(! userCan('agent.view'), 403);

        try {
            $agent = User::query()
                ->where('role', 'agent')
                ->with(['parentAgencyUser'])
                ->withCount('candidates')
                ->findOrFail($id);

            $workers = Candidate::query()
                ->where('agent_id', $agent->id)
                ->latest()
                ->take(10)
                ->get();

            return view('backend.agent.show', compact('agent', 'workers'));
        } catch (\Exception $e) {
            flashError($e->getMessage());

            return back();
        }
    }

    public function edit($id)
    {
        abort_if(! userCan('agent.update'), 403);

        $agent = User::query()->where('role', 'agent')->findOrFail($id);
        $agencies = User::query()->where('role', 'agency')->orderBy('name')->get(['id', 'name', 'email']);

        return view('backend.agent.edit', compact('agent', 'agencies'));
    }

    public function update(Request $request, $id)
    {
        abort_if(! userCan('agent.update'), 403);

        try {
            $agent = User::query()->where('role', 'agent')->findOrFail($id);

            $data = $request->validate([
                'name' => 'required|string|max:150',
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($agent->id)],
                'username' => ['nullable', 'string', 'max:100', Rule::unique('users', 'username')->ignore($agent->id)],
                'password' => 'nullable|string|min:6',
                'agency_id' => [
                    'nullable',
                    Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'agency')),
                ],
                'status' => 'nullable|boolean',
            ]);

            $agent->name = $data['name'];
            $agent->email = $data['email'];
            $agent->username = $data['username'] ?: $agent->username;
            $agent->agency_id = $data['agency_id'] ?? null;
            $agent->status = $request->boolean('status', (bool) $agent->status) ? 1 : 0;

            if (! empty($data['password'])) {
                $agent->password = Hash::make($data['password']);
            }

            $agent->save();

            flashSuccess('Agent / Facilitator updated successfully');

            return redirect()->route('agent.index');
        } catch (\Exception $e) {
            flashError($e->getMessage());

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        abort_if(! userCan('agent.delete'), 403);

        try {
            $agent = User::query()->where('role', 'agent')->findOrFail($id);
            $agent->delete();

            flashSuccess('Agent / Facilitator deleted successfully');

            return redirect()->route('agent.index');
        } catch (\Exception $e) {
            flashError($e->getMessage());

            return back();
        }
    }

    public function status(Request $request)
    {
        abort_if(! userCan('agent.update'), 403);

        try {
            $user = User::query()->where('role', 'agent')->findOrFail($request->id);
            $user->status = (int) $request->status;
            $user->save();

            return responseSuccess('Agent / Facilitator status updated');
        } catch (\Exception $e) {
            return responseError($e->getMessage());
        }
    }

    public function statusChange(Request $request)
    {
        return $this->status($request);
    }

    public function verificationChange(Request $request)
    {
        abort_if(! userCan('agent.update'), 403);

        try {
            $user = User::query()->where('role', 'agent')->findOrFail($request->id);
            $user->email_verified_at = $request->status ? now() : null;
            $user->save();

            return responseSuccess($request->status
                ? __('email_verified_successfully')
                : __('email_unverified_successfully'));
        } catch (\Exception $e) {
            return responseError($e->getMessage());
        }
    }

    public function candidates($id)
    {
        abort_if(! userCan('agent.view'), 403);

        $agent = User::query()->where('role', 'agent')->findOrFail($id);
        $candidates = Candidate::query()
            ->where('agent_id', $id)
            ->latest()
            ->paginate(20);

        return view('backend.agent.candidates', compact('agent', 'candidates'));
    }

    public function contractForm()
    {
        $contract = Contract::first();

        return view('backend.agent.contract', compact('contract'));
    }

    public function saveAgreement(Request $request)
    {
        $request->validate([
            'accept_agreement' => 'required|accepted',
        ]);

        $contract = new ContractAgreement;
        $contract->admin_id = auth()->id();
        $contract->contract_content = $request->contract_content ?? '';
        $contract->date_signed = now();
        $contract->signature = auth()->user()->name;
        $contract->is_contract_submitted = 1;
        $contract->save();

        return back()->with('success', 'Agreement saved');
    }

    public function approvedContract($id)
    {
        $contract = ContractAgreement::where('admin_id', $id)->first();

        if ($contract) {
            $contract->is_approved = 1;
            $contract->save();

            return back()->with('success', 'Contract Approved');
        }

        return back()->with('error', 'Not Found');
    }

    public function downloadAgreement()
    {
        $contract = Contract::first();
        $pdf = Pdf::loadView('backend.agent.contract-pdf', compact('contract'));

        return $pdf->download('Contract-Agreement.pdf');
    }
}

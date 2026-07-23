<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrokerDemand;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrokerDemandController extends Controller
{
    public function index(Request $request)
    {
        abort_if(! userCan('broker.view'), 403);

        $query = BrokerDemand::query()
            ->with(['broker.user:id,name,email', 'routedAgencyUser:id,name,email']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('country', 'LIKE', "%{$keyword}%");
            });
        }

        $request->sort_by === 'oldest' ? $query->oldest() : $query->latest();

        $demands = $query->paginate(20)->withQueryString();
        $agencies = User::query()->where('role', 'agency')->where('status', 1)->orderBy('name')->get(['id', 'name']);

        return view('backend.broker.demands.index', compact('demands', 'agencies'));
    }

    public function show($id)
    {
        abort_if(! userCan('broker.view'), 403);

        $demand = BrokerDemand::query()
            ->with(['broker.user', 'routedAgencyUser'])
            ->findOrFail($id);

        $agencies = User::query()->where('role', 'agency')->where('status', 1)->orderBy('name')->get(['id', 'name']);

        return view('backend.broker.demands.show', compact('demand', 'agencies'));
    }

    public function update(Request $request, $id)
    {
        abort_if(! userCan('broker.update'), 403);

        $demand = BrokerDemand::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'open', 'routed', 'closed'])],
            'routed_agency_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'agency')),
            ],
        ]);

        if ($data['status'] === 'routed' && empty($data['routed_agency_user_id']) && ! $demand->routed_agency_user_id) {
            return back()->with('error', 'Select a Recruitment Agency to route this demand.');
        }

        $agencyId = $data['routed_agency_user_id'] ?? $demand->routed_agency_user_id;

        if ($data['status'] === 'routed') {
            $demand->routed_agency_user_id = $agencyId;
            $demand->routed_at = $demand->routed_at ?? now();
        } elseif (! empty($data['routed_agency_user_id'])) {
            $demand->routed_agency_user_id = $data['routed_agency_user_id'];
            $demand->routed_at = now();
        }

        $demand->status = $data['status'];
        $demand->save();

        flashSuccess('Demand updated successfully');

        return back();
    }
}

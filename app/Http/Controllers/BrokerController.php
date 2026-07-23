<?php

namespace App\Http\Controllers;

use App\Models\BrokerDemand;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrokerController extends Controller
{
    public function dashboard()
    {
        $user = authUser();
        $broker = $user->broker;
        $demands = $broker
            ? $broker->demands()->latest()->take(10)->get()
            : collect();

        $stats = [
            'open' => $broker ? $broker->demands()->where('status', 'open')->count() : 0,
            'routed' => $broker ? $broker->demands()->where('status', 'routed')->count() : 0,
            'closed' => $broker ? $broker->demands()->where('status', 'closed')->count() : 0,
            'total' => $broker ? $broker->demands()->count() : 0,
        ];

        return view('frontend.pages.broker.dashboard', compact('broker', 'demands', 'stats'));
    }

    public function demands()
    {
        $broker = authUser()->broker;
        abort_unless($broker, 404);

        $demands = $broker->demands()->with('routedAgencyUser')->latest()->paginate(20);

        return view('frontend.pages.broker.demands', compact('demands', 'broker'));
    }

    public function createDemand()
    {
        $broker = authUser()->broker;
        abort_unless($broker, 404);

        $agencies = User::query()
            ->where('role', 'agency')
            ->where('status', 1)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'email']);

        return view('frontend.pages.broker.demand-create', compact('broker', 'agencies'));
    }

    public function storeDemand(Request $request)
    {
        $broker = authUser()->broker;
        abort_unless($broker, 404);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'country' => 'nullable|string|max:100',
            'vacancies' => 'nullable|integer|min:1|max:10000',
            'salary_note' => 'nullable|string|max:200',
            'routed_agency_user_id' => 'nullable|exists:users,id',
        ]);

        $agencyId = $data['routed_agency_user_id'] ?? null;
        if ($agencyId) {
            $agency = User::where('id', $agencyId)->where('role', 'agency')->first();
            if (! $agency) {
                return back()->withInput()->with('error', 'Please select a valid Recruitment Agency.');
            }
        }

        $demand = $broker->demands()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'country' => $data['country'] ?? null,
            'vacancies' => $data['vacancies'] ?? 1,
            'salary_note' => $data['salary_note'] ?? null,
            'status' => $agencyId ? 'routed' : 'open',
            'routed_agency_user_id' => $agencyId,
            'routed_at' => $agencyId ? now() : null,
        ]);

        return redirect()
            ->route('broker.demands')
            ->with('success', $agencyId
                ? 'Demand created and routed to the selected agency.'
                : 'Demand created. You can route it to an agency anytime.');
    }

    public function routeDemand(Request $request, int $id)
    {
        $broker = authUser()->broker;
        abort_unless($broker, 404);

        $demand = $broker->demands()->where('id', $id)->firstOrFail();

        $data = $request->validate([
            'routed_agency_user_id' => 'required|exists:users,id',
        ]);

        $agency = User::where('id', $data['routed_agency_user_id'])->where('role', 'agency')->first();
        if (! $agency) {
            return back()->with('error', 'Please select a valid Recruitment Agency.');
        }

        $demand->update([
            'routed_agency_user_id' => $agency->id,
            'status' => 'routed',
            'routed_at' => now(),
        ]);

        return back()->with('success', 'Demand routed to '.$agency->name.'.');
    }

    public function setting()
    {
        $broker = authUser()->broker;

        return view('frontend.pages.broker.setting', compact('broker'));
    }

    public function settingUpdate(Request $request)
    {
        $user = authUser();
        $broker = $user->broker;
        abort_unless($broker, 404);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'organization_name' => 'nullable|string|max:200',
            'license_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:40',
            'country' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:2000',
        ]);

        $user->update(['name' => $data['name']]);
        $broker->update([
            'organization_name' => $data['organization_name'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? null,
            'bio' => $data['bio'] ?? null,
            'profile_completion' => true,
        ]);

        return back()->with('success', 'Profile updated.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrokerController extends Controller
{
    public function index(Request $request)
    {
        abort_if(! userCan('broker.view'), 403);

        $query = User::query()
            ->where('role', 'broker')
            ->with(['broker' => fn ($q) => $q->withCount('demands')]);

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
                    ->orWhere('email', 'LIKE', "%{$keyword}%");
            });
        }

        $request->sort_by === 'oldest' ? $query->oldest() : $query->latest();

        $brokers = $query->paginate(15)->withQueryString();

        return view('backend.broker.index', compact('brokers'));
    }

    public function create()
    {
        abort_if(! userCan('broker.create'), 403);

        return view('backend.broker.create');
    }

    public function store(Request $request)
    {
        abort_if(! userCan('broker.create'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'username' => 'nullable|string|max:100|unique:users,username',
            'password' => 'required|string|min:6',
            'organization_name' => 'nullable|string|max:200',
            'license_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:40',
            'country' => 'nullable|string|max:100',
            'status' => 'nullable|boolean',
        ]);

        $username = $data['username'] ?? Str::slug($data['name']).'_'.Str::lower(Str::random(4));

        $user = User::create([
            'name' => $data['name'],
            'username' => $username,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'broker',
            'status' => $request->boolean('status', true) ? 1 : 0,
            'email_verified_at' => now(),
            'is_otp_verified' => 1,
        ]);

        $broker = $user->broker ?? Broker::firstOrCreate(['user_id' => $user->id]);
        $broker->update([
            'organization_name' => $data['organization_name'] ?? $data['name'],
            'license_number' => $data['license_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? null,
        ]);

        flashSuccess('Broker / Middleman created successfully');

        return redirect()->route('broker.index');
    }

    public function show($id)
    {
        abort_if(! userCan('broker.view'), 403);

        $user = User::query()
            ->where('role', 'broker')
            ->with(['broker.demands' => fn ($q) => $q->latest()->take(10)])
            ->findOrFail($id);

        $broker = $user->broker;

        return view('backend.broker.show', compact('user', 'broker'));
    }

    public function edit($id)
    {
        abort_if(! userCan('broker.update'), 403);

        $user = User::query()->where('role', 'broker')->with('broker')->findOrFail($id);

        return view('backend.broker.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        abort_if(! userCan('broker.update'), 403);

        $user = User::query()->where('role', 'broker')->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'organization_name' => 'nullable|string|max:200',
            'license_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:40',
            'country' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->username = $data['username'] ?: $user->username;
        $user->status = $request->boolean('status', (bool) $user->status) ? 1 : 0;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $broker = $user->broker ?? Broker::firstOrCreate(['user_id' => $user->id]);
        $broker->update([
            'organization_name' => $data['organization_name'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? null,
            'bio' => $data['bio'] ?? null,
        ]);

        flashSuccess('Broker / Middleman updated successfully');

        return redirect()->route('broker.index');
    }

    public function destroy($id)
    {
        abort_if(! userCan('broker.delete'), 403);

        $user = User::query()->where('role', 'broker')->findOrFail($id);
        $user->delete();

        flashSuccess('Broker / Middleman deleted successfully');

        return redirect()->route('broker.index');
    }

    public function statusChange(Request $request)
    {
        abort_if(! userCan('broker.update'), 403);

        $user = User::query()->where('role', 'broker')->findOrFail($request->id);
        $user->update(['status' => (int) $request->status]);

        return responseSuccess((int) $request->status === 1
            ? 'Broker activated successfully'
            : 'Broker deactivated successfully');
    }

    public function verificationChange(Request $request)
    {
        abort_if(! userCan('broker.update'), 403);

        $user = User::query()->where('role', 'broker')->findOrFail($request->id);
        $user->update(['email_verified_at' => $request->status ? now() : null]);

        return responseSuccess($request->status
            ? __('email_verified_successfully')
            : __('email_unverified_successfully'));
    }

    public function profileVerificationChange(Request $request)
    {
        abort_if(! userCan('broker.update'), 403);

        $broker = Broker::findOrFail($request->id);
        $broker->update(['is_profile_verified' => (bool) $request->status]);

        return responseSuccess($request->status
            ? __('profile_verified_successfully')
            : __('profile_unverified_successfully'));
    }
}

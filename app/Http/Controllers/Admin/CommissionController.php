<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $commissions = Commission::with(['agency.user', 'candidate.user', 'job'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        $totals = [
            'pending' => Commission::where('status', Commission::STATUS_PENDING)->sum('amount'),
            'approved' => Commission::where('status', Commission::STATUS_APPROVED)->sum('amount'),
            'paid' => Commission::where('status', Commission::STATUS_PAID)->sum('amount'),
        ];

        return view('backend.commissions.index', compact('commissions', 'totals'));
    }

    public function updateStatus(Request $request, Commission $commission)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,paid',
        ]);

        $data = ['status' => $validated['status']];
        if ($validated['status'] === Commission::STATUS_PAID && ! $commission->paid_at) {
            $data['paid_at'] = now();
        }

        $commission->update($data);

        flashSuccess('Commission status updated.');

        return back();
    }
}

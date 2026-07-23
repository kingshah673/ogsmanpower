<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\ProtectorRecord;
use App\Notifications\Website\ProtectorClearanceUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AgencyProtectorController extends Controller
{
    public function index()
    {
        $agencyId = currentAgency()->id;

        $records = ProtectorRecord::where('agency_id', $agencyId)
            ->with(['candidate', 'job'])
            ->latest()
            ->paginate(20);

        $candidates = Candidate::where('agency_id', $agencyId)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('frontend.pages.agency.protector.index', compact('records', 'candidates'));
    }

    public function store(Request $request)
    {
        $agencyId = currentAgency()->id;

        $validated = $request->validate([
            'candidate_id' => 'required|integer',
            'reference_number' => 'nullable|string|max:120',
            'submission_file' => 'nullable|file|max:10240',
        ]);

        $candidate = Candidate::where('agency_id', $agencyId)->findOrFail($validated['candidate_id']);

        $data = [
            'candidate_id' => $candidate->id,
            'agency_id' => $agencyId,
            'company_id' => optional($candidate->appliedJobs()->latest()->first())->company_id,
            'reference_number' => $validated['reference_number'] ?? null,
            'submission_status' => 'submitted',
            'clearance_status' => 'pending',
            'submitted_at' => now(),
            'created_by' => auth()->id(),
        ];

        if ($request->hasFile('submission_file')) {
            $data['submission_file'] = $request->file('submission_file')->store('protector', 'public');
        }

        ProtectorRecord::create($data);

        flashSuccess('Protector submission recorded for '.$candidate->first_name.'.');

        return back();
    }

    public function update(Request $request, $id)
    {
        $agencyId = currentAgency()->id;
        $record = ProtectorRecord::where('agency_id', $agencyId)->findOrFail($id);

        $validated = $request->validate([
            'clearance_status' => 'required|in:pending,cleared,rejected',
            'submission_status' => 'required|in:not_submitted,submitted,under_review',
            'rejection_reason' => 'nullable|string|max:1000',
            'expiry_date' => 'nullable|date',
            'clearance_file' => 'nullable|file|max:10240',
        ]);

        $data = [
            'clearance_status' => $validated['clearance_status'],
            'submission_status' => $validated['submission_status'],
            'rejection_reason' => $validated['clearance_status'] === 'rejected' ? ($validated['rejection_reason'] ?? null) : null,
            'expiry_date' => $validated['expiry_date'] ?? $record->expiry_date,
            'updated_by' => auth()->id(),
        ];

        if ($validated['clearance_status'] === 'cleared' && ! $record->cleared_at) {
            $data['cleared_at'] = now();
        }

        if ($request->hasFile('clearance_file')) {
            $data['clearance_file'] = $request->file('clearance_file')->store('protector', 'public');
        }

        $record->update($data);

        if (in_array($validated['clearance_status'], ['cleared', 'rejected'], true)) {
            $recipients = collect([
                $record->candidate?->user,
                $record->company?->user,
            ])->filter();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new ProtectorClearanceUpdatedNotification($record));
            }
        }

        flashSuccess('Protector record updated.');

        return back();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PassportOcrLog;
use App\Models\Candidate;
use Illuminate\Http\Request;

class PassportOcrController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST — all pending/recent OCR logs
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $logs = PassportOcrLog::with(['candidate.user'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25);

        return view('backend.candidate.passport-ocr-review', compact('logs'));
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM — admin approves extracted data → write to candidate
    |--------------------------------------------------------------------------
    */

    public function confirm(Request $request, PassportOcrLog $log)
    {
        $request->validate([
            'fields' => 'required|array',
        ]);

        $candidate = $log->candidate;

        if ($candidate) {
            $map = [
                'passport_number' => 'passport_number',
                'date_of_birth'   => 'dob',
                'date_of_expiry'  => 'passport_expiry',
                'place_of_issue'  => 'place_of_issue',
                'nationality'     => 'nationality',
                'gender'          => 'gender',
                'place_of_birth'  => 'place_of_birth',
                'date_of_issue'   => 'date_of_issue',
            ];

            $update = [];
            foreach ($request->fields as $ocrKey => $value) {
                if (isset($map[$ocrKey]) && !empty($value)) {
                    $update[$map[$ocrKey]] = $value;
                }
            }

            if (!empty($update)) {
                $candidate->update($update);
            }
        }

        $log->update([
            'status'       => 'confirmed',
            'confirmed_by' => auth('admin')->id(),
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Passport data confirmed and applied.');
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT — admin rejects this OCR scan
    |--------------------------------------------------------------------------
    */

    public function reject(PassportOcrLog $log)
    {
        $log->update([
            'status'       => 'rejected',
            'confirmed_by' => auth('admin')->id(),
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'OCR scan rejected.');
    }
}

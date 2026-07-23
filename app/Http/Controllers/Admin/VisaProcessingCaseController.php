<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NominatedWorker;
use App\Models\VpCase;
use App\Services\VisaProcessing\VisaProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VisaProcessingCaseController extends Controller
{
    public function index()
    {
        $cases = VpCase::with(['candidate.user', 'company', 'job'])
            ->latest()
            ->paginate(25);

        return view('backend.visa-processing.cases.index', compact('cases'));
    }

    public function show(VpCase $vp_case)
    {
        $case = $vp_case->load([
            'steps.requirements.answer',
            'steps.requirements.file',
            'events',
            'candidate.user',
            'job',
            'company.user',
        ]);

        return view('backend.visa-processing.cases.show', compact('case'));
    }

    public function cancel(Request $request, VpCase $vp_case, VisaProcessingService $service)
    {
        $data = $request->validate([
            'cancel_reason' => 'required|string|min:5|max:2000',
        ]);

        try {
            $service->cancelCase($vp_case, $data['cancel_reason'], auth('admin')->id() ?? 0);
            flashSuccess('Case cancelled. Employer and candidate have been notified.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function downloadFile(VpCase $vp_case, int $fileId)
    {
        $file = $vp_case->files()->where('id', $fileId)->firstOrFail();
        $path = storage_path('app/public/'.ltrim($file->path, '/'));
        if (! is_file($path)) {
            abort(404);
        }

        return response()->download($path, $file->original_name);
    }
}

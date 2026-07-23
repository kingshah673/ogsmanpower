<?php

namespace App\Http\Traits;

use App\Models\CandidateResume;
use Illuminate\Http\Request;

trait HasCandidateResume
{
    /**
     *  Candidate resume upload with normal form
     */
    public function resumeStore(Request $request)
    {
        $request->validate([
            'resume_name' => 'required',
            'resume_file' => 'required|mimes:pdf|max:5120',
        ]);

        $candidate = auth()->user()->candidate;
        $data['name'] = $request->resume_name;
        $data['candidate_id'] = $candidate->id;

        // cv
        if ($request->resume_file) {
            $pdfPath = 'file/candidates/';
            $file = uploadFileToPublic($request->resume_file, $pdfPath);
            $data['file'] = $file;
        }

        CandidateResume::create($data);

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $resume = CandidateResume::where('candidate_id', $candidate->id)->latest('id')->first();

            return response()->json([
                'success' => true,
                'message' => __('resume_added_successfully'),
                'item' => $resume ? [
                    'id' => $resume->id,
                    'name' => $resume->name,
                    'file' => $resume->file,
                ] : null,
                'action' => 'create',
                'resource' => 'resume',
            ]);
        }

        return back()->with('success', 'Resume added successfully');
    }

    /**
     *  Candidate resume upload with normal form
     */
    public function resumeStoreAjax(Request $request)
    {

        $request->validate([
            'resume_name' => 'required',
            'resume_file' => 'required|mimes:pdf|max:5120',
        ]);

        $candidate = auth()->user()->candidate;
        $data['name'] = $request->resume_name;
        $data['candidate_id'] = $candidate->id;

        // cv
        if ($request->resume_file) {
            $pdfPath = 'file/candidates/';
            $file = uploadFileToPublic($request->resume_file, $pdfPath);
            $data['file'] = $file;
        }

        CandidateResume::create($data);

        return response()->json(['success' => __('resume_added_successfully')]);
    }

    /**
     *  Candidate resume upload with ajax
     *
     * @return \Illuminate\Http\Response
     */
    public function getResumeAjax()
    {
        if (auth('user')->check() && authUser()->role == 'candidate') {
            $resumes = currentCandidate()->resumes()->latest()->get();
        } else {
            $resumes = [];
        }

        return response()->json($resumes);
    }

    /**
     * Candidate resume update
     *
     * @return \Illuminate\Http\Response
     */
    public function resumeUpdate(Request $request)
    {
        $request->validate([
            'resume_name' => 'required',
        ]);

        $resume = CandidateResume::where('id', $request->resume_id)->first();
        $candidate = auth()->user()->candidate;
        $data['name'] = $request->resume_name;
        $data['candidate_id'] = $candidate->id;

        // cv
        if ($request->resume_file) {
            $request->validate([
                'resume_file' => 'required|mimes:pdf|max:5120',
            ]);
            deleteFile($resume->file);
            $pdfPath = 'file/candidates/';
            $file = uploadFileToPublic($request->resume_file, $pdfPath);
            $data['file'] = $file;
        }

        $resume->update($data);

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Resume updated successfully',
                'item' => [
                    'id' => $resume->id,
                    'name' => $resume->name,
                    'file' => $resume->file,
                ],
                'action' => 'update',
                'resource' => 'resume',
            ]);
        }

        return back()->with('success', 'Resume updated successfully');
    }

    /**
     * Candidate resume delete
     *
     * @return \Illuminate\Http\Response
     */
    public function resumeDelete(Request $request, CandidateResume $resume)
    {
        deleteFile($resume->file);
        $id = $resume->id;
        $resume->delete();

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Resume deleted successfully',
                'id' => $id,
                'action' => 'delete',
                'resource' => 'resume',
            ]);
        }

        return back()->with('success', 'Resume deleted successfully');
    }

    /**
     * Candidate resume view
     *
     * @return \Illuminate\Http\Response
     */
    public function cvShow(Request $request)
    {
        $cv = CandidateResume::FindOrFail($request->cv);

        abort_if(! auth()->check(), 403);

        $user = auth()->user();
        $candidate = $user->candidate;

        if ($cv->candidate_id != $candidate->id) {
            abort(403);
        }

        $fullPath = storage_path('app/public/' . ltrim($cv->file, '/'));
        abort_if(!file_exists($fullPath), 404);
        return response()->file($fullPath);
    }
}

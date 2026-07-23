<?php

namespace App\Http\Traits;

use App\Models\ApplicationGroup;
use App\Models\AppliedJob;
use App\Models\Education;
use App\Models\Job;
use App\Models\SearchCountry;
use Illuminate\Http\Request;

trait HasAgencyApplication
{
    /**
     * Sync Applications
     */
    public function applicationsSync(Request $request)
    {
        $request->validate([
            'applicationGroups' => ['required', 'array'],
        ]);

        foreach ($request->applicationGroups as $applicationGroup) {
            foreach ($applicationGroup['applications'] as $i => $application) {

                $order = $i + 1;

                if (
                    $application['application_group_id'] !== $applicationGroup['id'] ||
                    $application['order'] != $order
                ) {
                    $applications = AppliedJob::where('id', $application['id'])
                        ->where('application_group_id', $application['application_group_id'])
                        ->first();

                    if ($applications) {
                        $applications->update([
                            'order' => $order,
                            'application_group_id' => $applicationGroup['id'],
                        ]);
                    }
                }
            }
        }

        $agency = $request->user()->agency;

        if (!$agency) {
            abort(403);
        }

        return $agency
            ->applicationGroups()
            ->with(['applications' => function ($query) {
                $query->with(['candidate' => function ($query) {
                    $query->select('id', 'user_id', 'profession_id', 'experience_id', 'education_id')
                        ->with('profession', 'education:id', 'experience:id', 'user:id,name,username,image');
                }]);
            }])
            ->get();
    }

    /**
     * Job Applications Page
     */
    public function jobApplications(Request $request)
    {
        $agency = auth()->user()->agency;

        if (!$agency) {
            abort(403);
        }

        $application_groups = $agency
            ->applicationGroups()
            ->with(['applications' => function ($query) use ($request) {

                $query->where('job_id', $request->job)
                    ->with(['candidate' => function ($query) use ($request) {

                        $query->select('id', 'user_id', 'profession_id', 'experience_id', 'education_id', 'gender', 'country')
                            ->with(
                                'profession',
                                'education:id',
                                \Illuminate\Support\Facades\Schema::hasColumn('experiences', 'years')
                                    ? 'experience:id,years'
                                    : 'experience:id',
                                'user:id,name,username,image'
                            );

                        if ($request->filled('gender')) {
                            $query->where('gender', $request->gender);
                        }

                        if ($request->filled('country')) {
                            $query->where('country', $request->country);
                        }

                        if ($request->filled('name')) {
                            $query->whereHas('user', function ($q) use ($request) {
                                $q->where('name', 'like', '%' . $request->name . '%');
                            });
                        }

                        if (\Illuminate\Support\Facades\Schema::hasColumn('candidates', 'age')) {
                            if ($request->filled('age_from')) {
                                $query->where('age', '>=', $request->age_from);
                            }

                            if ($request->filled('age_to')) {
                                $query->where('age', '<=', $request->age_to);
                            }
                        }

                        if (\Illuminate\Support\Facades\Schema::hasColumn('experiences', 'years')) {
                            if ($request->filled('experience_from')) {
                                $query->whereHas('experience', function ($q) use ($request) {
                                    $q->where('years', '>=', $request->experience_from);
                                });
                            }

                            if ($request->filled('experience_to')) {
                                $query->whereHas('experience', function ($q) use ($request) {
                                    $q->where('years', '<=', $request->experience_to);
                                });
                            }
                        }
                    }]);
            }])
            ->get();

        $directAttachments = AppliedJob::whereNull('candidate_id')
            ->where('job_id', $request->job)
            ->get();

        $job = Job::findOrFail($request->job, ['id', 'title', 'agency_id']);

        // ✅ FIXED (no helper used)
        abort_if($agency->id != $job->agency_id, 404);

        $countries = SearchCountry::all();
        $educations = Education::all();

        $rejected = AppliedJob::where('status', 'rejected')
            ->where('job_id', $request->job)->count();

        $shortlisted = AppliedJob::where('status', 'shortlisted')
            ->where('job_id', $request->job)->count();

        $selected = AppliedJob::where('status', 'selected')
            ->where('job_id', $request->job)->count();

        return view('frontend.pages.agency.applications', compact(
            'application_groups',
            'job',
            'countries',
            'directAttachments',
            'rejected',
            'shortlisted',
            'selected',
            'educations'
        ));
    }

    /**
     * Create Column
     */
    public function applicationColumnStore(Request $request)
    {
        $request->validate(['name' => 'required']);

        $agency = auth()->user()->agency;

        if (!$agency) {
            abort(403);
        }

        ApplicationGroup::create([
            'agency_id' => $agency->id,
            'name' => $request->name,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Update Column
     */
    public function applicationColumnUpdate(Request $request)
    {
        $request->validate(['name' => 'required']);

        ApplicationGroup::findOrFail($request->id)->update([
            'name' => $request->name,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete Column
     */
    public function applicationColumnDelete(ApplicationGroup $group)
    {
        $agency = auth()->user()->agency;

        if (!$agency) {
            abort(403);
        }

        if ($group->is_deleteable) {

            $new_group = ApplicationGroup::where('agency_id', $agency->id)
                ->where('id', '!=', $group->id)
                ->where('is_deleteable', false)
                ->first();

            if ($new_group) {
                $group->applications()->update([
                    'application_group_id' => $new_group->id,
                ]);
            }

            $group->delete();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    /**
     * Delete Application
     */
    public function destroyApplication(Job $job, Request $request)
    {
        $job->appliedJobs()->detach($request->candidate_id);

        return back();
    }

    /**
     * Bulk status update — multi-select applicants, apply one status to all.
     * Uses ApplicationStatusService so candidate/agency notifications fire
     * exactly the same way as a single-record update.
     */
    public function bulkUpdateApplicationStatus(Request $request)
    {
        $validated = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer', 'exists:applied_jobs,id'],
            'status' => ['required', 'in:selected,rejected,shortlisted,pending'],
        ]);

        $agency = auth()->user()->agency;
        if (!$agency) {
            abort(403);
        }

        $applications = AppliedJob::whereIn('id', $validated['application_ids'])
            ->where('agency_id', $agency->id)
            ->get();

        $service = app(\App\Services\Jobs\ApplicationStatusService::class);
        foreach ($applications as $application) {
            $service->updateStatus($application, $validated['status']);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'updated' => $applications->count()]);
        }

        flashSuccess($applications->count().' application(s) marked as '.$validated['status'].'.');

        return back();
    }
}
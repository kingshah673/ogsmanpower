<?php

namespace App\Http\Traits;

use App\Models\ApplicationGroup;
use App\Models\AppliedJob;
use App\Models\Education;
use App\Models\Job;
use App\Models\SearchCountry;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

trait HasCompanyApplication
{
    /**
     * Company job application sync (kanban — kept for compatibility)
     *
     * @return Response
     */
    public function applicationsSync(Request $request)
    {
        $this->validate(request(), [
            'applicationGroups' => ['required', 'array'],
        ]);

        foreach ($request->applicationGroups as $applicationGroup) {
            foreach ($applicationGroup['applications'] as $i => $application) {
                $order = $i + 1;

                if ($application['application_group_id'] !== $applicationGroup['id'] || $application['order'] != $order) {
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

        return $request->user()
            ->company
            ->applicationGroups()
            ->with(['applications' => function ($query) {
                $query->with(['candidate' => function ($query) {
                    return $query->select('id', 'user_id', 'profession_id', 'experience_id', 'education_id')
                        ->with('profession', 'education:id', 'experience:id', 'user:id,name,username,image');
                }]);
            }])
            ->get();
    }

    /**
     * Shared applicant query for company (job-specific or all jobs).
     */
    protected function companyApplicantQuery(Request $request, ?int $jobId = null)
    {
        $companyId = currentCompany()->id;

        $query = AppliedJob::query()
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereHas('job', fn ($jq) => $jq->where('company_id', $companyId));
            })
            ->whereNotNull('candidate_id')
            ->with([
                'job:id,title,slug,company_id,total_views,vacancies,status,featured',
                'vpCase',
                'resume',
                'candidate' => function ($q) {
                    $cols = ['id', 'user_id', 'profession_id', 'experience_id', 'education_id', 'gender', 'country', 'photo'];
                    if (Schema::hasColumn('candidates', 'age')) {
                        $cols[] = 'age';
                    }
                    if (Schema::hasColumn('candidates', 'birth_date')) {
                        $cols[] = 'birth_date';
                    }
                    if (Schema::hasColumn('candidates', 'district')) {
                        $cols[] = 'district';
                    }
                    if (Schema::hasColumn('candidates', 'region')) {
                        $cols[] = 'region';
                    }
                    $q->select($cols)->with([
                        'profession',
                        'education',
                        'experience',
                        'user:id,name,username,image',
                        'skills',
                        'experiences' => fn ($eq) => $eq->latest('id')->limit(1),
                        'educations' => fn ($eq) => $eq->latest('id')->limit(1),
                    ]);
                },
            ]);

        if ($jobId) {
            $query->where('job_id', $jobId);
        } elseif ($request->filled('job_id')) {
            $query->where('job_id', (int) $request->job_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('name') || $request->filled('q')) {
            $term = $request->input('name') ?: $request->input('q');
            $query->where(function ($outer) use ($term) {
                $outer->whereHas('candidate.user', function ($q) use ($term) {
                    $q->where('name', 'like', '%'.$term.'%');
                })->orWhereHas('candidate.skills', function ($q) use ($term) {
                    $q->whereTranslationLike('name', '%'.$term.'%');
                });
            });
        }

        if ($request->filled('gender')) {
            $query->whereHas('candidate', fn ($q) => $q->where('gender', $request->gender));
        }

        if ($request->filled('country')) {
            $query->whereHas('candidate', fn ($q) => $q->where('country', $request->country));
        }

        if ($request->filled('education')) {
            $edu = $request->education;
            $query->whereHas('candidate', function ($q) use ($edu) {
                if (is_numeric($edu)) {
                    $q->where('education_id', (int) $edu);
                } else {
                    $q->whereHas('education', function ($eq) use ($edu) {
                        $eq->whereTranslationLike('name', '%'.$edu.'%');
                    });
                }
            });
        }

        if ($request->filled('skill')) {
            $skill = $request->skill;
            $query->whereHas('candidate.skills', function ($q) use ($skill) {
                if (is_numeric($skill)) {
                    $q->where('skills.id', (int) $skill);
                } else {
                    $q->where('skills.id', $skill);
                }
            });
        }

        if (Schema::hasColumn('experiences', 'years')) {
            if ($request->filled('experience_from')) {
                $query->whereHas('candidate.experience', fn ($q) => $q->where('years', '>=', $request->experience_from));
            }
            if ($request->filled('experience_to')) {
                $query->whereHas('candidate.experience', fn ($q) => $q->where('years', '<=', $request->experience_to));
            }
        }

        if (Schema::hasColumn('candidates', 'age')) {
            if ($request->filled('age_from')) {
                $query->whereHas('candidate', fn ($q) => $q->where('age', '>=', $request->age_from));
            }
            if ($request->filled('age_to')) {
                $query->whereHas('candidate', fn ($q) => $q->where('age', '<=', $request->age_to));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sort = $request->input('sort', 'date_desc');
        match ($sort) {
            'date_asc' => $query->orderBy('created_at', 'asc'),
            'name' => $query
                ->leftJoin('candidates as sort_candidates', 'sort_candidates.id', '=', 'applied_jobs.candidate_id')
                ->leftJoin('users as sort_users', 'sort_users.id', '=', 'sort_candidates.user_id')
                ->orderBy('sort_users.name')
                ->select('applied_jobs.*'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query;
    }

    /**
     * Status counts for tabs.
     */
    protected function companyApplicantStatusCounts(?int $jobId = null, ?int $companyId = null): array
    {
        $companyId = $companyId ?: currentCompany()->id;

        $base = AppliedJob::query()
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereHas('job', fn ($jq) => $jq->where('company_id', $companyId));
            })
            ->whereNotNull('candidate_id');

        if ($jobId) {
            $base->where('job_id', $jobId);
        }

        return [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'shortlisted' => (clone $base)->where('status', 'shortlisted')->count(),
            'interview' => (clone $base)->where('status', 'interview')->count(),
            'selected' => (clone $base)->where('status', 'selected')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];
    }

    /**
     * Company job application page (Rozee-style list).
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function jobApplications(Request $request)
    {
        $request->validate([
            'job' => 'required|exists:jobs,id',
        ]);

        $job = Job::query()
            ->select(['id', 'title', 'slug', 'company_id', 'total_views', 'vacancies', 'status', 'featured', 'deadline'])
            ->findOrFail($request->job);

        abort_if((int) currentCompany()->id !== (int) $job->company_id, 404);

        $applications = $this->companyApplicantQuery($request, (int) $job->id)->paginate(12)->withQueryString();
        $counts = $this->companyApplicantStatusCounts((int) $job->id);
        $countries = SearchCountry::query()->orderBy('name')->get(['id', 'name']);
        $educations = Education::all();
        $skills = Skill::all()->sortBy('name');

        // Keep legacy variables for any leftover references
        $rejected = $counts['rejected'];
        $shortlisted = $counts['shortlisted'];
        $selected = $counts['selected'];
        $directAttachments = AppliedJob::whereNull('candidate_id')->where('job_id', $job->id)->get();
        $application_groups = collect();

        return view('frontend.pages.company.applications', compact(
            'applications',
            'job',
            'counts',
            'countries',
            'educations',
            'skills',
            'rejected',
            'shortlisted',
            'selected',
            'directAttachments',
            'application_groups'
        ));
    }

    /**
     * Company-wide applicants across all jobs.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function applicants(Request $request)
    {
        $company = currentCompany();
        $applications = $this->companyApplicantQuery($request)->paginate(12)->withQueryString();
        $counts = $this->companyApplicantStatusCounts();
        $countries = SearchCountry::query()->orderBy('name')->get(['id', 'name']);
        $educations = Education::all();
        $skills = Skill::all()->sortBy('name');
        $jobs = Job::query()
            ->where('company_id', $company->id)
            ->orderByDesc('id')
            ->get(['id', 'title']);

        return view('frontend.pages.company.applicants', compact(
            'applications',
            'counts',
            'countries',
            'educations',
            'skills',
            'jobs'
        ));
    }

    /**
     * Application Column Store
     *
     * @return \Illuminate\Http\Response
     */
    public function applicationColumnStore(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        ApplicationGroup::create([
            'company_id' => auth()->user()->company->id,
            'name' => $request->name,
        ]);

        flashSuccess(__('group_created_successfully'));

        return response()->json(['success' => true]);
    }

    /**
     * Application Column Update
     *
     * @return \Illuminate\Http\Response
     */
    public function applicationColumnUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        ApplicationGroup::find($request->id)->update([
            'name' => $request->name,
        ]);

        flashSuccess(__('group_updated_successfully'));

        return response()->json(['success' => true]);
    }

    /**
     * Application Column Delete
     *
     * @return \Illuminate\Http\Response
     */
    public function applicationColumnDelete(ApplicationGroup $group)
    {
        abort_if($group->company_id !== auth()->user()->company->id, 403);

        if (! $group->is_deleteable) {
            return response()->json(['success' => false, 'message' => __('group_is_not_deletable')]);
        }

        $new_group = ApplicationGroup::where('company_id', auth()->user()->company->id)
            ->where('id', '!=', $group->id)
            ->where('is_deleteable', false)
            ->first();

        if ($new_group) {
            $group->applications()->update([
                'application_group_id' => $new_group->id,
            ]);
        }

        $group->delete();

        return response()->json(['success' => true, 'message' => __('group_deleted_successfully')]);
    }

    public function applicationsGroupStore(Request $request)
    {
        return $this->applicationColumnStore($request);
    }

    public function applicationsGroupUpdate(Request $request, ApplicationGroup $group)
    {
        abort_if($group->company_id !== auth()->user()->company->id, 403);
        $request->merge(['id' => $group->id]);

        return $this->applicationColumnUpdate($request);
    }

    public function applicationsGroupDestroy(ApplicationGroup $group)
    {
        return $this->applicationColumnDelete($group);
    }

    /**
     * Company Delete Applications
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyApplication(Job $job, Request $request)
    {
        $job->appliedJobs()->detach($request->candidate_id);

        flashSuccess(__('application_removed_from_our_system'));

        return back();
    }
}

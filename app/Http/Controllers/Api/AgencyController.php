<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\AgencyJobTrait;
use App\Http\Traits\JobAble;
use App\Models\ApplicationGroup;
use App\Models\AgencyBookmarkCategory;
use App\Models\Earning;
use App\Models\Job;
use App\Models\UserPlan;
use App\Services\API\Website\Agency\ApplicationGroup\CreateApplicationGroupService;
use App\Services\API\Website\Agency\ApplicationGroup\DeleteApplicationGroupService;
use App\Services\API\Website\Agency\ApplicationGroup\UpdateApplicationGroupService;
use App\Services\API\Website\Agency\Bookmark\CandidateBookmarkService;
use App\Services\API\Website\Agency\Bookmark\CreateBookmarkCategoryService;
use App\Services\API\Website\Agency\Bookmark\FetchCandidateBookmarkService;
use App\Services\API\Website\Agency\Bookmark\UpdateBookmarkCategoryService;
use App\Services\API\Website\Agency\PostingJob\CloneJobService;
use App\Services\API\Website\Agency\PostingJob\FetchEditJobDataService;
use App\Services\API\Website\Agency\PostingJob\FetchPostJobDataService;
use App\Services\API\Website\Agency\PostingJob\JobStatusUpdateService;
use App\Services\API\Website\Agency\PostingJob\PromoteJobService;
use App\Services\API\Website\Agency\PostingJob\StoreJobService;
use App\Services\API\Website\Agency\PostingJob\UpdateJobService;
use App\Services\API\Website\AgencyAccountProgress;
use App\Services\API\Website\PaymentService;
use F9Web\ApiResponseHelpers;
use Illuminate\Http\Request;
use PDF;

class AgencyController extends Controller
{
    use ApiResponseHelpers, AgencyJobTrait, JobAble;

    public function dashboard()
    {
        // $data['userplan'] = UserPlan::with('plan')->apiAgencyData()->firstOrFail();
        $data['openJobCount'] = auth('sanctum')->user()->agency->jobs()->active()->count();

        // Recent 4 Jobs
        $data['recentJobs'] = auth('sanctum')->user()->agency->jobs()
            ->select(['id', 'title', 'agency_id', 'country', 'max_salary', 'min_salary', 'job_type_id', 'slug', 'deadline', 'status'])
            ->latest()->with('agency:id', 'job_type')->withCount('appliedJobs')->take(4)->get();
        $data['savedCandidateCount'] = auth('sanctum')->user()->agency->bookmarkCandidates()->count();

        return $this->respondWithSuccess([
            'data' => $data,
        ]);
    }

    public function createJob()
    {
        return $this->respondWithSuccess([
            'data' => (new FetchPostJobDataService)->execute(),
        ]);
    }

    public function storeJob(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new StoreJobService)->execute($request),
        ]);
    }

    public function editJob(Job $job)
    {
        return $this->respondWithSuccess([
            'data' => (new FetchEditJobDataService)->execute($job),
        ]);
    }

    public function updateJob(Request $request, Job $job)
    {
        return $this->respondWithSuccess([
            'data' => (new UpdateJobService)->execute($request, $job),
        ]);
    }

    public function promoteJob(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new PromoteJobService)->execute($request),
        ]);
    }

    public function cloneJob(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new CloneJobService)->execute($request),
        ]);
    }

    public function changeJobStatus(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new JobStatusUpdateService)->execute($request),
        ]);
    }

    public function fetchBookmarkCategories()
    {
        return $this->respondWithSuccess([
            'data' => AgencyBookmarkCategory::where('agency_id', auth('sanctum')->user()->agency->id)->get(),
        ]);
    }

    public function storeBookmarkCategories(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new CreateBookmarkCategoryService)->execute($request),
        ]);
    }

    public function editBookmarkCategories(AgencyBookmarkCategory $category)
    {
        return $this->respondWithSuccess([
            'data' => $category,
        ]);
    }

    public function updateBookmarkCategories(Request $request, AgencyBookmarkCategory $category)
    {
        return $this->respondWithSuccess([
            'data' => (new UpdateBookmarkCategoryService)->execute($request, $category),
        ]);
    }

    public function deleteBookmarkCategories(AgencyBookmarkCategory $category)
    {
        $category->delete();

        return $this->respondOk(__('category_deleted_successfully'));
    }

    public function fetchBookmarkCandidates(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new FetchCandidateBookmarkService)->execute($request),
        ]);
    }

    public function bookmarkCandidate(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new CandidateBookmarkService)->execute($request),
        ]);
    }

    public function fetchApplicationGroup(Request $request)
    {
        $groups = $request->user()->agency->applicationGroups()->get();

        return $this->respondWithSuccess([
            'data' => $groups,
        ]);
    }

    public function storeApplicationGroup(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new CreateApplicationGroupService)->execute($request),
        ]);
    }

    public function updateApplicationGroup(Request $request, ApplicationGroup $group)
    {
        return $this->respondWithSuccess([
            'data' => (new UpdateApplicationGroupService)->execute($request, $group),
        ]);
    }

    public function deleteApplicationGroup(ApplicationGroup $group)
    {
        return $this->respondWithSuccess([
            'data' => (new DeleteApplicationGroupService)->execute($group),
        ]);
    }

    public function fetchAccountProgress()
    {
        return $this->respondWithSuccess([
            'data' => (new AgencyAccountProgress)->fetchAccountProgressData(),
        ]);
    }

    public function submitAccountProgress(Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new AgencyAccountProgress)->submitAccountProgressData($request),
        ]);
    }

    public function plan()
    {
        $userPlan = UserPlan::with('plan')->apiAgencyData()->firstOrFail();
        $transactions = Earning::with('plan:id,label', 'manualPayment:id,name')->apiAgencyData()->latest()->paginate(8);

        return $this->respondWithSuccess([
            'data' => [
                'user_plan' => $userPlan,
                'transactions' => $transactions,
            ],
        ]);
    }

    public function planLimit()
    {
        $userPlan = UserPlan::with('plan')->firstOrFail();

        return $this->respondWithSuccess([
            'data' => $userPlan,
        ]);
    }

    public function payment($label, Request $request)
    {
        return $this->respondWithSuccess([
            'data' => (new PaymentService)->execute($label, $request),
        ]);
    }

    public function downloadInvoice($id)
    {
        $transaction = Earning::findOrFail($id);
        $data['transaction'] = $transaction->load('plan', 'agency.user.contactInfo');
        $data['logo'] = setting()->dark_logo_url ?? asset('frontend/assets/images/logo/logo.png');

        $pdf = PDF::loadView('website.pages.agency.invoice', $data)->setOptions(['defaultFont' => 'sans-serif']);

        return $pdf->download('invoice_'.$transaction->order_id.'.pdf');
    }

    public function getSocialLinks(Request $request)
    {

        $user = auth('sanctum')->user();

        if ($user && $user->role == 'agency') {
            return $this->respondWithSuccess([
                'data' => [
                    'social_media' => $user->socialInfo->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'social_media' => $item->social_media,
                            'url' => $item->url,
                        ];
                    }),
                ],
            ]);
        } else {
            return $this->respondUnAuthenticated('Unauthenticated User');

        }

    }
}

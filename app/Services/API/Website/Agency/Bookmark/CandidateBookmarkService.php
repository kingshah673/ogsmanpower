<?php

namespace App\Services\API\Website\Agency\Bookmark;

use App\Models\Candidate;
use App\Models\User;
use App\Notifications\Website\Agency\CandidateBookmarkNotification;
use F9Web\ApiResponseHelpers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CandidateBookmarkService
{
    use ApiResponseHelpers;

    public function execute($request)
    {
        $agency = auth('sanctum')->user()->agency;
        $user = User::where('username', $request->username)->first();

        if ($user) {
            $candidate = $user->candidate;
        } else {
            return $this->respondNotFound(__('candidate_not_found'));
        }

        if ($request->category_id) {
            $user_plan = $agency->userPlan;

            if (isset($user_plan) && $user_plan->candidate_cv_view_limit <= 0) {
                return $this->respondError(__('you_have_reached_your_limit_for_viewing_candidate_cv_please_upgrade_your_plan'));
            }

            isset($user_plan) ? $user_plan->decrement('candidate_cv_view_limit') : '';
        }

        $check = $agency->bookmarkCandidates()->toggle($candidate->id);

        if ($check['attached'] == [$candidate->id]) {
            DB::table('bookmark_agency')->where('agency_id', auth('sanctum')->user()->agency->id)->where('candidate_id', $candidate->id)->update(['category_id' => $request->category_id]);

            // make notification to candidate
            $user = Auth::user('user');
            if ($candidate->user->shortlisted_alert) {
                Notification::send($candidate->user, new CandidateBookmarkNotification($user, $candidate));
            }
            // notify to agency
            Notification::send(auth('sanctum')->user(), new CandidateBookmarkNotification($user, $candidate));

            return $this->respondOk(__('candidate_added_to_bookmark_list'));
        } else {
            return $this->respondOk(__('candidate_removed_from_bookmark_list'));
        }
    }
}

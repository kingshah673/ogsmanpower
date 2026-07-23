<?php

use App\Models\AppliedJob;
use App\Models\Candidate;
use App\Models\Job;
use App\Notifications\Website\Candidate\ShortlistedJobNotification;
use App\Services\Jobs\ApplicationStatusService;
use Illuminate\Support\Facades\Notification;

it('sends mail and database notification when seeker is shortlisted', function () {
    Notification::fake();

    $candidate = Candidate::factory()->create();
    $user = $candidate->user;
    $user->forceFill(['email' => 'seeker@example.com'])->save();

    $job = new Job(['title' => 'Physician', 'slug' => 'physician']);
    $job->id = 1;

    $application = new AppliedJob(['status' => 'pending', 'candidate_id' => $candidate->id, 'job_id' => 1]);
    $application->setRelation('candidate', $candidate->load('user'));
    $application->setRelation('job', $job);

    app(ApplicationStatusService::class)->notifyShortlisted($application);

    Notification::assertSentTo($user, ShortlistedJobNotification::class);
});

it('skips shortlist email when candidate has no user email', function () {
    Notification::fake();

    $candidate = Candidate::factory()->create();
    $candidate->setRelation('user', null);

    $application = new AppliedJob(['status' => 'pending']);
    $application->setRelation('candidate', $candidate);
    $application->setRelation('job', null);

    app(ApplicationStatusService::class)->notifyShortlisted($application);

    Notification::assertNothingSent();
});

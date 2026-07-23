<?php

use App\Models\Candidate;
use App\Models\User;
use Database\Seeders\EducationSeeder;
use Database\Seeders\ExperienceSeeder;
use Database\Seeders\JobRoleSeeder;
use Database\Seeders\JobTypeSeeder;
use Database\Seeders\ProfessionSeeder;

/**
 * Smoke tests: authenticated candidate can load every seeker portal page (HTTP 200).
 */
beforeEach(function () {
    $this->seed([
        JobTypeSeeder::class,
        JobRoleSeeder::class,
        ProfessionSeeder::class,
        ExperienceSeeder::class,
        EducationSeeder::class,
    ]);

    $candidate = Candidate::factory()->create();
    $this->user = User::find($candidate->user_id);
    $this->user->forceFill([
        'email_verified_at' => now(),
        'is_otp_verified' => true,
    ])->save();

    $this->actingAs($this->user, 'user');
});

it('loads the candidate dashboard', function () {
    $this->get(route('candidate.dashboard'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.dashboard');
});

it('loads candidate settings', function () {
    $this->get(route('candidate.setting'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.setting');
});

it('loads profile view', function () {
    $this->get(route('candidate.profile.view'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.profile-view');
});

it('loads bilingual CV page', function () {
    $this->get(route('candidate.view.cv'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.cv');
});

it('loads applied jobs page', function () {
    $this->get(route('candidate.appliedjob'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.applied-jobs');
});

it('loads favorite jobs page', function () {
    $this->get(route('candidate.bookmark'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.bookmark');
});

it('loads job alerts page', function () {
    $this->get(route('candidate.job.alerts'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.job-alerts');
});

it('loads contracts page', function () {
    $this->get(route('candidate.contracts.index'))
        ->assertOk();
});

it('loads plans page', function () {
    $this->get(route('candidate.plan'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.plan');
});

it('loads documents page', function () {
    $this->get(route('candidate.document'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.document');
});

it('loads additional settings page', function () {
    $this->get(route('candidate.additionlSetting'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.additionl-setting');
});

it('loads all notifications page', function () {
    $this->get(route('candidate.allNotification'))
        ->assertOk()
        ->assertViewIs('frontend.pages.candidate.all-notification');
});

it('loads visa dashboard', function () {
    $this->get(route('visa.dashboard'))
        ->assertOk();
});

it('returns HTML resume on view action without imagick', function () {
    $this->post(route('candidate.viewResume'), [
        'format' => 'general_format',
        'language_code' => 'en',
        'action_type' => 'view',
    ])
        ->assertOk()
        ->assertSee($this->user->name);
});

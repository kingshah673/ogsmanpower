<?php

namespace App\Services\AI;

use App\Models\Job;
use App\Models\Company;
use App\Models\AppliedJob;
use App\Models\InterviewSchedule;
use App\Models\CandidateJobMatch;
use App\Models\FailedAIMessage;

use App\Services\Interview\InterviewSchedulingService;

class AIMatchingService
{
    protected $interview;

    protected $settings;

    public function __construct(

        InterviewSchedulingService $interview,

        AISettingsService $settings

    ) {

        $this->interview = $interview;

        $this->settings = $settings;
    }

    /*
    |--------------------------------------------------------------------------
    | MATCH CANDIDATE
    |--------------------------------------------------------------------------
    */

    public function match(

        $candidate,

        $cvText

    ) {

        try {

            /*
            |--------------------------------------------------------------------------
            | AI ENABLED
            |--------------------------------------------------------------------------
            */

            if (

                !$this->settings
                    ->enabled('ai_enabled')

            ) {

                return [];
            }

            /*
            |--------------------------------------------------------------------------
            | GET JOBS
            |--------------------------------------------------------------------------
            */

            $jobs =
                Job::query()

                ->where(
                    'status',
                    1
                )

                ->latest()

                ->get();

            $results = [];

            /*
            |--------------------------------------------------------------------------
            | ATS THRESHOLD
            |--------------------------------------------------------------------------
            */

            $threshold =
                $this->settings
                    ->get(

                        'ai_ats_threshold',

                        80
                    );

            /*
            |--------------------------------------------------------------------------
            | LOOP JOBS
            |--------------------------------------------------------------------------
            */

            foreach ($jobs as $job) {

                /*
                |--------------------------------------------------------------------------
                | SCORE
                |--------------------------------------------------------------------------
                */

                $score =
                    $this->calculateScore(

                        $cvText,

                        $job
                    );

                /*
                |--------------------------------------------------------------------------
                | SAVE MATCH
                |--------------------------------------------------------------------------
                */

                CandidateJobMatch::updateOrCreate(

                    [

                        'candidate_id'
                            => $candidate->id,

                        'job_id'
                            => $job->id
                    ],

                    [

                        'score'
                            => $score,

                        'remarks'
                            => 'AI ATS generated score'
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | APPLICATION
                |--------------------------------------------------------------------------
                */

                $application =
                    AppliedJob::query()

                    ->where(
                        'candidate_id',
                        $candidate->id
                    )

                    ->where(
                        'job_id',
                        $job->id
                    )

                    ->first();

                /*
                |--------------------------------------------------------------------------
                | RUN AI WORKFLOW
                |--------------------------------------------------------------------------
                */

                app(
                    \App\Services\AI\AIWorkflowEngine::class
                )->run(

                    'candidate_matched',

                    [

                        'candidate'
                            => $candidate,

                        'job'
                            => $job,

                        'application'
                            => $application,

                        'score'
                            => $score
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | AUTO INTERVIEW
                |--------------------------------------------------------------------------
                */

                if (

                    $score >= $threshold

                    &&

                    $this->settings
                        ->enabled(
                            'auto_interview_enabled'
                        )

                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | PREVENT DUPLICATE
                    |--------------------------------------------------------------------------
                    */

                    $exists =
                        InterviewSchedule::query()

                        ->where(
                            'candidate_id',
                            $candidate->id
                        )

                        ->where(
                            'job_id',
                            $job->id
                        )

                        ->exists();

                    if (!$exists) {

                        $company =
                            Company::find(
                                $job->company_id
                            );

                        if ($company) {

                            $this->interview
                                ->schedule(

                                    $candidate,

                                    $job,

                                    $company,

                                    now()->addDays(2),

                                    'Google Meet'
                                );
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | RESULTS
                |--------------------------------------------------------------------------
                */

                $results[] = [

                    'job'
                        => $job,

                    'score'
                        => $score
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | SORT RESULTS
            |--------------------------------------------------------------------------
            */

            usort(

                $results,

                function ($a, $b) {

                    return
                        $b['score']
                        <=>
                        $a['score'];
                }
            );

            return $results;

        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | FAILED AI LOG
            |--------------------------------------------------------------------------
            */

            FailedAIMessage::create([

                'message'
                    => $cvText,

                'reason'
                    => $e->getMessage()
            ]);

            \Log::error(
                $e->getMessage()
            );

            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE ATS SCORE
    |--------------------------------------------------------------------------
    */

    protected function calculateScore(

        $cvText,

        $job

    ) {

        $score = 0;

        /*
        |--------------------------------------------------------------------------
        | CLEAN CV
        |--------------------------------------------------------------------------
        */

        $cv =
            strtolower(

                strip_tags(
                    $cvText
                )
            );

        /*
        |--------------------------------------------------------------------------
        | JOB TITLE
        |--------------------------------------------------------------------------
        */

        $title =
            strtolower(
                $job->title
            );

        if (

            str_contains(
                $cv,
                $title
            )

        ) {

            $score += 40;
        }

        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION
        |--------------------------------------------------------------------------
        */

        $description =
            strtolower(

                strip_tags(

                    $job->description
                    ?? ''

                )
            );

        $words =
            explode(
                ' ',
                $description
            );

        /*
        |--------------------------------------------------------------------------
        | KEYWORD MATCHING
        |--------------------------------------------------------------------------
        */

        foreach ($words as $word) {

            $word = trim($word);

            if (

                strlen($word) > 4

                &&

                str_contains(
                    $cv,
                    $word
                )

            ) {

                $score += 2;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EXPERIENCE
        |--------------------------------------------------------------------------
        */

        if (

            str_contains($cv, 'experience')
            ||
            str_contains($cv, 'years')

        ) {

            $score += 10;
        }

        /*
        |--------------------------------------------------------------------------
        | EDUCATION
        |--------------------------------------------------------------------------
        */

        if (

            str_contains($cv, 'diploma')
            ||
            str_contains($cv, 'degree')
            ||
            str_contains($cv, 'certificate')

        ) {

            $score += 10;
        }

        /*
        |--------------------------------------------------------------------------
        | SKILLS
        |--------------------------------------------------------------------------
        */

        $skills = [

            'electrician',

            'driver',

            'welder',

            'plumber',

            'mechanic',

            'construction',

            'engineering',

            'forklift',

            'saudi',

            'uae',

            'qatar'
        ];

        foreach ($skills as $skill) {

            if (

                str_contains(
                    $cv,
                    $skill
                )

            ) {

                $score += 3;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | LIMIT SCORE
        |--------------------------------------------------------------------------
        */

        if ($score > 100) {

            $score = 100;
        }

        if ($score < 0) {

            $score = 0;
        }

        return round(
            $score,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOP MATCHES
    |--------------------------------------------------------------------------
    */

    public function topMatches(

        $candidateId,

        $limit = 5

    ) {

        return CandidateJobMatch::query()

            ->with([

                'job',

                'candidate'
            ])

            ->where(
                'candidate_id',
                $candidateId
            )

            ->orderByDesc('score')

            ->take($limit)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | RECOMMENDED JOBS
    |--------------------------------------------------------------------------
    */

    public function recommendJobs(
        $candidateId
    ) {

        return $this->topMatches(

            $candidateId,

            10
        );
    }
}
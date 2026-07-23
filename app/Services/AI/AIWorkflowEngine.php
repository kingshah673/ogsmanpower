<?php

namespace App\Services\AI;

use App\Models\AIWorkflow;

class AIWorkflowEngine
{
    /*
    |--------------------------------------------------------------------------
    | RUN WORKFLOW
    |--------------------------------------------------------------------------
    */

    public function run(

        $trigger,

        $context = []

    ) {

        /*
        |--------------------------------------------------------------------------
        | GET WORKFLOWS
        |--------------------------------------------------------------------------
        */

        $workflows =
            AIWorkflow::query()

            ->where(
                'trigger',
                $trigger
            )

            ->where(
                'status',
                1
            )

            ->get();

        foreach ($workflows as $workflow) {

            /*
            |--------------------------------------------------------------------------
            | CHECK CONDITIONS
            |--------------------------------------------------------------------------
            */

            if (

                !$this->conditionsPassed(

                    $workflow,

                    $context
                )

            ) {

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | EXECUTE ACTIONS
            |--------------------------------------------------------------------------
            */

            $this->executeActions(

                $workflow,

                $context
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK CONDITIONS
    |--------------------------------------------------------------------------
    */

    protected function conditionsPassed(

        $workflow,

        $context

    ) {

        $conditions =
            $workflow->conditions
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | ATS SCORE
        |--------------------------------------------------------------------------
        */

        if (

            isset(
                $conditions['min_score']
            )

        ) {

            if (

                ($context['score'] ?? 0)

                <

                $conditions['min_score']

            ) {

                return false;
            }
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | EXECUTE ACTIONS
    |--------------------------------------------------------------------------
    */

    protected function executeActions(

        $workflow,

        $context

    ) {

        $actions =
            $workflow->actions
            ?? [];

        foreach ($actions as $action) {

            /*
            |--------------------------------------------------------------------------
            | SHORTLIST
            |--------------------------------------------------------------------------
            */

            if (
                $action === 'shortlist'
            ) {

                if (

                    isset(
                        $context['application']
                    )

                ) {

                    app(
                        \App\Services\Pipeline\PipelineService::class
                    )->shortlist(

                        $context['application']
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | INTERVIEW
            |--------------------------------------------------------------------------
            */

            if (
                $action === 'schedule_interview'
            ) {

                if (

                    isset($context['candidate'])
                    &&
                    isset($context['job'])

                ) {

                    $company =
                        \App\Models\Company::find(
                            $context['job']->company_id
                        );

                    app(
                        \App\Services\Interview\InterviewSchedulingService::class
                    )->schedule(

                        $context['candidate'],

                        $context['job'],

                        $company,

                        now()->addDays(2),

                        'Google Meet'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SEND NOTIFICATION
            |--------------------------------------------------------------------------
            */

            if (
                $action === 'notify_candidate'
            ) {

                if (

                    isset(
                        $context['candidate']
                    )

                ) {

                    app(
                        \App\Services\Notifications\NotificationService::class
                    )->send(

                        $context['candidate']->user,

                        'Your profile has been shortlisted by AI.',

                        'AI Recruitment Update'
                    );
                }
            }
        }
    }
}
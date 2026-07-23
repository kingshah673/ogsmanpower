<?php

namespace App\Services\AI;

use App\Models\Candidate;
use App\Models\CandidateJobMatch;

class AIRecruiterCopilotService
{
    /*
    |--------------------------------------------------------------------------
    | SEARCH CANDIDATES
    |--------------------------------------------------------------------------
    */

    public function search(
        $query
    ) {

        $queryLower =
            strtolower($query);

        /*
        |--------------------------------------------------------------------------
        | GET CANDIDATES
        |--------------------------------------------------------------------------
        */

        $candidates =
            Candidate::query()

            ->with('user')

            ->latest()

            ->get();

        $results = [];

        foreach ($candidates as $candidate) {

            /*
            |--------------------------------------------------------------------------
            | SCORE
            |--------------------------------------------------------------------------
            */

            $score =
                $this->scoreCandidate(

                    $candidate,

                    $queryLower
                );

            if ($score > 0) {

                $results[] = [

                    'candidate'
                        => $candidate,

                    'score'
                        => $score
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SORT
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
    }

    /*
    |--------------------------------------------------------------------------
    | SCORE CANDIDATE
    |--------------------------------------------------------------------------
    */

    protected function scoreCandidate(

        $candidate,

        $query

    ) {

        $score = 0;

        /*
        |--------------------------------------------------------------------------
        | RAW TEXT
        |--------------------------------------------------------------------------
        */

        $text = strtolower(

            ($candidate->profession ?? '')
            .
            ' '
            .
            ($candidate->skills ?? '')
            .
            ' '
            .
            ($candidate->country ?? '')
            .
            ' '
            .
            ($candidate->experience ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | QUERY WORDS
        |--------------------------------------------------------------------------
        */

        $words =
            explode(' ', $query);

        foreach ($words as $word) {

            if (

                strlen($word) > 2
                &&
                str_contains(
                    $text,
                    $word
                )

            ) {

                $score += 15;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ATS BONUS
        |--------------------------------------------------------------------------
        */

        $ats =
            CandidateJobMatch::where(
                'candidate_id',
                $candidate->id
            )
            ->max('score');

        $score += $ats ?? 0;

        return $score;
    }

    /*
    |--------------------------------------------------------------------------
    | FORMAT RESULTS
    |--------------------------------------------------------------------------
    */

    public function format(
        $results
    ) {

        if (!count($results)) {

            return
            'No matching candidates found.';
        }

        $response =
            "🤖 AI Recruiter Results:\n\n";

        foreach (

            array_slice($results,0,10)

            as $item

        ) {

            $candidate =
                $item['candidate'];

            $response .=

                "👤 "

                .

                ($candidate->user->name
                ?? 'Candidate')

                .

                "\n"

                .

                "💼 Profession: "

                .

                ($candidate->profession
                ?? 'N/A')

                .

                "\n"

                .

                "🌍 Country: "

                .

                ($candidate->country
                ?? 'N/A')

                .

                "\n"

                .

                "📈 AI Score: "

                .

                round(
                    $item['score']
                )

                .

                "\n\n";
        }

        return $response;
    }
}
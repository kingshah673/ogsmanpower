<?php

namespace App\Services\Chat;

use App\Models\AIKnowledgeBase;

class KnowledgeBaseService
{
    /*
    |--------------------------------------------------------------------------
    | Search Knowledge Base
    |--------------------------------------------------------------------------
    */

    public function search(
        $message
    ) {

        $message =
            strtolower(trim($message));

        /*
        |--------------------------------------------------------------------------
        | EXACT QUESTION MATCH
        |--------------------------------------------------------------------------
        */

        $exact =
            AIKnowledgeBase::where(
                'status',
                1
            )
            ->whereRaw(
                'LOWER(question) = ?',
                [$message]
            )
            ->first();

        if ($exact) {

            return $exact->answer;
        }

        /*
        |--------------------------------------------------------------------------
        | KEYWORD SEARCH
        |--------------------------------------------------------------------------
        */

        $rows =
            AIKnowledgeBase::where(
                'status',
                1
            )->get();

        foreach ($rows as $row) {

            $keywords =
                explode(
                    ',',
                    strtolower(
                        $row->keywords
                    )
                );

            foreach ($keywords as $keyword) {

                $keyword =
                    trim($keyword);

                if (
                    !$keyword
                ) {
                    continue;
                }

                if (
                    str_contains(
                        $message,
                        $keyword
                    )
                ) {

                    return $row->answer;
                }
            }
        }

        return null;
    }
}
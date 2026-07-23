<?php

namespace App\Services\AI;

class PromptSecurityService
{
    public function clean(
        $message
    ) {

        $blocked = [

            'ignore previous instructions',

            'system prompt',

            'reveal api key',

            'bypass security',

            'developer instructions'
        ];

        $message =
            strtolower($message);

        foreach ($blocked as $bad) {

            if (

                str_contains(
                    $message,
                    $bad
                )

            ) {

                return false;
            }
        }

        return true;
    }
}
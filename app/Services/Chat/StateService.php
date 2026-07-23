<?php

namespace App\Services\Chat;

class StateService
{
    /*
    |--------------------------------------------------------------------------
    | Get Current Step
    |--------------------------------------------------------------------------
    */

    public function current($session)
    {
        return $session->current_step;
    }

    /*
    |--------------------------------------------------------------------------
    | Set Current Step
    |--------------------------------------------------------------------------
    */

    public function set(
    $session,
    $step
) {

    $result = $session->update([
        'current_step' => $step
    ]);

    \Log::info('UPDATE RESULT', [
        'result' => $result,
        'session_id' => $session->id,
        'step' => $step,
        'dirty' => $session->getDirty(),
    ]);

    $session->refresh();

    \Log::info('AFTER REFRESH', [
        'session_id' => $session->id,
        'current_step' => $session->current_step,
        'intent' => $session->intent,
        'status' => $session->status,
    ]);
}

    /*
    |--------------------------------------------------------------------------
    | Check Current Step
    |--------------------------------------------------------------------------
    */

    public function is(
        $session,
        $step
    ) {

        return $session->current_step === $step;
    }

    /*
    |--------------------------------------------------------------------------
    | Reset Conversation
    |--------------------------------------------------------------------------
    */

    public function reset($session)
    {
        $session->update([

            'current_step' => null,

            'intent' => null,

            'data' => null,

            'status' => 'active'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Conversation
    |--------------------------------------------------------------------------
    */

    public function complete($session)
    {
        $session->update([

            'current_step' => 'completed',

            'status' => 'completed'
        ]);
    }
}
<?php

namespace App\Services\AI;

use App\Models\AISetting;

class AISettingsService
{
    /*
    |--------------------------------------------------------------------------
    | GET SETTING
    |--------------------------------------------------------------------------
    */

    public function get(

        $key,

        $default = null

    ) {

        $setting =
            AISetting::where(
                'key',
                $key
            )->first();

        return
            $setting->value
            ?? $default;
    }

    /*
    |--------------------------------------------------------------------------
    | SET VALUE
    |--------------------------------------------------------------------------
    */

    public function set(

        $key,

        $value

    ) {

        return AISetting::updateOrCreate(

            [

                'key'
                    => $key
            ],

            [

                'value'
                    => $value
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BOOLEAN
    |--------------------------------------------------------------------------
    */

    public function enabled(
        $key
    ) {

        return
            $this->get(
                $key
            ) == 1;
    }
}
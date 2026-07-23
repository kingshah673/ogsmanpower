<?php

namespace App\Services\Chat;

use Illuminate\Support\Str;

class IntentService
{
    /*
    |--------------------------------------------------------------------------
    | Detect Intent
    |--------------------------------------------------------------------------
    */

    public function detect(
        $message
    ) {

        $msg =
            strtolower(
                trim($message)
            );
            
            if (

    in_array(

        trim($msg),

        ['1','2','3','4','5','6']

    )

) {

    return 'menu_option';
}

        /*
        |--------------------------------------------------------------------------
        | APPLY JOB
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'apply job',

                'apply for',

                'job apply',

                'want a job',

                'need a job',

                'find job',

                'electrician job',

                'driver job',

                'work permit',

                'job abroad',

                'vacancy'

            ])

        ) {

            return 'apply_job';
        }
        
        /*
        |--------------------------------------------------------------------------
        |  REGISTRATION
        |--------------------------------------------------------------------------
        */
        
        if (

    Str::contains($msg, [

        'register',

        'sign up',

        'create account'

    ])

) {

    return 'registration';
}

        /*
        |--------------------------------------------------------------------------
        | CANDIDATE REGISTRATION
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'candidate',

                'register candidate',

                'create candidate account',

                'job seeker',

                'looking for work',

                'upload cv',

                'submit cv',

                'resume'

            ])

        ) {

            return 'candidate_register';
        }

        /*
        |--------------------------------------------------------------------------
        | COMPANY REGISTRATION
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'company',

                'hire workers',

                'hire manpower',

                'need staff',

                'recruit employees',

                'post job',

                'company registration',

                'employer'

            ])

        ) {

            return 'company_register';
        }

        /*
        |--------------------------------------------------------------------------
        | AGENCY REGISTRATION
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'agency',

                'recruitment agency',

                'sub agent',

                'agent account',

                'agency registration',

                'overseas employment'

            ])

        ) {

            return 'agency_register';
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK APPLICATION STATUS
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'application status',

                'check status',

                'my application',

                'job status',

                'visa status',

                'track application'

            ])

        ) {

            return 'check_application_status';
        }

        /*
        |--------------------------------------------------------------------------
        | POST JOB
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'post job',

                'create vacancy',

                'add job',

                'publish job',

                'need workers',

                'job posting'

            ])

        ) {

            return 'post_job';
        }
        /*
        |--------------------------------------------------------------------------
        | POST A JOB
        |--------------------------------------------------------------------------
        */
        
        if (

    Str::contains($msg,[

        'post job',

        'create vacancy',

        'add job',

        'need workers',

        'hiring',

        'vacancy'

    ])

) {

    return 'post_job';
}

        /*
        |--------------------------------------------------------------------------
        | PASSPORT / DOCUMENT UPLOAD
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'passport',

                'upload passport',

                'visa document',

                'upload documents',

                'medical slip'

            ])

        ) {

            return 'upload_documents';
        }

        /*
        |--------------------------------------------------------------------------
        | CONTRACT FLOW
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'contract',

                'accept contract',

                'reject contract',

                'agreement'

            ])

        ) {

            return 'contract_flow';
        }

        /*
        |--------------------------------------------------------------------------
        | INTERVIEW FLOW
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'interview',

                'interview date',

                'schedule interview',

                'online interview'

            ])

        ) {

            return 'interview_flow';
        }
        
         /*
        |--------------------------------------------------------------------------
        | HAND OVER
        |--------------------------------------------------------------------------
        */
        
        if (

    Str::contains($msg,[

        'human',

        'support',

        'agent',

        'consultant',

        'help me',

        'customer support'

    ])

) {

    return 'human_handover';
}

        /*
        |--------------------------------------------------------------------------
        | GREETING
        |--------------------------------------------------------------------------
        */

        if (
    Str::contains($msg, [
        'hello',
        'hi',
        'hey',
        'assalamualaikum',
        'salam',
        'start',
        'menu'
    ])
) {
    return 'main_menu';
}

        /*
        |--------------------------------------------------------------------------
        | THANK YOU
        |--------------------------------------------------------------------------
        */

        if (

            Str::contains($msg, [

                'thanks',

                'thank you',

                'jazakallah'

            ])

        ) {

            return 'thanks';
        }
        
        if (

    Str::contains($msg, [

        'visa',

        'work visa',

        'study visa',

        'visit visa',

        'immigration',

        'documents',

        'passport',

        'requirements'

    ])

) {

    return 'visa_service';
}
if (

    Str::contains($msg, [

        'hire staff',

        'hire workers',

        'need workers',

        'manpower',

        'recruitment',

        'employees'

    ])

) {

    return 'hire_staff';
}

        /*
        |--------------------------------------------------------------------------
        | DEFAULT
        |--------------------------------------------------------------------------
        */

        return 'knowledge_base';
    }
}
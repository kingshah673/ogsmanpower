<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForwardCandidateMail extends Mailable
{
    use SerializesModels;

    public $candidate;
    public $job_id;
    public $docs;

    public function __construct($candidate,$job_id,$docs)
    {
        $this->candidate = $candidate;
        $this->job_id = $job_id;
        $this->docs = $docs;
    }

    private function localFile($url)
    {
        if(!$url) return null;

        $path = preg_replace('#^https?://[^/]+#','',$url);
        $file = public_path($path);

        return file_exists($file) ? $file : null;
    }

    public function build()
    {
        $mail = $this->from('no-reply@careerworkforce.com','Career Work Force Recruitment Team')
        ->subject('Candidate Profile: '.$this->candidate->user->name)
        ->view('emails.forward_candidate');

        /*
        |--------------------------------------------------------------------------
        | Attach CV
        |--------------------------------------------------------------------------
        */

        if(in_array('cv',$this->docs)){

            $pdf = app()->call(
                [app(\App\Http\Controllers\Website\CompanyController::class),'downloadApplicantResume'],
                [
                    'candidate_id'=>$this->candidate->id,
                    'job_id'=>$this->job_id
                ]
            );

            $pdfPath = storage_path('app/cv_'.$this->candidate->id.'.pdf');

            file_put_contents($pdfPath,$pdf->getOriginalContent());

            $mail->attach($pdfPath);
        }

        /*
        |--------------------------------------------------------------------------
        | Attach Photo
        |--------------------------------------------------------------------------
        */

        if(in_array('photo',$this->docs)){

            $photo = $this->localFile($this->candidate->photo ?? null);

            if($photo){
                $mail->attach($photo);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Attach Passport
        |--------------------------------------------------------------------------
        */

        if(in_array('passport',$this->docs)){

            $passport = $this->localFile($this->candidate->passport ?? null);

            if($passport){
                $mail->attach($passport);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Attach Video
        |--------------------------------------------------------------------------
        */

        if(in_array('video',$this->docs)){

            $video = $this->localFile($this->candidate->video ?? null);

            if($video){
                $mail->attach($video);
            }
        }

        return $mail;
    }
}
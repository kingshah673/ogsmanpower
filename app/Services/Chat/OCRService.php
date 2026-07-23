<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Http;

class OCRService
{
    /*
    |--------------------------------------------------------------------------
    | Read Passport
    |--------------------------------------------------------------------------
    */

    public function scan(
        $path
    ) {

        $fullPath =
            storage_path(
                'app/public/' . $path
            );

        try {

            $response =
                Http::timeout(90)

                ->attach(

                    'file',

                    file_get_contents(
                        $fullPath
                    ),

                    basename(
                        $fullPath
                    )
                )

                ->post(

                    config('services.ocr.endpoint'),

                    [

                        'apikey'
                            => config('services.ocr.key'),

                        'language'
                            => 'eng',

                        'OCREngine'
                            => 2
                    ]
                );

            $json =
                $response->json();

            $text =
                $json['ParsedResults'][0]['ParsedText']
                ?? '';

            preg_match(

                '/[A-Z]{1,2}[0-9]{6,8}/',

                strtoupper($text),

                $passport

            );

            return [

                'passport_no'
                    => $passport[0]
                    ?? null,

                'raw_text'
                    => $text
            ];

        } catch (\Exception $e) {

            \Log::error(
                $e->getMessage()
            );

            return null;
        }
    }
}
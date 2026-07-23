<?php

namespace App\Services\Chat;

use Smalot\PdfParser\Parser;

class CandidateParserService
{
    /*
    |--------------------------------------------------------------------------
    | Parse CV
    |--------------------------------------------------------------------------
    */

    public function parse(
        $path
    ) {

        $fullPath =
            storage_path(
                'app/public/' . $path
            );

        $text = '';

        try {

            $parser = new Parser();

            $pdf =
                $parser->parseFile(
                    $fullPath
                );

            $text =
                $pdf->getText();

        } catch (\Exception $e) {

            \Log::error(
                $e->getMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | EXTRACT EMAIL
        |--------------------------------------------------------------------------
        */

        preg_match(

            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',

            $text,

            $emails

        );

        /*
        |--------------------------------------------------------------------------
        | EXTRACT PHONE
        |--------------------------------------------------------------------------
        */

        preg_match(

            '/(\+92|92|0)?3[0-9]{9}/',

            preg_replace('/\s+/', '', $text),

            $phones

        );

        /*
        |--------------------------------------------------------------------------
        | EXTRACT NAME
        |--------------------------------------------------------------------------
        */

        $lines =
            preg_split(
                "/\r\n|\n|\r/",
                $text
            );

        $name = null;

        foreach ($lines as $line) {

            $line = trim($line);

            if (

                strlen($line) > 4
                &&
                strlen($line) < 35
                &&
                preg_match('/^[A-Za-z ]+$/', $line)

            ) {

                $name = $line;

                break;
            }
        }

        return [

            'name'
                => $name,

            'email'
                => $emails[0]
                ?? null,

            'phone'
                => $phones[0]
                ?? null,

            'raw_text'
                => $text
        ];
    }
}
<?php

namespace App\Services\Website;

use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Candidate;

use App\Services\OpenAI\OpenAIService;
use App\Services\AI\GPTCVParserService;

class FileReaderService
{
    protected $openai;
    protected $gptParser;

    public function __construct(
        OpenAIService $openai,
        GPTCVParserService $gptParser
    ) {
        $this->openai    = $openai;
        $this->gptParser = $gptParser;
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN HANDLER
    |--------------------------------------------------------------------------
    */

    public function process($file)
    {
        try {
            $ext = strtolower($file->getClientOriginalExtension());

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return $this->readPassport($file);
            }

            return $this->readCV($file);

        } catch (Exception $e) {
            return 'Unable to process file.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | READ CV — extract text then run through GPTCVParserService
    |--------------------------------------------------------------------------
    */

    private function readCV($file)
    {
        $text = $this->extractText($file);

        if (!$text) {
            return 'Unable to read CV text.';
        }

        $data = $this->gptParser->parse($text);

        if (!$data) {
            return 'CV uploaded but parsing failed.';
        }

        $this->saveCandidate($data);

        $reply  = "CV parsed successfully.\n\n";
        $reply .= "Name: "  . ($data['Full Name']  ?? '-') . "\n";
        $reply .= "Skills: ". (is_array($data['Skills'] ?? null)
            ? implode(', ', $data['Skills'])
            : ($data['Skills'] ?? '-')) . "\n\n";
        $reply .= $this->suggestJobs();

        return $reply;
    }

    /*
    |--------------------------------------------------------------------------
    | READ PASSPORT — base64 → GPT-4o vision → structured data
    |--------------------------------------------------------------------------
    */

    private function readPassport($file)
    {
        $base64 = base64_encode(file_get_contents($file->getRealPath()));
        $mime   = $file->getMimeType();

        $systemPrompt = 'Read this passport or ID image and return ONLY valid JSON with these fields:
{
  "name": "",
  "passport_number": "",
  "cnic": "",
  "nationality": "",
  "dob": "",
  "issue_date": "",
  "expiry_date": ""
}';

        $content = $this->openai->vision(
            $base64,
            $mime,
            $systemPrompt,
            'Extract document details from this passport/ID image.',
            null,
            'passport_vision'
        );

        if (!$content) {
            return 'Unable to read passport.';
        }

        $content = str_replace(['```json', '```'], '', trim($content));
        $data    = json_decode($content, true);

        if (!$data) {
            return 'Passport parsing failed.';
        }

        $this->savePassportData($data);

        $reply  = "Passport uploaded successfully.\n\n";
        $reply .= "Name: "         . ($data['name']            ?? '-') . "\n";
        $reply .= "Passport No: "  . ($data['passport_number'] ?? '-') . "\n";
        $reply .= "Nationality: "  . ($data['nationality']     ?? '-') . "\n";
        $reply .= "Expiry: "       . ($data['expiry_date']     ?? '-') . "\n";

        return $reply;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function extractText($file)
    {
        return file_get_contents($file->getRealPath());
    }

    private function saveCandidate($data)
    {
        $name  = $data['Full Name'] ?? 'Candidate User';
        $email = 'cv' . time() . '@temp.com';
        $phone = null;

        $user = User::create([
            'name'              => $name,
            'username'          => 'cv' . rand(1000, 9999),
            'email'             => $email,
            'phone'             => $phone,
            'whatsapp'          => $phone,
            'password'          => Hash::make('12345678'),
            'role'              => 'candidate',
            'email_verified_at' => now(),
        ]);

        Candidate::updateOrCreate(
            ['user_id' => $user->id],
            ['bio' => null]
        );

        return $user;
    }

    private function savePassportData($data)
    {
        $passport = $data['passport_number'] ?? null;

        if (!$passport) return;

        $candidate = Candidate::where('passport_number', $passport)->first();

        if ($candidate) {
            $candidate->update([
                'passport_issue_date'  => $data['issue_date']  ?? null,
                'passport_expiry_date' => $data['expiry_date'] ?? null,
                'dob'                  => $data['dob']         ?? null,
                'nationality'          => $data['nationality'] ?? null,
            ]);
            return;
        }

        $user = User::create([
            'name'              => $data['name'] ?? 'Candidate User',
            'username'          => 'pp' . rand(1000, 9999),
            'email'             => 'passport' . time() . '@temp.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'candidate',
            'email_verified_at' => now(),
        ]);

        Candidate::create([
            'user_id'              => $user->id,
            'passport_number'      => $passport,
            'passport_issue_date'  => $data['issue_date']  ?? null,
            'passport_expiry_date' => $data['expiry_date'] ?? null,
            'dob'                  => $data['dob']         ?? null,
            'nationality'          => $data['nationality'] ?? null,
        ]);
    }

    private function suggestJobs(): string
    {
        return "Reply 'Need jobs' to see latest vacancies.";
    }
}

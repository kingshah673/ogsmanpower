<?php

namespace App\Services\AI;

use App\Services\OpenAI\OpenAIService;

class GPTCVParserService
{
    protected $openai;

    protected CandidateFormSchemaService $formSchema;

    protected CvDataNormalizer $normalizer;

    public function __construct(
        OpenAIService $openai,
        CandidateFormSchemaService $formSchema,
        CvDataNormalizer $normalizer
    ) {
        $this->openai = $openai;
        $this->formSchema = $formSchema;
        $this->normalizer = $normalizer;
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE CV
    |--------------------------------------------------------------------------
    */

    public function parse($cvText)
    {
        $formContext = $this->formSchema->cvPromptContext();

        $prompt = <<<PROMPT
CV parser schema v3. Read the ENTIRE CV carefully.

CRITICAL — contact block (top of CV):
- Extract phone/mobile/cell numbers from the header or contact section (never skip). Example: +92-316-3272572
- Extract email, and any LinkedIn/GitHub/portfolio URL as website (e.g. "GitHub | LinkedIn" → pick the first full URL you can infer, or linkedin.com/in/... if visible).
- Extract city and country from the address line (e.g. "Rawalpindi, Pakistan" → city=Rawalpindi, country=Pakistan).
- languages: REQUIRED when the CV mentions any spoken language. Scan the entire CV for a "Languages" / "Language Skills" section AND for language names in the header or summary (e.g. English, Urdu, Arabic). List every human spoken language found. Do NOT put programming languages (PHP, Python, JavaScript) under languages — those belong in skills.

CRITICAL — work history:
- Include EVERY role under PROFESSIONAL EXPERIENCE as work_experience entries.
- Use designation for job title (position/title/role are aliases).
- Set currently_working true when end is null or role says Present.

CRITICAL — education:
- education_history.level is REQUIRED — use Bachelor Degree, Master Degree, etc.

CRITICAL — links:
- social_links: [{platform, url}] for LinkedIn, GitHub, and every https URL in Projects (platform: linkedin|github|other).
- jobs: merge professional titles AND roles held (up to 6).
- industries: infer at least 3 from employers, skills, and sector when possible.

Passport / CNIC / national ID: only fill if explicitly written on the CV (most resumes omit these — use null).

{$formContext}

Return JSON only.

CV TEXT:
PROMPT;

        $prompt .= "\n\n" . mb_substr($cvText, 0, 14000);

        $json = $this->openai->askJson(
            $prompt,
            'You are a precise CV parser for a job portal. Output valid JSON only. Never omit phone or email when they appear in the CV.',
            'cv_parse',
            auth()->id(),
            config('services.openai.cv_model')
        );

        if (! $json) {
            return ['is_cv' => false];
        }

        $data = $this->normalizer->normalize($json);

        return $this->normalizer->supplementLanguages($data, $cvText);
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE BIO FROM PROFILE DATA
    |--------------------------------------------------------------------------
    */

    public function generateBio(array $profile, ?string $currentBio = null): ?string
    {
        $context = json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $refine = $currentBio
            ? "Refine or expand this existing bio when useful:\n\"{$currentBio}\"\n\n"
            : '';

        $prompt = <<<PROMPT
Write a professional third-person bio (3–4 sentences, max 120 words) for a job seeker profile.

Rules:
- Use ONLY facts from the profile data below — do not invent employers, degrees, or skills.
- If data is sparse, write a concise bio from whatever is available (name, profession, location, skills).
- Plain text only — no HTML, bullet points, or headings.
- Tone: confident, professional, suitable for a recruitment portal.

{$refine}Profile data:
{$context}

Return JSON: {"bio": "..."}
PROMPT;

        $json = $this->openai->askJson(
            $prompt,
            'You write concise professional candidate bios. Return valid JSON only.',
            'bio_generate',
            auth()->id(),
            config('services.openai.cv_model')
        );

        $bio = trim((string) ($json['bio'] ?? ''));

        return $bio !== '' ? $bio : null;
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE PASSPORT
    |--------------------------------------------------------------------------
    */

    public function parsePassport(
        string $ocrText
    ): array {

        $prompt = "
You are a passport data extraction system.

Extract the following fields from this raw passport OCR text and return ONLY valid JSON.
Use null for any field not found.

Fields:
- surname
- given_names
- passport_number
- nationality
- date_of_birth (YYYY-MM-DD)
- gender (return exactly: male, female, or null — never abbreviate as M or F)
- place_of_birth
- date_of_issue (YYYY-MM-DD)
- date_of_expiry (YYYY-MM-DD)
- place_of_issue
- national_id (national identity card number, CNIC, personal number, or citizen ID shown on the passport — or null)
- mrz_line1
- mrz_line2

OCR Text:
" . $ocrText;

        $response = $this->openai->ask($prompt);

        if (!$response) {
            return [];
        }

        $response = trim($response);
        $response = str_replace('```json', '', $response);
        $response = str_replace('```', '', $response);

        $json = json_decode($response, true);

        return $json ?? [];
    }
}
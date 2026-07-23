<?php

namespace App\Services\AI;

use App\Services\OpenAI\OpenAIService;

class GPTJobParserService
{
    public function __construct(
        protected OpenAIService $openai,
        protected JobFormSchemaService $formSchema,
        protected JobDataNormalizer $normalizer
    ) {}

    public function parse(string $documentText): array
    {
        $formContext = $this->formSchema->jobPromptContext();

        $prompt = <<<PROMPT
Job posting parser v2. Read the ENTIRE document carefully — it may be a PDF job ad, demand letter, newspaper clipping, flyer, or image OCR text in any language.

CRITICAL:
- Detect ALL distinct job positions. Demand letters with position tables = one job per row. Two-column flyers = one job per column. Single role ads = one job in jobs[].
- Put contract terms, working hours, food/accommodation, visa, and employer-wide benefits in shared.employment_terms and shared.benefits.
- education: null when not mentioned — never invent education requirements.
- description should be employer-ready HTML using <p> and <ul><li> — not markdown.
- For Arabic text, fill job_title_ar and description_ar; also provide English in job_title and description when you can translate.

{$formContext}

Return JSON only.

DOCUMENT TEXT:
PROMPT;

        $prompt .= "\n\n" . mb_substr($documentText, 0, 24000);

        $json = $this->openai->askJson(
            $prompt,
            'You are a precise job-ad parser for an employer job portal. Output valid JSON only.',
            'job_posting_parse',
            auth()->id(),
            config('services.openai.cv_model')
        );

        if (! $json) {
            return ['is_job_posting' => false];
        }

        return $this->normalizer->normalize($json);
    }
}

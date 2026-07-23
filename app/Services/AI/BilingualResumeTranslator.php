<?php

namespace App\Services\AI;

use App\Models\Candidate;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stichoza\GoogleTranslate\GoogleTranslate;

/**
 * Translates resume text for bilingual CV output.
 * Warms a cache in one batch before rendering to avoid server timeouts.
 */
class BilingualResumeTranslator
{
    protected array $cache = [];

    /** @return list<string> */
    public static function staticLabels(): array
    {
        return [
            'Mobile', 'Email', 'Address', 'Nationality', 'Date of Birth', 'yrs',
            'ID / Passport', 'Marital Status', 'Professional Summary',
            'Graduation Date', 'Qualifications', 'Experience', 'Present',
            'Handled', 'responsibilities with precision and professionalism',
            'Certifications & Courses', 'Skills', 'Languages', 'Proficient',
            'Passport & Documents', 'Passport No.', 'CNIC', 'Issue Date',
            'Expiry Date', 'Place of Issue', 'Attachments', 'Passport',
            'All rights reserved',
        ];
    }

    /**
     * Pre-translate all resume strings in bulk before the blade renders.
     */
    public function warmCacheForResume(Candidate $candidate, string $targetLang, ?string $jobTitle = null): void
    {
        if ($targetLang === '' || $targetLang === 'en') {
            return;
        }

        $strings = $this->collectStrings($candidate, $jobTitle);
        $this->batchTranslate($strings, $targetLang);
    }

    public function translate(?string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        $text = trim((string) $text);
        if ($text === '' || $targetLang === '' || $targetLang === $sourceLang) {
            return $text;
        }

        $key = md5($targetLang.'|'.$text);
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $cacheKey = 'bilingual_cv:'.$key;
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $this->cache[$key] = $cached;
        }

        try {
            $translated = (new GoogleTranslate($targetLang, $sourceLang))->translate($text);
            if ($translated && trim($translated) !== '') {
                Cache::put($cacheKey, $translated, now()->addDays(30));

                return $this->cache[$key] = $translated;
            }
        } catch (\Throwable $e) {
            Log::info('Bilingual CV: Google Translate unavailable, trying AI', [
                'lang' => $targetLang,
                'error' => $e->getMessage(),
            ]);
        }

        if (empty(config('services.openai.key'))) {
            return $text;
        }

        try {
            $prompt = <<<PROMPT
Translate the following recruitment/CV text to {$targetLang}.
Keep names, numbers, dates, URLs, and company names unchanged.
Return JSON only: {"translation": "..."}

Text:
{$text}
PROMPT;

            $json = app(OpenAIService::class)->askJson(
                $prompt,
                'You translate CV content accurately. Return valid JSON only.',
                'bilingual_cv',
                auth()->id()
            );

            $translated = trim((string) ($json['translation'] ?? ''));
            if ($translated !== '') {
                Cache::put($cacheKey, $translated, now()->addDays(30));

                return $this->cache[$key] = $translated;
            }
        } catch (\Throwable $e) {
            Log::warning('Bilingual CV: AI translation failed', ['error' => $e->getMessage()]);
        }

        return $text;
    }

    /** @return list<string> */
    protected function collectStrings(Candidate $candidate, ?string $jobTitle = null): array
    {
        $strings = self::staticLabels();

        if ($jobTitle) {
            $strings[] = $jobTitle;
            $strings[] = strtoupper($jobTitle);
        }

        foreach (['country', 'marital_status', 'bio', 'title', 'place_of_issue'] as $field) {
            if (! empty($candidate->{$field})) {
                $strings[] = (string) $candidate->{$field};
            }
        }

        foreach ($candidate->skills ?? [] as $skill) {
            if (! empty($skill->name)) {
                $strings[] = $skill->name;
            }
        }

        foreach ($candidate->languages ?? [] as $lang) {
            if (! empty($lang->name)) {
                $strings[] = $lang->name;
            }
            if (! empty($lang->level)) {
                $strings[] = $lang->level;
            }
        }

        foreach ($candidate->educations ?? [] as $edu) {
            foreach (['degree', 'level'] as $field) {
                if (! empty($edu->{$field})) {
                    $strings[] = (string) $edu->{$field};
                }
            }
        }

        foreach ($candidate->experiences ?? [] as $exp) {
            foreach (['company', 'designation', 'department', 'responsibilities'] as $field) {
                if (! empty($exp->{$field})) {
                    $strings[] = (string) $exp->{$field};
                }
            }
            if (! empty($exp->designation)) {
                $strings[] = strtolower((string) $exp->designation);
            }
        }

        foreach ($candidate->attributes ?? [] as $attr) {
            foreach (['attribute_name', 'attribute_value'] as $field) {
                if (! empty($attr->{$field})) {
                    $strings[] = (string) $attr->{$field};
                }
            }
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($s) => trim((string) $s),
            $strings
        ))));
    }

    /** @param list<string> $strings */
    protected function batchTranslate(array $strings, string $targetLang, string $sourceLang = 'en'): void
    {
        $pending = [];
        foreach ($strings as $text) {
            $key = md5($targetLang.'|'.$text);
            if (isset($this->cache[$key])) {
                continue;
            }
            $stored = Cache::get('bilingual_cv:'.$key);
            if (is_string($stored) && $stored !== '') {
                $this->cache[$key] = $stored;
                continue;
            }
            $pending[] = $text;
        }

        if ($pending === []) {
            return;
        }

        if (! empty(config('services.openai.key'))) {
            foreach (array_chunk($pending, 40) as $chunk) {
                $this->batchTranslateViaOpenAi($chunk, $targetLang, $sourceLang);
            }

            return;
        }

        $this->batchTranslateViaGoogle($pending, $targetLang, $sourceLang);
    }

    /** @param list<string> $strings */
    protected function batchTranslateViaGoogle(array $strings, string $targetLang, string $sourceLang = 'en'): void
    {
        $separator = "\n<<CWSEP>>\n";

        foreach (array_chunk($strings, 25) as $chunk) {
            try {
                $joined = implode($separator, $chunk);
                $translated = (new GoogleTranslate($targetLang, $sourceLang))->translate($joined);
                $parts = preg_split('/\s*<<CWSEP>>\s*/', (string) $translated) ?: [];

                if (count($parts) === count($chunk)) {
                    foreach ($chunk as $i => $original) {
                        $value = trim((string) ($parts[$i] ?? ''));
                        if ($value !== '') {
                            $key = md5($targetLang.'|'.$original);
                            $this->cache[$key] = $value;
                            Cache::put('bilingual_cv:'.$key, $value, now()->addDays(30));
                        }
                    }
                    continue;
                }
            } catch (\Throwable $e) {
                Log::info('Bilingual CV: Google batch failed, falling back per string', [
                    'error' => $e->getMessage(),
                ]);
            }

            foreach ($chunk as $text) {
                $this->translate($text, $targetLang, $sourceLang);
            }
        }
    }

    /** @param list<string> $strings */
    protected function batchTranslateViaOpenAi(array $strings, string $targetLang, string $sourceLang = 'en'): void
    {
        if ($strings === []) {
            return;
        }

        try {
            $payload = json_encode(['items' => array_values($strings)], JSON_UNESCAPED_UNICODE);
            $prompt = <<<PROMPT
Translate every string in the JSON "items" array from {$sourceLang} to {$targetLang}.
Rules:
- Keep person names, company names, numbers, dates, URLs, and passport/CNIC values unchanged.
- Return JSON only with the same number of items in the same order: {"items":["...", "..."]}

{$payload}
PROMPT;

            $json = app(OpenAIService::class)->askJson(
                $prompt,
                'You translate CV/resume content accurately. Return valid JSON only.',
                'bilingual_cv_batch',
                auth()->id()
            );

            $translated = $json['items'] ?? [];
            if (! is_array($translated) || count($translated) !== count($strings)) {
                throw new \RuntimeException('Batch translation returned unexpected item count.');
            }

            foreach ($strings as $i => $original) {
                $value = trim((string) ($translated[$i] ?? ''));
                if ($value !== '') {
                    $key = md5($targetLang.'|'.$original);
                    $this->cache[$key] = $value;
                    Cache::put('bilingual_cv:'.$key, $value, now()->addDays(30));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Bilingual CV: batch AI translation failed, falling back per string', [
                'error' => $e->getMessage(),
            ]);

            foreach ($strings as $text) {
                if (! isset($this->cache[md5($targetLang.'|'.$text)])) {
                    $this->translate($text, $targetLang, $sourceLang);
                }
            }
        }
    }
}

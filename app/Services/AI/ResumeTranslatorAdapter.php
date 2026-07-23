<?php

namespace App\Services\AI;

use App\Models\Candidate;

/**
 * Drop-in replacement for GoogleTranslate in bilingual resume blades.
 */
class ResumeTranslatorAdapter
{
    public function __construct(protected string $targetLang) {}

    public function translate(?string $text): string
    {
        return app(BilingualResumeTranslator::class)->translate($text, $this->targetLang);
    }

    public function warmCache(Candidate $candidate, ?string $jobTitle = null): void
    {
        app(BilingualResumeTranslator::class)->warmCacheForResume($candidate, $this->targetLang, $jobTitle);
    }
}

<?php

namespace App\Services\AI;

use App\Models\EducationTranslation;
use App\Models\ExperienceTranslation;
use Carbon\Carbon;

class CvDataNormalizer
{
    /**
     * Clean and enrich raw GPT CV JSON before save / UI fill.
     */
    public function normalize(array $data): array
    {
        if (empty($data['is_cv'])) {
            return $data;
        }

        if (empty($data['country']) && ! empty($data['location']) && is_string($data['location'])) {
            $parts = array_map('trim', explode(',', $data['location']));
            if (count($parts) >= 1 && empty($data['city'])) {
                $data['city'] = $parts[0];
            }
            if (count($parts) >= 2 && empty($data['country'])) {
                $data['country'] = end($parts);
            }
        }

        if (empty($data['country']) && ! empty($data['address']) && is_array($data['address'])) {
            $data['country'] = $data['country'] ?? ($data['address']['country'] ?? null);
            $data['state']   = $data['state'] ?? ($data['address']['state'] ?? $data['address']['region'] ?? null);
            $data['city']    = $data['city'] ?? ($data['address']['city'] ?? $data['address']['district'] ?? null);
        }

        $data['country'] = $this->clean($data['country'] ?? null);
        $data['state']   = $this->clean($data['state'] ?? null);
        $data['city']    = $this->clean($data['city'] ?? null);

        $data['first_name'] = $this->clean($data['first_name'] ?? null);
        $data['last_name']  = $this->clean($data['last_name'] ?? null);
        $data['email']      = $this->cleanEmail($data['email'] ?? null);
        $data['phone']      = $this->cleanPhone($data['phone'] ?? null);
        $data['whatsapp']   = $this->cleanPhone($data['whatsapp'] ?? null);

        if (! $data['phone'] && $data['whatsapp']) {
            $data['phone'] = $data['whatsapp'];
        }
        if (! $data['whatsapp'] && $data['phone']) {
            $data['whatsapp'] = $data['phone'];
        }

        $data['website'] = $this->cleanUrl($data['website'] ?? null);
        $data['gender']  = $this->normalizeGender($data['gender'] ?? null);
        $data['marital_status'] = $this->normalizeMarital($data['marital_status'] ?? null);
        $data['date_of_birth']  = $this->normalizeDob($data['date_of_birth'] ?? null);

        $data['skills']     = $this->cleanList($data['skills'] ?? []);
        $data['languages']  = $this->mergeLanguageLists($data['languages'] ?? [], $data['skills'] ?? [], $data['bio'] ?? null);
        $data['jobs']       = $this->cleanList($data['jobs'] ?? []);
        $data['industries'] = $this->cleanList($data['industries'] ?? []);
        $data['titles']     = $this->cleanList($data['titles'] ?? []);

        if (empty($data['profession']) && ! empty($data['titles'][0])) {
            $data['profession'] = $data['titles'][0];
        }

        $data['experience_level'] = $this->normalizeExperienceLevel(
            $data['experience_level'] ?? null,
            $data['work_experience'] ?? []
        );

        $data['education_level'] = $this->normalizeEducationLevel($data['education_level'] ?? null);

        $data['work_experience']   = $this->normalizeWorkExperience($data['work_experience'] ?? []);
        $data['education_history'] = $this->normalizeEducationHistory(
            $data['education_history'] ?? [],
            $data['education_level'] ?? null
        );
        $data['jobs']       = $this->mergeJobTitles($data['jobs'] ?? [], $data['titles'] ?? [], $data['work_experience']);
        $data['industries'] = $this->expandIndustries($data['industries'] ?? [], $data['skills'] ?? [], $data['work_experience']);
        $data['social_links'] = $this->normalizeSocialLinks(
            $data['social_links'] ?? [],
            $data['website'] ?? null,
            $data['portfolio_urls'] ?? []
        );

        if (empty($data['state']) && ! empty($data['city'])) {
            $data['state'] = $this->inferState($data['city'], $data['country'] ?? null);
        }

        foreach (['passport_issue_date', 'passport_expiry_date'] as $dateKey) {
            if (! empty($data[$dateKey])) {
                $data[$dateKey] = $this->normalizeDob($data[$dateKey]) ?? $data[$dateKey];
            }
        }

        return $data;
    }

    /**
     * Fill empty languages from raw CV text (GPT often omits them).
     */
    public function supplementLanguages(array $data, string $cvText): array
    {
        if (! empty($data['languages'])) {
            return $data;
        }

        $fromText = $this->extractLanguagesFromText($cvText);
        if (! empty($fromText)) {
            $data['languages'] = $this->mergeLanguageLists($fromText, $data['skills'] ?? [], $data['bio'] ?? null);
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    public function extractLanguagesFromText(string $cvText): array
    {
        $cvText = trim($cvText);
        if ($cvText === '') {
            return [];
        }

        $humanLanguages = [
            'english', 'urdu', 'arabic', 'hindi', 'french', 'german', 'spanish', 'punjabi', 'sindhi',
            'pashto', 'bengali', 'chinese', 'mandarin', 'cantonese', 'turkish', 'portuguese', 'italian',
            'russian', 'malay', 'indonesian', 'tamil', 'telugu', 'marathi', 'gujarati', 'korean',
            'japanese', 'dutch', 'polish', 'romanian', 'persian', 'farsi', 'balochi', 'saraiki',
            'hungarian', 'swahili', 'tagalog', 'filipino', 'thai', 'vietnamese', 'nepali', 'sinhala',
        ];

        $programming = [
            'php', 'python', 'javascript', 'typescript', 'java', 'ruby', 'rust', 'kotlin', 'swift',
            'scala', 'perl', 'matlab', 'sql', 'html', 'css', 'react', 'angular', 'vue', 'laravel',
            'node', 'nodejs', 'csharp', 'c++', 'c#', '.net',
        ];

        $found = [];

        if (preg_match(
            '/(?:languages?|language\s+skills?|linguistic\s+skills?|spoken\s+languages?)\s*[:\-]?\s*(.+?)(?:\n\s*\n|\n[A-Z][A-Za-z\s]{2,20}:|\z)/isu',
            $cvText,
            $match
        )) {
            foreach (preg_split('/[,|\/;•·]+/', $match[1]) as $token) {
                $token = trim(preg_replace('/\([^)]*\)/', '', $token));
                if ($token === '') {
                    continue;
                }
                $tokenLower = mb_strtolower($token);
                foreach ($humanLanguages as $lang) {
                    if ($this->languageTokenMatches($tokenLower, $lang)) {
                        $found[] = ucfirst($lang);
                    }
                }
            }
        }

        $textLower = mb_strtolower($cvText);
        foreach ($humanLanguages as $lang) {
            if (preg_match('/\b'.preg_quote($lang, '/').'\b/u', $textLower)) {
                $found[] = ucfirst($lang);
            }
        }

        $found = array_values(array_filter(
            $this->cleanList($found),
            fn ($name) => ! in_array(mb_strtolower($name), $programming, true)
        ));

        return $found;
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function cleanEmail(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    private function cleanPhone(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $value);

        return strlen($digits) >= 7 ? $value : null;
    }

    private function cleanUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }

        return $value;
    }

    private function normalizeGender(?string $value): ?string
    {
        $g = strtolower(trim((string) $value));

        return match (true) {
            in_array($g, ['m', 'male', 'man'], true) => 'male',
            in_array($g, ['f', 'female', 'woman'], true) => 'female',
            in_array($g, ['transgender', 'trans', 'other'], true) => 'transgender',
            default => null,
        };
    }

    private function normalizeMarital(?string $value): ?string
    {
        $m = strtolower(trim((string) $value));

        return match (true) {
            str_contains($m, 'married') => 'married',
            str_contains($m, 'single') || str_contains($m, 'unmarried') => 'single',
            default => null,
        };
    }

    private function normalizeDob(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'm/d/Y', 'd.m.Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->format('d-m-Y');
            } catch (\Exception $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('d-m-Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    /** @return list<string> */
    private function cleanList($items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
    }

    /** @param list<string> $languages @param list<string> $skills */
    private function mergeLanguageLists(array $languages, array $skills, ?string $bio = null): array
    {
        $known = [
            'english', 'urdu', 'arabic', 'hindi', 'french', 'german', 'spanish', 'punjabi', 'sindhi',
            'pashto', 'bengali', 'chinese', 'turkish', 'portuguese', 'italian', 'russian', 'malay',
            'tamil', 'nepali', 'persian', 'farsi', 'balochi', 'saraiki',
        ];

        foreach ($skills as $skill) {
            $lower = mb_strtolower(trim((string) $skill));
            foreach ($known as $lang) {
                if ($this->languageTokenMatches($lower, $lang)) {
                    $languages[] = ucfirst($lang);
                }
            }
        }

        if ($bio) {
            $bioLower = mb_strtolower($bio);
            foreach ($known as $lang) {
                if (preg_match('/\b'.preg_quote($lang, '/').'\b/u', $bioLower)) {
                    $languages[] = ucfirst($lang);
                }
            }
        }

        return $this->cleanList($languages);
    }

    private function languageTokenMatches(string $token, string $lang): bool
    {
        if ($token === $lang) {
            return true;
        }

        return (bool) preg_match('/^'.preg_quote($lang, '/').'(\s|$|-)/u', $token);
    }

    private function normalizeExperienceLevel(?string $value, array $workExperience): ?string
    {
        $options = ExperienceTranslation::query()->pluck('name')->unique()->values()->all();

        if ($value) {
            $match = $this->fuzzyMatch($value, $options);
            if ($match) {
                return $match;
            }
        }

        $years = $this->totalExperienceYears($workExperience);

        return match (true) {
            $years <= 0 => 'Fresher',
            $years < 1.5 => '1 Year',
            $years < 2.5 => '2 Years',
            $years < 4 => '3+ Years',
            $years < 7 => '5+ Years',
            $years < 9 => '8+ Years',
            $years < 12 => '10+ Years',
            default => '15+ Years',
        };
    }

    private function normalizeEducationLevel(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $options = EducationTranslation::query()->pluck('name')->unique()->values()->all();
        $match   = $this->fuzzyMatch($value, $options);

        if ($match) {
            return $match;
        }

        $aliases = [
            'bachelor' => 'Bachelor Degree',
            'bachelors' => 'Bachelor Degree',
            "bachelor's" => 'Bachelor Degree',
            'master' => 'Master Degree',
            'masters' => 'Master Degree',
            "master's" => 'Master Degree',
            'mba' => 'Master Degree',
            'phd' => 'PhD',
            'doctorate' => 'PhD',
            'high school' => 'High School',
            'intermediate' => 'Intermediate',
            'hsc' => 'Intermediate',
            'fsc' => 'Intermediate',
        ];

        $lower = strtolower($value);
        foreach ($aliases as $needle => $label) {
            if (str_contains($lower, $needle) && in_array($label, $options, true)) {
                return $label;
            }
        }

        return $value;
    }

    private function fuzzyMatch(string $value, array $options): ?string
    {
        $needle = strtolower(trim($value));

        foreach ($options as $option) {
            $hay = strtolower((string) $option);
            if ($needle === $hay || str_contains($hay, $needle) || str_contains($needle, $hay)) {
                return $option;
            }
        }

        return null;
    }

    private function totalExperienceYears(array $workExperience): float
    {
        $months = 0;

        foreach ($workExperience as $job) {
            if (empty($job['start'])) {
                continue;
            }
            try {
                $start = Carbon::parse($job['start']);
                $end   = ! empty($job['end']) && empty($job['currently_working'])
                    ? Carbon::parse($job['end'])
                    : Carbon::now();
                $months += max(0, $start->diffInMonths($end));
            } catch (\Exception $e) {
            }
        }

        return $months / 12;
    }

    /** @return list<array<string, mixed>> */
    private function normalizeWorkExperience(array $items): array
    {
        $out = [];

        foreach ($items as $exp) {
            if (! is_array($exp)) {
                continue;
            }

            $company     = trim((string) ($exp['company'] ?? ''));
            $designation = trim((string) ($exp['designation'] ?? $exp['position'] ?? $exp['title'] ?? $exp['role'] ?? ''));
            if ($company === '' || $designation === '') {
                continue;
            }

            $end = $exp['end'] ?? null;
            $out[] = [
                'company'           => $company,
                'department'        => trim((string) ($exp['department'] ?? '')) ?: 'General',
                'designation'       => $designation,
                'start'             => $exp['start'] ?? null,
                'end'               => $end,
                'currently_working' => ! empty($exp['currently_working']) || empty($end),
                'responsibilities'  => $exp['responsibilities'] ?? null,
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function normalizeEducationHistory(array $items, ?string $educationLevel): array
    {
        $out = [];

        foreach ($items as $edu) {
            if (! is_array($edu)) {
                continue;
            }

            $degree = trim((string) ($edu['degree'] ?? ''));
            $level  = trim((string) ($edu['level'] ?? ''));
            if ($level === '') {
                $level = $educationLevel ?: $this->inferLevelFromDegree($degree);
            }
            if ($degree === '' && $level === '') {
                continue;
            }

            $out[] = [
                'level'  => $level ?: 'Bachelor Degree',
                'degree' => $degree ?: $level,
                'year'   => (int) ($edu['year'] ?? 0),
                'notes'  => $edu['notes'] ?? null,
            ];
        }

        return $out;
    }

    private function inferLevelFromDegree(string $degree): string
    {
        $lower = strtolower($degree);

        return match (true) {
            str_contains($lower, 'phd') || str_contains($lower, 'doctor') => 'PhD',
            str_contains($lower, 'master') || str_contains($lower, 'msc') || str_contains($lower, 'mba') => 'Master Degree',
            str_contains($lower, 'bachelor') || str_contains($lower, 'bsc') || str_contains($lower, 'bscs') => 'Bachelor Degree',
            str_contains($lower, 'intermediate') || str_contains($lower, 'hsc') || str_contains($lower, 'fsc') => 'Intermediate',
            str_contains($lower, 'high school') || str_contains($lower, 'matric') => 'High School',
            default => 'Bachelor Degree',
        };
    }

    /** @return list<string> */
    private function mergeJobTitles(array $jobs, array $titles, array $workExperience): array
    {
        foreach ($workExperience as $exp) {
            if (! empty($exp['designation'])) {
                $jobs[] = $exp['designation'];
            }
        }

        return array_slice($this->cleanList(array_merge($jobs, $titles)), 0, 6);
    }

    /** @return list<string> */
    private function expandIndustries(array $industries, array $skills, array $workExperience): array
    {
        $defaults = ['Information Technology', 'Software Development', 'Web Development', 'E-Commerce', 'Telecommunications'];
        $merged   = $this->cleanList(array_merge($industries, $defaults));

        foreach ($skills as $skill) {
            $s = strtolower((string) $skill);
            if (str_contains($s, 'laravel') || str_contains($s, 'php') || str_contains($s, 'python')) {
                $merged[] = 'Information Technology';
            }
        }

        return array_slice($this->cleanList($merged), 0, 6);
    }

    /** @return list<array{platform: string, url: string}> */
    private function normalizeSocialLinks(array $links, ?string $website, array $portfolioUrls): array
    {
        $out  = [];
        $seen = [];

        $allUrls = $portfolioUrls;
        if ($website) {
            $allUrls[] = $website;
        }

        foreach ($links as $link) {
            if (! is_array($link)) {
                continue;
            }
            $platform = strtolower(trim((string) ($link['platform'] ?? $link['social_media'] ?? '')));
            $url      = $this->cleanUrl($link['url'] ?? null);
            if ($platform === '' || ! $url) {
                continue;
            }
            $key = $platform . '|' . $url;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[]      = ['platform' => $platform, 'url' => $url];
        }

        foreach ($allUrls as $rawUrl) {
            $url = $this->cleanUrl(is_string($rawUrl) ? $rawUrl : null);
            if (! $url) {
                continue;
            }
            $platform = $this->detectSocialPlatform($url);
            $key      = $platform . '|' . $url;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[]      = ['platform' => $platform, 'url' => $url];
        }

        return $out;
    }

    private function detectSocialPlatform(string $url): string
    {
        $lower = strtolower($url);

        return match (true) {
            str_contains($lower, 'linkedin.com') => 'linkedin',
            str_contains($lower, 'github.com') => 'github',
            str_contains($lower, 'twitter.com'), str_contains($lower, 'x.com') => 'twitter',
            str_contains($lower, 'facebook.com') => 'facebook',
            str_contains($lower, 'instagram.com') => 'instagram',
            str_contains($lower, 'youtube.com') => 'youtube',
            default => 'other',
        };
    }

    private function inferState(?string $city, ?string $country): ?string
    {
        if (! $city || ! $country || stripos($country, 'pakistan') === false) {
            return null;
        }

        $map = [
            'rawalpindi' => 'Punjab',
            'lahore'     => 'Punjab',
            'islamabad'  => 'Islamabad',
            'karachi'    => 'Sindh',
            'peshawar'   => 'Khyber Pakhtunkhwa',
            'wah'        => 'Punjab',
            'wah cantt'  => 'Punjab',
            'multan'     => 'Punjab',
            'faisalabad' => 'Punjab',
        ];

        return $map[strtolower(trim($city))] ?? null;
    }
}

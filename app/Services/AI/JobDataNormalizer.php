<?php

namespace App\Services\AI;

use App\Models\Education;
use App\Models\EducationTranslation;
use App\Models\Experience;
use App\Models\ExperienceTranslation;
use App\Models\IndustryType;
use App\Models\JobRole;
use App\Models\JobType;
use App\Models\JobTypeTranslation;
use App\Models\SalaryType;
use Carbon\Carbon;

class JobDataNormalizer
{
    public function __construct(
        protected JobFormSchemaService $formSchema
    ) {}

    public function normalize(array $data): array
    {
        if (empty($data['is_job_posting'])) {
            return $data;
        }

        // Legacy single-job flat shape from older parser responses.
        if (isset($data['job_title']) && ! isset($data['jobs'])) {
            $data = [
                'is_job_posting' => true,
                'shared' => $data['shared'] ?? [],
                'jobs' => [$data],
            ];
        }

        $shared = $this->normalizeShared($data['shared'] ?? []);
        $jobs = [];

        foreach ($data['jobs'] ?? [] as $job) {
            if (! is_array($job)) {
                continue;
            }
            $merged = array_merge($shared, $job);
            $normalized = $this->normalizeJob($merged, $shared);
            if (! empty($normalized['job_title'])) {
                $jobs[] = $normalized;
            }
        }

        return [
            'is_job_posting' => true,
            'job_count' => count($jobs),
            'shared' => $shared,
            'jobs' => $jobs,
        ];
    }

    /** @param  array<string, mixed>  $shared */
    private function normalizeShared(array $shared): array
    {
        $shared['industry'] = $this->fuzzyMatch($shared['industry'] ?? null, $this->formSchema->industryOptions());
        $shared['country'] = $this->clean($shared['country'] ?? null);
        $shared['city'] = $this->clean($shared['city'] ?? null);
        $shared['location'] = $this->clean($shared['location'] ?? null);
        $shared['deadline'] = $this->normalizeDeadline($shared['deadline'] ?? null);
        $shared['employment_terms'] = $this->cleanHtml($shared['employment_terms'] ?? null);
        $shared['benefits'] = $this->cleanList($shared['benefits'] ?? []);
        $shared['apply_email'] = $this->clean($shared['apply_email'] ?? null);
        $shared['apply_url'] = $this->clean($shared['apply_url'] ?? null);
        $shared['company_name'] = $this->clean($shared['company_name'] ?? null);

        if (empty($shared['location']) && ($shared['city'] || $shared['country'])) {
            $shared['location'] = trim(implode(', ', array_filter([$shared['city'], $shared['country']])));
        }

        $shared['category_id'] = $this->resolveIndustryId($shared['industry']);

        return $shared;
    }

    /** @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $shared
     */
    private function normalizeJob(array $job, array $shared): array
    {
        $job['job_title'] = $this->clean($job['job_title'] ?? null);
        $job['job_title_ar'] = $this->clean($job['job_title_ar'] ?? null);
        $job['description'] = $this->cleanHtml($job['description'] ?? null);
        $job['description_ar'] = $this->clean($job['description_ar'] ?? null);
        $job['custom_salary'] = $this->clean($job['custom_salary'] ?? null);
        $job['location'] = $this->clean($job['location'] ?? null);
        $job['country'] = $this->clean($job['country'] ?? null);
        $job['city'] = $this->clean($job['city'] ?? null);

        if (empty($job['location']) && ($job['city'] || $job['country'])) {
            $job['location'] = trim(implode(', ', array_filter([$job['city'], $job['country']])));
        }

        $job['skills'] = $this->cleanList($job['skills'] ?? []);
        $job['tags'] = $this->cleanList($job['tags'] ?? []);
        $job['benefits'] = array_values(array_unique(array_merge(
            $this->cleanList($job['benefits'] ?? []),
            $shared['benefits'] ?? []
        )));

        $job['industry'] = $this->fuzzyMatch($job['industry'] ?? null, $this->formSchema->industryOptions())
            ?? $shared['industry'] ?? null;
        $job['job_role'] = $this->fuzzyMatch($job['job_role'] ?? null, $this->formSchema->jobRoleOptions())
            ?? $job['job_title'];
        $job['experience'] = $this->fuzzyMatch($job['experience'] ?? null, $this->formSchema->experienceOptions());
        $job['education'] = $this->fuzzyMatch($job['education'] ?? null, $this->formSchema->educationOptions());
        $job['job_type'] = $this->fuzzyMatch($job['job_type'] ?? null, $this->formSchema->jobTypeOptions());
        $job['salary_type'] = $this->fuzzyMatch($job['salary_type'] ?? null, $this->formSchema->salaryTypeOptions());

        $job['category_id'] = $this->resolveIndustryId($job['industry']) ?? $shared['category_id'] ?? null;
        $job['role_id'] = $this->resolveJobRoleId($job['job_role']);
        $job['experience_id'] = $this->resolveExperienceId($job['experience']);
        $job['education_id'] = $this->resolveEducationId($job['education']);
        $job['job_type_id'] = $this->resolveJobTypeId($job['job_type']);
        $job['salary_type_id'] = $this->resolveSalaryTypeId($job['salary_type']);

        $job['salary_mode'] = in_array($job['salary_mode'] ?? '', ['range', 'custom'], true)
            ? $job['salary_mode']
            : (($job['min_salary'] || $job['max_salary']) ? 'range' : 'custom');

        $job['min_salary'] = $this->normalizeNumber($job['min_salary'] ?? null);
        $job['max_salary'] = $this->normalizeNumber($job['max_salary'] ?? null);
        $job['currency'] = strtoupper(trim((string) ($job['currency'] ?? 'USD'))) ?: 'USD';
        $job['vacancies'] = max(1, (int) ($job['vacancies'] ?? 1));
        $job['deadline'] = $this->normalizeDeadline($job['deadline'] ?? null) ?? $shared['deadline'] ?? null;
        $job['gender'] = $this->normalizeGender($job['gender'] ?? null);
        $job['min_age'] = $this->normalizeAge($job['min_age'] ?? null);
        $job['max_age'] = $this->normalizeAge($job['max_age'] ?? null);
        $job['is_remote'] = filter_var($job['is_remote'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $job['description'] = $this->mergeDescription($job['description'], $shared['employment_terms'] ?? null, $job['skills']);

        $job['apply_email'] = $job['apply_email'] ?? $shared['apply_email'] ?? null;
        $job['apply_url'] = $job['apply_url'] ?? $shared['apply_url'] ?? null;

        return $job;
    }

    private function mergeDescription(?string $description, ?string $employmentTerms, array $skills): ?string
    {
        $parts = array_filter([$description, $employmentTerms]);

        if (! empty($skills)) {
            $items = implode('', array_map(fn ($s) => '<li>' . e($s) . '</li>', $skills));
            $parts[] = '<p><strong>Required skills</strong></p><ul>' . $items . '</ul>';
        }

        if (empty($parts)) {
            return null;
        }

        return implode('', $parts);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function cleanHtml(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (! str_contains($value, '<')) {
            $paragraphs = preg_split("/\n{2,}/", $value) ?: [$value];

            return implode('', array_map(
                fn ($p) => '<p>' . nl2br(e(trim($p)), false) . '</p>',
                array_filter(array_map('trim', $paragraphs))
            ));
        }

        return $value;
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

    private function fuzzyMatch(?string $value, array $options): ?string
    {
        if (! $value || empty($options)) {
            return null;
        }

        $needle = strtolower(trim($value));

        foreach ($options as $option) {
            $hay = strtolower((string) $option);
            if ($needle === $hay || str_contains($hay, $needle) || str_contains($needle, $hay)) {
                return $option;
            }
        }

        return $value;
    }

    private function resolveIndustryId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        $match = IndustryType::query()
            ->whereHas('translations', fn ($q) => $q->where('name', 'like', '%'.$name.'%'))
            ->value('id');

        return $match ? (int) $match : null;
    }

    private function resolveJobRoleId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        $match = JobRole::query()
            ->whereHas('translations', fn ($q) => $q->where('name', 'like', '%'.$name.'%'))
            ->value('id');

        return $match ? (int) $match : null;
    }

    private function resolveExperienceId(?string $name): ?int
    {
        return $this->resolveTranslatedId($name, Experience::class, ExperienceTranslation::class, 'experience_id');
    }

    private function resolveEducationId(?string $name): ?int
    {
        return $this->resolveTranslatedId($name, Education::class, EducationTranslation::class, 'education_id');
    }

    private function resolveJobTypeId(?string $name): ?int
    {
        return $this->resolveTranslatedId($name, JobType::class, JobTypeTranslation::class, 'job_type_id');
    }

    private function resolveSalaryTypeId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        $match = SalaryType::query()
            ->whereHas('translations', fn ($q) => $q->where('name', 'like', '%'.$name.'%'))
            ->value('id');

        return $match ? (int) $match : null;
    }

    private function resolveTranslatedId(?string $name, string $modelClass, string $translationClass, string $foreignKey): ?int
    {
        if (! $name) {
            return null;
        }

        $translation = $translationClass::query()
            ->where('name', 'like', '%'.$name.'%')
            ->first();

        return $translation ? (int) $translation->{$foreignKey} : null;
    }

    private function normalizeNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $num = preg_replace('/[^0-9.]/', '', (string) $value);

        return $num !== '' ? (float) $num : null;
    }

    private function normalizeDeadline(?string $value): ?string
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

    private function normalizeGender(?string $value): ?string
    {
        $g = strtolower(trim((string) $value));

        return match (true) {
            in_array($g, ['m', 'male', 'man'], true) => 'male',
            in_array($g, ['f', 'female', 'woman'], true) => 'female',
            in_array($g, ['both', 'any', 'all'], true) => 'both',
            default => null,
        };
    }

    private function normalizeAge($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $age = (int) preg_replace('/\D/', '', (string) $value);

        return ($age >= 18 && $age <= 70) ? $age : null;
    }
}

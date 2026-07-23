<?php

namespace App\Services\AI;

use App\Models\EducationTranslation;
use App\Models\ExperienceTranslation;
use App\Models\IndustryType;
use App\Models\JobRole;
use App\Models\JobTypeTranslation;
use App\Models\SalaryType;

/**
 * Describes employer job-post form fields for AI extraction from ads / JD documents.
 */
class JobFormSchemaService
{
    public function experienceOptions(): array
    {
        return ExperienceTranslation::query()
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    public function educationOptions(): array
    {
        return EducationTranslation::query()
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    public function jobTypeOptions(): array
    {
        return JobTypeTranslation::query()
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    public function industryOptions(): array
    {
        return IndustryType::query()
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => $item->name)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function jobRoleOptions(): array
    {
        return JobRole::query()
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => $item->name)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function salaryTypeOptions(): array
    {
        return SalaryType::query()
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => $item->name)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function jobPromptContext(): string
    {
        $experiences = $this->formatList($this->experienceOptions());
        $educations = $this->formatList($this->educationOptions());
        $jobTypes = $this->formatList($this->jobTypeOptions());
        $industries = $this->formatList($this->industryOptions());
        $roles = $this->formatList($this->jobRoleOptions());
        $salaryTypes = $this->formatList($this->salaryTypeOptions());

        return <<<CTX

You are parsing a job advertisement or job description document. It may be in ANY language (English, Arabic, Urdu, etc.).
Translate extracted content to English for form fields unless a dedicated Arabic field is specified.
Set is_job_posting to false only if the document is clearly NOT a job posting (e.g. a CV, invoice, or random image).

MULTIPLE JOBS — VERY IMPORTANT:
- Demand letters / manpower requests with a table of positions: ONE object in jobs[] per row (e.g. Dump Truck Driver, Excavator Operator).
- Flyers with two columns or two job blocks: ONE object in jobs[] per distinct position.
- Single job ad (one role only): jobs[] with exactly ONE item.
- Shared terms (contract type, working hours, food/accommodation, visa, country, employer name, application deadline, general benefits) go in shared{}, NOT repeated in every job unless position-specific.
- employment_terms: HTML summary of shared contract/employment conditions that apply to all positions.

FORM FIELD RULES (per job in jobs[]):
- job_title: primary job title in English when possible
- job_title_ar: Arabic title if present in the document, else null
- industry: closest match from: {$industries} (use shared.industry when the whole ad is one sector)
- job_role: closest match from: {$roles}, or use job_title when no close match
- experience: closest match from: {$experiences} — null if not stated
- education: closest match from: {$educations} — null if NOT stated. NEVER invent or guess education.
- job_type: closest match from: {$jobTypes} (e.g. Full Time, Part Time, Contract)
- salary_type: closest match from: {$salaryTypes} when stated (Monthly, Yearly, Hourly, etc.)
- salary_mode: "range" when min/max salary given, "custom" when only text like "Negotiable" or "Competitive"
- min_salary, max_salary: numeric values only when salary_mode is range
- custom_salary: text salary when not a numeric range (e.g. "600 USD per month")
- currency: ISO code such as USD, PKR, SAR, AED, EUR, GBP
- vacancies: number of open positions for THIS role (default 1)
- deadline: application deadline as dd-mm-yyyy if stated for this role, else null (shared.deadline for all)
- gender: male, female, both, or null
- min_age, max_age: integers if age limits stated
- is_remote: true if remote / work from home mentioned
- location, country, city: job location text (inherit from shared when same for all)
- description: position-specific description in HTML (<p> and <ul><li>). Do not duplicate full shared terms here.
- description_ar: Arabic description if present, else null
- skills: up to 20 required skills for this position
- tags: up to 8 relevant job tags / keywords
- benefits: position-specific perks only; shared perks go in shared.benefits

Return this JSON when is_job_posting is true:

{
  "is_job_posting": true,
  "shared": {
    "industry": null,
    "country": null,
    "city": null,
    "location": null,
    "deadline": null,
    "employment_terms": null,
    "benefits": [],
    "apply_email": null,
    "apply_url": null,
    "company_name": null
  },
  "jobs": [
    {
      "job_title": null,
      "job_title_ar": null,
      "industry": null,
      "job_role": null,
      "experience": null,
      "education": null,
      "job_type": null,
      "salary_mode": "range",
      "salary_type": null,
      "min_salary": null,
      "max_salary": null,
      "custom_salary": null,
      "currency": null,
      "vacancies": 1,
      "deadline": null,
      "gender": null,
      "min_age": null,
      "max_age": null,
      "is_remote": false,
      "location": null,
      "country": null,
      "city": null,
      "description": null,
      "description_ar": null,
      "skills": [],
      "tags": [],
      "benefits": []
    }
  ]
}
CTX;
    }

    private function formatList(array $items): string
    {
        if (empty($items)) {
            return 'null';
        }

        return implode(' | ', array_map(
            fn ($item) => str_replace('|', '/', (string) $item),
            $items
        ));
    }
}

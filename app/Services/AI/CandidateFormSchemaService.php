<?php

namespace App\Services\AI;

use App\Models\EducationTranslation;
use App\Models\ExperienceTranslation;

/**
 * Describes candidate settings form fields so CV extraction can target real inputs.
 */
class CandidateFormSchemaService
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

    /**
     * JSON schema fragment + rules appended to the CV parser prompt.
     */
    public function cvPromptContext(): string
    {
        $experiences = $this->formatList($this->experienceOptions());
        $educations  = $this->formatList($this->educationOptions());

        return <<<CTX

FORM FIELD RULES (map CV content to these exact portal fields):
- experience_level: MUST be exactly one of: {$experiences}
- education_level: MUST be exactly one of: {$educations}
- gender: exactly "male", "female", "other", or null
- marital_status: exactly "single", "married", or null
- date_of_birth, passport_issue_date, passport_expiry_date: dd-mm-yyyy with leading zeros, or null
- country: full country name from address (e.g. "Pakistan", "Saudi Arabia")
- state: state / province / region from address
- city: city or district from address
- whatsapp: phone/WhatsApp number with country code when possible
- job_preference_region: one of "Anywhere", "Gulf", "Asia", "Europe" — infer from target countries in CV
- expected_salary: numeric monthly expectation if stated, else null
- salary_currency: ISO code such as USD, PKR, SAR, AED, EUR, GBP when salary is present
- passport_number, place_of_issue, cnic_number: extract only if clearly present on the CV
- profession: primary occupation / job title (e.g. "Software Engineer", "Driver")
- titles: array of professional titles / roles the candidate is suited for (up to 6)
- skills: up to 20 relevant skills
- languages: spoken languages
- jobs: job roles the candidate wants or has held (up to 6)
- industries: industries they have worked in or want (up to 6)
- bio: 3-sentence third-person professional summary written from the CV
- work_experience: array of {company, designation, department, start, end, currently_working, responsibilities}
- education_history: array of {level, degree, year} — level is required
- social_links: array of {platform, url} where platform is linkedin, github, or other

Return this extended JSON when is_cv is true (include every key; use null or [] when unknown):

{
  "is_cv": true,
  "first_name": null,
  "last_name": null,
  "email": null,
  "phone": null,
  "whatsapp": null,
  "website": null,
  "gender": null,
  "marital_status": null,
  "date_of_birth": null,
  "country": null,
  "state": null,
  "city": null,
  "titles": [],
  "profession": null,
  "experience_level": null,
  "education_level": null,
  "skills": [],
  "languages": [],
  "jobs": [],
  "industries": [],
  "bio": null,
  "nationality": null,
  "passport_number": null,
  "passport_issue_date": null,
  "passport_expiry_date": null,
  "place_of_issue": null,
  "cnic_number": null,
  "job_preference_region": null,
  "expected_salary": null,
  "salary_currency": null,
  "social_links": [],
  "portfolio_urls": [],
  "education_history": [],
  "work_experience": []
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

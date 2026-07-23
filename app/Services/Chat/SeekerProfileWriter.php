<?php

namespace App\Services\Chat;

use App\Models\Attachment;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use App\Models\CandidateLanguage;
use App\Models\CandidateResume;
use App\Models\City;
use App\Models\ContactInfo;
use App\Models\EducationTranslation;
use App\Models\ExperienceTranslation;
use App\Models\IndustryType;
use App\Models\IndustryTypeTranslation;
use App\Models\JobRequirement;
use App\Models\Profession;
use App\Models\ProfessionTranslation;
use App\Models\SearchCountry;
use App\Models\Skill;
use App\Models\SkillTranslation;
use App\Models\State;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * SeekerProfileWriter
 * ─────────────────────────────────────────────────────────────────
 * Writes the chatbot-extracted CV + passport data onto a Candidate
 * EXACTLY the way the candidate dashboard does (AIController::saveCVData
 * + applyPassportFields). Kept as a separate, chatbot-only class so the
 * working dashboard controller is never touched.
 *
 * Takes an explicit Candidate (no auth dependency) so it can run during
 * the chatbot registration, before the user is logged in.
 *
 * Every sub-step is individually guarded: one bad field can never abort
 * the whole save (mirrors the dashboard's resilience).
 * ─────────────────────────────────────────────────────────────────
 */
class SeekerProfileWriter
{
    /* ═══════════════════════════════════════════════════════════
       CV  →  candidate + contact info + skills + languages + edu + exp + file
    ═══════════════════════════════════════════════════════════ */

    public function applyCv(Candidate $candidate, array $data, ?string $cvStoredPath = null): void
    {
        // --- Direct candidate columns ---
        try {
            $update = [];

            if (!empty($data['first_name']))  $update['first_name']  = $data['first_name'];
            if (!empty($data['last_name']))   $update['last_name']   = $data['last_name'];
            if (!empty($data['gender']))      $update['gender']      = $this->normalizeGender($data['gender']);
            if (!empty($data['bio']))         $update['bio']         = $data['bio'];
            if (!empty($data['titles'][0]))   $update['title']       = $data['titles'][0];
            if (!empty($data['profession']) && empty($update['title'])) $update['title'] = $data['profession'];
            if (!empty($data['nationality'])) $update['nationality'] = $data['nationality'];
            if (!empty($data['marital_status'])) {
                $ms = strtolower(trim((string) $data['marital_status']));
                if (in_array($ms, ['single', 'married'], true)) {
                    $update['marital_status'] = $ms;
                }
            }
            if (!empty($data['website'])) $update['website'] = $data['website'];
            if (!empty($data['passport_number'])) $update['passport_number'] = $data['passport_number'];
            if (!empty($data['place_of_issue'])) $update['place_of_issue'] = $data['place_of_issue'];
            if (!empty($data['cnic_number'])) $update['cnic_number'] = $data['cnic_number'];

            if (!empty($data['passport_issue_date'])) {
                $d = $this->toDate($data['passport_issue_date']);
                if ($d) $update['passport_issue_date'] = $d;
            }
            if (!empty($data['passport_expiry_date'])) {
                $d = $this->toDate($data['passport_expiry_date']);
                if ($d) $update['passport_expiry_date'] = $d;
            }

            $update = array_merge($update, $this->resolveLocationFields($data));

            if (!empty($data['profession'])) {
                $professionId = $this->resolveProfessionId($data['profession']);
                if ($professionId) {
                    $update['profession_id'] = $professionId;
                }
            }

            if (!empty($data['date_of_birth'])) {
                $bd = $this->toDate($data['date_of_birth']);
                if ($bd) $update['birth_date'] = $bd;
            }

            if (!empty($data['experience_level'])) {
                $expTrans = ExperienceTranslation::where('name', 'LIKE', '%' . $data['experience_level'] . '%')->first();
                if ($expTrans) $update['experience_id'] = $expTrans->experience_id;
            }

            if (!empty($data['education_level'])) {
                $eduTrans = EducationTranslation::where('name', 'LIKE', '%' . $data['education_level'] . '%')->first();
                if ($eduTrans) $update['education_id'] = $eduTrans->education_id;
            }

            $update = array_filter($update, fn ($v) => $v !== null && $v !== '');

            if (!empty($update)) {
                $candidate->update($update);
            }

            // Keep users.name in sync with the parsed first/last name.
            $fn = $update['first_name'] ?? $candidate->getRawOriginal('first_name');
            $ln = $update['last_name']  ?? $candidate->getRawOriginal('last_name');
            if (trim("$fn $ln") !== '' && $candidate->user) {
                $candidate->user->update(['name' => trim("$fn $ln")]);
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] cv columns: ' . $e->getMessage());
        }

        // --- Contact info (email / phone) — account email wins over CV scrape ---
        try {
            $phone = ! empty($data['phone']) ? $data['phone'] : null;
            $whatsapp = ! empty($data['whatsapp']) ? $data['whatsapp'] : null;

            if ($phone && $whatsapp && preg_replace('/\D/', '', $phone) === preg_replace('/\D/', '', $whatsapp)) {
                $whatsapp = null;
            }

            $contact = [];
            if (! empty($data['email'])) {
                $contact['email'] = strtolower((string) $data['email']);
            }
            if ($phone) {
                $contact['phone'] = $phone;
            }
            if ($whatsapp) {
                $contact['whatsapp_number'] = $whatsapp;
            }

            if ($contact !== []) {
                ContactInfo::updateOrCreate(['user_id' => $candidate->user_id], $contact);
            }

            $userUpdate = [];
            if ($whatsapp) {
                $userUpdate['whatsapp'] = $whatsapp;
            } elseif ($phone) {
                $userUpdate['whatsapp'] = $phone;
            }

            if ($userUpdate !== [] && $candidate->user) {
                $candidate->user->update($userUpdate);
            }

            $candidateUpdate = [];
            if ($whatsapp) {
                $candidateUpdate['whatsapp_number'] = $whatsapp;
            } elseif ($phone) {
                $candidateUpdate['whatsapp_number'] = $phone;
            }

            if ($candidateUpdate !== []) {
                $candidate->update($candidateUpdate);
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] contact info: ' . $e->getMessage());
        }

        // --- Skills (find/create translation, then merge) ---
        try {
            if (!empty($data['skills']) && is_array($data['skills'])) {
                $skillIds = [];
                foreach ($data['skills'] as $skillName) {
                    $skillName = trim((string) $skillName);
                    if ($skillName === '') continue;

                    $existing = SkillTranslation::where('name', $skillName)->first();
                    if ($existing) {
                        $skillIds[] = $existing->skill_id;
                        continue;
                    }

                    $newSkill = Skill::create(['name' => $skillName]);
                    foreach (loadLanguage() as $lang) {
                        $newSkill->translateOrNew($lang->code)->name = $skillName;
                    }
                    $newSkill->save();
                    $skillIds[] = $newSkill->id;
                }
                if (!empty($skillIds)) {
                    $candidate->skills()->syncWithoutDetaching($skillIds);
                }
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] skills: ' . $e->getMessage());
        }

        // --- Languages (find/create, then merge) ---
        try {
            if (!empty($data['languages']) && is_array($data['languages'])) {
                $langIds = [];
                foreach ($data['languages'] as $langName) {
                    $langName = trim((string) $langName);
                    if ($langName === '') continue;

                    $lang = CandidateLanguage::query()
                        ->whereRaw('LOWER(name) = ?', [strtolower($langName)])
                        ->first();

                    if (! $lang) {
                        $lang = CandidateLanguage::firstOrCreate(['name' => $langName]);
                    }

                    $langIds[] = $lang->id;
                }
                if (!empty($langIds)) {
                    $candidate->languages()->syncWithoutDetaching($langIds);
                }
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] languages: ' . $e->getMessage());
        }

        // --- Education history (dedupe by degree) ---
        try {
            if (!empty($data['education_history']) && is_array($data['education_history'])) {
                foreach ($data['education_history'] as $edu) {
                    $degree = trim((string) ($edu['degree'] ?? ''));
                    $level  = trim((string) ($edu['level'] ?? ''));
                    if ($level === '') {
                        $level = trim((string) ($data['education_level'] ?? '')) ?: 'Bachelor Degree';
                    }
                    $year = (int) ($edu['year'] ?? 0);
                    if ($degree === '') {
                        continue;
                    }

                    $exists = CandidateEducation::where('candidate_id', $candidate->id)
                        ->where('degree', $degree)->exists();
                    if ($exists) continue;

                    CandidateEducation::create([
                        'candidate_id' => $candidate->id,
                        'level'        => $level,
                        'degree'       => $degree,
                        'year'         => $year > 1950 ? $year : (int) date('Y'),
                        'notes'        => $edu['notes'] ?? null,
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] education: ' . $e->getMessage());
        }

        // --- Work experience (dedupe by company + designation) ---
        try {
            if (!empty($data['work_experience']) && is_array($data['work_experience'])) {
                foreach ($data['work_experience'] as $exp) {
                    $company     = trim((string) ($exp['company'] ?? ''));
                    $designation = trim((string) ($exp['designation'] ?? $exp['position'] ?? $exp['title'] ?? ''));
                    if ($company === '' || $designation === '') continue;

                    $exists = CandidateExperience::where('candidate_id', $candidate->id)
                        ->where('company', $company)
                        ->where('designation', $designation)
                        ->exists();
                    if ($exists) continue;

                    $start = !empty($exp['start']) ? $this->toDate($exp['start']) : null;
                    $end   = !empty($exp['end'])   ? $this->toDate($exp['end'])   : null;
                    if (!$start) continue;

                    CandidateExperience::create([
                        'candidate_id'      => $candidate->id,
                        'company'           => $company,
                        'department'        => trim((string) ($exp['department'] ?? '')) ?: 'General',
                        'designation'       => $designation,
                        'start'             => $start,
                        'end'               => $end,
                        'responsibilities'  => $exp['responsibilities'] ?? null,
                        'currently_working' => !empty($exp['currently_working']) || empty($exp['end']) ? 1 : 0,
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] experience: ' . $e->getMessage());
        }

        // --- Social links (LinkedIn, GitHub, portfolio URLs) ---
        $this->saveSocialLinks($candidate, $data);

        // --- CV file → candidate->cv + a resume row (so it shows in the dashboard) ---
        $this->saveCvFile($candidate, $cvStoredPath, $data);

        // --- Job preferences (Job Requirements section) ---
        $this->saveJobRequirements($candidate, $data);
    }

    protected function saveJobRequirements(Candidate $candidate, array $data): void
    {
        try {
            $payload = [];
            $regions = ['Anywhere', 'Gulf', 'Asia', 'Europe'];

            if (! empty($data['job_preference_region']) && in_array($data['job_preference_region'], $regions, true)) {
                $payload['region'] = $data['job_preference_region'];
            }
            if (isset($data['expected_salary']) && is_numeric($data['expected_salary'])) {
                $payload['salary'] = (float) $data['expected_salary'];
            }
            if (! empty($data['salary_currency'])) {
                $payload['currency'] = strtoupper((string) $data['salary_currency']);
            }

            $existing = JobRequirement::where('candidate_id', $candidate->id)->first();

            if (! empty($data['jobs']) && is_array($data['jobs'])) {
                $payload['jobs'] = $this->resolveProfessionIds($data['jobs']);
            } elseif ($existing) {
                $payload['jobs'] = $existing->jobs;
            }

            if (! empty($data['industries']) && is_array($data['industries'])) {
                $payload['industries'] = $this->resolveIndustryIds($data['industries']);
            } elseif ($existing) {
                $payload['industries'] = $existing->industries;
            }

            if (
                empty($payload['jobs'])
                && empty($payload['industries'])
                && ! isset($payload['region'])
                && ! isset($payload['salary'])
            ) {
                return;
            }

            if ($existing) {
                $existing->update($payload);
            } else {
                JobRequirement::create(array_merge(['candidate_id' => $candidate->id], $payload));
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] job requirements: ' . $e->getMessage());
        }
    }

    protected function saveSocialLinks(Candidate $candidate, array $data): void
    {
        $links = $data['social_links'] ?? [];
        if (empty($links) || ! $candidate->user) {
            return;
        }

        try {
            foreach ($links as $link) {
                if (! is_array($link)) {
                    continue;
                }

                $platform = strtolower(trim((string) ($link['platform'] ?? $link['social_media'] ?? '')));
                $url      = trim((string) ($link['url'] ?? ''));
                if ($platform === '' || $url === '') {
                    continue;
                }

                $exists = $candidate->user->socialInfo()
                    ->where('social_media', $platform)
                    ->where('url', $url)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $candidate->user->socialInfo()->create([
                    'social_media' => $platform,
                    'url'          => $url,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] social links: ' . $e->getMessage());
        }
    }

    protected function resolveProfessionIds(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            if (is_numeric($name)) {
                $ids[] = (int) $name;
                continue;
            }

            $id = $this->resolveProfessionId(trim((string) $name));
            if ($id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolveIndustryIds(array $names): array
    {
        $ids = [];
        $locale = app()->getLocale();

        foreach ($names as $name) {
            if (is_numeric($name)) {
                $ids[] = (int) $name;
                continue;
            }

            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $translation = IndustryTypeTranslation::query()
                ->where('locale', $locale)
                ->where(function ($q) use ($name) {
                    $q->where('name', $name)
                        ->orWhere('name', 'LIKE', '%' . $name . '%');
                })
                ->first();

            if ($translation) {
                $ids[] = (int) $translation->industry_type_id;
                continue;
            }

            try {
                $industry = IndustryType::create();
                $industry->translateOrNew($locale)->name = $name;
                $industry->save();
                $ids[] = (int) $industry->id;
            } catch (Throwable $e) {
                Log::warning('[SeekerProfileWriter] industry: ' . $e->getMessage());
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolveProfessionId(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $translation = ProfessionTranslation::where('name', 'LIKE', $name)
            ->orWhere('name', 'LIKE', '%' . $name . '%')
            ->first();

        if ($translation) {
            return (int) $translation->profession_id;
        }

        try {
            $profession = Profession::create(['name' => $name]);
            foreach (loadLanguage() as $language) {
                $profession->translateOrNew($language->code)->name = $name;
            }
            $profession->save();

            return (int) $profession->id;
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] profession: ' . $e->getMessage());

            return null;
        }
    }

    /* ═══════════════════════════════════════════════════════════
       PASSPORT  →  candidate columns + attachment image
    ═══════════════════════════════════════════════════════════ */

    public function applyPassport(Candidate $candidate, array $fields, ?string $imgStoredPath = null): void
    {
        try {
            $map = [
                'passport_number' => 'passport_number',
                'date_of_birth'   => 'birth_date',
                'date_of_expiry'  => 'passport_expiry_date',
                'date_of_issue'   => 'passport_issue_date',
                'place_of_issue'  => 'place_of_issue',
                'nationality'     => 'nationality',
                'national_id'     => 'cnic_number',
                'gender'          => 'gender',
            ];

            $dateKeys = ['birth_date', 'passport_expiry_date', 'passport_issue_date'];
            $update   = [];

            foreach ($map as $ocrKey => $dbKey) {
                if (empty($fields[$ocrKey])) continue;
                $value = $fields[$ocrKey];

                if ($dbKey === 'gender') {
                    $value = $this->normalizeGender($value);
                } elseif (in_array($dbKey, $dateKeys, true)) {
                    $value = $this->toDate($value);
                }

                if ($value !== null && $value !== '') {
                    $update[$dbKey] = $value;
                }
            }

            if (! empty($update)) {
                $candidate->update($update);
            }

            // Passport names → candidate name when CV omitted them.
            $nameUpdate = $this->passportNameFields($fields);
            if ($nameUpdate !== []) {
                $candidate->update($nameUpdate);
                $update = array_merge($update, $nameUpdate);
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] passport columns: ' . $e->getMessage());
        }

        // --- Passport image → Attachment + CandidateDocument (dashboard parity) ---
        $this->savePassportImage($candidate, $imgStoredPath);
    }

    /**
     * @return array<string, string>
     */
    protected function passportNameFields(array $fields): array
    {
        $update = [];

        if (! empty($fields['given_names'])) {
            $first = trim(explode(' ', trim((string) $fields['given_names']))[0]);
            if ($first !== '') {
                $update['first_name'] = Str::title($first);
            }
        }

        if (! empty($fields['surname'])) {
            $update['last_name'] = Str::title(trim((string) $fields['surname']));
        }

        return $update;
    }

    /* ═══════════════════════════════════════════════════════════
       FILE HELPERS
    ═══════════════════════════════════════════════════════════ */

    /** Move the uploaded CV into file/candidates and link it (candidate->cv + CandidateResume). */
    protected function saveCvFile(Candidate $candidate, ?string $cvStoredPath, array $data): void
    {
        if (!$cvStoredPath || !Storage::disk('public')->exists($cvStoredPath)) {
            return;
        }

        try {
            $ext  = strtolower(pathinfo($cvStoredPath, PATHINFO_EXTENSION)) ?: 'pdf';
            $dest = 'file/candidates/cv_' . Str::random(20) . '.' . $ext;

            Storage::disk('public')->move($cvStoredPath, $dest);

            $oldCv = $candidate->cv;
            if ($oldCv && $oldCv !== $dest) {
                deleteFile($oldCv);
            }

            $candidate->update(['cv' => $dest]);

            $name = trim(($data['first_name'] ?? $candidate->first_name ?? '') . ' ' . ($data['last_name'] ?? $candidate->last_name ?? ''));
            $name = $name !== '' ? $name . ' Resume' : 'CV';

            $resume = CandidateResume::where('candidate_id', $candidate->id)->latest()->first();
            if ($resume) {
                if ($resume->file && $resume->file !== $dest && $resume->file !== $oldCv) {
                    deleteFile($resume->file);
                }
                $resume->update([
                    'name' => $name,
                    'file' => $dest,
                ]);
            } else {
                CandidateResume::create([
                    'candidate_id' => $candidate->id,
                    'name'         => $name,
                    'file'         => $dest,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] cv file: ' . $e->getMessage());
        }
    }

    /**
     * Copy the uploaded passport image into public/storage/candidates and link it on the
     * candidate's Attachment row — identical destination/column to the dashboard
     * (AIController::parsePassport), which serves it via asset('storage/candidates/...').
     */
    protected function savePassportImage(Candidate $candidate, ?string $imgStoredPath): void
    {
        if (!$imgStoredPath) {
            return;
        }

        // Only image passports go in the image slot (a PDF can't preview there).
        $ext = strtolower(pathinfo($imgStoredPath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return;
        }

        $source = storage_path('app/public/' . $imgStoredPath);
        if (!is_file($source)) {
            return;
        }

        try {
            $fileName = 'passport_' . Str::random(20) . '.' . $ext;
            $destDir  = public_path('storage/candidates');
            File::ensureDirectoryExists($destDir);
            File::copy($source, $destDir . DIRECTORY_SEPARATOR . $fileName);

            $attachment = Attachment::firstOrNew(['candidate_id' => $candidate->id]);

            if ($attachment->passport_image) {
                $old = $destDir . DIRECTORY_SEPARATOR . $attachment->passport_image;
                if (is_file($old)) {
                    @unlink($old);
                }
            }

            $attachment->candidate_id   = $candidate->id;
            $attachment->passport_image = $fileName;
            $attachment->save();

            $docRecord = CandidateDocument::firstOrNew(['candidate_id' => $candidate->id]);
            if ($docRecord->passport_image && $docRecord->passport_image !== $fileName) {
                $oldDoc = $destDir.DIRECTORY_SEPARATOR.$docRecord->passport_image;
                if (is_file($oldDoc)) {
                    @unlink($oldDoc);
                }
            }
            $docRecord->candidate_id   = $candidate->id;
            $docRecord->passport_image = $fileName;
            $docRecord->save();

            // Tidy up the temp upload now that we have our own copy.
            Storage::disk('public')->delete($imgStoredPath);
        } catch (Throwable $e) {
            Log::warning('[SeekerProfileWriter] passport image: ' . $e->getMessage());
        }
    }

    /* ═══════════════════════════════════════════════════════════
       SMALL HELPERS
    ═══════════════════════════════════════════════════════════ */

    protected function normalizeGender($value): ?string
    {
        $g = strtolower(trim((string) $value));
        return match (true) {
            in_array($g, ['m', 'male'])   => 'male',
            in_array($g, ['f', 'female']) => 'female',
            $g === 'other'                => 'other',
            default                       => null,
        };
    }

    /**
     * Map CV country/state/city to canonical names used by the settings location dropdowns.
     *
     * @return array<string, mixed>
     */
    protected function resolveLocationFields(array $data): array
    {
        $country = trim((string) ($data['country'] ?? ''));
        $state   = trim((string) ($data['state'] ?? ''));
        $city    = trim((string) ($data['city'] ?? ''));

        if ($country === '' && ! empty($data['nationality'])) {
            $country = trim((string) $data['nationality']);
        }

        if ($state === '' && $city !== '') {
            $state = $this->inferStateFromCity($city, $country) ?? '';
        }

        if ($country === '') {
            return array_filter([
                'region'   => $state ?: null,
                'district' => $city ?: null,
            ]);
        }

        $searchCountry = SearchCountry::query()
            ->where(function ($q) use ($country) {
                $q->whereRaw('LOWER(name) = ?', [strtolower($country)])
                    ->orWhere('name', 'like', '%'.$country.'%');
            })
            ->first();

        if (! $searchCountry) {
            return array_filter([
                'country'  => $country,
                'region'   => $state ?: null,
                'district' => $city ?: null,
            ]);
        }

        $resolved = [
            'country'           => $searchCountry->name,
            'search_country_id' => $searchCountry->id,
        ];

        $stateModel = null;
        if ($state !== '') {
            $stateModel = State::query()
                ->where('country_id', $searchCountry->id)
                ->where(function ($q) use ($state) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($state)])
                        ->orWhere('name', 'like', '%'.$state.'%');
                })
                ->first();

            $resolved['region'] = $stateModel?->name ?? $state;
        }

        if ($city !== '') {
            if (! $stateModel && ! empty($resolved['region'])) {
                $stateModel = State::query()
                    ->where('country_id', $searchCountry->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower((string) $resolved['region'])])
                    ->first();
            }

            if ($stateModel) {
                $cityModel = City::query()
                    ->where('state_id', $stateModel->id)
                    ->where(function ($q) use ($city) {
                        $q->whereRaw('LOWER(name) = ?', [strtolower($city)])
                            ->orWhere('name', 'like', '%'.$city.'%');
                    })
                    ->first();

                $resolved['district'] = $cityModel?->name ?? $city;
            } else {
                $resolved['district'] = $city;
            }
        }

        return array_filter($resolved, fn ($v) => $v !== null && $v !== '');
    }

    protected function inferStateFromCity(string $city, ?string $country): ?string
    {
        if ($country && stripos($country, 'pakistan') === false) {
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
            'sialkot'    => 'Punjab',
            'quetta'     => 'Balochistan',
        ];

        return $map[strtolower(trim($city))] ?? null;
    }

    /** Accept Y-m-d, d-m-Y, d/m/Y, etc.; return Y-m-d or null. */
    protected function toDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'Y-m-d H:i:s'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
            } catch (Throwable $e) {
                // try next
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }
}

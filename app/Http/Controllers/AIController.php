<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Smalot\PdfParser\Parser;

use App\Models\Attachment;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use App\Models\CandidateLanguage;
use App\Models\ContactInfo;
use App\Models\EducationTranslation;
use App\Models\ExperienceTranslation;
use App\Models\PassportOcrLog;
use App\Models\Skill;
use App\Models\SkillTranslation;

use App\Services\Chat\OCRService;
use App\Services\Chat\SeekerProfileWriter;
use App\Services\AI\CvDataNormalizer;
use App\Services\AI\GPTCVParserService;
use App\Services\AI\GPTJobParserService;
use App\Services\OpenAI\OpenAIService;

class AIController extends Controller
{
    protected $ocr;
    protected $gptParser;
    protected $gptJobParser;
    protected $openai;

    /*
    | Services are resolved lazily (inside try/catch) rather than constructor-
    | injected. OpenAIService throws if the key is missing, and constructor
    | injection would let that exception escape as an HTML 500 page before the
    | method try/catch runs — which the front-end's res.json() cannot parse.
    */
    private function bootServices(): void
    {
        $this->ocr       ??= app(OCRService::class);
        $this->gptParser ??= app(GPTCVParserService::class);
        $this->gptJobParser ??= app(GPTJobParserService::class);
        $this->openai    ??= app(OpenAIService::class);
    }

    private function aiConfigured(): bool
    {
        return ! empty(config('services.openai.key'));
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE CV
    |--------------------------------------------------------------------------
    */

    public function parseCV(Request $request)
    {
        $request->validate([
            'cv' => 'required|mimes:pdf,jpeg,jpg,png,webp|max:10240'
        ]);

        if (! $this->aiConfigured()) {
            Log::error('CV parse: OpenAI key not configured');
            return response()->json([
                'error'   => 'ai_unconfigured',
                'message' => 'AI extraction is not configured on the server. Please contact the administrator.',
            ], 503);
        }

        try {
            $this->bootServices();
            $file = $request->file('cv');
            $mime = $file->getMimeType();

            Log::info('CV parse: file received', [
                'filename' => $file->getClientOriginalName(),
                'size_kb'  => round($file->getSize() / 1024, 1),
                'mime'     => $mime,
            ]);

            $cvStoredPath = $file->store('temp/cv-uploads', 'public');
            $cvFilename   = $file->getClientOriginalName();

            // Image CV — use OCR to extract text
            if (in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
                $ocr = $this->ocr->scan($cvStoredPath);

                if (!$ocr || strlen(trim($ocr['raw_text'] ?? '')) < 30) {
                    return response()->json([
                        'error'   => 'unreadable',
                        'message' => 'Could not read text from this image. Please use a clear, high-resolution photo of your CV.',
                    ], 422);
                }

                $text = $ocr['raw_text'];

            // PDF CV — use PDF text extraction
            } else {
                $parser = new Parser();
                $pdf    = $parser->parseFile(Storage::disk('public')->path($cvStoredPath));
                $text   = $pdf->getText();

                if (strlen(trim($text)) < 50) {
                    Log::warning('CV parse: extracted text too short — possibly a scanned image PDF');
                    return response()->json([
                        'error'   => 'unreadable',
                        'message' => 'Could not extract text from this PDF. If it is a scanned image, please upload a JPG/PNG photo of it instead.',
                    ], 422);
                }
            }

            Log::info('CV parse: text extracted', [
                'chars'   => strlen($text),
                'preview' => substr($text, 0, 200),
            ]);

            $data = $this->gptParser->parse($text);

            Log::info('CV parse: GPT response', ['data' => $data]);

            if (empty($data['is_cv'])) {
                Log::warning('CV parse: document not detected as a CV', [
                    'filename' => $file->getClientOriginalName(),
                ]);
                return response()->json([
                    'error'   => 'not_cv',
                    'message' => 'This file does not appear to be a CV or resume. Please upload a CV.',
                ], 422);
            }

            return response()->json([
                'data'            => $data,
                'cv_stored_path'  => $cvStoredPath,
                'cv_filename'     => $cvFilename,
            ]);

        } catch (\Exception $e) {
            Log::error('CV parse: exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE JOB POSTING (advertisement PDF / image)
    |--------------------------------------------------------------------------
    */

    public function parseJobPosting(Request $request)
    {
        $request->validate([
            'job_document' => 'required|mimes:pdf,jpeg,jpg,png,webp|max:10240',
        ]);

        if (! $this->aiConfigured()) {
            Log::error('Job posting parse: OpenAI key not configured');

            return response()->json([
                'error' => 'ai_unconfigured',
                'message' => 'AI extraction is not configured on the server. Please contact the administrator.',
            ], 503);
        }

        try {
            $this->bootServices();
            $file = $request->file('job_document');
            $mime = $file->getMimeType();

            Log::info('Job posting parse: file received', [
                'filename' => $file->getClientOriginalName(),
                'size_kb' => round($file->getSize() / 1024, 1),
                'mime' => $mime,
            ]);

            $storedPath = $file->store('temp/job-uploads', 'public');
            $filename = $file->getClientOriginalName();

            if (in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
                $ocr = $this->ocr->scan($storedPath);

                if (! $ocr || strlen(trim($ocr['raw_text'] ?? '')) < 30) {
                    return response()->json([
                        'error' => 'unreadable',
                        'message' => 'Could not read text from this image. Please use a clear photo of the job advertisement.',
                    ], 422);
                }

                $text = $ocr['raw_text'];
            } else {
                $parser = new Parser();
                $pdf = $parser->parseFile(Storage::disk('public')->path($storedPath));
                $text = $pdf->getText();

                if (strlen(trim($text)) < 50) {
                    return response()->json([
                        'error' => 'unreadable',
                        'message' => 'Could not extract text from this PDF. If it is a scanned image, upload a JPG/PNG photo instead.',
                    ], 422);
                }
            }

            Log::info('Job posting parse: text extracted', [
                'chars' => strlen($text),
                'preview' => substr($text, 0, 200),
            ]);

            $data = $this->gptJobParser->parse($text);

            if (empty($data['is_job_posting'])) {
                return response()->json([
                    'error' => 'not_job_posting',
                    'message' => 'This file does not appear to be a job advertisement. Please upload a job posting PDF or image.',
                ], 422);
            }

            return response()->json([
                'data' => $data,
                'document_stored_path' => $storedPath,
                'document_filename' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error('Job posting parse: exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE PASSPORT
    |--------------------------------------------------------------------------
    */

    public function parsePassport(Request $request)
    {
        $request->validate([
            'passport' => 'required|mimes:jpeg,jpg,png,pdf,tiff|max:5120'
        ]);

        if (! $this->aiConfigured()) {
            Log::error('Passport parse: OpenAI key not configured');
            return response()->json([
                'error'   => 'ai_unconfigured',
                'message' => 'AI extraction is not configured on the server. Please contact the administrator.',
            ], 503);
        }

        try {
            $this->bootServices();
            $file = $request->file('passport');

            // Store to a temp path that OCRService can read
            $tempPath = $file->store('temp/passport-ocr', 'public');

            // OCR.space scan
            $ocr = $this->ocr->scan($tempPath);

            if (!$ocr) {
                return response()->json(['error' => 'OCR failed — please try a clearer image.'], 422);
            }

            $rawText = $ocr['raw_text'] ?? '';

            // GPT structured extraction
            $extracted = $this->gptParser->parsePassport($rawText);

            // Resolve candidate if logged in
            $candidate = null;
            if (auth()->check()) {
                $candidate = Candidate::where('user_id', auth()->id())->first();
            }

            // Detect conflicts with existing DB record
            $existing  = [];
            $conflicts = [];

            if ($candidate) {
                $map = [
                    'passport_number' => 'passport_number',
                    'date_of_birth'   => 'birth_date',
                    'date_of_expiry'  => 'passport_expiry_date',
                    'place_of_issue'  => 'place_of_issue',
                ];

                foreach ($map as $extractedKey => $dbKey) {
                    $dbVal        = $candidate->{$dbKey} ?? null;
                    $extractedVal = $extracted[$extractedKey] ?? null;
                    $existing[$extractedKey] = $dbVal;

                    if ($dbVal && $extractedVal) {
                        // Normalize both to Y-m-d before comparing so format differences
                        // (e.g. "1999-08-20 00:00:00" vs "1999-08-20", "27-10-2035" vs "2035-10-27")
                        // don't produce false conflicts
                        $normDb  = $this->normalizeDate((string)$dbVal);
                        $normOcr = $this->normalizeDate((string)$extractedVal);

                        if ($normDb !== $normOcr) {
                            $conflicts[$extractedKey] = [
                                'db'  => $dbVal,
                                'ocr' => $extractedVal,
                            ];
                        }
                    }
                }
            }

            // Log to passport_ocr_logs (only when a candidate record exists)
            $logId = null;
            if ($candidate) {
                $log = PassportOcrLog::create([
                    'candidate_id'       => $candidate->id,
                    'raw_ocr_text'       => $rawText,
                    'extracted_fields'   => $extracted,
                    'existing_db_fields' => $existing,
                    'conflicts'          => $conflicts,
                    'status'             => 'pending_review',
                    'ocr_engine'         => 'ocr.space',
                ]);
                $logId = $log->id;

                if (empty($conflicts) && !empty($extracted)) {
                    $this->applyPassportFields($candidate, $extracted);
                    $log->update(['status' => 'confirmed', 'confirmed_at' => now()]);
                }
            }

            // Auto-save the uploaded passport into the candidate's Attachment
            // record so it shows (and persists) in the Attachment section,
            // exactly like the profile photo. Only images are stored here
            // (a PDF passport can't be previewed in the image slot).
            $attachmentUrl = null;
            if ($candidate && str_starts_with((string) $file->getMimeType(), 'image/')) {
                try {
                    // Write straight into public/storage/candidates (the served dir),
                    // exactly like the profile photo — the storage:link symlink is not
                    // reliable across environments, so we avoid the storage disk here.
                    $relativePath = uploadImage($file, 'storage/candidates');
                    $fileName     = basename($relativePath);
                    $attachment   = Attachment::firstOrNew(['candidate_id' => $candidate->id]);

                    if ($attachment->passport_image) {
                        deleteImage(public_path('storage/candidates/' . $attachment->passport_image));
                    }

                    $attachment->candidate_id   = $candidate->id;
                    $attachment->passport_image = $fileName;
                    $attachment->save();

                    // Documents page reads CandidateDocument, not Attachment
                    $docRecord = CandidateDocument::firstOrNew(['candidate_id' => $candidate->id]);
                    if ($docRecord->passport_image && $docRecord->passport_image !== $fileName) {
                        deleteImage(public_path('storage/candidates/' . $docRecord->passport_image));
                    }
                    $docRecord->candidate_id   = $candidate->id;
                    $docRecord->passport_image = $fileName;
                    $docRecord->save();

                    $attachmentUrl = asset('storage/candidates/' . $fileName);
                } catch (\Exception $e) {
                    Log::warning('Passport attachment auto-save failed: ' . $e->getMessage());
                }
            }

            return response()->json([
                'extracted'      => $extracted,
                'conflicts'      => $conflicts,
                'log_id'         => $logId,
                'attachment_url' => $attachmentUrl,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ATS SCORE
    |--------------------------------------------------------------------------
    */

    public function calculateATS(Request $request)
    {
        $request->validate([
            'cv_text'         => 'required|string',
            'job_description' => 'required|string'
        ]);

        if (! $this->aiConfigured()) {
            return response()->json([
                'error'   => 'ai_unconfigured',
                'message' => 'AI is not configured on the server. Please contact the administrator.',
            ], 503);
        }

        try {
            $this->bootServices();
            $prompt = "
You are an ATS system.

Compare the candidate CV with job description.

Return JSON:
{
    \"score\": number (0-100),
    \"matched_skills\": [],
    \"missing_skills\": [],
    \"strengths\": [],
    \"suggestions\": []
}

CV:
{$request->cv_text}

JOB:
{$request->job_description}
";

            $response = $this->openai->ask($prompt);

            $response = trim($response ?? '');
            $response = str_replace(['```json', '```'], '', $response);

            $data = json_decode($response, true);

            return response()->json($data ?? []);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE CV DATA TO DATABASE
    |--------------------------------------------------------------------------
    */

    public function saveCVData(Request $request)
    {
        $candidate = Candidate::where('user_id', auth()->id())->first();
        if (! $candidate) {
            return response()->json(['error' => 'Candidate profile not found'], 404);
        }

        $data = $request->input('data', []);
        $data = app(CvDataNormalizer::class)->normalize(array_merge(['is_cv' => true], $data));

        $cvStoredPath = $this->resolveCvStoredPath($request->input('cv_stored_path'));

        try {
            app(SeekerProfileWriter::class)->applyCv($candidate, $data, $cvStoredPath);

            $candidate->refresh()->load([
                'skills', 'languages', 'experiences', 'educations',
                'user.contactInfo', 'user.socialInfo',
                'experience', 'education', 'profession',
            ]);

            $saved  = $this->summarizeSavedFields($data, $candidate);
            $failed = $this->summarizeMissingFields($data, $saved);

            Log::info('CV auto-save: completed', [
                'candidate_id' => $candidate->id,
                'saved'        => $saved,
                'failed'       => $failed,
            ]);

            return response()->json(array_merge([
                'saved'     => $saved,
                'failed'    => $failed,
                'extracted' => $data,
                'profile'   => $this->buildProfileSnapshot($candidate),
                'cv'        => $this->buildCvSnapshot($candidate, $request->input('cv_filename')),
            ], [
                'completionPercentage' => $candidate->calculateProfileCompletion(),
                'profileCompletionMissing' => array_map(static fn ($section) => [
                    'key' => $section['key'],
                    'label' => $section['label'],
                    'hint' => $section['hint'],
                    'anchor' => $section['anchor'],
                ], $candidate->profileCompletionMissing()),
            ]));
        } catch (\Exception $e) {
            Log::error('CV auto-save: exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE BIO FROM PROFILE
    |--------------------------------------------------------------------------
    */

    public function generateBio(Request $request)
    {
        if (! $this->aiConfigured()) {
            return response()->json([
                'error'   => 'ai_unconfigured',
                'message' => 'AI is not configured on the server.',
            ], 503);
        }

        $candidate = Candidate::where('user_id', auth()->id())->first();
        if (! $candidate) {
            return response()->json(['error' => 'Candidate profile not found'], 404);
        }

        try {
            $this->bootServices();
            $candidate->load([
                'skills', 'languages', 'experiences', 'educations',
                'user.contactInfo', 'user.socialInfo',
                'experience', 'education', 'profession',
            ]);

            $profile    = $this->buildProfileSnapshot($candidate);
            $currentBio = trim(strip_tags((string) $request->input('current_bio', '')));
            $bio        = $this->gptParser->generateBio($profile, $currentBio ?: null);

            if (! $bio) {
                return response()->json([
                    'error'   => 'generation_failed',
                    'message' => 'Could not generate a bio. Add a few profile details and try again.',
                ], 422);
            }

            return response()->json(['bio' => $bio]);
        } catch (\Exception $e) {
            Log::error('Bio generate: exception', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function resolveCvStoredPath(?string $path): ?string
    {
        if (! $path || ! is_string($path)) {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));

        if (! Str::startsWith($path, ['temp/cv-uploads/', 'temp/cv-ocr/'])) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    private function buildCvSnapshot(Candidate $candidate, ?string $uploadFilename = null): ?array
    {
        if (! $candidate->cv) {
            return null;
        }

        $resume = $candidate->resumes()->latest()->first();
        $name   = $uploadFilename
            ?: ($resume?->name ?: basename($candidate->cv));

        return [
            'name' => $name,
            'file' => $candidate->cv,
            'url'  => asset($candidate->cv),
        ];
    }

    private function summarizeSavedFields(array $data, Candidate $candidate): array
    {
        $saved = [];

        $map = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'whatsapp' => 'WhatsApp',
            'gender' => 'Gender',
            'marital_status' => 'Marital Status',
            'date_of_birth' => 'Date of Birth',
            'bio' => 'Bio',
            'profession' => 'Profession',
            'experience_level' => 'Experience Level',
            'education_level' => 'Education Level',
            'country' => 'Country',
            'state' => 'State / Region',
            'city' => 'City',
            'website' => 'Website',
            'passport_number' => 'Passport Number',
            'passport_issue_date' => 'Passport Issue Date',
            'passport_expiry_date' => 'Passport Expiry',
            'place_of_issue' => 'Place of Issue',
            'cnic_number' => 'CNIC',
            'nationality' => 'Nationality',
            'job_preference_region' => 'Job Preference Region',
            'expected_salary' => 'Expected Salary',
            'salary_currency' => 'Salary Currency',
        ];

        foreach ($map as $key => $label) {
            if ($key === 'phone') {
                if (! empty($data['phone']) || ! empty($data['whatsapp'])) {
                    $saved[] = $label;
                }
                continue;
            }
            if ($key === 'whatsapp') {
                if (! empty($data['whatsapp']) || ! empty($data['phone'])) {
                    $saved[] = $label;
                }
                continue;
            }
            if (! empty($data[$key])) {
                $saved[] = $label;
            }
        }

        if (! empty($data['titles'][0])) {
            $saved[] = 'Job Title';
        }
        if (! empty($data['skills']) && is_array($data['skills'])) {
            $saved[] = 'Skills (' . count(array_filter($data['skills'])) . ')';
        }
        if (! empty($data['languages']) && is_array($data['languages'])) {
            $saved[] = 'Languages (' . count(array_filter($data['languages'])) . ')';
        }
        if (! empty($data['jobs']) && is_array($data['jobs'])) {
            $saved[] = 'Job Roles (' . count(array_filter($data['jobs'])) . ')';
        }
        if (! empty($data['industries']) && is_array($data['industries'])) {
            $saved[] = 'Industries (' . count(array_filter($data['industries'])) . ')';
        }
        if ($candidate->educations->isNotEmpty()) {
            $saved[] = 'Education (' . $candidate->educations->count() . ' records)';
        }
        if ($candidate->experiences->isNotEmpty()) {
            $saved[] = 'Work Experience (' . $candidate->experiences->count() . ' jobs)';
        }
        if ($candidate->user?->socialInfo && $candidate->user->socialInfo->isNotEmpty()) {
            $saved[] = 'Social Links (' . $candidate->user->socialInfo->count() . ')';
        }

        return $saved;
    }

    private function summarizeMissingFields(array $data, array $saved): array
    {
        $wanted = [
            'First Name', 'Last Name', 'Email', 'Phone', 'Country', 'City',
            'Profession', 'Experience Level', 'Education Level', 'Skills',
        ];
        $missing = [];
        foreach ($wanted as $label) {
            if (! in_array($label, $saved, true) && ! $this->savedLabelContains($saved, $label)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    private function savedLabelContains(array $saved, string $label): bool
    {
        foreach ($saved as $item) {
            if (str_starts_with($item, $label)) {
                return true;
            }
        }

        return false;
    }

    private function buildProfileSnapshot(Candidate $candidate): array
    {
        $contact = $candidate->user?->contactInfo;

        return [
            'first_name'  => $candidate->first_name,
            'last_name'   => $candidate->last_name,
            'full_name'   => trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')),
            'email'       => $contact->email ?? $candidate->user?->email,
            'phone'       => $contact->phone ?? null,
            'whatsapp'    => $candidate->whatsapp_number ?? $candidate->user?->whatsapp,
            'bio'         => strip_tags($candidate->bio ?? ''),
            'country'     => $candidate->basicLocationCountry(),
            'region'      => $candidate->region,
            'district'    => $candidate->district,
            'location'    => collect([$candidate->district, $candidate->region, $candidate->basicLocationCountry()])->filter()->implode(', '),
            'profession'  => $candidate->profession?->name ?? $candidate->title,
            'experience'  => $candidate->experience?->name,
            'education'   => $candidate->education?->name,
            'skills'      => $candidate->skills->pluck('name')->values()->all(),
            'languages'   => $candidate->languages->pluck('name')->values()->all(),
            'experiences' => $candidate->experiences->map(fn ($e) => [
                'company'     => $e->company,
                'department'  => $e->department,
                'designation' => $e->designation,
                'start'       => $e->start,
                'end'         => $e->end,
                'currently_working' => (bool) $e->currently_working,
            ])->values()->all(),
            'educations' => $candidate->educations->map(fn ($e) => [
                'level'  => $e->level,
                'degree' => $e->degree,
                'year'   => $e->year,
            ])->values()->all(),
            'social_links' => $candidate->user?->socialInfo?->map(fn ($s) => [
                'platform' => $s->social_media,
                'url'      => $s->url,
            ])->values()->all() ?? [],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | APPLY PASSPORT FIELDS (internal helper)
    |--------------------------------------------------------------------------
    */

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        foreach (['Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
            } catch (\Exception $e) {}
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return strtolower($value);
        }
    }

    private function applyPassportFields(Candidate $candidate, array $fields): void
    {
        // Only columns that actually exist in the candidates table
        $map = [
            'passport_number' => 'passport_number',
            'date_of_birth'   => 'birth_date',
            'date_of_expiry'  => 'passport_expiry_date',
            'place_of_issue'  => 'place_of_issue',
            'date_of_issue'   => 'passport_issue_date',
            'nationality'     => 'nationality',
            'national_id'     => 'cnic_number',
            'gender'          => 'gender',
            // 'place_of_birth' column does not exist in candidates table
            'surname'         => null,
            'given_names'     => null,
        ];

        $update = [];
        foreach ($map as $ocrKey => $dbKey) {
            if (!$dbKey || empty($fields[$ocrKey])) continue;

            $value = $fields[$ocrKey];

            // Normalize gender to match the enum('male','female','other') column
            if ($dbKey === 'gender') {
                $g = strtolower(trim($value));
                $value = match(true) {
                    in_array($g, ['m', 'male'])   => 'male',
                    in_array($g, ['f', 'female']) => 'female',
                    default => null,
                };
            }

            if ($value !== null) {
                $update[$dbKey] = $value;
            }
        }

        if (!empty($update)) {
            $candidate->update($update);
        }
    }
}

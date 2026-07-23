<?php

namespace App\Services\NominatedWorkers;

use App\Models\NominatedWorker;
use App\Models\NominatedWorkerDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NominatedWorkerMatchService
{
    public function storeDocument(
        UploadedFile $file,
        ?int $companyId,
        int $uploadedBy,
        ?int $workerId = null,
        ?string $label = null,
        ?int $agencyId = null
    ): NominatedWorkerDocument {
        $ownerKey = $companyId ?: ('agency-'.$agencyId);
        $path = $file->store('nominated-workers/'.$ownerKey, 'public');

        $doc = NominatedWorkerDocument::create([
            'nominated_worker_id' => $workerId,
            'company_id' => $companyId,
            'agency_id' => $agencyId,
            'uploaded_by' => $uploadedBy,
            'label' => $label,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize() ?: 0,
            'mime' => $file->getClientMimeType(),
            'match_status' => 'unmatched',
        ]);

        $this->runOcrAndMatch($doc);

        return $doc->fresh(['matchedWorker', 'worker']);
    }

    public function runOcrAndMatch(NominatedWorkerDocument $doc): void
    {
        $text = $this->extractText($doc);
        $doc->ocr_raw_text = $text;
        $fields = $this->extractFields($text);
        $doc->extracted_fields = $fields;

        $workers = NominatedWorker::query()
            ->when($doc->company_id, fn ($q) => $q->where('company_id', $doc->company_id))
            ->when($doc->agency_id && ! $doc->company_id, fn ($q) => $q->where('agency_id', $doc->agency_id))
            ->get();

        $best = null;
        $bestScore = 0.0;

        foreach ($workers as $worker) {
            $score = $this->score($worker, $fields, $text);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $worker;
            }
        }

        if ($best && $bestScore >= 40) {
            $doc->matched_worker_id = $best->id;
            $doc->match_confidence = round($bestScore, 2);
            $doc->match_status = $bestScore >= 70 ? 'matched' : 'suggested';
            if (! $doc->nominated_worker_id) {
                $doc->nominated_worker_id = $best->id;
            }
            if ($doc->match_status === 'matched' && $best->status === 'pending_docs') {
                $best->update(['status' => 'matched']);
            }
        } else {
            $doc->match_status = 'unmatched';
            $doc->match_confidence = $bestScore ?: null;
        }

        $doc->save();
    }

    protected function extractText(NominatedWorkerDocument $doc): string
    {
        try {
            if (class_exists(\App\Services\Chat\OCRService::class)) {
                $ocr = app(\App\Services\Chat\OCRService::class);
                if (method_exists($ocr, 'scan')) {
                    $result = $ocr->scan($doc->path);
                    if (is_array($result)) {
                        return (string) ($result['raw_text'] ?? '');
                    }
                }
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return Str::upper(pathinfo($doc->original_name, PATHINFO_FILENAME));
    }

    protected function extractFields(string $text): array
    {
        $upper = Str::upper($text);
        $passport = null;
        if (preg_match('/\b([A-Z]{1,2}\d{6,9})\b/', $upper, $m)) {
            $passport = $m[1];
        }

        return [
            'passport_number' => $passport,
            'raw_snippet' => Str::limit($text, 500),
        ];
    }

    protected function score(NominatedWorker $worker, array $fields, string $text): float
    {
        $score = 0.0;
        $upper = Str::upper($text);
        $passport = Str::upper((string) $worker->passport_number);

        if ($passport && ! empty($fields['passport_number']) && $passport === Str::upper($fields['passport_number'])) {
            $score += 70;
        } elseif ($passport && $passport !== '' && Str::contains($upper, $passport)) {
            $score += 55;
        }

        $name = Str::upper((string) $worker->full_name);
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name) ?: [];
            $hits = 0;
            foreach ($parts as $part) {
                if (strlen($part) > 2 && Str::contains($upper, $part)) {
                    $hits++;
                }
            }
            if ($hits >= 2) {
                $score += 30;
            } elseif ($hits === 1) {
                $score += 15;
            }
        }

        return min(100, $score);
    }

    public function importCsv(string $contents, ?int $companyId, int $createdBy, ?int $agencyId = null, ?int $batchId = null): int
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($contents)) ?: [];
        if (count($lines) < 2) {
            return 0;
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn ($h) => Str::slug(Str::lower(trim((string) $h)), '_'), $header);
        $count = 0;

        $batch = $batchId ? \App\Models\NominatedWorkerBatch::find($batchId) : null;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line);
            $data = [];
            foreach ($header as $i => $key) {
                $data[$key] = $row[$i] ?? null;
            }

            $name = $data['full_name'] ?? $data['name'] ?? null;
            if (! $name) {
                continue;
            }

            NominatedWorker::create([
                'batch_id' => $batchId,
                'company_id' => $companyId,
                'agency_id' => $agencyId ?: $batch?->agency_id,
                'created_by' => $createdBy,
                'full_name' => $name,
                'passport_number' => $data['passport_number'] ?? $data['passport'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'date_of_birth' => ! empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'destination_country' => $data['destination_country'] ?? $data['country'] ?? $batch?->country_name,
                'job_title' => $data['job_title'] ?? $data['job'] ?? null,
                'status' => 'pending_docs',
            ]);
            $count++;
        }

        return $count;
    }

    public function confirmMatch(NominatedWorkerDocument $doc, int $workerId): void
    {
        $workerQuery = NominatedWorker::query()->where('id', $workerId);
        if ($doc->company_id) {
            $workerQuery->where('company_id', $doc->company_id);
        }
        if ($doc->agency_id) {
            $workerQuery->where('agency_id', $doc->agency_id);
        }
        $worker = $workerQuery->firstOrFail();

        $doc->update([
            'matched_worker_id' => $worker->id,
            'nominated_worker_id' => $worker->id,
            'match_status' => 'matched',
            'match_confidence' => max((float) $doc->match_confidence, 100),
        ]);

        if ($worker->status === 'pending_docs') {
            $worker->update(['status' => 'matched']);
        }
    }
}

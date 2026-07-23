<?php

namespace App\Services\Candidates;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use App\Models\SearchCountry;
use App\Models\VpCase;
use Illuminate\Support\Str;

class CandidatePublicCodeService
{
    /**
     * Build / refresh public code: AABB + ORG + CC
     * Regenerates when name, org, or destination country inputs change.
     */
    public function sync(Candidate $candidate, ?Job $job = null, ?VpCase $case = null): string
    {
        $candidate->loadMissing('user');
        $name = (string) ($candidate->user?->name ?? '');
        [$first, $last] = $this->nameParts($name);
        $aa = $this->letters($first, 2);
        $bb = $this->letters($last ?: $first, 2);

        $org = $this->orgShortCode($candidate, $job, $case);
        $cc = $this->countryIso($job, $case);

        $code = strtoupper($aa.$bb.$org.$cc);

        $meta = [
            'first' => $aa,
            'last' => $bb,
            'org' => $org,
            'country' => $cc,
            'versioned_at' => now()->toIso8601String(),
        ];

        $existingMeta = is_array($candidate->public_code_meta) ? $candidate->public_code_meta : [];
        $fingerprint = $aa.'|'.$bb.'|'.$org.'|'.$cc;
        $oldFingerprint = ($existingMeta['first'] ?? '').'|'.($existingMeta['last'] ?? '').'|'.($existingMeta['org'] ?? '').'|'.($existingMeta['country'] ?? '');

        if ($candidate->public_code === $code && $fingerprint === $oldFingerprint) {
            return $code;
        }

        $candidate->forceFill([
            'public_code' => $code,
            'public_code_meta' => $meta,
        ])->save();

        return $code;
    }

    protected function nameParts(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        if (count($parts) === 0) {
            return ['XX', 'XX'];
        }
        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }

        return [$parts[0], $parts[count($parts) - 1]];
    }

    protected function letters(string $word, int $len): string
    {
        $clean = preg_replace('/[^A-Za-z]/', '', $word) ?: 'X';
        return strtoupper(Str::padRight(substr($clean, 0, $len), $len, 'X'));
    }

    protected function orgShortCode(Candidate $candidate, ?Job $job, ?VpCase $case): string
    {
        $companyId = $case?->company_id ?: $job?->company_id;
        $agencyId = $case?->agency_id ?: $job?->agency_id;

        if ($companyId) {
            $company = Company::with('user')->find($companyId);
            $raw = $company?->user?->username ?: $company?->user?->name ?: 'CMP';

            return $this->slugOrg($raw);
        }

        if ($agencyId) {
            $agency = Agency::with('user')->find($agencyId);
            $raw = $agency?->user?->username ?: $agency?->user?->name ?: 'AGY';

            return $this->slugOrg($raw);
        }

        return 'OGS';
    }

    protected function slugOrg(string $raw): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $raw) ?: 'ORG';

        return strtoupper(substr($clean, 0, 4));
    }

    protected function countryIso(?Job $job, ?VpCase $case): string
    {
        $name = $case?->country_name ?: ($job?->country ?? $job?->exact_location ?? null);
        if (! $name) {
            return 'XX';
        }

        $country = SearchCountry::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $name))])
            ->orWhereRaw('LOWER(short_name) = ?', [mb_strtolower(trim((string) $name))])
            ->first();

        if ($country?->short_name) {
            return strtoupper(substr($country->short_name, 0, 2));
        }

        $clean = preg_replace('/[^A-Za-z]/', '', (string) $name) ?: 'XX';

        return strtoupper(substr($clean, 0, 2));
    }
}

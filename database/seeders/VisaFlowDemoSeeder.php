<?php

namespace Database\Seeders;

use App\Models\SearchCountry;
use App\Models\VisaFlow;
use App\Support\VisaLiability;
use Illuminate\Database\Seeder;

class VisaFlowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCountryFlow('Saudi Arabia', 'Work', $this->saudiSteps());
        $this->seedCountryFlow('United Arab Emirates', 'Work', $this->uaeSteps());
        // Alias common naming
        if (! VisaFlow::whereRaw('LOWER(country_name) = ?', ['uae'])->exists()) {
            $uae = VisaFlow::whereRaw('LOWER(country_name) like ?', ['%united arab%'])->first();
            // keep single UAE entry by SearchCountry name
        }
    }

    protected function seedCountryFlow(string $countryName, string $visaType, array $steps): void
    {
        $searchCountry = SearchCountry::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($countryName)])
            ->orWhereRaw('LOWER(name) like ?', ['%'.strtolower(explode(' ', $countryName)[0]).'%'.(str_contains($countryName, 'Arab') ? 'arab%' : '')])
            ->first();

        if ($countryName === 'United Arab Emirates') {
            $searchCountry = SearchCountry::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) like ?', ['%united arab%'])
                        ->orWhereRaw('LOWER(name) = ?', ['uae'])
                        ->orWhereRaw('LOWER(short_name) = ?', ['ae']);
                })
                ->first();
        } elseif ($countryName === 'Saudi Arabia') {
            $searchCountry = SearchCountry::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) like ?', ['%saudi%'])
                        ->orWhereRaw('LOWER(short_name) = ?', ['sa']);
                })
                ->first();
        }

        $flow = VisaFlow::query()
            ->when($searchCountry, fn ($q) => $q->where('search_country_id', $searchCountry->id))
            ->orWhereRaw('LOWER(country_name) = ?', [strtolower($countryName)])
            ->first();

        if ($flow && $flow->steps()->count() >= count($steps)) {
            $flow->update([
                'publish_status' => $flow->publish_status ?: 'published',
                'version' => max(1, (int) $flow->version),
                'is_active' => true,
                'search_country_id' => $flow->search_country_id ?: $searchCountry?->id,
            ]);

            return;
        }

        if (! $flow) {
            $flow = VisaFlow::create([
                'country_name' => $searchCountry?->name ?: $countryName,
                'search_country_id' => $searchCountry?->id,
                'visa_type' => $visaType,
                'is_active' => true,
                'publish_status' => 'published',
                'version' => 1,
            ]);
        } else {
            $flow->update([
                'country_name' => $searchCountry?->name ?: $countryName,
                'search_country_id' => $searchCountry?->id ?: $flow->search_country_id,
                'visa_type' => $visaType,
                'is_active' => true,
                'publish_status' => 'published',
                'version' => max(1, (int) $flow->version),
            ]);
            $flow->steps()->delete();
        }

        foreach ($steps as $i => $stepDef) {
            $step = $flow->steps()->create([
                'name' => $stepDef['name'],
                'description' => $stepDef['description'] ?? null,
                'assignee' => $stepDef['assignee'],
                'estimated_duration_days' => $stepDef['days'] ?? null,
                'sort_order' => $i,
                'is_active' => true,
            ]);
            foreach ($stepDef['reqs'] as $j => $req) {
                $step->requirements()->create([
                    'label' => $req['label'],
                    'type' => $req['type'],
                    'is_required' => $req['required'] ?? true,
                    'sort_order' => $j,
                    'is_active' => true,
                ]);
            }
        }
    }

    protected function saudiSteps(): array
    {
        $E = VisaLiability::EMPLOYER;
        $A = VisaLiability::AGENCY;
        $S = VisaLiability::SEEKER;

        return [
            ['name' => 'Wakala Transfer (Enjaz / MOFA)', 'assignee' => $E, 'days' => 7, 'description' => 'Employer initiates Wakala; agency coordinates rectification.', 'reqs' => [
                ['label' => 'Wakala / Enjaz reference', 'type' => 'text'],
                ['label' => 'Company Saudi CR / ID proof', 'type' => 'file'],
            ]],
            ['name' => 'Employment Agreement Paper (Embassy)', 'assignee' => $E, 'days' => 5, 'reqs' => [
                ['label' => 'Signed employment agreement', 'type' => 'file'],
            ]],
            ['name' => 'Embassy Supporting Requirements', 'assignee' => $S, 'days' => 14, 'reqs' => [
                ['label' => 'Police Clearance Certificate', 'type' => 'file'],
                ['label' => 'Medical Certificate (GAMCA/Wafid)', 'type' => 'file'],
                ['label' => 'Trade/Skill Test report (if applicable)', 'type' => 'file', 'required' => false],
                ['label' => 'VFS fingerprint attendance confirmed', 'type' => 'checkbox'],
            ]],
            ['name' => 'Visa Stamping', 'assignee' => $A, 'days' => 10, 'reqs' => [
                ['label' => 'Embassy submission receipt', 'type' => 'file'],
                ['label' => 'Passport with stamped visa', 'type' => 'file'],
            ]],
            ['name' => 'Ticketing & Travel', 'assignee' => $A, 'days' => 7, 'reqs' => [
                ['label' => 'Flight ticket', 'type' => 'file'],
                ['label' => 'Travel document pack issued', 'type' => 'checkbox'],
            ]],
            ['name' => 'Arrival & Iqama Processing', 'assignee' => $A, 'days' => 14, 'reqs' => [
                ['label' => 'Iqama / Muqeem reference', 'type' => 'text'],
                ['label' => 'In-Kingdom medical (if required)', 'type' => 'file', 'required' => false],
            ]],
            ['name' => 'Deployment Confirmation', 'assignee' => $E, 'days' => 3, 'reqs' => [
                ['label' => 'Joining confirmed', 'type' => 'checkbox'],
                ['label' => 'Deployment report', 'type' => 'file', 'required' => false],
            ]],
        ];
    }

    protected function uaeSteps(): array
    {
        $E = VisaLiability::EMPLOYER;
        $A = VisaLiability::AGENCY;
        $S = VisaLiability::SEEKER;

        return [
            ['name' => 'Offer & Documentation', 'assignee' => $E, 'days' => 5, 'reqs' => [
                ['label' => 'Signed offer letter', 'type' => 'file'],
                ['label' => 'Trade license & MOHRE establishment card', 'type' => 'file'],
                ['label' => 'Agency authorization letter', 'type' => 'file'],
            ]],
            ['name' => 'MOHRE e-Work Permit / Entry Permit', 'assignee' => $A, 'days' => 10, 'reqs' => [
                ['label' => 'e-Work Permit approval reference', 'type' => 'text'],
                ['label' => 'Approval document', 'type' => 'file'],
            ]],
            ['name' => 'Labour Approval (Labour Quota)', 'assignee' => $A, 'days' => 7, 'reqs' => [
                ['label' => 'Labour approval reference', 'type' => 'text'],
            ]],
            ['name' => 'Labour / Security Clearance', 'assignee' => $S, 'days' => 10, 'reqs' => [
                ['label' => 'Passport bio-data page', 'type' => 'file'],
                ['label' => 'Photo', 'type' => 'file'],
                ['label' => 'Personal declaration', 'type' => 'file', 'required' => false],
            ]],
            ['name' => 'Entry Visa Issuance', 'assignee' => $A, 'days' => 7, 'reqs' => [
                ['label' => 'Entry visa copy', 'type' => 'file'],
                ['label' => 'Medical fitness certificate', 'type' => 'file'],
            ]],
            ['name' => 'Ticketing & Travel', 'assignee' => $A, 'days' => 7, 'reqs' => [
                ['label' => 'Flight ticket', 'type' => 'file'],
                ['label' => 'Travel pack issued', 'type' => 'checkbox'],
            ]],
            ['name' => 'Arrival, Emirates ID & Labour Card', 'assignee' => $A, 'days' => 14, 'reqs' => [
                ['label' => 'Emirates ID application ref', 'type' => 'text'],
                ['label' => 'Labour card proof', 'type' => 'file', 'required' => false],
            ]],
            ['name' => 'Deployment Confirmation', 'assignee' => $E, 'days' => 3, 'reqs' => [
                ['label' => 'Joining confirmed', 'type' => 'checkbox'],
                ['label' => 'Deployment report', 'type' => 'file', 'required' => false],
            ]],
        ];
    }
}

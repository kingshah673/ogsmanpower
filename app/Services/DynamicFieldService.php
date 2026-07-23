<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateAttribute;
use App\Models\Company;
use App\Models\CompanyAttribute;
use App\Models\CompanyAttributeTranslation;
use Illuminate\Support\Collection;

class DynamicFieldService
{
    public static function seekerSections(): array
    {
        return config('dynamic_fields.seeker_sections', []);
    }

    public static function employerSections(): array
    {
        return config('dynamic_fields.employer_sections', []);
    }

    public static function seekerBuiltinFields(): array
    {
        return config('dynamic_fields.seeker_builtin_fields', []);
    }

    public static function employerBuiltinFields(): array
    {
        return config('dynamic_fields.employer_builtin_fields', []);
    }

    public static function builtinFieldsForSection(string $portal, string $section): array
    {
        $map = $portal === 'employer'
            ? self::employerBuiltinFields()
            : self::seekerBuiltinFields();

        return $map[$section] ?? [];
    }

    /**
     * Global seeker field definitions for admin (excludes per-candidate value rows).
     */
    public static function seekerDefinitions(): Collection
    {
        return CandidateAttribute::query()
            ->whereNull('definition_id')
            ->where(function ($query) {
                $query->whereNull('candidate_id')
                    ->orWhere('candidate_id', 0);
            })
            ->orderBy('section')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (CandidateAttribute $attribute) {
                if (empty($attribute->section)) {
                    $attribute->section = 'basic-info';
                }

                return $attribute;
            });
    }

    /**
     * All employer field definitions for admin.
     */
    public static function employerDefinitions(): Collection
    {
        return CompanyAttribute::query()
            ->orderBy('section')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (CompanyAttribute $attribute) {
                if (empty($attribute->section)) {
                    $attribute->section = 'job_post';
                }

                return $attribute;
            });
    }

    /**
     * @return array<string, \Illuminate\Support\Collection>
     */
    public static function groupBySections(Collection $attributes, array $sections, string $defaultSection = 'basic-info'): array
    {
        $grouped = $attributes->groupBy(function ($attribute) use ($sections, $defaultSection) {
            $section = $attribute->section ?? '';
            if ($section === '' || ! array_key_exists($section, $sections)) {
                return array_key_exists($defaultSection, $sections)
                    ? $defaultSection
                    : (array_key_first($sections) ?: $defaultSection);
            }

            return $section;
        });

        $result = [];
        foreach ($sections as $key => $label) {
            $result[$key] = $grouped->get($key, collect());
        }

        foreach ($grouped as $key => $items) {
            if (! array_key_exists($key, $result)) {
                $result[$key] = $items;
            }
        }

        return $result;
    }

    /**
     * Fields to render on seeker settings for one section (definitions + values).
     */
    public static function seekerFieldsForSection(Candidate $candidate, string $section): Collection
    {
        $definitions = CandidateAttribute::query()
            ->whereNull('candidate_id')
            ->whereNull('definition_id')
            ->where('section', $section)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $values = CandidateAttribute::query()
            ->where('candidate_id', $candidate->id)
            ->whereNotNull('definition_id')
            ->get()
            ->keyBy('definition_id');

        $merged = $definitions->map(function (CandidateAttribute $def) use ($values) {
            $val = $values->get($def->id);

            return (object) [
                'id' => $def->id,
                'definition_id' => $def->id,
                'attribute_name' => $def->attribute_name,
                'input_type' => $def->input_type,
                'attribute_value' => $val?->attribute_value ?? '',
                'options' => $def->options,
                'is_required' => (bool) $def->is_required,
            ];
        });

        // Legacy: per-candidate rows created before global definitions (no definition_id).
        $legacy = CandidateAttribute::query()
            ->where('candidate_id', $candidate->id)
            ->whereNull('definition_id')
            ->where('section', $section)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CandidateAttribute $row) => (object) [
                'id' => $row->id,
                'definition_id' => $row->id,
                'attribute_name' => $row->attribute_name,
                'input_type' => $row->input_type,
                'attribute_value' => $row->attribute_value ?? '',
                'options' => $row->options,
                'is_required' => (bool) $row->is_required,
            ]);

        return $merged->concat($legacy)->values();
    }

    public static function seekerFieldsGroupedBySection(Candidate $candidate): array
    {
        $grouped = [];
        foreach (array_keys(self::seekerSections()) as $section) {
            $fields = self::seekerFieldsForSection($candidate, $section);
            if ($fields->isNotEmpty()) {
                $grouped[$section] = $fields;
            }
        }

        return $grouped;
    }

    /**
     * Persist seeker dynamic field values from any settings form.
     *
     * @param  array<int, array{id?: mixed, definition_id?: mixed, value?: mixed}>  $inputs
     */
    public static function saveSeekerFieldValues(Candidate $candidate, array $inputs): void
    {
        foreach ($inputs as $inputData) {
            if (! is_array($inputData)) {
                continue;
            }

            $definitionId = $inputData['definition_id'] ?? $inputData['id'] ?? null;
            if (! $definitionId) {
                continue;
            }

            $definition = CandidateAttribute::query()
                ->whereNull('candidate_id')
                ->where('id', $definitionId)
                ->first();

            if ($definition) {
                CandidateAttribute::updateOrCreate(
                    [
                        'candidate_id' => $candidate->id,
                        'definition_id' => $definition->id,
                    ],
                    [
                        'section' => $definition->section,
                        'attribute_name' => $definition->attribute_name,
                        'input_type' => $definition->input_type,
                        'options' => $definition->options,
                        'is_required' => $definition->is_required,
                        'is_active' => 1,
                        'attribute_value' => $inputData['value'] ?? null,
                    ]
                );

                continue;
            }

            // Legacy row: update value on the same record.
            $legacy = CandidateAttribute::query()
                ->where('candidate_id', $candidate->id)
                ->where('id', $definitionId)
                ->whereNull('definition_id')
                ->first();

            if ($legacy) {
                $legacy->update(['attribute_value' => $inputData['value'] ?? null]);
            }
        }
    }

    public static function employerFieldsForSection(Company $company, string $section): Collection
    {
        $definitions = CompanyAttribute::query()
            ->where('section', $section)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $values = CompanyAttributeTranslation::query()
            ->where('company_id', $company->id)
            ->whereNull('job_id')
            ->get()
            ->keyBy('company_attribute_id');

        return $definitions->map(function (CompanyAttribute $def) use ($values) {
            $val = $values->get($def->id);

            return (object) [
                'id' => $def->id,
                'definition_id' => $def->id,
                'attribute_name' => $def->attribute_name,
                'input_type' => $def->input_type,
                'attribute_value' => $val?->attribute_value ?? '',
                'options' => $def->options ?? null,
                'is_required' => (bool) $def->is_required,
            ];
        });
    }

    public static function employerFieldsGroupedBySection(Company $company): array
    {
        $grouped = [];
        foreach (array_keys(self::employerSections()) as $section) {
            $fields = self::employerFieldsForSection($company, $section);
            if ($fields->isNotEmpty()) {
                $grouped[$section] = $fields;
            }
        }

        return $grouped;
    }

    /**
     * @param  array<int, array{id?: mixed, definition_id?: mixed, value?: mixed}>  $inputs
     */
    public static function saveEmployerFieldValues(Company $company, array $inputs): void
    {
        foreach ($inputs as $inputData) {
            if (! is_array($inputData)) {
                continue;
            }

            $attributeId = $inputData['definition_id'] ?? $inputData['id'] ?? null;
            if (! $attributeId) {
                continue;
            }

            CompanyAttributeTranslation::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'job_id' => null,
                    'company_attribute_id' => $attributeId,
                ],
                [
                    'attribute_value' => $inputData['value'] ?? null,
                ]
            );
        }
    }

    public static function parseDropdownOptions(?string $options): array
    {
        if (empty($options)) {
            return [];
        }

        $decoded = json_decode($options, true);
        if (is_array($decoded)) {
            return array_values($decoded);
        }

        return array_values(array_filter(array_map('trim', explode("\n", $options))));
    }
}

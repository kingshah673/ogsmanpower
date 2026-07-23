<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class JobCreateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->method() === 'PUT' || $this->boolean('is_remote')) {
            return;
        }

        if (session('location')) {
            return;
        }

        if (! config('templatecookie.map_show') && $this->filled('country')) {
            session()->put('location', [
                'country' => $this->input('country'),
                'region' => $this->input('state'),
                'district' => $this->input('district'),
                'lng' => session('selectedCityLong') ?? session('selectedStateLong') ?? session('selectedCountryLong'),
                'lat' => session('selectedCityLat') ?? session('selectedStateLat') ?? session('selectedCountryLat'),
            ]);
        }

        if ($this->filled('deadline')) {
            $raw = trim((string) $this->deadline);
            foreach (['d-m-Y', 'd/m/Y', 'm-d-Y', 'Y-m-d'] as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, $raw);
                    if ($parsed !== false) {
                        $this->merge(['deadline' => $parsed->format('Y-m-d')]);
                        break;
                    }
                } catch (\Throwable) {
                }
            }
        }
    }

    public function rules()
    {
        $requiresLocation = $this->method() !== 'PUT'
            && ! $this->boolean('is_remote');

        return [
            'title' => 'required|string|max:255',
            'category_id' => 'required',
            'role_id' => 'required',
            'experience' => 'required',
            'education' => 'nullable',
            'job_type' => 'required',
            'vacancies' => 'required',
            'salary_mode' => 'required',
            'custom_salary' => 'required_if:salary_mode,==,custom',
            'min_salary' => 'nullable|numeric',
            'max_salary' => 'nullable|numeric',
            'salary_type' => 'required',
            'deadline' => 'required|date',
            'description' => 'required|string',
            'title_ar' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'featured' => 'nullable|numeric',
            'is_remote' => 'nullable|numeric',
            'apply_on' => 'required',
            'country' => [
                Rule::requiredIf($requiresLocation && ! config('templatecookie.map_show')),
                'nullable',
                'string',
            ],
            'location' => [
                Rule::requiredIf($requiresLocation && config('templatecookie.map_show') && ! session('location')),
                'nullable',
                'string',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->method() === 'PUT' || $this->boolean('is_remote')) {
                return;
            }

            if (session('location')) {
                return;
            }

            if (config('templatecookie.map_show')) {
                $validator->errors()->add(
                    'location',
                    __('Please select a location on the map before posting the job.')
                );

                return;
            }

            if (! $this->filled('country')) {
                $validator->errors()->add(
                    'location',
                    __('Please select country, state, and city for this job.')
                );
            }

            $plainDescription = trim(strip_tags((string) $this->description));
            if (mb_strlen($plainDescription) < 30) {
                $validator->errors()->add(
                    'description',
                    __('The job description must be at least 30 characters of text (HTML formatting does not count).')
                );
            }
        });
    }

    public function messages()
    {
        return [
            'country.required' => __('Please select a country for this job.'),
            'location.required' => __('Please select a location for this job.'),
            'deadline.date' => __('Please enter a valid application deadline (e.g. 24-07-2026).'),
        ];
    }
}

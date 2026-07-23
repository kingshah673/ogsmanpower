<?php

namespace App\Http\Traits;

use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use Illuminate\Http\Request;

trait CandidateSkillAble
{
    private function traitWantsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    private function traitCompletionPayload(): array
    {
        $candidate = currentCandidate();
        if (! $candidate) {
            return [];
        }
        $candidate->refresh();

        return [
            'completionPercentage' => $candidate->calculateProfileCompletion(),
            'profileCompletionMissing' => array_map(static fn ($section) => [
                'key' => $section['key'],
                'label' => $section['label'],
                'hint' => $section['hint'],
                'anchor' => $section['anchor'],
            ], $candidate->profileCompletionMissing()),
        ];
    }

    private function traitJsonSuccess(Request $request, string $message, array $extra = [])
    {
        if ($this->traitWantsJson($request)) {
            return response()->json(array_merge([
                'success' => true,
                'message' => $message,
            ], $this->traitCompletionPayload(), $extra));
        }

        return back()->with('success', $message);
    }

    public function experienceStore(Request $request)
    {
        $request->session()->put('type', 'experience');

        $request->validate([
            'company' => 'required',
            'department' => 'required',
            'designation' => 'required',
            'start' => 'required',
            'end' => 'sometimes',
        ]);

        $start_date = $request->start ? formatTime($request->start, 'Y-m-d') : null;
        $end_date = $request->end ? formatTime($request->end, 'Y-m-d') : null;

        $experience = CandidateExperience::create([
            'candidate_id' => currentCandidate()->id,
            'company' => $request->company,
            'department' => $request->department,
            'designation' => $request->designation,
            'start' => $start_date,
            'end' => $end_date,
            'responsibilities' => $request->responsibilities,
            'currently_working' => $request->currently_working ?? 0,
        ]);

        return $this->traitJsonSuccess($request, 'Experience added successfully', [
            'item' => $this->formatExperienceRow($experience),
            'action' => 'create',
            'resource' => 'experience',
        ]);
    }

    public function experienceUpdate(Request $request)
    {
        $request->session()->put('type', 'experience');

        $request->validate([
            'company' => 'required',
            'designation' => 'required',
            'department' => 'required',
            'start' => 'required',
            'end' => 'sometimes',
        ]);

        $experience = CandidateExperience::findOrFail($request->experience_id);

        $start_date = $request->start ? formatTime($request->start, 'Y-m-d') : null;
        $end_date = $request->end ? formatTime($request->end, 'Y-m-d') : null;

        $experience->update([
            'candidate_id' => currentCandidate()->id,
            'company' => $request->company,
            'department' => $request->department,
            'designation' => $request->designation,
            'start' => $start_date,
            'end' => $end_date,
            'responsibilities' => $request->responsibilities,
            'currently_working' => $request->currently_working ?? 0,
        ]);

        return $this->traitJsonSuccess($request, 'Experience updated successfully', [
            'item' => $this->formatExperienceRow($experience->fresh()),
            'action' => 'update',
            'resource' => 'experience',
        ]);
    }

    public function experienceDelete(Request $request, CandidateExperience $experience)
    {
        session()->put('type', 'experience');

        $id = $experience->id;
        $experience->delete();

        return $this->traitJsonSuccess($request, 'Experience deleted successfully', [
            'id' => $id,
            'action' => 'delete',
            'resource' => 'experience',
        ]);
    }

    public function educationStore(Request $request)
    {
        $request->session()->put('type', 'experience');

        $request->validate([
            'level' => 'required',
            'degree' => 'required',
            'year' => 'required',
        ]);

        $education = CandidateEducation::create([
            'candidate_id' => currentCandidate()->id,
            'level' => $request->level,
            'degree' => $request->degree,
            'year' => $request->year,
            'notes' => $request->notes,
        ]);

        return $this->traitJsonSuccess($request, 'Education added successfully', [
            'item' => $this->formatEducationRow($education),
            'action' => 'create',
            'resource' => 'education',
        ]);
    }

    public function educationUpdate(Request $request)
    {
        $request->session()->put('type', 'experience');

        $request->validate([
            'level' => 'required',
            'degree' => 'required',
            'year' => 'required',
        ]);

        $education = CandidateEducation::findOrFail($request->education_id);

        $education->update([
            'candidate_id' => currentCandidate()->id,
            'level' => $request->level,
            'degree' => $request->degree,
            'year' => $request->year,
            'notes' => $request->notes,
        ]);

        return $this->traitJsonSuccess($request, 'Education updated successfully', [
            'item' => $this->formatEducationRow($education->fresh()),
            'action' => 'update',
            'resource' => 'education',
        ]);
    }

    public function educationDelete(Request $request, CandidateEducation $education)
    {
        session()->put('type', 'experience');

        $id = $education->id;
        $education->delete();

        return $this->traitJsonSuccess($request, 'Education deleted successfully', [
            'id' => $id,
            'action' => 'delete',
            'resource' => 'education',
        ]);
    }

    private function formatExperienceRow(CandidateExperience $experience): array
    {
        $start = $experience->start ? date('d M Y', strtotime($experience->start)) : '';
        $end = $experience->currently_working
            ? 'Currently Working'
            : ($experience->end ? date('d M Y', strtotime($experience->end)) : '—');

        return [
            'id' => $experience->id,
            'company' => $experience->company,
            'department' => $experience->department,
            'designation' => $experience->designation,
            'period' => trim($start . ' – ' . $end),
            'start' => $experience->start,
            'end' => $experience->end,
            'start_formatted' => $experience->start ? date('d-m-Y', strtotime($experience->start)) : '',
            'end_formatted' => $experience->end ? date('d-m-Y', strtotime($experience->end)) : '',
            'responsibilities' => $experience->responsibilities,
            'currently_working' => (bool) $experience->currently_working,
        ];
    }

    private function formatEducationRow(CandidateEducation $education): array
    {
        return [
            'id' => $education->id,
            'level' => $education->level,
            'degree' => $education->degree,
            'year' => $education->year,
            'notes' => $education->notes,
        ];
    }
}

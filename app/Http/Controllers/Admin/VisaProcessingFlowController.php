<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchCountry;
use App\Models\VisaFlow;
use App\Models\VisaFlowRequirement;
use App\Models\VisaFlowStep;
use App\Support\VisaLiability;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisaProcessingFlowController extends Controller
{
    public function index()
    {
        $flows = VisaFlow::withCount('steps')->with('searchCountry')->latest()->paginate(20);

        return view('backend.visa-processing.flows.index', compact('flows'));
    }

    public function create()
    {
        $countries = SearchCountry::query()->orderBy('name')->get(['id', 'name', 'short_name']);

        return view('backend.visa-processing.flows.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'search_country_id' => [
                'required',
                'integer',
                'exists:search_countries,id',
                Rule::unique('visa_flows', 'search_country_id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'visa_type' => 'nullable|string|max:120',
            'is_active' => 'nullable|boolean',
        ], [
            'search_country_id.unique' => 'An active visa flow already exists for this country.',
        ]);

        $country = SearchCountry::findOrFail($data['search_country_id']);

        $flow = VisaFlow::create([
            'search_country_id' => $country->id,
            'country_name' => $country->name,
            'visa_type' => $data['visa_type'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'publish_status' => 'draft',
            'version' => 1,
        ]);

        flashSuccess('Visa flow created as draft. Add steps, then Publish.');

        return redirect()->route('admin.visa-flows.edit', $flow->id);
    }

    public function edit(VisaFlow $visa_flow)
    {
        $this->ensureSearchCountryLinked($visa_flow);

        $visa_flow->load([
            'steps.requirements' => fn ($q) => $q->orderBy('sort_order'),
            'searchCountry',
        ]);
        $countries = SearchCountry::query()->orderBy('name')->get(['id', 'name', 'short_name']);

        return view('backend.visa-processing.flows.edit', [
            'flow' => $visa_flow,
            'countries' => $countries,
            'assignees' => VisaLiability::assignees(),
        ]);
    }

    protected function ensureSearchCountryLinked(VisaFlow $flow): void
    {
        if ($flow->search_country_id || ! filled($flow->country_name)) {
            return;
        }

        $country = SearchCountry::query()
            ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $flow->country_name))])
            ->first();

        if (! $country) {
            return;
        }

        $flow->forceFill(['search_country_id' => $country->id])->save();
    }

    public function update(Request $request, VisaFlow $visa_flow)
    {
        $data = $request->validate([
            'search_country_id' => [
                'required',
                'integer',
                'exists:search_countries,id',
                Rule::unique('visa_flows', 'search_country_id')
                    ->ignore($visa_flow->id)
                    ->where(fn ($q) => $q->where('is_active', true)),
            ],
            'visa_type' => 'nullable|string|max:120',
            'is_active' => 'nullable|boolean',
        ], [
            'search_country_id.unique' => 'An active visa flow already exists for this country.',
        ]);

        $country = SearchCountry::findOrFail($data['search_country_id']);

        $visa_flow->update([
            'search_country_id' => $country->id,
            'country_name' => $country->name,
            'visa_type' => $data['visa_type'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        flashSuccess('Flow updated.');

        return back();
    }

    public function publish(VisaFlow $visa_flow)
    {
        if ($visa_flow->activeSteps()->count() === 0) {
            flashError('Add at least one active step before publishing.');

            return back();
        }

        $visa_flow->publish();
        flashSuccess('Flow published as version '.$visa_flow->fresh()->version.'. Existing batches keep their frozen version.');

        return back();
    }

    public function markDraft(VisaFlow $visa_flow)
    {
        $visa_flow->markDraft();
        flashSuccess('Flow marked as draft. It will not attach to new batches until published again.');

        return back();
    }

    public function storeStep(Request $request, VisaFlow $visa_flow)
    {
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'description' => 'nullable|string',
            'assignee' => ['required', VisaLiability::validationRule()],
            'estimated_duration_days' => 'nullable|integer|min:1|max:365',
        ]);

        $order = (int) $visa_flow->steps()->max('sort_order') + 1;
        $visa_flow->steps()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'assignee' => $data['assignee'],
            'estimated_duration_days' => $data['estimated_duration_days'] ?? null,
            'sort_order' => $order,
            'is_active' => true,
        ]);

        flashSuccess('Step added.');

        return back();
    }

    public function updateStep(Request $request, VisaFlowStep $step)
    {
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'description' => 'nullable|string',
            'assignee' => ['required', VisaLiability::validationRule()],
            'estimated_duration_days' => 'nullable|integer|min:1|max:365',
            'is_active' => 'nullable|boolean',
        ]);

        $step->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'assignee' => $data['assignee'],
            'estimated_duration_days' => $data['estimated_duration_days'] ?? null,
            'is_active' => $request->boolean('is_active', $step->is_active),
        ]);

        flashSuccess('Step updated.');

        return back();
    }

    public function moveStep(Request $request, VisaFlowStep $step)
    {
        $data = $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        $siblings = VisaFlowStep::query()
            ->where('visa_flow_id', $step->visa_flow_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (VisaFlowStep $s) => (int) $s->id === (int) $step->id);
        if ($index === false) {
            return back();
        }

        $swapWith = $data['direction'] === 'up' ? $index - 1 : $index + 1;
        if ($swapWith < 0 || $swapWith >= $siblings->count()) {
            return back();
        }

        $other = $siblings[$swapWith];
        $currentOrder = $step->sort_order;
        $step->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $currentOrder]);

        VisaFlowStep::query()
            ->where('visa_flow_id', $step->visa_flow_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (VisaFlowStep $remaining, int $i) {
                if ((int) $remaining->sort_order !== $i) {
                    $remaining->update(['sort_order' => $i]);
                }
            });

        flashSuccess('Step order updated.');

        return back();
    }

    public function storeRequirement(Request $request, VisaFlowStep $step)
    {
        $data = $request->validate([
            'label' => 'required|string|max:180',
            'type' => 'required|in:file,text,date,checkbox',
            'is_required' => 'nullable|boolean',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('visa_flow_requirements', 'id')->where('visa_flow_step_id', $step->id),
            ],
        ]);

        $order = (int) $step->requirements()->max('sort_order') + 1;
        $step->requirements()->create([
            'label' => $data['label'],
            'type' => $data['type'],
            'is_required' => $request->boolean('is_required', true),
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $order,
            'is_active' => true,
        ]);

        flashSuccess('Requirement added.');

        return back();
    }

    public function updateRequirement(Request $request, VisaFlowRequirement $requirement)
    {
        $step = $requirement->step;
        $data = $request->validate([
            'label' => 'required|string|max:180',
            'type' => 'required|in:file,text,date,checkbox',
            'is_required' => 'nullable|boolean',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('visa_flow_requirements', 'id')->where('visa_flow_step_id', $step->id),
                Rule::notIn([(int) $requirement->id]),
            ],
        ]);

        $requirement->update([
            'label' => $data['label'],
            'type' => $data['type'],
            'is_required' => $request->boolean('is_required', $requirement->is_required),
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        flashSuccess('Requirement updated.');

        return back();
    }

    public function deactivateRequirement(VisaFlowRequirement $requirement)
    {
        $requirement->update(['is_active' => false]);
        flashSuccess('Requirement deactivated.');

        return back();
    }

    public function destroyRequirement(VisaFlowRequirement $requirement)
    {
        $requirement->delete();
        flashSuccess('Requirement deleted.');

        return back();
    }

    public function deactivateStep(VisaFlowStep $step)
    {
        $step->update(['is_active' => false]);
        flashSuccess('Step deactivated.');

        return back();
    }

    public function destroyStep(VisaFlowStep $step)
    {
        $flowId = $step->visa_flow_id;
        $step->delete();

        VisaFlowStep::query()
            ->where('visa_flow_id', $flowId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (VisaFlowStep $remaining, int $index) {
                if ((int) $remaining->sort_order !== $index) {
                    $remaining->update(['sort_order' => $index]);
                }
            });

        flashSuccess('Step deleted. Existing visa cases keep their snapshots; new cases will not include this step.');

        return back();
    }
}

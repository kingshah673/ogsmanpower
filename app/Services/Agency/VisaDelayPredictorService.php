<?php

namespace App\Services\Agency;

use App\Models\Agency;
use App\Models\VpCase;
use App\Models\VpCaseEvent;
use Illuminate\Support\Carbon;

/**
 * Heuristic (non-LLM) delay predictor: learns the average number of days each
 * visa-flow step historically takes (from `turn`/`started` -> `step_completed`
 * event pairs across all completed steps) and uses that to forecast a
 * completion date for each of the agency's in-progress cases, flagging any
 * case that is already running behind its own step's historical average.
 */
class VisaDelayPredictorService
{
    public function forecast(Agency $agency): array
    {
        $averages = $this->averageStepDurations();
        $globalAverage = $averages ? round(array_sum($averages) / count($averages), 1) : 3.0;

        $cases = VpCase::where('agency_id', $agency->id)
            ->where('status', 'in_progress')
            ->with(['steps', 'candidate.user', 'job'])
            ->get();

        $forecasts = [];

        foreach ($cases as $case) {
            $pendingSteps = $case->steps->whereIn('status', ['active', 'pending']);
            $remainingDays = 0.0;
            foreach ($pendingSteps as $step) {
                $remainingDays += $averages[$step->name] ?? $globalAverage;
            }

            $activeStep = $case->steps->firstWhere('status', 'active');
            $daysOnActiveStep = 0;
            $atRisk = false;

            if ($activeStep) {
                $stepStartedAt = $this->stepStartedAt($case, $activeStep) ?? $case->created_at;
                $daysOnActiveStep = max(0, (int) $stepStartedAt->diffInDays(now()));
                $expectedForStep = $averages[$activeStep->name] ?? $globalAverage;
                $atRisk = $daysOnActiveStep > ($expectedForStep * 1.5);
            }

            $forecasts[] = [
                'case' => $case,
                'active_step' => $activeStep?->name ?? 'N/A',
                'days_on_active_step' => $daysOnActiveStep,
                'estimated_days_remaining' => (int) round($remainingDays),
                'estimated_completion_date' => now()->addDays((int) round($remainingDays))->format('Y-m-d'),
                'at_risk' => $atRisk,
            ];
        }

        return [
            'forecasts' => $forecasts,
            'step_averages' => $averages,
            'global_average_days' => $globalAverage,
        ];
    }

    /**
     * @return array<string, float> step name => average days to complete
     */
    protected function averageStepDurations(): array
    {
        $events = VpCaseEvent::whereIn('event_type', ['turn', 'started', 'step_completed'])
            ->orderBy('created_at')
            ->get(['vp_case_id', 'event_type', 'meta', 'created_at']);

        // step_id => start timestamp
        $startedAt = [];
        $durationsByStepName = [];

        // Need step name lookup; batch-load once.
        $stepIds = $events->flatMap(fn ($e) => [$e->meta['step_id'] ?? null])->filter()->unique()->values();
        $stepNames = $stepIds->isEmpty()
            ? collect()
            : \App\Models\VpCaseStep::whereIn('id', $stepIds)->pluck('name', 'id');

        foreach ($events as $event) {
            $stepId = $event->meta['step_id'] ?? null;

            if (in_array($event->event_type, ['turn', 'started'], true)) {
                if ($stepId) {
                    $startedAt[$stepId] = $event->created_at;
                } elseif ($event->event_type === 'started') {
                    // First step of the case starts when the case itself starts.
                    $startedAt['case:'.$event->vp_case_id] = $event->created_at;
                }

                continue;
            }

            if ($event->event_type === 'step_completed' && $stepId) {
                $start = $startedAt[$stepId] ?? $startedAt['case:'.$event->vp_case_id] ?? null;
                if (! $start) {
                    continue;
                }

                $name = $stepNames[$stepId] ?? null;
                if (! $name) {
                    continue;
                }

                $days = $start->diffInHours($event->created_at) / 24;
                $durationsByStepName[$name][] = max($days, 0.1);
            }
        }

        $averages = [];
        foreach ($durationsByStepName as $name => $durations) {
            $averages[$name] = round(array_sum($durations) / count($durations), 1);
        }

        return $averages;
    }

    protected function stepStartedAt(VpCase $case, $step): ?Carbon
    {
        $turnEvent = VpCaseEvent::where('vp_case_id', $case->id)
            ->where('event_type', 'turn')
            ->where('meta->step_id', $step->id)
            ->orderByDesc('created_at')
            ->first();

        if ($turnEvent) {
            return $turnEvent->created_at;
        }

        // The first step in the flow becomes active when the case itself starts
        // (no dedicated "turn" event is logged for it).
        if ((int) $step->sort_order === 0) {
            $startedEvent = VpCaseEvent::where('vp_case_id', $case->id)
                ->where('event_type', 'started')
                ->first();

            return $startedEvent?->created_at;
        }

        return null;
    }
}

<?php

namespace App\Services\AI;

use App\Models\BrokerDemand;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Logged-in Broker demand create / route / track via Sophia.
 */
class BrokerDemandChatService
{
    public function isActive(): bool
    {
        return Cache::has($this->cacheKey());
    }

    public function startCreate(): string
    {
        Cache::put($this->cacheKey(), ['step' => 'title', 'data' => []], now()->addHours(2));

        return "📋 **Create a demand request**\n\n"
            ."**Step 1:** What is the **job / demand title**? (e.g. 50 Welders for UAE)";
    }

    /**
     * @return array{reply: string, actions?: array}|null
     */
    public function handle(User $user, string $message, string $action = ''): ?array
    {
        if ($user->role !== 'broker' || ! $user->broker) {
            return null;
        }

        if ($action === 'start_broker_demand') {
            return [
                'reply' => $this->startCreate(),
                'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
            ];
        }

        if ($action === 'list_broker_demands') {
            return $this->listDemands($user);
        }

        if (! $this->isActive()) {
            return null;
        }

        $lower = strtolower(trim($message));
        if (in_array($lower, ['cancel', 'stop'], true)) {
            Cache::forget($this->cacheKey());

            return [
                'reply' => '🔙 Demand creation cancelled.',
                'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
            ];
        }

        $state = Cache::get($this->cacheKey());
        $step = $state['step'] ?? 'title';
        $data = $state['data'] ?? [];

        return match ($step) {
            'title' => $this->afterTitle($message, $data),
            'country' => $this->afterCountry($message, $data),
            'vacancies' => $this->afterVacancies($message, $data),
            'agency' => $this->afterAgency($user, $message, $data),
            default => $this->restart(),
        };
    }

    /** @param  array<string, mixed>  $data */
    protected function afterTitle(string $message, array $data): array
    {
        if (mb_strlen(trim($message)) < 2) {
            return ['reply' => 'Please type a **demand title**.'];
        }
        $data['title'] = trim($message);
        Cache::put($this->cacheKey(), ['step' => 'country', 'data' => $data], now()->addHours(2));

        return ['reply' => '**Step 2:** Destination **country**? (or type *skip*)'];
    }

    /** @param  array<string, mixed>  $data */
    protected function afterCountry(string $message, array $data): array
    {
        if (! in_array(strtolower(trim($message)), ['skip', 'later'], true)) {
            $data['country'] = trim($message);
        }
        Cache::put($this->cacheKey(), ['step' => 'vacancies', 'data' => $data], now()->addHours(2));

        return ['reply' => '**Step 3:** How many **vacancies / workers** needed?'];
    }

    /** @param  array<string, mixed>  $data */
    protected function afterVacancies(string $message, array $data): array
    {
        $data['vacancies'] = max(1, (int) preg_replace('/\D/', '', $message) ?: 1);
        Cache::put($this->cacheKey(), ['step' => 'agency', 'data' => $data], now()->addHours(2));

        $agencies = User::query()->where('role', 'agency')->where('status', 1)->orderBy('name')->limit(8)->get(['id', 'name']);
        $lines = $agencies->map(fn ($a) => "• {$a->name} (id: {$a->id})")->implode("\n");

        return [
            'reply' => "**Step 4:** Route to a **Recruitment Agency** now?\n\n"
                .($lines ?: 'No agencies listed yet.')
                ."\n\nType an agency **id**, agency **name**, or *skip* to save as open.",
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function afterAgency(User $user, string $message, array $data): array
    {
        $agencyId = null;
        $lower = strtolower(trim($message));

        if (! in_array($lower, ['skip', 'later', 'none'], true)) {
            if (ctype_digit(trim($message))) {
                $agency = User::where('id', (int) $message)->where('role', 'agency')->first();
                $agencyId = $agency?->id;
            } else {
                $agency = User::where('role', 'agency')
                    ->where('name', 'like', '%'.trim($message).'%')
                    ->first();
                $agencyId = $agency?->id;
            }

            if (! $agencyId) {
                return ['reply' => 'Agency not found. Type a valid agency id/name, or *skip*.'];
            }
        }

        try {
            $demand = $user->broker->demands()->create([
                'title' => $data['title'],
                'country' => $data['country'] ?? null,
                'vacancies' => $data['vacancies'] ?? 1,
                'status' => $agencyId ? 'routed' : 'open',
                'routed_agency_user_id' => $agencyId,
                'routed_at' => $agencyId ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[BrokerDemandChat] '.$e->getMessage());
            Cache::forget($this->cacheKey());

            return ['reply' => '⚠️ Could not save demand. Please try again.'];
        }

        Cache::forget($this->cacheKey());

        $status = $demand->status === 'routed' ? 'routed to an agency' : 'saved as open';

        return [
            'reply' => "✅ Demand **{$demand->title}** {$status}.\n\n"
                ."👉 <a href='".route('broker.demands')."'>View demands</a>",
            'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
        ];
    }

    protected function listDemands(User $user): array
    {
        $demands = $user->broker->demands()->latest()->take(8)->get();
        if ($demands->isEmpty()) {
            return [
                'reply' => "You have no demand requests yet.\n\nTap **Create demand** to start.",
                'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
            ];
        }

        $lines = $demands->map(function (BrokerDemand $d) {
            $route = $d->routedAgencyUser?->name ? ' → '.$d->routedAgencyUser->name : '';

            return "• **{$d->title}** ({$d->status}){$route}";
        })->implode("\n");

        return [
            'reply' => "📊 **Your recent demands:**\n\n{$lines}\n\n👉 <a href='".route('broker.demands')."'>Open all demands</a>",
            'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
        ];
    }

    protected function restart(): array
    {
        Cache::forget($this->cacheKey());

        return ['reply' => $this->startCreate()];
    }

    protected function cacheKey(): string
    {
        return 'sophia_broker_demand_'.session()->getId();
    }
}

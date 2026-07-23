<?php

namespace App\Services\Admin;

use App\Models\Agency;
use App\Models\Broker;
use App\Models\BrokerDemand;
use App\Models\Candidate;
use App\Models\ChatLead;
use App\Models\Company;
use App\Models\Earning;
use App\Models\Job;
use App\Models\User;

class AdminSidebarKpiService
{
    /**
     * Sidebar badge counts aligned with the admin dashboard totals.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        return [
            'order' => Earning::query()->count(),
            'company' => Company::query()->count(),
            'agency' => Agency::query()->count(),
            'candidates' => Candidate::query()->count(),
            'agents' => User::query()->where('role', 'agent')->count(),
            'brokers' => Broker::query()->count(),
            'broker_demands_open' => BrokerDemand::query()->where('status', 'open')->count(),
            'all_jobs' => Job::query()->withoutEdited()->count(),
            'chat_leads' => ChatLead::query()->where('status', 'new')->count(),
        ];
    }
}

{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.agency.layout.app')

@section('css')
<style>

/* =============================
   GLOBAL DASHBOARD DESIGN
============================= */

body{
    background:#f4f6fb;
    font-family:'Inter','Segoe UI',sans-serif;
}

.dashboard-wrapper{
    padding-top:40px;
    padding-bottom:40px;
}

/* =============================
   PLAN CARD
============================= */

.plan-card{
    background:#ffffff;
    padding:35px;
    border-radius:18px;
    box-shadow:0 15px 40px rgba(0,0,0,0.06);
    height:100%;
    transition:all .3s ease;
}

.plan-card:hover{
    transform:translateY(-4px);
    box-shadow:0 20px 50px rgba(0,0,0,0.08);
}

.plan-card .title{
    font-size:22px;
    font-weight:700;
    margin-bottom:15px;
}

.plan-card .short-desc{
    font-size:14px;
    color:#6b7280;
    line-height:1.6;
    margin-bottom:25px;
}

.plan-card .btn-primary{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    border:none;
    padding:12px 28px;
    border-radius:12px;
    font-weight:600;
    box-shadow:0 10px 30px rgba(37,99,235,0.3);
}

/* =============================
   BENEFITS CARD
============================= */

.benefits-card{
    background:#ffffff;
    padding:35px;
    border-radius:18px;
    box-shadow:0 15px 40px rgba(0,0,0,0.06);
}

.benefits-card .title{
    font-size:18px;
    font-weight:600;
    margin-bottom:20px;
}

.benefit-list,
.remaining-list{
    list-style:none;
    padding:0;
    margin:0;
}

.benefit-list li,
.remaining-list li{
    display:flex;
    align-items:center;
    gap:12px;
    padding:10px 0;
    font-size:14px;
    color:#374151;
    border-bottom:1px solid #f1f1f1;
}

.benefit-list li:last-child,
.remaining-list li:last-child{
    border-bottom:none;
}

.remaining{
    margin-top:30px;
}

/* =============================
   INVOICE TABLE
============================= */

.invoices-table{
    background:#ffffff;
    padding:35px;
    border-radius:18px;
    box-shadow:0 15px 40px rgba(0,0,0,0.06);
    margin-top:40px;
}

.invoices-table .title{
    font-size:20px;
    font-weight:700;
    margin-bottom:25px;
}

.table-wrapper{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
}

thead{
    background:#f9fafb;
}

thead th{
    font-size:13px;
    text-transform:uppercase;
    letter-spacing:.5px;
    padding:14px;
    text-align:left;
    color:#6b7280;
    border-bottom:1px solid #e5e7eb;
}

tbody td{
    padding:14px;
    font-size:14px;
    border-bottom:1px solid #f1f1f1;
    vertical-align:middle;
}

tbody tr:hover{
    background:#f9fafb;
}

/* =============================
   BADGES
============================= */

.badge{
    padding:6px 10px;
    border-radius:8px;
    font-size:12px;
    font-weight:600;
}

.bg-success{
    background:#d1fae5 !important;
    color:#065f46 !important;
}

.bg-warning{
    background:#fef3c7 !important;
    color:#92400e !important;
}

.bg-primary{
    background:#dbeafe !important;
    color:#1e40af !important;
}

.bg-secondary{
    background:#e5e7eb !important;
    color:#374151 !important;
}

/* =============================
   LINKS
============================= */

a{
    color:#2563eb;
    text-decoration:none;
    font-weight:500;
}

a:hover{
    text-decoration:underline;
}

</style>
@endsection

@section('title')
    {{ __('plan') }}
@endsection

@section('main')
    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row">
                {{-- Sidebar --}}
                {{-- <x-website.agency.sidebar /> --}}
                <div class="col-lg-9">
                    <div class="dashboard-right tw-ps-0 lg:tw-ps-5">
                        <div class="row tw-my-5">
                            <div class="col-lg-5">
                                <div class="plan-card">
                                    <h2 class="title">{{ $userplan->plan->label }}</h2>
                                    <p class="short-desc">
                                        @if (isset($userplan->plan->descriptions) && isset($userplan->plan->descriptions[0]))
                                            {!! $userplan->plan->descriptions[0]->description !!}
                                        @else
                                            <span class="text-danger">{!! __('no_description_has_been_added_to_this_language', ['current' => $current_language_code]) !!}</span>
                                        @endif
                                    </p>
                                    <div class="">
                                        <a href="{{ route('website.plan') }}" class="btn btn-primary">
                                            {{ __('upgrade_plan') }}</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="benefits-card">
                                    <h4 class="title">{{ __('create_plan_benefits') }}</h4>
                                    <ul class="benefit-list">
                                        <li>
                                            <x-svg.double-check-icon />
                                            <span>{{ $userplan->plan->job_limit }} {{ __('active_jobs') }}</span>
                                        </li>
                                        <li>
                                            <x-svg.double-check-icon />
                                            <span>{{ $userplan->plan->highlight_job_limit }}
                                                {{ __('highlight_jobs') }}</span>
                                        </li>
                                        <li>
                                            <x-svg.double-check-icon />
                                            <span>{{ $userplan->plan->featured_job_limit }}
                                                {{ __('featured_jobs') }}</span>
                                        </li>
                                        <li>
                                            <x-svg.double-check-icon />
                                            <span>{{ $userplan->plan->candidate_cv_view_limitation == 'limited' ? $userplan->plan->candidate_cv_view_limit : __('unlimited') }}
                                                {{ __('candidate_profile_view') }}</span>
                                        </li>
                                    </ul>
                                    <div class="remaining">
                                        <h4 class="title">{{ __('remaining') }}</h4>
                                        <ul class="remaining-list">
                                            <li>
                                                @if (!$userplan->job_limit)
                                                    <x-svg.cross-round2-icon />
                                                @else
                                                    <x-svg.double-check-icon />
                                                @endif
                                                <span>{{ $userplan->job_limit }} {{ __('active_jobs') }}</span>
                                            </li>
                                            <li>
                                                @if (!$userplan->highlight_job_limit)
                                                    <x-svg.cross-round2-icon />
                                                @else
                                                    <x-svg.double-check-icon />
                                                @endif
                                                <span>{{ $userplan->highlight_job_limit }}
                                                    {{ __('highlight_jobs') }}</span>
                                            </li>
                                            <li>
                                                @if (!$userplan->featured_job_limit)
                                                    <x-svg.cross-round2-icon />
                                                @else
                                                    <x-svg.double-check-icon />
                                                @endif
                                                <span>{{ $userplan->featured_job_limit }}
                                                    {{ __('featured_jobs') }}</span>
                                            </li>
                                            <li>
                                                @if ($userplan->candidate_cv_view_limitation == 'unlimited')
                                                    <x-svg.double-check-icon />
                                                    <span>
                                                        {{ __('unlimited') }} {{ __('candidate_profile_view') }}
                                                    </span>
                                                @else
                                                    @if (!$userplan->candidate_cv_view_limit)
                                                        <x-svg.cross-round2-icon />
                                                    @else
                                                        <x-svg.double-check-icon />
                                                    @endif
                                                    <span>
                                                        {{ $userplan->candidate_cv_view_limit }}
                                                        {{ __('candidate_profile_view') }}
                                                    </span>
                                                @endif
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="invoices-table ">
                            <h2 class="title">{{ __('latest_invoice') }}</h2>
                            <div class="table-wrapper pb-1">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('date') }}</th>
                                            <th>{{ __('plan') }}</th>
                                            <th>{{ __('amount') }}</th>
                                            <th>{{ __('payment_provider') }}</th>
                                            <th>{{ __('payment_status') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($transactions->count() > 0)
                                            @foreach ($transactions as $transaction)
                                                <tr>
                                                    <td>#{{ $transaction->order_id }}</td>
                                                    <td>{{ formatTime($transaction->created_at, 'M, d Y') }}</td>
                                                    <td>
                                                        @if ($transaction->payment_type == 'per_job_based')
                                                            <span
                                                                class="badge bg-secondary">{{ ucfirst(Str::replace('_', ' ', $transaction->payment_type)) }}</span>
                                                        @else
                                                            <span
                                                                class="badge bg-primary">{{ $transaction->plan->label }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ currencyConversion($transaction->usd_amount, 'USD') }}
                                                        {{ currentCurrencyCode() }}
                                                    </td>
                                                    <td>
                                                        @if ($transaction->payment_provider == 'offline')
                                                            {{ __('offline') }}
                                                            @if (isset($transaction->manualPayment) && isset($transaction->manualPayment->name))
                                                                (<b>{{ $transaction->manualPayment->name }}</b>)
                                                            @endif
                                                        @else
                                                            {{ ucfirst($transaction->payment_provider) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($transaction->payment_status == 'paid')
                                                            <span
                                                                class="badge badge-pill bg-success">{{ __('paid') }}</span>
                                                        @else
                                                            <span
                                                                class="badge badge-pill bg-warning">{{ __('unpaid') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="tw-inline-flex tw-gap-2 tw-items-center">
                                                            <form
                                                            action="{{ route('agency.transaction.invoice.download', $transaction->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            <button type="submit" class="btn tw-p-0">
                                                                <x-svg.download-icon />
                                                            </button>
                                                        </form>
                                                        <a
                                                            href="{{ route('agency.transaction.invoice.view', $transaction->order_id) }}">
                                                            {{ __('view_invoice') }}
                                                        </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <x-website.not-found />
                                        @endif
                                    </tbody>
                                </table>
                                @if (request('perpage') != 'all' && $transactions->total() > $transactions->count())
                                    <div class="rt-pt-30 mb-3">
                                        <nav>
                                            {{ $transactions->onEachSide(0)->links('vendor.pagination.frontend') }}
                                        </nav>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

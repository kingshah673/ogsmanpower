<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commission Receipt #{{ $commission->id }}</title>
    <style>
        @page { size: a4; margin: 40px; }
        body { font-family: sans-serif; color: #333; font-size: 14px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1a73e8; padding-bottom: 15px; margin-bottom: 25px; }
        .header h1 { font-size: 22px; color: #1a73e8; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table td, table th { padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        table th { background: #f5f6fa; }
        .totals { margin-top: 20px; width: 100%; }
        .totals td { padding: 8px 10px; }
        .grand-total { font-size: 16px; font-weight: bold; background: #1a73e8; color: #fff; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; color: #fff; font-size: 12px; }
        .status-pending { background: #f0ad4e; }
        .status-approved { background: #337ab7; }
        .status-paid { background: #5cb85c; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Commission Receipt</h1>
            <p>#{{ $commission->id }} &middot; {{ optional($commission->created_at)->format('M d, Y') }}</p>
        </div>
        <div>
            <span class="status-badge status-{{ $commission->status }}">{{ ucfirst($commission->status) }}</span>
        </div>
    </div>

    <table>
        <tr>
            <th style="width: 30%;">Candidate</th>
            <td>{{ optional($commission->candidate?->user)->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Job</th>
            <td>{{ $commission->job->title ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Employer</th>
            <td>{{ optional($commission->appliedJob?->job?->company?->user)->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Commission Rate</th>
            <td>{{ $commission->rate ? $commission->rate.'%' : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Notes</th>
            <td>{{ $commission->notes ?? '—' }}</td>
        </tr>
        @if($commission->paid_at)
        <tr>
            <th>Paid On</th>
            <td>{{ $commission->paid_at->format('M d, Y') }}</td>
        </tr>
        @endif
    </table>

    <table class="totals">
        <tr class="grand-total">
            <td>Commission Amount</td>
            <td style="text-align: right;">{{ $commission->currency }} {{ number_format($commission->amount, 2) }}</td>
        </tr>
    </table>

    <p style="margin-top: 40px; color: #888; font-size: 12px;">
        This is a system-generated commission receipt from {{ config('app.name') }}.
    </p>
</body>
</html>

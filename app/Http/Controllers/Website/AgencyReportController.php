<?php

namespace App\Http\Controllers\Website;

use App\Export\SimpleArrayExport;
use App\Http\Controllers\Controller;
use App\Services\Agency\AgencyReportService;
use Maatwebsite\Excel\Facades\Excel;

class AgencyReportController extends Controller
{
    protected function guardType(string $type): void
    {
        abort_unless(array_key_exists($type, AgencyReportService::TYPES), 404);
    }

    public function index()
    {
        return view('frontend.pages.agency.reports.index', [
            'types' => AgencyReportService::TYPES,
        ]);
    }

    public function show(string $type, AgencyReportService $service)
    {
        $this->guardType($type);

        $report = $service->build(currentAgency(), $type);

        return view('frontend.pages.agency.reports.show', [
            'type' => $type,
            'report' => $report,
            'types' => AgencyReportService::TYPES,
        ]);
    }

    public function export(string $type, AgencyReportService $service)
    {
        $this->guardType($type);

        $report = $service->build(currentAgency(), $type);

        return Excel::download(
            new SimpleArrayExport($report['rows'], $report['headings']),
            'agency-report-'.$type.'-'.now()->format('Ymd_His').'.xlsx'
        );
    }
}

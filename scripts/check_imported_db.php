<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$checks = [
    'home' => '/',
    'candidates' => '/candidates',
    'employers' => '/employers',
    'jobs' => '/jobs',
    'login' => '/login',
    'register' => '/register',
    'candidate.dashboard' => null,
    'candidate.setting' => null,
    'company.dashboard' => null,
    'company.verify.documents.index' => null,
    'company.job.create' => null,
    'company.pipeline' => null,
    'company.job.assign.agency' => null,
    'company.nominated-workers.index' => null,
    'company.visa-processing.index' => null,
    'ai.parse.cv' => null,
];

echo "=== ROUTE NAMES ===\n";
foreach ($checks as $name => $path) {
    if ($path !== null) {
        continue;
    }
    try {
        $params = str_contains($name, 'assign.agency') ? ['job' => 1] : [];
        echo 'OK name '.$name.' => '.route($name, $params, false)."\n";
    } catch (Throwable $e) {
        echo 'FAIL name '.$name.': '.$e->getMessage()."\n";
    }
}

echo "\n=== HTTP (guest) ===\n";
$kernelHttp = $app->make(Illuminate\Contracts\Http\Kernel::class);
foreach ($checks as $label => $path) {
    if ($path === null) {
        continue;
    }
    $request = Illuminate\Http\Request::create($path, 'GET');
    try {
        $response = $kernelHttp->handle($request);
        $status = $response->getStatusCode();
        $body = $response->getContent();
        $err = '';
        if ($status >= 400) {
            if (preg_match('/SQLSTATE\[.*?\]:\s*([^\n<]+)/', $body, $m)) {
                $err = ' SQL: '.trim($m[1]);
            } elseif (preg_match('/class\s+[\'\"]([^\'\"]+)[\'\"]\s+not found/i', $body, $m)) {
                $err = ' Missing: '.$m[1];
            } elseif (preg_match('/Call to undefined method ([^<\n]+)/', $body, $m)) {
                $err = ' Method: '.$m[1];
            } elseif (preg_match('/Attempt to read property &quot;(\w+)&quot; on null/', $body, $m)) {
                $err = ' null->'.$m[1];
            } elseif (preg_match('/Attempt to read property \"(\w+)\" on null/', $body, $m)) {
                $err = ' null->'.$m[1];
            }
        }
        echo "$status $label ($path)$err\n";
        $kernelHttp->terminate($request, $response);
    } catch (Throwable $e) {
        echo 'EXC '.$label.': '.$e->getMessage()."\n";
    }
}

echo "\n=== MODEL QUICK READS ===\n";
foreach ([
    'users' => fn () => App\Models\User::count(),
    'candidates' => fn () => App\Models\Candidate::count(),
    'companies' => fn () => App\Models\Company::count(),
    'jobs' => fn () => App\Models\Job::count(),
    'applied_jobs' => fn () => App\Models\AppliedJob::count(),
    'attachments' => fn () => App\Models\Attachment::count(),
    'Agency' => fn () => class_exists(App\Models\Agency::class) ? (Illuminate\Support\Facades\Schema::hasTable('agencies') ? App\Models\Agency::count() : 'NO TABLE') : 'NO CLASS',
    'job_agencies' => fn () => Illuminate\Support\Facades\Schema::hasTable('job_agencies') ? 'EXISTS' : 'NO TABLE',
    'pipeline' => fn () => Illuminate\Support\Facades\Schema::hasTable('job_candidate_pipeline') ? 'EXISTS' : 'NO TABLE',
    'vp_cases' => fn () => Illuminate\Support\Facades\Schema::hasTable('vp_cases') ? 'EXISTS' : 'NO TABLE',
    'nominated_workers' => fn () => Illuminate\Support\Facades\Schema::hasTable('nominated_workers') ? 'EXISTS' : 'NO TABLE',
    'passport_ocr_logs' => fn () => Illuminate\Support\Facades\Schema::hasTable('passport_ocr_logs') ? 'EXISTS' : 'NO TABLE',
    'employer_verification_document_types' => fn () => Illuminate\Support\Facades\Schema::hasTable('employer_verification_document_types') ? 'EXISTS' : 'NO TABLE',
] as $label => $fn) {
    try {
        $v = $fn();
        echo "$label: $v\n";
    } catch (Throwable $e) {
        echo "$label FAIL: ".$e->getMessage()."\n";
    }
}

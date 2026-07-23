<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$names = [
    'candidate.dashboard',
    'candidate.setting',
    'candidate.resume.store',
    'ai.parse.cv',
    'ai.parse.passport',
    'company.dashboard',
    'company.job.create',
    'company.job.assign.agency',
    'company.pipeline.shortlist',
    'company.verify.documents.index',
    'company.visa-processing.index',
    'company.nominated-workers.index',
    'otp.verify',
    'candidate.visa-processing.index',
];

foreach ($names as $n) {
    try {
        $params = [];
        if (str_contains($n, 'assign.agency')) {
            $params['job'] = 1;
        }
        $url = route($n, $params, false);
        echo "OK {$n} => {$url}\n";
    } catch (Throwable $e) {
        echo "FAIL {$n}: ".$e->getMessage()."\n";
    }
}

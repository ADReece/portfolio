<?php

// Runs ProcessImageUpload::makeDerivatives() on one file in a fresh PHP
// process and prints its peak resident memory (VmHWM), which includes
// Imagick's native allocations that memory_get_peak_usage() can't see.
// Usage: php measure-derivatives.php <image path>

require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$out = App\Jobs\ProcessImageUpload::makeDerivatives($argv[1], '@shutterbug');
preg_match('/VmHWM:\s+(\d+) kB/', (string) @file_get_contents('/proc/self/status'), $m);

echo json_encode([
    'peak_kb' => isset($m[1]) ? (int) $m[1] : null,
    'thumbnail_bytes' => strlen($out['thumbnail']),
    'watermarked_bytes' => strlen($out['watermarked']),
]);

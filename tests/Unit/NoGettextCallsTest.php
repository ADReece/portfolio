<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * PHP's gettext _() isn't installed in the production image (Alpine), so a
 * stray _() instead of Laravel's __() works locally and 500s on the Pi.
 */
class NoGettextCallsTest extends TestCase
{
    public function test_code_and_views_do_not_call_gettext(): void
    {
        $root = dirname(__DIR__, 2);
        $offenders = [];

        foreach (['app', 'resources/views', 'routes', 'config'] as $dir) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir"));
            foreach ($files as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                foreach (file($file->getPathname()) as $n => $line) {
                    if (preg_match('/(?<![\w$>:_])_\(/', $line)) {
                        $offenders[] = substr($file->getPathname(), strlen($root) + 1).':'.($n + 1);
                    }
                }
            }
        }

        $this->assertSame([], $offenders, 'Use __() instead of gettext _()');
    }
}

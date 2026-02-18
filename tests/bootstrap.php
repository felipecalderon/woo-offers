<?php

declare(strict_types=1);

$_testsDir = getenv('WP_TESTS_DIR');
if (!$_testsDir) {
    $_testsDir = 'C:/tmp/wordpress-tests-lib';
}

if (!is_dir($_testsDir)) {
    fwrite(STDERR, "WP tests library not found at: {$_testsDir}\n");
    exit(1);
}

require_once $_testsDir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
    require dirname(__DIR__) . '/woo-offers.php';
});

require_once $_testsDir . '/includes/bootstrap.php';

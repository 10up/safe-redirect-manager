<?php
/**
 * PHPUnit bootstrap file
 *
 * @package safe-redirect-manager
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

define( 'PHPUNIT_SRM_TESTSUITE', true );

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// Activate the plugin.
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__, 2 ) . '/safe-redirect-manager.php';
	}
);

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

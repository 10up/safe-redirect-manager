<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

define( 'PHPUNIT_SRM_TESTSUITE', 1 );

require_once ( $_tests_dir . '/includes/functions.php' );

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../safe-redirect-manager.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require( $_tests_dir . '/includes/bootstrap.php' );


<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/*
 * This needs to go after you include WordPress's unit test functions, but before
 * loading WordPress's bootstrap.php file.
 */

// Include the uninstall test tools functions.
include_once dirname( __FILE__ ) . '/uninstall/includes/functions.php';

function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/breadcrumb-navxt.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

include_once dirname( __FILE__ ) . '/uninstall/bootstrap.php';
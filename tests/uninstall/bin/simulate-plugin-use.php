<?php

/**
 * Simulate plugin usage.
 *
 * @package WP_Plugin_Uninstall_Tester
 * @since 0.1.0
 */

/**
 * Load the main plugin file as if it was active in plugin usage simulation.
 *
 * @since 0.1.0
 */
function _wp_plugin_unintsall_tester_load_plugin_file() {

	require $GLOBALS['argv'][1];
}

/**
 * Load the WordPress tests functions.
 *
 * We are loading this so that we can add our tests filter to load the plugin, using
 * tests_add_filter().
 *
 * @since 0.1.0
 */
require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', '_wp_plugin_unintsall_tester_load_plugin_file' );

$simulation_file  = $argv[2];
$config_file_path = $argv[3];
$is_multisite     = $argv[4];

require dirname( __FILE__ ) . '/bootstrap.php';

/**
 * Load the WP unit test factories.
 *
 * Use the $wp_test_factory global to create users, posts, etc., the same way that
 * you use the $factory propety in WP unit test case classes.
 *
 * @since 0.2.0
 */
require_once getenv( 'WP_TESTS_DIR' ) . '/includes/factory.php';

$GLOBALS['wp_test_factory'] = new WP_UnitTest_Factory;

require $simulation_file;

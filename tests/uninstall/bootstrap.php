<?php

/**
 * Bootstrap to load the classes.
 *
 * Include this in the bootstrap for your plugin tests.
 *
 * @package WP_Plugin_Uninstall_Tester
 * @since 0.1.0
 */

/**
 * The directory path of the uninstall tester tools.
 *
 * @since 0.2.0
 *
 * @type string
 */
define( 'WP_PLUGIN_UNINSTALL_TESTER_DIR', dirname( __FILE__ ) );

/**
 * The commandline options parser.
 */
require_once WP_PLUGIN_UNINSTALL_TESTER_DIR . '/includes/wp-plugin-uninstall-tester-phpunit-util-getopt.php';

/**
 * General functions.
 */
require_once WP_PLUGIN_UNINSTALL_TESTER_DIR . '/includes/functions.php';

/**
 * The plugin install/uninstall test case.
 */
require_once WP_PLUGIN_UNINSTALL_TESTER_DIR . '/includes/wp-plugin-uninstall-unittestcase.php';

/**
 * Table exists constraint.
 */
require_once WP_PLUGIN_UNINSTALL_TESTER_DIR . '/includes/constraints/is-table-existant.php';

/**
 * Table non-existant constraint.
 */
require_once WP_PLUGIN_UNINSTALL_TESTER_DIR . '/includes/constraints/table-is-non-existant.php';

/**
 * No rows with prefix constraint.
 */
require_once WP_PLUGIN_UNINSTALL_TESTER_DIR . '/includes/constraints/no-rows-with-prefix.php';

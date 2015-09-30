<?php

/**
 * General functions.
 *
 * @package WP_Plugin_Uninstall_Tester
 * @since 0.1.0
 */

/**
 * Pull in the option parser if we haven't already.
 */
require_once dirname( __FILE__ ) . '/wp-plugin-uninstall-tester-phpunit-util-getopt.php';

/**
 * Check if the plugin uninstall unit tests are being run.
 *
 * @since 0.1.0
 *
 * @return bool Whether the plugin uninstall group is being run.
 */
function running_wp_plugin_uninstall_tests() {

	static $uninstall_tests;

	if ( ! isset( $uninstall_tests ) ) {

		global $argv;

		$option_parser = new WP_Plugin_Uninstall_Tester_PHPUnit_Util_Getopt( $argv );

		$uninstall_tests = $option_parser->running_uninstall_group();
	}

	return $uninstall_tests;
}

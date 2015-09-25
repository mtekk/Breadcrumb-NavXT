<?php

/**
 * Is table exists constraint.
 *
 * @package WP_Plugin_Uninstall_Tester
 * @since 0.1.0
 */

/**
 * Database table exists constraint matcher.
 *
 * @since 0.1.0
 */
class WP_Plugin_Uninstall_Tester_PHPUnit_Constraint_IsTableExistant extends PHPUnit_Framework_Constraint {

	/**
	 * Checks if $table exists in the database.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table The name of the table that should exist.
	 *
	 * @return bool Whether the table exists.
	 */
	public function matches( $table ) {

		global $wpdb;

		$_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		return ( $table == $_table );
	}

	/**
	 * Returns a string representation of the constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function toString() {

		return 'is a table in the database';
	}
}

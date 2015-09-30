<?php

/**
 * WordPress plugin uninstall test case.
 *
 * @package WP_Plugin_Uninstall_Tester
 * @since 0.1.0
 */

/**
 * Test WordPress plugin installation and uninstallation.
 *
 * @since 0.1.0
 */
abstract class WP_Plugin_Uninstall_UnitTestCase extends WP_UnitTestCase {

	//
	// Protected properties.
	//

	/**
	 * The full path to the main plugin file.
	 *
	 * @since 0.1.0
	 *
	 * @type string $plugin_file
	 */
	protected $plugin_file;

	/**
	 * The plugin's install function.
	 *
	 * @since 0.1.0
	 *
	 * @type callable $install_function
	 */
	protected $install_function;

	/**
	 * The plugin's uninstall function (if it has one).
	 *
	 * @since 0.1.0
	 *
	 * @type callable $uninstall_function
	 */
	protected $uninstall_function;

	/**
	 * Full path to a file to simulate plugin usage.
	 *
	 * @since 0.1.0
	 *
	 * @type string $simulation_file
	 */
	protected $simulation_file;

	/**
	 * Whether the usage simulate file has been run yet.
	 *
	 * @since 0.3.0
	 *
	 * @type bool $simulated_usage
	 */
	protected $simulated_usage = false;

	/**
	 * The ID of the blog created for multisite tests.
	 *
	 * @since 0.2.0
	 *
	 * @type int $_blog_id
	 */
	protected $_blog_id;

	//
	// Methods.
	//

	/**
	 * Set up for the tests.
	 *
	 * If you need to set any of the class properties (like $plugin_file), you'll
	 * need to have a setUp() method in your child class. Don't forget to call
	 * parent::setUp() at the end of it.
	 *
	 * @since 0.1.0
	 */
	public function setUp() {

		// Create another site on multisite.
		if ( is_multisite() ) {

			// $this->factory isn't available until after setup.
			$factory = new WP_UnitTest_Factory;
			$this->_blog_id = $factory->blog->create();
		}

		//$this->install();

		parent::setUp();
	}

	/**
	 * Clean up after the tests.
	 *
	 * @since 0.2.0
	 */
	public function tearDown() {

		parent::tearDown();

		if ( is_multisite() ) {
			wpmu_delete_blog( $this->_blog_id, true );
		}
	}

	/**
	 * Locate the config file for the WordPress tests.
	 *
	 * The script is exited with an error message if no config file can be found.
	 *
	 * @since 0.1.0
	 *
	 * @return string The path to the file, if found.
	 */
	protected function locate_wp_tests_config() {

		$config_file_path = getenv( 'WP_TESTS_DIR' );

		if ( ! file_exists( $config_file_path . '/wp-tests-config.php' ) ) {

			// Support the config file from the root of the develop repository.
			if (
				basename( $config_file_path ) === 'phpunit'
				&& basename( dirname( $config_file_path ) ) === 'tests'
			) {
				$config_file_path = dirname( dirname( $config_file_path ) );
			}
		}

		$config_file_path .= '/wp-tests-config.php';

		if ( ! is_readable( $config_file_path ) ) {
			exit( "Error: Unable to locate the wp-tests-config.php file.\n" );
		}

		return $config_file_path;
	}

	/**
	 * Run the plugin's install script.
	 *
	 * Called by the setUp() method.
	 *
	 * Installation is run seperately, so the plugin is never actually loaded in this
	 * process. This provides more realistic testing of the uninstall process, since
	 * it is run while the plugin is inactive, just like in "real life".
	 *
	 * @since 0.1.0
	 */
	protected function install() {

		system(
			WP_PHP_BINARY
			. ' ' . escapeshellarg( dirname( dirname( __FILE__ ) ) . '/bin/install-plugin.php' )
			. ' ' . escapeshellarg( $this->plugin_file )
			. ' ' . escapeshellarg( $this->install_function )
			. ' ' . escapeshellarg( $this->locate_wp_tests_config() )
			. ' ' . (int) is_multisite()
		);
	}

	/**
	 * Simulate the usage of the plugin, by including a simulation file remotely.
	 *
	 * Called by uninstall() to simulate the usage of the plugin. This is useful to
	 * help make sure that the plugin really uninstalls itself completely, by undoing
	 * everything that might be done while it is active, not just reversing the un-
	 * install routine (though in some cases that may be all that is necessary).
	 *
	 * @since 0.1.0
	 */
	public function simulate_usage() {

		if ( empty( $this->simulation_file ) || $this->simulated_usage ) {
			return;
		}

		global $wpdb;

		$wpdb->query( 'ROLLBACK' );

		system(
			WP_PHP_BINARY
			. ' ' . escapeshellarg( dirname( dirname( __FILE__ ) ) . '/bin/simulate-plugin-use.php' )
			. ' ' . escapeshellarg( $this->plugin_file )
			. ' ' . escapeshellarg( $this->simulation_file )
			. ' ' . escapeshellarg( $this->locate_wp_tests_config() )
			. ' ' . (int) is_multisite()
		);

		$this->flush_cache();

		$this->simulated_usage = true;
	}

	/**
	 * Run the plugin's uninstall script.
	 *
	 * Call it and then run your uninstall assertions. You should always test
	 * installation before testing uninstallation.
	 *
	 * @since 0.1.0
	 */
	public function uninstall() {

		global $wpdb, $plugin;

		if ( ! $this->simulated_usage ) {

			$wpdb->query( 'ROLLBACK' );

			// If the plugin has a usage simulation file, run it remotely.
			$this->simulate_usage();
		}

		// We're going to do real table dropping, not temporary tables.
		$drop_temp_tables = array( $this, '_drop_temporary_table' );

		// Back compat. See https://core.trac.wordpress.org/ticket/24800.
		if ( method_exists( $this, '_drop_temporary_tables' ) ) {
			$drop_temp_tables = array( $this, '_drop_temporary_tables' );
		}

		remove_filter( 'query', $drop_temp_tables );

		if ( empty( $this->plugin_file ) ) {
			exit( 'Error: $plugin_file property not set.' . PHP_EOL );
		}

		$plugin_dir = dirname( $this->plugin_file );

		if ( file_exists( $plugin_dir . '/uninstall.php' ) ) {

			define( 'WP_UNINSTALL_PLUGIN', $this->plugin_file );
			include $plugin_dir . '/uninstall.php';

		} elseif ( ! empty( $this->uninstall_function ) ) {

			include $this->plugin_file;

			add_action( 'uninstall_' . $this->plugin_file, $this->uninstall_function );

			do_action( 'uninstall_' . $this->plugin_file );

		} else {

			exit( 'Error: $uninstall_function property not set.' . PHP_EOL );
		}

		$this->flush_cache();
	}

	/**
	 * Asserts that a database table does not exist.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table	  The table name.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertTableNotExists( $table, $message = '' ) {

		self::assertThat( $table, self::isNotInDatabase(), $message );
	}

	/**
	 * Asserts that a database table exsists.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table The table name.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertTableExists( $table, $message = '' ) {

		self::assertThat( $table, self::isInDatabase(), $message );
	}

	/**
	 * Asserts that no options with a given prefix exist.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoOptionsWithPrefix( $prefix, $message = '' ) {

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $GLOBALS['wpdb']->options, 'option_name', $prefix ), $message );
	}

	/**
	 * Asserts that no site options with a given prefix exist.
	 *
	 * @since 0.4.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoSiteOptionsWithPrefix( $prefix, $message = '' ) {

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $GLOBALS['wpdb']->sitemeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Asserts that no usermeta with a given prefix exists.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoUserMetaWithPrefix( $prefix, $message = '' ) {

		global $wpdb;

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $wpdb->usermeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Asserts that no user options with a given prefix exist.
	 *
	 * User options are usermeta, prefixed with the current blog's prefix. They are
	 * mainly used in multisite, or multisite compatible plugins.
	 *
	 * @since 0.2.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoUserOptionsWithPrefix( $prefix, $message = '' ) {

		global $wpdb;

		$prefix = $wpdb->get_blog_prefix() . $prefix;

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $wpdb->usermeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Asserts that no postmeta with a given prefix exists.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoPostMetaWithPrefix( $prefix, $message = '' ) {

		global $wpdb;

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $wpdb->postmeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Asserts that no commentmeta with a given prefix exist.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix  The prefix to check for.
	 * @param string $message An optional message.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNoCommentMetaWithPrefix( $prefix, $message = '' ) {

		global $wpdb;

		self::assertThat( $prefix, self::tableColumnHasNoRowsWithPrefix( $wpdb->commentmeta, 'meta_key', $prefix ), $message );
	}

	/**
	 * Database table not existant constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_Plugin_Uninstall_Tester_PHPUnit_Constraint_IsTableNonExistant
	 */
	public static function isNotInDatabase() {

		return new WP_Plugin_Uninstall_Tester_PHPUnit_Constraint_IsTableNonExistant;
	}

	/**
	 * Database table is in the database constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_Plugin_Uninstall_Tester_PHPUnit_Constraint_IsTableExistant
	 */
	public static function isInDatabase() {

		return new WP_Plugin_Uninstall_Tester_PHPUnit_Constraint_IsTableExistant;
	}

	/**
	 * No row values with prefix in DB table constraint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table  The name of the table.
	 * @param string $column The name of the row in the table to check.
	 *
	 * @return WP_Plugin_Uninstall_Tester_PHPUnit_Constraint_NoRowsWithPrefix
	 */
	public static function tableColumnHasNoRowsWithPrefix( $table, $column, $prefix ) {

		return new WP_Plugin_Uninstall_Tester_PHPUnit_Constraint_NoRowsWithPrefix( $table, $column, $prefix );
	}
}

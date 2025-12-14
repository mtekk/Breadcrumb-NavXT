<?php
/**
 * This file contains tests for the mtekk_adminKit class
 *
 * @group adminKit
 * @group bcn_core
 */
use \mtekk\adminKit\adminKit as adminKit;
use \mtekk\adminKit\setting;
if(class_exists('\mtekk\adminKit\adminKit')) {
	class adminKitDUT extends adminKit{
		const version = '1.8.1';
		protected $full_name = 'A Plugin Settings';
		protected $short_name = 'A Plugin';
		protected $access_level = 'manage_options';
		protected $identifier = 'adminkit';
		protected $unique_prefix = 'mak';
		protected $plugin_basename = null;
		protected $support_url = 'http://mtekk.us/archives/wordpress/plugins-wordpress/breadcrumb-navxt-';
		function __construct() {
			parent::__construct();
		}
		function setOpts($opts) {
			$this->opt = $opts;
		}
		function setSettings($settings) {
			$this->settings = $settings;
		}
		//Wrapper for settings_update_loop as have to pass reference
		function call_settings_update_loop(&$settings, $input) {
			return $this->settings_update_loop($settings, $input);
		}
		//Super evil caller function to get around our private and protected methods in the parent class
		function call($function, $args = array()) {
			return call_user_func_array(array($this, $function), $args);
		}
	}
}
class adminKitTest extends WP_UnitTestCase {
	public $admin;
	protected static $superadmin_id;
	protected static $editor_id;
	protected static $author_id;
	protected static $contributor_id;
	function set_up() {
		parent::set_up();
		self::$superadmin_id  = self::factory()->user->create(
				array(
						'role'       => 'administrator',
						'user_login' => 'superadmin',
				)
				);
		self::$editor_id      = self::factory()->user->create(
				array(
						'role' => 'editor',
				)
				);
		self::$author_id      = self::factory()->user->create(
				array(
						'role' => 'author',
				)
				);
		self::$contributor_id = self::factory()->user->create(
				array(
						'role' => 'contributor',
				)
				);
		if ( is_multisite() ) {
			update_site_option( 'site_admins', array( 'superadmin' ) );
		}
		$this->admin = new adminKitDUT();
	}
	public function tear_down() {
		unset($_REQUEST['_wpnonce']);
		self::delete_user( self::$superadmin_id );
		self::delete_user( self::$editor_id );
		self::delete_user( self::$author_id );
		self::delete_user( self::$contributor_id );
		parent::tear_down();
	}
	function test_install() {
		$defaults = array('Sopta' => 'A Value', 'Soptb' => 'B Value', 'Soptc' => 'C Value');
		//Check that the settings aren't there
		$this->assertFalse($this->admin->get_option('mak_options'));
		$this->assertFalse($this->admin->get_option('mak_options_bk'));
		$this->assertFalse($this->admin->get_option('mak_version'));
		//Run install
		//'Logon' for nonce check
		wp_set_current_user( self::$superadmin_id);
		$_REQUEST['_wpnonce'] = wp_create_nonce('mak_admin_undo');
		//Run the install
		$this->admin->install();
		//Check that the expected values are there
		$this->assertSame(array(), $this->admin->get_option('mak_options'));
		$this->assertSame(array(), $this->admin->get_option('mak_options_bk'));
		$this->assertSame('1.8.1', $this->admin->get_option('mak_version'));
		//Now see that happens on a re-run
		$this->admin->setOpts($defaults);
		$this->admin->install();
		//Nothing should have happened, as the version is current
		$this->assertSame(array(), $this->admin->get_option('mak_options'));
		$this->assertSame(array(), $this->admin->get_option('mak_options_bk'));
		$this->assertSame('1.8.1', $this->admin->get_option('mak_version'));
		//Change the DB version
		$this->admin->update_option('mak_options', $defaults);
		$this->admin->update_option('mak_version', '1.0.0');
		$this->admin->install();
		$this->assertSame($defaults, $this->admin->get_option('mak_options'));
		$this->assertSame(array(), $this->admin->get_option('mak_options_bk'));
		$this->assertSame('1.8.1', $this->admin->get_option('mak_version'));
	}
	function test_uninstall() {
		//Setup the options
		$this->admin->update_option('mak_options', array('foo' => 'bar'));
		$this->admin->update_option('mak_options_bk', array('foo' => 'car'));
		$this->admin->update_option('mak_version', '1.8.1');
		//Uninstall
		$this->admin->uninstall();
		//Check that they're now gone
		$this->assertFalse($this->admin->get_option('mak_options'));
		$this->assertFalse($this->admin->get_option('mak_options_bk'));
		$this->assertFalse($this->admin->get_option('mak_version'));
	}
	function test_version_check_old_version() {
		//Test when the version is less than the current version
		$this->expectOutputRegex('/.?Your settings are for an older version of this plugin and need to be migrated\..?/');
		$this->assertFalse($this->admin->version_check('1.8.0'));
		$this->expectOutputRegex('/.?Your settings are for an older version of this plugin and need to be migrated\..?/');
		$this->assertFalse($this->admin->version_check('1.7.3'));
		$this->expectOutputRegex('/.?Your settings are for an older version of this plugin and need to be migrated\..?/');
		$this->assertFalse($this->admin->version_check('1.2.0'));
	}
	function test_version_check_current_version() {
		//Test when the version is equal to the current version
		$this->expectOutputString('');
		$this->assertTrue($this->admin->version_check('1.8.1'));
	}
	function test_version_check_newer_version() {
		//Test when the version is greater than current version
		$this->expectOutputRegex('/.?Your settings are for a newer version of this plugin\..?/');
		$this->assertTrue($this->admin->version_check('1.8.2'));
	}
	function test_version_check_no_settings() {
		$this->admin->setSettings(NULL);
		//Test when the version is greater than current version
		$this->expectOutputRegex('/.?Your plugin install is incomplete\..?/');
		$this->assertFalse($this->admin->version_check(''));
	}
	function test_versopm_check_invalid_settings() {
		$settings = array();
		$settings['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$settings['Soptb'] = new setting\setting_string('optb', 'B Value', 'An option');
		$settings['Aoptc'] = new setting\setting_absint('optc', '-1', 'An option');
		//Setup some settings that should not validate
		$this->admin->setSettings($settings);
		$this->expectOutputRegex('/.?One or more of your plugin settings are invalid\..?/');
		$this->assertFalse($this->admin->version_check('1.8.1'));
	}
	function test_opt_backup() {
		$current_val = array('Sopta' => 'A Value', 'Soptb' => 'Hello', 'Soptc' => 'C Value');
		$old_val = array('Sopta' => 'AB Value', 'Soptb' => 'Hello', 'Soptc' => 'CD Value');
		$this->admin->update_option('mak_options', $current_val);
		$this->admin->update_option('mak_options_bk', $old_val);
		$this->admin->call('opts_backup');
		$this->assertSame($current_val, $this->admin->get_option('mak_options_bk'));
		$this->assertSame($current_val, $this->admin->get_option('mak_options'));
	}
	function test_settings_validate() {
		$settings = array();
		$settings['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$settings['Soptb'] = new setting\setting_string('optb', 'B Value', 'An option');
		$settings['Aoptc'] = new setting\setting_absint('optc', '-1', 'An option');
		//Setup some settings that should not validate
		$this->admin->setSettings($settings);
		$this->assertFalse($this->admin->settings_validate($settings));
		//Fix the issue and check that they now validate
		$settings['Aoptc'] = new setting\setting_absint('optc', '2', 'An option');
		$this->admin->setSettings($settings);
		$this->assertTrue($this->admin->settings_validate($settings));
	}
	function test_settings_update_loop() {
		$defaults = array();
		$defaults['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$defaults['Soptb'] = new setting\setting_string('optb', 'B Value', 'An option');
		$defaults['Soptc'] = new setting\setting_string('optc', 'C Value', 'An option');
		$input = array('Sopta' => 'A Value', 'Soptb' => 'Hello', 'Soptc' => 'C Value');
		$this->admin->call_settings_update_loop($defaults, $input);
		$this->assertSame('optb', $defaults['Soptb']->get_name());
		$this->assertSame($input['Soptb'], $defaults['Soptb']->get_value());
	}
	function test_opts_update_save_only_non_defaults() {
		$defaults = array();
		$defaults['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$defaults['Soptb'] = new setting\setting_string('optb', 'B Value', 'An option');
		$defaults['Soptc'] = new setting\setting_string('optc', 'C Value', 'An option');
		$this->admin->setSettings($defaults);
		//Setup what's in the db
		$this->admin->update_option('mak_options', array('Sopta' => 'Cool Value', 'Soptb' => 'Hello', 'Soptc' => 'C Value'));
		//Mockup the update request, change optb
		$_POST['mak_options'] = array('Sopta' => 'A Value', 'Soptb' => 'Hello', 'Soptc' => 'C Value');
		//'Logon' for nonce check
		wp_set_current_user( self::$superadmin_id);
		$_REQUEST['_wpnonce'] = wp_create_nonce('mak_options-options');
		$this->admin->call('opts_update');
		//Retrieve the saved options
		$saved_options = $this->admin->get_option('mak_options');
		//We should only see the two non-default option values
		$this->assertSame(array('Soptb' => 'Hello'), $saved_options);
	}
	function test_settings_to_opts() {
		$defaults = array();
		$defaults['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$defaults['Soptb'] = new setting\setting_string('optb', 'B Value', 'An option');
		$defaults['Soptc'] = new setting\setting_string('optc', 'C Value', 'An option');
		$this->assertSame(array('Sopta' => 'A Value', 'Soptb' => 'B Value', 'Soptc' => 'C Value'), adminKit::settings_to_opts($defaults));
	}
	function test_load_opts_into_settings() {
		$defaults = array();
		$defaults['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$defaults['Soptb'] = new setting\setting_string('optb', 'B Value', 'An option');
		$defaults['Soptc'] = new setting\setting_string('optc', 'C Value', 'An option');
		$opts = array('Sopta' => 'Some Value', 'Soptb' => 'B Value', 'Soptc' => 'Cool Value');
		adminKit::load_opts_into_settings($opts, $defaults);
		$this->assertSame('Some Value', $defaults['Sopta']->get_value());
		$this->assertSame('B Value', $defaults['Soptb']->get_value());
		$this->assertSame('Cool Value', $defaults['Soptc']->get_value());
	}
	function test_setting_equal_check() {
		$defaults = array();
		$defaults['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$defaults['Soptb'] = new setting\setting_string('optb', 'A Value', 'An option');
		$defaults['Soptc'] = new setting\setting_string('optc', 'C Value', 'An option');
		$defaults['Soptd'] = new setting\setting_string('optc', 'C Value', 'An option');
		$defaults['ioptd'] = new setting\setting_int('optd', 8, 'An option');
		$defaults['iopte'] = new setting\setting_int('optd', 7, 'An option');
		//Check obviously non-equal
		$this->assertSame(-1, $this->admin->setting_equal_check($defaults['Sopta'], $defaults['Soptc']));
		//Check equal value, different name
		$this->assertSame(-1, $this->admin->setting_equal_check($defaults['Sopta'], $defaults['Soptb']));
		//Test truly equal
		$this->assertSame(0, $this->admin->setting_equal_check($defaults['Soptc'], $defaults['Soptd']));
		//Test greater than
		$this->assertSame(1, $this->admin->setting_equal_check($defaults['ioptd'], $defaults['iopte']));
	}
	function test_opts_undo() {
		$current_val = array('Sopta' => 'A Value', 'Soptb' => 'Hello', 'Soptc' => 'C Value');
		$old_val = array('Sopta' => 'AB Value', 'Soptb' => 'Hello', 'Soptc' => 'CD Value');
		$this->admin->update_option('mak_options', $current_val);
		$this->admin->update_option('mak_options_bk', $old_val);
		//'Logon' for nonce check
		wp_set_current_user( self::$superadmin_id);
		$_REQUEST['_wpnonce'] = wp_create_nonce('mak_admin_undo');
		//Undo the settings (swap _bk with the regular options)
		$this->admin->opts_undo();
		do_action('admin_notices');
		$this->expectOutputRegex('/.?Settings successfully undid the last operation\..?/');
		$this->assertSame($current_val, $this->admin->get_option('mak_options_bk'));
		$this->assertSame($old_val, $this->admin->get_option('mak_options'));
	}
	function test_init_opts_undo() {
		$current_val = array('Sopta' => 'A Value', 'Soptb' => 'Hello', 'Soptc' => 'C Value');
		$old_val = array('Sopta' => 'AB Value', 'Soptb' => 'Hello', 'Soptc' => 'CD Value');
		$this->admin->update_option('mak_options', $current_val);
		$this->admin->update_option('mak_options_bk', $old_val);
		//'Logon' for nonce check
		wp_set_current_user( self::$superadmin_id);
		$_GET['mak_admin_undo'] = true;
		$_REQUEST['_wpnonce'] = wp_create_nonce('mak_admin_undo');
		//Undo the settings (swap _bk with the regular options)
		$this->admin->init();
		do_action('admin_notices');
		$this->expectOutputRegex('/.?Settings successfully undid the last operation\..?/');
		$this->assertSame($current_val, $this->admin->get_option('mak_options_bk'));
		$this->assertSame($old_val, $this->admin->get_option('mak_options'));
	}
	function test_opts_reset() {
		$current_val = array('Sopta' => 'A Value', 'Soptb' => 'Hello', 'Soptc' => 'C Value');
		$old_val = array('Sopta' => 'AB Value', 'Soptb' => 'Hello', 'Soptc' => 'CD Value');
		$defaults = array();
		$defaults['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$defaults['Soptb'] = new setting\setting_string('optb', 'B Value', 'An option');
		$defaults['Soptc'] = new setting\setting_string('optc', 'C Value', 'An option');
		$this->admin->setSettings($defaults);
		$this->admin->update_option('mak_options', $current_val);
		$this->admin->update_option('mak_options_bk', $old_val);
		//'Logon' for nonce check
		wp_set_current_user( self::$superadmin_id);
		$_REQUEST['_wpnonce'] = wp_create_nonce('mak_admin_import_export');
		//Reset to the defaults
		$this->admin->opts_reset();
		do_action('admin_notices');
		$this->expectOutputRegex('/.?Settings successfully reset to the default values\..?/');
		$this->assertSame($current_val, $this->admin->get_option('mak_options_bk'));
		//While we would expect the settings to end up being the defaults, if we do a get_option, it should be an empty array
		$this->assertSame(array(), $this->admin->get_option('mak_options'));
		//$this->assertSame(array('Sopta' => 'A Value', 'Soptb' => 'B Value', 'Soptc' => 'C Value'), $this->admin->get_option('mak_options'));
	}
	function test_init_opts_reset() {
		$current_val = array('Sopta' => 'A Value', 'Soptb' => 'Hello', 'Soptc' => 'C Value');
		$old_val = array('Sopta' => 'AB Value', 'Soptb' => 'Hello', 'Soptc' => 'CD Value');
		$defaults = array();
		$defaults['Sopta'] = new setting\setting_string('opta', 'A Value', 'An option');
		$defaults['Soptb'] = new setting\setting_string('optb', 'B Value', 'An option');
		$defaults['Soptc'] = new setting\setting_string('optc', 'C Value', 'An option');
		$this->admin->setSettings($defaults);
		$this->admin->update_option('mak_options', $current_val);
		$this->admin->update_option('mak_options_bk', $old_val);
		//'Logon' for nonce check
		wp_set_current_user( self::$superadmin_id);
		$_POST['mak_admin_reset'] = true;
		$_REQUEST['_wpnonce'] = wp_create_nonce('mak_admin_import_export');
		//Reset to the defaults
		$this->admin->init();
		do_action('admin_notices');
		$this->expectOutputRegex('/.?Settings successfully reset to the default values\..?/');
		$this->assertSame($current_val, $this->admin->get_option('mak_options_bk'));
		//While we would expect the settings to end up being the defaults, if we do a get_option, it should be an empty array
		$this->assertSame(array(), $this->admin->get_option('mak_options'));
		//$this->assertSame(array('Sopta' => 'A Value', 'Soptb' => 'B Value', 'Soptc' => 'C Value'), $this->admin->get_option('mak_options'));
	}
}
<?php
/**
 * This file contains tests for the mtekk_adminKit class
 *
 * @group adminKit
 * @group bcn_core
 */
if(class_exists('mtekk_adminKit')) {
	class adminKitDUT extends mtekk_adminKit {
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
	}
}
class adminKitTest extends WP_UnitTestCase {
	public $admin;
	protected static $superadmin_id;
	protected static $editor_id;
	protected static $author_id;
	protected static $contributor_id;
	function setUp() {
		parent::setUp();
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
	public function tearDown() {
		unset($_REQUEST['_wpnonce']);
		self::delete_user( self::$superadmin_id );
		self::delete_user( self::$editor_id );
		self::delete_user( self::$author_id );
		self::delete_user( self::$contributor_id );
		parent::tearDown();
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
		$settings['Sopta'] = new mtekk_adminKit_setting_string('opta', 'A Value', 'An option');
		$settings['Soptb'] = new mtekk_adminKit_setting_string('optb', 'B Value', 'An option');
		$settings['Aoptc'] = new mtekk_adminKit_setting_absint('optc', '-1', 'An option');
		//Setup some settings that should not validate
		$this->admin->setSettings($settings);
		$this->expectOutputRegex('/.?One or more of your plugin settings are invalid\..?/');
		$this->assertFalse($this->admin->version_check('1.8.1'));
	}
	function test_settings_validate() {
		$settings = array();
		$settings['Sopta'] = new mtekk_adminKit_setting_string('opta', 'A Value', 'An option');
		$settings['Soptb'] = new mtekk_adminKit_setting_string('optb', 'B Value', 'An option');
		$settings['Aoptc'] = new mtekk_adminKit_setting_absint('optc', '-1', 'An option');
		//Setup some settings that should not validate
		$this->admin->setSettings($settings);
		$this->assertFalse($this->admin->settings_validate($settings));
		//Fix the issue and check that they now validate
		$settings['Aoptc'] = new mtekk_adminKit_setting_absint('optc', '2', 'An option');
		$this->admin->setSettings($settings);
		$this->assertTrue($this->admin->settings_validate($settings));
	}
	function test_opts_update_save_only_non_defaults() {
		$defaults = array();
		$defaults['Sopta'] = new mtekk_adminKit_setting_string('opta', 'A Value', 'An option');
		$defaults['Soptb'] = new mtekk_adminKit_setting_string('optb', 'B Value', 'An option');
		$defaults['Soptc'] = new mtekk_adminKit_setting_string('optc', 'C Value', 'An option');
		$this->admin->setSettings($defaults);
		//Mockup the update request, change optb
		$_POST['mak_options'] = array('opta' => 'A Value', 'optb' => 'Hello', 'optc' => 'C Value');
		//'Logon' for nonce check
		wp_set_current_user( self::$superadmin_id);
		$_REQUEST['_wpnonce'] = wp_create_nonce('mak_options-options');
		$this->admin->opts_update();
		//Retrieve the saved options
		$saved_options = $this->admin->get_option('mak_options');
		//We should only see the one option that changed value
		$this->assertSame(array('Soptb' => 'Hello'), $saved_options);
	}
}
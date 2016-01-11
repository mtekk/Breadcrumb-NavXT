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
	}
}
class adminKitTest extends WP_UnitTestCase {
	public $admin;
	function setUp() {
		parent::setUp();
		$this->admin = new adminKitDUT();
	}
	public function tearDown() {
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
	function test_version_check_no_opts() {
		$this->admin->setOpts(NULL);
		//Test when the version is greater than current version
		$this->expectOutputRegex('/.?Your plugin install is incomplete\..?/');
		$this->assertFalse($this->admin->version_check(''));
	}
}
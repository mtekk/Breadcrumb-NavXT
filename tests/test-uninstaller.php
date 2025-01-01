<?php
/**
 * This file contains tests for the adminKit uninstaller
 *
 * @group uninstall
 */
class UninstallerTests extends WP_Plugin_Uninstall_UnitTestCase
{
	public function set_up()
	{
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/breadcrumb-navxt.php';
		parent::set_up();
		global $current_user;
		// This code will run before each test!
		$current_user = new WP_User(1);
		$current_user->set_role('administrator');
		require dirname( dirname( __FILE__ ) ) . '/class.bcn_breadcrumb.php';
		require dirname( dirname( __FILE__ ) ) . '/class.bcn_breadcrumb_trail.php';
		require dirname( dirname( __FILE__ ) ) . '/class.bcn_admin.php';
		$settings = array();
		$bcn_breadcrumb_trail = new bcn_breadcrumb_trail();
		$bcn_breadcrumb_admin = new bcn_admin($bcn_breadcrumb_trail->opt, 'breadcrumb-navxt', $settings);
		$bcn_breadcrumb_admin->install();
	}
	
	public function tear_down()
	{
		parent::tear_down();
		// This code will run after each test
	}
	function test_uninstall()
	{
		global $plugin, $current_screen;
		//Ensure we're actually installed
		$this->assertNotEquals( false, get_option('bcn_version') );
		$this->assertNotEquals( false, get_option('bcn_options') );
		$this->assertNotEquals( false, get_option('bcn_options_bk') );
		
		$plugin = 'breadcrumb-navxt.php';
		
		//We need to trigger is_admin()
		$screen = WP_Screen::get( 'admin_init' );
		$current_screen = $screen;
		
		//No go on and uninstall
		$this->uninstall();
		
		//Ensure we're actually uninstalled
		$this->assertEquals( false, get_option('bcn_version') );
		$this->assertEquals( false, get_option('bcn_options') );
		$this->assertEquals( false, get_option('bcn_options_bk') );
	}
}


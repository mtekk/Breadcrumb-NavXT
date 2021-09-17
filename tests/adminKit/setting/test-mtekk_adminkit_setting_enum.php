<?php
/**
 * This file contains tests for the mtekk_adminKit setting_enum class
 *
 * @group adminKit
 * @group bcn_core
 */
class adminKitSettingEnumTest extends WP_UnitTestCase {
	public $settings = array();
	function setUp() {
		parent::setUp();
		$this->settings['normal_setting'] = new \mtekk\adminKit\setting\setting_enum(
				'normal_setting',
				'A Value',
				'Normal Setting',
				false,
				false,
				array('A Value', 'Another Value', 'Some other value'));
		$this->settings['empty_ok_setting'] = new \mtekk\adminKit\setting\setting_enum(
				'empty_ok_setting',
				'A Value',
				'Empty Ok Setting',
				true,
				false,
				array('A Value', 'Another Value', 'Some other value'));
		$this->settings['deprecated_setting'] = new \mtekk\adminKit\setting\setting_enum(
				'deprecated_setting',
				'A different Value',
				'Deprecated Setting',
				false,
				true,
				array('A different Value', 'Something Else'));
	}
	public function tearDown() {
		parent::tearDown();
	}
	function test_is_deprecated() {
		$this->assertFalse($this->settings['normal_setting']->is_deprecated());
		$this->assertTrue($this->settings['deprecated_setting']->is_deprecated());
	}
	function test_set_deprecated() {
		$new_setting = new \mtekk\adminKit\setting\setting_enum(
				'normal_setting',
				'A Value',
				'Normal Setting',
				false,
				false,
				array('A Value', 'Another Value', 'Some other value'));
		//Check initial value from the constructed version
		$this->assertFalse($new_setting->is_deprecated());
		//Change to being deprecated
		$new_setting->set_deprecated(true);
		$this->assertTrue($new_setting->is_deprecated());
		//Change back
		$new_setting->set_deprecated(false);
		$this->assertFalse($new_setting->is_deprecated());
	}
	function test_get_value() {
		$this->assertSame($this->settings['normal_setting']->get_value(), 'A Value');
		$this->assertSame($this->settings['deprecated_setting']->get_value(), 'A different Value');
	}
	function test_set_value() {
		//Check default value
		$this->assertSame($this->settings['normal_setting']->get_value(), 'A Value');
		//Change the value
		$this->settings['normal_setting']->set_value('A New Value');
		//Check
		$this->assertSame($this->settings['normal_setting']->get_value(), 'A New Value');
	}
	function test_get_allowed_vals() {
		$this->assertSame($this->settings['normal_setting']->get_allowed_vals(), array('A Value', 'Another Value', 'Some other value'));
	}
	function test_set_allowed_vals() {
		$this->settings['normal_setting']->set_allowed_vals(array('Garbage', 'Plastic', 'Aluminum', 'Steel', 'Glass'));
		$this->assertSame($this->settings['normal_setting']->get_allowed_vals(), array('Garbage', 'Plastic', 'Aluminum', 'Steel', 'Glass'));
	}
	function test_get_title() {
		$this->assertSame($this->settings['normal_setting']->get_title(), 'Normal Setting');
	}
	function test_get_name() {
		$this->assertSame($this->settings['normal_setting']->get_name(), 'normal_setting');
	}
	function test_get_allow_empty() {
		$this->assertFalse($this->settings['normal_setting']->get_allow_empty());
		$this->assertTrue($this->settings['empty_ok_setting']->get_allow_empty());
	}
	function test_set_allow_empty() {
		$this->assertTrue($this->settings['empty_ok_setting']->get_allow_empty());
		$this->settings['empty_ok_setting']->set_allow_empty(false);
		$this->assertFalse($this->settings['empty_ok_setting']->get_allow_empty());
	}
	function test_maybe_update_from_form_input() {
		$input = array('normal_setting' => 'Some other value', 'normal_settinga' => 'barf', 'empty_string_setting' => '');
		$input_notthere = array('normal_settinga' => 'barf', 'empty_string_setting' => '');
		$this->settings['normal_setting']->set_allow_empty(true);
		//Test allowing empty
		$this->settings['normal_setting']->maybe_update_from_form_input($input);
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Some other value');
		//Change the value
		$this->settings['normal_setting']->set_value('Another Value');
		$this->settings['normal_setting']->maybe_update_from_form_input(array('normal_setting' => ''));
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Another Value');
		//Change the value
		$this->settings['normal_setting']->set_value('Another Value');
		$this->settings['normal_setting']->maybe_update_from_form_input($input_notthere);
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Another Value');
		//Test diallowing empty
		$this->settings['normal_setting']->set_allow_empty(false);
		//Change the value
		$this->settings['normal_setting']->set_value('Another Value');
		$this->settings['normal_setting']->maybe_update_from_form_input($input);
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Some other value');
		//Change the value
		$this->settings['normal_setting']->set_value('Another Value');
		$this->settings['normal_setting']->maybe_update_from_form_input(array('normal_setting' => ''));
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Another Value');
		//Change the value
		$this->settings['normal_setting']->set_value('Another Value');
		$this->settings['normal_setting']->maybe_update_from_form_input($input_notthere);
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Another Value');
	}
	function test_validate() {
		//Test something in the allowed values
		$this->assertSame($this->settings['normal_setting']->validate('Another Value'), 'Another Value');
		//Test something not in the allowed values
		$this->assertSame($this->settings['normal_setting']->validate('Another Value or something'), 'A Value');
	}
}

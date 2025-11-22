<?php
/**
 * This file contains tests for the mtekk_adminKit setting_bool class
 *
 * @group adminKit
 * @group bcn_core
 */
class adminKitSettingBoolTest extends WP_UnitTestCase {
	public $settings = array();
	function set_up() {
		parent::set_up();
		$this->settings['normal_setting'] = new \mtekk\adminKit\setting\setting_bool(
				'normal_setting',
				true,
				'Normal Setting');
		$this->settings['normal_settingb'] = new \mtekk\adminKit\setting\setting_bool(
				'normal_settingb',
				true,
				'Normal Setting');
		$this->settings['normal_settingc'] = new \mtekk\adminKit\setting\setting_bool(
				'normal_settingc',
				true,
				'Normal Setting');
		$this->settings['empty_ok_setting'] = new \mtekk\adminKit\setting\setting_bool(
				'empty_ok_setting',
				true,
				'Normal Setting',
				true);
		$this->settings['deprecated_setting'] = new \mtekk\adminKit\setting\setting_bool(
				'deprecated_setting',
				true,
				'Deprecated Setting',
				false,
				true);
	}
	public function tear_down() {
		parent::tear_down();
	}
	function test_is_deprecated() {
		$this->assertFalse($this->settings['normal_setting']->is_deprecated());
		$this->assertTrue($this->settings['deprecated_setting']->is_deprecated());
	}
	function test_set_deprecated() {
		$new_setting = new \mtekk\adminKit\setting\setting_bool(
				'normal_setting',
				true,
				'Normal Setting',
				false);
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
		$this->assertTrue($this->settings['normal_setting']->get_value());
		$this->assertTrue($this->settings['deprecated_setting']->get_value());
	}
	function test_set_value() {
		//Check default value
		$this->assertTrue($this->settings['normal_setting']->get_value());
		//Change the value
		$this->settings['normal_setting']->set_value(false);
		//Check
		$this->assertFalse($this->settings['normal_setting']->get_value());
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
	function test_get_opt_name() {
		$this->assertSame('b' . $this->settings['normal_setting']->get_name(), $this->settings['normal_setting']->get_opt_name());
	}
	function test_maybe_update_from_form_input() {
		$input = array('bnormal_setting' => '1', 'bnormal_settinga' => true, 'bnormal_settingb' => '0', 'bnormal_settingc' => false);
		$input_notthere = array('bnormal_settinga' => true, 'babnormal_setting' => 'sdf');
		//Test allowing empty
		$this->settings['normal_setting']->set_allow_empty(true);
		$this->settings['normal_setting']->maybe_update_from_form_input($input);
		$this->assertTrue($this->settings['normal_setting']->get_value());
		$this->settings['normal_setting']->maybe_update_from_form_input($input_notthere);
		$this->assertFalse($this->settings['normal_setting']->get_value());
		//Test false value
		$this->settings['normal_settingb']->maybe_update_from_form_input($input);
		$this->assertFalse($this->settings['normal_settingb']->get_value());
		$this->settings['normal_settingc']->maybe_update_from_form_input($input);
		$this->assertFalse($this->settings['normal_settingc']->get_value());
		//Test ignore missing param
		$this->settings['normal_setting']->maybe_update_from_form_input($input_notthere, true);
		$this->assertFalse($this->settings['normal_setting']->get_value());
		$this->settings['normal_setting']->maybe_update_from_form_input($input, true);
		$this->assertTrue($this->settings['normal_setting']->get_value());
		$this->settings['normal_setting']->maybe_update_from_form_input($input_notthere, true);
		$this->assertTrue($this->settings['normal_setting']->get_value());
		//Test false value
		$this->settings['normal_settingb']->set_value(true);
		$this->settings['normal_settingc']->set_value(true);
		$this->settings['normal_settingb']->maybe_update_from_form_input($input, true);
		$this->assertFalse($this->settings['normal_settingb']->get_value());
		$this->settings['normal_settingc']->maybe_update_from_form_input($input, true);
		$this->assertFalse($this->settings['normal_settingc']->get_value());
		//Test diallowing empty
		$this->settings['empty_ok_setting']->set_allow_empty(false);
		$this->settings['normal_setting']->maybe_update_from_form_input($input);
		$this->assertTrue($this->settings['normal_setting']->get_value());
		$this->settings['normal_setting']->maybe_update_from_form_input($input_notthere);
		$this->assertFalse($this->settings['normal_setting']->get_value());
		$this->settings['normal_settingb']->maybe_update_from_form_input($input);
		$this->assertFalse($this->settings['normal_settingb']->get_value());
	}
	function test_validate() {
		//Test a normal/expected condition
		$this->assertFalse($this->settings['normal_setting']->validate(false));
		$this->assertTrue($this->settings['normal_setting']->validate(true));
		//Test a string
		$this->assertTrue($this->settings['normal_setting']->validate('false'));
		//TODO: Test some PHP type jugling stuff here
	}
}

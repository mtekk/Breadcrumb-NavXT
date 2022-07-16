<?php
/**
 * This file contains tests for the mtekk_adminKit setting_html class
 *
 * @group adminKit
 * @group bcn_core
 */
class adminKitSettingHTMLTest extends WP_UnitTestCase {
	public $settings = array();
	function set_up() {
		parent::set_up();
		$this->settings['normal_setting'] = new \mtekk\adminKit\setting\setting_html(
				'normal_setting',
				'A Value',
				'Normal Setting');
		$this->settings['empty_ok_setting'] = new \mtekk\adminKit\setting\setting_html(
				'empty_ok_setting',
				'A Value',
				'Empty Ok Setting',
				true);
		$this->settings['deprecated_setting'] = new \mtekk\adminKit\setting\setting_html(
				'deprecated_setting',
				'A different Value',
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
		$new_setting = new \mtekk\adminKit\setting\setting_html(
				'normal_setting',
				'A Value',
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
		$this->assertSame('H' . $this->settings['normal_setting']->get_name(), $this->settings['normal_setting']->get_opt_name());
		$this->assertSame('h' . $this->settings['empty_ok_setting']->get_name(), $this->settings['empty_ok_setting']->get_opt_name());
	}
	function test_maybe_update_from_form_input() {
		$input = array('Hnormal_setting' => 'Some Value', 'Hnormal_settinga' => 'barf', 'hempty_string_setting' => '', 'hempty_ok_setting' => 'foobar');
		$input_notthere = array('Hnormal_settinga' => 'barf', 'hempty_string_setting' => '');
		//Test allowing empty
		$this->settings['empty_ok_setting']->maybe_update_from_form_input($input);
		$this->assertSame($this->settings['empty_ok_setting']->get_value(), 'foobar');
		//Change the value
		$this->settings['empty_ok_setting']->set_value('Yep');
		$this->settings['empty_ok_setting']->maybe_update_from_form_input(array('hempty_ok_setting' => ''));
		$this->assertSame($this->settings['empty_ok_setting']->get_value(), '');
		//Change the value
		$this->settings['empty_ok_setting']->set_value('Yep');
		$this->settings['empty_ok_setting']->maybe_update_from_form_input($input_notthere);
		$this->assertSame($this->settings['empty_ok_setting']->get_value(), 'Yep');
		//Test diallowing empty
		//Change the value
		$this->settings['normal_setting']->set_value('Yep');
		$this->settings['normal_setting']->maybe_update_from_form_input($input);
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Some Value');
		//Change the value
		$this->settings['normal_setting']->set_value('Yep');
		$this->settings['normal_setting']->maybe_update_from_form_input(array('Hnormal_setting' => ''));
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Yep');
		//Change the value
		$this->settings['normal_setting']->set_value('Yep');
		$this->settings['normal_setting']->maybe_update_from_form_input($input_notthere);
		$this->assertSame($this->settings['normal_setting']->get_value(), 'Yep');
	}
	function test_validate() {
		//Test an integer
		$this->assertSame($this->settings['normal_setting']->validate(42), '42');
		//Test a string
		$this->assertSame($this->settings['normal_setting']->validate('42'), '42');
		//Test a negative numbeer
		$this->assertSame($this->settings['normal_setting']->validate(-42), '-42');
		//Test a normal string
		$this->assertSame($this->settings['normal_setting']->validate('Hello World'), 'Hello World');
		//Test an empty string
		$this->settings['normal_setting']->set_allow_empty(true);
		$this->assertSame($this->settings['normal_setting']->validate(''), '');
		$this->settings['normal_setting']->set_allow_empty(false);
		$this->assertSame($this->settings['normal_setting']->validate(''), 'A Value');
		//Test HTML
		$this->assertSame($this->settings['normal_setting']->validate('<span>Hello World</span>'), '<span>Hello World</span>');
		//Try a script
		$this->assertSame($this->settings['normal_setting']->validate('<script>alert("porkchop sandwiches!")</script>Hello World'), 'alert("porkchop sandwiches!")Hello World');
	}
}

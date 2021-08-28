<?php
/**
 * This file contains tests for the mtekk_adminKit setting_bool class
 *
 * @group adminKit
 * @group bcn_core
 */
class adminKitSettingBoolTest extends WP_UnitTestCase {
	public $settings = array();
	function setUp() {
		parent::setUp();
		$this->settings['normal_setting'] = new mtekk_adminKit_setting_bool(
				'normal_setting',
				true,
				'Normal Setting');
		$this->settings['empty_ok_setting'] = new mtekk_adminKit_setting_bool(
				'empty_ok_setting',
				true,
				'Normal Setting',
				true);
		$this->settings['deprecated_setting'] = new mtekk_adminKit_setting_bool(
				'deprecated_setting',
				true,
				'Deprecated Setting',
				false,
				true);
	}
	public function tearDown() {
		parent::tearDown();
	}
	function test_is_deprecated() {
		$this->assertFalse($this->settings['normal_setting']->is_deprecated());
		$this->assertTrue($this->settings['deprecated_setting']->is_deprecated());
	}
	function test_setDeprecated() {
		$new_setting = new mtekk_adminKit_setting_bool(
				'normal_setting',
				true,
				'Normal Setting',
				false);
		//Check initial value from the constructed version
		$this->assertFalse($new_setting->is_deprecated());
		//Change to being deprecated
		$new_setting->setDeprecated(true);
		$this->assertTrue($new_setting->is_deprecated());
		//Change back
		$new_setting->setDeprecated(false);
		$this->assertFalse($new_setting->is_deprecated());
	}
	function test_getValue() {
		$this->assertTrue($this->settings['normal_setting']->getValue());
		$this->assertTrue($this->settings['deprecated_setting']->getValue());
	}
	function test_setValue() {
		//Check default value
		$this->assertTrue($this->settings['normal_setting']->getValue());
		//Change the value
		$this->settings['normal_setting']->setValue(false);
		//Check
		$this->assertFalse($this->settings['normal_setting']->getValue());
	}
	function test_getTitile() {
		$this->assertSame($this->settings['normal_setting']->getTitle(), 'Normal Setting');
	}
	function test_getName() {
		$this->assertSame($this->settings['normal_setting']->getName(), 'normal_setting');
	}
	function test_getAllowEmpty() {
		$this->assertFalse($this->settings['normal_setting']->getAllowEmpty());
		$this->assertTrue($this->settings['empty_ok_setting']->getAllowEmpty());
	}
	function test_setAllowEmpty() {
		$this->assertTrue($this->settings['empty_ok_setting']->getAllowEmpty());
		$this->settings['empty_ok_setting']->setAllowEmpty(false);
		$this->assertFalse($this->settings['empty_ok_setting']->getAllowEmpty());
	}
	function test_maybeUpdateFromFormInput() {
		$input = array('normal_setting' => 1, 'normal_settinga' => true);
		$input_notthere = array('normal_settinga' => true, 'abnormal_setting' => 'sdf');
		//Test allowing empty
		$this->settings['normal_setting']->setAllowEmpty(true);
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input);
		$this->assertTrue($this->settings['normal_setting']->getValue());
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere);
		$this->assertFalse($this->settings['normal_setting']->getValue());
		//Test diallowing empty
		$this->settings['empty_ok_setting']->setAllowEmpty(false);
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input);
		$this->assertTrue($this->settings['normal_setting']->getValue());
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere);
		$this->assertFalse($this->settings['normal_setting']->getValue());
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

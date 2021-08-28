<?php
/**
 * This file contains tests for the mtekk_adminKit setting_float class
 *
 * @group adminKit
 * @group bcn_core
 */
class adminKitSettingFloatTest extends WP_UnitTestCase {
	public $settings = array();
	function setUp() {
		parent::setUp();
		$this->settings['normal_setting'] = new mtekk_adminKit_setting_float(
				'normal_setting',
				42.1,
				'Normal Setting');
		$this->settings['empty_ok_setting'] = new mtekk_adminKit_setting_float(
				'empty_ok_setting',
				42.1,
				'Empty Ok Setting',
				true);
		$this->settings['deprecated_setting'] = new mtekk_adminKit_setting_float(
				'deprecated_setting',
				30.2,
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
		$new_setting = new mtekk_adminKit_setting_float(
				'normal_setting',
				42.1,
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
		$this->assertSame($this->settings['normal_setting']->getValue(), 42.1);
		$this->assertSame($this->settings['deprecated_setting']->getValue(), 30.2);
	}
	function test_setValue() {
		//Check default value
		$this->assertSame($this->settings['normal_setting']->getValue(), 42.1);
		//Change the value
		$this->settings['normal_setting']->setValue(67);
		//Check
		$this->assertSame($this->settings['normal_setting']->getValue(), 67);
	}
	function test_getTitile() {
		$this->assertSame($this->settings['normal_setting']->getTitle(), 'Normal Setting');
	}
	function test_getName() {
		$this->assertSame($this->settings['normal_setting']->getName(), 'normal_setting');
	}
	function test_setAllowEmpty() {
		$this->assertTrue($this->settings['empty_ok_setting']->getAllowEmpty());
		$this->settings['empty_ok_setting']->setAllowEmpty(false);
		$this->assertFalse($this->settings['empty_ok_setting']->getAllowEmpty());
	}
	function test_maybeUpdateFromFormInput() {
		$input = array('normal_setting' => 45.6, 'normal_settinga' => 423);
		$input_notthere = array('normal_settinga' => 53, 'abnormal_setting' => 33);
		//Test allowing empty
		$this->settings['normal_setting']->setAllowEmpty(true);
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input);
		$this->assertSame($this->settings['normal_setting']->getValue(), 45.6);
		//Change the value
		$this->settings['normal_setting']->setValue(67.0);
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere);
		$this->assertSame($this->settings['normal_setting']->getValue(), 67.0);
		//Test diallowing empty
		$this->settings['normal_setting']->setAllowEmpty(false);
		//Change the value
		$this->settings['normal_setting']->setValue(67.0);
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input);
		$this->assertSame($this->settings['normal_setting']->getValue(), 45.6);
		//Change the value
		$this->settings['normal_setting']->setValue(67.0);
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere);
		$this->assertSame($this->settings['normal_setting']->getValue(), 67.0);
	}
	function test_validate() {
		//Test a normal/expected condition
		$this->assertSame($this->settings['normal_setting']->validate(42.1), 42.1);
		//Test a string
		$this->assertSame($this->settings['normal_setting']->validate('42'), 42.0);
		//Test a negative numbeer
		$this->assertSame($this->settings['normal_setting']->validate(-42), -42.0);
	}
}

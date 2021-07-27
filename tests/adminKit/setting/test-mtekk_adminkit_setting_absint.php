<?php
/**
 * This file contains tests for the mtekk_adminKit setting_absint class
 *
 * @group adminKit
 * @group bcn_core
 */
class adminKitSettingAbsintTest extends WP_UnitTestCase {
	public $settings = array();
	function setUp() {
		parent::setUp();
		$this->settings['normal_setting'] = new mtekk_adminKit_setting_absint(
				'normal_setting',
				42,
				'Normal Setting',
				false);
		$this->settings['deprecated_setting'] = new mtekk_adminKit_setting_absint(
				'deprecated_setting',
				30,
				'Deprecated Setting',
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
		$new_setting = new mtekk_adminKit_setting_absint(
				'normal_setting',
				42,
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
		$this->assertSame($this->settings['normal_setting']->getValue(), 42);
		$this->assertSame($this->settings['deprecated_setting']->getValue(), 30);
	}
	function test_setValue() {
		//Check default value
		$this->assertSame($this->settings['normal_setting']->getValue(), 42);
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
	function test_maybeUpdateFromFormInput() {
		$imput = array('normal_setting' => 45, 'normal_settinga' => 423);
		$input_notthere = array('normal_settinga' => 53, 'abnormal_setting' => 33);
		//Test allowing empty
		$this->assertSame($this->settings['normal_setting']->maybeUpdateFromFormInput($imput, true), 45);
		$this->assertSame($this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere, true), 0);
		//Test diallowing empty
		$this->assertSame($this->settings['normal_setting']->maybeUpdateFromFormInput($imput, false), 45);
		$this->assertSame($this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere, false), 0);
	}
	function test_validate() {
		//Test a normal/expected condition
		$this->assertSame($this->settings['normal_setting']->validate(42), 42);
		//Test a string
		$this->assertSame($this->settings['normal_setting']->validate('42'), 42);
		//Test a negative numbeer
		$this->assertSame($this->settings['normal_setting']->validate(-42), 42);
	}
}

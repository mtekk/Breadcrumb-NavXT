<?php
/**
 * This file contains tests for the mtekk_adminKit setting_string class
 *
 * @group adminKit
 * @group bcn_core
 */
class adminKitSettingStringTest extends WP_UnitTestCase {
	public $settings = array();
	function setUp() {
		parent::setUp();
		$this->settings['normal_setting'] = new mtekk_adminKit_setting_string(
				'normal_setting',
				'A Value',
				'Normal Setting');
		$this->settings['empty_ok_setting'] = new mtekk_adminKit_setting_string(
				'empty_ok_setting',
				'A Value',
				'Empty Ok Setting',
				true);
		$this->settings['deprecated_setting'] = new mtekk_adminKit_setting_string(
				'deprecated_setting',
				'A different Value',
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
		$new_setting = new mtekk_adminKit_setting_string(
				'normal_setting',
				'A Value',
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
		$this->assertSame($this->settings['normal_setting']->getValue(), 'A Value');
		$this->assertSame($this->settings['deprecated_setting']->getValue(), 'A different Value');
	}
	function test_setValue() {
		//Check default value
		$this->assertSame($this->settings['normal_setting']->getValue(), 'A Value');
		//Change the value
		$this->settings['normal_setting']->setValue('A New Value');
		//Check
		$this->assertSame($this->settings['normal_setting']->getValue(), 'A New Value');
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
		$input = array('normal_setting' => 'Some Value', 'normal_settinga' => 'barf', 'empty_string_setting' => '');
		$input_notthere = array('normal_settinga' => 'barf', 'empty_string_setting' => '');
		//Test allowing empty
		$this->settings['normal_setting']->setAllowEmpty(true);
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input);
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Some Value');
		//Change the value
		$this->settings['normal_setting']->setValue('Yep');
		$this->settings['normal_setting']->maybeUpdateFromFormInput(array('normal_setting' => ''));
		$this->assertSame($this->settings['normal_setting']->getValue(), '');
		//Change the value
		$this->settings['normal_setting']->setValue('Yep');
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere);
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Yep');
		//Test diallowing empty
		$this->settings['normal_setting']->setAllowEmpty(false);
		//Change the value
		$this->settings['normal_setting']->setValue('Yep');
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input);
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Some Value');
		//Change the value
		$this->settings['normal_setting']->setValue('Yep');
		$this->settings['normal_setting']->maybeUpdateFromFormInput(array('normal_setting' => ''));
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Yep');
		//Change the value
		$this->settings['normal_setting']->setValue('Yep');
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere);
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Yep');
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
		$this->settings['normal_setting']->setAllowEmpty(true);
		$this->assertSame($this->settings['normal_setting']->validate(''), '');
		$this->settings['normal_setting']->setAllowEmpty(false);
		$this->assertSame($this->settings['normal_setting']->validate(''), 'A Value');
		//Test HTML
		$this->assertSame($this->settings['normal_setting']->validate('<span>Hello World</span>'), '&lt;span&gt;Hello World&lt;/span&gt;');
	}
}

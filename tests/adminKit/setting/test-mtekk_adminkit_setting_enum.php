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
		$this->settings['normal_setting'] = new mtekk_adminKit_setting_enum(
				'normal_setting',
				'A Value',
				'Normal Setting',
				false,
				false,
				array('A Value', 'Another Value', 'Some other value'));
		$this->settings['empty_ok_setting'] = new mtekk_adminKit_setting_enum(
				'empty_ok_setting',
				'A Value',
				'Empty Ok Setting',
				true,
				false,
				array('A Value', 'Another Value', 'Some other value'));
		$this->settings['deprecated_setting'] = new mtekk_adminKit_setting_enum(
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
	function test_setDeprecated() {
		$new_setting = new mtekk_adminKit_setting_enum(
				'normal_setting',
				'A Value',
				'Normal Setting',
				false,
				false,
				array('A Value', 'Another Value', 'Some other value'));
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
	function test_getAllowedVals() {
		$this->assertSame($this->settings['normal_setting']->getAllowedVals(), array('A Value', 'Another Value', 'Some other value'));
	}
	function test_setAllowedVals() {
		$this->settings['normal_setting']->setAllowedVals(array('Garbage', 'Plastic', 'Aluminum', 'Steel', 'Glass'));
		$this->assertSame($this->settings['normal_setting']->getAllowedVals(), array('Garbage', 'Plastic', 'Aluminum', 'Steel', 'Glass'));
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
		$input = array('normal_setting' => 'Some other value', 'normal_settinga' => 'barf', 'empty_string_setting' => '');
		$input_notthere = array('normal_settinga' => 'barf', 'empty_string_setting' => '');
		$this->settings['normal_setting']->setAllowEmpty(true);
		//Test allowing empty
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input);
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Some other value');
		//Change the value
		$this->settings['normal_setting']->setValue('Another Value');
		$this->settings['normal_setting']->maybeUpdateFromFormInput(array('normal_setting' => ''));
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Another Value');
		//Change the value
		$this->settings['normal_setting']->setValue('Another Value');
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere);
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Another Value');
		//Test diallowing empty
		$this->settings['normal_setting']->setAllowEmpty(false);
		//Change the value
		$this->settings['normal_setting']->setValue('Another Value');
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input);
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Some other value');
		//Change the value
		$this->settings['normal_setting']->setValue('Another Value');
		$this->settings['normal_setting']->maybeUpdateFromFormInput(array('normal_setting' => ''));
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Another Value');
		//Change the value
		$this->settings['normal_setting']->setValue('Another Value');
		$this->settings['normal_setting']->maybeUpdateFromFormInput($input_notthere);
		$this->assertSame($this->settings['normal_setting']->getValue(), 'Another Value');
	}
	function test_validate() {
		//Test something in the allowed values
		$this->assertSame($this->settings['normal_setting']->validate('Another Value'), 'Another Value');
		//Test something not in the allowed values
		$this->assertSame($this->settings['normal_setting']->validate('Another Value or something'), 'A Value');
	}
}

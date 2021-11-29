<?php
/**
 * This file contains tests for the mtekk_adminKit class
 *
 * @group adminKit
 * @group bcn_core
 */
use \mtekk\adminKit\message as message;
if(class_exists('\mtekk\adminKit\message'))
{
	class mtekk_adminKit_message_DUT extends message{
		//Super evil caller function to get around our private and protected methods in the parent class
		function call($function, $args = array()) {
			return call_user_func_array(array($this, $function), $args);
		}
		//Super evil getter function to get around our private and protected methods in the parent class
		function get($var) {
			return $this->$var;
		}
 	}
}
class adminKitMessageTest extends WP_UnitTestCase {
	public $messages = array();
	function setUp() {
		parent::setUp();
		$this->messages[] = new mtekk_adminKit_message_DUT('test dismissible msg', 'warning', true, 'test_msga');
		$this->messages[] = new mtekk_adminKit_message_DUT('test msg', 'warning', false, 'test_msgb');
		$this->messages[] = new mtekk_adminKit_message_DUT('another test dismissible msg', 'warning', true, 'test_msgc');
		$this->messages[] = new mtekk_adminKit_message_DUT('test msg', 'info');
	}
	public function tearDown() {
		parent::tearDown();
	}
	function test_was_dismissed() {
		//Ensure we start with messages that were not dismissed
		$this->assertFalse($this->messages[0]->was_dismissed());
		$this->assertFalse($this->messages[2]->was_dismissed());
		//Now dismiss one of them via a transient
		set_transient('test_msga', true, 2592000);
		$this->assertTrue($this->messages[0]->was_dismissed());
		$this->assertFalse($this->messages[2]->was_dismissed());
	}
	function test_dismiss() {
		//Ensure we start with messages that were not dismissed
		$this->assertFalse($this->messages[0]->was_dismissed());
		$this->assertFalse($this->messages[2]->was_dismissed());
		$_POST['uid'] = 'test_msga';
		$_REQUEST['nonce'] = wp_create_nonce($_POST['uid'] . '_dismiss');
		$this->messages[0]->dismiss();
		$this->assertTrue(get_transient('test_msga'));
		$this->assertFalse(get_transient('test_msgc'));
	}
	function test_render_dismissible() {
		$this->expectOutputRegex('/.?<div class="notice notice-warning is-dismissible"><p>test dismissible msg<\/p><meta property="uid" content="test_msga"><meta property="nonce" content="?/');
		$this->messages[0]->render();
	}
	function test_render_dismissed() {
		set_transient('test_msga', true, 2592000);
		$this->expectOutputString('<div class="notice notice-warning"><p>test msg</p></div>');
		$this->messages[0]->render();
		$this->messages[1]->render();
	}
	function test_construction_dismissible() {
		$this->messages[] = new mtekk_adminKit_message_DUT('test msg 2', 'info');
		$this->assertFalse(end($this->messages)->get('dismissible'));
		$this->messages[] = new mtekk_adminKit_message_DUT('test msg 3', 'info', true, 'uida');
		$this->assertTrue(end($this->messages)->get('dismissible'));
		//Now try our invalid uids
		$this->setExpectedIncorrectUsage('mtekk\adminKit\message::__construct');
		$this->messages[] = new mtekk_adminKit_message_DUT('test msg 4', 'info', true, '');
		$this->assertFalse(end($this->messages)->get('dismissible'));
		$this->messages[] = new mtekk_adminKit_message_DUT('test msg 5', 'info', true, ' ');
		$this->assertFalse(end($this->messages)->get('dismissible'));
		$this->messages[] = new mtekk_adminKit_message_DUT('test msg 6', 'info', true, null);
		$this->assertFalse(end($this->messages)->get('dismissible'));
		$this->messages[] = new mtekk_adminKit_message_DUT('test msg 7', 'info', true);
		$this->assertFalse(end($this->messages)->get('dismissible'));
	}
}
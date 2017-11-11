<?php
/**
 * This file contains tests for the mtekk_adminKit class
 *
 * @group adminKit
 * @group bcn_core
 */
class adminKitMessageTest extends WP_UnitTestCase {
	public $messages = array();
	function setUp() {
		parent::setUp();
		$this->messages[] = new mtekk_adminKit_message('test dismissible msg', 'warning', true, 'test_msga');
		$this->messages[] = new mtekk_adminKit_message('test msg', 'warning', false, 'test_msgb');
		$this->messages[] = new mtekk_adminKit_message('another test dismissible msg', 'warning', true, 'test_msgc');
		$this->messages[] = new mtekk_adminKit_message('test msg', 'info');
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
}
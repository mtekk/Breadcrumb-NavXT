<?php
/**
 * This file contains tests for the bcn_breadcrumb class
 *
 * @group bcn_breadcrumb
 * @group bcn_core
 */
class WidgetTest extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();
	}
	public function tearDown() {
		parent::tearDown();
	}
	function test_update() {
		$widget = new bcn_widget();
		$instance = array(
			'title' => '<a>Breadcrumbs-n-Stuff</a>',
			'pretext' => '<img src="https://test.com/foo.png" alt="foo" />',
			'type' => 'microdata',
			'linked' => true
		);
		wp_set_current_user( $this->factory()->user->create(array(
			'role' => 'administrator',
		)));
		
		$expected = array(
			'title' => 'Breadcrumbs-n-Stuff',
			'pretext' => '<img src="https://test.com/foo.png" alt="foo" />',
			'type' => 'microdata',
			'linked' => true,
			'reverse' => false,
			'front' => false,
			'force' => false
		);
		$result = $widget->update($instance, array());
		$this->assertEquals($result, $expected);
	}
}
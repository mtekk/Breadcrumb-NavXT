<?php
/**
 * This file contains tests for the bcn_breadcrumb class
 *
 * @group bcn_breadcrumb_trail
 * @group bcn_core
 */
 if(class_exists('bcn_breadcrumb_trail'))
 {
 	class bcn_breadcrumb_trail_DUT extends bcn_breadcrumb_trail {
 		function __construct() {
			parent::__construct();
		}
		//Super evil caller function to get around our private and protected methods in the parent class
		function call($function, $args) {
			return call_user_func_array(array($this, $function), $args);
		}
 	}
 }
class BreadcrumbTrailTest extends WP_UnitTestCase {
	public $breadcrumb_trail;
	function setUp() {
		parent::setUp();
		$this->breadcrumb_trail = new bcn_breadcrumb_trail_DUT();
	}
	public function tearDown() {
		parent::tearDown();
	}
	function test_post_terms() {
		//Create our terms and post
		$tids = $this->factory->tag->create_many(10);
		$pid = $this->factory->post->create(array('post_title' => 'Test Post'));
		//Assign the terms to the post
		wp_set_object_terms($pid, $tids, 'post_tag');
		//Now call post_terms
		$this->breadcrumb_trail->call('post_terms', array($pid, 'post_tag'));
		//Ensure we have only one breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Now disect this breadcrumb
		$title_exploded = explode(', ', $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		//Ensure we have only 10 sub breadcrumbs
		$this->assertCount(10, $title_exploded);
		//Reset the breadcrumb trail
		$this->breadcrumb_trail->breadcrumbs = array();
		//Now register our filter
		add_filter('bcn_post_terms', 
			function($terms, $taxonomy, $id) {
				return array_slice($terms, 2, 5);
		}, 3, 10);
		//Call post_terms again
		$this->breadcrumb_trail->call('post_terms', array($pid, 'post_tag'));
		//Ensure we have only one breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Now disect this breadcrumb
		$title_exploded = explode(', ', $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		//Ensure we have only 5 sub breadcrumbs
		$this->assertCount(5, $title_exploded);
	}
}
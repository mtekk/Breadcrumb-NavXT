<?php
/**
 * This file contains tests for the bcn_breadcrumb class
 *
 * @group bcn_breadcrumb_trail
 * @group bcn_core
 */
 //Only PHP5.3 or newer are valid for these tests
 if(version_compare(phpversion(), '5.3.0', '<'))
 {
 	return;
 }
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
		//Register some types to use for various tests
		register_post_type('czar', array(
			'label' => 'Czars',
			'public' => true,
			'hierarchical' => false,
			'has_archive' => true,
			'publicaly_queryable' => true,
			'taxonomies' => array('post_tag', 'category')
			)
		);
		register_post_type('bureaucrat', array(
			'label' => 'Bureaucrats',
			'public' => true,
			'hierarchical' => true,
			'has_archive' => true,
			'publicaly_queryable' => true
			)
		);
		register_taxonomy('ring', 'czar', array(
			'lable' => 'Rings',
			'public' => true,
			'hierarchical' => false,
			)
		);
		register_taxonomy('party', 'czar', array(
			'lable' => 'Parties',
			'public' => true,
			'hierarchical' => true,
			)
		);
		register_taxonomy('job_title', 'bureaucrat', array(
			'lable' => 'Job Title',
			'public' => true,
			'hierarchical' => true,
			)
		);
	}
	public function tearDown() {
		parent::tearDown();
	}
	/**
	 * Tests for the bcn_post_terms filter
	 */
	function test_bcn_post_terms() {
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
	/**
	 * Tests for the bcn_add_post_type_arg filter
	 */
	function test_bcn_add_post_type_arg() {		
		//Call maybe_add_post_type_arg
		$url1 = $this->breadcrumb_trail->call('maybe_add_post_type_arg', array('http://foo.bar/car', 'czar', 'category'));
		//Ensure we added a post type arg
		$this->assertSame('http://foo.bar/car?post_type=czar', $url1);
		//Now register our filter
		add_filter('bcn_add_post_type_arg', 
			function($add_query_arg, $type, $taxonomy) {
				return false;
		}, 3, 10);
		//Call maybe_add_post_type_arg again
		$url2 = $this->breadcrumb_trail->call('maybe_add_post_type_arg', array('http://foo.bar/car', 'czar', 'ring'));
		//Ensure we didn't add a post type arg
		$this->assertSame('http://foo.bar/car', $url2);
	}
}
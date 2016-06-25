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
	/**
	 * Tests for the bcn_pick_post_term filter
	 */
	function test_bcn_pick_post_term() {
		global $tids;
		//Create our terms and post
		$tids = $this->factory->category->create_many(10);
		$pid = $this->factory->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($tids[7], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[8], 'category', array('parent' => $tids[6]));
		//Assign the terms to the post
		wp_set_object_terms($pid, $tids, 'category');
		//Call post_hierarchy
		$this->breadcrumb_trail->call('post_hierarchy', array($pid, 'post'));
		//Inspect the resulting breadcrumb
		//Ensure we have 3 breadcrumbs
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		//Should be term 7, 8, 6
		$this->assertSame(get_term($tids[7], 'category')->name, $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(get_term($tids[8], 'category')->name, $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_term($tids[6], 'category')->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		//Now, let's add a filter that selects a middle id
		add_filter('bcn_pick_post_term', 
			function($term, $id, $type) {
				global $tids;
				$terms = get_the_terms($id, 'category');
				foreach($terms as $sterm)
				{
					if($sterm->term_id == $tids[3])
					{
						return $sterm;	
					}
				}
				return $term;
		}, 3, 10);
		//Reset the breadcrumb trail
		$this->breadcrumb_trail->breadcrumbs = array();
		//Call post_hierarchy
		$this->breadcrumb_trail->call('post_hierarchy', array($pid, 'post'));
		//Inspect the resulting breadcrumb
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Should only be term 3
		$this->assertSame(get_term($tids[3], 'category')->name, $this->breadcrumb_trail->breadcrumbs[0]->get_title());
	}
	/**
	 * Tests for the pick_post_term_deep function
	 */
	function test_pick_post_term_deep() {
		global $tids;
		//Create our terms and post
		$tids = $this->factory->category->create_many(10);
		$pid = $this->factory->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($tids[7], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[8], 'category', array('parent' => $tids[6]));
		wp_update_term($tids[9], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[5], 'category', array('parent' => $tids[7]));
		//Setup a second hierarchy
		wp_update_term($tids[2], 'category', array('parent' => $tids[1]));
		wp_update_term($tids[3], 'category', array('parent' => $tids[2]));
		//Assign the terms to the post
		wp_set_object_terms($pid, $tids, 'category');
		//Call post_hierarchy, should have gotten the first hierarchy
		$this->assertSame(get_term($tids[3], 'category')->name, $this->breadcrumb_trail->call('pick_post_term', array($pid, 'post'))->name);
	}
}
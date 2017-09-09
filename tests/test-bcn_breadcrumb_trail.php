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
		function call($function, $args = array()) {
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
			'label' => 'Rings',
			'public' => true,
			'hierarchical' => false,
			)
		);
		register_taxonomy('party', array('czar', 'post'), array(
			'label' => 'Parties',
			'public' => true,
			'hierarchical' => true,
			)
		);
		register_taxonomy('job_title', 'bureaucrat', array(
			'label' => 'Job Title',
			'public' => true,
			'hierarchical' => true,
			)
		);
	}
	public function tearDown() {
		parent::tearDown();
	}
	function test_add()
	{
		$pid = $this->factory->post->create(array('post_title' => 'Test Post', 'post_type' => 'post'));
		$post = get_post($pid);
		$breadcrumb = new bcn_breadcrumb(get_the_title($post), bcn_breadcrumb::default_template_no_anchor, array('post', 'post-' . $post->post_type, 'current-item'), NULL, $post->ID);
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Call the add function
		$breadcrumb_ret = $this->breadcrumb_trail->call('add', array($breadcrumb));
		//Make sure we have one breadcrumb on the trail
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Ensure the add function returned the added breadcrumb
		$this->assertSame($breadcrumb, $breadcrumb_ret);
		//Ensure the breadcrumb on the trail is what we expect
		$this->assertSame($breadcrumb, $this->breadcrumb_trail->breadcrumbs[0]);
	}
	function test_do_post() {
		//Test a single post
		$popid = $this->factory->post->create(array('post_title' => 'Test Post', 'post_type' => 'post'));
		$post = get_post($popid);
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs to start
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Call do_post
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(2, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame('Test Post' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('post', 'post-post', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertSame('Uncategorized' , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(array('taxonomy', 'category') , $this->breadcrumb_trail->breadcrumbs[1]->get_types());
		
		//Test a page
		$papid = $this->factory->post->create(array('post_title' => 'Test Parent', 'post_type' => 'page'));
		$papid = $this->factory->post->create(array('post_title' => 'Test Child', 'post_type' => 'page', 'post_parent' => $papid));
		$post = get_post($papid);
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs to start
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Call do_post
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(2, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame('Test Child' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('post', 'post-page', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertSame('Test Parent' , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(array('post', 'post-page') , $this->breadcrumb_trail->breadcrumbs[1]->get_types());
			
		//Test an attachment
		$attpida = $this->factory->post->create(array('post_title' => 'Test Attahementa', 'post_type' => 'attachment', 'post_parent' => $popid));
		$attpidb = $this->factory->post->create(array('post_title' => 'Test Attahementb', 'post_type' => 'attachment', 'post_parent' => $papid));
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$post = get_post($attpida);
		//Call do_post on the post attachement
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame('Test Attahementa' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('post', 'post-attachment', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertSame('Test Post' , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(array('post', 'post-post') , $this->breadcrumb_trail->breadcrumbs[1]->get_types());
		$this->assertSame('Uncategorized' , $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(array('taxonomy', 'category') , $this->breadcrumb_trail->breadcrumbs[2]->get_types());
		$this->breadcrumb_trail->breadcrumbs = array();
		$post = get_post($attpidb);
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Call do_post on the post attachement
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame('Test Attahementb' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('post', 'post-attachment', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertSame('Test Child' , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(array('post', 'post-page') , $this->breadcrumb_trail->breadcrumbs[1]->get_types());
		$this->assertSame('Test Parent' , $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(array('post', 'post-page') , $this->breadcrumb_trail->breadcrumbs[2]->get_types());
	}
	function test_query_var_to_taxonomy() {
		//Setup some taxonomies
		register_taxonomy('custom_tax0', 'post', array('query_var' => 'custom_tax_0'));
		register_taxonomy('custom_tax1', 'post', array('query_var' => 'custom_tax_1'));
		register_taxonomy('custom_tax1', 'post', array('query_var' => 'custom_tax_2'));
		//Check matching of an existant taxonomy
		$this->assertSame('custom_tax0', $this->breadcrumb_trail->call('query_var_to_taxonomy', array('custom_tax_0')));
		//Check return false of non-existant taxonomy
		$this->assertFalse($this->breadcrumb_trail->call('query_var_to_taxonomy', array('custom_tax_326375')));
	}
	function test_determine_taxonomy() {
		$this->set_permalink_structure('/%category%/%postname%/');
		//Create the custom taxonomy
		register_taxonomy('wptests_tax2', 'post', array(
			'hierarchical' => true,
			'rewrite' => array(
				'slug' => 'foo',
				'hierarchical true'
				)));
		//Create some terms
		$tids = $this->factory->category->create_many(10);
		$ttid1 = $this->factory()->term->create(array(
			'taxonomy' => 'wptests_tax2',
			'slug' => 'ctxterm1'));
		$ttid2 = $this->factory()->term->create(array(
			'taxonomy' => 'wptests_tax2',
			'slug' => 'ctxterm2',
			'parent' => $ttid1));
		//Create a test post
		$pid = $this->factory->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($tids[7], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[8], 'category', array('parent' => $tids[6]));
		wp_update_term($tids[9], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[5], 'category', array('parent' => $tids[7]));
		//Assign the terms to the post
		wp_set_object_terms($pid, array($tids[5]), 'category');
		wp_set_object_terms($pid, $ttid2, 'wptests_tax2');
		flush_rewrite_rules();
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		//Check no referer
		$this->assertFalse($this->breadcrumb_trail->call('determine_taxonomy'));
		//Let the custom taxonomy be our referer
		$_SERVER['HTTP_REFERER'] = get_term_link($ttid2);
		//Check matching of an existant taxonomy
		$this->assertSame('wptests_tax2', $this->breadcrumb_trail->call('determine_taxonomy'));
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
		$title_exploded = explode(', ', $this->breadcrumb_trail->breadcrumbs[0]->assemble(true, 3));
		//Ensure we have only 5 sub breadcrumbs
		$this->assertCount(5, $title_exploded);
		//Ensure we do not have double wrapped items
		foreach($title_exploded as $title_under_test)
		{
			$this->assertRegExp('@^<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the [^"]* archives\." href="[^"]*" class="[^"]*"><span property="name">[^<]*</span></a><meta property="position" content="[^"]*"></span>$@', $title_under_test);
		}
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
	 * Tests for the pick_post_term function
	 */
	function test_pick_post_term() {
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
		//Call post_hierarchy, should have gotten the deepest in the first returned hierarchy
		//However, we do not know the order of term return, so just check for any valid response (any deepest child)
		$this->assertThat(
			$this->breadcrumb_trail->call('pick_post_term', array($pid, 'post', 'category'))->name,
			$this->logicalOr(
				$this->equalTo(get_term($tids[3], 'category')->name),
				$this->equalTo(get_term($tids[5], 'category')->name),
				$this->equalTo(get_term($tids[9], 'category')->name)
			)
		);
	}
	function test_do_root()
	{
		//Create some pages
		$paid = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '3' as the home page
		update_option('page_on_front', $paid[3]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		$this->set_permalink_structure('/%category%/%postname%/');
		//Create some terms
		$tids = $this->factory->category->create_many(10);
		//Create a test post
		$pid = $this->factory->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($tids[7], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[8], 'category', array('parent' => $tids[6]));
		wp_update_term($tids[9], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[5], 'category', array('parent' => $tids[7]));
		//Assign the terms to the post
		wp_set_object_terms($pid, array($tids[5]), 'category');
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		$this->breadcrumb_trail->call('do_root');
		//Ensure we have 3 breadcrumbs
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertEquals($paid[0], $this->breadcrumb_trail->breadcrumbs[2]->get_id());
		$this->assertSame(get_the_title($paid[0]), $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertEquals($paid[5], $this->breadcrumb_trail->breadcrumbs[1]->get_id());
		$this->assertSame(get_the_title($paid[5]), $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertEquals($paid[6], $this->breadcrumb_trail->breadcrumbs[0]->get_id());
		$this->assertSame(get_the_title($paid[6]), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
	}
	function test_do_root_page()
	{
		//Create some pages
		$paid = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '3' as the home page
		update_option('page_on_front', $paid[9]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		//"Go to" our post
		$this->go_to(get_permalink($paid[1]));
		$this->breadcrumb_trail->call('do_root');
		//Ensure we have 0 breadcrumbs, root should not do anything for pages (we get to all but the home in post_parents)
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
	}
	function test_do_root_blog_home()
	{
		//Create some pages
		$paid = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '3' as the home page
		update_option('page_on_front', $paid[9]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		//"Go to" our post
		$this->go_to(get_home_url());
		$this->breadcrumb_trail->call('do_root');
		//Ensure we have 4 breadcrumbs
		$this->assertCount(4, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertEquals($paid[3], $this->breadcrumb_trail->breadcrumbs[3]->get_id());
		$this->assertSame(get_the_title($paid[3]), $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertEquals($paid[0], $this->breadcrumb_trail->breadcrumbs[2]->get_id());
		$this->assertSame(get_the_title($paid[0]), $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertEquals($paid[5], $this->breadcrumb_trail->breadcrumbs[1]->get_id());
		$this->assertSame(get_the_title($paid[5]), $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertEquals($paid[6], $this->breadcrumb_trail->breadcrumbs[0]->get_id());
		$this->assertSame(get_the_title($paid[6]), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
	}
	function test_do_root_no_blog()
	{
		//Create some pages
		$paid = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '3' as the home page
		update_option('page_on_front', $paid[3]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		$this->set_permalink_structure('/%category%/%postname%/');
		//Create some terms
		$tids = $this->factory->category->create_many(10);
		//Create a test post
		$pid = $this->factory->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($tids[7], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[8], 'category', array('parent' => $tids[6]));
		wp_update_term($tids[9], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[5], 'category', array('parent' => $tids[7]));
		//Assign the terms to the post
		wp_set_object_terms($pid, array($tids[5]), 'category');
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		//We don't want the blog breadcrumb
		$this->breadcrumb_trail->opt['bblog_display'] = false;
		$this->breadcrumb_trail->call('do_root');
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
	}
	function test_do_root_cpt()
	{
		//Create some pages
		$paid = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '3' as the home page
		update_option('page_on_front', $paid[3]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		register_post_type('bcn_testa', array('public' => true, 'rewrite' => array('slug' => 'bcn_testa')));
		flush_rewrite_rules();
		//Have to setup some CPT specific settings
		$this->breadcrumb_trail->opt['apost_bcn_testa_root'] = $paid[1];
		$this->breadcrumb_trail->opt['Hpost_bcn_testa_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_bcn_testa_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		$this->set_permalink_structure('/%category%/%postname%/');
		//Create a test post
		$pid = $this->factory->post->create(array('post_title' => 'Test Post', 'post_type' => 'bcn_testa'));
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		$this->breadcrumb_trail->call('do_root');
		//Ensure we have 2 breadcrumbs
		$this->assertCount(2, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertEquals($paid[2], $this->breadcrumb_trail->breadcrumbs[1]->get_id());
		$this->assertSame(get_the_title($paid[2]), $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertEquals($paid[1], $this->breadcrumb_trail->breadcrumbs[0]->get_id());
		$this->assertSame(get_the_title($paid[1]), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
	}
	/**
	 * Test for when the CPT root setting is a non-integer see #148
	 */
	function test_do_root_cpt_null_root()
	{
		//Create some pages
		$paid = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '3' as the home page
		update_option('page_on_front', $paid[3]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		register_post_type('bcn_testa', array('public' => true, 'rewrite' => array('slug' => 'bcn_testa')));
		flush_rewrite_rules();
		//Have to setup some CPT specific settings
		$this->breadcrumb_trail->opt['apost_bcn_testa_root'] = NULL;
		$this->breadcrumb_trail->opt['Hpost_bcn_testa_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_bcn_testa_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		$this->set_permalink_structure('/%category%/%postname%/');
		//Create a test post
		$pid = $this->factory->post->create(array('post_title' => 'Test Post', 'post_type' => 'bcn_testa'));
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		$this->breadcrumb_trail->call('do_root');
		//Ensure we have 0 breadcrumbs (no root)
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
	}
	function test_do_root_search()
	{
		register_post_type('bcn_testa', 
			array('public' => true,
			'rewrite' => array('slug' => 'bcn_testa',
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'query_var' => 'bcn_testa',
			'show_ui' => true,
			'show_in_menu' => true,
			'has_archive' => true,
			'can_export' => true,
			'show_in_nav_menus' => true)));
		flush_rewrite_rules();
		//Create some pages
		$paid = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '3' as the home page
		update_option('page_on_front', $paid[3]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		//Have to setup some CPT specific settings
		$this->breadcrumb_trail->opt['apost_bcn_testa_root'] = $paid[2];
		$this->breadcrumb_trail->opt['Hpost_bcn_testa_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_bcn_testa_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		$this->set_permalink_structure('/%category%/%postname%/');
		//Create a test post
		$pid = $this->factory->post->create(array('post_title' => 'Test Post', 'post_type' => 'bcn_testa'));
		
		//"Go to" our search, non-post type restricted
		$this->go_to(get_search_link('test'));
		$this->breadcrumb_trail->call('do_root');
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		
		//Again with the search CPT restricted
	/*	$this->breadcrumb_trail->breadcrumbs = array();
		$this->go_to(add_query_arg('post_type', 'bcn_testa', get_search_link('test')));
		var_dump(is_post_type_archive());
		$this->breadcrumb_trail->call('do_root');
		//Ensure we have 1 breadcrumbs from the do_root portion
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);*/
	}
	function test_is_builtin()
	{
		//Try some built in types
		$this->assertTrue($this->breadcrumb_trail->call('is_builtin', array('post')));
		$this->assertTrue($this->breadcrumb_trail->call('is_builtin', array('page')));
		$this->assertTrue($this->breadcrumb_trail->call('is_builtin', array('attachement')));
		//And now our CPT
		$this->assertFalse($this->breadcrumb_trail->call('is_builtin', array('czar')));
		$this->assertFalse($this->breadcrumb_trail->call('is_builtin', array('bureaucrat')));
	}
	function test_treat_as_root_page()
	{
		//TODO complete all cases
		$paid = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		$pidc = $this->factory->post->create(array('post_title' => 'Test Czar', 'post_type' => 'czar'));
		$pidb = $this->factory->post->create(array('post_title' => 'Test Bureaucrat', 'post_type' => 'bureaucrat'));
		//Set page '3' as the home page
		update_option('page_on_front', $paid[3]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		//Set page '0' as the root for czars
		$this->breadcrumb_trail->opt['apost_czar_root'] = $paid[0];
		$this->breadcrumb_trail->opt['bpost_czar_archive_display'] = false;
		//Set page '5' as the root for bureaucrats
		$this->breadcrumb_trail->opt['apost_bureaucrat_root'] = $paid[5];
		$this->breadcrumb_trail->opt['bpost_bureaucrat_archive_display'] = true;
		//End of setup, now to tests
		//Set of tests with the normal post post type
		//Test on the home page (page for posts)
		$this->go_to(get_home_url());
		$this->assertTrue($this->breadcrumb_trail->call('treat_as_root_page', array('post')));
		//Test on the frontpage
		$this->go_to(get_permalink($paid[3]));
		$this->assertFalse($this->breadcrumb_trail->call('treat_as_root_page', array('post')));
		//Onto a CPT
		//Test on the archive of a CPT
		$this->go_to(get_permalink($paid[0]));
		//Set the is_post_type_archive() flag, technically, we should do this differently
		$this->go_to(get_post_type_archive_link('czar'));
		$this->assertTrue($this->breadcrumb_trail->call('treat_as_root_page', array('czar')));
		//Test on the instance of a CPT
		$this->go_to(get_permalink($pidc));
		$this->assertFalse($this->breadcrumb_trail->call('treat_as_root_page', array('czar')));
		//Onto another CPT
		//Test on the archive of a CPT
		$this->go_to(get_permalink($paid[5]));
		//Set the is_post_type_archive() flag, technically, we should do this differently
		$this->go_to(get_post_type_archive_link('bureaucrat'));
		$this->assertFalse($this->breadcrumb_trail->call('treat_as_root_page', array('bureaucrat')));
		//Test on the instance of a CPT
		$this->go_to(get_permalink($pidb));
		$this->assertFalse($this->breadcrumb_trail->call('treat_as_root_page', array('bureaucrat')));
	}
	function test_has_archive()
	{
		register_post_type('bcn_testb', 
			array('public' => true,
			'rewrite' => array('slug' => 'bcn_testa',
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'query_var' => 'bcn_testa',
			'show_ui' => true,
			'show_in_menu' => true,
			'has_archive' => false,
			'can_export' => true,
			'show_in_nav_menus' => true)));
		flush_rewrite_rules();
		//Try a type that has an archive
		$this->assertTrue($this->breadcrumb_trail->call('has_archive', array('czar')));
		$this->assertTrue($this->breadcrumb_trail->call('has_archive', array('bureaucrat')));
		//Try some types that do not have archives
		$this->assertFalse($this->breadcrumb_trail->call('has_archive', array('post')));
		$this->assertFalse($this->breadcrumb_trail->call('has_archive', array('page')));
		$this->assertFalse($this->breadcrumb_trail->call('has_archive', array('bcn_testb')));
	}
	function test_get_type_string_query_var()
	{
		//Test with just one post type
		$this->go_to(add_query_arg(array('post_type' => 'czar'), get_search_link('test')));
		$this->assertSame('czar', $this->breadcrumb_trail->call('get_type_string_query_var'));
		//Test on multiple post types
		$this->go_to(add_query_arg(array('post_type' => array('bureaucrat', 'czar')), get_search_link('test')));
		$this->assertSame('czars', $this->breadcrumb_trail->call('get_type_string_query_var', array('czars')));
		//Test default
		$this->go_to(add_query_arg(array('post_type' => ''), get_search_link('test')));
		$this->assertSame('post', $this->breadcrumb_trail->call('get_type_string_query_var'));
	}
	function test_is_type_query_var_array()
	{
		//Test with just one post type
		$this->go_to(add_query_arg(array('post_type' => 'czar'), get_search_link('test')));
		$this->assertFalse($this->breadcrumb_trail->call('is_type_query_var_array'));
		//Test on multiple post types
		$this->go_to(add_query_arg(array('post_type' => array('bureaucrat', 'czar')), get_search_link('test')));
		$this->assertTrue($this->breadcrumb_trail->call('is_type_query_var_array'));
	}
	function test_maybe_add_post_type_arg()
	{
		$tida = $this->factory->term->create(array('name' => 'Test Category', 'taxonomy' => 'category'));
		$tidb = $this->factory->term->create(array('name' => 'Test Party', 'taxonomy' => 'party'));
		$pida = $this->factory->post->create(array('post_title' => 'Test Post', 'post_type' => 'post'));
		//Assign the terms to the post
		wp_set_object_terms($pida, array($tida), 'category');
		wp_set_object_terms($pida, array($tidb), 'party');
		$pidb = $this->factory->post->create(array('post_title' => 'Test Czar', 'post_type' => 'czar'));
		//Assign the terms to the czar
		wp_set_object_terms($pida, array($tida), 'category');
		wp_set_object_terms($pida, array($tidb), 'party');
		//We shouldn't do anything for a regular post (and not on a taxonomy archive were post isn't primary type)
		$url = get_term_link($tida);
		$this->go_to($url);
		$url_ret = $this->breadcrumb_trail->call('maybe_add_post_type_arg', array($url));
		$this->assertSame($url, $url_ret);
		//Reaffirm when passing in the post post type
		$url = get_term_link($tida);
		$this->go_to($url);
		$url_ret = $this->breadcrumb_trail->call('maybe_add_post_type_arg', array($url, 'post'));
		$this->assertSame($url, $url_ret);
		//Again, but when not normally a post's archive
		$url = get_term_link($tidb);
		$this->go_to($url);
		$url_ret = $this->breadcrumb_trail->call('maybe_add_post_type_arg', array($url, 'post'));
		$this->assertSame($url, $url_ret); //Currently, we never add post (as documented for the function), need to determine if that is correct
		//Try without passing anything in
		$url = get_term_link($tida);
		$this->go_to($url);
		$url_ret = $this->breadcrumb_trail->call('maybe_add_post_type_arg', array($url));
		$this->assertSame($url, $url_ret);
		//Try with passing in type
		$url = get_term_link($tida);
		$this->go_to($url);
		$url_ret = $this->breadcrumb_trail->call('maybe_add_post_type_arg', array($url, 'czar'));
		$this->assertSame(add_query_arg(array('post_type' => 'czar'), $url), $url_ret);
		//Try with passing in type and taxonomy
		$url = get_term_link($tidb);
		$this->go_to($url);
		$url_ret = $this->breadcrumb_trail->call('maybe_add_post_type_arg', array($url, 'czar', 'party'));
		$this->assertSame($url, $url_ret);
	}
	function test_order()
	{
		//Going to cheat here and not fill with bcn_breadcrumb objects to make this easier to implement
		$this->breadcrumb_trail->breadcrumbs = array('car', 'gar', 'zar');
		//Order non-reversed (last breadcrumb added needs to be output first)
		$this->breadcrumb_trail->call('order', array(false));
		$this->assertSame('zar', reset($this->breadcrumb_trail->breadcrumbs));
		//Order reversed (last breadcrumb added needs to be output last)
		$this->breadcrumb_trail->call('order', array(true));
		$this->assertSame('car', reset($this->breadcrumb_trail->breadcrumbs));
	}
	function test_json_ld_loop()
	{
		//Clear any breadcrumbs that may be lingering
		$this->breadcrumb_trail->breadcrumbs = array();
		//Setup our two breadcrumbs and add them to the breadcrumbs array
		$breadcrumba = new bcn_breadcrumb("A Preposterous Post", bcn_breadcrumb::default_template_no_anchor, array('post', 'post-post', 'current-item'), NULL, 101);
		$this->breadcrumb_trail->call('add', array($breadcrumba));
		$breadcrumbb = new bcn_breadcrumb("A Test", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com/test', 102);
		$this->breadcrumb_trail->call('add', array($breadcrumbb));
		//Now test the JSON-LD loop prepairer
		$breadcrumbs = $this->breadcrumb_trail->call('json_ld_loop', array());
		//Now check our work
		$this->assertJsonStringEqualsJsonString(
			'[{"@type":"ListItem","position":1,"item":{"@id":null,"name":"A Preposterous Post"}},{"@type":"ListItem","position":2,"item":{"@id":"http://flowissues.com/test","name":"A Test"}}]',
			json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES));
	}
	function test_display_json_ld()
	{
		//Clear any breadcrumbs that may be lingering
		$this->breadcrumb_trail->breadcrumbs = array();
		//Setup our two breadcrumbs and add them to the breadcrumbs array
		$breadcrumba = new bcn_breadcrumb("A Preposterous Post", bcn_breadcrumb::default_template_no_anchor, array('post', 'post-post', 'current-item'), NULL, 101);
		$this->breadcrumb_trail->call('add', array($breadcrumba));
		$breadcrumbb = new bcn_breadcrumb("A Test", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com/test', 102);
		$this->breadcrumb_trail->call('add', array($breadcrumbb));
		//Now test the normal order mode
		$breadcrumb_string = $this->breadcrumb_trail->call('display_json_ld', array(true, false));
		//Now check our work
		$this->assertJsonStringEqualsJsonString(
			'{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"item":{"@id":"http://flowissues.com/test","name":"A Test"}},{"@type":"ListItem","position":2,"item":{"@id":null,"name":"A Preposterous Post"}}]}',
			$breadcrumb_string);
		//Now test the reverse order mode
		$breadcrumb_string = $this->breadcrumb_trail->call('display_json_ld', array(true, true));
		//Now check our work
		$this->assertJsonStringEqualsJsonString(
			'{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"item":{"@id":null,"name":"A Preposterous Post"}},{"@type":"ListItem","position":2,"item":{"@id":"http://flowissues.com/test","name":"A Test"}}]}',
			$breadcrumb_string);
	}
}
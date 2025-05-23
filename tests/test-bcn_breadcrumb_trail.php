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
	protected static $pids;
	protected static $paids;
	protected static $tids;
	public static function wpSetUpBeforeClass($factory)
	{
		//Register some types to use for various tests
		register_post_type('czar', array(
				'label' => 'Czars',
				'public' => true,
				'hierarchical' => false,
				'has_archive' => true,
				'publicly_queryable' => true,
				'taxonomies' => array('post_tag', 'category')
		)
				);
		register_post_type('bureaucrat', array(
				'label' => 'Bureaucrats',
				'public' => true,
				'hierarchical' => true,
				'has_archive' => true,
				'publicly_queryable' => true
		)
				);
		register_post_type('autocrat', array(
				'label' => 'Autocrats',
				'public' => true,
				'hierarchical' => true,
				'has_archive' => false,
				'publicly_queryable' => true
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
		register_taxonomy('family', 'autocrat', array(
				'label' => 'House Name',
				'public' => true,
				'hierarchical' => true,
		)
				);
		//Can we really do this?
		register_taxonomy('nonassociated', null, array(
				'label' => 'Non Associated',
				'public' => true,
				'hierarchical' => false,
		)
				);
		//Create some posts
		self::$pids = $factory->post->create_many(10, array('post_type' => 'post'));
		//Create some terms
		self::$tids = $factory->category->create_many(10);
		//Make some of the terms be in a hierarchy
		wp_update_term(self::$tids[7], 'category', array('parent' => self::$tids[8]));
		wp_update_term(self::$tids[8], 'category', array('parent' => self::$tids[6]));
		wp_update_term(self::$tids[9], 'category', array('parent' => self::$tids[8]));
		wp_update_term(self::$tids[5], 'category', array('parent' => self::$tids[7]));
		//Assign a category to a post
		wp_set_object_terms(self::$pids[0], array(self::$tids[5]), 'category');
		wp_set_object_terms(self::$pids[5], array(self::$tids[0]), 'category');
		wp_set_object_terms(self::$pids[7], array(self::$tids[7]), 'category');
		//Create some pages
		self::$paids = $factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => self::$paids[0], 'post_parent' => self::$paids[3]));
		wp_update_post(array('ID' => self::$paids[1], 'post_parent' => self::$paids[2]));
		wp_update_post(array('ID' => self::$paids[2], 'post_parent' => self::$paids[3], 'post_status' => 'private'));
		wp_update_post(array('ID' => self::$paids[6], 'post_parent' => self::$paids[5]));
		wp_update_post(array('ID' => self::$paids[5], 'post_parent' => self::$paids[0]));
		wp_update_post(array('ID' => self::$paids[9], 'post_parent' => self::$paids[1]));
	}
	public function set_up() {
		parent::set_up();
		$this->breadcrumb_trail = new bcn_breadcrumb_trail_DUT();
	}
	public function tear_down() {
		parent::tear_down();
	}
	function test_add() {
		$post = get_post(self::$pids[0]);
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
	function test_do_author() {
		//Some setup
		$author_id = self::factory()->user->create(array('role' => 'editor', 'user_login' => 'cooleditor1', 'display_name' => 'Cool Editor'));
		$pids = self::factory()->post->create_many(10, array('author' => $author_id));
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Now go to the author archives
		$this->go_to(get_author_posts_url($author_id));
		$this->breadcrumb_trail->call('do_author', array(get_queried_object()));
		//Ensure we have 1 breadcrumb from the do_author portion
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame('Cool Editor' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('author', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
	}
	function test_do_paged() {
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$this->breadcrumb_trail->call('do_paged', array(42));
		//Ensure we have 1 breadcrumbs from the do_paged portion
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame('42' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('paged') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
	}
	function test_do_post() {
		//Test a single post
		$popid = self::factory()->post->create(array('post_title' => 'Test Post', 'post_type' => 'post'));
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
		$papid = self::factory()->post->create(array('post_title' => 'Test Parent', 'post_type' => 'page'));
		$papid = self::factory()->post->create(array('post_title' => 'Test Child', 'post_type' => 'page', 'post_parent' => $papid));
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
		$attpida = self::factory()->post->create(array('post_title' => 'Test Attahementa', 'post_type' => 'attachment', 'post_parent' => self::$pids[0]));
		$attpidb = self::factory()->post->create(array('post_title' => 'Test Attahementb', 'post_type' => 'attachment', 'post_parent' => $papid));
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$post = get_post($attpida);
		//Call do_post on the post attachment
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(6, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame('Test Attahementa' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('post', 'post-attachment', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertSame(get_the_title(self::$pids[0]), $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(array('post', 'post-post') , $this->breadcrumb_trail->breadcrumbs[1]->get_types());
		$this->assertSame(get_term(self::$tids[5])->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(get_term_link(self::$tids[5]), $this->breadcrumb_trail->breadcrumbs[2]->get_url());
		$this->assertSame(array('taxonomy', 'category') , $this->breadcrumb_trail->breadcrumbs[2]->get_types());
		$this->assertSame(get_term(self::$tids[7])->name, $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertSame(get_term_link(self::$tids[7]), $this->breadcrumb_trail->breadcrumbs[3]->get_url());
		$this->assertSame(array('taxonomy', 'category') , $this->breadcrumb_trail->breadcrumbs[3]->get_types());
		$this->assertSame(get_term(self::$tids[8])->name, $this->breadcrumb_trail->breadcrumbs[4]->get_title());
		$this->assertSame(get_term_link(self::$tids[8]), $this->breadcrumb_trail->breadcrumbs[4]->get_url());
		$this->assertSame(array('taxonomy', 'category') , $this->breadcrumb_trail->breadcrumbs[4]->get_types());
		$this->assertSame(get_term(self::$tids[6])->name, $this->breadcrumb_trail->breadcrumbs[5]->get_title());
		$this->assertSame(get_term_link(self::$tids[6]), $this->breadcrumb_trail->breadcrumbs[5]->get_url());
		$this->assertSame(array('taxonomy', 'category') , $this->breadcrumb_trail->breadcrumbs[5]->get_types());
		
		$this->breadcrumb_trail->breadcrumbs = array();
		$post = get_post($attpidb);
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Call do_post on the post attachment
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame('Test Attahementb' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('post', 'post-attachment', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertSame('Test Child' , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(array('post', 'post-page') , $this->breadcrumb_trail->breadcrumbs[1]->get_types());
		$this->assertSame('Test Parent' , $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(array('post', 'post-page') , $this->breadcrumb_trail->breadcrumbs[2]->get_types());
	}
	/**
	 * Tests for the bcn_pick_post_term filter
	 */
	function test_bcn_show_post_private() {
		//Try with default (post_post_hierarchy_parent_first false
		$this->breadcrumb_trail->breadcrumbs = array();
		$post = get_post(self::$paids[1]);
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(2, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame(get_the_title(self::$paids[1]) , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(get_the_title(self::$paids[3]) , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		//Again with the private post in a deeper location
		$this->breadcrumb_trail->breadcrumbs = array();
		$post = get_post(self::$paids[9]);
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame(get_the_title(self::$paids[9]) , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(get_the_title(self::$paids[1]) , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_the_title(self::$paids[3]) , $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		//Add a filter that enables showing private posts in the hierarchy
		add_filter('bcn_show_post_private',
				function($display, $id) {
					return true;
				}, 2, 10);
		//Have another go
		$this->breadcrumb_trail->breadcrumbs = array();
		$post = get_post(self::$paids[1]);
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame(get_the_title(self::$paids[1]) , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(get_the_title(self::$paids[2]) , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_the_title(self::$paids[3]) , $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		//Again with the private post in a deeper location
		$this->breadcrumb_trail->breadcrumbs = array();
		$post = get_post(self::$paids[9]);
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(4, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame(get_the_title(self::$paids[9]) , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(get_the_title(self::$paids[1]) , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_the_title(self::$paids[2]) , $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(get_the_title(self::$paids[3]) , $this->breadcrumb_trail->breadcrumbs[3]->get_title());
	}
	function test_do_post_hierarchy_first() {
		wp_update_post(array('ID' => self::$pids[0], 'post_parent' => self::$pids[5]));
		//Try with default (post_post_hierarchy_parent_first false
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$post = get_post(self::$pids[0]);
		//$this->breadcrumb_trail->opt['bpost_post_hierarchy_parent_first'] = false;
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(5, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame(get_the_title(self::$pids[0]) , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('post', 'post-post', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertSame(get_term(self::$tids[5], 'category')->name, $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_term(self::$tids[7], 'category')->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(get_term(self::$tids[8], 'category')->name, $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertSame(get_term(self::$tids[6], 'category')->name, $this->breadcrumb_trail->breadcrumbs[4]->get_title());		
		//Try with default (post_post_hierarchy_parent_first true
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$this->breadcrumb_trail->opt['bpost_post_hierarchy_parent_first'] = true;
		$this->breadcrumb_trail->call('do_post', array($post));
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		$this->assertSame(get_the_title(self::$pids[0]) , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('post', 'post-post', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertSame(get_the_title(self::$pids[5]) , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(array('post', 'post-post') , $this->breadcrumb_trail->breadcrumbs[1]->get_types());
		$this->assertSame(get_term(self::$tids[0], 'category')->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());	
	}
	function test_query_var_to_taxonomy() {
		//Setup some taxonomies
		register_taxonomy('custom_tax0', 'post', array('query_var' => 'custom_tax_0'));
		register_taxonomy('custom_tax1', 'post', array('query_var' => 'custom_tax_1'));
		register_taxonomy('custom_tax1', 'post', array('query_var' => 'custom_tax_2'));
		//Check matching of an existent taxonomy
		$this->assertSame('custom_tax0', $this->breadcrumb_trail->call('query_var_to_taxonomy', array('custom_tax_0')));
		//Check return false of non-existent taxonomy
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
		$tids = self::factory()->category->create_many(10);
		$ttid1 = self::factory()->term->create(array(
			'taxonomy' => 'wptests_tax2',
			'slug' => 'ctxterm1'));
		$ttid2 = self::factory()->term->create(array(
			'taxonomy' => 'wptests_tax2',
			'slug' => 'ctxterm2',
			'parent' => $ttid1));
		//Create a test post
		$pid = self::factory()->post->create(array('post_title' => 'Test Post'));
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
		//Check matching of an existent taxonomy
		$this->assertSame('wptests_tax2', $this->breadcrumb_trail->call('determine_taxonomy'));
	}
	/**
	 * Tests for the bcn_post_terms filter
	 */
	function test_bcn_post_terms() {
		//Create our terms and post
		$tids = self::factory()->tag->create_many(10);
		$pid = self::factory()->post->create(array('post_title' => 'Test Post'));
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
			$this->assertMatchesRegularExpression('@^<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the [^"]* archives\." href="[^"]*" class="[^"]*" ><span property="name">[^<]*</span></a><meta property="position" content="[^"]*"></span>$@', $title_under_test);
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
		$tids = self::factory()->category->create_many(10);
		$pid = self::factory()->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($tids[7], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[8], 'category', array('parent' => $tids[6]));
		//Assign the terms to the post
		wp_set_object_terms($pid, $tids, 'category');
		//Call post_hierarchy
		$this->breadcrumb_trail->call('post_hierarchy', array(get_post($pid)));
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
		$this->breadcrumb_trail->call('post_hierarchy', array(get_post($pid)));
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
		$tids = self::factory()->category->create_many(10);
		$pid = self::factory()->post->create(array('post_title' => 'Test Post'));
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
	function test_do_archive_by_post_type()
	{
		$pidb = self::factory()->post->create(array('post_title' => 'Test Bureaucrat', 'post_type' => 'bureaucrat'));
		$this->breadcrumb_trail->opt['bpost_bureaucrat_archive_display'] = true;
		$this->breadcrumb_trail->opt['Hpost_bureaucrat_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_bureaucrat_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		////
		//Nominal test
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$cpt_inst = get_post($pidb);
		$this->breadcrumb_trail->opt['bcurrent_item_linked'] = false;
		$this->breadcrumb_trail->opt['bpaged_display'] = false;
		$this->breadcrumb_trail->call('do_archive_by_post_type', array('bureaucrat'));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Bureaucrats', 'bureaucrat'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-bureaucrat-archive', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertFalse($this->breadcrumb_trail->breadcrumbs[0]->is_linked());
		////
		//Nominal test with bcurrent_item_linked set true
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$cpt_inst = get_post($pidb);
		$this->breadcrumb_trail->opt['bcurrent_item_linked'] = true;
		$this->breadcrumb_trail->opt['bpaged_display'] = false;
		$this->breadcrumb_trail->call('do_archive_by_post_type', array('bureaucrat'));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Bureaucrats', 'bureaucrat'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-bureaucrat-archive', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertTrue($this->breadcrumb_trail->breadcrumbs[0]->is_linked());
		////
		//Test with forced link
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$cpt_inst = get_post($pidb);
		$this->breadcrumb_trail->call('do_archive_by_post_type', array('bureaucrat', true));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Bureaucrats', 'bureaucrat'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-bureaucrat-archive', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertTrue($this->breadcrumb_trail->breadcrumbs[0]->is_linked());
		////
		//Test with is_paged
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$cpt_inst = get_post($pidb);
		$this->breadcrumb_trail->opt['bcurrent_item_linked'] = false;
		$this->breadcrumb_trail->opt['bpaged_display'] = false;
		$this->breadcrumb_trail->call('do_archive_by_post_type', array('bureaucrat', false, true));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Bureaucrats', 'bureaucrat'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-bureaucrat-archive', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertFalse($this->breadcrumb_trail->breadcrumbs[0]->is_linked());
		////
		//Test with is_paged and bpaged_display set true
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$cpt_inst = get_post($pidb);
		$this->breadcrumb_trail->opt['bcurrent_item_linked'] = false;
		$this->breadcrumb_trail->opt['bpaged_display'] = true;
		$this->breadcrumb_trail->call('do_archive_by_post_type', array('bureaucrat', false, true));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Bureaucrats', 'bureaucrat'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-bureaucrat-archive', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertTrue($this->breadcrumb_trail->breadcrumbs[0]->is_linked());
		////
		//Test with all optional parameters set false
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$cpt_inst = get_post($pidb);
		$this->breadcrumb_trail->call('do_archive_by_post_type', array('bureaucrat', false, false, false));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Bureaucrats', 'bureaucrat'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-bureaucrat-archive') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		$this->assertFalse($this->breadcrumb_trail->breadcrumbs[0]->is_linked());
	}
	function test_maybe_do_archive_by_post_type()
	{
		$pidb = self::factory()->post->create(array('post_title' => 'Test Bureaucrat', 'post_type' => 'bureaucrat'));
		$this->breadcrumb_trail->opt['bpost_bureaucrat_archive_display'] = true;
		$this->breadcrumb_trail->opt['Hpost_bureaucrat_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_bureaucrat_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		$pida = self::factory()->post->create(array('post_title' => 'Test Autocrat', 'post_type' => 'autocrat'));
		$this->breadcrumb_trail->opt['bpost_autocrat_archive_display'] = true;
		$this->breadcrumb_trail->opt['Hpost_autocrat_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_autocrat_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		$tidd = self::factory()->term->create(array('name' => 'Test House', 'taxonomy' => 'family'));
		//Assign the terms to their posts
		wp_set_object_terms($pida, array($tidd), 'family');
		////
		//Test CPT post
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$this->breadcrumb_trail->call('maybe_do_archive_by_post_type', array('bureaucrat'));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Bureaucrats', 'bureaucrat'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-bureaucrat-archive') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		////
		//Test CPT post with archive display disabled
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$this->breadcrumb_trail->opt['bpost_bureaucrat_archive_display'] = false;
		$this->breadcrumb_trail->call('maybe_do_archive_by_post_type', array('bureaucrat'));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		////
		//Test "Post" post
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$post_inst = get_post(self::$pids[0]);
		$this->breadcrumb_trail->call('maybe_do_archive_by_post_type', array('post'));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		////
		//Test CPT post w/o archive
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$this->breadcrumb_trail->call('maybe_do_archive_by_post_type', array('autocrat'));
		//Ensure we have 1 breadcrumb
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
	}
	function test_type_archive()
	{
		$pidc = self::factory()->post->create(array('post_title' => 'Test Czar', 'post_type' => 'czar'));
		$this->breadcrumb_trail->opt['bpost_czar_archive_display'] = true;
		$this->breadcrumb_trail->opt['Hpost_czar_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_czar_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		$pidb = self::factory()->post->create(array('post_title' => 'Test Bureaucrat', 'post_type' => 'bureaucrat'));
		$this->breadcrumb_trail->opt['bpost_bureaucrat_archive_display'] = true;
		$this->breadcrumb_trail->opt['Hpost_bureaucrat_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_bureaucrat_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		$pida = self::factory()->post->create(array('post_title' => 'Test Autocrat', 'post_type' => 'autocrat'));
		$this->breadcrumb_trail->opt['bpost_autocrat_archive_display'] = true;
		$this->breadcrumb_trail->opt['Hpost_autocrat_template'] = bcn_breadcrumb::get_default_template();
		$this->breadcrumb_trail->opt['Hpost_autocrat_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
		$tidb = self::factory()->term->create(array('name' => 'Test Party', 'taxonomy' => 'party'));
		$tidc = self::factory()->term->create(array('name' => 'Test Non Associated', 'taxonomy' => 'nonassociated'));
		$tidd = self::factory()->term->create(array('name' => 'Test House', 'taxonomy' => 'family'));
		//Assign the terms to their posts
		wp_set_object_terms($pidc, array($tidb), 'party');
		wp_set_object_terms($pida, array($tidd), 'family');
		////
		//Test bad type
		////
		$this->go_to(get_permalink($pidc));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$this->breadcrumb_trail->call('type_archive', array(false, false));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Czars', 'czar'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-czar-archive') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		////
		//Test CPT post
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$cpt_inst = get_post($pidb);
		$this->breadcrumb_trail->call('type_archive', array($cpt_inst, 'bureaucrat'));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Bureaucrats', 'bureaucrat'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-bureaucrat-archive') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		////
		//Test CPT post with archive display disabled
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$this->breadcrumb_trail->opt['bpost_bureaucrat_archive_display'] = false;
		$cpt_inst = get_post($pidb);
		$this->breadcrumb_trail->call('type_archive', array($cpt_inst, 'bureaucrat'));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		////
		//Test "Post" post
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$post_inst = get_post(self::$pids[0]);
		$this->breadcrumb_trail->call('type_archive', array($post_inst, 'post'));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		////
		//Test CPT post w/o archive
		////
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$cpt_inst = get_post($pida);
		$this->breadcrumb_trail->call('type_archive', array($cpt_inst, 'autocrat'));
		//Ensure we have 1 breadcrumb
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		
		////
		//Test custom taxonomy
		////
		//"Go to" our term archive
		$this->go_to(get_term_link($tidb));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$term_inst = get_term($tidb, 'party');
		$this->breadcrumb_trail->call('type_archive', array($term_inst));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(apply_filters('post_type_archive_title', 'Czars', 'czar'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('archive', 'post-czar-archive') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
		////
		//Test with taxonomy that is unaffiliated with a post type
		////
		$this->go_to(get_term_link($tidc));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$term_inst = get_term($tidc, 'party');
		$this->breadcrumb_trail->call('type_archive', array($term_inst));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		////
		//Test with taxonomy that is affiliated primarily with a builtin type
		////
		//"Go to" our term archive
		$this->go_to(get_term_link(self::$tids[0]));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$term_inst = get_term(self::$tids[0], 'party');
		$this->breadcrumb_trail->call('type_archive', array($term_inst));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		////
		//Test with affiliaed postype with disabled archive via setting
		////
		//"Go to" our term archive
		$this->go_to(get_term_link($tidb));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$term_inst = get_term($tidb, 'party');
		$this->breadcrumb_trail->opt['bpost_czar_archive_display'] = false;
		$this->breadcrumb_trail->call('type_archive', array($term_inst));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Cleanup
		$this->breadcrumb_trail->opt['bpost_czar_archive_display'] = true;
		////
		//Test with affiliaed postype that does not have archives
		////
		//"Go to" our term archive
		$this->go_to(get_term_link($tidd));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$term_inst = get_term($tidd, 'family');
		$this->breadcrumb_trail->call('type_archive', array($term_inst));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		////
		//Test with multiple post types in the query, not sure if possible to do here
		////
		//"Go to" our term archive
		$this->go_to(add_query_arg(array('post_type' => array('bureaucrat', 'czar')), get_term_link($tidb)));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$term_inst = get_term($tidb, 'party');
		$this->breadcrumb_trail->call('type_archive', array($term_inst));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		////
		//Test with filter disabling showing the archive for the taxonomy in question
		////
		//Now, let's add a filter that selects a middle id
		add_filter('bcn_show_type_term_archive',
				function($show, $taxonomy) {
					if($taxonomy === 'party')
					{
						return false;
					}
					else
					{
						return $show;
					}
				}, 2, 10);
		//"Go to" our term archive
		$this->go_to(get_term_link($tidb));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		$term_inst = get_term($tidb, 'party');
		$this->breadcrumb_trail->call('type_archive', array($term_inst));
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
	}
	function test_do_root()
	{
		//Create some pages
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
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
		//Breadcrumb NavXT normally grabs this on instantiation, but we're late in setting the option
		$this->breadcrumb_trail->opt['apost_post_root'] = get_option('page_for_posts');
		$this->set_permalink_structure('/%category%/%postname%/');
		//Create some terms
		$tids = self::factory()->category->create_many(10);
		//Create a test post
		$pid = self::factory()->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($tids[7], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[8], 'category', array('parent' => $tids[6]));
		wp_update_term($tids[9], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[5], 'category', array('parent' => $tids[7]));
		//Assign the terms to the post
		wp_set_object_terms($pid, array($tids[5]), 'category');
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		$this->breadcrumb_trail->call('do_root', array('post',  $this->breadcrumb_trail->opt['apost_post_root']));
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
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '9' as the home page
		update_option('page_on_front', $paid[9]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		//"Go to" our post
		$this->go_to(get_permalink($paid[1]));
		$this->breadcrumb_trail->call('do_root', array('page',  $this->breadcrumb_trail->opt['apost_page_root']));
		//Ensure we have 0 breadcrumbs, root should not do anything for pages (we get to all but the home in post_parents)
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//TODO: Need more test cases here?
	}
	public function test_fill_REST_author()
	{
		//Some setup
		$author_id = self::factory()->user->create(array('role' => 'editor', 'user_login' => 'cooleditor1', 'display_name' => 'Cool Editor'));
		$pids = self::factory()->post->create_many(10, array('author' => $author_id));
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('fill_REST', array(get_user_by('id', $author_id)));
		$this->assertCount(2, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(get_option('blogname'), $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame('Cool Editor' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('author', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
	}
	public function test_fill_REST_post()
	{
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('fill_REST', array(get_post(self::$pids[0])));
		//Ensure we have 6 breadcrumb from the do_author portion
		$this->assertCount(6, $this->breadcrumb_trail->breadcrumbs);
		//Look at each breadcrumb
		$this->assertSame(get_option('blogname'), $this->breadcrumb_trail->breadcrumbs[5]->get_title());
		$this->assertSame(get_term(self::$tids[6], 'category')->name, $this->breadcrumb_trail->breadcrumbs[4]->get_title());
		$this->assertSame(get_term(self::$tids[8], 'category')->name, $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertSame(get_term(self::$tids[7], 'category')->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(get_term(self::$tids[5], 'category')->name, $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_the_title(self::$pids[0]), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
	}
	public function test_fill_REST_term()
	{
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('fill_REST', array(get_term(self::$tids[7], 'category')));
		//Ensure we have 4 breadcrumb from the do_author portion
		$this->assertCount(4, $this->breadcrumb_trail->breadcrumbs);
		//Look at each breadcrumb
		$this->assertSame(get_option('blogname'), $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertSame(get_term(self::$tids[6], 'category')->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(get_term(self::$tids[8], 'category')->name, $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_term(self::$tids[7], 'category')->name, $this->breadcrumb_trail->breadcrumbs[0]->get_title());
	}
	/**
	 * Tests for invalid items being passed into the fill_REST function
	 */
	public function test_fill_REST_invalids()
	{
		//Try passing in NULL
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('fill_REST', array(null));
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Try passing in WP_Error
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('fill_REST', array(new WP_Error('test_error', 'test error')));
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
	}
	function test_fill_author_no_root()
	{
		//Create some pages
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '9' as the home page
		update_option('page_on_front', $paid[9]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		//Some setup
		$author_id = self::factory()->user->create(array('role' => 'editor', 'user_login' => 'cooleditor1', 'display_name' => 'Cool Editor'));
		$pids = self::factory()->post->create_many(10, array('author' => $author_id));
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Now go to the author archives
		$this->go_to(get_author_posts_url($author_id));
		$this->breadcrumb_trail->call('fill');
		//Ensure we have 2 breadcrumbs
		$this->assertCount(2, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(get_option('blogname'), $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame('Cool Editor' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('author', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
	}
	function test_fill_author_root()
	{
		//Create some pages
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		//Set page '9' as the home page
		update_option('page_on_front', $paid[9]);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $paid[6]);
		//Some setup
		$author_id = self::factory()->user->create(array('role' => 'editor', 'user_login' => 'cooleditor1', 'display_name' => 'Cool Editor'));
		$pids = self::factory()->post->create_many(10, array('author' => $author_id));
		$this->breadcrumb_trail->opt['aauthor_root'] = $paid[0];
		$this->breadcrumb_trail->breadcrumbs = array();
		//Ensure we have 0 breadcrumbs
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
		//Now go to the author archives
		$this->go_to(get_author_posts_url($author_id));
		$this->breadcrumb_trail->call('fill');
		//Ensure we have 4 breadcrumbs
		$this->assertCount(4, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(get_option('blogname'), $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertSame(get_the_title($paid[3]) , $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(get_the_title($paid[0]) , $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame('Cool Editor' , $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertSame(array('author', 'current-item') , $this->breadcrumb_trail->breadcrumbs[0]->get_types());
	}
	function test_fill_blog_home()
	{
		//Create some pages
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
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
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('fill');
		//Ensure we have 1 breadcrumbs
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertSame(get_option('blogname'), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		
		/*$this->assertEquals($paid[3], $this->breadcrumb_trail->breadcrumbs[3]->get_id());
		$this->assertSame(get_the_title($paid[3]), $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertEquals($paid[0], $this->breadcrumb_trail->breadcrumbs[2]->get_id());
		$this->assertSame(get_the_title($paid[0]), $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertEquals($paid[5], $this->breadcrumb_trail->breadcrumbs[1]->get_id());
		$this->assertSame(get_the_title($paid[5]), $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertEquals($paid[6], $this->breadcrumb_trail->breadcrumbs[0]->get_id());
		$this->assertSame(get_the_title($paid[6]), $this->breadcrumb_trail->breadcrumbs[0]->get_title());*/
	}
	function test_fill_bblog_display()
	{
		//Create some pages
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
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
		//Breadcrumb NavXT normally grabs this on instantiation, but we're late in setting the option
		$this->breadcrumb_trail->opt['apost_post_root'] = get_option('page_for_posts');
		//Create some terms
		$tids = self::factory()->category->create_many(10);
		//Create a test post
		$pid = self::factory()->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($tids[7], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[8], 'category', array('parent' => $tids[6]));
		wp_update_term($tids[9], 'category', array('parent' => $tids[8]));
		wp_update_term($tids[5], 'category', array('parent' => $tids[7]));
		//Assign the terms to the post
		wp_set_object_terms($pid, array($tids[5]), 'category');
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		$this->breadcrumb_trail->breadcrumbs = array();
		//We don't want the blog breadcrumb
		$this->breadcrumb_trail->opt['bblog_display'] = true;
		$this->breadcrumb_trail->call('fill');
		//Ensure we have 9 breadcrumbs
		$this->assertCount(9, $this->breadcrumb_trail->breadcrumbs);
		$this->assertEquals($pid, $this->breadcrumb_trail->breadcrumbs[0]->get_id());
		$this->assertSame(get_the_title($pid), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertEquals($tids[5], $this->breadcrumb_trail->breadcrumbs[1]->get_id());
		$this->assertSame(get_term($tids[5])->name, $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertEquals($tids[7], $this->breadcrumb_trail->breadcrumbs[2]->get_id());
		$this->assertSame(get_term($tids[7])->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertEquals($tids[8], $this->breadcrumb_trail->breadcrumbs[3]->get_id());
		$this->assertSame(get_term($tids[8])->name, $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertEquals($tids[6], $this->breadcrumb_trail->breadcrumbs[4]->get_id());
		$this->assertSame(get_term($tids[6])->name, $this->breadcrumb_trail->breadcrumbs[4]->get_title());
		$this->assertEquals($paid[6], $this->breadcrumb_trail->breadcrumbs[5]->get_id());
		$this->assertSame(get_the_title($paid[6]), $this->breadcrumb_trail->breadcrumbs[5]->get_title());
		$this->assertEquals($paid[5], $this->breadcrumb_trail->breadcrumbs[6]->get_id());
		$this->assertSame(get_the_title($paid[5]), $this->breadcrumb_trail->breadcrumbs[6]->get_title());
		$this->assertEquals($paid[0], $this->breadcrumb_trail->breadcrumbs[7]->get_id());
		$this->assertSame(get_the_title($paid[0]), $this->breadcrumb_trail->breadcrumbs[7]->get_title());
		$this->assertSame(get_option('blogname'), $this->breadcrumb_trail->breadcrumbs[8]->get_title());
		$this->breadcrumb_trail->breadcrumbs = array();
		//We don't want the blog breadcrumb
		$this->breadcrumb_trail->opt['bblog_display'] = false;
		$this->breadcrumb_trail->call('fill');
		//Ensure we have 6 breadcrumbs
		$this->assertCount(6, $this->breadcrumb_trail->breadcrumbs);
		$this->assertEquals($pid, $this->breadcrumb_trail->breadcrumbs[0]->get_id());
		$this->assertSame(get_the_title($pid), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		$this->assertEquals($tids[5], $this->breadcrumb_trail->breadcrumbs[1]->get_id());
		$this->assertSame(get_term($tids[5])->name, $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertEquals($tids[7], $this->breadcrumb_trail->breadcrumbs[2]->get_id());
		$this->assertSame(get_term($tids[7])->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertEquals($tids[8], $this->breadcrumb_trail->breadcrumbs[3]->get_id());
		$this->assertSame(get_term($tids[8])->name, $this->breadcrumb_trail->breadcrumbs[3]->get_title());
		$this->assertEquals($tids[6], $this->breadcrumb_trail->breadcrumbs[4]->get_id());
		$this->assertSame(get_term($tids[6])->name, $this->breadcrumb_trail->breadcrumbs[4]->get_title());
		$this->assertSame(get_option('blogname'), $this->breadcrumb_trail->breadcrumbs[5]->get_title());
	}
/*	function test_do_root_search()
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
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
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
		$pid = self::factory()->post->create(array('post_title' => 'Test Post', 'post_type' => 'bcn_testa'));
		
		//"Go to" our search, non-post type restricted
		$this->go_to(get_search_link('test'));
		$this->breadcrumb_trail->call('do_root', array(get_queried_object()));
		//Ensure we have 0 breadcrumbs from the do_root portion
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
	}
*/
	function test_do_root_cpt()
	{
		//Create some pages
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
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
		$pid = self::factory()->post->create(array('post_title' => 'Test Post', 'post_type' => 'bcn_testa'));
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		$this->breadcrumb_trail->call('do_root', array('bcn_testa',  $this->breadcrumb_trail->opt['apost_bcn_testa_root']));
		//Ensure we have 2 breadcrumbs
		$this->assertCount(2, $this->breadcrumb_trail->breadcrumbs);
		//Check to ensure we got the breadcrumbs we wanted
		$this->assertEquals($paid[2], $this->breadcrumb_trail->breadcrumbs[1]->get_id());
		$this->assertSame(get_the_title($paid[2]), $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertEquals($paid[1], $this->breadcrumb_trail->breadcrumbs[0]->get_id());
		$this->assertSame(get_the_title($paid[1]), $this->breadcrumb_trail->breadcrumbs[0]->get_title());
	}
	function test_term_parents()
	{
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('term_parents', array(get_term(self::$tids[7], 'category')));
		//Ensure we have 3 breadcrumbs
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		//Look at each breadcrumb
		$this->assertSame(get_term(self::$tids[6], 'category')->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(get_term(self::$tids[8], 'category')->name, $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_term(self::$tids[7], 'category')->name, $this->breadcrumb_trail->breadcrumbs[0]->get_title());
	}
	function test_do_archive_by_term()
	{
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('do_archive_by_term', array(get_term(self::$tids[7], 'category')));
		//Ensure we have 3 breadcrumbs
		$this->assertCount(3, $this->breadcrumb_trail->breadcrumbs);
		//Look at each breadcrumb
		$this->assertSame(get_term(self::$tids[6], 'category')->name, $this->breadcrumb_trail->breadcrumbs[2]->get_title());
		$this->assertSame(get_term(self::$tids[8], 'category')->name, $this->breadcrumb_trail->breadcrumbs[1]->get_title());
		$this->assertSame(get_term(self::$tids[7], 'category')->name, $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		
		//A test with a term w/o parents
		$this->breadcrumb_trail->breadcrumbs = array();
		$this->breadcrumb_trail->call('do_archive_by_term', array(get_term(self::$tids[6], 'category')));
		//Ensure we have 1 breadcrumb
		$this->assertCount(1, $this->breadcrumb_trail->breadcrumbs);
		//Look at each breadcrumb
		$this->assertSame(get_term(self::$tids[6], 'category')->name, $this->breadcrumb_trail->breadcrumbs[0]->get_title());
		
		//TODO add tests for the is_paged parameter
	}
	/**
	 * Test for when the CPT root setting is a non-integer see #148
	 */
	function test_do_root_cpt_null_root()
	{
		//Create some pages
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
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
		$pid = self::factory()->post->create(array('post_title' => 'Test Post', 'post_type' => 'bcn_testa'));
		//"Go to" our post
		$this->go_to(get_permalink($pid));
		$this->breadcrumb_trail->call('do_root', array('bcn_testa',  $this->breadcrumb_trail->opt['apost_bcn_testa_root']));
		//Ensure we have 0 breadcrumbs (no root)
		$this->assertCount(0, $this->breadcrumb_trail->breadcrumbs);
	}
	function test_is_builtin()
	{
		//Try some built in types
		$this->assertTrue($this->breadcrumb_trail->call('is_builtin', array('post')));
		$this->assertTrue($this->breadcrumb_trail->call('is_builtin', array('page')));
		$this->assertTrue($this->breadcrumb_trail->call('is_builtin', array('attachment')));
		//And now our CPT
		$this->assertFalse($this->breadcrumb_trail->call('is_builtin', array('czar')));
		$this->assertFalse($this->breadcrumb_trail->call('is_builtin', array('bureaucrat')));
	}
	function test_treat_as_root_page()
	{
		//TODO complete all cases
		$paid = self::factory()->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $paid[0], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[1], 'post_parent' => $paid[2]));
		wp_update_post(array('ID' => $paid[2], 'post_parent' => $paid[3]));
		wp_update_post(array('ID' => $paid[6], 'post_parent' => $paid[5]));
		wp_update_post(array('ID' => $paid[5], 'post_parent' => $paid[0]));
		$pidc = self::factory()->post->create(array('post_title' => 'Test Czar', 'post_type' => 'czar'));
		$pidb = self::factory()->post->create(array('post_title' => 'Test Bureaucrat', 'post_type' => 'bureaucrat'));
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
		$tida = self::factory()->term->create(array('name' => 'Test Category', 'taxonomy' => 'category'));
		$tidb = self::factory()->term->create(array('name' => 'Test Party', 'taxonomy' => 'party'));
		$pida = self::factory()->post->create(array('post_title' => 'Test Post', 'post_type' => 'post'));
		//Assign the terms to the post
		wp_set_object_terms($pida, array($tida), 'category');
		wp_set_object_terms($pida, array($tidb), 'party');
		$pidb = self::factory()->post->create(array('post_title' => 'Test Czar', 'post_type' => 'czar'));
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
		$breadcrumba = new bcn_breadcrumb("A Preposterous Post", bcn_breadcrumb::default_template_no_anchor, array('post', 'post-post', 'current-item'), 'http://flowissues.com/test/a-prepost-post', 101);
		$this->breadcrumb_trail->call('add', array($breadcrumba));
		$breadcrumbb = new bcn_breadcrumb("A Test", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com/test', 102, true);
		$this->breadcrumb_trail->call('add', array($breadcrumbb));
		//Now test the JSON-LD loop prepairer
		$breadcrumbs = $this->breadcrumb_trail->call('json_ld_loop', array());
		//Now check our work
		$this->assertJsonStringEqualsJsonString(
			'[{"@type":"ListItem","position":1,"item":{"@id":"http://flowissues.com/test/a-prepost-post","name":"A Preposterous Post"}},{"@type":"ListItem","position":2,"item":{"@id":"http://flowissues.com/test","name":"A Test"}}]',
			json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES));
		//Now test the JSON-LD loop in reverse
		$breadcrumbs = $this->breadcrumb_trail->call('json_ld_loop', array(true));
		//Now check our work
		$this->assertJsonStringEqualsJsonString(
				'[{"@type":"ListItem","position":2,"item":{"@id":"http://flowissues.com/test/a-prepost-post","name":"A Preposterous Post"}},{"@type":"ListItem","position":1,"item":{"@id":"http://flowissues.com/test","name":"A Test"}}]',
				json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES));
	}
	function test_display_json_ld()
	{
		//Clear any breadcrumbs that may be lingering
		$this->breadcrumb_trail->breadcrumbs = array();
		//Setup our two breadcrumbs and add them to the breadcrumbs array
		$breadcrumba = new bcn_breadcrumb("A Preposterous Post", bcn_breadcrumb::default_template_no_anchor, array('post', 'post-post', 'current-item'), 'http://flowissues.com/test/a-prepost-post', 101);
		$this->breadcrumb_trail->call('add', array($breadcrumba));
		$breadcrumbb = new bcn_breadcrumb("A Test", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com/test', 102, true);
		$this->breadcrumb_trail->call('add', array($breadcrumbb));
		//Now test the normal order mode
		$breadcrumb_string = $this->breadcrumb_trail->call('display_json_ld', array(false));
		//Now check our work
		$this->assertJsonStringEqualsJsonString(
			'{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"item":{"@id":"http://flowissues.com/test","name":"A Test"}},{"@type":"ListItem","position":2,"item":{"@id":"http://flowissues.com/test/a-prepost-post","name":"A Preposterous Post"}}]}',
			json_encode($breadcrumb_string, JSON_UNESCAPED_SLASHES));
		//Now test the reverse order mode
		$breadcrumb_string = $this->breadcrumb_trail->call('display_json_ld', array(true));
		//Now check our work
		$this->assertJsonStringEqualsJsonString(
			'{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":2,"item":{"@id":"http://flowissues.com/test/a-prepost-post","name":"A Preposterous Post"}},{"@type":"ListItem","position":1,"item":{"@id":"http://flowissues.com/test","name":"A Test"}}]}',
			json_encode($breadcrumb_string, JSON_UNESCAPED_SLASHES));
	}
	function test_display()
	{
		//Clear any breadcrumbs that may be lingering
		$this->breadcrumb_trail->breadcrumbs = array();
		//Add some breadcrumbs to the trail
		//Setup our three breadcrumbs and add them to the breadcrumbs array
		$breadcrumba = new bcn_breadcrumb("A Preposterous Post", bcn_breadcrumb::default_template_no_anchor, array('post', 'post-post', 'current-item'), 'http://flowissues.com/test/a-prepost-post', 101);
		$this->breadcrumb_trail->call('add', array($breadcrumba));
		$breadcrumbb = new bcn_breadcrumb("A Test", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com/test', 102, true);
		$this->breadcrumb_trail->call('add', array($breadcrumbb));
		$breadcrumbc = new bcn_breadcrumb("Home", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com', 103, true);
		$this->breadcrumb_trail->call('add', array($breadcrumbc));
		//Check the resulting trail
		$breadcrumb_string = $this->breadcrumb_trail->call('display', array(false));
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test</span><meta property="url" content="http://flowissues.com/test"><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">A Preposterous Post</span><meta property="url" content="http://flowissues.com/test/a-prepost-post"><meta property="position" content="3"></span>', $breadcrumb_string);
		//Now try reversing
		$breadcrumb_string = $this->breadcrumb_trail->call('display', array(false, true));
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">A Preposterous Post</span><meta property="url" content="http://flowissues.com/test/a-prepost-post"><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test</span><meta property="url" content="http://flowissues.com/test"><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span>', $breadcrumb_string);
		//Now remove a breadcrumb
		unset($this->breadcrumb_trail->breadcrumbs[1]);
		//Check that we still have separators where we expect them
		$breadcrumb_string2 = $this->breadcrumb_trail->call('display', array(false));
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">A Preposterous Post</span><meta property="url" content="http://flowissues.com/test/a-prepost-post"><meta property="position" content="2"></span>', $breadcrumb_string2);
	}
	function test_display_loop()
	{
		//Clear any breadcrumbs that may be lingering
		$this->breadcrumb_trail->breadcrumbs = array();
		//Add some breadcrumbs to the trail
		//Setup our three breadcrumbs and add them to the breadcrumbs array
		$breadcrumba = new bcn_breadcrumb("A Preposterous Post", bcn_breadcrumb::default_template_no_anchor, array('post', 'post-post', 'current-item'), 'http://flowissues.com/test/a-prepost-post', 101);
		$this->breadcrumb_trail->call('add', array($breadcrumba));
		$breadcrumbb = new bcn_breadcrumb("A Test", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com/test', 102, true);
		$this->breadcrumb_trail->call('add', array($breadcrumbb));
		$breadcrumbc = new bcn_breadcrumb("Home", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com', 104, true);
		$this->breadcrumb_trail->call('add', array($breadcrumbc));
		$this->breadcrumb_trail->call('order', array(false));
		//Test without a filter
		$breadcrumb_string = $this->breadcrumb_trail->call('display_loop', array($this->breadcrumb_trail->breadcrumbs, false, false, '<li%3$s>%1$s</li>', '<ul>%1$s</ul>', ' &gt; '));
		$this->assertSame('<li class="post post-post"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span></li><li class="post post-post"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test</span><meta property="url" content="http://flowissues.com/test"><meta property="position" content="2"></span></li><li class="post post-post current-item"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">A Preposterous Post</span><meta property="url" content="http://flowissues.com/test/a-prepost-post"><meta property="position" content="3"></span></li>', $breadcrumb_string);
		//Add our filter
		add_filter('bcn_display_attribute_array', function($attrib_array, $types, $id){
			$attrib_array['class'][] = 'dynamico';
			$attrib_array['arria-current'][0] = 'page';
			return $attrib_array;
		}, 10, 3);
		//Test with filtered result
		$breadcrumb_string = $this->breadcrumb_trail->call('display_loop', array($this->breadcrumb_trail->breadcrumbs, false, false, '<li%3$s>%1$s</li>', '<li><ul>%1$s</ul></li>', ' &gt; '));
		$this->assertSame('<li class="post post-post dynamico" arria-current="page"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span></li><li class="post post-post dynamico" arria-current="page"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test</span><meta property="url" content="http://flowissues.com/test"><meta property="position" content="2"></span></li><li class="post post-post current-item dynamico" arria-current="page"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">A Preposterous Post</span><meta property="url" content="http://flowissues.com/test/a-prepost-post"><meta property="position" content="3"></span></li>', $breadcrumb_string);
		//Test with filtered start
		add_filter('bcn_before_loop', function($breadcrumbs){
			//Remove the first breadcrumb in the trail
			unset($breadcrumbs[0]);
			return $breadcrumbs;
		});
		$breadcrumb_string = $this->breadcrumb_trail->call('display_loop', array($this->breadcrumb_trail->breadcrumbs, false, false, '<li%3$s>%1$s</li>', '<ul>%1$s</ul>', ' &gt; '));
		$this->assertSame('<li class="post post-post dynamico" arria-current="page"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span></li><li class="post post-post dynamico" arria-current="page"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test</span><meta property="url" content="http://flowissues.com/test"><meta property="position" content="2"></span></li>', $breadcrumb_string);
		//Test the bcn_display_separator filter
		add_filter('bcn_display_separator', function($separator, $position, $last_position, $depth){
			if($position >= $last_position)
			{
				return $separator;
			}
			return ' &lt; ';
		}, 10, 4);
		$breadcrumb_string = $this->breadcrumb_trail->call('display_loop', array($this->breadcrumb_trail->breadcrumbs, false, false, '%1$s%2$s', '<span>%1$s</span>%2$s', ' &gt; '));
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span> &lt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test</span><meta property="url" content="http://flowissues.com/test"><meta property="position" content="2"></span>', $breadcrumb_string);
	}
	/**
	 * display_loop second dimension testing, gets its own test
	 */
	function test_display_loop_2nddim()
	{
		//Clear any breadcrumbs that may be lingering
		$this->breadcrumb_trail->breadcrumbs = array();
		//Add some breadcrumbs to the trail
		//Setup our three breadcrumbs and add them to the breadcrumbs array
		$breadcrumba = new bcn_breadcrumb("A Preposterous Post", bcn_breadcrumb::default_template_no_anchor, array('post', 'post-post', 'current-item'), 'http://flowissues.com/test/a-prepost-post', 101);
		$this->breadcrumb_trail->call('add', array($breadcrumba));
		$breadcrumbb1 = new bcn_breadcrumb("A Test Sibbling 1", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com/test1', 102, true);
		$breadcrumbb2 = new bcn_breadcrumb("A Test Sibbling 2", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com/test2', 103, true);
		$this->breadcrumb_trail->breadcrumbs[] = array($breadcrumbb1, $breadcrumbb2);
		$breadcrumbc = new bcn_breadcrumb("Home", bcn_breadcrumb::get_default_template(), array('post', 'post-post'), 'http://flowissues.com', 104, true);
		$this->breadcrumb_trail->call('add', array($breadcrumbc));
		$this->breadcrumb_trail->call('order', array(false));
		//Test using list elements/no separators
		$breadcrumb_string = $this->breadcrumb_trail->call('display_loop', array($this->breadcrumb_trail->breadcrumbs, false, false, '<li%3$s>%1$s</li>', '<li><ul>%1$s</ul></li>', ' &gt; '));
		$this->assertSame('<li class="post post-post"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span></li><li><ul><li class="post post-post"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test Sibbling 1</span><meta property="url" content="http://flowissues.com/test1"><meta property="position" content="1"></span></li><li class="post post-post"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test Sibbling 2</span><meta property="url" content="http://flowissues.com/test2"><meta property="position" content="2"></span></li></ul></li><li class="post post-post current-item"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">A Preposterous Post</span><meta property="url" content="http://flowissues.com/test/a-prepost-post"><meta property="position" content="3"></span></li>', $breadcrumb_string);
		$breadcrumb_string = $this->breadcrumb_trail->call('display_loop', array($this->breadcrumb_trail->breadcrumbs, false, false, '%1$s%2$s', '<span>%1$s</span>%2$s', ' &gt; '));
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span> &gt; <span><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test Sibbling 1</span><meta property="url" content="http://flowissues.com/test1"><meta property="position" content="1"></span>, <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test Sibbling 2</span><meta property="url" content="http://flowissues.com/test2"><meta property="position" content="2"></span></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">A Preposterous Post</span><meta property="url" content="http://flowissues.com/test/a-prepost-post"><meta property="position" content="3"></span>', $breadcrumb_string);
		//Test the bcn_display_separator filter
		add_filter('bcn_display_separator', function($separator, $position, $last_position, $depth){
			if($depth > 1)
			{
				if($position == $last_position - 1)
				{
					return ' &amp; ';
				}
			}
			if($position < $last_position)
			{
				return ' &lt; ';
			}
			return $separator;
		}, 10, 4);
		$breadcrumb_string = $this->breadcrumb_trail->call('display_loop', array($this->breadcrumb_trail->breadcrumbs, false, false, '%1$s%2$s', '<span>%1$s</span>%2$s', ' &gt; '));
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">Home</span><meta property="url" content="http://flowissues.com"><meta property="position" content="1"></span> &lt; <span><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test Sibbling 1</span><meta property="url" content="http://flowissues.com/test1"><meta property="position" content="1"></span> &amp; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post">A Test Sibbling 2</span><meta property="url" content="http://flowissues.com/test2"><meta property="position" content="2"></span></span> &lt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">A Preposterous Post</span><meta property="url" content="http://flowissues.com/test/a-prepost-post"><meta property="position" content="3"></span>', $breadcrumb_string);
	}
}

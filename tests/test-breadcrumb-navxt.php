<?php
/**
 * This file contains tests for the bcn_breadcrumb class
 *
 * @group breadcrumb-navxt
 * @group bcn_core
 */
if(class_exists('bcn_breadcrumb_trail'))
{
	class breadcrumb_navxt_DUT extends breadcrumb_navxt{
		function __construct(bcn_breadcrumb_trail $breadcrumb_trail) {
			parent::__construct($breadcrumb_trail);
		}
		//Super evil caller function to get around our private and protected methods in the parent class
		function call($function, $args = array()) {
			return call_user_func_array(array($this, $function), $args);
		}
		function get_opt($key)
		{
			return $this->breadcrumb_trail->opt[$key];
		}
	}
}
class BreadcrumbNavXTTest extends WP_UnitTestCase {
	public $pages;
	public $home;
	public $blog;
	public $terms;
	public $posts;
	public $breadcrumb_navxt;
	function set_up() {
		parent::set_up();
		$this->breadcrumb_navxt = new breadcrumb_navxt_DUT(new bcn_breadcrumb_trail());
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
		//Create some pages
		$this->pages = self::factory()->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $this->pages[0], 'post_parent' => $this->pages[3]));
		wp_update_post(array('ID' => $this->pages[1], 'post_parent' => $this->pages[2]));
		wp_update_post(array('ID' => $this->pages[2], 'post_parent' => $this->pages[3]));
		wp_update_post(array('ID' => $this->pages[6], 'post_parent' => $this->pages[5]));
		wp_update_post(array('ID' => $this->pages[5], 'post_parent' => $this->pages[0]));
		$this->home = self::factory()->post->create(array('post_title' => 'Home', 'post_type' => 'page'));
		$this->blog = self::factory()->post->create(array('post_title' => 'Articles', 'post_type' => 'page'));
		//Set page '3' as the home page
		update_option('page_on_front', $this->home);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $this->blog);
		//Create some terms
		$this->terms = self::factory()->category->create_many(10);
		//Create a test post
		$this->posts[] = self::factory()->post->create(array('post_title' => 'Test Post'));
		//Make some of the terms be in a hierarchy
		wp_update_term($this->terms[7], 'category', array('parent' => $this->terms[8]));
		wp_update_term($this->terms[8], 'category', array('parent' => $this->terms[6]));
		wp_update_term($this->terms[9], 'category', array('parent' => $this->terms[8]));
		wp_update_term($this->terms[5], 'category', array('parent' => $this->terms[7]));
		//Assign the terms to the post
		wp_set_object_terms($this->posts[0], array($this->terms[5]), 'category');
		add_filter('default_option_bcn_options', 
			function($default, $opts) {
				$opts = array();
				$opts['apost_post_root'] = get_option('page_for_posts');
				return $opts;
		}, 2, 10);
	}
	public function tear_down() {
		parent::tear_down();
	}
	function test_get_settings_base() {
		//Setup network site settings
		update_site_option('bcn_options', array(
				'bmainsite_display' => true,
				'S404_title' => 'Oopse'
		));
		//Setup single site settings
		update_option('bcn_options', array(
				'bmainsite_display' => false,
				'bcurrent_item_linked' => true,
				'Eauthor_name' => 'display_name'
		));
		//Test default (use local)
		$this->breadcrumb_navxt->call('init');
		$this->setExpectedIncorrectUsage('WP_Block_Type_Registry::register');
		//Check for the opts for expected values
		$this->assertSame($this->breadcrumb_navxt->get_opt('bmainsite_display'), false);
		$this->assertSame($this->breadcrumb_navxt->get_opt('bcurrent_item_linked'), true);
		$this->assertSame($this->breadcrumb_navxt->get_opt('Eauthor_name'), 'display_name');
		$this->assertSame($this->breadcrumb_navxt->get_opt('S404_title'), '404');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bhome_display'), true);
		//These are only valid in a multisite test enviornment
		//Test use network
/*		define('BCN_SETTINGS_USE_NETWORK', true);
		$this->breadcrumb_navxt->call('init');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bmainsite_display'), true);
		$this->assertSame($this->breadcrumb_navxt->get_opt('bcurrent_item_linked'), false);
		$this->assertSame($this->breadcrumb_navxt->get_opt('Eauthor_name'), 'display_name');
		$this->assertSame($this->breadcrumb_navxt->get_opt('S404_title'), 'Oopse');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bhome_display'), true);
		//Test prefer local
		define('BCN_SETTINGS_FAVOR_LOCAL', true);
		$this->breadcrumb_navxt->call('init');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bmainsite_display'), false);
		$this->assertSame($this->breadcrumb_navxt->get_opt('bcurrent_item_linked'), true);
		$this->assertSame($this->breadcrumb_navxt->get_opt('Eauthor_name'), 'display_name');
		$this->assertSame($this->breadcrumb_navxt->get_opt('S404_title'), 'Oopse');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bhome_display'), true);
		//Test prefer network
		define('BCN_SETTINGS_FAVOR_NETWORK', true);
		$this->breadcrumb_navxt->call('init');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bmainsite_display'), true);
		$this->assertSame($this->breadcrumb_navxt->get_opt('bcurrent_item_linked'), true);
		$this->assertSame($this->breadcrumb_navxt->get_opt('Eauthor_name'), 'display_name');
		$this->assertSame($this->breadcrumb_navxt->get_opt('S404_title'), 'Oopse');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bhome_display'), true);*/
	}
	function test_get_settings_use_local() {
		//Setup network site settings
		update_site_option('bcn_options', array(
				'bmainsite_display' => true,
				'S404_title' => 'Oopse'
		));
		//Setup single site settings
		update_option('bcn_options', array(
				'bmainsite_display' => false,
				'bcurrent_item_linked' => true,
				'Eauthor_name' => 'display_name'
		));
		//Test use local explicit
		define('BCN_SETTINGS_USE_LOCAL', true);
		$this->breadcrumb_navxt->call('init');
		$this->setExpectedIncorrectUsage('WP_Block_Type_Registry::register');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bmainsite_display'), false);
		$this->assertSame($this->breadcrumb_navxt->get_opt('bcurrent_item_linked'), true);
		$this->assertSame($this->breadcrumb_navxt->get_opt('Eauthor_name'), 'display_name');
		$this->assertSame($this->breadcrumb_navxt->get_opt('S404_title'), '404');
		$this->assertSame($this->breadcrumb_navxt->get_opt('bhome_display'), true);
	}
	function test_bcn_display_cache() {
		//Create a test post
		$pid1 = self::factory()->post->create(array('post_title' => 'Test Post 1', 'post_type' => 'post'));
		//Create another test post
		$pidb = self::factory()->post->create(array('post_title' => 'Test Post B', 'post_type' => 'post'));
		//"Go to" our post
		$this->go_to(get_permalink($pid1));
		//Check the breadcrumb trail
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Test Blog." href="'
			. get_home_url() .
			'" class="home" ><span property="name">Test Blog</span></a><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Articles." href="'
			. get_permalink($this->blog) .
			'" class="post-root post post-post" ><span property="name">Articles</span></a><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the Uncategorized Category archives." href="'
			. get_term_link(1) .
			'" class="taxonomy category" ><span property="name">Uncategorized</span></a><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">Test Post 1</span><meta property="url" content="'
			. get_permalink($pid1) . '"><meta property="position" content="4"></span>',
			bcn_display(true, true, false, true));
		//"Go to" post B
		$this->go_to(get_permalink($pidb));
		//Check the breadcrumb trail, should be the same as before with caching
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Test Blog." href="'
			. get_home_url() .
			'" class="home" ><span property="name">Test Blog</span></a><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Articles." href="'
			. get_permalink($this->blog) .
			'" class="post-root post post-post" ><span property="name">Articles</span></a><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the Uncategorized Category archives." href="'
			. get_term_link(1) .
			'" class="taxonomy category" ><span property="name">Uncategorized</span></a><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">Test Post 1</span><meta property="url" content="'
			. get_permalink($pid1) . '"><meta property="position" content="4"></span>',
			bcn_display(true, true, false, false));
		//Check the breadcrumb trail again without caching
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Test Blog." href="'
			. get_home_url() .
			'" class="home" ><span property="name">Test Blog</span></a><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Articles." href="'
			. get_permalink($this->blog) .
			'" class="post-root post post-post" ><span property="name">Articles</span></a><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the Uncategorized Category archives." href="'
			. get_term_link(1) .
			'" class="taxonomy category" ><span property="name">Uncategorized</span></a><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">Test Post B</span><meta property="url" content="'
			. get_permalink($pidb) . '"><meta property="position" content="4"></span>',
			bcn_display(true, true, false, true));
	}
	function test_bcn_display()
	{
		$this->go_to(get_permalink($this->posts[0]));
		//Now test the normal order mode
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name" class="home">' . get_option('blogname')
			. '</span><meta property="url" content="' . get_home_url() . '"><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post-root post post-post">' . get_the_title($this->blog)
			. '</span><meta property="url" content="' . get_permalink($this->blog) . '"><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[6])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[6]) . '"><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[8])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[8]) . '"><meta property="position" content="4"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[7])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[7]) . '"><meta property="position" content="5"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[5])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[5]) . '"><meta property="position" content="6"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">' . get_the_title($this->posts[0])
			. '</span><meta property="url" content="' . get_the_permalink($this->posts[0]) . '"><meta property="position" content="7"></span>'
			, bcn_display(true, false, false, true));
		//Now in reverse order
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">' . get_the_title($this->posts[0])
			. '</span><meta property="url" content="' . get_the_permalink($this->posts[0]) . '"><meta property="position" content="7"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[5])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[5]) . '"><meta property="position" content="6"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[7])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[7]) . '"><meta property="position" content="5"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[8])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[8]) . '"><meta property="position" content="4"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[6])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[6]) . '"><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post-root post post-post">' . get_the_title($this->blog)
			. '</span><meta property="url" content="' . get_permalink($this->blog). '"><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="home">' . get_option('blogname')
			. '</span><meta property="url" content="' . get_home_url() . '"><meta property="position" content="1"></span>'
			, bcn_display(true, false, true, true));
		//Now linked
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to ' . get_option('blogname') . '." href="' . get_home_url() . '" class="home" ><span property="name">' . get_option('blogname')
			. '</span></a><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to ' . get_the_title($this->blog) . '." href="' . get_permalink($this->blog) . '" class="post-root post post-post" ><span property="name">' . get_the_title($this->blog)
			. '</span></a><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[6])->name . ' Category archives." href="' . get_term_link($this->terms[6]) . '" class="taxonomy category" ><span property="name">' . get_term($this->terms[6])->name
			. '</span></a><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[8])->name . ' Category archives." href="' . get_term_link($this->terms[8]) . '" class="taxonomy category" ><span property="name">' . get_term($this->terms[8])->name
			. '</span></a><meta property="position" content="4"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[7])->name . ' Category archives." href="' . get_term_link($this->terms[7]) . '" class="taxonomy category" ><span property="name">' . get_term($this->terms[7])->name
			. '</span></a><meta property="position" content="5"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[5])->name . ' Category archives." href="' . get_term_link($this->terms[5]) . '" class="taxonomy category" ><span property="name">' . get_term($this->terms[5])->name
			. '</span></a><meta property="position" content="6"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">' . get_the_title($this->posts[0])
			. '</span><meta property="url" content="' . get_the_permalink($this->posts[0]) . '"><meta property="position" content="7"></span>'
			, bcn_display(true, true, false, true));
	}
	function test_bcn_display_list()
	{
		$this->go_to(get_permalink($this->posts[0]));
		//Now test the normal order mode
		$this->assertSame('<li class="home"><span property="itemListElement" typeof="ListItem"><span property="name" class="home">' . get_option('blogname')
			. '</span><meta property="url" content="' . get_home_url() . '"><meta property="position" content="1"></span></li>' . "\n" . '<li class="post-root post post-post"><span property="itemListElement" typeof="ListItem"><span property="name" class="post-root post post-post">' . get_the_title($this->blog)
			. '</span><meta property="url" content="' . get_permalink($this->blog) . '"><meta property="position" content="2"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[6])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[6]) . '"><meta property="position" content="3"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[8])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[8]) . '"><meta property="position" content="4"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[7])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[7]) . '"><meta property="position" content="5"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[5])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[5]). '"><meta property="position" content="6"></span></li>' . "\n" . '<li class="post post-post current-item"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">' . get_the_title($this->posts[0])
			. '</span><meta property="url" content="' . get_the_permalink($this->posts[0]) . '"><meta property="position" content="7"></span></li>' . "\n"
			, bcn_display_list(true, false, false, true));
		//Now in reverse order
		$this->assertSame('<li class="post post-post current-item"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">' . get_the_title($this->posts[0])
			. '</span><meta property="url" content="' . get_the_permalink($this->posts[0]) . '"><meta property="position" content="7"></span></li>'. "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[5])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[5]). '"><meta property="position" content="6"></span></li>'. "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[7])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[7]) . '"><meta property="position" content="5"></span></li>'. "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[8])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[8]) . '"><meta property="position" content="4"></span></li>'. "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name" class="taxonomy category">' . get_term($this->terms[6])->name
			. '</span><meta property="url" content="' . get_term_link($this->terms[6]) . '"><meta property="position" content="3"></span></li>'. "\n" . '<li class="post-root post post-post"><span property="itemListElement" typeof="ListItem"><span property="name" class="post-root post post-post">' . get_the_title($this->blog)
			. '</span><meta property="url" content="' . get_permalink($this->blog) . '"><meta property="position" content="2"></span></li>' . "\n" . '<li class="home"><span property="itemListElement" typeof="ListItem"><span property="name" class="home">' . get_option('blogname')
			. '</span><meta property="url" content="' . get_home_url() . '"><meta property="position" content="1"></span></li>'. "\n"
			, bcn_display_list(true, false, true, true));
		//Now linked
		$this->assertSame('<li class="home"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to ' . get_option('blogname') . '." href="' . get_home_url() . '" class="home" ><span property="name">' . get_option('blogname')
			. '</span></a><meta property="position" content="1"></span></li>' . "\n" . '<li class="post-root post post-post"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to ' . get_the_title($this->blog) . '." href="' . get_permalink($this->blog) . '" class="post-root post post-post" ><span property="name">' . get_the_title($this->blog)
			. '</span></a><meta property="position" content="2"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[6])->name . ' Category archives." href="' . get_term_link($this->terms[6]) . '" class="taxonomy category" ><span property="name">' . get_term($this->terms[6])->name
			. '</span></a><meta property="position" content="3"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[8])->name . ' Category archives." href="' . get_term_link($this->terms[8]) . '" class="taxonomy category" ><span property="name">' . get_term($this->terms[8])->name
			. '</span></a><meta property="position" content="4"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[7])->name . ' Category archives." href="' . get_term_link($this->terms[7]) . '" class="taxonomy category" ><span property="name">' . get_term($this->terms[7])->name
			. '</span></a><meta property="position" content="5"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[5])->name . ' Category archives." href="' . get_term_link($this->terms[5]) . '" class="taxonomy category" ><span property="name">' . get_term($this->terms[5])->name
			. '</span></a><meta property="position" content="6"></span></li>' . "\n" . '<li class="post post-post current-item"><span property="itemListElement" typeof="ListItem"><span property="name" class="post post-post current-item">' . get_the_title($this->posts[0])
			. '</span><meta property="url" content="' . get_the_permalink($this->posts[0]) . '"><meta property="position" content="7"></span></li>' . "\n"
			, bcn_display_list(true, true, false, true));
	}
	function test_bcn_display_json_ld()
	{
		$this->go_to(get_permalink($this->posts[0]));
		//Now test the normal order mode
		$this->assertJsonStringEqualsJsonString(
			'{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"item":{"@id":"' . get_home_url() . '","name":"' . get_option('blogname')
				. '"}},{"@type":"ListItem","position":2,"item":{"@id":"' . get_permalink($this->blog) . '","name":"' . get_the_title($this->blog)
				. '"}},{"@type":"ListItem","position":3,"item":{"@id":"' . get_term_link($this->terms[6]) . '","name":"' . get_term($this->terms[6])->name
				. '"}},{"@type":"ListItem","position":4,"item":{"@id":"' . get_term_link($this->terms[8]) . '","name":"' . get_term($this->terms[8])->name
				. '"}},{"@type":"ListItem","position":5,"item":{"@id":"' . get_term_link($this->terms[7]) . '","name":"' . get_term($this->terms[7])->name
				. '"}},{"@type":"ListItem","position":6,"item":{"@id":"' . get_term_link($this->terms[5]) . '","name":"' . get_term($this->terms[5])->name
				. '"}},{"@type":"ListItem","position":7,"item":{"@id":"' . get_permalink($this->posts[0]) . '","name":"' . get_the_title($this->posts[0])
				. '"}}]}',
			bcn_display_json_ld(true, false, true));
		//Now test the reverse order mode
		$this->assertJsonStringEqualsJsonString(
				'{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":7,"item":{"@id":"' . get_permalink($this->posts[0]). '","name":"' . get_the_title($this->posts[0])
				. '"}},{"@type":"ListItem","position":6,"item":{"@id":"' . get_term_link($this->terms[5]) . '","name":"' . get_term($this->terms[5])->name
				. '"}},{"@type":"ListItem","position":5,"item":{"@id":"' . get_term_link($this->terms[7]) . '","name":"' . get_term($this->terms[7])->name
				. '"}},{"@type":"ListItem","position":4,"item":{"@id":"' . get_term_link($this->terms[8]) . '","name":"' . get_term($this->terms[8])->name
				. '"}},{"@type":"ListItem","position":3,"item":{"@id":"' . get_term_link($this->terms[6]) . '","name":"' . get_term($this->terms[6])->name
				. '"}},{"@type":"ListItem","position":2,"item":{"@id":"' . get_permalink($this->blog) . '","name":"' . get_the_title($this->blog)
				. '"}},{"@type":"ListItem","position":1,"item":{"@id":"' . get_home_url() . '","name":"' . get_option('blogname')
				. '"}}]}',
			bcn_display_json_ld(true, true, true));
	}
}

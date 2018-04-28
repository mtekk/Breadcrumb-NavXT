<?php
/**
 * This file contains tests for the bcn_breadcrumb class
 *
 * @group breadcrumb-navxt
 * @group bcn_core
 */
class BreadcrumbNavXTTest extends WP_UnitTestCase {
	public $pages;
	public $home;
	public $blog;
	public $terms;
	public $posts;
	function setUp() {
		parent::setUp();
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
		$this->pages = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $this->pages[0], 'post_parent' => $this->pages[3]));
		wp_update_post(array('ID' => $this->pages[1], 'post_parent' => $this->pages[2]));
		wp_update_post(array('ID' => $this->pages[2], 'post_parent' => $this->pages[3]));
		wp_update_post(array('ID' => $this->pages[6], 'post_parent' => $this->pages[5]));
		wp_update_post(array('ID' => $this->pages[5], 'post_parent' => $this->pages[0]));
		$this->home = $this->factory->post->create(array('post_title' => 'Home', 'post_type' => 'page'));
		$this->blog = $this->factory->post->create(array('post_title' => 'Articles', 'post_type' => 'page'));
		//Set page '3' as the home page
		update_option('page_on_front', $this->home);
		//Set page '6' as the root for posts
		update_option('page_for_posts', $this->blog);
		//Create some terms
		$this->terms = $this->factory->category->create_many(10);
		//Create a test post
		$this->posts[] = $this->factory->post->create(array('post_title' => 'Test Post'));
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
	public function tearDown() {
		parent::tearDown();
	}
	function test_bcn_display_cache()
	{
		//Create a test post
		$pid1 = $this->factory->post->create(array('post_title' => 'Test Post 1', 'post_type' => 'post'));
		//Create another test post
		$pidb = $this->factory->post->create(array('post_title' => 'Test Post B', 'post_type' => 'post'));
		//"Go to" our post
		$this->go_to(get_permalink($pid1));
		//Check the breadcrumb trail
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Test Blog." href="'
			. get_home_url() .
			'" class="home"><span property="name">Test Blog</span></a><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Articles." href="'
			. get_permalink($this->blog) .
			'" class="post-root post post-post"><span property="name">Articles</span></a><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the Uncategorized category archives." href="'
			. get_term_link(1) .
			'" class="taxonomy category"><span property="name">Uncategorized</span></a><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">Test Post 1</span><meta property="position" content="4"></span>',
			bcn_display(true, true, false, true));
		//"Go to" post B
		$this->go_to(get_permalink($pidb));
		//Check the breadcrumb trail, should be the same as before with caching
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Test Blog." href="'
			. get_home_url() .
			'" class="home"><span property="name">Test Blog</span></a><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Articles." href="'
			. get_permalink($this->blog) .
			'" class="post-root post post-post"><span property="name">Articles</span></a><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the Uncategorized category archives." href="'
			. get_term_link(1) .
			'" class="taxonomy category"><span property="name">Uncategorized</span></a><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">Test Post 1</span><meta property="position" content="4"></span>',
			bcn_display(true, true, false, false));
		//Check the breadcrumb trail again without caching
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Test Blog." href="'
			. get_home_url() .
			'" class="home"><span property="name">Test Blog</span></a><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to Articles." href="'
			. get_permalink($this->blog) .
			'" class="post-root post post-post"><span property="name">Articles</span></a><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the Uncategorized category archives." href="'
			. get_term_link(1) .
			'" class="taxonomy category"><span property="name">Uncategorized</span></a><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">Test Post B</span><meta property="position" content="4"></span>',
			bcn_display(true, true, false, true));
	}
	function test_bcn_display()
	{
		$this->go_to(get_permalink($this->posts[0]));
		//Now test the normal order mode
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name">' . get_option('blogname')
			. '</span><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->blog)
			. '</span><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[6])->name
			. '</span><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[8])->name
			. '</span><meta property="position" content="4"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[7])->name
			. '</span><meta property="position" content="5"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[5])->name
			. '</span><meta property="position" content="6"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->posts[0])
			. '</span><meta property="position" content="7"></span>'
			, bcn_display(true, false, false, true));
		//Now in reverse order
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->posts[0])
			. '</span><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[5])->name
			. '</span><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[7])->name
			. '</span><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[8])->name
			. '</span><meta property="position" content="4"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[6])->name
			. '</span><meta property="position" content="5"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->blog)
			. '</span><meta property="position" content="6"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_option('blogname')
			. '</span><meta property="position" content="7"></span>'
			, bcn_display(true, false, true, true));
		//Now linked
		$this->assertSame('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to ' . get_option('blogname') . '." href="' . get_home_url() . '" class="home"><span property="name">' . get_option('blogname')
			. '</span></a><meta property="position" content="1"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to ' . get_the_title($this->blog) . '." href="' . get_permalink($this->blog) . '" class="post-root post post-post"><span property="name">' . get_the_title($this->blog)
			. '</span></a><meta property="position" content="2"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[6])->name . ' category archives." href="' . get_term_link($this->terms[6]) . '" class="taxonomy category"><span property="name">' . get_term($this->terms[6])->name
			. '</span></a><meta property="position" content="3"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[8])->name . ' category archives." href="' . get_term_link($this->terms[8]) . '" class="taxonomy category"><span property="name">' . get_term($this->terms[8])->name
			. '</span></a><meta property="position" content="4"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[7])->name . ' category archives." href="' . get_term_link($this->terms[7]) . '" class="taxonomy category"><span property="name">' . get_term($this->terms[7])->name
			. '</span></a><meta property="position" content="5"></span> &gt; <span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[5])->name . ' category archives." href="' . get_term_link($this->terms[5]) . '" class="taxonomy category"><span property="name">' . get_term($this->terms[5])->name
			. '</span></a><meta property="position" content="6"></span> &gt; <span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->posts[0])
			. '</span><meta property="position" content="7"></span>'
			, bcn_display(true, true, false, true));
	}
	function test_bcn_display_list()
	{
		$this->go_to(get_permalink($this->posts[0]));
		//Now test the normal order mode
		$this->assertSame('<li class="home"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_option('blogname')
			. '</span><meta property="position" content="1"></span></li>' . "\n" . '<li class="post-root post post-post"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->blog)
			. '</span><meta property="position" content="2"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[6])->name
			. '</span><meta property="position" content="3"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[8])->name
			. '</span><meta property="position" content="4"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[7])->name
			. '</span><meta property="position" content="5"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[5])->name
			. '</span><meta property="position" content="6"></span></li>' . "\n" . '<li class="post post-post current-item"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->posts[0])
			. '</span><meta property="position" content="7"></span></li>' . "\n"
			, bcn_display_list(true, false, false, true));
		//Now in reverse order
		$this->assertSame('<li class="post post-post current-item"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->posts[0])
			. '</span><meta property="position" content="1"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[5])->name
			. '</span><meta property="position" content="2"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[7])->name
			. '</span><meta property="position" content="3"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[8])->name
			. '</span><meta property="position" content="4"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_term($this->terms[6])->name
			. '</span><meta property="position" content="5"></span></li>' . "\n" . '<li class="post-root post post-post"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->blog)
			. '</span><meta property="position" content="6"></span></li>' . "\n" . '<li class="home"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_option('blogname')
			. '</span><meta property="position" content="7"></span></li>' . "\n"
			, bcn_display_list(true, false, true, true));
		//Now linked
		$this->assertSame('<li class="home"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to ' . get_option('blogname') . '." href="' . get_home_url() . '" class="home"><span property="name">' . get_option('blogname')
			. '</span></a><meta property="position" content="1"></span></li>' . "\n" . '<li class="post-root post post-post"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to ' . get_the_title($this->blog) . '." href="' . get_permalink($this->blog) . '" class="post-root post post-post"><span property="name">' . get_the_title($this->blog)
			. '</span></a><meta property="position" content="2"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[6])->name . ' category archives." href="' . get_term_link($this->terms[6]) . '" class="taxonomy category"><span property="name">' . get_term($this->terms[6])->name
			. '</span></a><meta property="position" content="3"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[8])->name . ' category archives." href="' . get_term_link($this->terms[8]) . '" class="taxonomy category"><span property="name">' . get_term($this->terms[8])->name
			. '</span></a><meta property="position" content="4"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[7])->name . ' category archives." href="' . get_term_link($this->terms[7]) . '" class="taxonomy category"><span property="name">' . get_term($this->terms[7])->name
			. '</span></a><meta property="position" content="5"></span></li>' . "\n" . '<li class="taxonomy category"><span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the ' . get_term($this->terms[5])->name . ' category archives." href="' . get_term_link($this->terms[5]) . '" class="taxonomy category"><span property="name">' . get_term($this->terms[5])->name
			. '</span></a><meta property="position" content="6"></span></li>' . "\n" . '<li class="post post-post current-item"><span property="itemListElement" typeof="ListItem"><span property="name">' . get_the_title($this->posts[0])
			. '</span><meta property="position" content="7"></span></li>' . "\n"
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
				. '"}},{"@type":"ListItem","position":7,"item":{"@id":null,"name":"' . get_the_title($this->posts[0])
				. '"}}]}',
			bcn_display_json_ld(true, false, true));
		//Now test the reverse order mode
		$this->assertJsonStringEqualsJsonString(
			'{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"item":{"@id":null,"name":"' . get_the_title($this->posts[0])
				. '"}},{"@type":"ListItem","position":2,"item":{"@id":"' . get_term_link($this->terms[5]) . '","name":"' . get_term($this->terms[5])->name
				. '"}},{"@type":"ListItem","position":3,"item":{"@id":"' . get_term_link($this->terms[7]) . '","name":"' . get_term($this->terms[7])->name
				. '"}},{"@type":"ListItem","position":4,"item":{"@id":"' . get_term_link($this->terms[8]) . '","name":"' . get_term($this->terms[8])->name
				. '"}},{"@type":"ListItem","position":5,"item":{"@id":"' . get_term_link($this->terms[6]) . '","name":"' . get_term($this->terms[6])->name
				. '"}},{"@type":"ListItem","position":6,"item":{"@id":"' . get_permalink($this->blog) . '","name":"' . get_the_title($this->blog)
				. '"}},{"@type":"ListItem","position":7,"item":{"@id":"' . get_home_url() . '","name":"' . get_option('blogname')
				. '"}}]}',
			bcn_display_json_ld(true, true, true));
	}
}
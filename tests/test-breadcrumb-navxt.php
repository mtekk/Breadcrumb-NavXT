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
			bcn_display(true, true, false, false));
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
}
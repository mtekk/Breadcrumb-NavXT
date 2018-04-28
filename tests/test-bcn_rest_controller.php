<?php
/**
 * This file contains tests for the bcn_breadcrumb class
 *
 * @group bcn_breadcrumb_trail
 * @group bcn_core
 */
class BreadcrumbRESTControllerTest extends WP_UnitTestCase {
	protected $pids;
	protected $paids;
	protected $tids;
	
	protected static $superadmin_id;
	protected static $editor_id;
	protected static $author_id;
	protected static $contributor_id;
	
	public static function wpTearDownAfterClass() {
		self::delete_user( self::$superadmin_id );
		self::delete_user( self::$editor_id );
		self::delete_user( self::$author_id );
		self::delete_user( self::$contributor_id );
	}
	public function setUp() {
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
		$this->tids = $this->factory->term->create(array('name' => 'Test Party', 'taxonomy' => 'party'));
		self::$superadmin_id  = $this->factory->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'superadmin',
			)
		);
		self::$editor_id      = $this->factory->user->create(
			array(
				'role' => 'editor',
			)
		);
		self::$author_id      = $this->factory->user->create(
			array(
				'role' => 'author',
			)
		);
		self::$contributor_id = $this->factory->user->create(
			array(
				'role' => 'contributor',
			)
		);
		if ( is_multisite() ) {
			update_site_option( 'site_admins', array( 'superadmin' ) );
		}
		//Create some posts
		$this->pids = $this->factory->post->create_many(10, array('post_type' => 'post'));
		//Create some terms
		$this->tids = $this->factory->category->create_many(10);
		//Make some of the terms be in a hierarchy
		wp_update_term($this->tids[7], 'category', array('parent' => $this->tids[8]));
		wp_update_term($this->tids[8], 'category', array('parent' => $this->tids[6]));
		wp_update_term($this->tids[9], 'category', array('parent' => $this->tids[8]));
		wp_update_term($this->tids[5], 'category', array('parent' => $this->tids[7]));
		//Assign a category to a post
		wp_set_object_terms($this->pids[0], array($this->tids[5]), 'category');
		wp_set_object_terms($this->pids[5], array($this->tids[0]), 'category');
		wp_set_object_terms($this->pids[7], array($this->tids[7]), 'category');
		//Create some pages
		$this->paids = $this->factory->post->create_many(10, array('post_type' => 'page'));
		//Setup some relationships between the posts
		wp_update_post(array('ID' => $this->paids[0], 'post_parent' => $this->paids[3]));
		wp_update_post(array('ID' => $this->paids[1], 'post_parent' => $this->paids[2]));
		wp_update_post(array('ID' => $this->paids[2], 'post_parent' => $this->paids[3]));
		wp_update_post(array('ID' => $this->paids[6], 'post_parent' => $this->paids[5]));
		wp_update_post(array('ID' => $this->paids[5], 'post_parent' => $this->paids[0]));
	}
	public function tearDown() {
		parent::tearDown();
	}
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		
		$this->assertArrayHasKey('/bcn/v1/post/(?P<id>[\d]+)', $routes);
		$this->assertCount(1, $routes['/bcn/v1/post/(?P<id>[\d]+)']);
		$this->assertArrayHasKey('/bcn/v1/term/(?P<taxonomy>[\w-]+)/(?P<id>[\d]+)', $routes);
		$this->assertCount(1, $routes['/bcn/v1/term/(?P<taxonomy>[\w-]+)/(?P<id>[\d]+)']);
		$this->assertArrayHasKey('/bcn/v1/author/(?P<id>\d+)', $routes);
		$this->assertCount(1, $routes['/bcn/v1/author/(?P<id>\d+)']);
	}
/*	public function test_registered_query_params() {
		//Check post endpoint
		$request  = new WP_REST_Request( 'OPTIONS', sprintf('/bcn/v1/post/%d', $this->pids[0] ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$keys     = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals(
			array(
				'id',
				'password'
			), $keys
		);
		//Check term endpoint
		$request  = new WP_REST_Request( 'OPTIONS', sprintf('/bcn/v1/term/party/%d', $this->tids[0] ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$keys     = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals(
			array(
				'id',
				'taxonomy'
			), $keys
		);
		//Check term endpoint
		$request  = new WP_REST_Request( 'OPTIONS', sprintf('/bcn/v1/author/%d', self::$editor_id ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$keys     = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals(
			array(
				'id'
			), $keys
		);
	}*/
	public function test_get_items_empty_query() {
		$request = new WP_REST_Request( 'GET', '/bcn/v1/post/99999999' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEmpty( $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );

		$request = new WP_REST_Request( 'GET', '/bcn/v1/term/party/99999999' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEmpty( $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
		
		$request = new WP_REST_Request( 'GET', '/bcn/v1/author/99999999' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEmpty( $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}
	
	public function test_get_items_status_without_permissions() {
		$draft_id = $this->factory->post->create(
			array(
				'post_status' => 'draft',
			)
		);
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', sprintf('/bcn/v1/post/%d', $draft_id ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals('rest_forbidden', $data['code']);
		$this->assertEquals(array('status' => 401), $data['data']);
	}
	/**
	 * Tests for a REST author request
	 */
	public function test_rest_author_request() {
		//Some setup
		$author_id = $this->factory->user->create(array('role' => 'editor', 'user_login' => 'cooleditor1', 'display_name' => 'Cool Editor'));
		$pids = $this->factory->post->create_many(10, array('author' => $author_id));
		//Now request the author archives
		$request  = new WP_REST_Request('GET', sprintf('/bcn/v1/author/%d', $author_id));
		$response = rest_get_server()->dispatch( $request );

		$this->assertNotWPError( $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		//Ensure we have 1 breadcrumb from the do_author portion
		$this->assertCount(2, $data->itemListElement);
		$this->assertSame('Cool Editor', $data->itemListElement[1]->item->name);
	}
	/**
	 * Tests for a REST taxonomy term request
	 */
	function test_rest_term_request() {
		//Now request the author archives
		$request  = new WP_REST_Request('GET', sprintf('/bcn/v1/term/category/%d', $this->tids[7]));
		$response = rest_get_server()->dispatch( $request );

		$this->assertNotWPError( $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		
		//Ensure we have 4 breadcrumb from the do_author portion
		$this->assertCount(4, $data->itemListElement);
		//Look at each breadcrumb
		$this->assertSame(get_option('blogname') ,$data->itemListElement[0]->item->name);
		$this->assertSame(get_term($this->tids[6], 'category')->name, $data->itemListElement[1]->item->name);
		$this->assertSame(get_term($this->tids[8], 'category')->name, $data->itemListElement[2]->item->name);
		$this->assertSame(get_term($this->tids[7], 'category')->name, $data->itemListElement[3]->item->name);
	}
	/**
	 * Tests for a REST post request
	 */
	function test_rest_post_request() {
		//Now request the author archives
		$request  = new WP_REST_Request('GET', sprintf('/bcn/v1/post/%d', $this->pids[0]));
		$response = rest_get_server()->dispatch( $request );

		$this->assertNotWPError( $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		
		//Ensure we have 6 breadcrumb from the do_author portion
		$this->assertCount(6, $data->itemListElement);
		//Look at each breadcrumb
		$this->assertSame(get_option('blogname') ,$data->itemListElement[0]->item->name);
		$this->assertSame(get_term($this->tids[6], 'category')->name, $data->itemListElement[1]->item->name);
		$this->assertSame(get_term($this->tids[8], 'category')->name, $data->itemListElement[2]->item->name);
		$this->assertSame(get_term($this->tids[7], 'category')->name, $data->itemListElement[3]->item->name);
		$this->assertSame(get_term($this->tids[5], 'category')->name, $data->itemListElement[4]->item->name);
		$this->assertSame(get_the_title($this->pids[0]), $data->itemListElement[5]->item->name);
	}
}

<?php
/*
	Copyright 2015-2025  John Havlik  (email : john.havlik@mtekk.us)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once(dirname(__FILE__) . '/includes/block_direct_access.php');
//Do a PHP version check, require 5.3 or newer
if(version_compare(phpversion(), '5.3.0', '<'))
{
	//Only purpose of this function is to echo out the PHP version error
	function bcn_phpold()
	{
		printf('<div class="notice notice-error"><p>' . __('Your PHP version is too old, please upgrade to a newer version. Your version is %1$s, Breadcrumb NavXT requires %2$s', 'breadcrumb-navxt') . '</p></div>', phpversion(), '5.3.0');
	}
	//If we are in the admin, let's print a warning then return
	if(is_admin())
	{
		add_action('admin_notices', 'bcn_phpold');
	}
	return;
}
class bcn_rest_controller
{
	const version = '1';
	protected $unique_prefix = 'bcn';
	protected $breadcrumb_trail = null;
	protected $methods = array('GET', 'OPTIONS');
	/**
	 * Default constructor
	 * 
	 * @param bcn_breadcrumb_trail $breadcrumb_trail An instance of a bcn_breadcrumb_trail object to use for everything
	 * @param string $unique_prefix The unique prefix to use for the API endpoint
	 */
	public function __construct(bcn_breadcrumb_trail $breadcrumb_trail, $unique_prefix)
	{
		$this->breadcrumb_trail = $breadcrumb_trail;
		$this->unique_prefix = $unique_prefix;
		add_action('rest_api_init', array($this, 'register_routes'));
	}
	/**
	 * A quick wrapper for register_rest_route to add our inclusion filter
	 * 
	 * @param string $endpoint The endpoint name passed into the bcn_register_rest_endpoint filter
	 * @param string $namespace The first URL segment after core prefix. Should be unique
	 * @param string $route The base URL for route being added
	 * @param array $args Optional. Either an array of options for the endpoint, or an array of arrays for
	 *                          multiple methods. Default empty array.
	 * @param bool $override Optional. If the route already exists, should we override it?
	 * @return boolean True on success, false on error.
	 */
	protected function register_rest_route($endpoint, $namespace, $route, $args = array(), $override = false)
	{
		if(apply_filters('bcn_register_rest_endpoint', false, $endpoint, $this::version, $this->methods))
		{
			return register_rest_route($namespace, $route, $args, $override);
		}
		return false;
	}
	public function register_routes()
	{
		$this->register_rest_route('post', $this->unique_prefix . '/v' . $this::version, '/post/(?P<id>[\d]+)', array(
			'args' => array(
				'id' => array(
					'description' => __('The ID of the post (any type) to retrieve the breadcrumb trail for.', 'breadcrumb-navxt'),
					'type' => 'integer',
					'required' => true,
					'validate_callback' => array($this, 'validate_id')
				)
			),
			'methods' => $this->methods,
			'callback' => array($this, 'display_rest_post'),
			'permission_callback' => array($this, 'display_rest_post_permissions_check')
			), false
		);
		$this->register_rest_route('term', $this->unique_prefix . '/v' . $this::version, '/term/(?P<taxonomy>[\w-]+)/(?P<id>[\d]+)', array(
			'args' => array(
				'id' => array(
					'description' => __('The ID of the term to retrieve the breadcrumb trail for.', 'breadcrumb-navxt'),
					'type' => 'integer',
					'required' => true,
					'validate_callback' => array($this, 'validate_id')
				),
				'taxonomy' => array(
					'description' => __('The taxonomy of the term to retrieve the breadcrumb trail for.', 'breadcrumb-navxt'),
					'type' => 'string',
					'required' => true,
					'validate_callback' => array($this, 'validate_taxonomy')
				)
			),
			'methods' => $this->methods,
			'callback' => array($this, 'display_rest_term'),
			'permission_callback' => '__return_true'
			), false
		);
		$this->register_rest_route('author', $this->unique_prefix . '/v' . $this::version, '/author/(?P<id>\d+)', array(
			'args' => array(
				'id' => array(
					'description' => __('The ID of the author to retrieve the breadcrumb trail for.', 'breadcrumb-navxt'),
					'type' => 'integer',
					'required' => true,
					'validate_callback' => array($this, 'validate_id')
				)
			),
			'methods' => $this->methods,
			'callback' => array($this, 'display_rest_author'),
			'permission_callback' => '__return_true'
			), false
		);
	}
	/**
	 * Checks to see if the request ID looks like it could be an ID (numeric and greater than 0)
	 * 
	 * @param mixed $param The parameter to validate
	 * @param WP_REST_Request $request REST API request data
	 * @param string $key The paramter key
	 * @return bool Whether or not the ID is valid (or atleast looks valid)
	 */
	public function validate_id($param, $request, $key)
	{
		return is_numeric($param) && absint($param) > 0;
	}
	/**
	 * Checks to see if the request taxonomy is a valid taxonomy
	 * 
	 * @param mixed $param The parameter to validate
	 * @param WP_REST_Request $request REST API request data
	 * @param string $key The paramter key
	 * @return bool Whether or not the ID is valid (or atleast looks valid)
	 */
	public function validate_taxonomy($param, $request, $key)
	{
		return taxonomy_exists(esc_attr($param));
	}
	/**
	 * Check permissions for the post
	 * 
	 * @param WP_REST_Request $request The request to check the permissions on
	 * @return bool | WP_Error Whether or not the user can view the requested post
	 */
	public function display_rest_post_permissions_check(WP_REST_Request $request)
	{
		$post = get_post(absint($request->get_param('id')));
		if($post === null)
		{
			return true;
		}
		return $this->check_post_read_permission($post);
	}
	/**
	 * Check to ensure the current user can read the post (and subsequently view its breadcrumb trail)
	 * 
	 * @param WP_Post $post The post to check if the current user can view the breadcrumb trail for
	 * @return bool Whether or not the post should be readable
	 */
	public function check_post_read_permission($post)
	{
		if(!($post instanceof WP_Post))
		{
			return false;
		}
		$post_type = get_post_type_object($post->post_type);
		if(empty($post_type) || empty($post_type->show_in_rest))
		{
			return false;
		}
		if($post->post_status === 'publish' || current_user_can($post_type->cap->read_post, $post->ID))
		{
			return true;
		}
		$post_status_obj = get_post_status_object($post->post_status);
		if($post_status_obj && $post_status_obj->public)
		{
			return true;
		}
		if($post->post_status === 'inherit' && $post->post_parent > 0)
		{
			$parent = get_post($post->post_parent);
			if($parent)
			{
				return $this->check_post_read_permission($parent);
			}
		}
		if($post->post_status === 'inherit')
		{
			return true;
		}
		return false;
	}
	/**
	 * Breadcrumb trail handler for REST requests for post breadcrumb trails
	 * 
	 * @param WP_REST_Request $request REST API request data
	 * @return STD_Object Basic object data of the Schema.org Breadcrumb List compatible breadcrumb trail
	 */
	public function display_rest_post(WP_REST_Request $request)
	{
		$post = get_post(absint($request->get_param('id')));
		if($post instanceof WP_Post)
		{
			$this->breadcrumb_trail->breadcrumbs = array();
			//Generate the breadcrumb trail
			$this->breadcrumb_trail->fill_REST($post);
			return $this->breadcrumb_trail->display_json_ld(false);
		}
	}
	/**
	 * Breadcrumb trail handler for REST requests for term breadcrumb trails
	 * 
	 * @param WP_REST_Request $request REST API request data
	 * @return STD_Object Basic object data of the Schema.org Breadcrumb List compatible breadcrumb trail
	 */
	public function display_rest_term(WP_REST_Request $request)
	{
		$term = get_term(absint($request->get_param('id')), esc_attr($request->get_param('taxonomy')));
		if($term instanceof WP_Term)
		{
			$this->breadcrumb_trail->breadcrumbs = array();
			//Generate the breadcrumb trail
			$this->breadcrumb_trail->fill_REST($term);
			return $this->breadcrumb_trail->display_json_ld(false);
		}
	}
	/**
	 * Breadcrumb trail handler for REST requests for term breadcrumb trails
	 * 
	 * @param WP_REST_Request $request REST API request data
	 * @return STD_Object Basic object data of the Schema.org Breadcrumb List compatible breadcrumb trail
	 */
	public function display_rest_author(WP_REST_Request $request)
	{
		$user = get_user_by('ID', absint($request->get_param('id')), esc_attr($request->get_param('taxonomy')));
		if($user instanceof WP_User)
		{
			$this->breadcrumb_trail->breadcrumbs = array();
			//Generate the breadcrumb trail
			$this->breadcrumb_trail->fill_REST($user);
			return $this->breadcrumb_trail->display_json_ld(false);
		}
	}
}

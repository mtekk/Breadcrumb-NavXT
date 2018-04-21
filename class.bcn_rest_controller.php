<?php
/*
	Copyright 2015-2018  John Havlik  (email : john.havlik@mtekk.us)

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
	public function register_routes()
	{
		register_rest_route( $this->unique_prefix . '/v' . $this::version, '/post/(?P<id>[\d]+)', array(
			'args' => array(
				'id' => array(
					'description' => __('The ID of the post (any type) to retrieve the breadcrumb trail for.', 'breadcrumb-navxt'),
					'type' => 'integer',
					'validate_callback' => array($this, 'validate_id')
				)
			),
			array('methods' => 'GET', 'callback' => array($this, 'display_rest_post')))
		);
		register_rest_route( $this->unique_prefix . '/v' . $this::version, '/term/(?P<taxonomy>[\w-]+)/(?P<id>[\d]+)', array(
			'args' => array(
				'taxonomy' => array(
					'description' => __('The taxonomy of the term to retrieve the breadcrumb trail for.', 'breadcrumb-navxt'),
					'type' => 'string',
					'validate_callback' => array($this, 'validate_taxonomy')
				),
				'id' => array(
					'description' => __('The ID of the term to retrieve the breadcrumb trail for.', 'breadcrumb-navxt'),
					'type' => 'integer',
					'validate_callback' => array($this, 'validate_id')
				)
			),
			array('methods' => 'GET', 'callback' => array($this, 'display_rest_term')))
		);
		//register_rest_route( $this->unique_prefix . '/v' . $this::version, '/author/(?P<id>\d+)', array('methods' => 'GET', 'callback' => array($this, 'display_rest_author')));
	}
	/**
	 * Checks to see if the request ID looks like it could be an ID (numeric and greater than 0)
	 * 
	 * @param mixed @param The parameter to validate
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
	 * @param mixed @param The parameter to validate
	 * @param WP_REST_Request $request REST API request data
	 * @param string $key The paramter key
	 * @return bool Whether or not the ID is valid (or atleast looks valid)
	 */
	public function validate_taxonomy($param, $request, $key)
	{
		return taxonomy_exists(esc_attr($param));
	}
	/**
	 * Breadcrumb trail handler for REST requests for post breadcrumb trails
	 * 
	 * @param WP_REST_Request $request REST API request data
	 * @return string String-Data of breadcrumb trail.
	 */
	public function display_rest_post(WP_REST_Request $request)
	{
		//Generate the breadcrumb trail
		$this->breadcrumb_trail->fill_rest(get_post(absint($request->get_param('id'))));
		return $this->breadcrumb_trail->display_json_ld(false);
	}
	/**
	 * Breadcrumb trail handler for REST requests for term breadcrumb trails
	 * 
	 * @param WP_REST_Request $request REST API request data
	 * @return string String-Data of breadcrumb trail.
	 */
	public function display_rest_term(WP_REST_Request $request)
	{
		//Generate the breadcrumb trail
		$this->breadcrumb_trail->fill_rest(get_term(absint($request->get_param('id')), esc_attr($request->get_param('taxonomy'))));
		return $this->breadcrumb_trail->display_json_ld(false);
	}
}

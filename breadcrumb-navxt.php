<?php
/*
Plugin Name: Breadcrumb NavXT
Plugin URI: http://mtekk.us/code/breadcrumb-navxt/
Description: Adds a breadcrumb navigation showing the visitor&#39;s path to their current location. For details on how to use this plugin visit <a href="http://mtekk.us/code/breadcrumb-navxt/">Breadcrumb NavXT</a>. 
Version: 4.4.52
Author: John Havlik
Author URI: http://mtekk.us/
License: GPL2
TextDomain: breadcrumb-navxt
DomainPath: /languages/
*/
/*  Copyright 2007-2013  John Havlik  (email : mtekkmonkey@gmail.com)

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
//Do a PHP version check, require 5.2 or newer
if(version_compare(phpversion(), '5.2.0', '<'))
{
	//Only purpose of this function is to echo out the PHP version error
	function bcn_phpold()
	{
		printf('<div class="error"><p>' . __('Your PHP version is too old, please upgrade to a newer version. Your version is %1$s, Breadcrumb NavXT requires %2$s', 'breadcrumb-navxt') . '</p></div>', phpversion(), '5.2.0');
	}
	//If we are in the admin, let's print a warning then return
	if(is_admin())
	{
		add_action('admin_notices', 'bcn_phpold');
	}
	return;
}
if(!function_exists('mb_strlen'))
{
	require_once(dirname(__FILE__) . '/includes/multibyte_supplicant.php');
}
//Include admin base class
if(!class_exists('mtekk_adminKit'))
{
	require_once(dirname(__FILE__) . '/includes/class.mtekk_adminkit.php');
}
//Include the breadcrumb class
require_once(dirname(__FILE__) . '/class.bcn_breadcrumb.php');
//Include the breadcrumb trail class
require_once(dirname(__FILE__) . '/class.bcn_breadcrumb_trail.php');
//Include the WP 2.8+ widget class
require_once(dirname(__FILE__) . '/class.bcn_widget.php');
//TODO change to extends mtekk_plugKit
class breadcrumb_navxt
{
	private $version = '4.3.90';
	protected $name = 'Breadcrumb NavXT';
	protected $identifier = 'breadcrumb-navxt';
	protected $unique_prefix = 'bcn';
	protected $plugin_basename = null;
	protected $opt = null;
	protected $breadcrumb_trail = null;
	protected $admin = null;
	/**
	 * Constructor for a new breadcrumb_navxt object
	 * 
	 * @param bcn_breadcrumb_trail $breadcrumb_trail An instance of a bcn_breadcrumb_trail object to use for everything
	 */
	public function __construct(bcn_breadcrumb_trail $breadcrumb_trail)
	{
		//We get our breadcrumb trail object from our constructor
		$this->breadcrumb_trail = $breadcrumb_trail;
		//Grab defaults from the breadcrumb_trail object
		$this->opt = $this->breadcrumb_trail->opt;
		//We set the plugin basename here
		$this->plugin_basename = plugin_basename(__FILE__);
		//We need to add in the defaults for CPTs and custom taxonomies after all other plugins are loaded
		add_action('wp_loaded', array($this, 'wp_loaded'));
		add_action('init', array($this, 'init'));
		//Register the WordPress 2.8 Widget
		add_action('widgets_init', create_function('', 'return register_widget("'. $this->unique_prefix . '_widget");'));
		//Load our network admin if in the network dashboard (yes is_network_admin() doesn't exist)
		if(defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN)
		{
			require_once(dirname(__FILE__) . '/class.bcn_network_admin.php');
			//Instantiate our new admin object
			$this->admin = new bcn_network_admin($this->breadcrumb_trail, $this->plugin_basename);
		}
		//Load our main admin if in the dashboard, but only if we're not in the network dashboard (prevents goofy bugs)
		else if(is_admin())
		{
			require_once(dirname(__FILE__) . '/class.bcn_admin.php');
			//Instantiate our new admin object
			$this->admin = new bcn_admin($this->breadcrumb_trail, $this->plugin_basename);
		}
	}
	public function init()
	{
		add_filter('bcn_allowed_html', array($this, 'allowed_html'), 1, 1);
		//We want to run late for using our breadcrumbs
		add_filter('tha_breadcrumb_navigation', array($this, 'tha_compat'), 99);
	}
	public function allowed_html($tags)
	{
		$allowed_html = array(
					'a' => array(
						'href' => true,
						'title' => true,
						'class' => true,
						'id' => true,
						'media' => true,
						'dir' => true,
						'relList' => true,
						'rel' => true,
						'aria-hidden' => true,
						'data-icon' => true,
						'itemref' => true,
						'itemid' => true,
						'itemprop' => true,
						'itemscope' => true,
						'itemtype' => true
					),
					'img' => array(
						'alt' => true,
						'align' => true,
						'height' => true,
						'width' => true,
						'src' => true,
						'id' => true,
						'class' => true,
						'aria-hidden' => true,
						'data-icon' => true,
						'itemref' => true,
						'itemid' => true,
						'itemprop' => true,
						'itemscope' => true,
						'itemtype' => true
					),
					'span' => array(
						'title' => true,
						'class' => true,
						'id' => true,
						'dir' => true,
						'align' => true,
						'lang' => true,
						'xml:lang' => true,
						'aria-hidden' => true,
						'data-icon' => true,
						'itemref' => true,
						'itemid' => true,
						'itemprop' => true,
						'itemscope' => true,
						'itemtype' => true
					),
					'h1' => array(
						'title' => true,
						'class' => true,
						'id' => true,
						'dir' => true,
						'align' => true,
						'lang' => true,
						'xml:lang' => true,
						'aria-hidden' => true,
						'data-icon' => true,
						'itemref' => true,
						'itemid' => true,
						'itemprop' => true,
						'itemscope' => true,
						'itemtype' => true
					),
					'h2' => array(
						'title' => true,
						'class' => true,
						'id' => true,
						'dir' => true,
						'align' => true,
						'lang' => true,
						'xml:lang' => true,
						'aria-hidden' => true,
						'data-icon' => true,
						'itemref' => true,
						'itemid' => true,
						'itemprop' => true,
						'itemscope' => true,
						'itemtype' => true
					)
		);
		return mtekk_adminKit::array_merge_recursive($tags, $allowed_html);
	}
	public function get_version()
	{
		return $this->version;
	}
	public function wp_loaded()
	{
		//First make sure our defaults are safe
		$this->find_posttypes($this->opt);
		$this->find_taxonomies($this->opt);
		//Let others hook into our settings
		$this->opt = apply_filters($this->unique_prefix . '_settings_init', $this->opt);
	}
	/**
	 * Places settings into $opts array, if missing, for the registered post types
	 * 
	 * @param array $opts
	 */
	static function find_posttypes(&$opts)
	{
		global $wp_post_types, $wp_taxonomies;
		//Loop through all of the post types in the array
		foreach($wp_post_types as $post_type)
		{
			//We only want custom post types
			if(!$post_type->_builtin)
			{
				//If the post type does not have settings in the options array yet, we need to load some defaults
				if(!array_key_exists('Hpost_' . $post_type->name . '_template', $opts) || !$post_type->hierarchical && !array_key_exists('Spost_' . $post_type->name . '_taxonomy_type', $opts))
				{
					//Add the necessary option array members
					$opts['Hpost_' . $post_type->name . '_template'] = __('<a title="Go to %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt');
					$opts['Hpost_' . $post_type->name . '_template_no_anchor'] = __('%htitle%', 'breadcrumb-navxt');
					$opts['bpost_' . $post_type->name . '_archive_display'] = $post_type->has_archive;
					//Do type dependent tasks
					if($post_type->hierarchical)
					{
						//Set post_root for hierarchical types
						$opts['apost_' . $post_type->name . '_root'] = get_option('page_on_front');
					}
					//If it is flat, we need a taxonomy selection
					else
					{
						//Set post_root for flat types
						$opts['apost_' . $post_type->name . '_root'] = get_option('page_for_posts');
					}
					//Default to not displaying a taxonomy
					$opts['bpost_' . $post_type->name . '_taxonomy_display'] = false;
					//Loop through all of the possible taxonomies
					foreach($wp_taxonomies as $taxonomy)
					{
						//Activate the first taxonomy valid for this post type and exit the loop
						if($taxonomy->object_type == $post_type->name || in_array($post_type->name, $taxonomy->object_type))
						{
							$opts['bpost_' . $post_type->name . '_taxonomy_display'] = true;
							$opts['Spost_' . $post_type->name . '_taxonomy_type'] = $taxonomy->name;
							break;
						}
					}
					//If there are no valid taxonomies for this type, we default to not displaying taxonomies for this post type
					if(!isset($opts['Spost_' . $post_type->name . '_taxonomy_type']))
					{
						$opts['Spost_' . $post_type->name . '_taxonomy_type'] = 'date';
					}
				}
			}
		}
	}
	/**
	 * Places settings into $opts array, if missing, for the registered taxonomies
	 * 
	 * @param $opts
	 */
	static function find_taxonomies(&$opts)
	{
		global $wp_taxonomies;
		//We'll add our custom taxonomy stuff at this time
		foreach($wp_taxonomies as $taxonomy)
		{
			//We only want custom taxonomies
			if(!$taxonomy->_builtin)
			{
				//If the taxonomy does not have settings in the options array yet, we need to load some defaults
				if(!array_key_exists('H' . $taxonomy->name . '_template', $opts))
				{
					//Add the necessary option array members
					$opts['H' . $taxonomy->name . '_template'] = __(sprintf('<a title="Go to the %%title%% %s archives." href="%%link%%">%%htitle%%</a>', $taxonomy->labels->singular_name), 'breadcrumb-navxt');
					$opts['H' . $taxonomy->name . '_template_no_anchor'] = __(sprintf('%%htitle%%', $taxonomy->labels->singular_name), 'breadcrumb-navxt');
				}
			}
		}
	}
	/**
	 * Hooks into the theme hook alliance tha_breadcrumb_navigation filter and replaces the trail
	 * with one generated by Breadcrumb NavXT
	 * 
	 * @param string $bradcrumb_trail The string breadcrumb trail that we will replace
	 * @return string The Breadcrumb NavXT assembled breadcrumb trail
	 */
	public function tha_compat($breadcrumb_trail)
	{
		//Return our breadcrumb trail
		return $this->display(true);
	}
	/**
	 * Function updates the breadcrumb_trail options array from the database in a semi intellegent manner
	 * 
	 * @since  5.0.0
	 */
	private function get_settings()
	{
		//Let's begin by grabbing the current settings for the site (works for both multisite and single installs)
		$this->breadcrumb_trail->opt = wp_parse_args(get_site_option('bcn_options'), $this->opt);
		//If we're in multisite mode, look at the three BCN_SETTINGS globals
		if(defined('MULTISITE') && MULTISITE)
		{
			if(defined('BCN_SETTINGS_USE_LOCAL') && BCN_SETTINGS_USE_LOCAL)
			{
				//Grab the current settings from the db
				$this->breadcrumb_trail->opt = wp_parse_args(get_option('bcn_options'), $this->opt);
			}
			else if(defined('BCN_SETTINGS_FAVOR_LOCAL') && BCN_SETTINGS_FAVOR_LOCAL)
			{
				//Grab the current settings from the db
				$this->breadcrumb_trail->opt = wp_parse_args(get_option('bcn_options'), $this->breadcrumb_trail->opt);
			}
			else if(defined('BCN_SETTINGS_FAVOR_NETWORK') && BCN_SETTINGS_FAVOR_NETWORK)
			{
				//Grab the current settings from the db
				$this->breadcrumb_trail->opt = wp_parse_args($this->breadcrumb_trail->opt, get_option('bcn_options'));
			}
		}
	}
	/**
	 * Outputs the breadcrumb trail
	 * 
	 * @param bool $return Whether to return or echo the trail.
	 * @param bool $linked Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse Whether to reverse the output or not.
	 */
	public function display($return = false, $linked = true, $reverse = false)
	{
		$this->get_settings();
		//Generate the breadcrumb trail
		$this->breadcrumb_trail->fill();
		return $this->breadcrumb_trail->display($return, $linked, $reverse);
	}
	/**
	 * Outputs the breadcrumb trail with each element encapsulated with li tags
	 * 
	 * @since  3.2.0
	 * @param  bool $return Whether to return or echo the trail.
	 * @param  bool $linked Whether to allow hyperlinks in the trail or not.
	 * @param  bool	$reverse Whether to reverse the output or not.
	 */
	public function display_list($return = false, $linked = true, $reverse = false)
	{
		$this->get_settings();
		//Generate the breadcrumb trail
		$this->breadcrumb_trail->fill();
		return $this->breadcrumb_trail->display_list($return, $linked, $reverse);
	}
}
//In the future there will be a hook for this so derivatives of bcn_breadcrumb_trail can use the admin
$bcn_breadcrumb_trail = new bcn_breadcrumb_trail();
//Let's make an instance of our object takes care of everything
$breadcrumb_navxt = new breadcrumb_navxt(apply_filters('bcn_breadcrumb_trail_object', $bcn_breadcrumb_trail));
/**
 * Outputs the breadcrumb trail
 * 
 * @param bool $return Whether to return or echo the trail. (optional)
 * @param bool $linked Whether to allow hyperlinks in the trail or not. (optional)
 * @param bool $reverse Whether to reverse the output or not. (optional)
 */
function bcn_display($return = false, $linked = true, $reverse = false)
{
	global $breadcrumb_navxt;
	if($breadcrumb_navxt !== null)
	{
		return $breadcrumb_navxt->display($return, $linked, $reverse);
	}
}
/**
 * Outputs the breadcrumb trail with each element encapsulated with li tags
 * 
 * @param bool $return Whether to return or echo the trail. (optional)
 * @param bool $linked Whether to allow hyperlinks in the trail or not. (optional)
 * @param bool $reverse Whether to reverse the output or not. (optional)
 */
function bcn_display_list($return = false, $linked = true, $reverse = false)
{
	global $breadcrumb_navxt;
	if($breadcrumb_navxt !== null)
	{
		return $breadcrumb_navxt->display_list($return, $linked, $reverse);
	}
}
<?php
/*
Plugin Name: Breadcrumb NavXT
Plugin URI: http://mtekk.us/code/breadcrumb-navxt/
Description: Adds a breadcrumb navigation showing the visitor&#39;s path to their current location. For details on how to use this plugin visit <a href="http://mtekk.us/code/breadcrumb-navxt/">Breadcrumb NavXT</a>. 
Version: 6.6.0
Author: John Havlik
Author URI: http://mtekk.us/
License: GPL2
Text Domain: breadcrumb-navxt
Domain Path: /languages
*/
/*
	Copyright 2007-2020  John Havlik  (email : john.havlik@mtekk.us)

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
//Do a PHP version check, require 5.3 or newer
if(version_compare(phpversion(), '5.3.0', '<'))
{
	//Only purpose of this function is to echo out the PHP version error
	function bcn_phpold()
	{
		printf('<div class="notice notice-error"><p>' . esc_html__('Your PHP version is too old, please upgrade to a newer version. Your version is %1$s, Breadcrumb NavXT requires %2$s', 'breadcrumb-navxt') . '</p></div>', phpversion(), '5.3.0');
	}
	//If we are in the admin, let's print a warning then return
	if(is_admin())
	{
		add_action('admin_notices', 'bcn_phpold');
	}
	return;
}
require_once(dirname(__FILE__) . '/includes/multibyte_supplicant.php');
//Include admin base class
if(!class_exists('mtekk_adminKit'))
{
	require_once(dirname(__FILE__) . '/includes/class.mtekk_adminkit.php');
}
//Include the breadcrumb class
require_once(dirname(__FILE__) . '/class.bcn_breadcrumb.php');
//Include the breadcrumb trail class
require_once(dirname(__FILE__) . '/class.bcn_breadcrumb_trail.php');
if(class_exists('WP_Widget'))
{
	//Include the WP 2.8+ widget class
	require_once(dirname(__FILE__) . '/class.bcn_widget.php');
}
$breadcrumb_navxt = null;
//TODO change to extends mtekk_plugKit
class breadcrumb_navxt
{
	const version = '6.6.0';
	protected $name = 'Breadcrumb NavXT';
	protected $identifier = 'breadcrumb-navxt';
	protected $unique_prefix = 'bcn';
	protected $plugin_basename = null;
	protected $opt = null;
	protected $breadcrumb_trail = null;
	protected $admin = null;
	protected $rest_controller = null;
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
		add_action('wp_loaded', array($this, 'wp_loaded'), 15);
		add_action('rest_api_init', array($this, 'rest_api_init'), 10);
		//Run a little later than everyone else
		add_action('init', array($this, 'init'), 11);
		//Register the WordPress 2.8 Widget
		add_action('widgets_init', array($this, 'register_widget'));
		//Load our network admin if in the network dashboard (yes is_network_admin() doesn't exist)
		if(defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN)
		{
			require_once(dirname(__FILE__) . '/class.bcn_network_admin.php');
			//Instantiate our new admin object
			$this->admin = new bcn_network_admin($this->breadcrumb_trail, $this->plugin_basename);
		}
		//Load our main admin if in the dashboard, but only if we're not in the network dashboard (prevents goofy bugs)
		else if(is_admin() || defined('WP_UNINSTALL_PLUGIN'))
		{
			require_once(dirname(__FILE__) . '/class.bcn_admin.php');
			//Instantiate our new admin object
			$this->admin = new bcn_admin($this->breadcrumb_trail, $this->plugin_basename);
		}
	}
	public function init()
	{
		breadcrumb_navxt::setup_options($this->opt);
		if(!is_admin() || !isset($_POST[$this->unique_prefix . '_admin_reset']))
		{
			$this->get_settings(); //This breaks the reset options script, so only do it if we're not trying to reset the settings
		}
		add_filter('bcn_allowed_html', array($this, 'allowed_html'), 1, 1);
		//We want to run late for using our breadcrumbs
		add_filter('tha_breadcrumb_navigation', array($this, 'tha_compat'), 99);
		//Only include the REST API if enabled
		if(!defined('BCN_DISABLE_REST_API') || !BCN_DISABLE_REST_API)
		{
			require_once(dirname(__FILE__) . '/class.bcn_rest_controller.php');
			$this->rest_controller = new bcn_rest_controller($this->breadcrumb_trail, $this->unique_prefix);
		}
		//Register Guternberg
		$this->register_block();
	}
	public function rest_api_init()
	{
		add_filter('bcn_register_rest_endpoint', array($this, 'api_enable_for_block'), 10, 4);
	}
	public function register_widget()
	{
		return register_widget($this->unique_prefix . '_widget');
	}
	/**
	 * Server-side rendering for front-end block display
	 * 
	 * @param array $attributes Array of attributes set by the Gutenberg sidebar
	 * @return string The Breadcrumb Trail string
	 */
	public function render_block($attributes)
	{
		$extra_classs = '';
		if(isset($attributes['className']))
		{
			$extra_classs = esc_attr($attributes['className']);
		}
		return sprintf('<div class="breadcrumbs %2$s" typeof="BreadcrumbList" vocab="https://schema.org/">%1$s</div>', bcn_display(true), $extra_classs);
	}
	/**
	 * Handles registering the Breadcrumb Trail Gutenberg block
	 */
	public function register_block()
	{
		wp_register_script($this->unique_prefix . '-breadcrumb-trail-block-script', plugins_url('bcn_gutenberg_block.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-api'));
		if(function_exists('register_block_type'))
		{
			register_block_type( $this->unique_prefix . '/breadcrumb-trail', array(
				'editor_script' => $this->unique_prefix . '-breadcrumb-trail-block-script',
				'render_callback' => array($this, 'render_block')
				/*'editor_style' => ''/*,
				'style' => ''*/
			));
			if(function_exists('wp_set_script_translations'))
			{
				//Setup our translation strings
				wp_set_script_translations($this->unique_prefix . '-breadcrumb-trail-block-script', 'breadcrumb-navxt');
			}
			//Setup some bcn settings
			//TODO: New settings arch should make this easier
			wp_add_inline_script($this->unique_prefix . '-breadcrumb-trail-block-script',
					$this->unique_prefix . 'Opts = ' . json_encode(
							array(
									'bcurrent_item_linked' => $this->opt['bcurrent_item_linked'],
									'hseparator' => $this->opt['hseparator']
							)) . ';',
					'before');
		}
	}
	public function api_enable_for_block($register_rest_endpoint, $endpoint, $version, $methods)
	{
		//Enable if the current user can edit posts
		if(current_user_can('edit_posts') && $endpoint === 'post')
		{
			return true;
		}
		return $register_rest_endpoint;
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
						'itemtype' => true,
						'xmlns:v' => true,
						'typeof' => true,
						'property' => true,
						'vocab' => true,
						'translate' => true,
						'lang' => true,
						'bcn-aria-current' => true
					),
					'img' => array(
						'alt' => true,
						'align' => true,
						'height' => true,
						'width' => true,
						'src' => true,
						'srcset' => true,
						'sizes' => true,
						'id' => true,
						'class' => true,
						'aria-hidden' => true,
						'data-icon' => true,
						'itemref' => true,
						'itemid' => true,
						'itemprop' => true,
						'itemscope' => true,
						'itemtype' => true,
						'xmlns:v' => true,
						'typeof' => true,
						'property' => true,
						'vocab' => true,
						'lang' => true
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
						'itemtype' => true,
						'xmlns:v' => true,
						'typeof' => true,
						'property' => true,
						'vocab' => true,
						'translate' => true,
						'lang' => true
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
						'itemtype' => true,
						'xmlns:v' => true,
						'typeof' => true,
						'property' => true,
						'vocab' => true,
						'translate' => true,
						'lang' => true
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
						'itemtype' => true,
						'xmlns:v' => true,
						'typeof' => true,
						'property' => true,
						'vocab' => true,
						'translate' => true,
						'lang' => true
					),
					'meta' => array(
						'content' => true,
						'property' => true,
						'vocab' => true,
						'itemprop' => true
					)
		);
		return mtekk_adminKit::array_merge_recursive($tags, $allowed_html);
	}
	public function get_version()
	{
		return self::version;
	}
	public function wp_loaded()
	{

	}
	public function uninstall()
	{
		$this->admin->uninstall();
	}
	/**
	 * Sets up the extended options for any CPTs, taxonomies or extensions
	 * 
	 * @param array $opt The options array, passed by reference
	 */
	static public function setup_options(&$opt)
	{
		//Add custom post types
		breadcrumb_navxt::find_posttypes($opt);
		//Add custom taxonomy types
		breadcrumb_navxt::find_taxonomies($opt);
		//Let others hook into our settings
		$opt = apply_filters('bcn_settings_init', $opt);
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
				if(!isset($opts['bpost_' . $post_type->name . '_taxonomy_referer']))
				{
					//Default to not letting the refering page influence the referer
					$opts['bpost_' . $post_type->name . '_taxonomy_referer'] = false;
				}
				//If the post type does not have settings in the options array yet, we need to load some defaults
				if(!isset($opts['Hpost_' . $post_type->name . '_template']))
				{
					//Add the necessary option array members
					$opts['Hpost_' . $post_type->name . '_template'] = bcn_breadcrumb::get_default_template();
				}
				if(!isset($opts['Hpost_' . $post_type->name . '_template_no_anchor']))
				{
					$opts['Hpost_' . $post_type->name . '_template_no_anchor'] = bcn_breadcrumb::default_template_no_anchor;
				}
				if(!isset($opts['apost_' . $post_type->name . '_root']))
				{
					//Default to not showing a post_root
					$opts['apost_' . $post_type->name . '_root'] = 0;
				}
				if(!isset($opts['bpost_' . $post_type->name . '_hierarchy_display']))
				{
					//Default to not displaying a taxonomy
					$opts['bpost_' . $post_type->name . '_hierarchy_display'] = false;
				}
				if(!isset($opts['Spost_' . $post_type->name . '_hierarchy_type']))
				{
					if($post_type->has_archive == true || is_string($post_type->has_archive))
					{
						$opts['bpost_' . $post_type->name . '_archive_display'] = true;
					}
					else
					{
						$opts['bpost_' . $post_type->name . '_archive_display'] = false;		
					}
					if(!$post_type->hierarchical)
					{
						//Loop through all of the possible taxonomies
						foreach($wp_taxonomies as $taxonomy)
						{
							//Check for non-public taxonomies
							if(!apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name, $post_type->name))
							{
								continue;
							}
							//Activate the first taxonomy valid for this post type and exit the loop
							if($taxonomy->object_type == $post_type->name || in_array($post_type->name, $taxonomy->object_type))
							{
								$opts['bpost_' . $post_type->name . '_hierarchy_display'] = true;
								$opts['Spost_' . $post_type->name . '_hierarchy_type'] = $taxonomy->name;
								break;
							}
						}
					}
					else
					{
						$opts['bpost_' . $post_type->name . '_hierarchy_display'] = true;
						$opts['Spost_' . $post_type->name . '_hierarchy_type'] = 'BCN_PARENT';
					}
					//If there are no valid taxonomies for this type, setup our defaults
					if(!isset($opts['Spost_' . $post_type->name . '_hierarchy_type']))
					{
						$opts['Spost_' . $post_type->name . '_hierarchy_type'] = 'BCN_DATE';
					}
					//Run through some filters, allowing extensions to directly influence the default hierarchy selection/display
					$opts['Spost_' . $post_type->name . '_hierarchy_type'] = apply_filters('bcn_default_hierarchy_type', $opts['Spost_' . $post_type->name . '_hierarchy_type'], $post_type->name);
					$opts['bpost_' . $post_type->name . '_hierarchy_display'] = apply_filters('bcn_default_hierarchy_display', $opts['bpost_' . $post_type->name . '_hierarchy_display'], $post_type->name, $opts['Spost_' . $post_type->name . '_hierarchy_type']);
				}
				//New for 6.2
				if(!isset($opts['bpost_' . $post_type->name . '_hierarchy_parent_first']))
				{
					$opts['bpost_' . $post_type->name . '_hierarchy_parent_first'] = false;
					$opts['bpost_' . $post_type->name . '_hierarchy_parent_first'] = apply_filters('bcn_default_hierarchy_parent_first', $opts['bpost_' . $post_type->name . '_hierarchy_parent_first'], $post_type->name);
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
				if(!isset($opts['Htax_' . $taxonomy->name . '_template']))
				{
					//Add the necessary option array members
					$opts['Htax_' . $taxonomy->name . '_template'] = __(sprintf('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the %%title%% %s archives." href="%%link%%" class="%%type%%" bcn-aria-current><span property="name">%%htitle%%</span></a><meta property="position" content="%%position%%"></span>', $taxonomy->labels->singular_name), 'breadcrumb-navxt');
					$opts['Htax_' . $taxonomy->name . '_template_no_anchor'] = __(sprintf('<span property="itemListElement" typeof="ListItem"><span property="name">%%htitle%%</span><meta property="position" content="%%position%%"></span>', $taxonomy->labels->singular_name), 'breadcrumb-navxt');
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
		//Grab the current settings for the current local site from the db
		$this->breadcrumb_trail->opt = wp_parse_args(get_option('bcn_options'), $this->opt);
		//If we're in multisite mode, look at the three BCN_SETTINGS globals
		if(is_multisite())
		{
			if(defined('BCN_SETTINGS_USE_NETWORK') && BCN_SETTINGS_USE_NETWORK)
			{
				//Grab the current network wide settings
				$this->breadcrumb_trail->opt = wp_parse_args(get_site_option('bcn_options'), $this->opt);
			}
			else if(defined('BCN_SETTINGS_FAVOR_LOCAL') && BCN_SETTINGS_FAVOR_LOCAL)
			{
				//Grab the current settings for the current local site from the db
				$this->breadcrumb_trail->opt = wp_parse_args(get_option('bcn_options'), $this->breadcrumb_trail->opt);
			}
			else if(defined('BCN_SETTINGS_FAVOR_NETWORK') && BCN_SETTINGS_FAVOR_NETWORK)
			{
				//Grab the current settings from the db
				$this->breadcrumb_trail->opt = wp_parse_args(get_site_option('bcn_options'), get_option('bcn_options'));
			}
		}
		//Currently only support using post_parent for the page hierarchy
		$this->breadcrumb_trail->opt['bpost_page_hierarchy_display'] = true;
		$this->breadcrumb_trail->opt['bpost_page_hierarchy_parent_first'] = true;
		$this->breadcrumb_trail->opt['Spost_page_hierarchy_type'] = 'BCN_POST_PARENT';
		$this->breadcrumb_trail->opt['apost_page_root'] = get_option('page_on_front');
		//This one isn't needed as it is performed in bcn_breadcrumb_trail::fill(), it's here for completeness only
		$this->breadcrumb_trail->opt['apost_post_root'] = get_option('page_for_posts');
		
		//Loop through all of the post types in the array, migrate automatically if necessary
		foreach($GLOBALS['wp_post_types'] as $post_type)
		{
			if(isset($this->opt['Spost_' . $post_type->name . '_taxonomy_type']))
			{
				$this->opt['Spost_' . $post_type->name . '_hierarchy_type'] = $this->opt['Spost_' . $post_type->name . '_taxonomy_type'];
				unset($this->opt['Spost_' . $post_type->name . '_taxonomy_type']);
			}
			if(isset($this->opt['Spost_' . $post_type->name . '_taxonomy_display']))
			{
				$this->opt['Spost_' . $post_type->name . '_hierarchy_display'] = $this->opt['Spost_' . $post_type->name . '_taxonomy_display'];
				unset($this->opt['Spost_' . $post_type->name . '_taxonomy_display']);
			}
		}
	}
	/**
	 * Outputs the breadcrumb trail
	 * 
	 * @param bool $return Whether to return or echo the trail.
	 * @param bool $linked Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse Whether to reverse the output or not.
	 * @param bool $force Whether or not to force the fill function to run.
	 * @param string $template The template to use for the string output.
	 * 
	 * @return void Void if Option to print out breadcrumb trail was chosen.
	 * @return string String-Data of breadcrumb trail.
	 */
	public function display($return = false, $linked = true, $reverse = false, $force = false, $template = '%1$s%2$s')
	{
		//If we're being forced to fill the trail, clear it before calling fill
		if($force)
		{
			$this->breadcrumb_trail->breadcrumbs = array();
		}
		//Generate the breadcrumb trail
		$this->breadcrumb_trail->fill();
		$trail_string = $this->breadcrumb_trail->display($linked, $reverse, $template);
		if($return)
		{
			return $trail_string;
		}
		else
		{
			//Helps track issues, please don't remove it
			$credits = "<!-- Breadcrumb NavXT " . $this::version . " -->\n";
			echo $credits . $trail_string;
		}
	}
	/**
	 * Outputs the breadcrumb trail with each element encapsulated with li tags
	 * 
	 * @deprecated 6.0.0 No longer needed, superceeded by $template parameter in display
	 * 
	 * @param bool $return Whether to return or echo the trail.
	 * @param bool $linked Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse Whether to reverse the output or not.
	 * @param bool $force Whether or not to force the fill function to run.
	 * 
	 * @return void Void if Option to print out breadcrumb trail was chosen.
	 * @return string String-Data of breadcrumb trail.
	 */
	public function display_list($return = false, $linked = true, $reverse = false, $force = false)
	{
		_deprecated_function( __FUNCTION__, '6.0', 'breadcrumb_navxt::display');
		return $this->display($return, $linked, $reverse, $force, "<li%3\$s>%1\$s</li>\n");
	}
	/**
	 * Outputs the breadcrumb trail in Schema.org BreadcrumbList compatible JSON-LD
	 * 
	 * @param bool $return Whether to return or echo the trail.
	 * @param bool $reverse Whether to reverse the output or not.
	 * @param bool $force Whether or not to force the fill function to run.
	 * 
	 * @return void Void if Option to print out breadcrumb trail was chosen.
	 * @return string String-Data of breadcrumb trail.
	 */
	public function display_json_ld($return = false, $reverse = false, $force = false)
	{
		//If we're being forced to fill the trail, clear it before calling fill
		if($force)
		{
			$this->breadcrumb_trail->breadcrumbs = array();
		}
		//Generate the breadcrumb trail
		$this->breadcrumb_trail->fill();
		$trail_string = json_encode($this->breadcrumb_trail->display_json_ld($reverse), JSON_UNESCAPED_SLASHES);
		if($return)
		{
			return $trail_string;
		}
		else
		{
			echo $trail_string;
		}
	}
}
//Have to bootstrap our startup so that other plugins can replace the bcn_breadcrumb_trail object if they need to
add_action('plugins_loaded', 'bcn_init', 15);
function bcn_init()
{
	global $breadcrumb_navxt;
	//Create an instance of bcn_breadcrumb_trail
	$bcn_breadcrumb_trail = new bcn_breadcrumb_trail();
	//Let's make an instance of our object that takes care of everything
	$breadcrumb_navxt = new breadcrumb_navxt(apply_filters('bcn_breadcrumb_trail_object', $bcn_breadcrumb_trail));
}
/**
 * Outputs the breadcrumb trail
 * 
 * @param bool $return Whether to return or echo the trail. (optional)
 * @param bool $linked Whether to allow hyperlinks in the trail or not. (optional)
 * @param bool $reverse Whether to reverse the output or not. (optional)
 * @param bool $force Whether or not to force the fill function to run. (optional)
 * 
 * @return void Void if Option to print out breadcrumb trail was chosen.
 * @return string String-Data of breadcrumb trail.
 */
function bcn_display($return = false, $linked = true, $reverse = false, $force = false)
{
	global $breadcrumb_navxt;
	if($breadcrumb_navxt !== null)
	{
		return $breadcrumb_navxt->display($return, $linked, $reverse, $force);
	}
}
/**
 * Outputs the breadcrumb trail with each element encapsulated with li tags
 * 
 * @param bool $return Whether to return or echo the trail. (optional)
 * @param bool $linked Whether to allow hyperlinks in the trail or not. (optional)
 * @param bool $reverse Whether to reverse the output or not. (optional)
 * @param bool $force Whether or not to force the fill function to run. (optional)
 * 
 * @return void Void if Option to print out breadcrumb trail was chosen.
 * @return string String-Data of breadcrumb trail.
 */
function bcn_display_list($return = false, $linked = true, $reverse = false, $force = false)
{
	global $breadcrumb_navxt;
	if($breadcrumb_navxt !== null)
	{
		return $breadcrumb_navxt->display($return, $linked, $reverse, $force, "<li%3\$s>%1\$s</li>\n");
	}
}
/**
 * Outputs the breadcrumb trail in Schema.org BreadcrumbList compatible JSON-LD
 * 
 * @param bool $return Whether to return or echo the trail. (optional)
 * @param bool $reverse Whether to reverse the output or not. (optional)
 * @param bool $force Whether or not to force the fill function to run. (optional)
 * 
 * @return void Void if Option to print out breadcrumb trail was chosen.
 * @return string String-Data of breadcrumb trail.
 */
function bcn_display_json_ld($return = false, $reverse = false, $force = false)
{
	global $breadcrumb_navxt;
	if($breadcrumb_navxt !== null)
	{
		return $breadcrumb_navxt->display_json_ld($return, $reverse, $force);
	}
}

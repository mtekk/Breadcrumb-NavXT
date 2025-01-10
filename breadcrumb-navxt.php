<?php
/*
Plugin Name: Breadcrumb NavXT
Plugin URI: http://mtekk.us/code/breadcrumb-navxt/
Description: Adds a breadcrumb navigation showing the visitor&#39;s path to their current location. For details on how to use this plugin visit <a href="http://mtekk.us/code/breadcrumb-navxt/">Breadcrumb NavXT</a>. 
Version: 7.4.1
Author: John Havlik
Author URI: http://mtekk.us/
License: GPL2
Text Domain: breadcrumb-navxt
Domain Path: /languages
*/
/*
	Copyright 2007-2025  John Havlik  (email : john.havlik@mtekk.us)

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
//Do a PHP version check, require 5.6 or newer
if(version_compare(phpversion(), '5.6.0', '<'))
{
	//Only purpose of this function is to echo out the PHP version error
	function bcn_phpold()
	{
		printf('<div class="notice notice-error"><p>' . esc_html__('Your PHP version is too old, please upgrade to a newer version. Your version is %1$s, Breadcrumb NavXT requires %2$s', 'breadcrumb-navxt') . '</p></div>', phpversion(), '5.6.0');
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
if(!class_exists('\mtekk\adminKit\adminKit'))
{
	require_once(dirname(__FILE__) . '/includes/adminKit/class-mtekk_adminkit.php');
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
use mtekk\adminKit\adminKit as adminKit;
use mtekk\adminKit\setting;
$breadcrumb_navxt = null;
//TODO change to extends \mtekk\plugKit
class breadcrumb_navxt
{
	const version = '7.4.1';
	protected $name = 'Breadcrumb NavXT';
	protected $identifier = 'breadcrumb-navxt';
	protected $unique_prefix = 'bcn';
	protected $plugin_basename = null;
	protected $opt = null;
	protected $settings = array();
	protected $breadcrumb_trail = null;
	protected $admin = null;
	protected $rest_controller = null;
	/**
	 * Constructor for a new breadcrumb_navxt object
	 *
	 */
	public function __construct()
	{
		//We set the plugin basename here
		$this->plugin_basename = plugin_basename(__FILE__);
		add_action('rest_api_init', array($this, 'rest_api_init'), 10);
		//Run much later than everyone else to give other plugins a chance to hook into the filters and actions in this
		add_action('init', array($this, 'init'), 9000);
		//Register the WordPress 2.8 Widget
		add_action('widgets_init', array($this, 'register_widget'));
	}
	public function init()
	{
		//Create an instance of bcn_breadcrumb_trail
		$bcn_breadcrumb_trail = new bcn_breadcrumb_trail();
		//Allow others to swap out the breadcrumb trail object
		$this->breadcrumb_trail = apply_filters('bcn_breadcrumb_trail_object', $bcn_breadcrumb_trail);
		add_filter('bcn_allowed_html', array($this, 'allowed_html'), 1, 1);
		add_filter('mtekk_adminkit_allowed_html', array($this, 'adminkit_allowed_html'), 1, 1);
		//We want to run late for using our breadcrumbs
		add_filter('tha_breadcrumb_navigation', array($this, 'tha_compat'), 99);
		//Only include the REST API if enabled
		if(!defined('BCN_DISABLE_REST_API') || !BCN_DISABLE_REST_API)
		{
			require_once(dirname(__FILE__) . '/class.bcn_rest_controller.php');
			$this->rest_controller = new bcn_rest_controller($this->breadcrumb_trail, $this->unique_prefix);
		}
		breadcrumb_navxt::setup_setting_defaults($this->settings);
		if(!is_admin() || (!isset($_POST[$this->unique_prefix . '_admin_reset']) && !isset($_POST[$this->unique_prefix . '_admin_options'])))
		{
			$this->get_settings(); //This breaks the reset options script, so only do it if we're not trying to reset the settings
		}
		//Register Guternberg Block
		$this->register_block();
		//Load our network admin if in the network dashboard (yes is_network_admin() doesn't exist)
		if(defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN)
		{
			require_once(dirname(__FILE__) . '/class.bcn_network_admin.php');
			//Instantiate our new admin object
			$this->admin = new bcn_network_admin($this->breadcrumb_trail->opt, $this->plugin_basename, $this->settings);
		}
		//Load our main admin if in the dashboard, but only if we're not in the network dashboard (prevents goofy bugs)
		else if(is_admin() || defined('WP_UNINSTALL_PLUGIN'))
		{
			require_once(dirname(__FILE__) . '/class.bcn_admin.php');
			//Instantiate our new admin object
			$this->admin = new bcn_admin($this->breadcrumb_trail->opt, $this->plugin_basename, $this->settings);
		}
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
	 * Handles registering the Breadcrumb Trail Gutenberg block
	 */
	public function register_block()
	{
		if(function_exists('register_block_type'))
		{
			register_block_type( dirname(__FILE__) . '/includes/blocks/build/breadcrumb-trail');
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
	public function adminkit_allowed_html($tags)
	{
		//Hoop through normal allowed_html filters
		return apply_filters('bcn_allowed_html', $tags);
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
		if(!is_array($tags))
		{
			$tags = array();
		}
		return adminKit::array_merge_recursive($tags, $allowed_html);
	}
	public function get_version()
	{
		return self::version;
	}
	public function uninstall()
	{
		$this->admin->uninstall();
	}
	static function setup_setting_defaults(array &$settings)
	{
		//Hook for letting other plugins add in their default settings (has to go first to prevent other from overriding base settings)
		$settings = apply_filters('bcn_settings_init', $settings);
		//Now on to our settings
		$settings['bmainsite_display'] = new setting\setting_bool(
				'mainsite_display',
				true,
				__('Main Site Breadcrumb', 'breadcrumb-navxt'));
		$settings['Hmainsite_template'] = new setting\setting_html(
				'mainsite_template',
				bcn_breadcrumb::get_default_template(),
				__('Main Site Home Template', 'breadcrumb-navxt'));
		$settings['Hmainsite_template_no_anchor'] = new setting\setting_html(
				'mainsite_template_no_anchor',
				bcn_breadcrumb::default_template_no_anchor,
				__('Main Site Home Template (Unlinked)', 'breadcrumb-navxt'));
		$settings['bhome_display'] = new setting\setting_bool(
				'home_display',
				true,
				__('Home Breadcrumb', 'breadcrumb-navxt'));
		$settings['Hhome_template'] = new setting\setting_html(
				'home_template',
				(isset($settings['Hhome_template']) && is_string($settings['Hhome_template'])) ? $settings['Hhome_template'] : bcn_breadcrumb::get_default_template(),
				__('Home Template', 'breadcrumb-navxt'));
		$settings['Hhome_template_no_anchor'] = new setting\setting_html(
				'home_template_no_anchor',
				(isset($settings['Hhome_template_no_anchor']) && is_string($settings['Hhome_template_no_anchor'])) ? $settings['Hhome_template_no_anchor'] : bcn_breadcrumb::default_template_no_anchor,
				__('Home Template (Unlinked)', 'breadcrumb-navxt'));
		$settings['bblog_display'] = new setting\setting_bool(
				'blog_display',
				true,
				__('Blog Breadcrumb', 'breadcrumb-navxt'));
		$settings['hseparator'] = new setting\setting_html(
				'separator',
				(isset($settings['hseparator']) && is_string($settings['hseparator'])) ? $settings['hseparator'] : ' &gt; ',
				__('Breadcrumb Separator', 'breadcrumb-navxt'),
				true);
		$settings['hseparator_higher_dim'] = new setting\setting_html(
				'separator_higher_dim',
				(isset($settings['hseparator_higher_dim']) && is_string($settings['hseparator_higher_dim'])) ? $settings['hseparator_higher_dim'] : ', ',
				__('Breadcrumb Separator (Higher Dimension)', 'breadcrumb-navxt'),
				true);
		$settings['bcurrent_item_linked'] = new setting\setting_bool(
				'current_item_linked',
				false,
				__('Link Current Item', 'breadcrumb-navxt'));
		$settings['Hpaged_template'] = new setting\setting_html(
				'paged_template',
				sprintf('<span class="%%type%%">%1$s</span>', esc_attr__('Page %htitle%', 'breadcrumb-navxt')),
				_x('Paged Template', 'Paged as in when on an archive or post that is split into multiple pages', 'breadcrumb-navxt'));
		$settings['bpaged_display'] = new setting\setting_bool(
				'paged_display',
				false,
				_x('Paged Breadcrumb', 'Paged as in when on an archive or post that is split into multiple pages', 'breadcrumb-navxt'));
		//Post types
		foreach($GLOBALS['wp_post_types'] as $post_type)
		{
			//If we somehow end up with the WP_Post_Types array having a non-WP_Post_Type object, we should skip it
			if(!($post_type instanceof WP_Post_Type))
			{
				continue;
			}
			$settings['Hpost_' . $post_type->name . '_template'] = new setting\setting_html(
					'post_' . $post_type->name . '_template',
					bcn_breadcrumb::get_default_template(),
					sprintf(__('%s Template', 'breadcrumb-navxt'), $post_type->labels->singular_name));
			$settings['Hpost_' . $post_type->name . '_template_no_anchor'] = new setting\setting_html(
					'post_' . $post_type->name . '_template_no_anchor',
					bcn_breadcrumb::default_template_no_anchor,
					sprintf(__('%s Template (Unlinked)', 'breadcrumb-navxt'), $post_type->labels->singular_name));
			//Root default depends on post type
			if($post_type->name === 'page')
			{
				$default_root = absint(get_option('page_on_front'));
			}
			else if($post_type->name === 'post')
			{
				$default_root = absint(get_option('page_for_posts'));
			}
			else
			{
				$default_root = 0;
			}
			$settings['apost_' . $post_type->name . '_root'] = new setting\setting_absint(
					'post_' . $post_type->name . '_root',
					$default_root,
					sprintf(__('%s Root Page', 'breadcrumb-navxt'), $post_type->labels->singular_name));
			//Archive display default depends on post type
			if($post_type->has_archive == true || is_string($post_type->has_archive))
			{
				$default_archive_display = true;
			}
			else
			{
				$default_archive_display = false;
			}
			$settings['bpost_' . $post_type->name . '_archive_display'] = new setting\setting_bool(
					'post_' . $post_type->name . '_archive_display',
					$default_archive_display,
					sprintf(__('%s Archive Display', 'breadcrumb-navxt'), $post_type->labels->singular_name));
			$settings['bpost_' . $post_type->name . '_taxonomy_referer'] = new setting\setting_bool(
					'post_' . $post_type->name . '_taxonomy_referer',
					false,
					sprintf(__('%s Hierarchy Referer Influence', 'breadcrumb-navxt'), $post_type->labels->singular_name));
			//Hierarchy use parent first depends on post type
			if(in_array($post_type->name, array('page', 'post')))
			{
				$default_parent_first = false;
			}
			else if($post_type->name === 'attachment')
			{
				$default_parent_first = true;
			}
			else
			{
				$default_parent_first = apply_filters('bcn_default_hierarchy_parent_first', false, $post_type->name);
			}
			$settings['bpost_' . $post_type->name . '_hierarchy_parent_first'] = new setting\setting_bool(
					'post_' . $post_type->name . '_hierarchy_parent_first',
					$default_parent_first,
					sprintf(__('%s Hierarchy Use Parent First', 'breadcrumb-navxt'), $post_type->labels->singular_name));
			//Hierarchy depends on post type
			if($post_type->name === 'page')
			{
				$hierarchy_type_allowed_values = array('BCN_POST_PARENT');
				$hierarchy_type_default = 'BCN_POST_PARENT';
				$default_hierarchy_display = true;
			}
			else
			{
				$hierarchy_type_allowed_values = array('BCN_POST_PARENT', 'BCN_DATE');
				$hierarchy_type_default = 'BCN_POST_PARENT';
				$default_hierarchy_display = false;
				//Loop through all of the possible taxonomies
				foreach($GLOBALS['wp_taxonomies'] as $taxonomy)
				{
					//Check for non-public taxonomies
					if(!apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name, $post_type->name))
					{
						continue;
					}
					//Add valid taxonomies to list
					if($taxonomy->object_type == $post_type->name || in_array($post_type->name, $taxonomy->object_type))
					{
						$hierarchy_type_allowed_values[] = $taxonomy->name;
						$default_hierarchy_display = true;
						//Only change from default on first valid taxonomy, if not a hierarchcial post type
						if($hierarchy_type_default === 'BCN_POST_PARENT')
						{
							$hierarchy_type_default = $taxonomy->name;
						}
					}
				}
				//For hierarchical post types and attachments, override whatever we may have done in the taxonomy finding
				if($post_type->hierarchical === true || $post_type->name === 'attachment')
				{
					$default_hierarchy_display = true;
					$hierarchy_type_default = 'BCN_POST_PARENT';
				}
			}
			$settings['bpost_' . $post_type->name . '_hierarchy_display'] = new setting\setting_bool(
					'post_' . $post_type->name . '_hierarchy_display',
					$default_hierarchy_display,
					sprintf(__('%s Hierarchy Display', 'breadcrumb-navxt'), $post_type->labels->singular_name));
			$settings['Epost_' . $post_type->name . '_hierarchy_type'] = new setting\setting_enum(
					'post_' . $post_type->name . '_hierarchy_type',
					$hierarchy_type_default,
					sprintf(__('%s Hierarchy Referer Influence', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false,
					false,
					$hierarchy_type_allowed_values);
		}
		//Taxonomies
		foreach($GLOBALS['wp_taxonomies']as $taxonomy)
		{
			$settings['Htax_' . $taxonomy->name. '_template'] = new setting\setting_html(
					'tax_' . $taxonomy->name. '_template',
					__(sprintf('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the %%title%% %s archives." href="%%link%%" class="%%type%%" bcn-aria-current><span property="name">%%htitle%%</span></a><meta property="position" content="%%position%%"></span>', $taxonomy->labels->singular_name), 'breadcrumb-navxt'),
					sprintf(__('%s Template', 'breadcrumb-navxt'), $taxonomy->labels->singular_name));
			$settings['Htax_' . $taxonomy->name. '_template_no_anchor'] = new setting\setting_html(
					'tax_' . $taxonomy->name. '_template_no_anchor',
					bcn_breadcrumb::default_template_no_anchor,
					sprintf(__('%s Template (Unlinked)', 'breadcrumb-navxt'), $taxonomy->labels->singular_name));
		}
		//Miscellaneous
		$settings['H404_template'] = new setting\setting_html(
				'404_template',
				bcn_breadcrumb::get_default_template(),
				__('404 Template', 'breadcrumb-navxt'));
		$settings['S404_title'] = new setting\setting_string(
				'404_title',
				__('404', 'breadcrumb-navxt'),
				__('404 Title', 'breadcrumb-navxt'));
		$settings['Hsearch_template'] = new setting\setting_html(
				'search_template',
				sprintf('<span property="itemListElement" typeof="ListItem"><span property="name">%1$s</span><meta property="position" content="%%position%%"></span>',
						sprintf(esc_attr__('Search results for &#39;%1$s&#39;', 'breadcrumb-navxt'),
								sprintf('<a property="item" typeof="WebPage" title="%1$s" href="%%link%%" class="%%type%%" bcn-aria-current>%%htitle%%</a>', esc_attr__('Go to the first page of search results for %title%.', 'breadcrumb-navxt')))),
				__('Search Template', 'breadcrumb-navxt'));
		$settings['Hsearch_template_no_anchor'] = new setting\setting_html(
				'search_template_no_anchor',
				sprintf('<span class="%%type%%">%1$s</span>',
						sprintf(esc_attr__('Search results for &#39;%1$s&#39;', 'breadcrumb-navxt'), '%htitle%')),
				__('Search Template (Unlinked)', 'breadcrumb-navxt'));
		$settings['Hdate_template'] = new setting\setting_html(
				'date_template',
				sprintf('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="%1$s" href="%%link%%" class="%%type%%" bcn-aria-current><span property="name">%%htitle%%</span></a><meta property="position" content="%%position%%"></span>', esc_attr__('Go to the %title% archives.', 'breadcrumb-navxt')),
				__('Date Template', 'breadcrumb-navxt'));
		$settings['Hdate_template_no_anchor'] = new setting\setting_html(
				'date_template_no_anchor',
				bcn_breadcrumb::default_template_no_anchor,
				__('Date Template (Unlinked)', 'breadcrumb-navxt'));
		$settings['Hauthor_template'] = new setting\setting_html(
				'author_template',
				sprintf('<span property="itemListElement" typeof="ListItem"><span property="name">%1$s</span><meta property="position" content="%%position%%"></span>',
						sprintf(esc_attr__('Articles by: %1$s', 'breadcrumb-navxt'),
								sprintf('<a title="%1$s" href="%%link%%" class="%%type%%" bcn-aria-current>%%htitle%%</a>', esc_attr__('Go to the first page of posts by %title%.', 'breadcrumb-navxt')))),
				__('Author Template', 'breadcrumb-navxt'));
		$settings['Hauthor_template_no_anchor'] = new setting\setting_html(
				'author_template_no_anchor',
				sprintf('<span class="%%type%%">%1$s</span>',
						sprintf(esc_attr__('Articles by: %1$s', 'breadcrumb-navxt'), '%htitle%')),
				__('Author Template (Unlinked)', 'breadcrumb-navxt'));
		$settings['aauthor_root'] = new setting\setting_absint(
				'author_root',
				0,
				__('Author Root Page', 'breadcrumb-navxt'));
		$settings['Eauthor_name'] = new setting\setting_enum(
				'author_name',
				'display_name',
				__('Author Display Format', 'breadcrumb-navxt'),
				false,
				false,
				array('display_name', 'nickname', 'first_name', 'last_name'));
		/**
		 * Here are some deprecated settings
		 */
		$settings['blimit_title'] = new setting\setting_bool(
				'limit_title',
				false,
				__('Limit Title Length', 'breadcrumb-navxt'),
				false,
				true);
		$settings['amax_title_length'] = new setting\setting_absint(
				'max_title_length',
				30,
				__('Maximum Title Length', 'breadcrumb-navxt'),
				false,
				true);
	}
	/**
	 * Sets up the extended options for any CPTs, taxonomies or extensions
	 * 
	 * @param array $opt The options array, passed by reference
	 * @deprecated 7.0
	 */
	static public function setup_options(&$opt)
	{
		//Do nothing by default, deprecated and keeping just for compatibility
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
	public function show_paged()
	{
		return $this->settings['bpaged_display']->get_value();
	}
	public function _display_post($post, $return = false, $linked = true, $reverse = false, $force = false, $template = '%1$s%2$s', $outer_template = '%1$s')
	{
		if($post instanceof WP_Post)
		{
			//If we're being forced to fill the trail, clear it before calling fill
			if($force)
			{
				$this->breadcrumb_trail->breadcrumbs = array();
			}
			//Generate the breadcrumb trail
			$this->breadcrumb_trail->fill_REST($post);
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
	}
	/**
	 * Function updates the breadcrumb_trail options array from the database in a semi intellegent manner
	 * 
	 * @since  5.0.0
	 */
	private function get_settings()
	{
		//Convert our settings to opts
		$opts = adminKit::settings_to_opts($this->settings);
		//Run setup_options for compatibilty reasons
		breadcrumb_navxt::setup_options($opts);
		//TODO: Unit tests needed to ensure the expected behavior exists
		//Grab the current settings for the current local site from the db
		$this->breadcrumb_trail->opt = wp_parse_args(get_option('bcn_options'), $opts);
		//If we're in multisite mode, look at the three BCN_SETTINGS globals
		if(is_multisite())
		{
			$multisite_opts = wp_parse_args(get_site_option('bcn_options'), $opts);
			if(defined('BCN_SETTINGS_USE_NETWORK') && BCN_SETTINGS_USE_NETWORK)
			{
				//Grab the current network wide settings
				$this->breadcrumb_trail->opt = $multisite_opts;
			}
			else if(defined('BCN_SETTINGS_FAVOR_LOCAL') && BCN_SETTINGS_FAVOR_LOCAL)
			{
				//Grab the current local site settings and merge into network site settings + defaults
				$this->breadcrumb_trail->opt = wp_parse_args(get_option('bcn_options'), $multisite_opts);
			}
			else if(defined('BCN_SETTINGS_FAVOR_NETWORK') && BCN_SETTINGS_FAVOR_NETWORK)
			{
				//Grab the current network site settings and merge into local site settings + defaults
				$this->breadcrumb_trail->opt = wp_parse_args(get_site_option('bcn_options'), $this->breadcrumb_trail->opt);
			}
		}
		//Currently only support using post_parent for the page hierarchy
		$this->breadcrumb_trail->opt['bpost_page_hierarchy_display'] = true;
		$this->breadcrumb_trail->opt['bpost_page_hierarchy_parent_first'] = true;
		$this->breadcrumb_trail->opt['Epost_page_hierarchy_type'] = 'BCN_POST_PARENT';
		$this->breadcrumb_trail->opt['apost_page_root'] = get_option('page_on_front');
		//This one isn't needed as it is performed in bcn_breadcrumb_trail::fill(), it's here for completeness only
		$this->breadcrumb_trail->opt['apost_post_root'] = get_option('page_for_posts');
	}
	/**
	 * Outputs the breadcrumb trail
	 * 
	 * @param bool $return Whether to return or echo the trail.
	 * @param bool $linked Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse Whether to reverse the output or not.
	 * @param bool $force Whether or not to force the fill function to run.
	 * @param string $template The template to use for the string output.
	 * @param string $outer_template The template to place an entire dimension of the trail into for all dimensions higher than 1.
	 * 
	 * @return void Void if Option to print out breadcrumb trail was chosen.
	 * @return string String-Data of breadcrumb trail.
	 */
	public function display($return = false, $linked = true, $reverse = false, $force = false, $template = '%1$s%2$s', $outer_template = '%1$s')
	{
		//If we're being forced to fill the trail, clear it before calling fill
		if($force)
		{
			$this->breadcrumb_trail->breadcrumbs = array();
		}
		//Generate the breadcrumb trail
		$this->breadcrumb_trail->fill($force);
		$trail_string = $this->breadcrumb_trail->display($linked, $reverse, $template, $outer_template);
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
		$this->breadcrumb_trail->fill($force);
		$trail_string = json_encode($this->breadcrumb_trail->display_json_ld($reverse), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
	$breadcrumb_navxt = new breadcrumb_navxt();
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
		return $breadcrumb_navxt->display($return, $linked, $reverse, $force, "<li%3\$s>%1\$s</li>\n", "<ul>%1\$s</ul>\n");
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

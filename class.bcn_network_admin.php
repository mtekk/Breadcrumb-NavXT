<?php
/*
	Copyright 2015-2022  John Havlik  (email : john.havlik@mtekk.us)

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
//Include admin base class
if(!class_exists('bcn_admin'))
{
	require_once(dirname(__FILE__) . '/class.bcn_admin.php');
}
use mtekk\adminKit\{adminKit, form, message, setting};
/**
 * The administrative interface class 
 * 
 */
class bcn_network_admin extends bcn_admin
{
	const version = '7.0.2';
	protected $full_name = 'Breadcrumb NavXT Network Settings';
	protected $access_level = 'manage_network_options';
	/**
	 * Administrative interface class default constructor
	 * @param bcn_breadcrumb_trail $breadcrumb_trail a breadcrumb trail object
	 * @param string $basename The basename of the plugin
	 */
	function __construct(array &$opts, $basename, array &$settings)
	{
		//We're going to make sure we load the parent's constructor
		parent::__construct($opts, $basename, $settings);
		//Change to the proper name
		$this->full_name = __('Breadcrumb NavXT Network Settings', 'breadcrumb-navxt');
		//Remove the hook added by the parent as we don't want this classes settings page everywhere
		remove_action('admin_menu', array($this, 'add_page'));
		//Replace with the network_admin hook
		add_action('network_admin_menu', array($this, 'add_page'));
	}
	/**
	 * admin initialization callback function
	 * 
	 * is bound to wordpress action 'admin_init' on instantiation
	 * 
	 * @since  3.2.0
	 * @return void
	 */
	function init()
	{
		//We're going to make sure we run the parent's version of this function as well
		parent::init();
	}
	function wp_loaded()
	{
		parent::wp_loaded();
	}
	/**
	 * Return the URL of the settings page for the plugin
	 */
	function admin_url()
	{
		return admin_url('network/settings.php?page=' . $this->identifier);
	}
	/**
	 * Adds the adminpage the menu and the nice little settings link
	 */
	function add_page()
	{
		//Add the submenu page to "settings" menu
		$hookname = add_submenu_page('settings.php', $this->full_name, $this->short_name, $this->access_level, $this->identifier, array($this, 'admin_page'));
		// check capability of user to manage options (access control)
		if(current_user_can($this->access_level))
		{
			//Register admin_head-$hookname callback
			add_action('admin_head-' . $hookname, array($this, 'admin_head'));
			//Register admin_print_styles-$hookname callback
			add_action('admin_print_styles-' . $hookname, array($this, 'admin_styles'));
			//Register admin_print_scripts-$hookname callback
			add_action('admin_print_scripts-' . $hookname, array($this, 'admin_scripts'));
			//Register Help Output
			add_action('load-' . $hookname, array($this, 'help'));
		}
	}
	/**
	 * Have to hook into get_option and replace with network wide alternate
	 * 
	 * @param string $option The name of the option to retrieve
	 * @return mixed The value of the option
	 */
	function get_option($option)
	{
		return get_site_option($option);
	}
	/**
	 * Have to hook into update_option and replace with network wide alternate
	 * 
	 * @param string $option The name of the option to update
	 * @param mixed $newvalue The new value to set the option to
	 * 
	 */
	function update_option($option, $newvalue)
	{
		return update_site_option($option, $newvalue);
	}
	/**
	 * Have to hook into add_option and replace with network wide alternate
	 * 
	 * @param string $option The name of the option to update
	 * @param mixed $value The new value to set the option to
	 * @param null $deprecated Deprecated parameter
	 * @param string $autoload Whether or not to autoload the option, it's a string because WP is special
	 * 
	 */
	function add_option($option, $value = '', $deprecated = '', $autoload = 'yes')
	{
		return add_site_option($option, $value);
	}
	/**
	 * Have to hook into delete_option and replace with network wide alternate
	 * 
	 * @param string $option The name of the option to delete
	 */
	function delete_option($option)
	{
		return delete_site_option($option);
	}
	/**
	 * A message function that checks for the BCN_SETTINGS_* define statement
	 */
	function multisite_settings_warn()
	{
		if(is_multisite())
		{
			if(defined('BCN_SETTINGS_USE_LOCAL') && BCN_SETTINGS_USE_LOCAL)
			{
				$this->messages[] = new message(esc_html__('Warning: Individual site settings will override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_ns_isiteoveride');
			}
			else if(defined('BCN_SETTINGS_USE_NETWORK') && BCN_SETTINGS_USE_NETWORK)
			{
				
			}
			else if(defined('BCN_SETTINGS_FAVOR_LOCAL') && BCN_SETTINGS_FAVOR_LOCAL)
			{
				$this->messages[] = new message(esc_html__('Warning: Individual site settings may override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_ns_isitemayoveride');
			}
			else if(defined('BCN_SETTINGS_FAVOR_NETWORK') && BCN_SETTINGS_FAVOR_NETWORK)
			{
				$this->messages[] = new message(esc_html__('Warning: Individual site settings may override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_ns_nsmayoveride');
			}
			//Fall through if no settings mode was set
			else
			{
				$this->messages[] = new message(esc_html__('Warning: No BCN_SETTINGS_* define statement found, defaulting to BCN_SETTINGS_USE_LOCAL.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_ns_nosetting');
				$this->messages[] = new message(esc_html__('Warning: Individual site settings will override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_ns_isiteoveride');
			}
		}
	}
	/**
	 * A message function that checks for deprecated settings that are set and warns the user
	 */
	function deprecated_settings_warn()
	{
		parent::deprecated_settings_warn();
	}
	/**
	 * Function checks the current site to see if the blog options should be disabled
	 * 
	 * @return boool Whether or not the blog options should be disabled
	 */
	function maybe_disable_blog_options()
	{
		return false;
	}
	/**
	 * Function checks the current site to see if the mainsite options should be disabled
	 * 
	 * @return bool Whether or not the mainsite options should be disabled
	 */
	function maybe_disable_mainsite_options()
	{
		return false;
	}
}
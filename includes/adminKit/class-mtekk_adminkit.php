<?php
/*
	Copyright 2015-2023  John Havlik  (email : john.havlik@mtekk.us)

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
namespace mtekk\adminKit;
use mtekk\adminKit\setting\setting;

require_once( __DIR__ . '/../block_direct_access.php');
//Include message class
if(!class_exists('message'))
{
	require_once(__DIR__ . '/class-mtekk_adminkit_message.php');
}
if(version_compare(phpversion(), '8.0.0', '<'))
{
	//Include setting class
	if(!class_exists('setting\setting_bool'))
	{
		require_once(__DIR__ . '/setting/php7/class-mtekk_adminkit_setting_bool.php');
	}
	//Include setting class
	if(!class_exists('setting\setting_float'))
	{
		require_once(__DIR__ . '/setting/php7/class-mtekk_adminkit_setting_float.php');
	}
	//Include setting class
	if(!class_exists('setting\setting_int'))
	{
		require_once(__DIR__ . '/setting/php7/class-mtekk_adminkit_setting_int.php');
	}
}
else
{
	//Include setting class
	if(!class_exists('setting\setting_bool'))
	{
		require_once(__DIR__ . '/setting/class-mtekk_adminkit_setting_bool.php');
	}
	//Include setting class
	if(!class_exists('setting\setting_float'))
	{
		require_once(__DIR__ . '/setting/class-mtekk_adminkit_setting_float.php');
	}
	//Include setting class
	if(!class_exists('setting\setting_int'))
	{
		require_once(__DIR__ . '/setting/class-mtekk_adminkit_setting_int.php');
	}
}
//Include setting class
if(!class_exists('setting\setting_absint'))
{
	require_once(__DIR__ . '/setting/class-mtekk_adminkit_setting_absint.php');
}
//Include setting class
if(!class_exists('setting\setting_enum'))
{
	require_once(__DIR__ . '/setting/class-mtekk_adminkit_setting_enum.php');
}
//Include setting class
if(!class_exists('settingsetting_\html'))
{
	require_once(__DIR__ . '/setting/class-mtekk_adminkit_setting_html.php');
}
//Include setting class
if(!class_exists('setting\setting_string'))
{
	require_once(__DIR__ . '/setting/class-mtekk_adminkit_setting_string.php');
}
//Include from class
if(!class_exists('form'))
{
	require_once(__DIR__ . '/class-mtekk_adminkit_form.php');
}
abstract class adminKit
{
	const version = '3.1.1';
	protected $full_name;
	protected $short_name;
	protected $plugin_basename;
	protected $access_level = 'manage_options';
	protected $identifier;
	protected $unique_prefix;
	protected $opt = array();
	protected $messages;
	protected $message;
	protected $support_url;
	protected $allowed_html;
	protected $settings = array();
	protected $form;
	function __construct()
	{
		$this->message = array();
		$this->messages = array();
		//Admin Init Hook
		add_action('admin_init', array($this, 'init'));
		//WordPress Admin interface hook
		add_action('admin_menu', array($this, 'add_page'));
		//Installation Script hook
		add_action('activate_' . $this->plugin_basename, array($this, 'install'));
		//Initilizes l10n domain
		$this->local();
		add_action('wp_loaded', array($this, 'wp_loaded'));
		$this->form = new form($this->unique_prefix);
		//Register Help Output
		//add_action('add_screen_help_and_options', array($this, 'help'));
	}
	function wp_loaded()
	{
		//Filter our allowed html tags
		$this->allowed_html = apply_filters($this->unique_prefix . '_allowed_html', wp_kses_allowed_html('post'));
	}
	/**
	 * Returns the internal mtekk_admin_class version
	 */
	function get_admin_class_version()
	{
		return adminKit::version;
	}
	/**
	 * Checks if the administrator has the access capability, and adds it if they don't
	 */
	function add_cap()
	{
		$role = get_role('administrator');
		if($role instanceof \WP_Role && !$role->has_cap($this->access_level))
		{
			$role->add_cap($this->access_level);
		}
	}
	/**
	 * Return the URL of the settings page for the plugin
	 */
	function admin_url()
	{
		return admin_url('options-general.php?page=' . $this->identifier);
	}
	/**
	 * A wrapper for nonced_anchor returns a nonced anchor for admin pages
	 * 
	 * @param string $mode The nonce "mode", a unique string at the end of the standardized nonce identifier
	 * @param string $title (optional) The text to use in the title portion of the anchor
	 * @param string $text (optional) The text that will be surrounded by the anchor tags
	 * @return string the assembled anchor
	 */
	function admin_anchor($mode, $title = '', $text = '')
	{
		return $this->nonced_anchor($this->admin_url(), 'admin_' . $mode, 'true', $title, $text);
	}
	/**
	 * Returns a properly formed nonced anchor to the specified URI
	 * 
	 * @param string $uri The URI that the anchor should be for
	 * @param string $mode The nonce "mode", a unique string at the end of the standardized nonce identifier
	 * @param mixed $value (optional) The value to place in the query string 
	 * @param string $title (optional) The text to use in the title portion of the anchor
	 * @param string $text (optional) The text that will be surrounded by the anchor tags
	 * @param string $anchor_extras (optional) This text is placed within the opening anchor tag, good for adding id, classe, rel field
	 * @return string the assembled anchor
	 */
	function nonced_anchor($uri, $mode, $value = 'true', $title = '', $text = '', $anchor_extras = '')
	{
		//Assemble our url, nonce and all
		$url = wp_nonce_url(add_query_arg($this->unique_prefix . '_' . $mode, $value, $uri), $this->unique_prefix . '_' . $mode);
		//Return a valid anchor
		return ' <a title="' . esc_attr($title) . '" href="' . $url . '" '. $anchor_extras . '>' . esc_html($text) . '</a>';
	}
	/**
	 * Abstracts the check_admin_referer so that all the end user has to supply is the mode
	 * 
	 * @param string $mode The specific nonce "mode" (see nonced_anchor) that is being checked
	 */
	function check_nonce($mode)
	{
		check_admin_referer($this->unique_prefix . '_' . $mode);
	}
	/**
	 * Makes sure the current user can manage options to proceed
	 */
	function security()
	{
		//If the user can not manage options we will die on them
		if(!current_user_can($this->access_level))
		{
			wp_die(__('Insufficient privileges to proceed.', $this->identifier));
		}
	}
	function init()
	{
		$this->add_cap();
		//Admin Options reset hook
		if(isset($_POST[$this->unique_prefix . '_admin_reset']))
		{
			//Run the reset function on init if reset form has been submitted
			$this->opts_reset();
		}
		//Admin Settings export hook
		else if(isset($_POST[$this->unique_prefix . '_admin_settings_export']))
		{
			//Run the export function on init if export form has been submitted
			$this->settings_export();
		}
		//Admin Settings import hook
		else if(isset($_POST[$this->unique_prefix . '_admin_settings_import']) && isset($_FILES[$this->unique_prefix . '_admin_import_file']) && !empty($_FILES[$this->unique_prefix . '_admin_import_file']['name']))
		{
			//Run the import function on init if import form has been submitted
			$this->settings_import();
		}
		//Admin Options rollback hook
		else if(isset($_GET[$this->unique_prefix . '_admin_undo']))
		{
			//Run the rollback function on init if undo button has been pressed
			$this->opts_undo();
		}
		//Admin Options upgrade hook
		else if(isset($_GET[$this->unique_prefix . '_admin_upgrade']))
		{
			//Run the upgrade function on init if upgrade button has been pressed
			$this->opts_upgrade_wrapper();
		}
		//Admin Options fix hook
		else if(isset($_GET[$this->unique_prefix . '_admin_fix']))
		{
			//Run the options fix function on init if fix button has been pressed
			$this->opts_upgrade_wrapper();
		}
		//Admin Options update hook
		else if(isset($_POST[$this->unique_prefix . '_admin_options']))
		{
			//Temporarily add update function on init if form has been submitted
			$this->opts_update();
		}
		//Add in the nice "settings" link to the plugins page
		add_filter('plugin_action_links', array($this, 'filter_plugin_actions'), 10, 2);
		if(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG)
		{
			$suffix = '';
		}
		else
		{
			$suffix = '.min';
		}
		//Register JS for more permanently dismissing messages
		wp_register_script('mtekk_adminkit_messages', plugins_url('/mtekk_adminkit_messages' . $suffix . '.js', dirname(__FILE__) . '/assets/mtekk_adminkit_messages' . $suffix . '.js'), array('jquery'), self::version, true);
		//Register JS for enable/disable settings groups
		wp_register_script('mtekk_adminkit_engroups', plugins_url('/mtekk_adminkit_engroups' . $suffix . '.js', dirname(__FILE__) . '/assets/mtekk_adminkit_engroups' . $suffix . '.js'), array('jquery'), self::version, true);
		//Register JS for tabs
		wp_register_script('mtekk_adminkit_tabs', plugins_url('/mtekk_adminkit_tabs' . $suffix . '.js', dirname(__FILE__) . '/assets/mtekk_adminkit_tabs' . $suffix . '.js'), array('jquery-ui-tabs'), self::version, true);
		//Register CSS for tabs
		wp_register_style('mtekk_adminkit_tabs', plugins_url('/mtekk_adminkit_tabs' . $suffix . '.css', dirname(__FILE__) . '/assets/mtekk_adminkit_tabs' . $suffix . '.css'));
		//Register options
		register_setting($this->unique_prefix . '_options', $this->unique_prefix . '_options', '');
		//Synchronize up our settings with the database as we're done modifying them now
		$this->opt = $this::parse_args($this->get_option($this->unique_prefix . '_options'), $this->opt);
		add_action('wp_ajax_mtekk_admin_message_dismiss', array($this, 'dismiss_message'));
	}
	/**
	 * Adds the adminpage the menu and the nice little settings link
	 * TODO: make this more generic for easier extension
	 */
	function add_page()
	{
		//Add the submenu page to "settings" menu
		$hookname = add_submenu_page('options-general.php', $this->full_name, $this->short_name, $this->access_level, $this->identifier, array($this, 'admin_page'));
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
	 * Initilizes localization textdomain for translations (if applicable)
	 * 
	 * Will conditionally load the textdomain for translations. This is here for
	 * plugins that span multiple files and have localization in more than one file
	 * 
	 * @return void
	 */
	function local()
	{
		global $l10n;
		// the global and the check might become obsolete in
		// further wordpress versions
		// @see https://core.trac.wordpress.org/ticket/10527		
		if(!isset($l10n[$this->identifier]))
		{
			load_plugin_textdomain($this->identifier, false, $this->identifier . '/languages');
		}
	}
	/**
	 * Places in a link to the settings page in the plugins listing entry
	 * 
	 * @param  array  $links An array of links that are output in the listing
	 * @param  string $file The file that is currently in processing
	 * @return array  Array of links that are output in the listing.
	 */
	function filter_plugin_actions($links, $file)
	{
		//Make sure we are adding only for the current plugin
		if($file == $this->plugin_basename)
		{ 
			//Add our link to the end of the array to better integrate into the WP 2.8 plugins page
			$links[] = '<a href="' . $this->admin_url() . '">' . esc_html__('Settings') . '</a>';
		}
		return $links;
	}
	/**
	 * Checks to see if the plugin has been fully installed
	 *
	 * @return bool whether or not the plugin has been installed
	 */
	function is_installed()
	{
		$opts = $this->get_option($this->unique_prefix . '_options');
		return is_array($opts);
	}
	/** 
	 * This sets up and upgrades the database settings, runs on every activation
	 * 
	 * FIXME: seems there is a lot of very similar code in opts_upgrade_wrapper
	 */
	function install()
	{
		//Call our little security function
		$this->security();
		//Try retrieving the options from the database
		$opts = $this->get_option($this->unique_prefix . '_options');
		//If there are no settings, copy over the default settings
		if(!is_array($opts))
		{
			//Add the options, we only store differences from defaults now, so start with blank array
			$this->add_option($this->unique_prefix . '_options', array());
			$this->add_option($this->unique_prefix . '_options_bk', array(), '', false);
			//Add the version, no need to autoload the db version
			$this->update_option($this->unique_prefix . '_version', $this::version, false);
		}
		else
		{
			//Retrieve the database version
			$db_version = $this->get_option($this->unique_prefix . '_version');
			if($this::version !== $db_version)
			{
				//Run the settings update script
				$this->opts_upgrade($opts, $db_version);
				//Always have to update the version
				$this->update_option($this->unique_prefix . '_version', $this::version, false);
				//Store the options
				$this->update_option($this->unique_prefix . '_options', $this->opt, true);
			}
		}
	}
	/**
	 * This removes database settings upon deletion of the plugin from WordPress
	 */
	function uninstall()
	{
		//Remove the option array setting
		$this->delete_option($this->unique_prefix . '_options');
		//Remove the option backup array setting
		$this->delete_option($this->unique_prefix . '_options_bk');
		//Remove the version setting
		$this->delete_option($this->unique_prefix . '_version');
	}
	/**
	 * Compares the supplided version with the internal version, places an upgrade warning if there is a missmatch
	 * TODO: change this to being auto called in admin_init action
	 */
	function version_check($version)
	{
		//If we didn't get a version, setup
		if($version === false)
		{
			//Add the version, no need to autoload the db version
			$this->add_option($this->unique_prefix . '_version', $this::version, '', 'no');
		}
		//Do a quick version check
		if($version && version_compare($version, $this::version, '<') && is_array($this->opt))
		{
			//Throw an error since the DB version is out of date
			$this->messages[] = new message(esc_html__('Your settings are for an older version of this plugin and need to be migrated.', $this->identifier)
				. $this->admin_anchor('upgrade', __('Migrate the settings now.', $this->identifier), __('Migrate now.', $this->identifier)), 'warning');
			//Output any messages that there may be
			$this->messages();
			return false;
		}
		//Do a quick version check
		else if($version && version_compare($version, $this::version, '>') && is_array($this->opt))
		{
			//Let the user know that their settings are for a newer version
			$this->messages[] = new message(esc_html__('Your settings are for a newer version of this plugin.', $this->identifier)
				. $this->admin_anchor('upgrade', __('Migrate the settings now.', $this->identifier), __('Attempt back migration now.', $this->identifier)), 'warning');
			//Output any messages that there may be
			$this->messages();
			return true;
		}
		else if(!is_array($this->settings))
		{
			//Throw an error since it appears the options were never registered
			$this->messages[] = new message(esc_html__('Your plugin install is incomplete.', $this->identifier)
				. $this->admin_anchor('upgrade', __('Load default settings now.', $this->identifier), __('Complete now.', $this->identifier)), 'error');
			//Output any messages that there may be
			$this->messages();
			return false;
		}
		else if(!$this->settings_validate($this->settings))
		{
			//Throw an error since it appears the options contain invalid data
			$this->messages[] = new message(esc_html__('One or more of your plugin settings are invalid.', $this->identifier)
				. $this->admin_anchor('fix', __('Attempt to fix settings now.', $this->identifier), __('Fix now.', $this->identifier)), 'error');
			//Output any messages that there may be
			$this->messages();
			return false;
		}
		return true;
	}
	/**
	 * Run through all of the settings, check if the value matches the validated value
	 * 
	 * @param array $settings The settings array
	 * @return boolean
	 */
	function settings_validate(array &$settings)
	{
		foreach($settings as $setting)
		{
			if(is_array($setting))
			{
				if(!$this->settings_validate($setting))
				{
					return false;
				}
			}
			else if($setting instanceof setting && $setting->get_value() !== $setting->validate($setting->get_value()))
			{
				return false;
			}
		}
		return true;
	}
	/**
	 * Synchronizes the backup options entry with the current options entry
	 */
	function opts_backup()
	{
		//Set the backup options in the DB to the current options
		$this->update_option($this->unique_prefix . '_options_bk', $this->get_option($this->unique_prefix . '_options'), false);
	}
	/**
	 * The new, simpler settings update loop, handles the new settings array and replaces the old opts_update_loop
	 * 
	 * @param array $settings
	 * @param array $input
	 * @param bool $bool_ignore_missing
	 */
	protected function settings_update_loop(&$settings, $input, $bool_ignore_missing = false)
	{
		foreach($settings as $key => $setting)
		{
			if(is_array($setting))
			{
				if(isset($input[$key]))
				{
					$this->settings_update_loop($settings[$key], $input[$key]);
				}
			}
			else if($setting instanceof setting)
			{
				$setting->maybe_update_from_form_input($input, $bool_ignore_missing);
			}
		}
	}
	/**
	 * A better version of parse_args, will recrusivly follow arrays
	 * 
	 * @param mixed $args The arguments to be parsed
	 * @param mixed $defaults (optional) The default values to validate against
	 * @return mixed
	 */
	static function parse_args($args, $defaults = '')
	{
		if(is_object($args))
		{
			$r = get_object_vars($args);
		}
		else if(is_array($args))
		{
			$r =& $args;
		}
		else
		{
			wp_parse_str($args, $r);	
		}
		if(is_array($defaults))
		{
			return adminKit::array_merge_recursive($defaults, $r);
		}
		return $r;
	}
	/**
	 * An alternate version of array_merge_recursive, less flexible
	 * still recursive, ~2x faster than the more flexible version
	 * 
	 * @param array $arg1 first array
	 * @param array $arg2 second array to merge into $arg1
	 * @return array
	 */
	static function array_merge_recursive($arg1, $arg2)
	{
		foreach($arg2 as $key => $value)
		{
			if(array_key_exists($key, $arg1) && is_array($value))
			{
				$arg1[$key] = adminKit::array_merge_recursive($arg1[$key], $value);
			}
			else
			{
				$arg1[$key] = $value;
			}
		}
		return $arg1;
	}
	/**
	 * Extracts settings values to form opts array, for old options compatibility
	 * 
	 * @param array $settings The settings array
	 * @return array
	 */
	static function settings_to_opts($settings)
	{
		$opts = array();
		foreach ($settings as $key => $setting)
		{
			if(is_array($setting))
			{
				$opts[$key] = adminKit::settings_to_opts($setting);
			}
			else if($setting instanceof setting)
			{
				$opts[$key] = $setting->get_value();
			}
		}
		return $opts;
	}
	/**
	 * Loop through the settings and applying opts values if found
	 * 
	 * @param array $opts The opts array
	 */
	function load_opts_into_settings($opts)
	{
		foreach($opts as $key => $value)
		{
			if(isset($this->settings[$key]) && $this->settings[$key] instanceof setting)
			{
				$this->settings[$key]->set_value($this->settings[$key]->validate($value));
			}
			else if(isset($this->settings[$key]) && is_array($this->settings[$key]) && is_array($value))
			{
				foreach($value as $subkey => $subvalue)
				{
					if(isset($this->settings[$key][$subkey]) && $this->settings[$key][$subkey]instanceof setting)
					{
						$this->settings[$key][$subkey]->set_value($this->settings[$key][$subkey]->validate($subvalue));
					}
				}
			}
		}
	}
	/**
	 * Compares two settings by name and value to see if they are equal
	 * 
	 * @param \mtekk\adminKit\setting\setting $a
	 * @param \mtekk\adminKit\setting\setting $b
	 * @return number
	 */
	function setting_equal_check($a, $b)
	{
		if(is_array($a) || is_array($b))
		{
			foreach($a as $key=>$value)
			{
				if($value instanceof setting && isset($b[$key]) && $b[$key] instanceof setting)
				{
					return $this->setting_equal_check($value, $b[$key]);
				}
				else
				{
					return -1;
				}
			}
			return -1;
		}
		if($a instanceof setting && $b instanceof setting)
		{
			if($a->get_name() === $b->get_name() && $a->get_value() === $b->get_value())
			{
				return 0;
			}
			else if($a->get_name() === $b->get_name() && $a->get_value() > $b->get_value())
			{
				return 1;
			}
		}
		return -1;
	}
	static function setting_cloner($setting)
	{
		if(is_array($setting))
		{
			return array_map('mtekk\adminKit\adminKit::setting_cloner', $setting);
		}
		if($setting instanceof setting)
		{
			return clone $setting;
		}
	}
	/**
	 * Generates array of the new non-default settings based off of form input
	 * 
	 * @param array $input The form input array of setting values
	 * @param bool $bool_ignore_missing Tell maybe_update_from_form_input to not treat missing bool setting entries as setting to false
	 * @return array The diff array of adminkit settings
	 */
	private function get_settings_diff($input, $bool_ignore_missing = false)
	{
		//Backup default settings
		//Must clone the defaults since PHP normally shallow copies
		$default_settings = array_map('mtekk\adminKit\adminKit::setting_cloner', $this->settings);
		//Run the update loop
		$this->settings_update_loop($this->settings, $input, $bool_ignore_missing);
		//Calculate diff
		$new_settings = apply_filters($this->unique_prefix . '_opts_update_to_save', array_udiff_assoc($this->settings, $default_settings, array($this, 'setting_equal_check')));
		//Return the new settings
		return $new_settings;
	}
	/**
	 * Updates the database settings from the webform
	 * 
	 * The general flow of data is:
	 * 1) Establish default values
	 * 2) Merge in updates from webform
	 * 3) Compute difference between defaults and results of #3
	 * 4) Save to database the difference generated in #4
	 */
	function opts_update()
	{
		//Do some security related thigns as we are not using the normal WP settings API
		$this->security();
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_options-options');
		//Update local options from database
		$this->opt = adminKit::parse_args($this->get_option($this->unique_prefix . '_options'), $this->opt);
		$this->opt = apply_filters($this->unique_prefix . '_opts_update_prebk', $this->opt);
		//Update our backup options
		$this->update_option($this->unique_prefix . '_options_bk', $this->opt, false);
		$opt_prev = $this->opt;
		//Grab our incomming array (the data is dirty)
		$input = $_POST[$this->unique_prefix . '_options'];
		//Run through the loop and get the diff from detauls
		$new_settings = $this->get_settings_diff($input);
		//FIXME: Eventually we'll save the object array, but not today
		//Convert to opts array for saving
		$this->opt = adminKit::settings_to_opts($new_settings);
		//Commit the option changes
		$updated = $this->update_option($this->unique_prefix . '_options', $this->opt, true);
		//Check if known settings match attempted save
		if($updated && count(array_diff_key($input, $this->settings)) == 0)
		{
			//Let the user know everything went ok
			$this->messages[] = new message(esc_html__('Settings successfully saved.', $this->identifier)
				. $this->admin_anchor('undo', __('Undo the options save.', $this->identifier), __('Undo', $this->identifier)), 'success');
		}
		else if(!$updated && count(array_diff_key($opt_prev, $this->settings)) == 0)
		{
			$this->messages[] = new message(esc_html__('Settings did not change, nothing to save.', $this->identifier), 'info');
		}
		else if(!$updated)
		{
			$this->messages[] = new message(esc_html__('Settings were not saved.', $this->identifier), 'error');
		}
		else
		{
			//Let the user know the following were not saved
			$this->messages[] = new message(esc_html__('Some settings were not saved.', $this->identifier)
				. $this->admin_anchor('undo', __('Undo the options save.', $this->identifier), __('Undo', $this->identifier)), 'warning');
			$temp = esc_html__('The following settings were not saved:', $this->identifier);
			foreach(array_diff_key($input, $this->settings) as $setting => $value)
			{
				$temp .= '<br />' . $setting;
			}
			$this->messages[] = new message($temp . '<br />' . sprintf(esc_html__('Please include this message in your %sbug report%s.', $this->identifier), '<a title="' . sprintf(esc_attr__('Go to the %s support forum.', $this->identifier), $this->short_name) . '" href="' . $this->support_url . '">', '</a>'), 'info');
		}
		add_action('admin_notices', array($this, 'messages'));
	}
	/**
	 * Retrieves the settings from database and exports as JSON
	 */
	function settings_export()
	{
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_admin_import_export');
		//Must clone the defaults since PHP normally shallow copies
		$default_settings = array_map('mtekk\adminKit\adminKit::setting_cloner', $this->settings);
		//Get the database options, and load
		//FIXME: This changes once we save settings to the db instead of opts
		$this->load_opts_into_settings($this->get_option($this->unique_prefix . '_options'));
		//Get the unique settings
		$export_settings = apply_filters($this->unique_prefix . '_settings_to_export', array_udiff_assoc($this->settings, $default_settings, array($this, 'setting_equal_check')));
		//Change our headder to application/json for direct save
		header('Cache-Control: public');
		//The next two will cause good browsers to download instead of displaying the file
		header('Content-Description: File Transfer');
		header('Content-disposition: attachemnt; filename=' . $this->unique_prefix . '_settings.json');
		header('Content-Type: application/json');
		//JSON encode our settings array
		$output = json_encode(
				(object)array(
						'plugin' => $this->short_name,
						'version' => $this::version,
						'settings' => $export_settings)
				, JSON_UNESCAPED_SLASHES, 32);
		//Let the browser know how long the file is
		header('Content-Length: ' . strlen($output)); // binary length
		//Output the file
		echo $output;
		//Prevent WordPress from continuing on
		die();
	}
	/**
	 * Imports JSON settings into database
	 */
	function settings_import()
	{
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_admin_import_export');
		//Set the backup options in the DB to the current options
		$this->opts_backup();
		//Load the user uploaded file, handle failure gracefully
		if(is_uploaded_file($_FILES[$this->unique_prefix . '_admin_import_file']['tmp_name']))
		{
			//Grab the json settings from the temp file, treat as associative array so we can just throw the settings subfield at the update loop
			$settings_upload = json_decode(file_get_contents($_FILES[$this->unique_prefix . '_admin_import_file']['tmp_name']), true);
			//Only continue if we have a JSON object that is for this plugin (the the WP rest_is_object() function is handy here as the REST API passes JSON)
			if(rest_is_object($settings_upload) && isset($settings_upload['plugin']) && $settings_upload['plugin'] === $this->short_name)
			{
				//Act as if the JSON file was just a bunch of POST entries for a settings save
				//Run through the loop and get the diff from detauls
				$new_settings = $this->get_settings_diff($settings_upload['settings'], true);
				//FIXME: Eventually we'll save the object array, but not today
				//Convert to opts array for saving
				$this->opt = adminKit::settings_to_opts($new_settings);
				//Run opts through update script
				//Make sure we safely import and upgrade settings if needed
				$this->opts_upgrade($this->opt, $settings_upload['version']);
				//Commit the option changes
				$updated = $this->update_option($this->unique_prefix . '_options', $this->opt, true);
				//Check if known settings match attempted save
				if($updated && count(array_diff_key($settings_upload['settings'], $this->settings)) == 0)
				{
					//Let the user know everything went ok
					$this->messages[] = new message(esc_html__('Settings successfully imported from the uploaded file.', $this->identifier)
							. $this->admin_anchor('undo', __('Undo the options import.', $this->identifier), __('Undo', $this->identifier)), 'success');
				}
				else
				{
					$this->messages[] = new message(esc_html__('No settings were imported. Settings from uploaded file matched existing settings.', $this->identifier), 'info');
				}
				//Output any messages that there may be
				add_action('admin_notices', array($this, 'messages'));
				//And return as we're successful
				return;
			}
			//If it wasn't JSON, try XML
			else
			{
				return $this->opts_import();
			}
		}
		//Throw an error since we could not load the file for various reasons
		$this->messages[] = new message(esc_html__('Importing settings from file failed.', $this->identifier), 'error');
	}
	/**
	 * Exports a XML options document
	 */
	function opts_export()
	{
		//Do a nonce check, prevent malicious link/form problems 
		check_admin_referer($this->unique_prefix . '_admin_import_export');
		//Update our internal settings
		$this->opt = $this->get_option($this->unique_prefix . '_options');
		//Create a DOM document
		$dom = new \DOMDocument('1.0', 'UTF-8');
		//Adds in newlines and tabs to the output
		$dom->formatOutput = true;
		//We're not using a DTD therefore we need to specify it as a standalone document
		$dom->xmlStandalone = true;
		//Add an element called options
		$node = $dom->createElement('options');
		$parnode = $dom->appendChild($node);
		//Add a child element named plugin
		$node = $dom->createElement('plugin');
		$plugnode = $parnode->appendChild($node);
		//Add some attributes that identify the plugin and version for the options export
		$plugnode->setAttribute('name', $this->short_name);
		$plugnode->setAttribute('version', $this::version);
		//Change our headder to text/xml for direct save
		header('Cache-Control: public');
		//The next two will cause good browsers to download instead of displaying the file
		header('Content-Description: File Transfer');
		header('Content-disposition: attachemnt; filename=' . $this->unique_prefix . '_settings.xml');
		header('Content-Type: text/xml');
		//Loop through the options array
		foreach($this->opt as $key=>$option)
		{
			if(is_array($option))
			{
				continue;
			}
			//Add a option tag under the options tag, store the option value
			$node = $dom->createElement('option', htmlentities($option, ENT_COMPAT | ENT_XML1, 'UTF-8'));
			$newnode = $plugnode->appendChild($node);
			//Change the tag's name to that of the stored option
			$newnode->setAttribute('name', $key);
		}
		//Prepair the XML for output
		$output = $dom->saveXML();
		//Let the browser know how long the file is
		header('Content-Length: ' . strlen($output)); // binary length
		//Output the file
		echo $output;
		//Prevent WordPress from continuing on
		die();
	}
	/**
	 * Imports a XML options document
	 */
	function opts_import()
	{
		//Our quick and dirty error supressor
		$error_handler = function($errno, $errstr, $eerfile, $errline, $errcontext)
		{
			return true;
		};
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_admin_import_export');
		//Set the backup options in the DB to the current options
		$this->opts_backup();
		//Create a DOM document
		$dom = new \DOMDocument('1.0', 'UTF-8');
		//We want to catch errors ourselves
		set_error_handler($error_handler);
		//Load the user uploaded file, handle failure gracefully
		if(is_uploaded_file($_FILES[$this->unique_prefix . '_admin_import_file']['tmp_name']) && $dom->load($_FILES[$this->unique_prefix . '_admin_import_file']['tmp_name']))
		{
			$opts_temp = array();
			$version = '';
			//Have to use an xpath query otherwise we run into problems
			$xpath = new \DOMXPath($dom);  
			$option_sets = $xpath->query('plugin');
			//Loop through all of the xpath query results
			foreach($option_sets as $options)
			{
				//We only want to import options for only this plugin
				if($options->getAttribute('name') === $this->short_name)
				{
					//Grab the file version
					$version = $options->getAttribute('version');
					//Loop around all of the options
					foreach($options->getelementsByTagName('option') as $child)
					{
						//Place the option into the option array, DOMDocument decodes html entities for us
						$opts_temp[$child->getAttribute('name')] = $child->nodeValue;
					}
				}
			}
			//Make sure we safely import and upgrade settings if needed
			$this->opts_upgrade($opts_temp, $version);
			//Commit the loaded options to the database
			$this->update_option($this->unique_prefix . '_options', $this->opt, true);
			//Everything was successful, let the user know
			$this->messages[] = new message(esc_html__('Settings successfully imported from the uploaded file.', $this->identifier)
				. $this->admin_anchor('undo', __('Undo the options import.', $this->identifier), __('Undo', $this->identifier)), 'success');
		}
		else
		{
			//Throw an error since we could not load the file for various reasons
			$this->messages[] = new message(esc_html__('Importing settings from file failed.', $this->identifier), 'error');
		}
		//Reset to the default error handler after we're done
		restore_error_handler();
		//Output any messages that there may be
		add_action('admin_notices', array($this, 'messages'));
	}
	/**
	 * Resets the database settings array to the default set in opt
	 */
	function opts_reset()
	{
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_admin_import_export');
		//Set the backup options in the DB to the current options
		$this->opts_backup();
		//Load in the hard coded default option values
		$this->update_option($this->unique_prefix . '_options', array(), true);
		//Reset successful, let the user know
		$this->messages[] = new message(esc_html__('Settings successfully reset to the default values.', $this->identifier)
			. $this->admin_anchor('undo', __('Undo the options reset.', $this->identifier), __('Undo', $this->identifier)), 'success');
		add_action('admin_notices', array($this, 'messages'));
	}
	/**
	 * Undos the last settings save/reset/import
	 */
	function opts_undo()
	{
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_admin_undo');
		//Set the options array to the current options
		$opt = $this->get_option($this->unique_prefix . '_options');
		//Set the options in the DB to the backup options
		$this->update_option($this->unique_prefix . '_options', $this->get_option($this->unique_prefix . '_options_bk'), true);
		//Set the backup options to the undone options
		$this->update_option($this->unique_prefix . '_options_bk', $opt, false);
		//Send the success/undo message
		$this->messages[] = new message(esc_html__('Settings successfully undid the last operation.', $this->identifier)
			. $this->admin_anchor('undo', __('Undo the last undo operation.', $this->identifier), __('Undo', $this->identifier)), 'success');
		add_action('admin_notices', array($this, 'messages'));
	}
	/**
	 * Upgrades input options array, sets to $this->opt, designed to be overwritten
	 * 
	 * @param array $opts
	 * @param string $version the version of the passed in options
	 */
	function opts_upgrade($opts, $version)
	{
		//We don't support using newer versioned option files in older releases
		if(version_compare($this::version, $version, '>='))
		{
			$this->opt = $opts;
		}
	}
	/**
	 * Forces a database settings upgrade
	 * 
	 * FIXME: seems there is a lot of very similar code in install
	 */
	function opts_upgrade_wrapper()
	{
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_admin_upgrade');
		//Grab the database options
		$opts = $this->get_option($this->unique_prefix . '_options');
		if(is_array($opts))
		{
			//Feed the just read options into the upgrade function
			$this->opts_upgrade($opts, $this->get_option($this->unique_prefix . '_version'));
			//Always have to update the version
			$this->update_option($this->unique_prefix . '_version', $this::version, false);
			//Store the options
			$this->update_option($this->unique_prefix . '_options', $this->opt, true);
			//Send the success message
			$this->messages[] = new message(esc_html__('Settings successfully migrated.', $this->identifier), 'success');
		}
		else
		{
			//Run the install script
			$this->install();
			//Send the success message
			$this->messages[] = new message(esc_html__('Default settings successfully installed.', $this->identifier), 'success');
		}
		add_action('admin_notices', array($this, 'messages'));
	}
	/**
	 * help action hook function
	 *
	 * @return string
	 *
	 */
	function help()
	{
		$screen = get_current_screen();
		//Exit early if the add_help_tab function doesn't exist
		if(!method_exists($screen, 'add_help_tab'))
		{
			return;
		}
		//Add contextual help on current screen
		if($screen->id == 'settings_page_' . $this->identifier)
		{
			$this->help_contents($screen);
		}
	}
	function help_contents(\WP_Screen &$screen)
	{
		
	}
	function dismiss_message()
	{
		//Grab the submitted UID
		$uid = esc_attr($_POST['uid']);
		//Create a dummy message, with the discovered UID
		$message = new message('', '', true, $uid);
		//Dismiss the message
		$message->dismiss();
		wp_die();
	}
	/**
	 * Prints to screen all of the messages stored in the message member variable
	 */
	function messages()
	{
		foreach($this->messages as $message)
		{
			$message->render();
		}
		//Old deprecated messages
		if(is_array($this->message) && count($this->message))
		{
			_deprecated_function( __FUNCTION__, '2.0.0', __('adminKit::message is deprecated, use new adminkit_messages instead.', $this->identifier) );
			//Loop through our message classes
			foreach($this->message as $key => $class)
			{
				//Loop through the messages in the current class
				foreach($class as $message)
				{
					printf('<div class="%s"><p>%s</p></div>', esc_attr($key), $message);	
				}
			}
			$this->message = array();
		}
		$this->messages = array();
	}
	/**
	 * Function prototype to prevent errors
	 */
	function admin_styles()
	{

	}
	/**
	 * Function prototype to prevent errors
	 */
	function admin_scripts()
	{

	}
	/**
	 * Function prototype to prevent errors
	 */
	function admin_head()
	{

	}
	/**
	 * Function prototype to prevent errors
	 */
	function admin_page()
	{

	}
	/**
	 * Function prototype to prevent errors
	 */
	protected function _get_help_text()
	{
		
	}
	/**
	 * Returns a valid xHTML element ID
	 *
	 * @param object $option
	 * 
	 * @deprecated 7.0.0
	 */
	static public function get_valid_id($option)
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::get_valid_id');
		if(is_numeric($option[0]))
		{
			return 'p' . $option;
		}
		else
		{
			return $option;
		}
	}
	function import_form()
	{
		$form = '<div id="mtekk_admin_import_export_relocate">';
		$form .= sprintf('<form action="%s" method="post" enctype="multipart/form-data" id="%s_admin_upload">', esc_attr($this->admin_url()), esc_attr($this->unique_prefix));
		$form .= wp_nonce_field($this->unique_prefix . '_admin_import_export', '_wpnonce', true, false);
		$form .= sprintf('<fieldset id="import_export" class="%s_options">', esc_attr($this->unique_prefix));
		$form .= '<legend class="screen-reader-text">' . esc_html__( 'Import settings', $this->identifier ) . '</legend>';
		$form .= '<p>' . esc_html__('Import settings from a JSON or XML file, export the current settings to a JSON file, or reset to the default settings.', $this->identifier) . '</p>';
		$form .= '<table class="form-table"><tr valign="top"><th scope="row">';
		$form .= sprintf('<label for="%s_admin_import_file">', esc_attr($this->unique_prefix));
		$form .= esc_html__('Settings File', $this->identifier);
		$form .= '</label></th><td>';
		$form .= sprintf('<input type="file" name="%1$s_admin_import_file" id="%1$s_admin_import_file" size="32" /><p class="description">', esc_attr($this->unique_prefix));
		$form .= esc_html__('Select a JSON or XML settings file to upload and import settings from.', $this->identifier);
		$form .= '</p></td></tr></table><p class="submit">';
		$form .= sprintf('<input type="submit" class="button" name="%1$s_admin_settings_import" value="%2$s"/>', $this->unique_prefix, esc_attr__('Import', $this->identifier));
		$form .= sprintf('<input type="submit" class="button" name="%1$s_admin_settings_export" value="%2$s"/>', $this->unique_prefix, esc_attr__('Export', $this->identifier));
		$form .= sprintf('<input type="submit" class="button" name="%1$s_admin_reset" value="%2$s"/>', $this->unique_prefix, esc_attr__('Reset', $this->identifier));
		$form .= '</p></fieldset></form></div>';
		return $form;
	}
	/**
	 * This will output a well formed hidden option
	 *
	 * @param string $option
	 * 
	 * @deprecated 7.0.0
	 */
	function input_hidden($option)
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::input_hidden');
		$opt_id = adminKit::get_valid_id($option);
		$opt_name = $this->unique_prefix . '_options[' . $option . ']';
		printf('<input type="hidden" name="%1$s" id="%2$s" value="%3$s" />', esc_attr($opt_name), esc_attr($opt_id), esc_attr($this->opt[$option]));
	}
	/**
	 * This will output a well formed option label
	 *
	 * @param string $opt_id
	 * @param string $label
	 * 
	 * @deprecated 7.0.0
	 */
	function label($opt_id, $label)
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::label');
		printf('<label for="%1$s">%2$s</label>', esc_attr($opt_id), $label);
	}
	/**
	 * This will output a well formed table row for a text input
	 *
	 * @param string $label
	 * @param string $option
	 * @param string $class (optional)
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * 
	 * @deprecated 7.0.0
	 */
	function input_text($label, $option, $class = 'regular-text', $disable = false, $description = '')
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::input_text');
		$opt_id = adminKit::get_valid_id($option);
		$opt_name = $this->unique_prefix . '_options[' . $option . ']';
		if($disable)
		{
			$this->input_hidden($option);
			$class .= ' disabled';
		}?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $label);?>
			</th>
			<td>
				<?php printf('<input type="text" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %5$s/><br />', esc_attr($opt_name), esc_attr($opt_id), esc_attr($this->opt[$option]), esc_attr($class), disabled($disable, true, false));?>
				<?php if($description !== ''){?><p class="description"><?php echo $description;?></p><?php }?>
			</td>
		</tr>
	<?php
	}
	/**
	 * This will output a well formed table row for a HTML5 number input
	 *
	 * @param string $label
	 * @param string $option
	 * @param string $class (optional)
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * @param int|string $min (optional) 
	 * @param int|string $max (optional)
	 * @param int|string $step (optional)
	 * 
	 * @deprecated 7.0.0
	 */
	function input_number($label, $option, $class = 'small-text', $disable = false, $description = '', $min = '', $max = '', $step = '')
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::input_number');
		$opt_id = adminKit::get_valid_id($option);
		$opt_name = $this->unique_prefix . '_options[' . $option . ']';
		$extras = '';
		if($min !== '')
		{
			$extras .= 'min="' . esc_attr($min) . '" ';
		}
		if($max !== '')
		{
			$extras .= 'max="' . esc_attr($max) . '" ';
		}
		if($step !== '')
		{
			$extras .= 'step="' . esc_attr($step) . '" ';
		}
		if($disable)
		{
			$this->input_hidden($option);
			$class .= ' disabled';
		}?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $label);?>
			</th>
			<td>
				<?php printf('<input type="number" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %6$s%5$s/><br />', esc_attr($opt_name), esc_attr($opt_id), esc_attr($this->opt[$option]), esc_attr($class), disabled($disable, true, false), $extras);?>
				<?php if($description !== ''){?><p class="description"><?php echo $description;?></p><?php }?>
			</td>
		</tr>
	<?php
	}
	/**
	 * This will output a well formed textbox
	 *
	 * @param string $label
	 * @param string $option
	 * @param string $rows (optional)
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * 
	 * @deprecated 7.0.0
	 */
	function textbox($label, $option, $height = '3', $disable = false, $description = '', $class = '')
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::textbox');
		$opt_id = adminKit::get_valid_id($option);
		$opt_name = $this->unique_prefix . '_options[' . $option . ']';
		$class .= ' large-text';
		if($disable)
		{
			$this->input_hidden($option);
			$class .= ' disabled';
		}?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $label);?>
			</th>
			<td>
				<?php printf('<textarea rows="%6$s" name="%1$s" id="%2$s" class="%4$s" %5$s/>%3$s</textarea><br />', esc_attr($opt_name), esc_attr($opt_id), esc_textarea($this->opt[$option]), esc_attr($class), disabled($disable, true, false), esc_attr($height));?>
					<?php if($description !== ''){?><p class="description"><?php echo $description;?></p><?php }?>
			</td>
		</tr>
		<?php
	}
	/**
	 * This will output a well formed tiny mce ready textbox
	 *
	 * @param string $label
	 * @param string $option
	 * @param string $rows (optional)
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * 
	 * @deprecated 7.0.0
	 */
	function tinymce($label, $option, $height = '3', $disable = false, $description = '')
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::tinymce');
		$opt_id = adminKit::get_valid_id($option);
		$class = 'mtekk_mce';
		if($disable)
		{
			$this->input_hidden($option);
			$class .= ' disabled';
		}?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $label);?>
			</th>
			<td>
				<?php printf('<textarea rows="%6$s" name="%1$s" id="%2$s" class="%4$s" %5$s/>%3$s</textarea><br />', esc_attr($opt_name), esc_attr($opt_id), esc_textarea($this->opt[$option]), esc_attr($class), disabled($disable, true, false), esc_attr($height));?>
				<?php if($description !== ''){?><p class="description"><?php echo $description;?></p><?php }?>
			</td>
		</tr>
	<?php
	}
	/**
	 * This will output a well formed table row for a checkbox input
	 *
	 * @param string $label
	 * @param string $option
	 * @param string $instruction
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * @param string $class (optional)
	 * 
	 * @deprecated 7.0.0
	 */
	function input_check($label, $option, $instruction, $disable = false, $description = '', $class = '')
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::input_check');
		$opt_id = adminKit::get_valid_id($option);
		$opt_name = $this->unique_prefix . '_options[' . $option . ']';
		if($disable)
		{
			$this->input_hidden($option);
			$class .= ' disabled';
		}?>
		<tr valign="top">
			<th scope="row">
				<?php echo esc_html( $label ); ?>
			</th>
			<td>
				<label for="<?php echo esc_attr( $opt_id ); ?>">
					<?php printf('<input type="checkbox" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %5$s %6$s/>', esc_attr($opt_name), esc_attr($opt_id), esc_attr($this->opt[$option]), esc_attr($class), disabled($disable, true, false), checked($this->opt[$option], true, false));?>
					<?php echo $instruction; ?>
				</label><br />
				<?php if($description !== ''){?><p class="description"><?php echo $description;?></p><?php }?>
			</td>
		</tr>
	<?php
	}
	/**
	 * This will output a singular radio type form input field
	 *
	 * @param string $option
	 * @param string $value
	 * @param string $instruction
	 * @param object $disable (optional)
	 * @param string $class (optional)
	 * 
	 * @deprecated 7.0.0
	 */
	function input_radio($option, $value, $instruction, $disable = false, $class = '')
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::input_radio');
		$opt_id = adminKit::get_valid_id($option);
		$opt_name = $this->unique_prefix . '_options[' . $option . ']';
		$class .= ' togx';
		if($disable)
		{
			$this->input_hidden($option);
			$class .= ' disabled';
		}?>
		<label>
			<?php printf('<input type="radio" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %5$s %6$s/>', esc_attr($opt_name), esc_attr($opt_id), esc_attr($value), esc_attr($class), disabled($disable, true, false), checked($value, $this->opt[$option], false));?>
			<?php echo $instruction; ?>
		</label><br/>
	<?php
	}
	/**
	 * This will output a well formed table row for a select input
	 *
	 * @param string $label
	 * @param string $option
	 * @param array $values
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * @param array $titles (optional) The array of titiles for the options, if they should be different from the values
	 * @param string $class (optional) Extra class to apply to the elements
	 * 
	 * @deprecated 7.0.0
	 */
	function input_select($label, $option, $values, $disable = false, $description = '', $titles = false, $class = '')
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::input_select');
		//If we don't have titles passed in, we'll use option names as values
		if(!$titles)
		{
			$titles = $values;
		}
		$opt_id = adminKit::get_valid_id($option);
		$opt_name = $this->unique_prefix . '_options[' . $option . ']';
		if($disable)
		{
			$this->input_hidden($option);
			$class .= ' disabled';
		}?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $label);?>
			</th>
			<td>
				<?php printf('<select name="%1$s" id="%2$s" class="%4$s" %5$s>%3$s</select><br />', esc_attr($opt_name), esc_attr($opt_id), $this->select_options($option, $titles, $values), esc_attr($class), disabled($disable, true, false));?>
				<?php if($description !== ''){?><p class="description"><?php echo $description;?></p><?php }?>
			</td>
		</tr>
	<?php
	}
	/**
	 * Displays wordpress options as <seclect>
	 *
	 * @param string $optionname name of wordpress options store
	 * @param array $options array of names of options that can be selected
	 * @param array $values array of the values of the options that can be selected
	 * @param array $exclude(optional) array of names in $options array to be excluded
	 * 
	 * @return string The assembled HTML for the select options
	 * 
	 * @deprecated 7.0.0
	 */
	function select_options($optionname, $options, $values, $exclude = array())
	{
		_deprecated_function( __FUNCTION__, '7.0', '\mtekk\adminKit\form::select_options');
		$options_html = '';
		$value = $this->opt[$optionname];
		//Now do the rest
		foreach($options as $key => $option)
		{
			if(!in_array($option, $exclude))
			{
				$options_html .= sprintf('<option value="%1$s" %2$s>%3$s</option>', esc_attr($values[$key]), selected($value, $values[$key], false), $option);
			}
		}
		return $options_html;
	}
	/**
	 * A local pass through for get_option so that we can hook in and pick the correct method if needed
	 *
	 * @param string $option The name of the option to retrieve
	 * @return mixed The value of the option
	 */
	function get_option($option)
	{
		return get_option($option);
	}
	/**
	 * A local pass through for update_option so that we can hook in and pick the correct method if needed
	 *
	 * @param string $option The name of the option to update
	 * @param mixed $newvalue The new value to set the option to
	 */
	function update_option($option, $newvalue, $autoload = null)
	{
		return update_option($option, $newvalue, $autoload);
	}
	/**
	 * A local pass through for add_option so that we can hook in and pick the correct method if needed
	 *
	 * @param string $option The name of the option to update
	 * @param mixed $value The new value to set the option to
	 * @param null $deprecated Deprecated parameter
	 * @param string $autoload Whether or not to autoload the option, it's a string because WP is special
	 */
	function add_option($option, $value = '', $deprecated = '', $autoload = 'yes')
	{
		return add_option($option, $value, null, $autoload);
	}
	/**
	 * A local pass through for delete_option so that we can hook in and pick the correct method if needed
	 *
	 * @param string $option The name of the option to delete
	 */
	function delete_option($option)
	{
		return delete_option($option);
	}
}
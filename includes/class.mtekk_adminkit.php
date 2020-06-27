<?php
/*
	Copyright 2015-2020  John Havlik  (email : john.havlik@mtekk.us)

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
require_once(dirname(__FILE__) . '/block_direct_access.php');
//Include admin base class
if(!class_exists('mtekk_adminKit_message'))
{
	require_once(dirname(__FILE__) . '/class.mtekk_adminkit_message.php');
}
abstract class mtekk_adminKit
{
	const version = '2.1.1';
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
		return mtekk_adminKit::version;
	}
	/**
	 * Checks if the administrator has the access capability, and adds it if they don't
	 */
	function add_cap()
	{
		$role = get_role('administrator');
		if($role instanceof WP_Role && !$role->has_cap($this->access_level))
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
		//Admin Options export hook
		else if(isset($_POST[$this->unique_prefix . '_admin_export']))
		{
			//Run the export function on init if export form has been submitted
			$this->opts_export();
		}
		//Admin Options import hook
		else if(isset($_FILES[$this->unique_prefix . '_admin_import_file']) && !empty($_FILES[$this->unique_prefix . '_admin_import_file']['name']))
		{
			//Run the import function on init if import form has been submitted
			$this->opts_import();
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
		wp_register_script('mtekk_adminkit_messages', plugins_url('/mtekk_adminkit_messages' . $suffix . '.js', dirname(__FILE__) . '/mtekk_adminkit_messages' . $suffix . '.js'), array('jquery'), self::version, true);
		//Register JS for enable/disable settings groups
		wp_register_script('mtekk_adminkit_engroups', plugins_url('/mtekk_adminkit_engroups' . $suffix . '.js', dirname(__FILE__) . '/mtekk_adminkit_engroups' . $suffix . '.js'), array('jquery'), self::version, true);
		//Register JS for tabs
		wp_register_script('mtekk_adminkit_tabs', plugins_url('/mtekk_adminkit_tabs' . $suffix . '.js', dirname(__FILE__) . '/mtekk_adminkit_tabs' . $suffix . '.js'), array('jquery-ui-tabs'), self::version, true);
		//Register CSS for tabs
		wp_register_style('mtekk_adminkit_tabs', plugins_url('/mtekk_adminkit_tabs' . $suffix . '.css', dirname(__FILE__) . '/mtekk_adminkit_tabs' . $suffix . '.css'));
		//Register options
		register_setting($this->unique_prefix . '_options', $this->unique_prefix . '_options', '');
		//Synchronize up our settings with the database as we're done modifying them now
		$this->opt = $this::parse_args($this->get_option($this->unique_prefix . '_options'), $this->opt);
		//Run the opts fix filter
		$this->opts_fix($this->opt);
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
		
	}
	/** 
	 * This sets up and upgrades the database settings, runs on every activation
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
			//Grab defaults from the object
			$opts = $this->opt;
			//Add the options
			$this->add_option($this->unique_prefix . '_options', $opts);
			$this->add_option($this->unique_prefix . '_options_bk', $opts, '', 'no');
			//Add the version, no need to autoload the db version
			$this->update_option($this->unique_prefix . '_version', $this::version, 'no');
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
				$this->update_option($this->unique_prefix . '_version', $this::version);
				//Store the options
				$this->update_option($this->unique_prefix . '_options', $this->opt);
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
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Your settings are for an older version of this plugin and need to be migrated.', $this->identifier)
				. $this->admin_anchor('upgrade', __('Migrate the settings now.', $this->identifier), __('Migrate now.', $this->identifier)), 'warning');
			//Output any messages that there may be
			$this->messages();
			return false;
		}
		//Do a quick version check
		else if($version && version_compare($version, $this::version, '>') && is_array($this->opt))
		{
			//Let the user know that their settings are for a newer version
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Your settings are for a newer version of this plugin.', $this->identifier)
				. $this->admin_anchor('upgrade', __('Migrate the settings now.', $this->identifier), __('Attempt back migration now.', $this->identifier)), 'warning');
			//Output any messages that there may be
			$this->messages();
			return true;
		}
		else if(!is_array($this->opt))
		{
			//Throw an error since it appears the options were never registered
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Your plugin install is incomplete.', $this->identifier)
				. $this->admin_anchor('upgrade', __('Load default settings now.', $this->identifier), __('Complete now.', $this->identifier)), 'error');
			//Output any messages that there may be
			$this->messages();
			return false;
		}
		else if(!$this->opts_validate($this->opt))
		{
			//Throw an error since it appears the options contain invalid data
			$this->messages[] = new mtekk_adminKit_message(esc_html__('One or more of your plugin settings are invalid.', $this->identifier)
				. $this->admin_anchor('fix', __('Attempt to fix settings now.', $this->identifier), __('Fix now.', $this->identifier)), 'error');
			//Output any messages that there may be
			$this->messages();
			return false;
		}
		return true;
	}
	/**
	 * A prototype function. End user should override if they need this feature.
	 */
	function opts_validate(&$opts)
	{
		return true;
	}
	/**
	 * A prototype function. End user should override if they need this feature.
	 * 
	 * @param array $opts
	 */
	function opts_fix(&$opts)
	{
	}
	/**
	 * Synchronizes the backup options entry with the current options entry
	 */
	function opts_backup()
	{
		//Set the backup options in the DB to the current options
		$this->update_option($this->unique_prefix . '_options_bk', $this->get_option($this->unique_prefix . '_options'));
	}
	/**
	 * Runs recursivly through the opts array, sanitizing and merging in updates from the $input array
	 * 
	 * @param array $opts good, clean array
	 * @param array $input unsanitzed input array, not trusted at all
	 * @todo This function should probably get a filter thrown within it to be more extensible
	 */
	protected function opts_update_loop(&$opts, $input)
	{
		//Loop through all of the existing options (avoids random setting injection)
		foreach($opts as $option => $value)
		{
			//If we have an array, dive into another recursive loop
			if(isset($input[$option]) && is_array($value))
			{
				$this->opts_update_loop($opts[$option], $input[$option]);
			}
			//We must check for unset settings, but booleans are ok to be unset
			else if(isset($input[$option]) || $option[0] == 'b')
			{
				switch($option[0])
				{
					//Handle the boolean options
					case 'b':
						$opts[$option] = isset($input[$option]);
						break;
					//Handle the integer options
					case 'i':
						$opts[$option] = (int) $input[$option];
						break;
					//Handle the absolute integer options
					case 'a':
						$opts[$option] = (int) abs($input[$option]);
						break;
					//Handle the floating point options
					case 'f':
						$opts[$option] = (float) $input[$option];
						break;
					//Handle the HTML options
					case 'h':
						$opts[$option] = wp_kses(stripslashes($input[$option]), $this->allowed_html);
						break;
					//Handle the HTML options that must not be null
					case 'H':
						if(isset($input[$option]))
						{
							$opts[$option] = wp_kses(stripslashes($input[$option]), $this->allowed_html);
						}
						break;
					//Handle the text options that must not be null
					case 'S':
						if(isset($input[$option]))
						{
							$opts[$option] = esc_html($input[$option]);
						}
						break;
					//Deal with strings that can be null
					case 's':
						$opts[$option] = esc_html($input[$option]);
						break;
					//Deal with enumerated types
					case 'E':
						$opts[$option] = $this->opts_sanitize_enum($input[$option], $option);
						break;
					//By default we have nothing to do, allows for internal settings
					default:
						break;
				}
			}
		}
	}
	/**
	 * Simple sanitization function for enumerated types, end users should overload this
	 * with something more usefull
	 * 
	 * @param string $value The input value from the form
	 * @param string $option The option name
	 * @return string The sanitized enumerated string
	 */
	private function opts_sanitize_enum($value, $option)
	{
		return esc_html($value);
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
			return mtekk_adminKit::array_merge_recursive($defaults, $r);
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
				$arg1[$key] = mtekk_adminKit::array_merge_recursive($arg1[$key], $value);
			}
			else
			{
				$arg1[$key] = $value;
			}
		}
		return $arg1;
	}
	/**
	 * An action that fires just before the options backup, use to add in dynamically detected options
	 * 
	 * @param array $opts the options array, passed in by reference
	 * @return null
	 */
	function opts_update_prebk(&$opts)
	{
		//Just a prototype function
	}
	/**
	 * Updates the database settings from the webform
	 */
	function opts_update()
	{
		//Do some security related thigns as we are not using the normal WP settings API
		$this->security();
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_options-options');
		//Update local options from database
		$this->opt = $this::parse_args($this->get_option($this->unique_prefix . '_options'), $this->opt);
		$this->opts_update_prebk($this->opt);
		//Update our backup options
		$this->update_option($this->unique_prefix . '_options_bk', $this->opt);
		$opt_prev = $this->opt;
		//Grab our incomming array (the data is dirty)
		$input = $_POST[$this->unique_prefix . '_options'];
		//Run the update loop
		$this->opts_update_loop($this->opt, $input);
		//Commit the option changes
		$updated = $this->update_option($this->unique_prefix . '_options', $this->opt);
		//Check if known settings match attempted save
		if($updated && count(array_diff_key($input, $this->opt)) == 0)
		{
			//Let the user know everything went ok
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Settings successfully saved.', $this->identifier)
				. $this->admin_anchor('undo', __('Undo the options save.', $this->identifier), __('Undo', $this->identifier)), 'success');
		}
		else if(!$updated && count(array_diff_key($opt_prev, $this->opt)) == 0)
		{
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Settings did not change, nothing to save.', $this->identifier), 'info');
		}
		else if(!$updated)
		{
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Settings were not saved.', $this->identifier), 'error');
		}
		else
		{
			//Let the user know the following were not saved
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Some settings were not saved.', $this->identifier)
				. $this->admin_anchor('undo', __('Undo the options save.', $this->identifier), __('Undo', $this->identifier)), 'warning');
			$temp = esc_html__('The following settings were not saved:', $this->identifier);
			foreach(array_diff_key($input, $this->opt) as $setting => $value)
			{
				$temp .= '<br />' . $setting;
			}
			$this->messages[] = new mtekk_adminKit_message($temp . '<br />' . sprintf(esc_html__('Please include this message in your %sbug report%s.', $this->identifier), '<a title="' . sprintf(esc_attr__('Go to the %s support forum.', $this->identifier), $this->short_name) . '" href="' . $this->support_url . '">', '</a>'), 'info');
		}
		add_action('admin_notices', array($this, 'messages'));
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
		$dom = new DOMDocument('1.0', 'UTF-8');
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
		function error($errno, $errstr, $eerfile, $errline)
		{
			return true;
		}
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer($this->unique_prefix . '_admin_import_export');
		//Set the backup options in the DB to the current options
		$this->opts_backup();
		//Create a DOM document
		$dom = new DOMDocument('1.0', 'UTF-8');
		//We want to catch errors ourselves
		set_error_handler('error');
		//Load the user uploaded file, handle failure gracefully
		if(is_uploaded_file($_FILES[$this->unique_prefix . '_admin_import_file']['tmp_name']) && $dom->load($_FILES[$this->unique_prefix . '_admin_import_file']['tmp_name']))
		{
			$opts_temp = array();
			$version = '';
			//Have to use an xpath query otherwise we run into problems
			$xpath = new DOMXPath($dom);  
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
			$this->update_option($this->unique_prefix . '_options', $this->opt);
			//Everything was successful, let the user know
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Settings successfully imported from the uploaded file.', $this->identifier)
				. $this->admin_anchor('undo', __('Undo the options import.', $this->identifier), __('Undo', $this->identifier)), 'success');
		}
		else
		{
			//Throw an error since we could not load the file for various reasons
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Importing settings from file failed.', $this->identifier), 'error');
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
		$this->update_option($this->unique_prefix . '_options', $this->opt);
		//Reset successful, let the user know
		$this->messages[] = new mtekk_adminKit_message(esc_html__('Settings successfully reset to the default values.', $this->identifier)
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
		$this->update_option($this->unique_prefix . '_options', $this->get_option($this->unique_prefix . '_options_bk'));
		//Set the backup options to the undone options
		$this->update_option($this->unique_prefix . '_options_bk', $opt);
		//Send the success/undo message
		$this->messages[] = new mtekk_adminKit_message(esc_html__('Settings successfully undid the last operation.', $this->identifier)
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
			$this->update_option($this->unique_prefix . '_version', $this::version);
			//Store the options
			$this->update_option($this->unique_prefix . '_options', $this->opt);
			//Send the success message
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Settings successfully migrated.', $this->identifier), 'success');
		}
		else
		{
			//Run the install script
			$this->install();
			//Send the success message
			$this->messages[] = new mtekk_adminKit_message(esc_html__('Default settings successfully installed.', $this->identifier), 'success');
		}
		add_action('admin_notices', array($this, 'messages'));
	}
	/**
	 * help action hook function, meant to be overridden
	 * 
	 * @return string
	 * 
	 */
	function help()
	{
		$screen = get_current_screen();
		//Add contextual help on current screen
		if($screen->id == 'settings_page_' . $this->identifier)
		{
			
		}
	}
	function dismiss_message()
	{
		//Grab the submitted UID
		$uid = esc_attr($_POST['uid']);
		//Create a dummy message, with the discovered UID
		$message = new mtekk_adminKit_message('', '', true, $uid);
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
	 */
	static public function get_valid_id($option)
	{
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
		$form .= sprintf('<form action="options-general.php?page=%s" method="post" enctype="multipart/form-data" id="%s_admin_upload">', esc_attr($this->identifier), esc_attr($this->unique_prefix));
		$form .= wp_nonce_field($this->unique_prefix . '_admin_import_export', '_wpnonce', true, false);
		$form .= sprintf('<fieldset id="import_export" class="%s_options">', esc_attr($this->unique_prefix));
		$form .= '<legend class="screen-reader-text">' . esc_html__( 'Import settings', $this->identifier ) . '</legend>';
		$form .= '<p>' . esc_html__('Import settings from a XML file, export the current settings to a XML file, or reset to the default settings.', $this->identifier) . '</p>';
		$form .= '<table class="form-table"><tr valign="top"><th scope="row">';
		$form .= sprintf('<label for="%s_admin_import_file">', esc_attr($this->unique_prefix));
		$form .= esc_html__('Settings File', $this->identifier);
		$form .= '</label></th><td>';
		$form .= sprintf('<input type="file" name="%1$s_admin_import_file" id="%1$s_admin_import_file" size="32" /><p class="description">', esc_attr($this->unique_prefix));
		$form .= esc_html__('Select a XML settings file to upload and import settings from.', 'breadcrumb_navxt');
		$form .= '</p></td></tr></table><p class="submit">';
		$form .= sprintf('<input type="submit" class="button" name="%1$s_admin_import" value="%2$s"/>', $this->unique_prefix, esc_attr__('Import', $this->identifier));
		$form .= sprintf('<input type="submit" class="button" name="%1$s_admin_export" value="%2$s"/>', $this->unique_prefix, esc_attr__('Export', $this->identifier));
		$form .= sprintf('<input type="submit" class="button" name="%1$s_admin_reset" value="%2$s"/>', $this->unique_prefix, esc_attr__('Reset', $this->identifier));
		$form .= '</p></fieldset></form></div>';
		return $form;
	}
	/**
	 * This will output a well formed hidden option
	 *
	 * @param string $option
	 */
	function input_hidden($option)
	{
		$opt_id = mtekk_adminKit::get_valid_id($option);
		$opt_name = $this->unique_prefix . '_options[' . $option . ']';
		printf('<input type="hidden" name="%1$s" id="%2$s" value="%3$s" />', esc_attr($opt_name), esc_attr($opt_id), esc_attr($this->opt[$option]));
	}
	/**
	 * This will output a well formed option label
	 *
	 * @param string $opt_id
	 * @param string $label
	 */
	function label($opt_id, $label)
	{
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
	 */
	function input_text($label, $option, $class = 'regular-text', $disable = false, $description = '')
	{
		$opt_id = mtekk_adminKit::get_valid_id($option);
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
	 */
	function input_number($label, $option, $class = 'small-text', $disable = false, $description = '', $min = '', $max = '', $step = '')
	{
		$opt_id = mtekk_adminKit::get_valid_id($option);
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
	 */
	function textbox($label, $option, $height = '3', $disable = false, $description = '', $class = '')
	{
		$opt_id = mtekk_adminKit::get_valid_id($option);
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
	 */
	function tinymce($label, $option, $height = '3', $disable = false, $description = '')
	{
		$opt_id = mtekk_adminKit::get_valid_id($option);
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
	 */
	function input_check($label, $option, $instruction, $disable = false, $description = '', $class = '')
	{
		$opt_id = mtekk_adminKit::get_valid_id($option);
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
	 */
	function input_radio($option, $value, $instruction, $disable = false, $class = '')
	{
		$opt_id = mtekk_adminKit::get_valid_id($option);
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
	 */
	function input_select($label, $option, $values, $disable = false, $description = '', $titles = false, $class = '')
	{
		//If we don't have titles passed in, we'll use option names as values
		if(!$titles)
		{
			$titles = $values;
		}
		$opt_id = mtekk_adminKit::get_valid_id($option);
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
	 */
	function select_options($optionname, $options, $values, $exclude = array())
	{
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
	function update_option($option, $newvalue)
	{
		return update_option($option, $newvalue);
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
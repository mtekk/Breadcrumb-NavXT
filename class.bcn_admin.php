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
require_once(dirname(__FILE__) . '/includes/block_direct_access.php');
//Do a PHP version check, require 5.6.0 or newer
if(version_compare(phpversion(), '5.6.0', '<'))
{
	//Only purpose of this function is to echo out the PHP version error
	function bcn_phpold()
	{
		printf('<div class="notice notice-error"><p>' . __('Your PHP version is too old, please upgrade to a newer version. Your version is %1$s, Breadcrumb NavXT requires %2$s', 'breadcrumb-navxt') . '</p></div>', phpversion(), '5.6.0');
	}
	//If we are in the admin, let's print a warning then return
	if(is_admin())
	{
		add_action('admin_notices', 'bcn_phpold');
	}
	return;
}
//Include admin base class
if(!class_exists('\mtekk\adminKit\adminKit'))
{
	require_once(dirname(__FILE__) . '/includes/adminKit/class-mtekk_adminkit.php');
}
use mtekk\adminKit\{adminKit, form, message, setting};
/**
 * The administrative interface class 
 * 
 */
class bcn_admin extends adminKit
{
	const version = '7.2.0';
	protected $full_name = 'Breadcrumb NavXT Settings';
	protected $short_name = 'Breadcrumb NavXT';
	protected $access_level = 'bcn_manage_options';
	protected $identifier = 'breadcrumb-navxt';
	protected $unique_prefix = 'bcn';
	protected $plugin_basename = null;
	protected $support_url = 'https://wordpress.org/support/plugin/breadcrumb-navxt/';
	/**
	 * Administrative interface class default constructor
	 * 
	 * @param array $opts The breadcrumb trail object's settings array
	 * @param string $basename The basename of the plugin
	 * @param array $settings The array of settings objects
	 */
	function __construct(array &$opts, $basename, array &$settings)
	{
		$this->plugin_basename = $basename;
		$this->full_name = esc_html__('Breadcrumb NavXT Settings', 'breadcrumb-navxt');
		$this->settings =& $settings;
		$this->opt =& $opts;
		//We're going to make sure we load the parent's constructor
		parent::__construct();
	}
	function is_network_admin()
	{
		return false;
	}
	/**
	 * Loads opts array values into the local settings array
	 * 
	 * @param array $opts The opts array
	 */
	function setting_merge($opts)
	{
		$unknown = array();
		foreach($opts as $key => $value)
		{
			if(isset($this->settings[$key]) && $this->settings[$key] instanceof setting\setting)
			{
				$this->settings[$key]->set_value($this->settings[$key]->validate($value));
			}
			else if(isset($this->settings[$key]) && is_array($this->settings[$key]) && is_array($value))
			{
				foreach($value as $subkey => $subvalue)
				{
					if(isset($this->settings[$key][$subkey]) && $this->settings[$key][$subkey]instanceof setting\setting)
					{
						$this->settings[$key][$subkey]->set_value($this->settings[$key][$subkey]->validate($subvalue));
					}
				}
			}
			else
			{
				$unknown[] = $key;
			}
		}
		//Add a message if we found some unknown settings while merging
		if(count($unknown) > 0)
		{
			$this->messages[] = new message(
					sprintf(__('Found %u unknown legacy settings: %s','breadcrumb-navxt'), count($unknown), implode(', ', $unknown)),
					'warning',
					true,
					'bcn_unkonwn_legacy_settings');
		}
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
		$this->setting_merge($this->opt);
	}
	/**
	 * Upgrades input options array, sets to $this->opt
	 * 
	 * @param array $opts
	 * @param string $version the version of the passed in options
	 */
	function opts_upgrade($opts, $version)
	{
		//If our version is not the same as in the db, time to update
		if(version_compare($version, $this::version, '<'))
		{
			require_once(dirname(__FILE__) . '/options_upgrade.php');
			bcn_options_upgrade_handler($opts, $version, $this->opt);
		}
		//Merge in the defaults
		$this->opt = adminKit::parse_args($opts, adminKit::settings_to_opts($this->settings));
	}
	/**
	 * Fills in the help tab contents
	 * 
	 * @param WP_Screen $screen The screen to add the help tab items to
	 */
	function help_contents(\WP_Screen &$screen)
	{
		$general_tab = '<p>' . esc_html__('Tips for the settings are located below select options.', 'breadcrumb-navxt') .
				'</p><h5>' . esc_html__('Resources', 'breadcrumb-navxt') . '</h5><ul><li>' .
				'<a title="' . esc_attr__('Go to the Breadcrumb NavXT tag archive.', 'breadcrumb-navxt') . '" href="https://mtekk.us/archives/tag/breadcrumb-navxt">' . esc_html__('Tutorials and How Tos', 'breadcrumb-navxt') . '</a>: ' .
				esc_html__("There are several guides, tutorials, and how tos available on the author's website.", 'breadcrumb-navxt') . '</li><li>' .
				'<a title="' . esc_attr__('Go to the Breadcrumb NavXT online documentation', 'breadcrumb-navxt') . '" href="https://mtekk.us/code/breadcrumb-navxt/breadcrumb-navxt-doc/">' . esc_html__('Online Documentation', 'breadcrumb-navxt') . '</a>: '.
				esc_html__('Check out the documentation for more indepth technical information.', 'breadcrumb-navxt')  . '</li><li>' .
				'<a title="' . esc_attr__('Go to the Breadcrumb NavXT support post for your version.', 'breadcrumb-navxt') . '" href="https://wordpress.org/support/plugin/breadcrumb-navxt/">' . esc_html__('Report a Bug', 'breadcrumb-navxt') . '</a>: ' .
				esc_html__('If you think you have found a bug, please include your WordPress version and details on how to reproduce the bug.', 'breadcrumb-navxt') . '</li></ul>' . 
				
				'<h5>' . esc_html__('Giving Back', 'breadcrumb-navxt') . '</h5><ul><li>' .
				'<a title="' . esc_attr__('Go to PayPal to give a donation to Breadcrumb NavXT.', 'breadcrumb-navxt') . '" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=FD5XEU783BR8U&lc=US&item_name=Breadcrumb%20NavXT%20Donation&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">' . 
				esc_html__('Donate', 'breadcrumb-navxt') . '</a>: ' .
				esc_html__('Love Breadcrumb NavXT and want to help development? Consider buying the author a beer.', 'breadcrumb-navxt') . '</li><li>' .
				'<a title="' . esc_attr__('Go to the Breadcrumb NavXT translation project.', 'breadcrumb-navxt') . '" href="https://translate.wordpress.org/projects/wp-plugins/breadcrumb-navxt">' . esc_html__('Translate', 'breadcrumb-navxt') . '</a>: ' .
				esc_html__('Is your language not available? Visit the Breadcrumb NavXT translation project on WordPress.org to start translating.', 'breadcrumb-navxt') . '</li></ul>';
			
		$screen->add_help_tab(
				array(
				'id' => $this->identifier . '-base',
				'title' => __('General', 'breadcrumb-navxt'),
				'content' => $general_tab
				));
		$quickstart_tab = '<p>' . esc_html__('For the settings on this page to take effect, you must either use the included Breadcrumb NavXT widget, or place either of the code sections below into your theme.', 'breadcrumb-navxt') .
				'</p><h5>' . esc_html__('Breadcrumb trail with separators', 'breadcrumb-navxt') . '</h5><pre><code>&lt;div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/"&gt;' . "
	&lt;?php if(function_exists('bcn_display'))
	{
		bcn_display();
	}?&gt;
&lt;/div&gt;</code></pre>" .
				'<h5>' . esc_html__('Breadcrumb trail in list form', 'breadcrumb-navxt').'</h5><pre><code>&lt;ol class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/"&gt;'."
	&lt;?php if(function_exists('bcn_display_list'))
	{
		bcn_display_list();
	}?&gt;
&lt;/ol&gt;</code></pre>";
		$screen->add_help_tab(
				array(
				'id' => $this->identifier . '-quick-start',
				'title' => __('Quick Start', 'breadcrumb-navxt'),
				'content' => $quickstart_tab
				));
		$styling_tab = '<p>' . esc_html__('Using the code from the Quick Start section above, the following CSS can be used as base for styling your breadcrumb trail.', 'breadcrumb-navxt') . '</p>' .
				'<pre><code>.breadcrumbs
{
	font-size: 1.1em;
	color: #fff;
	margin: 30px 0 0 10px;
	position: relative;
	float: left;
}</code></pre>';
		$screen->add_help_tab(
				array(
				'id' => $this->identifier . '-styling',
				'title' => __('Styling', 'breadcrumb-navxt'),
				'content' => $styling_tab
				));
		$screen->add_help_tab(
				array(
				'id' => $this->identifier . '-import-export-reset',
				'title' => __('Import/Export/Reset', 'breadcrumb-navxt'),
				'content' => $this->import_form()
				));
	}
	/**
	 * enqueue's the tab style sheet on the settings page
	 */
	function admin_styles()
	{
		wp_enqueue_style('mtekk_adminkit_tabs');
	}
	/**
	 * enqueue's the tab js and translation js on the settings page
	 */
	function admin_scripts()
	{
		//Enqueue ui-tabs
		wp_enqueue_script('jquery-ui-tabs');
		//Enqueue the admin tabs javascript
		wp_enqueue_script('mtekk_adminkit_tabs');
		//Load the translations for the tabs
		wp_localize_script('mtekk_adminkit_tabs', 'objectL10n', array(
			'mtad_uid' => 'bcn_admin',
			'mtad_import' => __('Import', 'breadcrumb-navxt'),
			'mtad_export' => __('Export', 'breadcrumb-navxt'),
			'mtad_reset' => __('Reset', 'breadcrumb-navxt'),
		));
		//Enqueue the admin enable/disable groups javascript
		wp_enqueue_script('mtekk_adminkit_engroups');
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
				
			}
			else if(defined('BCN_SETTINGS_USE_NETWORK') && BCN_SETTINGS_USE_NETWORK)
			{
				$this->messages[] = new message(esc_html__('Warning: Your network settings will override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_is_nsiteoveride');
			}
			else if(defined('BCN_SETTINGS_FAVOR_LOCAL') && BCN_SETTINGS_FAVOR_LOCAL)
			{
				$this->messages[] = new message(esc_html__('Warning: Your network settings may override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_is_isitemayoveride');
			}
			else if(defined('BCN_SETTINGS_FAVOR_NETWORK') && BCN_SETTINGS_FAVOR_NETWORK)
			{
				$this->messages[] = new message(esc_html__('Warning: Your network settings may override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_is_nsitemayoveride');
			}
			//Fall through if no settings mode was set
			else
			{
				$this->messages[] = new message(esc_html__('Warning: No BCN_SETTINGS_* define statement found, defaulting to BCN_SETTINGS_USE_LOCAL.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_is_nosetting');
			}
		}
	}
	/**
	 * A message function that checks for deprecated settings that are set and warns the user
	 */
	function deprecated_settings_warn()
	{
		//We're deprecating the limit title length setting, let the user know the new method of accomplishing this
		if(isset($this->settings['blimit_title']) && $this->settings['blimit_title']->get_value())
		{
			$this->messages[] = new message(
					sprintf(
							esc_html__('Error: The deprecated setting "Title Length" (see Miscellaneous &gt; Deprecated) has no effect in this version Breadcrumb NavXT. Please %1$suse CSS instead%2$s.', 'breadcrumb-navxt'), 
							'<a title="' . __('Go to the guide on trimming breadcrumb title lengths with CSS', 'breadcrumb-navxt') . '" href="https://mtekk.us/archives/guides/trimming-breadcrumb-title-lengths-with-css/">', '</a>'),
					'error');
		}
		foreach($this->settings as $key => $setting)
		{
			if($key[0] == "H" && substr_count($key, '_template') >= 1)
			{
				$deprecated_tags = array();
				$replacement_tags = array();
				//Deprecated ftitle check
				if(substr_count($setting->get_value(), '%ftitle%') >= 1)
				{
					$deprecated_tags[] = '%ftitle%';
					$replacement_tags[] = '%title%';
				}
				//Deprecated fhtitle check
				if(substr_count($setting->get_value(), '%fhtitle%') >= 1)
				{
					$deprecated_tags[] = '%fhtitle%';
					$replacement_tags[] = '%htitle%';
				}
				if(count($deprecated_tags) > 0)
				{
					$setting_link = sprintf('<a href="#%1$s">%2$s</a>', $key, $setting->get_title());
					$this->messages[] = new message(
							sprintf(
									esc_html__('Error: The deprecated template tag %1$s found in setting %3$s. Please use %2$s instead.', 'breadcrumb-navxt'),
									implode(' and ', $deprecated_tags),
									implode(' and ', $replacement_tags),
									$setting_link),
							'error');
				}
			}
		}
	}
	/**
	 * A message function that checks for post types added after the settings defaults were established
	 */
	function unknown_custom_types_warn()
	{
		foreach($GLOBALS['wp_post_types'] as $post_type)
		{
			if(!($post_type instanceof WP_Post_Type))
			{
				$this->messages[] = new message(
						sprintf(
							esc_html__('Error: WP_Post_Types global contains non WP_Post_Type object. Debug information: %1$s', 'breadcrumb-navxt'),
							var_export($post_type, true)),
						'error',
						true,
						'badtypeWP_Post_Types');
				continue;
			}
			//If we haven't seen this post type before, warn the user
			if(!isset($this->settings['Hpost_' . $post_type->name . '_template']))
			{
				$this->messages[] = new message(
						sprintf(
								esc_html__('Warning: The post type %1$s (%2$s) was registered after the Breadcrumb NavXT default settings. It will not show up in the settings.', 'breadcrumb-navxt'),
								$post_type->labels->singular_name,
								$post_type->name),
						'warning',
						true,
						$post_type->name);
			}
		}
		foreach($GLOBALS['wp_taxonomies'] as $taxonomy)
		{
			if(!($taxonomy instanceof WP_Taxonomy))
			{
				//If we haven't seen this taxonomy before, warn the user
				$this->messages[] = new message(
						sprintf(
								esc_html__('Error: WP_Taxonomies global contains non WP_Taxonomy object. Debug information: %1$s', 'breadcrumb-navxt'),
								var_export($taxonomy, true)),
						'error',
						true,
						'badtypeWP_Taxonomies');
				continue;
			}
			if(!isset($this->settings['Htax_' . $taxonomy->name . '_template']))
			{
				//If we haven't seen this taxonomy before, warn the user
				$this->messages[] = new message(
						sprintf(
								esc_html__('Warning: The taxonomy %1$s (%2$s) was registered after the Breadcrumb NavXT default settings. It will not show up in the settings.', 'breadcrumb-navxt'),
								$taxonomy->label,
								$taxonomy->name),
						'warning',
						true,
						$taxonomy->name);
			}
		}
	}
	/**
	 * Function checks the current site to see if the blog options should be disabled
	 * 
	 * @return boool Whether or not the blog options should be disabled
	 */
	function maybe_disable_blog_options()
	{
		return (get_option('show_on_front') !== 'page' || get_option('page_for_posts') < 1);
	}
	/**
	 * Function checks the current site to see if the mainsite options should be disabled
	 * 
	 * @return bool Whether or not the mainsite options should be disabled
	 */
	function maybe_disable_mainsite_options()
	{
		return !is_multisite();
	}
	/**
	 * The administrative page for Breadcrumb NavXT
	 */
	function admin_page()
	{
		global $wp_taxonomies, $wp_post_types;
		$this->security();
		//Do a check on deprecated settings
		$this->deprecated_settings_warn();
		//Do a check for unknown CPTs and Taxnomies
		$this->unknown_custom_types_warn();
		//Do a check for multisite settings mode
		$this->multisite_settings_warn();
		do_action($this->unique_prefix . '_settings_pre_messages', $this->settings);
		//Display our messages
		$this->messages();
		//Grab the network options, if multisite
		$network_opts = array();
		$local_opts = array();
		$overriden = array();
		$overriden_style = array();
		if(is_multisite() && !$this->is_network_admin())
		{
			$network_opts = get_site_option('bcn_options');
			$local_opts = get_option('bcn_options');
		}
		foreach($this->settings as $key => $setting)
		{
			if(isset($network_opts[$key]))
			{
				$overriden[$key] = ' ' . __('Value has been set via network wide setting.', 'breadcrumb-navxt');
				$overriden_style[$key] = ' disabled';
			}
			else
			{
				$overriden[$key] = '';
				$overriden_style[$key] = '';
			}
		}
		?>
		<div class="wrap"><h1><?php echo $this->full_name; ?></h1>
		<?php
		//We exit after the version check if there is an action the user needs to take before saving settings
		if(!$this->version_check($this->get_option($this->unique_prefix . '_version')))
		{
			return;
		}
		?>
		<form action="<?php echo $this->admin_url(); ?>" method="post" id="bcn_admin-options">
			<?php settings_fields('bcn_options');?>
			<div id="hasadmintabs">
			<fieldset id="general" class="bcn_options">
				<legend class="screen-reader-text" data-title="<?php _e( 'A collection of settings most likely to be modified are located under this tab.', 'breadcrumb-navxt' );?>"><?php _e( 'General', 'breadcrumb-navxt' ); ?></legend>
				<h2><?php _e('General', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->form->textbox($this->settings['hseparator'], '2', false, __('Placed in between each breadcrumb.', 'breadcrumb-navxt') . $overriden['hseparator'], $overriden_style['hseparator']);
						do_action($this->unique_prefix . '_settings_general', $this->settings);
					?>
				</table>
				<h2><?php _e('Current Item', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->form->input_check(
								$this->settings['bcurrent_item_linked'],
								__('Yes', 'breadcrumb-navxt'),
								false,
								$overriden['bcurrent_item_linked'],
								$overriden_style['bcurrent_item_linked']);
						$this->form->input_check(
								$this->settings['bpaged_display'],
								__('Place the page number breadcrumb in the trail.', 'breadcrumb-navxt'),
								false,
								__('Indicates that the user is on a page other than the first of a paginated archive or post.', 'breadcrumb-navxt') . $overriden['bpaged_display'],
								'adminkit-enset-ctrl adminkit-enset' . $overriden_style['bpaged_display']);
						$this->form->textbox(
								$this->settings['Hpaged_template'],
								'4',
								false,
								__('The template for paged breadcrumbs.', 'breadcrumb-navxt') . $overriden['Hpaged_template'],
								'adminkit-enset' . $overriden_style['Hpaged_template']);
						do_action($this->unique_prefix . '_settings_current_item', $this->settings);
					?>
				</table>
				<h2><?php _e('Home Breadcrumb', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->form->input_check(
								$this->settings['bhome_display'],
								__('Place the home breadcrumb in the trail.', 'breadcrumb-navxt'),
								false,
								$overriden['bhome_display'],
								'adminkit-enset-ctrl adminkit-enset' . $overriden_style['bhome_display']);
						$this->form->textbox(
								$this->settings['Hhome_template'],
								'6',
								false,
								__('The template for the home breadcrumb.', 'breadcrumb-navxt') . $overriden['Hhome_template'],
								'adminkit-enset' . $overriden_style['Hhome_template']);
						$this->form->textbox(
								$this->settings['Hhome_template_no_anchor'],
								'4',
								false,
								__('The template for the home breadcrumb, used when the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Hhome_template_no_anchor'],
								'adminkit-enset' . $overriden_style['Hhome_template_no_anchor']);
						do_action($this->unique_prefix . '_settings_home', $this->settings);
					?>
				</table>
				<h2><?php _e('Blog Breadcrumb', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->form->input_check(
								$this->settings['bblog_display'],
								__('Place the blog breadcrumb in the trail.', 'breadcrumb-navxt'),
								$this->maybe_disable_blog_options(),
								$overriden['bblog_display'],
								'adminkit-enset-ctrl adminkit-enset' . $overriden_style['bblog_display']);
						do_action($this->unique_prefix . '_settings_blog', $this->settings);
					?>
				</table>
				<h2><?php _e('Mainsite Breadcrumb', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->form->input_check(
								$this->settings['bmainsite_display'],
								__('Place the main site home breadcrumb in the trail in an multisite setup.', 'breadcrumb-navxt'),
								$this->maybe_disable_mainsite_options(),
								$overriden['bmainsite_display'],
								'adminkit-enset-ctrl adminkit-enset' . $overriden_style['bmainsite_display']);
						$this->form->textbox(
								$this->settings['Hmainsite_template'],
								'6',
								$this->maybe_disable_mainsite_options(),
								__('The template for the main site home breadcrumb, used only in multisite environments.', 'breadcrumb-navxt') . $overriden['Hmainsite_template'],
								'adminkit-enset' . $overriden_style['Hmainsite_template']);
						$this->form->textbox(
								$this->settings['Hmainsite_template_no_anchor'],
								'4',
								$this->maybe_disable_mainsite_options(),
								__('The template for the main site home breadcrumb, used only in multisite environments and when the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Hmainsite_template_no_anchor'],
								'adminkit-enset' . $overriden_style['Hmainsite_template_no_anchor']);
						do_action($this->unique_prefix . '_settings_mainsite', $this->settings);
					?>
				</table>
				<?php do_action($this->unique_prefix . '_after_settings_tab_general', $this->settings); ?>
			</fieldset>
			<fieldset id="post" class="bcn_options">
				<legend class="screen-reader-text" data-title="<?php _e( 'The settings for all post types (Posts, Pages, and Custom Post Types) are located under this tab.', 'breadcrumb-navxt' );?>"><?php _e( 'Post Types', 'breadcrumb-navxt' ); ?></legend>
			<?php
			//Loop through all of the post types in the array
			foreach($wp_post_types as $post_type)
			{
				//Check for bad post type objects, for non-public CPTs, and if the CPT wasn't known when defaults were generated
				if(!($post_type instanceof WP_Post_Type) || !apply_filters('bcn_show_cpt_private', $post_type->public, $post_type->name) || !isset($this->settings['Hpost_' . $post_type->name . '_template']))
				{
					continue;
				}
				$singular_name_lc = mb_strtolower($post_type->labels->singular_name, 'UTF-8');
				?>
				<h2><?php echo $post_type->labels->singular_name; ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->form->textbox(
								$this->settings['Hpost_' . $post_type->name . '_template'],
								'6',
								false,
								sprintf(__('The template for %s breadcrumbs.', 'breadcrumb-navxt'), $singular_name_lc) . $overriden['Hpost_' . $post_type->name . '_template'],
								$overriden_style['Hpost_' . $post_type->name . '_template']);
						$this->form->textbox(
								$this->settings['Hpost_' . $post_type->name . '_template_no_anchor'],
								'4',
								false,
								sprintf(__('The template for %s breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'), $singular_name_lc) . $overriden['Hpost_' . $post_type->name . '_template_no_anchor'],
								$overriden_style['Hpost_' . $post_type->name . '_template_no_anchor']);
						if(!in_array($post_type->name, array('page', 'post')))
						{
						$optid = form::get_valid_id('apost_' . $post_type->name . '_root');
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo $optid;?>"><?php printf(esc_html__('%s Root Page', 'breadcrumb-navxt'), $post_type->labels->singular_name);?></label>
						</th>
						<td>
							<?php wp_dropdown_pages(
									array('name' => $this->unique_prefix . '_options[apost_' . $post_type->name . '_root]',
											'id' => $optid,
											'echo' => 1,
											'show_option_none' => __( '&mdash; Select &mdash;' ),
											'option_none_value' => '0',
											'selected' => $this->settings['apost_' . $post_type->name . '_root']->get_value(),
											'class' => $overriden_style['apost_' . $post_type->name . '_root']));
							if(isset($overriden['apost_' . $post_type->name . '_root']) && $overriden['apost_' . $post_type->name . '_root'] !== '')
							{
								printf('<p class="description">%s</p>', $overriden['apost_' . $post_type->name . '_root']);
							}
							?>
						</td>
					</tr>
					<?php
							$this->form->input_check(
									$this->settings['bpost_' . $post_type->name . '_archive_display'],
									sprintf(__('Show the breadcrumb for the %s post type archives in the breadcrumb trail.', 'breadcrumb-navxt'), $singular_name_lc),
									!$post_type->has_archive,
									$overriden['bpost_' . $post_type->name . '_archive_display'],
									$overriden_style['bpost_' . $post_type->name . '_archive_display']);
						}
						if(in_array($post_type->name, array('page')))
						{
							$this->form->input_hidden($this->settings['bpost_' . $post_type->name . '_hierarchy_display']);
							$this->form->input_hidden($this->settings['bpost_' . $post_type->name . '_hierarchy_parent_first']);
							$this->form->input_hidden($this->settings['bpost_' . $post_type->name . '_taxonomy_referer']);
						}
						else
						{
							$this->form->input_check(
									$this->settings['bpost_' . $post_type->name . '_hierarchy_display'],
									sprintf(__('Show the hierarchy (specified below) leading to a %s in the breadcrumb trail.', 'breadcrumb-navxt'), $singular_name_lc),
									false,
									$overriden['bpost_' . $post_type->name . '_hierarchy_display'],
									'adminkit-enset-ctrl adminkit-enset' . $overriden_style['bpost_' . $post_type->name . '_hierarchy_display']);
							$this->form->input_check(
									$this->settings['bpost_' . $post_type->name . '_hierarchy_parent_first'],
									sprintf(__('Use the parent of the %s as the primary hierarchy, falling back to the hierarchy selected below when the parent hierarchy is exhausted.', 'breadcrumb-navxt'), $singular_name_lc),
									false,
									$overriden['bpost_' . $post_type->name . '_hierarchy_parent_first'],
									'adminkit-enset' . $overriden_style['bpost_' . $post_type->name . '_hierarchy_parent_first']);
							$this->form->input_check(
									$this->settings['bpost_' . $post_type->name . '_taxonomy_referer'],
									__('Allow the referring page to influence the taxonomy selected for the hierarchy.', 'breadcrumb-navxt'),
									false,
									$overriden['bpost_' . $post_type->name . '_taxonomy_referer'],
									'adminkit-enset' . $overriden_style['bpost_' . $post_type->name . '_taxonomy_referer']);
					?>
					<tr valign="top">
						<th scope="row">
							<?php printf(__('%s Hierarchy', 'breadcrumb-navxt'), $post_type->labels->singular_name); ?>
						</th>
						<td>
							<?php
								//We use the value 'page' but really, this will follow the parent post hierarchy
								$this->form->input_radio($this->settings['Epost_' . $post_type->name . '_hierarchy_type'], 'BCN_POST_PARENT', __('Post Parent', 'breadcrumb-navxt'), false, 'adminkit-enset' . $overriden_style['Epost_' . $post_type->name . '_hierarchy_type']);
								$this->form->input_radio($this->settings['Epost_' . $post_type->name . '_hierarchy_type'], 'BCN_DATE', __('Dates', 'breadcrumb-navxt'), false, 'adminkit-enset' . $overriden_style['Epost_' . $post_type->name . '_hierarchy_type']);
								//Loop through all of the taxonomies in the array
								foreach($wp_taxonomies as $taxonomy)
								{
									//Check for non-public taxonomies
									if(!($taxonomy instanceof WP_Taxonomy) || !apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name, $post_type->name))
									{
										continue;
									}
									//We only want custom taxonomies
									if($taxonomy->object_type == $post_type->name || in_array($post_type->name, $taxonomy->object_type))
									{
										$this->form->input_radio($this->settings['Epost_' . $post_type->name . '_hierarchy_type'], $taxonomy->name, $taxonomy->labels->singular_name, false, 'adminkit-enset' . $overriden_style['Epost_' . $post_type->name . '_hierarchy_type']);
									}
								}
							?>
							<p class="description">
							<?php
							if($post_type->hierarchical)
							{
								esc_html_e('The hierarchy which the breadcrumb trail will show.', 'breadcrumb-navxt');
							}
							else
							{
								esc_html_e('The hierarchy which the breadcrumb trail will show. Note that the "Post Parent" option may require an additional plugin to behave as expected since this is a non-hierarchical post type.', 'breadcrumb-navxt');
							}
							echo $overriden['Epost_' . $post_type->name . '_hierarchy_type'];
							?>
							</p>
						</td>
					</tr>
					<?php
						}
					?>
				</table>
				<?php
			}
			do_action($this->unique_prefix . '_after_settings_tab_post', $this->settings);
			//FIXME: Why don't we use the taxonomy loop for all taxonomies? We do it with posts now
			?>
			</fieldset>
			<fieldset id="tax" class="bcn_options alttab">
				<legend class="screen-reader-text" data-title="<?php _e( 'The settings for all taxonomies (including Categories, Tags, and custom taxonomies) are located under this tab.', 'breadcrumb-navxt' );?>"><?php _e( 'Taxonomies', 'breadcrumb-navxt' ); ?></legend>
				<h2><?php _e('Categories', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->form->textbox(
								$this->settings['Htax_category_template'],
								'6',
								false,
								__('The template for category breadcrumbs.', 'breadcrumb-navxt') . $overriden['Htax_category_template'],
								$overriden_style['Htax_category_template']);
						$this->form->textbox(
								$this->settings['Htax_category_template_no_anchor'],
								'4',
								false,
								__('The template for category breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Htax_category_template_no_anchor'],
								$overriden_style['Htax_category_template_no_anchor']);
					?>
				</table>
				<h2><?php _e('Tags', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->form->textbox(
								$this->settings['Htax_post_tag_template'],
								'6',
								false,
								__('The template for tag breadcrumbs.', 'breadcrumb-navxt') . $overriden['Htax_post_tag_template'],
								$overriden_style['Htax_post_tag_template']);
						$this->form->textbox(
								$this->settings['Htax_post_tag_template_no_anchor'],
								'4',
								false,
								__('The template for tag breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Htax_post_tag_template_no_anchor'],
								$overriden_style['Htax_post_tag_template_no_anchor']);
					?>
				</table>
				<h2><?php _e('Post Formats', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->form->textbox(
								$this->settings['Htax_post_format_template'],
								'6',
								false,
								__('The template for post format breadcrumbs.', 'breadcrumb-navxt') . $overriden['Htax_post_format_template'],
								$overriden_style['Htax_post_format_template']);
						$this->form->textbox(
								$this->settings['Htax_post_format_template_no_anchor'],
								'4',
								false,
								__('The template for post_format breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Htax_post_format_template_no_anchor'],
								$overriden_style['Htax_post_format_template_no_anchor']);
					?>
				</table>
			<?php
			//Loop through all of the taxonomies in the array
			foreach($wp_taxonomies as $taxonomy)
			{
				//Check for non-public taxonomies and if the taxonomy wasn't known when defaults were generated
				if(!($taxonomy instanceof WP_Taxonomy) || !apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name, null) || !isset($this->settings['Htax_' . $taxonomy->name . '_template']))
				{
					continue;
				}
				//We only want custom taxonomies
				if(!$taxonomy->_builtin)
				{
					$label_lc = mb_strtolower($taxonomy->label, 'UTF-8');
				?>
				<h3><?php echo mb_convert_case($taxonomy->label, MB_CASE_TITLE, 'UTF-8'); ?></h3>
				<table class="form-table">
					<?php
						$this->form->textbox(
								$this->settings['Htax_' . $taxonomy->name . '_template'],
								'6',
								false,
								sprintf(__('The template for %s breadcrumbs.', 'breadcrumb-navxt') . $overriden['Htax_' . $taxonomy->name . '_template'], $label_lc),
								$overriden_style['Htax_' . $taxonomy->name . '_template']);
						$this->form->textbox(
								$this->settings['Htax_' . $taxonomy->name . '_template_no_anchor'],
								'4',
								false,
								sprintf(__('The template for %s breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Htax_' . $taxonomy->name . '_template_no_anchor'], $label_lc),
								$overriden_style['Htax_' . $taxonomy->name . '_template_no_anchor']);
					?>
				</table>
				<?php
				}
			}
			do_action($this->unique_prefix . '_after_settings_tab_taxonomy', $this->settings); ?>
			</fieldset>
			<fieldset id="miscellaneous" class="bcn_options">
				<legend class="screen-reader-text" data-title="<?php _e( 'The settings for author and date archives, searches, and 404 pages are located under this tab.', 'breadcrumb-navxt' );?>"><?php _e( 'Miscellaneous', 'breadcrumb-navxt' ); ?></legend>
				<h2><?php _e('Author Archives', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->form->textbox(
								$this->settings['Hauthor_template'],
								'6',
								false,
								__('The template for author breadcrumbs.', 'breadcrumb-navxt') . $overriden['Hauthor_template'],
								$overriden_style['Hauthor_template']);
						$this->form->textbox(
								$this->settings['Hauthor_template_no_anchor'],
								'4',
								false,
								__('The template for author breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Hauthor_template_no_anchor'],
								$overriden_style['Hauthor_template_no_anchor']);
						$this->form->input_select(
								$this->settings['Eauthor_name'],
								$this->settings['Eauthor_name']->get_allowed_vals(),
								false,
								__('display_name uses the name specified in "Display name publicly as" under the user profile the others correspond to options in the user profile.', 'breadcrumb-navxt') . $overriden['Eauthor_name'],
								$overriden_style['Eauthor_name']);
						$optid = form::get_valid_id('aauthor_root');
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo $optid;?>"><?php esc_html_e('Author Root Page', 'breadcrumb-navxt');?></label>
						</th>
						<td>
							<?php wp_dropdown_pages(array(
									'name' => $this->unique_prefix . '_options[aauthor_root]',
									'id' => $optid,
									'echo' => 1,
									'show_option_none' => __( '&mdash; Select &mdash;' ),
									'option_none_value' => '0',
									'selected' => $this->settings['aauthor_root']->get_value(),
									'class' => $overriden_style['aauthor_root']
							));
							if(isset($overriden['aauthor_root']) && $overriden['aauthor_root'] !== '')
							{
								printf('<p class="description">%s</p>', $overriden['aauthor_root']);
							}
							?>
						</td>
					</tr>
				</table>
				<h2><?php _e('Miscellaneous', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->form->textbox(
								$this->settings['Hdate_template'],
								'6',
								false,
								__('The template for date breadcrumbs.', 'breadcrumb-navxt') . $overriden['Hdate_template'],
								$overriden_style['Hdate_template']);
						$this->form->textbox(
								$this->settings['Hdate_template_no_anchor'],
								'4',
								false,
								__('The template for date breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Hdate_template_no_anchor'],
								$overriden_style['Hdate_template_no_anchor']);
						$this->form->textbox(
								$this->settings['Hsearch_template'],
								'6',
								false,
								__('The anchor template for search breadcrumbs, used only when the search results span several pages.', 'breadcrumb-navxt') . $overriden['Hsearch_template'],
								$overriden_style['Hsearch_template']);
						$this->form->textbox(
								$this->settings['Hsearch_template_no_anchor'],
								'4',
								false,
								__('The anchor template for search breadcrumbs, used only when the search results span several pages and the breadcrumb is not linked.', 'breadcrumb-navxt') . $overriden['Hsearch_template_no_anchor'],
								$overriden_style['Hsearch_template_no_anchor']);
						$this->form->input_text(
								$this->settings['S404_title'],
								'regular-text' . $overriden_style['S404_title'],
								false,
								$overriden['S404_title']);
						$this->form->textbox(
								$this->settings['H404_template'],
								'4',
								false,
								__('The template for 404 breadcrumbs.', 'breadcrumb-navxt') . $overriden['H404_template'],
								$overriden_style['H404_template']);
					?>
				</table>
				<h2><?php _e('Deprecated', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e('Title Length', 'breadcrumb-navxt'); ?>
						</th>
						<td>
							<label>
								<input name="bcn_options[blimit_title]" type="checkbox" id="blimit_title" value="true" class="disabled" <?php checked(true, $this->settings['blimit_title']->get_value()); ?> />
								<?php printf(esc_html__('Limit the length of the breadcrumb title. (Deprecated, %suse CSS instead%s)', 'breadcrumb-navxt'), '<a title="' . esc_attr__('Go to the guide on trimming breadcrumb title lengths with CSS', 'breadcrumb-navxt') . '" href="https://mtekk.us/archives/guides/trimming-breadcrumb-title-lengths-with-css/">', '</a>');?>
							</label><br />
							<ul>
								<li>
									<label for="amax_title_length">
										<?php esc_html_e('Max Title Length: ','breadcrumb-navxt');?>
										<input type="number" name="bcn_options[amax_title_length]" id="amax_title_length" min="1" step="1" value="<?php echo esc_html($this->settings['amax_title_length']->get_value(), ENT_COMPAT, 'UTF-8'); ?>" class="small-text disabled" />
									</label>
								</li>
							</ul>
						</td>
					</tr>
				</table>
				<?php do_action($this->unique_prefix . '_after_settings_tab_miscellaneous', $this->settings); ?>
			</fieldset>
			<?php do_action($this->unique_prefix . '_after_settings_tabs', $this->settings); ?>
			</div>
			<p class="submit"><input type="submit" class="button-primary" name="bcn_admin_options" value="<?php esc_attr_e('Save Changes') ?>" /></p>
		</form>
		</div>
		<?php
	}
}
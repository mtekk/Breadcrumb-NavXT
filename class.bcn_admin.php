<?php
/*  Copyright 2007-2015  John Havlik  (email : john.havlik@mtekk.us)

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
//Do a PHP version check, require 5.2 or newer
if(version_compare(phpversion(), '5.3.0', '<'))
{
	//Only purpose of this function is to echo out the PHP version error
	function bcn_phpold()
	{
		printf('<div class="error"><p>' . __('Your PHP version is too old, please upgrade to a newer version. Your version is %1$s, Breadcrumb NavXT requires %2$s', 'breadcrumb-navxt') . '</p></div>', phpversion(), '5.3.0');
	}
	//If we are in the admin, let's print a warning then return
	if(is_admin())
	{
		add_action('admin_notices', 'bcn_phpold');
	}
	return;
}
//Include admin base class
if(!class_exists('mtekk_adminKit'))
{
	require_once(dirname(__FILE__) . '/includes/class.mtekk_adminkit.php');
}
/**
 * The administrative interface class 
 * 
 */
class bcn_admin extends mtekk_adminKit
{
	const version = '5.2.2';
	protected $full_name = 'Breadcrumb NavXT Settings';
	protected $short_name = 'Breadcrumb NavXT';
	protected $access_level = 'manage_options';
	protected $identifier = 'breadcrumb-navxt';
	protected $unique_prefix = 'bcn';
	protected $plugin_basename = null;
	protected $support_url = 'http://mtekk.us/archives/wordpress/plugins-wordpress/breadcrumb-navxt-';
	protected $breadcrumb_trail = null;
	/**
	 * Administrative interface class default constructor
	 * 
	 * @param bcn_breadcrumb_trail $breadcrumb_trail a breadcrumb trail object
	 * @param string $basename The basename of the plugin
	 */
	function __construct(bcn_breadcrumb_trail &$breadcrumb_trail, $basename)
	{
		$this->breadcrumb_trail =& $breadcrumb_trail;
		$this->plugin_basename = $basename;
		//Grab defaults from the breadcrumb_trail object
		$this->opt =& $this->breadcrumb_trail->opt;
		//We're going to make sure we load the parent's constructor
		parent::__construct();
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
		breadcrumb_navxt::setup_options($this->opt);
	}
	/**
	 * Makes sure the current user can manage options to proceed
	 */
	function security()
	{
		//If the user can not manage options we will die on them
		if(!current_user_can($this->access_level))
		{
			wp_die(__('Insufficient privileges to proceed.', 'breadcrumb-navxt'));
		}
	}
	/**
	 * Upgrades input options array, sets to $this->opt
	 * 
	 * @param array $opts
	 * @param string $version the version of the passed in options
	 */
	function opts_upgrade($opts, $version)
	{
		global $wp_post_types;
		//If our version is not the same as in the db, time to update
		if(version_compare($version, $this::version, '<'))
		{
			//Upgrading to 3.8.1
			if(version_compare($version, '3.8.1', '<'))
			{
				$opts['post_page_root'] = get_option('page_on_front');
				$opts['post_post_root'] = get_option('page_for_posts');
			}
			//Upgrading to 4.0
			if(version_compare($version, '4.0.0', '<'))
			{
				//Only migrate if we haven't migrated yet
				if(isset($opts['current_item_linked']))
				{
					//Loop through the old options, migrate some of them
					foreach($opts as $option => $value)
					{
						//Handle all of our boolean options first, they're real easy, just add a 'b'
						if(strpos($option, 'display') > 0 || $option == 'current_item_linked')
						{
							$this->breadcrumb_trail->opt['b' . $option] = $value;
						}
						//Handle migration of anchor templates to the templates
						else if(strpos($option, 'anchor') > 0)
						{
							$parts = explode('_', $option);
							//Do excess slash removal sanitation
							$this->breadcrumb_trail->opt['H' . $parts[0] . '_template'] = $value . '%htitle%</a>';
						}
						//Handle our abs integers
						else if($option == 'max_title_length' || $option == 'post_post_root' || $option == 'post_page_root')
						{
							$this->breadcrumb_trail->opt['a' . $option] = $value;
						}
						//Now everything else, minus prefix and suffix
						else if(strpos($option, 'prefix') === false && strpos($option, 'suffix') === false)
						{
							$this->breadcrumb_trail->opt['S' . $option] = $value;
						}
					}
				}
				//Add in the new settings for CPTs introduced in 4.0
				foreach($wp_post_types as $post_type)
				{
					//We only want custom post types
					if(!$post_type->_builtin)
					{
						//Add in the archive_display option
						$this->breadcrumb_trail->opt['bpost_' . $post_type->name . '_archive_display'] = $post_type->has_archive;
					}
				}
				$opts = $this->breadcrumb_trail->opt;
			}
			if(version_compare($version, '4.0.1', '<'))
			{
				if(isset($opts['Hcurrent_item_template_no_anchor']))
				{
					unset($opts['Hcurrent_item_template_no_anchor']);
				}
				if(isset($opts['Hcurrent_item_template']))
				{
					unset($opts['Hcurrent_item_template']);
				}
			}
			//Upgrading to 4.3.0
			if(version_compare($version, '4.3.0', '<'))
			{
				//Removed home_title
				if(isset($opts['Shome_title']))
				{
					unset($opts['Shome_title']);
				}
				//Removed mainsite_title
				if(isset($opts['Smainsite_title']))
				{
					unset($opts['Smainsite_title']);
				}
			}
			//Upgrading to 5.1.0
			if(version_compare($version, '5.1.0', '<'))
			{
				global $wp_taxonomies;
				foreach($wp_taxonomies as $taxonomy)
				{
					//If we have the old options style for it, update
					if($taxonomy->name !== 'post_format' && isset($opts['H' . $taxonomy->name . '_template']))
					{
						//Migrate to the new setting name
						$opts['Htax_' . $taxonomy->name . '_template'] = $opts['H' . $taxonomy->name . '_template'];
						$opts['Htax_' . $taxonomy->name . '_template_no_anchor'] = $opts['H' . $taxonomy->name . '_template_no_anchor'];
						//Clean up old settings
						unset($opts['H' . $taxonomy->name . '_template']);
						unset($opts['H' . $taxonomy->name . '_template_no_anchor']);
					}
				}
			}
			//Add custom post types
			breadcrumb_navxt::find_posttypes($opts);
			//Add custom taxonomy types
			breadcrumb_navxt::find_taxonomies($opts);
			//Set the max title length to 20 if we are not limiting the title and the length was 0
			if(!$opts['blimit_title'] && $opts['amax_title_length'] == 0)
			{
				$opts['amax_title_length'] = 20;
			}
		}
		//Save the passed in opts to the object's option array
		$this->opt = $opts;
	}
	function opts_update_prebk(&$opts)
	{
		//This may no longer be needed
		breadcrumb_navxt::setup_options($opts);
		$opts = apply_filters('bcn_opts_update_prebk', $opts);
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
			$general_tab = '<p>' . __('Tips for the settings are located below select options.', 'breadcrumb-navxt') .
				'</p><h5>' . __('Resources', 'breadcrumb-navxt') . '</h5><ul><li>' .
				sprintf(__("%sTutorials and How Tos%s: There are several guides, tutorials, and how tos available on the author's website.", 'breadcrumb-navxt'),'<a title="' . __('Go to the Breadcrumb NavXT tag archive.', 'breadcrumb-navxt') . '" href="http://mtekk.us/archives/tag/breadcrumb-navxt">', '</a>') . '</li><li>' .
				sprintf(__('%sOnline Documentation%s: Check out the documentation for more indepth technical information.', 'breadcrumb-navxt'), '<a title="' . __('Go to the Breadcrumb NavXT online documentation', 'breadcrumb-navxt') . '" href="http://mtekk.us/code/breadcrumb-navxt/breadcrumb-navxt-doc/">', '</a>') . '</li><li>' .
				sprintf(__('%sReport a Bug%s: If you think you have found a bug, please include your WordPress version and details on how to reproduce the bug.', 'breadcrumb-navxt'),'<a title="' . __('Go to the Breadcrumb NavXT support post for your version.', 'breadcrumb-navxt') . '" href="http://mtekk.us/archives/wordpress/plugins-wordpress/breadcrumb-navxt-' . $this::version . '/#respond">', '</a>') . '</li></ul>' . 
				'<h5>' . __('Giving Back', 'breadcrumb-navxt') . '</h5><ul><li>' .
				sprintf(__('%sDonate%s: Love Breadcrumb NavXT and want to help development? Consider buying the author a beer.', 'breadcrumb-navxt'),'<a title="' . __('Go to PayPal to give a donation to Breadcrumb NavXT.', 'breadcrumb-navxt') . '" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=FD5XEU783BR8U&lc=US&item_name=Breadcrumb%20NavXT%20Donation&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">', '</a>') . '</li><li>' .
				sprintf(__('%sTranslate%s: Is your language not available? Contact John Havlik to get translating.', 'breadcrumb-navxt'),'<a title="' . __('Go to the Breadcrumb NavXT translation project.', 'breadcrumb-navxt') . '" href="http://translate.mtekk.us/projects/breadcrumb-navxt">', '</a>') . '</li></ul>';
			
			$screen->add_help_tab(
				array(
				'id' => $this->identifier . '-base',
				'title' => __('General', 'breadcrumb-navxt'),
				'content' => $general_tab
				));
			$quickstart_tab = '<p>' . __('For the settings on this page to take effect, you must either use the included Breadcrumb NavXT widget, or place either of the code sections below into your theme.', 'breadcrumb-navxt') .
				'</p><h5>' . __('Breadcrumb trail with separators', 'breadcrumb-navxt') . '</h5><pre><code>&lt;div class="breadcrumbs"&gt;' . "
	&lt;?php if(function_exists('bcn_display'))
	{
		bcn_display();
	}?&gt;
&lt;/div&gt;</code></pre>" .
				'<h5>' . __('Breadcrumb trail in list form', 'breadcrumb-navxt').'</h5><pre><code>&lt;ol class="breadcrumbs"&gt;'."
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
			$styling_tab = '<p>' . __('Using the code from the Quick Start section above, the following CSS can be used as base for styling your breadcrumb trail.', 'breadcrumb-navxt') . '</p>' .
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
		if(defined('MULTISITE') && MULTISITE)
		{
			if(defined('BCN_SETTINGS_USE_LOCAL') && BCN_SETTINGS_USE_LOCAL)
			{
				
			}
			else if(defined('BCN_SETTINGS_USE_NETWORK') && BCN_SETTINGS_USE_NETWORK)
			{
				$this->message['updated fade'][] = __('Warning: Your network settings will override any settings set in this page.', 'breadcrumb-navxt');
			}
			else if(defined('BCN_SETTINGS_FAVOR_LOCAL') && BCN_SETTINGS_FAVOR_LOCAL)
			{
				$this->message['updated fade'][] = __('Warning: Your network settings may override any settings set in this page.', 'breadcrumb-navxt');
			}
			else if(defined('BCN_SETTINGS_FAVOR_NETWORK') && BCN_SETTINGS_FAVOR_NETWORK)
			{
				$this->message['updated fade'][] = __('Warning: Your network settings may override any settings set in this page.', 'breadcrumb-navxt');
			}
			//Fall through if no settings mode was set
			else
			{
				$this->message['updated fade'][] = __('Warning: No BCN_SETTINGS_* define statement found, defaulting to BCN_SETTINGS_FAVOR_NETWORK.', 'breadcrumb-navxt');
				$this->message['updated fade'][] = __('Warning: Your network settings will override any settings set in this page.', 'breadcrumb-navxt');
			}
		}
    }
	/**
	 * A message function that checks for deprecated settings that are set and warns the user
	 */
	function deprecated_settings_warn()
	{
		//We're deprecating the limit title length setting, let the user know the new method of accomplishing this
		if(isset($this->opt['blimit_title']) && $this->opt['blimit_title'])
		{
			$this->message['updated fade'][] = sprintf(__('Warning: Your are using a deprecated setting "Title Length" (see Miscellaneous &gt; Deprecated), please %1$suse CSS instead%2$s.', 'breadcrumb-navxt'), '<a title="' . __('Go to the guide on trimming breadcrumb title lengths with CSS', 'breadcrumb-navxt') . '" href="https://mtekk.us/archives/guides/trimming-breadcrumb-title-lengths-with-css/">', '</a>');
		}
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
		//Do a check for multisite settings mode
		$this->multisite_settings_warn();
		//Display our messages
		$this->messages();
		?>
		<div class="wrap"><h2><?php _e('Breadcrumb NavXT Settings', 'breadcrumb-navxt'); ?></h2>
		<?php
		//We exit after the version check if there is an action the user needs to take before saving settings
		if(!$this->version_check(get_option($this->unique_prefix . '_version')))
		{
			return;
		}
		?>
		<form action="<?php echo $this->admin_url(); ?>" method="post" id="bcn_admin-options">
			<?php settings_fields('bcn_options');?>
			<div id="hasadmintabs">
			<fieldset id="general" class="bcn_options">
				<h3 class="tab-title" title="<?php _e('A collection of settings most likely to be modified are located under this tab.', 'breadcrumb-navxt');?>"><?php _e('General', 'breadcrumb-navxt'); ?></h3>
				<h3><?php _e('General', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Breadcrumb Separator', 'breadcrumb-navxt'), 'hseparator', 'regular-text', false, __('Placed in between each breadcrumb.', 'breadcrumb-navxt'));
						do_action($this->unique_prefix . '_settings_general', $this->opt);
					?>
				</table>
				<h3><?php _e('Current Item', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_check(__('Link Current Item', 'breadcrumb-navxt'), 'bcurrent_item_linked', __('Yes', 'breadcrumb-navxt'));
						$this->input_check(__('Paged Breadcrumb', 'breadcrumb-navxt'), 'bpaged_display', __('Include the paged breadcrumb in the breadcrumb trail.', 'breadcrumb-navxt'), false, __('Indicates that the user is on a page other than the first on paginated posts/pages.', 'breadcrumb-navxt'));
						$this->input_text(__('Paged Template', 'breadcrumb-navxt'), 'Hpaged_template', 'large-text', false, __('The template for paged breadcrumbs.', 'breadcrumb-navxt'));
						do_action($this->unique_prefix . '_settings_current_item', $this->opt);
					?>
				</table>
				<h3><?php _e('Home Breadcrumb', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table adminkit-engroup">
					<?php 
						$this->input_check(__('Home Breadcrumb', 'breadcrumb-navxt'), 'bhome_display', __('Place the home breadcrumb in the trail.', 'breadcrumb-navxt'));
						$this->input_text(__('Home Template', 'breadcrumb-navxt'), 'Hhome_template', 'large-text', false, __('The template for the home breadcrumb.', 'breadcrumb-navxt'));
						$this->input_text(__('Home Template (Unlinked)', 'breadcrumb-navxt'), 'Hhome_template_no_anchor', 'large-text', false, __('The template for the home breadcrumb, used when the breadcrumb is not linked.', 'breadcrumb-navxt'));
						do_action($this->unique_prefix . '_settings_home', $this->opt);
					?>
				</table>
				<h3><?php _e('Blog Breadcrumb ', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table adminkit-engroup">
					<?php
						$this->input_check(__('Blog Breadcrumb', 'breadcrumb-navxt'), 'bblog_display', __('Place the blog breadcrumb in the trail.', 'breadcrumb-navxt'), (get_option('show_on_front') !== "page"));
						$this->input_text(__('Blog Template', 'breadcrumb-navxt'), 'Hblog_template', 'large-text', (get_option('show_on_front') !== "page"), __('The template for the blog breadcrumb, used only in static front page environments.', 'breadcrumb-navxt'));
						$this->input_text(__('Blog Template (Unlinked)', 'breadcrumb-navxt'), 'Hblog_template_no_anchor', 'large-text', (get_option('show_on_front') !== "page"), __('The template for the blog breadcrumb, used only in static front page environments and when the breadcrumb is not linked.', 'breadcrumb-navxt'));
						do_action($this->unique_prefix . '_settings_blog', $this->opt);
					?>
				</table>
				<h3><?php _e('Mainsite Breadcrumb', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table adminkit-engroup">
					<?php
						$this->input_check(__('Main Site Breadcrumb', 'breadcrumb-navxt'), 'bmainsite_display', __('Place the main site home breadcrumb in the trail in an multisite setup.', 'breadcrumb-navxt'), !is_multisite());
						$this->input_text(__('Main Site Home Template', 'breadcrumb-navxt'), 'Hmainsite_template', 'large-text', !is_multisite(), __('The template for the main site home breadcrumb, used only in multisite environments.', 'breadcrumb-navxt'));
						$this->input_text(__('Main Site Home Template (Unlinked)', 'breadcrumb-navxt'), 'Hmainsite_template_no_anchor', 'large-text', !is_multisite(), __('The template for the main site home breadcrumb, used only in multisite environments and when the breadcrumb is not linked.', 'breadcrumb-navxt'));
						do_action($this->unique_prefix . '_settings_mainsite', $this->opt);
					?>
				</table>
				<?php do_action($this->unique_prefix . '_after_settings_tab_general', $this->opt); ?>
			</fieldset>
			<fieldset id="post" class="bcn_options">
				<h3 class="tab-title" title="<?php _e('The settings for all post types (Posts, Pages, and Custom Post Types) are located under this tab.', 'breadcrumb-navxt');?>"><?php _e('Post Types', 'breadcrumb-navxt'); ?></h3>
				<h3><?php _e('Posts', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->input_text(__('Post Template', 'breadcrumb-navxt'), 'Hpost_post_template', 'large-text', false, __('The template for post breadcrumbs.', 'breadcrumb-navxt'));
						$this->input_text(__('Post Template (Unlinked)', 'breadcrumb-navxt'), 'Hpost_post_template_no_anchor', 'large-text', false, __('The template for post breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
						$this->input_check(__('Post Hierarchy Display', 'breadcrumb-navxt'), 'bpost_post_taxonomy_display', __('Show the hierarchy (specified below) leading to a post in the breadcrumb trail.', 'breadcrumb-navxt'), false, '', 'adminkit-enset-ctrl adminkit-enset');
					?>
					<tr valign="top">
						<th scope="row">
							<?php _e('Post Hierarchy', 'breadcrumb-navxt'); ?>
						</th>
						<td>
							<?php
								$this->input_radio('Spost_post_taxonomy_type', 'category', __('Categories'), false, 'adminkit-enset');
								$this->input_radio('Spost_post_taxonomy_type', 'date', __('Dates', 'breadcrumb-navxt'), false, 'adminkit-enset');
								$this->input_radio('Spost_post_taxonomy_type', 'post_tag', __('Tags'), false, 'adminkit-enset');
								//We use the value 'page' but really, this will follow the parent post hierarchy
								$this->input_radio('Spost_post_taxonomy_type', 'page', __('Post Parent', 'breadcrumb-navxt'), false, 'adminkit-enset');
								//Loop through all of the taxonomies in the array
								foreach($wp_taxonomies as $taxonomy)
								{
									//Check for non-public taxonomies
									if(!apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name))
									{
										continue;
									}
									//We only want custom taxonomies
									if(($taxonomy->object_type == 'post' || is_array($taxonomy->object_type) && in_array('post', $taxonomy->object_type)) && !$taxonomy->_builtin)
									{
										$this->input_radio('Spost_post_taxonomy_type', $taxonomy->name, mb_convert_case($taxonomy->label, MB_CASE_TITLE, 'UTF-8'), false, 'adminkit-enset');
									}
								}
							?>
							<p class="description"><?php _e('The hierarchy which the breadcrumb trail will show. Note that the "Post Parent" option may require an additional plugin to behave as expected since this is a non-hierarchical post type.', 'breadcrumb-navxt'); ?></p>
						</td>
					</tr>
				</table>
				<h3><?php _e('Pages', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Page Template', 'breadcrumb-navxt'), 'Hpost_page_template', 'large-text', false, __('The template for page breadcrumbs.', 'breadcrumb-navxt'));
						$this->input_text(__('Page Template (Unlinked)', 'breadcrumb-navxt'), 'Hpost_page_template_no_anchor', 'large-text', false, __('The template for page breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
					?>
				</table>
				<h3><?php _e('Attachments', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Attachment Template', 'breadcrumb-navxt'), 'Hpost_attachment_template', 'large-text', false, __('The template for attachment breadcrumbs.', 'breadcrumb-navxt'));
						$this->input_text(__('Attachment Template (Unlinked)', 'breadcrumb-navxt'), 'Hpost_attachment_template_no_anchor', 'large-text', false, __('The template for attachment breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
					?>
				</table>
			<?php
			//Loop through all of the post types in the array
			foreach($wp_post_types as $post_type)
			{
				//Check for non-public CPTs
				if(!apply_filters('bcn_show_cpt_private', $post_type->public, $post_type->name))
				{
					continue;
				}
				//We only want custom post types
				if(!$post_type->_builtin)
				{
					$singular_name_lc = mb_strtolower($post_type->labels->singular_name, 'UTF-8');
				?>
				<h3><?php echo $post_type->labels->singular_name; ?></h3>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->input_text(sprintf(__('%s Template', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'Hpost_' . $post_type->name . '_template', 'large-text', false, sprintf(__('The template for %s breadcrumbs.', 'breadcrumb-navxt'), $singular_name_lc));
						$this->input_text(sprintf(__('%s Template (Unlinked)', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'Hpost_' . $post_type->name . '_template_no_anchor', 'large-text', false, sprintf(__('The template for %s breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'), $singular_name_lc));
						$optid = $this->get_valid_id('apost_' . $post_type->name . '_root');
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo $optid;?>"><?php printf(__('%s Root Page', 'breadcrumb-navxt'), $post_type->labels->singular_name);?></label>
						</th>
						<td>
							<?php wp_dropdown_pages(array('name' => $this->unique_prefix . '_options[apost_' . $post_type->name . '_root]', 'id' => $optid, 'echo' => 1, 'show_option_none' => __( '&mdash; Select &mdash;' ), 'option_none_value' => '0', 'selected' => $this->opt['apost_' . $post_type->name . '_root']));?>
						</td>
					</tr>
					<?php
						$this->input_check(sprintf(__('%s Archive Display', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'bpost_' . $post_type->name . '_archive_display', sprintf(__('Show the breadcrumb for the %s post type archives in the breadcrumb trail.', 'breadcrumb-navxt'), $singular_name_lc), !$post_type->has_archive);
						$this->input_check(sprintf(__('%s Hierarchy Display', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'bpost_' . $post_type->name . '_taxonomy_display', sprintf(__('Show the hierarchy (specified below) leading to a %s in the breadcrumb trail.', 'breadcrumb-navxt'), $singular_name_lc), false, '', 'adminkit-enset-ctrl adminkit-enset');
					?>
					<tr valign="top">
						<th scope="row">
							<?php printf(__('%s Hierarchy', 'breadcrumb-navxt'), $post_type->labels->singular_name); ?>
						</th>
						<td>
							<?php
								//We use the value 'page' but really, this will follow the parent post hierarchy
								$this->input_radio('Spost_' . $post_type->name . '_taxonomy_type', 'page', __('Post Parent', 'breadcrumb-navxt'), false, 'adminkit-enset');
								//Loop through all of the taxonomies in the array
								foreach($wp_taxonomies as $taxonomy)
								{
									//Check for non-public taxonomies
									if(!apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name))
									{
										continue;
									}
									//We only want custom taxonomies
									if($taxonomy->object_type == $post_type->name || in_array($post_type->name, $taxonomy->object_type))
									{
										$this->input_radio('Spost_' . $post_type->name . '_taxonomy_type', $taxonomy->name, $taxonomy->labels->singular_name, false, 'adminkit-enset');
									}
								}
							?>
							<p class="description">
							<?php
							if($post_type->hierarchical)
							{
								_e('The hierarchy which the breadcrumb trail will show.', 'breadcrumb-navxt'); 
							}
							else
							{
								_e('The hierarchy which the breadcrumb trail will show. Note that the "Post Parent" option may require an additional plugin to behave as expected since this is a non-hierarchical post type.', 'breadcrumb-navxt');
							}
							?>
							</p>
						</td>
					</tr>
				</table>
					<?php
				}
			}
			do_action($this->unique_prefix . '_after_settings_tab_post', $this->opt);
			?>
			</fieldset>
			<fieldset id="tax" class="bcn_options alttab">
				<h3 class="tab-title" title="<?php _e('The settings for all taxonomies (including Categories, Tags, and custom taxonomies) are located under this tab.', 'breadcrumb-navxt');?>"><?php _e('Taxonomies', 'breadcrumb-navxt'); ?></h3>
				<h3><?php _e('Categories', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Category Template', 'breadcrumb-navxt'), 'Htax_category_template', 'large-text', false, __('The template for category breadcrumbs.', 'breadcrumb-navxt'));
						$this->input_text(__('Category Template (Unlinked)', 'breadcrumb-navxt'), 'Htax_category_template_no_anchor', 'large-text', false, __('The template for category breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
					?>
				</table>
				<h3><?php _e('Tags', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Tag Template', 'breadcrumb-navxt'), 'Htax_post_tag_template', 'large-text', false, __('The template for tag breadcrumbs.', 'breadcrumb-navxt'));
						$this->input_text(__('Tag Template (Unlinked)', 'breadcrumb-navxt'), 'Htax_post_tag_template_no_anchor', 'large-text', false, __('The template for tag breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
					?>
				</table>
				<h3><?php _e('Post Formats', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Post Format Template', 'breadcrumb-navxt'), 'Htax_post_format_template', 'large-text', false, __('The template for post format breadcrumbs.', 'breadcrumb-navxt'));
						$this->input_text(__('Post Format Template (Unlinked)', 'breadcrumb-navxt'), 'Htax_post_format_template_no_anchor', 'large-text', false, __('The template for post_format breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
					?>
				</table>
			<?php
			//Loop through all of the taxonomies in the array
			foreach($wp_taxonomies as $taxonomy)
			{
				//Check for non-public taxonomies
				if(!apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name))
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
						$this->input_text(sprintf(__('%s Template', 'breadcrumb-navxt'), $taxonomy->labels->singular_name), 'Htax_' . $taxonomy->name . '_template', 'large-text', false, sprintf(__('The template for %s breadcrumbs.', 'breadcrumb-navxt'), $label_lc));
						$this->input_text(sprintf(__('%s Template (Unlinked)', 'breadcrumb-navxt'), $taxonomy->labels->singular_name), 'Htax_' . $taxonomy->name . '_template_no_anchor', 'large-text', false, sprintf(__('The template for %s breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'), $label_lc));
					?>
				</table>
				<?php
				}
			}
			do_action($this->unique_prefix . '_after_settings_tab_taxonomy', $this->opt); ?>
			</fieldset>
			<fieldset id="miscellaneous" class="bcn_options">
				<h3 class="tab-title" title="<?php _e('The settings for author and date archives, searches, and 404 pages are located under this tab.', 'breadcrumb-navxt');?>"><?php _e('Miscellaneous', 'breadcrumb-navxt'); ?></h3>
				<h3><?php _e('Author Archives', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Author Template', 'breadcrumb-navxt'), 'Hauthor_template', 'large-text', false, __('The template for author breadcrumbs.', 'breadcrumb-navxt'));
						$this->input_text(__('Author Template (Unlinked)', 'breadcrumb-navxt'), 'Hauthor_template_no_anchor', 'large-text', false, __('The template for author breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
						$this->input_select(__('Author Display Format', 'breadcrumb-navxt'), 'Sauthor_name', array("display_name", "nickname", "first_name", "last_name"), false, __('display_name uses the name specified in "Display name publicly as" under the user profile the others correspond to options in the user profile.', 'breadcrumb-navxt'));
					?>
				</table>
				<h3><?php _e('Miscellaneous', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Date Template', 'breadcrumb-navxt'), 'Hdate_template', 'large-text', false, __('The template for date breadcrumbs.', 'breadcrumb-navxt'));
						$this->input_text(__('Date Template (Unlinked)', 'breadcrumb-navxt'), 'Hdate_template_no_anchor', 'large-text', false, __('The template for date breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
						$this->input_text(__('Search Template', 'breadcrumb-navxt'), 'Hsearch_template', 'large-text', false, __('The anchor template for search breadcrumbs, used only when the search results span several pages.', 'breadcrumb-navxt'));
						$this->input_text(__('Search Template (Unlinked)', 'breadcrumb-navxt'), 'Hsearch_template_no_anchor', 'large-text', false, __('The anchor template for search breadcrumbs, used only when the search results span several pages and the breadcrumb is not linked.', 'breadcrumb-navxt'));
						$this->input_text(__('404 Title', 'breadcrumb-navxt'), 'S404_title', 'regular-text');
						$this->input_text(__('404 Template', 'breadcrumb-navxt'), 'H404_template', 'large-text', false, __('The template for 404 breadcrumbs.', 'breadcrumb-navxt'));
					?>
				</table>
				<h3><?php _e('Deprecated', 'breadcrumb-navxt'); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e('Title Length', 'breadcrumb-navxt'); ?>						
						</th>
						<td>
							<label>
								<input name="bcn_options[blimit_title]" type="checkbox" id="blimit_title" value="true" <?php checked(true, $this->opt['blimit_title']); ?> />
								<?php printf(__('Limit the length of the breadcrumb title. (Deprecated, %suse CSS instead%s)', 'breadcrumb-navxt'), '<a title="' . __('Go to the guide on trimming breadcrumb title lengths with CSS', 'breadcrumb-navxt') . '" href="https://mtekk.us/archives/guides/trimming-breadcrumb-title-lengths-with-css/">', '</a>');?>
							</label><br />
							<ul>
								<li>
									<label for="amax_title_length">
										<?php _e('Max Title Length: ','breadcrumb-navxt');?>
										<input type="number" name="bcn_options[amax_title_length]" id="amax_title_length" min="1" step="1" value="<?php echo esc_html($this->opt['amax_title_length'], ENT_COMPAT, 'UTF-8'); ?>" class="small-text" />
									</label>
								</li>
							</ul>
						</td>
					</tr>
				</table>
				<?php do_action($this->unique_prefix . '_after_settings_tab_miscellaneous', $this->opt); ?>
			</fieldset>
			<?php do_action($this->unique_prefix . '_after_settings_tabs', $this->opt); ?>
			</div>
			<p class="submit"><input type="submit" class="button-primary" name="bcn_admin_options" value="<?php esc_attr_e('Save Changes') ?>" /></p>
		</form>
		</div>
		<?php
	}
}
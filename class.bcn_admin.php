<?php
/*
	Copyright 2015-2021  John Havlik  (email : john.havlik@mtekk.us)

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
//Include admin base class
if(!class_exists('mtekk_adminKit'))
{
	require_once(dirname(__FILE__) . '/includes/adminKit/class.mtekk_adminkit.php');
}
/**
 * The administrative interface class 
 * 
 */
class bcn_admin extends mtekk_adminKit
{
	const version = '6.6.0';
	protected $full_name = 'Breadcrumb NavXT Settings';
	protected $short_name = 'Breadcrumb NavXT';
	protected $access_level = 'bcn_manage_options';
	protected $identifier = 'breadcrumb-navxt';
	protected $unique_prefix = 'bcn';
	protected $plugin_basename = null;
	protected $support_url = 'https://wordpress.org/support/plugin/breadcrumb-navxt/';
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
		$this->full_name = esc_html__('Breadcrumb NavXT Settings', 'breadcrumb-navxt');
		//Grab defaults from the breadcrumb_trail object
		$this->opt =& $this->breadcrumb_trail->opt;
		//We're going to make sure we load the parent's constructor
		parent::__construct();
	}
	function setup_setting_defaults()
	{
		$this->settings['bmainsite_display'] = new mtekk_adminKit_setting_bool(
				'mainsite_display',
				true,
				__('Main Site Breadcrumb', 'breadcrumb-navxt'),
				false);
		$this->settings['Hmainsite_template'] = new mtekk_adminKit_setting_html(
				'mainsite_template',
				bcn_breadcrumb::get_default_template(),
				__('Main Site Home Template', 'breadcrumb-navxt'),
				false);
		$this->settings['Hmainsite_template_no_anchor'] = new mtekk_adminKit_setting_html(
				'mainsite_template_no_anchor',
				bcn_breadcrumb::default_template_no_anchor,
				__('Main Site Home Template (Unlinked)', 'breadcrumb-navxt'),
				false);
		$this->settings['bhome_display'] = new mtekk_adminKit_setting_bool(
				'home_display',
				true,
				__('Home Breadcrumb', 'breadcrumb-navxt'),
				false);
		$this->settings['Hhome_template'] = new mtekk_adminKit_setting_html(
				'home_template',
				bcn_breadcrumb::get_default_template(),
				__('Home Template', 'breadcrumb-navxt'),
				false);
		$this->settings['Hhome_template_no_anchor'] = new mtekk_adminKit_setting_html(
				'home_template_no_anchor',
				bcn_breadcrumb::default_template_no_anchor,
				__('Home Template (Unlinked)', 'breadcrumb-navxt'),
				false);
		$this->settings['bblog_display'] = new mtekk_adminKit_setting_bool(
				'blog_display',
				true,
				__('Blog Breadcrumb', 'breadcrumb-navxt'),
				false);
		$this->settings['Hseparator'] = new mtekk_adminKit_setting_html(
				'separator',
				' &gt; ',
				__('Breadcrumb Separator', 'breadcrumb-navxt'),
				false);
		$this->settings['Hseparator_higher_dim'] = new mtekk_adminKit_setting_html(
				'separator_higher_dim',
				', ',
				__('Breadcrumb Separator (Higher Dimension)', 'breadcrumb-navxt'),
				false);
		$this->settings['bcurrent_item_linked'] = new mtekk_adminKit_setting_bool(
				'current_item_linked',
				false,
				__('Link Current Item', 'breadcrumb-navxt'),
				false);
		$this->settings['Hpaged_template'] = new mtekk_adminKit_setting_html(
				'paged_template',
				sprintf('<span class="%%type%%">%1$s</span>', esc_attr__('Page %htitle%', 'breadcrumb-navxt')),
				_x('Paged Template', 'Paged as in when on an archive or post that is split into multiple pages', 'breadcrumb-navxt'),
				false);
		$this->settings['bpaged_display'] = new mtekk_adminKit_setting_bool(
				'paged_display',
				false,
				_x('Paged Breadcrumb', 'Paged as in when on an archive or post that is split into multiple pages', 'breadcrumb-navxt'),
				false);
		//Post types
		foreach($GLOBALS['wp_post_types']as $post_type)
		{
			$this->settings['Hpost_' . $post_type->name . '_template'] = new mtekk_adminKit_setting_html(
					'post_' . $post_type->name . '_template',
					bcn_breadcrumb::get_default_template(),
					sprintf(__('%s Template', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false);
			$this->settings['Hpost_' . $post_type->name . '_template_no_anchor'] = new mtekk_adminKit_setting_html(
					'post_' . $post_type->name . '_template_no_anchor',
					bcn_breadcrumb::default_template_no_anchor,
					sprintf(__('%s Template (Unlinked)', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false);
			//Root default depends on post type
			if($post_type->name === 'page')
			{
				$default_root = get_option('page_on_front');
			}
			else if($post_type->name === 'post')
			{
				$default_root = get_option('page_for_posts');
			}
			else
			{
				$default_root = 0;
			}
			$this->settings['apost_' . $post_type->name . '_root'] = new mtekk_adminKit_setting_absint(
					'post_' . $post_type->name . '_root',
					$default_root,
					sprintf(__('%s Root Page', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false);
			//Archive display default depends on post type
			if($post_type->has_archive == true || is_string($post_type->has_archive))
			{
				$default_archive_display = true;
			}
			else
			{
				$default_archive_display = false;
			}
			$this->settings['bpost_' . $post_type->name . '_archive_display'] = new mtekk_adminKit_setting_bool(
					'post_' . $post_type->name . '_archive_display',
					$default_archive_display,
					sprintf(__('%s Archive Display', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false);
			$this->settings['bpost_' . $post_type->name . '_taxonomy_referer'] = new mtekk_adminKit_setting_bool(
					'post_' . $post_type->name . '_taxonomy_referer',
					false,
					sprintf(__('%s Hierarchy Referer Influence', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false);
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
			$this->settings['bpost_' . $post_type->name . '_hierarchy_parent_first'] = new mtekk_adminKit_setting_bool(
					'post_' . $post_type->name . '_hierarchy_parent_first',
					$default_parent_first,
					sprintf(__('%s Hierarchy Use Parent First', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false);
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
					$hierarchy_type_default = 'BCN_PARENT';
				}
			}
			$this->settings['bpost_' . $post_type->name . '_hierarchy_display'] = new mtekk_adminKit_setting_bool(
					'post_' . $post_type->name . '_hierarchy_display',
					$default_hierarchy_display,
					sprintf(__('%s Hierarchy Display', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false);
			//FIXME: This is a new type that is masquerading as a legacy type in array key
			$this->settings['Spost_' . $post_type->name . '_hierarchy_type'] = new mtekk_adminKit_setting_enum(
					'post_' . $post_type->name . '_hierarchy_type',
					$hierarchy_type_default,
					sprintf(__('%s Hierarchy Referer Influence', 'breadcrumb-navxt'), $post_type->labels->singular_name),
					false,
					$hierarchy_type_allowed_values);
		}
		//Taxonomies
		foreach($GLOBALS['wp_taxonomies']as $taxonomy)
		{
			$this->settings['Htax_' . $taxonomy->name. '_template'] = new mtekk_adminKit_setting_html(
					'tax_' . $taxonomy->name. '_template',
					__(sprintf('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the %%title%% %s archives." href="%%link%%" class="%%type%%" bcn-aria-current><span property="name">%%htitle%%</span></a><meta property="position" content="%%position%%"></span>', $taxonomy->labels->singular_name), 'breadcrumb-navxt'),
					sprintf(__('%s Template', 'breadcrumb-navxt'), $taxonomy->labels->singular_name),
					false);
			$this->settings['Htax_' . $taxonomy->name. '_template_no_anchor'] = new mtekk_adminKit_setting_html(
					'tax_' . $taxonomy->name. '_template_no_anchor',
					bcn_breadcrumb::default_template_no_anchor,
					sprintf(__('%s Template (Unlinked)', 'breadcrumb-navxt'), $taxonomy->labels->singular_name),
					false);
		}
		//Miscellaneous
		$this->settings['H404_template'] = new mtekk_adminKit_setting_html(
				'404_template',
				bcn_breadcrumb::get_default_template(),
				__('404 Template', 'breadcrumb-navxt'),
				false);
		$this->settings['S404_title'] = new mtekk_adminKit_setting_string(
				'404_title',
				__('404', 'breadcrumb-navxt'),
				__('404 Title', 'breadcrumb-navxt'),
				false);
		$this->settings['Hsearch_template'] = new mtekk_adminKit_setting_html(
				'search_template',
				sprintf('<span property="itemListElement" typeof="ListItem"><span property="name">%1$s</span><meta property="position" content="%%position%%"></span>',
						sprintf(esc_attr__('Search results for &#39;%1$s&#39;', 'breadcrumb-navxt'),
								sprintf('<a property="item" typeof="WebPage" title="%1$s" href="%%link%%" class="%%type%%" bcn-aria-current>%%htitle%%</a>', esc_attr__('Go to the first page of search results for %title%.', 'breadcrumb-navxt')))),
				__('Search Template', 'breadcrumb-navxt'),
				false);
		$this->settings['Hsearch_template_no_anchor'] = new mtekk_adminKit_setting_html(
				'search_template_no_anchor',
				sprintf('<span class="%%type%%">%1$s</span>',
						sprintf(esc_attr__('Search results for &#39;%1$s&#39;', 'breadcrumb-navxt'), '%htitle%')),
				__('Search Template (Unlinked)', 'breadcrumb-navxt'),
				false);
		$this->settings['Hdate_template'] = new mtekk_adminKit_setting_html(
				'date_template',
				sprintf('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="%1$s" href="%%link%%" class="%%type%%" bcn-aria-current><span property="name">%%htitle%%</span></a><meta property="position" content="%%position%%"></span>', esc_attr__('Go to the %title% archives.', 'breadcrumb-navxt')),
				__('Date Template', 'breadcrumb-navxt'),
				false);
		$this->settings['Hdate_template_no_anchor'] = new mtekk_adminKit_setting_html(
				'date_template_no_anchor',
				bcn_breadcrumb::default_template_no_anchor,
				__('Date Template (Unlinked)', 'breadcrumb-navxt'),
				false);
		$this->settings['Hauthor_template'] = new mtekk_adminKit_setting_html(
				'author_template',
				sprintf('<span property="itemListElement" typeof="ListItem"><span property="name">%1$s</span><meta property="position" content="%%position%%"></span>',
						sprintf(esc_attr__('Articles by: %1$s', 'breadcrumb-navxt'),
								sprintf('<a title="%1$s" href="%%link%%" class="%%type%%" bcn-aria-current>%%htitle%%</a>', esc_attr__('Go to the first page of posts by %title%.', 'breadcrumb-navxt')))),
				__('Author Template', 'breadcrumb-navxt'),
				false);
		$this->settings['Hauthor_template_no_anchor'] = new mtekk_adminKit_setting_html(
				'author_template_no_anchor',
				sprintf('<span class="%%type%%">%1$s</span>',
						sprintf(esc_attr__('Articles by: %1$s', 'breadcrumb-navxt'), '%htitle%')),
				__('Author Template (Unlinked)', 'breadcrumb-navxt'),
				false);
		$this->settings['aauthor_root'] = new mtekk_adminKit_setting_absint(
				'author_root',
				0,
				__('Author Root Page', 'breadcrumb-navxt'),
				false);
		//FIXME: This is a new type that is masquerading as a legacy type in array key
		$this->settings['Sauthor_name'] = new mtekk_adminKit_setting_enum(
				'author_name',
				'display_name',
				__('Author Display Format', 'breadcrumb-navxt'),
				false,
				array('display_name', 'nickname', 'first_name', 'last_name'));
		/**
		 * Here are some deprecated settings
		 */
		$this->settings['blimit_title'] = new mtekk_adminKit_setting_bool(
				'limit_title',
				false,
				__('Limit Title Length', 'breadcrumb-navxt'),
				true);
		$this->settings['amax_title_length'] = new mtekk_adminKit_setting_absint(
				'max_title_length',
				30,
				__('Maximum Title Length', 'breadcrumb-navxt'),
				true);
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
	 * Sets hard constants into the options array
	 * 
	 * @param &$opts The options array to set hard constants into
	 */
	function opts_fix(&$opts)
	{
		$opts['bpost_page_hierarchy_display'] = true;
		$opts['Spost_page_hierarchy_type'] = 'BCN_POST_PARENT';
		$opts['apost_page_root'] = get_option('page_on_front');
	}
	/**
	 * Upgrades input options array, sets to $this->opt
	 * 
	 * @param array $opts
	 * @param string $version the version of the passed in options
	 */
	function opts_upgrade($opts, $version)
	{
		global $wp_post_types, $wp_taxonomies;
		//If our version is not the same as in the db, time to update
		if(version_compare($version, $this::version, '<'))
		{
			require_once(dirname(__FILE__) . '/options_upgrade.php');
			bcn_options_upgrade_handler($opts, $version, $this->breadcrumb_trail->opt);
		}
		//Save the passed in opts to the object's option array
		$this->opt = mtekk_adminKit::parse_args($opts, $this->opt);
		//End with resetting up the options
		breadcrumb_navxt::setup_options($this->opt);
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
			$general_tab = '<p>' . esc_html__('Tips for the settings are located below select options.', 'breadcrumb-navxt') .
				'</p><h5>' . esc_html__('Resources', 'breadcrumb-navxt') . '</h5><ul><li>' .
				sprintf(esc_html__("%sTutorials and How Tos%s: There are several guides, tutorials, and how tos available on the author's website.", 'breadcrumb-navxt'),'<a title="' . esc_attr__('Go to the Breadcrumb NavXT tag archive.', 'breadcrumb-navxt') . '" href="https://mtekk.us/archives/tag/breadcrumb-navxt">', '</a>') . '</li><li>' .
				sprintf(esc_html__('%sOnline Documentation%s: Check out the documentation for more indepth technical information.', 'breadcrumb-navxt'), '<a title="' . esc_attr__('Go to the Breadcrumb NavXT online documentation', 'breadcrumb-navxt') . '" href="https://mtekk.us/code/breadcrumb-navxt/breadcrumb-navxt-doc/">', '</a>') . '</li><li>' .
				sprintf(esc_html__('%sReport a Bug%s: If you think you have found a bug, please include your WordPress version and details on how to reproduce the bug.', 'breadcrumb-navxt'),'<a title="' . esc_attr__('Go to the Breadcrumb NavXT support post for your version.', 'breadcrumb-navxt') . '" href="https://wordpress.org/support/plugin/breadcrumb-navxt/">', '</a>') . '</li></ul>' . 
				'<h5>' . esc_html__('Giving Back', 'breadcrumb-navxt') . '</h5><ul><li>' .
				sprintf(esc_html__('%sDonate%s: Love Breadcrumb NavXT and want to help development? Consider buying the author a beer.', 'breadcrumb-navxt'),'<a title="' . esc_attr__('Go to PayPal to give a donation to Breadcrumb NavXT.', 'breadcrumb-navxt') . '" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=FD5XEU783BR8U&lc=US&item_name=Breadcrumb%20NavXT%20Donation&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">', '</a>') . '</li><li>' .
				sprintf(esc_html__('%sTranslate%s: Is your language not available? Visit the Breadcrumb NavXT translation project on WordPress.org to start translating.', 'breadcrumb-navxt'),'<a title="' . esc_attr__('Go to the Breadcrumb NavXT translation project.', 'breadcrumb-navxt') . '" href="https://translate.wordpress.org/projects/wp-plugins/breadcrumb-navxt">', '</a>') . '</li></ul>';
			
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
				$this->messages[] = new mtekk_adminKit_message(esc_html__('Warning: Your network settings will override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_is_nsiteoveride');
			}
			else if(defined('BCN_SETTINGS_FAVOR_LOCAL') && BCN_SETTINGS_FAVOR_LOCAL)
			{
				$this->messages[] = new mtekk_adminKit_message(esc_html__('Warning: Your network settings may override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_is_isitemayoveride');
			}
			else if(defined('BCN_SETTINGS_FAVOR_NETWORK') && BCN_SETTINGS_FAVOR_NETWORK)
			{
				$this->messages[] = new mtekk_adminKit_message(esc_html__('Warning: Your network settings may override any settings set in this page.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_is_nsitemayoveride');
			}
			//Fall through if no settings mode was set
			else
			{
				$this->messages[] = new mtekk_adminKit_message(esc_html__('Warning: No BCN_SETTINGS_* define statement found, defaulting to BCN_SETTINGS_USE_LOCAL.', 'breadcrumb-navxt'), 'warning', true, $this->unique_prefix . '_msg_is_nosetting');
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
			$this->messages[] = new mtekk_adminKit_message(
					sprintf(
							esc_html__('Error: The deprecated setting "Title Length" (see Miscellaneous &gt; Deprecated) has no effect in this version Breadcrumb NavXT. Please %1$suse CSS instead%2$s.', 'breadcrumb-navxt'), 
							'<a title="' . __('Go to the guide on trimming breadcrumb title lengths with CSS', 'breadcrumb-navxt') . '" href="https://mtekk.us/archives/guides/trimming-breadcrumb-title-lengths-with-css/">', '</a>'),
					'error');
		}
		foreach($this->opt as $key => $opt)
		{
			if($key[0] == "H" && substr_count($key, '_template') >= 1)
			{
				$deprecated_tags = array();
				$replacement_tags = array();
				//Deprecated ftitle check
				if(substr_count($opt, '%ftitle%') >= 1)
				{
					$deprecated_tags[] = '%ftitle%';
					$replacement_tags[] = '%title%';
				}
				//Deprecated fhtitle check
				if(substr_count($opt, '%fhtitle%') >= 1)
				{
					$deprecated_tags[] = '%fhtitle%';
					$replacement_tags[] = '%htitle%';
				}
				if(count($deprecated_tags) > 0)
				{
					$setting_link = sprintf('<a href="#%1$s">%2$s</a>', $key, $this->settings[$key]->getTitle());
					$this->messages[] = new mtekk_adminKit_message(
							sprintf(
									esc_html__('Error: The deprecated template tag %1$s found in setting %3$s. Please use %2$s instead.', 'breadcrumb-navxt'), implode(' and ', $deprecated_tags),  implode(' and ', $replacement_tags), $setting_link),
							'error');
				}
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
		//Do a check for multisite settings mode
		$this->multisite_settings_warn();
		do_action($this->unique_prefix . '_settings_pre_messages', $this->opt);
		//Display our messages
		$this->messages();
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
						$this->textbox(__('Breadcrumb Separator', 'breadcrumb-navxt'), 'hseparator', '2', false, __('Placed in between each breadcrumb.', 'breadcrumb-navxt'));
						do_action($this->unique_prefix . '_settings_general', $this->opt);
					?>
				</table>
				<h2><?php _e('Current Item', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->input_check(__('Link Current Item', 'breadcrumb-navxt'), 'bcurrent_item_linked', __('Yes', 'breadcrumb-navxt'));
						$this->input_check(_x('Paged Breadcrumb', 'Paged as in when on an archive or post that is split into multiple pages', 'breadcrumb-navxt'), 'bpaged_display', __('Place the page number breadcrumb in the trail.', 'breadcrumb-navxt'), false, __('Indicates that the user is on a page other than the first of a paginated archive or post.', 'breadcrumb-navxt'), 'adminkit-enset-ctrl adminkit-enset');
						$this->textbox(_x('Paged Template', 'Paged as in when on an archive or post that is split into multiple pages', 'breadcrumb-navxt'), 'Hpaged_template', '4', false, __('The template for paged breadcrumbs.', 'breadcrumb-navxt'), 'adminkit-enset');
						do_action($this->unique_prefix . '_settings_current_item', $this->opt);
					?>
				</table>
				<h2><?php _e('Home Breadcrumb', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->input_check(__('Home Breadcrumb', 'breadcrumb-navxt'), 'bhome_display', __('Place the home breadcrumb in the trail.', 'breadcrumb-navxt'), false, '', 'adminkit-enset-ctrl adminkit-enset');
						$this->textbox(__('Home Template', 'breadcrumb-navxt'), 'Hhome_template', '6', false, __('The template for the home breadcrumb.', 'breadcrumb-navxt'), 'adminkit-enset');
						$this->textbox(__('Home Template (Unlinked)', 'breadcrumb-navxt'), 'Hhome_template_no_anchor', '4', false, __('The template for the home breadcrumb, used when the breadcrumb is not linked.', 'breadcrumb-navxt'), 'adminkit-enset');
						do_action($this->unique_prefix . '_settings_home', $this->opt);
					?>
				</table>
				<h2><?php _e('Blog Breadcrumb', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->input_check(__('Blog Breadcrumb', 'breadcrumb-navxt'), 'bblog_display', __('Place the blog breadcrumb in the trail.', 'breadcrumb-navxt'), $this->maybe_disable_blog_options(), '', 'adminkit-enset-ctrl adminkit-enset');
						do_action($this->unique_prefix . '_settings_blog', $this->opt);
					?>
				</table>
				<h2><?php _e('Mainsite Breadcrumb', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->input_check(__('Main Site Breadcrumb', 'breadcrumb-navxt'), 'bmainsite_display', __('Place the main site home breadcrumb in the trail in an multisite setup.', 'breadcrumb-navxt'), $this->maybe_disable_mainsite_options(), '', 'adminkit-enset-ctrl adminkit-enset');
						$this->textbox(__('Main Site Home Template', 'breadcrumb-navxt'), 'Hmainsite_template', '6', $this->maybe_disable_mainsite_options(), __('The template for the main site home breadcrumb, used only in multisite environments.', 'breadcrumb-navxt'), 'adminkit-enset');
						$this->textbox(__('Main Site Home Template (Unlinked)', 'breadcrumb-navxt'), 'Hmainsite_template_no_anchor', '4', $this->maybe_disable_mainsite_options(), __('The template for the main site home breadcrumb, used only in multisite environments and when the breadcrumb is not linked.', 'breadcrumb-navxt'), 'adminkit-enset');
						do_action($this->unique_prefix . '_settings_mainsite', $this->opt);
					?>
				</table>
				<?php do_action($this->unique_prefix . '_after_settings_tab_general', $this->opt); ?>
			</fieldset>
			<fieldset id="post" class="bcn_options">
				<legend class="screen-reader-text" data-title="<?php _e( 'The settings for all post types (Posts, Pages, and Custom Post Types) are located under this tab.', 'breadcrumb-navxt' );?>"><?php _e( 'Post Types', 'breadcrumb-navxt' ); ?></legend>
			<?php
			//Loop through all of the post types in the array
			foreach($wp_post_types as $post_type)
			{
				//Check for non-public CPTs
				if(!apply_filters('bcn_show_cpt_private', $post_type->public, $post_type->name))
				{
					continue;
				}
				$singular_name_lc = mb_strtolower($post_type->labels->singular_name, 'UTF-8');
				?>
				<h2><?php echo $post_type->labels->singular_name; ?></h2>
				<table class="form-table adminkit-enset-top">
					<?php
						$this->textbox(sprintf(__('%s Template', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'Hpost_' . $post_type->name . '_template', '6', false, sprintf(__('The template for %s breadcrumbs.', 'breadcrumb-navxt'), $singular_name_lc));
						$this->textbox(sprintf(__('%s Template (Unlinked)', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'Hpost_' . $post_type->name . '_template_no_anchor', '4', false, sprintf(__('The template for %s breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'), $singular_name_lc));
						if(!in_array($post_type->name, array('page', 'post')))
						{
						$optid = mtekk_adminKit::get_valid_id('apost_' . $post_type->name . '_root');
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo $optid;?>"><?php printf(esc_html__('%s Root Page', 'breadcrumb-navxt'), $post_type->labels->singular_name);?></label>
						</th>
						<td>
							<?php wp_dropdown_pages(array('name' => $this->unique_prefix . '_options[apost_' . $post_type->name . '_root]', 'id' => $optid, 'echo' => 1, 'show_option_none' => __( '&mdash; Select &mdash;' ), 'option_none_value' => '0', 'selected' => $this->opt['apost_' . $post_type->name . '_root']));?>
						</td>
					</tr>
					<?php
							$this->input_check(sprintf(__('%s Archive Display', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'bpost_' . $post_type->name . '_archive_display', sprintf(__('Show the breadcrumb for the %s post type archives in the breadcrumb trail.', 'breadcrumb-navxt'), $singular_name_lc), !$post_type->has_archive);
						}
						if(!in_array($post_type->name, array('page')))
						{
							$this->input_check(sprintf(__('%s Hierarchy Display', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'bpost_' . $post_type->name . '_hierarchy_display', sprintf(__('Show the hierarchy (specified below) leading to a %s in the breadcrumb trail.', 'breadcrumb-navxt'), $singular_name_lc), false, '', 'adminkit-enset-ctrl adminkit-enset');
							$this->input_check(sprintf(__('%s Hierarchy Use Parent First', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'bpost_' . $post_type->name . '_hierarchy_parent_first', sprintf(__('Use the parent of the %s as the primary hierarchy, falling back to the hierarchy selected below when the parent hierarchy is exhausted.', 'breadcrumb-navxt'), $singular_name_lc), false, '', 'adminkit-enset');
							$this->input_check(sprintf(__('%s Hierarchy Referer Influence', 'breadcrumb-navxt'), $post_type->labels->singular_name), 'bpost_' . $post_type->name . '_taxonomy_referer', __('Allow the referring page to influence the taxonomy selected for the hierarchy.', 'breadcrumb-navxt'), false, '', 'adminkit-enset');
					?>
					<tr valign="top">
						<th scope="row">
							<?php printf(__('%s Hierarchy', 'breadcrumb-navxt'), $post_type->labels->singular_name); ?>
						</th>
						<td>
							<?php
								//We use the value 'page' but really, this will follow the parent post hierarchy
								$this->input_radio('Spost_' . $post_type->name . '_hierarchy_type', 'BCN_POST_PARENT', __('Post Parent', 'breadcrumb-navxt'), false, 'adminkit-enset');
								$this->input_radio('Spost_' . $post_type->name . '_hierarchy_type', 'BCN_DATE', __('Dates', 'breadcrumb-navxt'), false, 'adminkit-enset');
								//Loop through all of the taxonomies in the array
								foreach($wp_taxonomies as $taxonomy)
								{
									//Check for non-public taxonomies
									if(!apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name, $post_type->name))
									{
										continue;
									}
									//We only want custom taxonomies
									if($taxonomy->object_type == $post_type->name || in_array($post_type->name, $taxonomy->object_type))
									{
										$this->input_radio('Spost_' . $post_type->name . '_hierarchy_type', $taxonomy->name, $taxonomy->labels->singular_name, false, 'adminkit-enset');
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
			do_action($this->unique_prefix . '_after_settings_tab_post', $this->opt);
			?>
			</fieldset>
			<fieldset id="tax" class="bcn_options alttab">
				<legend class="screen-reader-text" data-title="<?php _e( 'The settings for all taxonomies (including Categories, Tags, and custom taxonomies) are located under this tab.', 'breadcrumb-navxt' );?>"><?php _e( 'Taxonomies', 'breadcrumb-navxt' ); ?></legend>
				<h2><?php _e('Categories', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->textbox(__('Category Template', 'breadcrumb-navxt'), 'Htax_category_template', '6', false, __('The template for category breadcrumbs.', 'breadcrumb-navxt'));
						$this->textbox(__('Category Template (Unlinked)', 'breadcrumb-navxt'), 'Htax_category_template_no_anchor', '4', false, __('The template for category breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
					?>
				</table>
				<h2><?php _e('Tags', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->textbox(__('Tag Template', 'breadcrumb-navxt'), 'Htax_post_tag_template', '6', false, __('The template for tag breadcrumbs.', 'breadcrumb-navxt'));
						$this->textbox(__('Tag Template (Unlinked)', 'breadcrumb-navxt'), 'Htax_post_tag_template_no_anchor', '4', false, __('The template for tag breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
					?>
				</table>
				<h2><?php _e('Post Formats', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->textbox(__('Post Format Template', 'breadcrumb-navxt'), 'Htax_post_format_template', '6', false, __('The template for post format breadcrumbs.', 'breadcrumb-navxt'));
						$this->textbox(__('Post Format Template (Unlinked)', 'breadcrumb-navxt'), 'Htax_post_format_template_no_anchor', '4', false, __('The template for post_format breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
					?>
				</table>
			<?php
			//Loop through all of the taxonomies in the array
			foreach($wp_taxonomies as $taxonomy)
			{
				//Check for non-public taxonomies
				if(!apply_filters('bcn_show_tax_private', $taxonomy->public, $taxonomy->name, null))
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
						$this->textbox(sprintf(__('%s Template', 'breadcrumb-navxt'), $taxonomy->labels->singular_name), 'Htax_' . $taxonomy->name . '_template', '6', false, sprintf(__('The template for %s breadcrumbs.', 'breadcrumb-navxt'), $label_lc));
						$this->textbox(sprintf(__('%s Template (Unlinked)', 'breadcrumb-navxt'), $taxonomy->labels->singular_name), 'Htax_' . $taxonomy->name . '_template_no_anchor', '4', false, sprintf(__('The template for %s breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'), $label_lc));
					?>
				</table>
				<?php
				}
			}
			do_action($this->unique_prefix . '_after_settings_tab_taxonomy', $this->opt); ?>
			</fieldset>
			<fieldset id="miscellaneous" class="bcn_options">
				<legend class="screen-reader-text" data-title="<?php _e( 'The settings for author and date archives, searches, and 404 pages are located under this tab.', 'breadcrumb-navxt' );?>"><?php _e( 'Miscellaneous', 'breadcrumb-navxt' ); ?></legend>
				<h2><?php _e('Author Archives', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->textbox(__('Author Template', 'breadcrumb-navxt'), 'Hauthor_template', '6', false, __('The template for author breadcrumbs.', 'breadcrumb-navxt'));
						$this->textbox(__('Author Template (Unlinked)', 'breadcrumb-navxt'), 'Hauthor_template_no_anchor', '4', false, __('The template for author breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
						$this->input_select(__('Author Display Format', 'breadcrumb-navxt'), 'Sauthor_name', array("display_name", "nickname", "first_name", "last_name"), false, __('display_name uses the name specified in "Display name publicly as" under the user profile the others correspond to options in the user profile.', 'breadcrumb-navxt'));
						$optid = mtekk_adminKit::get_valid_id('aauthor_root');
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo $optid;?>"><?php esc_html_e('Author Root Page', 'breadcrumb-navxt');?></label>
						</th>
						<td>
							<?php wp_dropdown_pages(array('name' => $this->unique_prefix . '_options[aauthor_root]', 'id' => $optid, 'echo' => 1, 'show_option_none' => __( '&mdash; Select &mdash;' ), 'option_none_value' => '0', 'selected' => $this->opt['aauthor_root']));?>
						</td>
					</tr>
				</table>
				<h2><?php _e('Miscellaneous', 'breadcrumb-navxt'); ?></h2>
				<table class="form-table">
					<?php
						$this->textbox(__('Date Template', 'breadcrumb-navxt'), 'Hdate_template', '6', false, __('The template for date breadcrumbs.', 'breadcrumb-navxt'));
						$this->textbox(__('Date Template (Unlinked)', 'breadcrumb-navxt'), 'Hdate_template_no_anchor', '4', false, __('The template for date breadcrumbs, used only when the breadcrumb is not linked.', 'breadcrumb-navxt'));
						$this->textbox(__('Search Template', 'breadcrumb-navxt'), 'Hsearch_template', '6', false, __('The anchor template for search breadcrumbs, used only when the search results span several pages.', 'breadcrumb-navxt'));
						$this->textbox(__('Search Template (Unlinked)', 'breadcrumb-navxt'), 'Hsearch_template_no_anchor', '4', false, __('The anchor template for search breadcrumbs, used only when the search results span several pages and the breadcrumb is not linked.', 'breadcrumb-navxt'));
						$this->input_text(__('404 Title', 'breadcrumb-navxt'), 'S404_title', 'regular-text');
						$this->textbox(__('404 Template', 'breadcrumb-navxt'), 'H404_template', '4', false, __('The template for 404 breadcrumbs.', 'breadcrumb-navxt'));
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
								<input name="bcn_options[blimit_title]" type="checkbox" id="blimit_title" value="true" <?php checked(true, $this->opt['blimit_title']); ?> />
								<?php printf(esc_html__('Limit the length of the breadcrumb title. (Deprecated, %suse CSS instead%s)', 'breadcrumb-navxt'), '<a title="' . esc_attr__('Go to the guide on trimming breadcrumb title lengths with CSS', 'breadcrumb-navxt') . '" href="https://mtekk.us/archives/guides/trimming-breadcrumb-title-lengths-with-css/">', '</a>');?>
							</label><br />
							<ul>
								<li>
									<label for="amax_title_length">
										<?php esc_html_e('Max Title Length: ','breadcrumb-navxt');?>
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
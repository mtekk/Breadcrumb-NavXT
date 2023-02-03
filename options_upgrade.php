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
//FIXME: this seems to be all sorts of garbage that needs fixing
function bcn_options_upgrade_handler(&$opts, $version, $defaults)
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
					$defaults['b' . $option] = $value;
				}
				//Handle migration of anchor templates to the templates
				else if(strpos($option, 'anchor') > 0)
				{
					$parts = explode('_', $option);
					//Do excess slash removal sanitation
					$defaults['H' . $parts[0] . '_template'] = $value . '%htitle%</a>';
				}
				//Handle our abs integers
				else if($option == 'max_title_length' || $option == 'post_post_root' || $option == 'post_page_root')
				{
					$opts['a' . $option] = $value;
				}
				//Now everything else, minus prefix and suffix
				else if(strpos($option, 'prefix') === false && strpos($option, 'suffix') === false)
				{
					$defaults['S' . $option] = $value;
				}
			}
		}
		//Add in the new settings for CPTs introduced in 4.0
		foreach($GLOBALS['wp_post_types'] as $post_type)
		{
			//We only want custom post types
			if(!$post_type->_builtin)
			{
				//Add in the archive_display option
				$defaults['bpost_' . $post_type->name . '_archive_display'] = $post_type->has_archive;
			}
		}
		$opts = $defaults;
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
		foreach($GLOBALS['wp_taxonomies'] as $taxonomy)
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
	//Upgrading to 5.4.0
	if(version_compare($version, '5.4.0', '<'))
	{
		//Migrate users to schema.org breadcrumbs for author and search if still on the defaults for posts
		if($opts['Hpost_post_template'] === bcn_breadcrumb::get_default_template() && $opts['Hpost_post_template_no_anchor'] === bcn_breadcrumb::default_template_no_anchor)
		{
			if($opts['Hpaged_template'] === 'Page %htitle%')
			{
				$opts['Hpaged_template'] = $defaults['Hpaged_template'];
			}
			if($opts['Hsearch_template'] === 'Search results for &#39;<a title="Go to the first page of search results for %title%." href="%link%" class="%type%">%htitle%</a>&#39;' || $opts['Hsearch_template'] === 'Search results for &#039;<a title="Go to the first page of search results for %title%." href="%link%" class="%type%">%htitle%</a>&#039;')
			{
				$opts['Hsearch_template'] = $defaults['Hsearch_template'];
			}
			if($opts['Hsearch_template_no_anchor'] === 'Search results for &#39;%htitle%&#39;' || $opts['Hsearch_template_no_anchor'] === 'Search results for &#039;%htitle%&#039;')
			{
				$opts['Hsearch_template_no_anchor'] = $defaults['Hsearch_template_no_anchor'];
			}
			if($opts['Hauthor_template'] === 'Articles by: <a title="Go to the first page of posts by %title%." href="%link%" class="%type%">%htitle%</a>')
			{
				$opts['Hauthor_template'] = $defaults['Hauthor_template'];
			}
			if($opts['Hauthor_template_no_anchor'] === 'Articles by: %htitle%')
			{
				$opts['Hauthor_template_no_anchor'] = $defaults['Hauthor_template_no_anchor'];
			}
		}
	}
	//Upgrading to 5.5.0
	if(version_compare($version, '5.5.0', '<'))
	{
		//Translate the old 'page' taxonomy type to BCN_POST_PARENT
		if($defaults['Spost_post_taxonomy_type'] === 'page')
		{
			$opts['Spost_post_taxonomy_type'] = 'BCN_POST_PARENT';
		}
		if(!isset($defaults['Spost_post_taxonomy_referer']))
		{
			$opts['bpost_post_taxonomy_referer'] = false;
		}
		//Loop through all of the post types in the array
		foreach($GLOBALS['wp_post_types'] as $post_type)
		{
			//Check for non-public CPTs
			if(!apply_filters('bcn_show_cpt_private', $post_type->public, $post_type->name))
			{
				continue;
			}
			//We only want custom post types
			if(!$post_type->_builtin)
			{
				//Translate the old 'page' taxonomy type to BCN_POST_PARENT
				if($opts['Spost_' . $post_type->name . '_taxonomy_type'] === 'page')
				{
					$opts['Spost_' . $post_type->name . '_taxonomy_type'] = 'BCN_POST_PARENT';
				}
				//Translate the old 'date' taxonomy type to BCN_DATE
				if($opts['Spost_' . $post_type->name . '_taxonomy_type'] === 'date')
				{
					$opts['Spost_' . $post_type->name . '_taxonomy_type'] = 'BCN_DATE';
				}
				if(!isset($opts['Spost_' . $post_type->name . '_taxonomy_referer']))
				{
					$opts['bpost_' . $post_type->name . '_taxonomy_referer'] = false;
				}
			}
		}
	}
	//Upgrading to 6.0.0
	if(version_compare($version, '6.0.0', '<'))
	{
		//Loop through all of the post types in the array
		foreach($GLOBALS['wp_post_types'] as $post_type)
		{
			if(isset($opts['Spost_' . $post_type->name . '_taxonomy_type']))
			{
				$opts['Spost_' . $post_type->name . '_hierarchy_type'] = $opts['Spost_' . $post_type->name . '_taxonomy_type'];
				unset($opts['Spost_' . $post_type->name . '_taxonomy_type']);
			}
			if(isset($opts['Spost_' . $post_type->name . '_taxonomy_display']))
			{
				$opts['Spost_' . $post_type->name . '_hierarchy_display'] = $opts['Spost_' . $post_type->name . '_taxonomy_display'];
				unset($opts['Spost_' . $post_type->name . '_taxonomy_display']);
			}
		}
	}
	if(version_compare($version, '7.0.0', '<'))
	{
		//Loop through all of the post types in the array
		foreach($GLOBALS['wp_post_types'] as $post_type)
		{
			if(isset($opts['Spost_' . $post_type->name . '_hierarchy_type']))
			{
				$opts['Epost_' . $post_type->name . '_hierarchy_type'] = $opts['Spost_' . $post_type->name . '_hierarchy_type'];
				unset($opts['Spost_' . $post_type->name . '_hierarchy_type']);
			}
		}
		if(isset($opts['Sauthor_name']))
		{
			$opts['Eauthor_name'] = $opts['Sauthor_name'];
			unset($opts['Sauthor_name']);
		}
	}
}
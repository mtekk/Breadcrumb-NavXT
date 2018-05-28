<?php
/*
	Copyright 2015-2018  John Havlik  (email : john.havlik@mtekk.us)

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
//The trail class
class bcn_breadcrumb_trail
{
	//Our member variables
	const version = '6.1.0';
	//An array of breadcrumbs
	public $breadcrumbs = array();
	public $trail = array();
	//The options
	public $opt;
	//Default constructor
	public function __construct()
	{
		//@see https://core.trac.wordpress.org/ticket/10527
		if(!is_textdomain_loaded('breadcrumb-navxt'))
		{
			load_plugin_textdomain('breadcrumb-navxt', false, 'breadcrumb-navxt/languages');
		}
		$this->trail = &$this->breadcrumbs;
		//Initilize with default option values
		$this->opt = array(
			//Should the mainsite be shown
			'bmainsite_display' => true,
			//The breadcrumb template for the main site
			'Hmainsite_template' => bcn_breadcrumb::get_default_template(),
			//The breadcrumb template for the main site, used when an anchor is not needed
			'Hmainsite_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor,
			//Should the home page be shown
			'bhome_display' => true,
			//The breadcrumb template for the home page
			'Hhome_template' => bcn_breadcrumb::get_default_template(),
			//The breadcrumb template for the home page, used when an anchor is not needed
			'Hhome_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor,
			//Should the blog page be shown globally
			'bblog_display' => true,
			//Separator that is placed between each item in the breadcrumb trial, but not placed before
			//the first and not after the last breadcrumb
			'hseparator' => ' &gt; ',
			//Whether or not we should trim the breadcrumb titles
			'blimit_title' => false,
			//The maximum title length
			'amax_title_length' => 20,
			//Current item options
			'bcurrent_item_linked' => false,
			//Static page options
			//Should the trail include the hierarchy of the page
			'bpost_page_hierarchy_display' => true,
			//What hierarchy should be shown leading to the page
			'Spost_page_hierarchy_type' => 'BCN_POST_PARENT',
			//The anchor template for page breadcrumbs
			'Hpost_page_template' => bcn_breadcrumb::get_default_template(),
			//The anchor template for page breadcrumbs, used when an anchor is not needed
			'Hpost_page_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor,
			//Just a link to the page on front property
			'apost_page_root' => get_option('page_on_front'),
			//Paged options
			//The template for paged breadcrumb
			'Hpaged_template' => __('<span property="itemListElement" typeof="ListItem"><span property="name">Page %htitle%</span><meta property="position" content="%position%"></span>', 'breadcrumb-navxt'),
			//Should we try filling out paged information
			'bpaged_display' => false,
			//The post options previously singleblogpost
			//The breadcrumb template for post breadcrumbs
			'Hpost_post_template' => bcn_breadcrumb::get_default_template(),
			//The breadcrumb template for post breadcrumbs, used when an anchor is not needed
			'Hpost_post_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor,
			//Just a link for the page for posts
			'apost_post_root' => get_option('page_for_posts'),
			//Should the trail include the hierarchy of the post
			'bpost_post_hierarchy_display' => true,
			//Should the trail reflect the referer taxonomy or not
			'bpost_post_taxonomy_referer' => false,
			//What hierarchy should be shown leading to the post, tag or category
			'Spost_post_hierarchy_type' => 'category',
			//Attachment settings
			'bpost_attachment_hierarchy_display' => true,
			//What hierarchy should be shown leading to the attachment
			'Spost_attachment_hierarchy_type' => 'BCN_POST_PARENT',
			//Give an invlaid page ID for the attachement root
			'apost_attachment_root' => 0,
			//The breadcrumb template for attachment breadcrumbs
			'Hpost_attachment_template' => bcn_breadcrumb::get_default_template(),
			//The breadcrumb template for attachment breadcrumbs, used when an anchor is not needed
			'Hpost_attachment_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor,
			//404 page settings
			//The template for 404 breadcrumbs
			'H404_template' => bcn_breadcrumb::default_template_no_anchor,
			//The text to be shown in the breadcrumb for a 404 page
			'S404_title' => __('404', 'breadcrumb-navxt'),
			//Search page options
			//The breadcrumb template for search breadcrumbs
			'Hsearch_template' => __('<span property="itemListElement" typeof="ListItem"><span property="name">Search results for &#39;<a property="item" typeof="WebPage" title="Go to the first page of search results for %title%." href="%link%" class="%type%">%htitle%</a>&#39;</span><meta property="position" content="%position%"></span>', 'breadcrumb-navxt'),
			//The breadcrumb template for search breadcrumbs, used when an anchor is not necessary
			'Hsearch_template_no_anchor' => __('<span property="itemListElement" typeof="ListItem"><span property="name">Search results for &#39;%htitle%&#39;</span><meta property="position" content="%position%"></span>', 'breadcrumb-navxt'),
			//Tag related stuff
			//The breadcrumb template for tag breadcrumbs
			'Htax_post_tag_template' => __('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the %title% tag archives." href="%link%" class="%type%"><span property="name">%htitle%</span></a><meta property="position" content="%position%"></span>', 'breadcrumb-navxt'),
			//The breadcrumb template for tag breadcrumbs, used when an anchor is not necessary
			'Htax_post_tag_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor,
			//Post format related stuff
			//The breadcrumb template for post format breadcrumbs, used when an anchor is not necessary
			'Htax_post_format_template' => __('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the %title% archives." href="%link%" class="%type%"><span property="name">%htitle%</span></a><meta property="position" content="%position%"></span>', 'breadcrumb-navxt'),
			//The breadcrumb template for post format breadcrumbs
			'Htax_post_format_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor,
			//Author page stuff
			//The anchor template for author breadcrumbs
			'Hauthor_template' => __('<span property="itemListElement" typeof="ListItem"><span property="name">Articles by: <a title="Go to the first page of posts by %title%." href="%link%" class="%type%">%htitle%</a>', 'breadcrumb-navxt'),
			//The anchor template for author breadcrumbs, used when anchors are not needed
			'Hauthor_template_no_anchor' => __('<span property="itemListElement" typeof="ListItem"><span property="name">Articles by: %htitle%</span><meta property="position" content="%position%"></span>', 'breadcrumb-navxt'),
			//Which of the various WordPress display types should the author breadcrumb display
			'Sauthor_name' => 'display_name',
			//Give an invlaid page ID for the author root
			'aauthor_root' => 0,
			//Category stuff
			//The breadcrumb template for category breadcrumbs
			'Htax_category_template' => __('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the %title% category archives." href="%link%" class="%type%"><span property="name">%htitle%</span></a><meta property="position" content="%position%"></span>', 'breadcrumb-navxt'),
			//The breadcrumb template for category breadcrumbs, used when anchors are not needed
			'Htax_category_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor,
			//The breadcrumb template for date breadcrumbs
			'Hdate_template' => __('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to the %title% archives." href="%link%" class="%type%"><span property="name">%htitle%</span></a><meta property="position" content="%position%"></span>', 'breadcrumb-navxt'),
			//The breadcrumb template for date breadcrumbs, used when anchors are not needed
			'Hdate_template_no_anchor' => bcn_breadcrumb::default_template_no_anchor
		);
	}
	/**
	 * This returns the internal version
	 * 
	 * @deprecated 5.2.0 No longer needed, superceeded bcn_breadcrumb_trail::version
	 *
	 * @return string internal version of the Breadcrumb trail
	 */
	public function get_version()
	{
		_deprecated_function( __FUNCTION__, '5.2', 'bcn_breadcrumb_trail::version' );
		return self::version;
	}
	/**
	 * Adds a breadcrumb to the breadcrumb trail
	 * 
	 * @param bcn_breadcrumb $object Breadcrumb to add to the trail
	 * 
	 * @return pointer to the just added Breadcrumb
	 */
	public function &add(bcn_breadcrumb $object)
	{
		$this->breadcrumbs[] = $object;
		//Return the just added object
		return $this->breadcrumbs[count($this->breadcrumbs) - 1];
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a search page
	 * 
	 * @param string $search_query The search query that was performed
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 */
	protected function do_search($search_query, $is_paged = false)
	{
		//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb($search_query, $this->opt['Hsearch_template_no_anchor'], array('search', 'current-item')));
		//If we're paged, or allowing the current item to be linked, let's link to the first page
		if($this->opt['bcurrent_item_linked'] || ($is_paged && $this->opt['bpaged_display']))
		{
			//Since we are paged and are linking the root breadcrumb, time to change to the regular template
			$breadcrumb->set_template($this->opt['Hsearch_template']);
			//Figure out the anchor for the search
			$breadcrumb->set_url(get_search_link($search_query));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for an author page
	 * 
	 * @param string $author_data The author to generate the breadcrumb for
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 */
	protected function do_author($author_data, $is_paged = false)
	{
		//Setup array of valid author_name values
		$valid_author_name = array('display_name', 'nickname', 'first_name', 'last_name');
		//Make sure user picks only safe values
		if(in_array($this->opt['Sauthor_name'], $valid_author_name))
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb(get_the_author_meta($this->opt['Sauthor_name'], $author_data->ID), $this->opt['Hauthor_template_no_anchor'], array('author', 'current-item'), null, $author_data->ID));
			//If we're paged, or allowing the current item to be linked, let's link to the first page
			if($this->opt['bcurrent_item_linked'] || ($is_paged && $this->opt['bpaged_display']))
			{
				//Set the template to our one containing an anchor
				$breadcrumb->set_template($this->opt['Hauthor_template']);
				$breadcrumb->set_url(get_author_posts_url($author_data->ID));
			}
		}
	}
	/**
	 * Determines the taxonomy name represented by the specified query var
	 * 
	 * @param string $query_var The query var to attempt to find the corresponding taxonomy
	 * 
	 * @return string|bool Either the name of the taxonomy corresponding to the query_var or false if no taxonomy exists for the specified query_var
	 */
	protected function query_var_to_taxonomy($query_var)
	{
		global $wp_taxonomies;
		foreach($wp_taxonomies as $taxonomy)
		{
			if($taxonomy->query_var === $query_var)
			{
				return $taxonomy->name;
			}
		}
		return false;
	}
	/**
	 * Determines the referer taxonomy
	 * 
	 * @return string|bool Either the name of the taxonomy to use or false if a referer taxonomy wasn't found
	 */
	protected function determine_taxonomy()
	{
		global $wp;
		//Backup the server request variable
		$bk_req = $_SERVER['REQUEST_URI'];
		//Now set the request URL to the referrer URL
		//Could just chain the [1] selection, but that's not PHP5.3 compatible
		$url_split = explode(home_url(), esc_url(wp_get_referer()));
		if(isset($url_split[1]))
		{
			$_SERVER['REQUEST_URI'] = $url_split[1];
		}
		else
		{
			return false;
		}
		//Create our own new instance of WP, and have it parse our faux request
		$bcn_wp = new WP();
		//Copy over the current global wp object's query_vars since CPTs and taxonomies are added directly to the global $wp
		$bcn_wp->public_query_vars = $wp->public_query_vars;
		$bcn_wp->parse_request();
		$_SERVER['REQUEST_URI'] = $bk_req;
		if(is_array($bcn_wp->query_vars))
		{
			foreach($bcn_wp->query_vars as $query_var => $value)
			{
				if($taxonomy = $this->query_var_to_taxonomy($query_var))
				{
					return $taxonomy;
				}
			}
		}
		return false;
	}
	/**
	 * This function selects the term that should be used for a post's hierarchy
	 * 
	 * @param int $id The ID of the post to find the term for
	 * @param string $type The post type of the post to figure out the taxonomy for
	 * @param string $taxonomy The taxonomy to use
	 * 
	 * @return WP_Term|bool The term object to use for the post hierarchy or false if no suitable term was found 
	 */
	protected function pick_post_term($id, $type, $taxonomy)
	{
		//Fill a temporary object with the terms
		$bcn_object = get_the_terms($id, $taxonomy);
		$potential_parent = 0;
		//Make sure we have an non-empty array
		if(is_array($bcn_object))
		{
			//Now try to find the deepest term of those that we know of
			$bcn_use_term = key($bcn_object);
			foreach($bcn_object as $key => $object)
			{
				//Can't use the next($bcn_object) trick since order is unknown
				if($object->parent > 0  && ($potential_parent === 0 || $object->parent === $potential_parent))
				{
					$bcn_use_term = $key;
					$potential_parent = $object->term_id;
				}
			}
			return $bcn_object[$bcn_use_term];
		}
		return false;
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This function fills breadcrumbs for any post taxonomy
	 * 
	 * @param int $id The id of the post to figure out the taxonomy for
	 * @param string $type The post type of the post to figure out the taxonomy for
	 * @param int $parent (optional) The id of the parent of the current post, used if hiearchal posts will be the "taxonomy" for the current post
	 */
	protected function post_hierarchy($id, $type, $parent = null)
	{
		//Check to see if breadcrumbs for the hierarchy of the post needs to be generated
		if($this->opt['bpost_' . $type . '_hierarchy_display'])
		{
			//Check if we have a date 'taxonomy' request
			if($this->opt['Spost_' . $type . '_hierarchy_type'] === 'BCN_DATE')
			{
				$post = get_post($id);
				$this->do_day($post, $type, false, false);
				$this->do_month($post, $type, false, false);
				$this->do_year($post, $type, false, false);
			}
			//Handle the use of hierarchical posts as the 'taxonomy'
			else if($this->opt['Spost_' . $type . '_hierarchy_type'] === 'BCN_POST_PARENT')
			{
				if($parent == null)
				{
					//We have to grab the post to find its parent, can't use $post for this one
					$parent = get_post($id);
					//TODO should we check that we have a WP_Post object here?
					$parent = $parent->post_parent;
				}
				//Grab the frontpage, we'll need it shortly
				$frontpage = get_option('page_on_front');
				//If there is a parent page let's find it
				if($parent > 0 && $id != $parent && $frontpage != $parent)
				{
					$parent = $this->post_parents($parent, $frontpage);
				}
			}
			else
			{
				$taxonomy = $this->opt['Spost_' . $type . '_hierarchy_type'];
				//Possibly let the referer influence the taxonomy used
				if($this->opt['bpost_' . $type . '_taxonomy_referer'] && $referrer_taxonomy = $this->determine_taxonomy())
				{
					//See if there were any terms, if so, we can use the referrer influenced taxonomy
					$terms = get_the_terms($id, $referrer_taxonomy);
					if(is_array($terms))
					{
						$taxonomy = $referrer_taxonomy;
					}
				}
				//Handle all hierarchical taxonomies, including categories
				if(is_taxonomy_hierarchical($taxonomy))
				{
					//Filter the results of post_pick_term
					$term = apply_filters('bcn_pick_post_term', $this->pick_post_term($id, $type, $taxonomy), $id, $type, $taxonomy);
					//Only do something if we found a term
					if($term instanceof WP_Term)
					{
						//Fill out the term hiearchy
						$parent = $this->term_parents($term->term_id, $taxonomy);
					}
				}
				//Handle the rest of the taxonomies, including tags
				else
				{
					$this->post_terms($id, $taxonomy);
				}
			}
		}
		//If we never got a good parent for the type_archive, make it now
		if(!($parent instanceof WP_Post))
		{
			$parent = get_post($id);
		}
		//Finish off with trying to find the type archive
		$this->type_archive($parent, $type);
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for the terms of a post
	 * 
	 * @param int $id The id of the post to find the terms for
	 * @param string $taxonomy The name of the taxonomy that the term belongs to
	 * 
	 * TODO Need to implement this cleaner
	 */
	protected function post_terms($id, $taxonomy)
	{
		//Apply a filter to the terms for the post referred to by ID
		$bcn_terms = apply_filters('bcn_post_terms', get_the_terms($id, $taxonomy), $taxonomy, $id);
		//Only process if we have terms
		if(is_array($bcn_terms))
		{
			$title = '';	
			$is_first = true;
			//Loop through all of the term results
			foreach($bcn_terms as $term)
			{
				//Everything but the first term needs a comma separator
				if($is_first == false)
				{
					$title .= ', ';
				}
				//This is a bit hackish, but it compiles the term anchor and appends it to the current breadcrumb title
				$title .= str_replace(
					array('%title%', '%link%', '%htitle%', '%type%'), 
					array($term->name, $this->maybe_add_post_type_arg(get_term_link($term), null, $term->taxonomy), $term->name, $term->taxonomy),
					$this->opt['Htax_' . $term->taxonomy . '_template']);
				$is_first = false;
			}
			//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($title, '%htitle%', array('taxonomy', $taxonomy)));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This recursive functions fills the trail with breadcrumbs for parent terms
	 * 
	 * @param int $id The id of the term
	 * @param string $taxonomy The name of the taxonomy that the term belongs to
	 * 
	 * @return WP_Term|WP_Error The term we stopped at
	 */
	protected function term_parents($id, $taxonomy)
	{
		//Get the current category object, filter applied within this call
		$term = get_term($id, $taxonomy);
		if($term instanceof WP_Term)
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($term->name, $this->opt['Htax_' . $taxonomy . '_template'], array('taxonomy', $taxonomy), $this->maybe_add_post_type_arg(get_term_link($term), null, $taxonomy), $id));
			//Make sure the id is valid, and that we won't end up spinning in a loop
			if($term->parent && $term->parent != $id)
			{
				//Figure out the rest of the term hiearchy via recursion
				$ret_term = $this->term_parents($term->parent, $taxonomy);
				//May end up with WP_Error, don't update the term if that's the case
				if($ret_term instanceof WP_Term)
				{
					$term = $ret_term;
				}
			}
		}
		return $term;
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This recursive functions fills the trail with breadcrumbs for parent posts/pages
	 * 
	 * @param int $id The id of the parent page
	 * @param int $frontpage The id of the front page
	 * 
	 * @return WP_Post The parent we stopped at
	 */
	protected function post_parents($id, $frontpage)
	{
		//Use WordPress API, though a bit heavier than the old method, this will ensure compatibility with other plug-ins
		$parent = get_post($id);
		//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_title($id), $this->opt['Hpost_' . $parent->post_type . '_template'], array('post', 'post-' . $parent->post_type), get_permalink($id), $id));
		//Make sure the id is valid, and that we won't end up spinning in a loop
		if($parent->post_parent > 0 && $id != $parent->post_parent && $frontpage != $parent->post_parent)
		{
			//If valid, recursively call this function
			$parent = $this->post_parents($parent->post_parent, $frontpage);
		}
		return $parent;
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for posts
	 * 
	 * @param WP_Post $post Instance of WP_Post object to create a breadcrumb for
	 * @param bool $force_link Whether or not to force this breadcrumb to be linked
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 * @param bool $is_current_item Whether or not the breadcrumb being generated is the current item
	 */
	protected function do_post($post, $force_link = false, $is_paged = false, $is_current_item = true)
	{
		//If we did not get a WP_Post object, warn developer and return early
		if(!($post instanceof WP_Post))
		{
			_doing_it_wrong(__CLASS__ . '::' . __FUNCTION__, __('$post global is not of type WP_Post', 'breadcrumb-navxt'), '5.1.1');
			return;
		}
		//Place the breadcrumb in the trail, uses the bcn_breadcrumb constructor to set the title, template, and type
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_title($post), $this->opt['Hpost_' . $post->post_type . '_template_no_anchor'], array('post', 'post-' . $post->post_type), null, $post->ID));
		if($is_current_item)
		{
			$breadcrumb->add_type('current-item');
		}
		//Under a couple of circumstances we will want to link this breadcrumb
		if($force_link || ($is_current_item && $this->opt['bcurrent_item_linked']) || ($is_paged && $this->opt['bpaged_display']))
		{
			//Change the template over to the normal, linked one
			$breadcrumb->set_template($this->opt['Hpost_' . $post->post_type . '_template']);
			//Add the link
			$breadcrumb->set_url(get_permalink($post));
		}
		//If we have an attachment, run through the post again
		if($post->post_type === 'attachment')
		{
			//Done with the current item, now on to the parents
			$frontpage = get_option('page_on_front');
			//Make sure the id is valid, and that we won't end up spinning in a loop
			if($post->post_parent > 0 && $post->ID != $post->post_parent && $frontpage != $post->post_parent)
			{
				//Get the parent's information
				$parent = get_post($post->post_parent);
				//Take care of the parent's breadcrumb
				$this->do_post($parent, true, false, false);
			}
		}
		//Otherwise we need the follow the hiearchy tree
		else
		{
			//Handle the post's hiearchy
			$this->post_hierarchy($post->ID, $post->post_type, $post->post_parent);
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * @deprecated 6.0.0 No longer needed, superceeded by do_post
	 * 
	 * This functions fills a breadcrumb for an attachment page.
	 */
	protected function do_attachment()
	{
		_deprecated_function( __FUNCTION__, '6.0', 'bcn_breadcrumb_trail::do_post');
		$this->do_post(get_post());
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This function fills a breadcrumb for any taxonomy archive, was previously two separate functions
	 * 
	 * @param WP_Term $term The term object to generate the breadcrumb for
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 */
	protected function do_archive_by_term($term, $is_paged = false)
	{
		//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb($term->name, $this->opt['Htax_' . $term->taxonomy . '_template_no_anchor'], array('archive', 'taxonomy', $term->taxonomy, 'current-item'), null, $term->term_id));
		//If we're paged, let's link to the first page
		if($this->opt['bcurrent_item_linked'] || ($is_paged && $this->opt['bpaged_display']))
		{
			$breadcrumb->set_template($this->opt['Htax_' . $term->taxonomy . '_template']);
			//Figure out the anchor for current category
			$breadcrumb->set_url($this->maybe_add_post_type_arg(get_term_link($term), null, $term->taxonomy));
		}
		//Get parents of current term
		if($term->parent)
		{
			$this->term_parents($term->parent, $term->taxonomy);
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for day date archives
	 * 
	 * @param WP_Post $post Instance of WP_Post object to create a breadcrumb for
	 * @param string $type The name of the CPT to generate the archive breadcrumb for
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 * @param bool $is_current_item Whether or not the breadcrumb being generated is the current item
	 */
	protected function do_day($post, $type, $is_paged = false, $is_current_item = true)
	{
		//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_time(_x('d', 'day archive breadcrumb date format', 'breadcrumb-navxt'), $post), $this->opt['Hdate_template_no_anchor'], array('archive', 'date-day')));
		//If this is a day archive, add current-item type
		if($is_current_item)
		{
			$breadcrumb->add_type('current-item');
		}
		//If we're paged, let's link to the first page
		if(!$is_current_item || ($is_current_item && $this->opt['bcurrent_item_linked']) || ($is_paged && $this->opt['bpaged_display']))
		{
			//We're linking, so set the linked template
			$breadcrumb->set_template($this->opt['Hdate_template']);
			$url = get_day_link(get_the_time('Y'), get_the_time('m'), get_the_time('d'));
			//Deal with the anchor
			$breadcrumb->set_url($this->maybe_add_post_type_arg($url, $type));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for month date archives
	 * 
	 * @param WP_Post $post Instance of WP_Post object to create a breadcrumb for
	 * @param string $type The name of the CPT to generate the archive breadcrumb for
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 * @param bool $is_current_item Whether or not the breadcrumb being generated is the current item
	 */
	protected function do_month($post, $type, $is_paged = false, $is_current_item = true)
	{
		//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_time(_x('F', 'month archive breadcrumb date format', 'breadcrumb-navxt'), $post), $this->opt['Hdate_template_no_anchor'], array('archive', 'date-month')));
		//If this is a month archive, add current-item type
		if($is_current_item)
		{
			$breadcrumb->add_type('current-item');
		}
		//If we're paged, or not in the archive by month let's link to the first archive by month page
		if(!$is_current_item || ($is_current_item && $this->opt['bcurrent_item_linked']) || ($is_paged && $this->opt['bpaged_display']))
		{
			//We're linking, so set the linked template
			$breadcrumb->set_template($this->opt['Hdate_template']);
			$url = get_month_link(get_the_time('Y'), get_the_time('m'));
			//Deal with the anchor
			$breadcrumb->set_url($this->maybe_add_post_type_arg($url, $type));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for year date archives
	 * 
	 * @param WP_Post $post Instance of WP_Post object to create a breadcrumb for
	 * @param string $type The name of the CPT to generate the archive breadcrumb for
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 * @param bool $is_current_item Whether or not the breadcrumb being generated is the current item
	 */
	protected function do_year($post, $type, $is_paged = false, $is_current_item = true)
	{
		//Place the year breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_time(_x('Y', 'year archive breadcrumb date format', 'breadcrumb-navxt'), $post), $this->opt['Hdate_template_no_anchor'], array('archive', 'date-year')));
		//If this is a year archive, add current-item type
		if($is_current_item)
		{
			$breadcrumb->add_type('current-item');
		}
		//If we're paged, or not in the archive by year let's link to the first archive by year page
		if(!$is_current_item || ($is_current_item && $this->opt['bcurrent_item_linked']) || ($is_paged && $this->opt['bpaged_display']))
		{
			//We're linking, so set the linked template
			$breadcrumb->set_template($this->opt['Hdate_template']);
			$url = get_year_link(get_the_time('Y'));
			//Deal with the anchor
			$breadcrumb->set_url($this->maybe_add_post_type_arg($url, $type));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a date archive.
	 * 
	 * @param string $type The type to restrict the date archives to
	 * 
	 * @deprecated 6.0.0 No longer needed, superceeded by do_day, do_month, and/or do_year
	 */
	protected function do_archive_by_date($type)
	{
		_deprecated_function( __FUNCTION__, '6.0', 'bcn_breadcrumb_trail::do_day, bcn_breadcrumb_trail::do_month, and/or bcn_breadcrumb_trail::do_year');
		//First deal with the day breadcrumb
		if(is_day() || is_single())
		{
			$this->do_day(get_post(), $type, is_paged(), is_day());
		}
		//Now deal with the month breadcrumb
		if(is_month() || is_day() || is_single())
		{
			$this->do_month(get_post(), $type, is_paged(), is_month());
		}
		$this->do_year(get_post(), $type, is_paged(), is_year());
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a post type archive (WP 3.1 feature)
	 * 
	 * @param string type_str The name of the CPT to generate the archive breadcrumb for
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 */
	protected function do_archive_by_post_type($type_str, $is_paged = false)
	{
		//Manually grabbing the post type object insted of post_type_archive_title('', false) to remove get_query_var() dependancy
		$post_type_obj = get_post_type_object($type_str);
		$title = apply_filters('post_type_archive_title', $post_type_obj->labels->name, $type_str);
		//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb($title, $this->opt['Hpost_' . $type_str . '_template_no_anchor'], array('archive', 'post-' . $type_str . '-archive', 'current-item')));
		if($this->opt['bcurrent_item_linked'] || ($is_paged && $this->opt['bpaged_display']))
		{
			
			$breadcrumb->set_template($this->opt['Hpost_' . $type_str . '_template']);
			//Deal with the anchor
			$breadcrumb->set_url(get_post_type_archive_link($type_str));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for the front page
	 * 
	 * @param bool $force_link Whether or not to force this breadcrumb to be linked
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 * @param bool $is_current_item Whether or not the breadcrumb being generated is the current item
	 */
	protected function do_home($force_link = false, $is_paged = false, $is_current_item = true)
	{
		global $current_site;
		//Exit early if we're not displaying the home breadcrumb
		if(!$this->opt['bhome_display'])
		{
			return;
		}
		//Get the site name
		$site_name = get_option('blogname');
		//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb($site_name, $this->opt['Hhome_template_no_anchor'], array('home')));
		if($is_current_item)
		{
			$breadcrumb->add_type('current-item');
		}
		//Under a couple of circumstances we will want to link this breadcrumb
		if($force_link || ($is_current_item && $this->opt['bcurrent_item_linked']) || ($is_paged && $this->opt['bpaged_display']))
		{
			$breadcrumb->set_template($this->opt['Hhome_template']);
			//Figure out the anchor for home page
			$breadcrumb->set_url(get_home_url());
		}
		//If we have a multi site and are not on the main site we may need to add a breadcrumb for the main site
		if($this->opt['bmainsite_display'] && !is_main_site())
		{
			//Get the site name
			$site_name = get_site_option('site_name');
			//Place the main site breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($site_name, $this->opt['Hmainsite_template'], array('main-home'), get_home_url($current_site->blog_id)));
		}
	}
	/**
	 * A modified version of WordPress' function of the same name
	 * 
	 * @param object $object the post or taxonomy object used to attempt to find the title
	 * 
	 * @return string the title
	 */
	protected function post_type_archive_title($object)
	{
		if(isset($object->labels->name))
		{
			//Core filter use here is ok for time being
			//TODO: Recheck validitiy prior to each release
			return apply_filters('post_type_archive_title', $object->labels->name, $object->name);
		}
	}
	/**
	 * Determines if a post type is a built in type or not
	 * 
	 * @param string $post_type the name of the post type
	 * 
	 * @return bool
	 */
	protected function is_builtin($post_type)
	{
		$type = get_post_type_object($post_type);
		//If we get a null, that means either then type wasn't found, or we had 'any' as a type, treat as builtin
		if($type === null)
		{
			return true;
		}
		else
		{
			return $type->_builtin;
		}
	}
	/**
	 * Determines if the current location is a for a root page or not
	 * 
	 * @param string $post_type the name of the post type
	 * @return bool
	 * 
	 * TODO: Remove dependancies to current state (state should be passed in)
	 */
	protected function treat_as_root_page($post_type)
	{
		return (is_home() || (is_post_type_archive() && !$this->opt['bpost_' . $post_type . '_archive_display']));
	}
	/**
	 * Determines if a post type has archives enabled or not
	 * 
	 * @param string $post_type the name of the post type
	 * 
	 * @return bool
	 */
	protected function has_archive($post_type)
	{
		$type = get_post_type_object($post_type); //TODO need a check on this for WP_Error?
		return $type->has_archive;
	}
	/**
	 * Retrieves the query var for 'post_type', sets default to post, and escapes
	 * 
	 * @param string $default[optional] The default value to return if nothing was found/set or if post_type was an array
	 * 
	 * @return string The post type string found in the post_type query var
	 */
	protected function get_type_string_query_var($default = 'post')
	{
		$type_str = get_query_var('post_type', $default);
		if($type_str === '' || is_array($type_str))
		{
			//If we didn't get a type, or it was an array, try the the first post
			$post = get_post();
			if($post instanceof WP_Post)
			{
				$type_str = $post->post_type;
			}
			else
			{
				$type_str = $default;
			}
		}
		return esc_attr($type_str);
	}
	/**
	 * Retrieves the query var for 'post_type', and returns whether or not it is an array
	 * 
	 * @return bool Whether or not the post_type query var is an array
	 */
	protected function is_type_query_var_array()
	{
		return is_array(get_query_var('post_type'));
	}
	/**
	 * Adds the post type argument to the URL iff the passed in type is not post
	 * 
	 * @param string $url The URL to possibly add the post_type argument to
	 * @param string $type[optional] The type to possibly add to the URL
	 * @param string $taxonomy[optional] If we're dealing with a taxonomy term, the taxonomy of that term
	 * 
	 * @return string The possibly modified URL
	 */
	protected function maybe_add_post_type_arg($url, $type = null, $taxonomy = null)
	{
		global $wp_taxonomies;
		//Rather than default to post, we should try to find the type
		if($type == null)
		{
			$type = $this->get_type_string_query_var();
		}
		//Add a query arg if we are not on the default post type for the archive in question and the post type is not post
		$add_query_arg = (!($taxonomy && $type === $wp_taxonomies[$taxonomy]->object_type[0]) && $type !== 'post');
		//Filter the add_query_arg logic, only add the query arg if necessary
		if(apply_filters('bcn_add_post_type_arg', $add_query_arg, $type, $taxonomy))
		{
			$url = add_query_arg(array('post_type' => $type), $url);
		}
		return $url;
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * Deals with the post type archive and taxonomy archives
	 * 
	 * @param WP_Post|WP_Taxonomy $type The post or taxonomy to generate the archive breadcrumb for
	 * @param string $type_str The type string for the archive
	 * 
	 * TODO: Remove dependancies to current state (state should be passed in)
	 */
	protected function type_archive($type, $type_str = false)
	{
		global $wp_taxonomies;
		if(!isset($type->taxonomy) && $type_str === false) //TODO could probably check the class type here
		{
			$type_str = $this->get_type_string_query_var();
		}
		//If this is a custom post type with a post type archive, add it
		if($type_str && !$this->is_builtin($type_str) && $this->opt['bpost_' . $type_str . '_archive_display'] && $this->has_archive($type_str))
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($this->post_type_archive_title(get_post_type_object($type_str)), $this->opt['Hpost_' . $type_str . '_template'], array('post', 'post-' . $type_str . '-archive'), get_post_type_archive_link($type_str)));
		}
		//Otherwise, if this is a custom taxonomy with an archive, add it
		else if(isset($type->taxonomy) && isset($wp_taxonomies[$type->taxonomy]->object_type[0]) 
			&& !$this->is_builtin($this->get_type_string_query_var($wp_taxonomies[$type->taxonomy]->object_type[0])) 
			&& $this->opt['bpost_' . $this->get_type_string_query_var($wp_taxonomies[$type->taxonomy]->object_type[0]) . '_archive_display'] 
			&& $this->has_archive($this->get_type_string_query_var($wp_taxonomies[$type->taxonomy]->object_type[0]))
			&& !$this->is_type_query_var_array())
		{
			//We end up using the post type in several places, give it a variable
			$post_type = apply_filters('bcn_type_archive_post_type', $this->get_type_string_query_var($wp_taxonomies[$type->taxonomy]->object_type[0]));
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($this->post_type_archive_title(get_post_type_object($post_type)), $this->opt['Hpost_' . $post_type . '_template'], array('post', 'post-' . $post_type . '-archive'), get_post_type_archive_link($post_type)));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function 
	 *
	 * Handles only the root page stuff for post types, including the "page for posts"
	 * 
	 * @param string $type_str The type string variable
	 * @param int $root_id The ID for the post type root
	 * @param bool $is_paged Whether or not the current resource is on a page other than page 1
	 * @param bool $is_current_item Whether or not the breadcrumb being generated is the current item
	 */
	protected function do_root($type_str, $root_id, $is_paged = false, $is_current_item = true)
	{
		//Nothing to do for the page post type, exit early
		if($type_str === 'page')
		{
			return;
		}
		$frontpage_id = get_option('page_on_front');
		//Retrieve the post for the root_id as we will need it eventually
		$bcn_post = get_post($root_id);
		//We'll have to check if this ID is valid, e.g. user has specified a posts page
		if($bcn_post instanceof WP_Post && $root_id > 0 && $root_id != $frontpage_id)
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, we get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb(get_the_title($root_id), $this->opt['Hpost_' . $type_str . '_template_no_anchor'], array($type_str . '-root', 'post', 'post-' . $type_str), null, $root_id));
			//If we are at home, or any root page archive then we need to add the current item type
			if($is_current_item)
			{
				$breadcrumb->add_type('current-item');
			}
			//If we're not on the current item we need to setup the anchor
			if(!$is_current_item || ($is_current_item && $this->opt['bcurrent_item_linked']) || ($is_paged && $this->opt['bpaged_display']))
			{
				$breadcrumb->set_template($this->opt['Hpost_' . $type_str . '_template']);
				//Figure out the anchor for home page
				$breadcrumb->set_url(get_permalink($root_id));
			}
			//Done with the "root", now on to the parents
			//If there is a parent post let's find it
			if($bcn_post->post_parent > 0 && $bcn_post->ID != $bcn_post->post_parent && $frontpage_id != $bcn_post->post_parent)
			{
				$this->post_parents($bcn_post->post_parent, $frontpage_id);
			}
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for 404 pages.
	 */
	protected function do_404()
	{
		//Place the breadcrumb in the trail, uses the bcn_breadcrumb constructor to set the title, prefix, and suffix
		$this->breadcrumbs[] = new bcn_breadcrumb($this->opt['S404_title'], $this->opt['H404_template'], array('404', 'current-item'));
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for paged pages
	 * 
	 * @param int $page_number The page number to create a breadcrumb for
	 */
	protected function do_paged($page_number)
	{
		//Place the breadcrumb in the trail, uses the bcn_breadcrumb constructor to set the title, prefix, and suffix
		$this->breadcrumbs[] = new bcn_breadcrumb((string) $page_number, $this->opt['Hpaged_template'], array('paged'));
	}
	/**
	 * Breadcrumb Trail Filling Function
	 * 
	 * This functions fills the breadcrumb trail.
	 */
	public function fill()
	{
		global $wpdb, $wp_query, $wp;
		//Check to see if the trail is already populated
		if(count($this->breadcrumbs) > 0)
		{
			//Exit early since we have breadcrumbs in the trail
			return null;
		}
		if($this->opt['bblog_display'])
		{
			$this->opt['apost_post_root'] = get_option('page_for_posts');
		}
		else
		{
			$this->opt['apost_post_root'] = false;
		}
		//Do any actions if necessary, we past through the current object instance to keep life simple
		do_action('bcn_before_fill', $this);
		$type = $wp_query->get_queried_object();
		//Do specific opperations for the various page types
		//Check if this isn't the first of a multi paged item
		if($this->opt['bpaged_display'] && (is_paged() || is_singular() && get_query_var('page') > 1))
		{
			//Need to switch between paged and page for archives and singular (posts)
			if(get_query_var('paged') > 0)
			{
				//Can use simple type hinting here to int since we already checked for greater than 0
				$page_number = (int) abs(get_query_var('paged'));
			}
			else
			{
				$page_number = (int) abs(get_query_var('page'));
			}
			$this->do_paged($page_number);
		}
		//For the front page, as it may also validate as a page, do it first
		if(is_front_page())
		{
			//Must have two seperate branches so that we don't evaluate it as a page
			if($this->opt['bhome_display'])
			{
				$this->do_home(false, is_paged());
			}
		}
		//For posts
		else if(is_singular())
		{
			$this->do_post(get_post(), false, (get_query_var('page') > 1));
			//If this is an attachment then we need to change the queried object to the parent post
			if(is_attachment())
			{
				//Could use the $post global, but we can't really trust it
				$post = get_post();
				$type = get_post($post->post_parent); //TODO check for WP_Error?
			}
			$this->do_root($type->post_type, $this->opt['apost_' . $type->post_type . '_root'], is_paged(), false);
		}
		//For searches
		else if(is_search())
		{
			$this->do_search(get_search_query(), is_paged());
		}
		//For author pages
		else if(is_author())
		{
			$this->do_author($type, is_paged());
			$this->do_root('post', $this->opt['aauthor_root'], is_paged(), false);
		}
		//For archives
		else if(is_archive())
		{
			//We need the type for later, so save it
			$type_str = get_query_var('post_type');
			//May be an array, if so, rewind the iterator and grab first item
			if(is_array($type_str))
			{
				$type_str = reset($type_str);
			}
			//For date based archives
			if(is_date())
			{
				//First deal with the day breadcrumb
				if(is_day())
				{
					$this->do_day(get_post(), $this->get_type_string_query_var(), is_paged(), true);
				}
				//Now deal with the month breadcrumb
				if(is_month() || is_day())
				{
					$this->do_month(get_post(), $this->get_type_string_query_var(), is_paged(), is_month());
				}
				$this->do_year(get_post(), $this->get_type_string_query_var(), is_paged(), is_year());
				$type_str = $this->get_type_string_query_var();
				$this->type_archive($type, $type_str);
			}
			//If we have a post type archive, and it does not have a root page generate the archive
			else if(is_post_type_archive() && !isset($type->taxonomy)
				&& (!is_numeric($this->opt['apost_' . $type_str . '_root']) || $this->opt['bpost_' . $type_str . '_archive_display']))
			{
				$this->do_archive_by_post_type($this->get_type_string_query_var(), is_paged());
			}
			//For taxonomy based archives
			else if(is_category() || is_tag() || is_tax())
			{
				$this->do_archive_by_term($type, is_paged());
				$this->type_archive($type);
				$type_str = $this->get_type_string_query_var($GLOBALS['wp_taxonomies'][$type->taxonomy]->object_type[0]);
			}
			else
			{
				$this->type_archive($type);
			}
			$this->do_root($type_str, $this->opt['apost_' . $type_str . '_root'], is_paged(), $this->treat_as_root_page($type_str));
		}
		//For 404 pages
		else if(is_404())
		{
			$this->do_404();
		}
		else
		{
			//If it looks, walks, and quacks like a taxonomy, treat is as one
			if(isset($type->taxonomy))
			{
				$this->do_archive_by_term($type, is_paged());
				$this->type_archive($type);
				$type_str = $this->get_type_string_query_var($wp_taxonomies[$type->taxonomy]->object_type[0]);
			}
			//Otherwise, it's likely the blog page
			else if($this->opt['bblog_display'] || is_home())
			{
				$type_str = 'post';
			}
			if(isset($this->opt['apost_' . $type_str . '_root']))
			{
				$this->do_root($type_str, $this->opt['apost_' . $type_str . '_root'], is_paged(), $this->treat_as_root_page($type_str));
			}
		}
		//We always do the home link last, unless on the frontpage
		if(!is_front_page())
		{
			$this->do_home(true, false, false);
		}
		//Do any actions if necessary, we past through the current object instance to keep life simple
		do_action('bcn_after_fill', $this);
	}
	public function fill_REST($item)
	{
		if($item instanceof WP_Error || $item === null)
		{
			return;
		}
		//Handle Posts
		if($item instanceof WP_Post)
		{
			$this->do_post($item, false, true);
			$this->do_root($item->post_type, $this->opt['apost_' . $item->post_type . '_root'], false, false);
		}
		//Handle Terms
		else if($item instanceof WP_Term)
		{
			$this->do_archive_by_term($item, true);
			$this->type_archive($item);
			$type_str = $this->get_type_string_query_var($GLOBALS['wp_taxonomies'][$item->taxonomy]->object_type[0]);
			$this->do_root($type_str, $this->opt['apost_' . $type_str . '_root'], is_paged(), $this->treat_as_root_page($type_str));
		}
		//Handle Author Archives
		else if($item instanceof WP_User)
		{
			$this->do_author($item, true);
			$this->do_root('post', $this->opt['aauthor_root'], false, false);
		}
		$this->do_home(true, false, false);
	}
	/**
	 * This function will either set the order of the trail to reverse key 
	 * order, or make sure it is forward key ordered.
	 * 
	 * @param bool $reverse[optional] Whether to reverse the trail or not.
	 */
	protected function order($reverse = false)
	{
		if($reverse)
		{
			//Since there may be multiple calls our trail may be in a non-standard order
			ksort($this->breadcrumbs);
		}
		else
		{
			//For normal opperation we must reverse the array by key
			krsort($this->breadcrumbs);
		}
	}
	/**
	 * This functions outputs or returns the breadcrumb trail in string form.
	 *
	 * @param bool $linked[optional] Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse[optional] Whether to reverse the output or not.
	 * @param string $template The template to use for the string output.
	 * 
	 * @return void Void if Option to print out breadcrumb trail was chosen.
	 * @return string String-Data of breadcrumb trail.
	 */
	public function display($linked = true, $reverse = false, $template = '%1$s%2$s')
	{
		//Set trail order based on reverse flag
		$this->order($reverse);
		//The main compiling loop
		$trail_str = $this->display_loop($linked, $reverse, $template);
		return $trail_str;
	}
	/**
	 * This functions outputs or returns the breadcrumb trail in list form.
	 *
	 * @deprecated 6.0.0 No longer needed, superceeded by $template parameter in display
	 * 
	 * @param bool $linked[optional] Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse[optional] Whether to reverse the output or not.
	 * 
	 * @return void Void if option to print out breadcrumb trail was chosen.
	 * @return string String version of the breadcrumb trail.
	 */
	public function display_list($linked = true, $reverse = false)
	{
		_deprecated_function( __FUNCTION__, '6.0', 'bcn_breadcrumb_trail::display');
		return $this->display($return, $linked, $reverse, "<li%3\$s>%1\$s</li>\n");
	}
	/**
	 * This function assembles the breadcrumbs in the breadcrumb trail in accordance with the passed in template
	 * 
	 * @param bool $linked  Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse Whether to reverse the output or not.
	 * @param string $template The template to use for the string output.
	 * 
	 * @return string String-Data of breadcrumb trail.
	 */
	protected function display_loop($linked, $reverse, $template)
	{
		$position = 1;
		$last_position = count($this->breadcrumbs);
		//Initilize the string which will hold the assembled trail
		$trail_str = '';
		foreach($this->breadcrumbs as $key => $breadcrumb)
		{
			$types = $breadcrumb->get_types();
			array_walk($types, 'sanitize_html_class');
			$class = sprintf(' class="%s"', esc_attr(implode(' ', $types)));
			//Deal with the separator
			if($position < $last_position)
			{
				$separator = $this->opt['hseparator'];
			}
			else
			{
				$separator = '';
			}
			//Filter li_attributes adding attributes to the li element
			$attribs = apply_filters_deprecated('bcn_li_attributes', array($class, $breadcrumb->get_types(), $breadcrumb->get_id()), '6.0.0', 'bcn_display_attributes');
			$attribs = apply_filters('bcn_display_attributes', $class, $breadcrumb->get_types(), $breadcrumb->get_id());
			//Trim titles, if requested
			if($this->opt['blimit_title'] && $this->opt['amax_title_length'] > 0)
			{
				//Trim the breadcrumb's title
				$breadcrumb->title_trim($this->opt['amax_title_length']);
			}
			//Assemble the breadrumb and wrap with li's
			$trail_str .= sprintf($template, $breadcrumb->assemble($linked, $position), $separator, $attribs);
			$position++;
		}
		return $trail_str;
	}
	/**
	 * This functions outputs or returns the breadcrumb trail in Schema.org BreadcrumbList compliant JSON-LD
	 *
	 * @param bool $reverse[optional] Whether to reverse the output or not.
	 * 
	 * @return void Void if option to print out breadcrumb trail was chosen.
	 * @return object basic object version of the breadcrumb trail ready for json_encode.
	 */
	public function display_json_ld($reverse = false)
	{
		//Set trail order based on reverse flag
		$this->order($reverse);
		$trail_str = (object)array(
			'@context' => 'http://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => $this->json_ld_loop());
		return $trail_str;
	}
	/**
	 * This function assembles all of the breadcrumbs into an object ready for json_encode
	 *
	 * @return array The array of breadcrumbs prepared for JSON-LD
	 */
	protected function json_ld_loop()
	{		
		$postion = 1;
		$breadcrumbs = array();
		//Loop around our breadcrumbs, call the JSON-LD assembler
		foreach($this->breadcrumbs as $breadcrumb)
		{
			$breadcrumbs[] = $breadcrumb->assemble_json_ld($postion);
			$postion++;
		}
		return $breadcrumbs;
	}
}

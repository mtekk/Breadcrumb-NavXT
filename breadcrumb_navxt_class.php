<?php
/*  
	Copyright 2007-2012  John Havlik  (email : mtekkmonkey@gmail.com)

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

//The breadcrumb class
class bcn_breadcrumb
{
	//Our member variables
	//The main text that will be shown
	protected $title;
	//The breadcrumb's template, used durring assembly
	protected $template;
	//The breadcrumb's no anchor template, used durring assembly when there won't be an anchor
	protected $template_no_anchor = '%title%';
	//Boolean, is this element linked
	protected $linked;
	//The link the breadcrumb leads to, null if $linked == false
	protected $url;
	protected $_tags = array(
					'%title%',
					'%link%',
					'%htitle%',
					'%type%');
	//The type of this breadcrumb
	public $type;
	/**
	 * The enhanced default constructor, ends up setting all parameters via the set_ functions
	 *  
	 * @param string $title (optional) The title of the breadcrumb
	 * @param string $template (optional) The html template for the breadcrumb
	 * @param string $type (optional) The breadcrumb type
	 * @param string $url (optional) The url the breadcrumb links to
	 */
	public function bcn_breadcrumb($title = '', $template = '', $type = '', $url = NULL)
	{
		//Set the title
		$this->set_title($title);
		//Assign the breadcrumb template
		if($template == NULL)
		{
			$template = __('<a title="Go to %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt');
		}
		if($url == NULL)
		{
			$this->template_no_anchor = $template;
		}
		else
		{
			$this->template = $template;
		}
		//The breadcrumb type
		$this->type = $type;
		//Always NULL if unlinked
		$this->set_url($url);
	}
	/**
	 * Function to set the protected title member
	 * 
	 * @param string $title The title of the breadcrumb
	 */
	public function set_title($title)
	{
		//Set the title
		$this->title = apply_filters('bcn_breadcrumb_title', __($title, 'breadcrumb-navxt'));
	}
	/**
	 * Function to get the protected title member
	 * 
	 * @return $this->title
	 */
	public function get_title()
	{
		//Return the title
		return $this->title;
	}
	/**
	 * Function to set the internal URL variable
	 * 
	 * @param string $url the url to link to
	 */
	public function set_url($url)
	{
		$this->url = $url;
		//Set linked to true if we set a non-null $url
		if($url)
		{
			$this->linked = true;
		}
	}
	/**
	 * Sets the internal breadcrumb template
	 * 
	 * @param string $template the template to use durring assebly
	 */
	public function set_template($template)
	{
		//Assign the breadcrumb template
		$this->template = $template;
	}
	/**
	 * Append a type entry to the type array
	 * 
	 * @param string $type the type to append
	 */
	public function add_type($type)
	{
		$this->type[] = $type;
	}
	/**
	 * This function will intelligently trim the title to the value passed in through $max_length.
	 * 
	 * @param int $max_length of the title.
	 */
	public function title_trim($max_length)
	{
		//Make sure that we are not making it longer with that ellipse
		if((mb_strlen($this->title) + 3) > $max_length)
		{
			//Trim the title
			$this->title = mb_substr($this->title, 0, $max_length - 1);
			//Make sure we can split a, four keywords are available %link%, %title%, %htitle%, and %type%pace, but we want to limmit to cutting at max an additional 25%
			if(mb_strpos($this->title, ' ', .75 * $max_length) > 0)
			{
				//Don't split mid word
				while(mb_substr($this->title,-1) != ' ')
				{
					$this->title = mb_substr($this->title, 0, -1);
				}
			}
			//Remove the whitespace at the end and add the hellip
			$this->title = rtrim($this->title) . '&hellip;';
		}
	}
	/**
	 * Assembles the parts of the breadcrumb into a html string
	 * 
	 * @param bool $linked (optional) Allow the output to contain anchors?
	 * @return string The compiled breadcrumb string
	 */
	public function assemble($linked = true)
	{
		//var_dump($this);
		//Build our replacements array
		$replacements = array(
							esc_attr(strip_tags($this->title)),
							$this->url,
							$this->title,
							$this->type);
		//The type may be an array, implode it if that is the case
		if(is_array($replacements[3]))
		{
			$replacements[3] = implode(' ', $replacements[3]);
		}
		//If we are linked we'll need to use the normal template
		if($this->linked && $linked)
		{
			//Return the assembled breadcrumb string
			return str_replace($this->_tags, $replacements, $this->template);
		}
		//Otherwise we use the no anchor template
		else
		{
			//Return the assembled breadcrumb string
			return str_replace($this->_tags, $replacements, $this->template_no_anchor);
		}
	}
}

//The trail class
class bcn_breadcrumb_trail
{
	//Our member variables
	private $version = '4.1.0';
	//An array of breadcrumbs
	public $trail = array();
	//The options
	public $opt;
	//Default constructor
	function bcn_breadcrumb_trail()
	{
		//Load the translation domain as the next part needs it		
		load_plugin_textdomain($domain = 'breadcrumb-navxt', false, 'breadcrumb-navxt/languages');
		//Initilize with default option values
		$this->opt = array(
			//Should the mainsite be shown
			'bmainsite_display' => true,
			//Title displayed when for the main site
			'Smainsite_title' => __('Home', 'breadcrumb-navxt'),
			//The breadcrumb template for the main site, this is global, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hmainsite_template' => __('<a title="Go to %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for the main site, used when an anchor is not needed, this is global, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hmainsite_template_no_anchor' => '%htitle%',
			//Should the home page be shown
			'bhome_display' => true,
			//Title displayed when is_home() returns true
			'Shome_title' => __('Home', 'breadcrumb-navxt'),
			//The breadcrumb template for the home page, this is global, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hhome_template' => __('<a title="Go to %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for the home page, used when an anchor is not needed, this is global, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hhome_template_no_anchor' => '%htitle%',
			//Should the blog page be shown globally
			'bblog_display' => true,
			//The breadcrumb template for the blog page only in static front page mode, this is global, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hblog_template' => __('<a title="Go to %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for the blog page only in static front page mode, used when an anchor is not needed, this is global, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hblog_template_no_anchor' => '%htitle%',
			//Separator that is placed between each item in the breadcrumb trial, but not placed before
			//the first and not after the last breadcrumb
			'hseparator' => ' &gt; ',
			//The maximum title lenght
			'amax_title_length' => 0,
			//Current item options, really only applies to static pages and posts unless other current items are linked
			'bcurrent_item_linked' => false,
			//The breadcrumb template for current items, this is global, four keywords are available %link%, %title%, %htitle%, and %type%
			//'Hcurrent_item_template' => __('<a title="Reload the current page." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for current items, used when an anchor is not needed, four keywords are available %link%, %title%, %htitle%, and %type%
			//'Hcurrent_item_template_no_anchor' => '%htitle%',
			//Static page options
			//The anchor template for page breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpost_page_template' => __('<a title="Go to %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The anchor template for page breadcrumbs, used when an anchor is not needed, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpost_page_template_no_anchor' => '%htitle%',
			//Just a link to the page on front property
			'apost_page_root' => get_option('page_on_front'),
			//Paged options
			//The template for paged breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpaged_template' => __('Page %htitle%', 'breadcrumb-navxt'),
			//Should we try filling out paged information
			'bpaged_display' => false,
			//The post options previously singleblogpost
			//The breadcrumb template for post breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpost_post_template' => __('<a title="Go to %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for post breadcrumbs, used when an anchor is not needed, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpost_post_template_no_anchor' => '%htitle%',
			//Just a link for the page for posts
			'apost_post_root' => get_option('page_for_posts'),
			//Should the trail include the taxonomy of the post
			'bpost_post_taxonomy_display' => true,
			//What taxonomy should be shown leading to the post, tag or category
			'Spost_post_taxonomy_type' => 'category',
			//Attachment settings
			//TODO: Need to move attachments to support via normal post handlers
			//The breadcrumb template for attachment breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpost_attachment_template' => __('<a title="Go to %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for attachment breadcrumbs, used when an anchor is not needed, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpost_attachment_template_no_anchor' => '%htitle%',
			//404 page settings
			//The template for 404 breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'H404_template' => '%htitle%',
			//The text to be shown in the breadcrumb for a 404 page
			'S404_title' => __('404', 'breadcrumb-navxt'),
			//Search page options
			//The breadcrumb template for search breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hsearch_template' => __('Search results for &#39;<a title="Go to the first page of search results for %title%." href="%link%">%htitle%</a>&#39;', 'breadcrumb-navxt'),
			//The breadcrumb template for search breadcrumbs, used when an anchor is not necessary, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hsearch_template_no_anchor' => __('Search results for &#39;%htitle%&#39;', 'breadcrumb-navxt'),
			//Tag related stuff
			//The breadcrumb template for tag breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpost_tag_template' => __('<a title="Go to the %title% tag archives." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for tag breadcrumbs, used when an anchor is not needed, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hpost_tag_template_no_anchor' => '%htitle%',
			//Author page stuff
			//The anchor template for author breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hauthor_template' => __('Articles by: <a title="Go to the first page of posts by %title%." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The anchor template for author breadcrumbs, used when anchors are not needed, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hauthor_template_no_anchor' => __('Articles by: %htitle%', 'breadcrumb-navxt'),
			//Which of the various WordPress display types should the author breadcrumb display
			'Sauthor_name' => 'display_name',
			//Category stuff
			//The breadcrumb template for category breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hcategory_template' => __('<a title="Go to the %title% category archives." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for category breadcrumbs, used when anchors are not needed, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hcategory_template_no_anchor' => '%htitle%',
			//The breadcrumb template for date breadcrumbs, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hdate_template' => __('<a title="Go to the %title% archives." href="%link%">%htitle%</a>', 'breadcrumb-navxt'),
			//The breadcrumb template for date breadcrumbs, used when anchors are not needed, four keywords are available %link%, %title%, %htitle%, and %type%
			'Hdate_template_no_anchor' => '%htitle%'
		);
	}
	/**
	 * This returns the internal version
	 *
	 * @return string internal version of the Breadcrumb trail
	 */
	public function get_version()
	{
		return $this->version;
	}
	/**
	 * Adds a breadcrumb to the breadcrumb trail
	 * 
	 * @return pointer to the just added Breadcrumb
	 * @param bcn_breadcrumb $object Breadcrumb to add to the trail
	 */
	function &add(bcn_breadcrumb $object)
	{
		$this->trail[] = $object;
		//Return the just added object
		return $this->trail[count($this->trail) - 1];
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a search page.
	 */
	function do_search()
	{
		//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_search_query(), $this->opt['Hsearch_template_no_anchor'], array('search', 'current-item')));
		//If we're paged, or allowing the current item to be linked, let's link to the first page
		if($this->opt['bcurrent_item_linked'] || (is_paged() && $this->opt['bpaged_display']))
		{
			//Since we are paged and are linking the root breadcrumb, time to change to the regular template
			$breadcrumb->set_template($this->opt['Hsearch_template']);
			//Figure out the hyperlink for the anchor
			$url = get_option('home') . '?s=' . str_replace(' ', '+', get_search_query());
			//Figure out the anchor for the search
			$breadcrumb->set_url($url);
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for an author page.
	 */
	function do_author()
	{
		global $author;
		//Get the Author name, note it is an object
		$curauth = (isset($_GET['author_name'])) ? get_userdatabylogin($author_name) : get_userdata(intval($author));
		//Setup array of valid author_name values
		$valid_author_name = array('display_name', 'nickname', 'first_name', 'last_name');
		//This translation allows us to easily select the display type later on
		$author_name = $this->opt['Sauthor_name'];
		//Make sure user picks only safe values
		if(in_array($author_name, $valid_author_name))
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb(apply_filters('the_author', $curauth->$author_name), $this->opt['Hauthor_template_no_anchor'], array('author', 'current-item')));
			//If we're paged, or allowing the current item to be linked, let's link to the first page
			if($this->opt['bcurrent_item_linked'] || (is_paged() && $this->opt['bpaged_display']))
			{
				//Set the template to our one containing an anchor
				$breadcrumb->set_template($this->opt['Hauthor_template']);
				$breadcrumb->set_url(get_author_posts_url($curauth->ID));
			}
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This function fills breadcrumbs for any post taxonomy.
	 * @param int $id The id of the post to figure out the taxonomy for.
	 * @param string $type The post type of the post to figure out the taxonomy for.
	 * @param int $parent (optional) The id of the parent of the current post, used if hiearchal posts will be the "taxonomy" for the current post
	 * 
	 * TODO: Add logic for contextual taxonomy selection
	 */
	function post_taxonomy($id, $type, $parent = null)
	{
		//Check to see if breadcrumbs for the taxonomy of the post needs to be generated
		if($this->opt['bpost_' . $type . '_taxonomy_display'])
		{
			//Check if we have a date 'taxonomy' request
			if($this->opt['Spost_' . $type . '_taxonomy_type'] == 'date')
			{
				$this->do_archive_by_date();
			}
			//Handle all hierarchical taxonomies, including categories
			else if(is_taxonomy_hierarchical($this->opt['Spost_' . $type . '_taxonomy_type']))
			{
				//Fill a temporary object with the terms
				$bcn_object = get_the_terms($id, $this->opt['Spost_' . $type . '_taxonomy_type']);
				if(is_array($bcn_object))
				{
					//Now find which one has a parent, pick the first one that does
					$bcn_use_term = key($bcn_object);
					foreach($bcn_object as $key=>$object)
					{
						//We want the first term hiearchy
						if($object->parent > 0)
						{
							$bcn_use_term = $key;
							//We found our first term hiearchy, can exit loop now
							break;
						}
					}
					//Fill out the term hiearchy
					$this->term_parents($bcn_object[$bcn_use_term]->term_id, $this->opt['Spost_' . $type . '_taxonomy_type']);
				}
			}
			//Handle the use of hierarchical posts as the 'taxonomy'
			else if(is_post_type_hierarchical($this->opt['Spost_' . $type . '_taxonomy_type']))
			{
				if($parent == null)
				{
					//We have to grab the post to find its parent, can't use $post for this one
					$parent = get_post($id);
					$parent = $parent->post_parent;
				}
				//Done with the current item, now on to the parents
				$bcn_frontpage = get_option('page_on_front');
				//If there is a parent page let's find it
				if($parent && $id != $parent && $bcn_frontpage != $parent)
				{
					$this->post_parents($parent, $bcn_frontpage);
				}
			}
			//Handle the rest of the taxonomies, including tags
			else
			{
				$this->post_terms($id, $this->opt['Spost_' . $type . '_taxonomy_type']);
			}
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for the terms of a post
	 * @param int $id The id of the post to find the terms for.
	 * @param string $taxonomy The name of the taxonomy that the term belongs to
	 * 
	 * TODO Need to implement this cleaner, fix up the entire tag_ thing, as this is now generic
	 * TODO This still needs to be updated to the new method of adding breadcrumbs to the trail
	 */
	function post_terms($id, $taxonomy)
	{
		//Fills a temporary object with the terms for the post
		$bcn_object = get_the_terms($id, $taxonomy);
		//Only process if we have terms
		if(is_array($bcn_object))
		{
			//Add new breadcrumb to the trail
			$this->trail[] = new bcn_breadcrumb();
			//Figure out where we placed the crumb, make a nice pointer to it
			$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
			$is_first = true;
			//Loop through all of the term results
			foreach($bcn_object as $term)
			{
				//Run through a filter for good measure
				$term->name = apply_filters("get_$taxonomy", $term->name);
				//Everything but the first term needs a comma separator
				if($is_first == false)
				{
					$bcn_breadcrumb->set_title($bcn_breadcrumb->get_title() . ', ');
				}
				//This is a bit hackish, but it compiles the term anchor and appends it to the current breadcrumb title
				$bcn_breadcrumb->set_title($bcn_breadcrumb->get_title() . str_replace(
					array('%title%', '%link%', '%htitle%', '%type%'),
					array($term->name, get_term_link($term, $taxonomy), $term->name, $term->taxonomy),
					$this->opt['H' . $term->taxonomy . '_template']));
				$is_first = false;
			}
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This recursive functions fills the trail with breadcrumbs for parent terms.
	 * @param int $id The id of the term.
	 * @param string $taxonomy The name of the taxonomy that the term belongs to
	 * @TODO Evaluate if we need to do tax_ for a prefix
	 */
	function term_parents($id, $taxonomy)
	{
		global $post;
		//Get the current category object, filter applied within this call
		$term = &get_term($id, $taxonomy);
		//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb($term->name, $this->opt['H' . $taxonomy . '_template'], array($taxonomy), get_term_link($term, $taxonomy)));
		//Make sure the id is valid, and that we won't end up spinning in a loop
		if($term->parent && $term->parent != $id)
		{
			//Figure out the rest of the term hiearchy via recursion
			$this->term_parents($term->parent, $taxonomy);
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This recursive functions fills the trail with breadcrumbs for parent posts/pages.
	 * @param int $id The id of the parent page.
	 * @param int $frontpage The id of the front page.
	 */
	function post_parents($id, $frontpage)
	{
		//Use WordPress API, though a bit heavier than the old method, this will ensure compatibility with other plug-ins
		$parent = get_post($id);
		//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_title($id), $this->opt['Hpost_' . $parent->post_type . '_template'], array($parent->post_type), get_permalink($id)));
		//Make sure the id is valid, and that we won't end up spinning in a loop
		if($parent->post_parent >= 0 && $parent->post_parent != false && $id != $parent->post_parent && $frontpage != $parent->post_parent)
		{
			//If valid, recursively call this function
			$this->post_parents($parent->post_parent, $frontpage);
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for posts
	 * 
	 */
	function do_post()
	{
		global $post, $page;
		//Place the breadcrumb in the trail, uses the bcn_breadcrumb constructor to set the title, template, and type
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_title(), $this->opt['Hpost_' . $post->post_type . '_template_no_anchor'], array('post-' . $post->post_type, 'current-item')));
		//If the current item is to be linked, or this is a paged post, add in links
		if($this->opt['bcurrent_item_linked'] || ($page > 1 && $this->opt['bpaged_display']))
		{
			//Change the template over to the normal, linked one
			$breadcrumb->set_template($this->opt['Hpost_' . $post->post_type . '_template']);
			//Add the link
			$breadcrumb->set_url(get_permalink());
		}
		//If we have a hiearchical post, go through the parent tree
		if(is_post_type_hierarchical($post->post_type))
		{
			//Done with the current item, now on to the parents
			$frontpage = get_option('page_on_front');
			//If there is a parent page let's find it
			if($post->post_parent && $post->ID != $post->post_parent && $frontpage != $post->post_parent)
			{
				$this->post_parents($post->post_parent, $frontpage);
			}
			else if(!$this->is_builtin($post->post_type))
			{
				//Handle the post's taxonomy
				$this->post_taxonomy($post->ID, $post->post_type, $post->post_parent);	
			}
		}
		//Otherwise we need the follow the taxonomy tree
		else
		{
			//Handle the post's taxonomy
			$this->post_taxonomy($post->ID, $post->post_type, $post->post_parent);
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for an attachment page.
	 * 
	 * @TODO Evaluate if this can be merged in with the do_post function
	 */
	function do_attachment()
	{
		global $post;
		//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_title(), $this->opt['Hpost_attachment_template_no_anchor'], array('post-attachment', 'current-item')));
		if($this->opt['bcurrent_item_linked'])
		{
			//Change the template over to the normal, linked one
			$breadcrumb->set_template($this->opt['Hpost_attachment_template']);
			//Add the link
			$breadcrumb->set_url(get_permalink());
		}
		//Get the parent's information
		$parent = get_post($post->post_parent);
		//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_title($post->post_parent), $this->opt['Hpost_' . $parent->post_type . '_template'], array($parent->post_type), get_permalink($post->post_parent)));
		//We need to treat flat and hiearchical post attachment hierachies differently
		if(is_post_type_hierarchical($parent->post_type))
		{
			//Done with the current item, now on to the parents
			$frontpage = get_option('page_on_front');
			//If there is a parent page let's find it
			if($parent->post_parent && $parent->ID != $parent->post_parent && $frontpage != $parent->post_parent)
			{
				$this->post_parents($parent->post_parent, $frontpage);
			}
			else if(!$this->is_builtin($parent->post_type))
			{
				//Handle the post's taxonomy
				$this->post_taxonomy($post->post_parent, $parent->post_type);	
			}
		}
		else
		{
			//Handle the post's taxonomy
			$this->post_taxonomy($post->post_parent, $parent->post_type);
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This function fills a breadcrumb for any taxonomy archive, was previously two separate functions
	 * 
	 */
	function do_archive_by_term()
	{
		global $wp_query;
		//Simmilar to using $post, but for things $post doesn't cover
		$term = $wp_query->get_queried_object();
		//Run through a filter for good measure
		$term->name = apply_filters('get_' . $term->taxonomy, $term->name);
		//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb($term->name, $this->opt['H' . $term->taxonomy . '_template_no_anchor'], array($term->taxonomy, 'current-item')));
		//If we're paged, let's link to the first page
		if($this->opt['bcurrent_item_linked'] || (is_paged() && $this->opt['bpaged_display']))
		{
			$breadcrumb->set_template($this->opt['H' . $term->taxonomy . '_template']);
			//Figure out the anchor for current category
			$breadcrumb->set_url(get_term_link($term, $term->taxonomy));
		}
		//Get parents of current category
		if($term->parent)
		{
			$this->term_parents($term->parent, $term->taxonomy);
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a date archive.
	 *
	 */
	function do_archive_by_date()
	{
		global $wp_query;
		//First deal with the day breadcrumb
		if(is_day() || is_single())
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb(get_the_time('d'), $this->opt['Hdate_template_no_anchor'], array('date-day')));
			//If this is a day archive, add current-item type
			if(is_day())
			{
				$breadcrumb->add_type('current-item');
			}
			//If we're paged, let's link to the first page
			if($this->opt['bcurrent_item_linked'] || is_paged() && $this->opt['bpaged_display'] || is_single())
			{
				//We're linking, so set the linked template
				$breadcrumb->set_template($this->opt['Hdate_template']);
				//Deal with the anchor
				$breadcrumb->set_url(get_day_link(get_the_time('Y'), get_the_time('m'), get_the_time('d')));
			}
		}
		//Now deal with the month breadcrumb
		if(is_month() || is_day() || is_single())
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb(get_the_time('F'), $this->opt['Hdate_template_no_anchor'], array('date-month')));
			//If this is a month archive, add current-item type
			if(is_month())
			{
				$breadcrumb->add_type('current-item');
			}
			//If we're paged, or not in the archive by month let's link to the first archive by month page
			if($this->opt['bcurrent_item_linked'] || is_day() || is_single() || (is_month() && is_paged() && $this->opt['bpaged_display']))
			{
				//We're linking, so set the linked template
				$breadcrumb->set_template($this->opt['Hdate_template']);
				//Deal with the anchor
				$breadcrumb->set_url(get_month_link(get_the_time('Y'), get_the_time('m')));
			}
		}
		//Place the year breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(get_the_time('Y'), $this->opt['Hdate_template_no_anchor'], array('date-year')));
		//If this is a year archive, add current-item type
		if(is_year())
		{
			$breadcrumb->add_type('current-item');
		}
		//If we're paged, or not in the archive by year let's link to the first archive by year page
		if($this->opt['bcurrent_item_linked'] || is_day() || is_month() || is_single() || (is_paged() && $this->opt['bpaged_display']))
		{
			//We're linking, so set the linked template
			$breadcrumb->set_template($this->opt['Hdate_template']);
			//Deal with the anchor
			$breadcrumb->set_url(get_year_link(get_the_time('Y')));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a post type archive (WP 3.1 feature)
	 */
	function do_archive_by_post_type()
	{
		//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb(post_type_archive_title('', false), $this->opt['Hpost_' . get_post_type() . '_template_no_anchor'], array('post-' . get_post_type() . '-archive', 'current-item')));
		if($this->opt['bcurrent_item_linked'] || is_paged() && $this->opt['bpaged_display'])
		{
			$breadcrumb->set_template($this->opt['Hpost_' . get_post_type() . '_template']);
			//Deal with the anchor
			$breadcrumb->set_url(get_post_type_archive_link(get_post_type()));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for the front page.
	 */
	function do_front_page()
	{
		global $post, $current_site;
		//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
		$breadcrumb = $this->add(new bcn_breadcrumb($this->opt['Shome_title'], $this->opt['Hhome_template_no_anchor'], array('site-home', 'current-item')));
		//If we're paged, let's link to the first page
		if($this->opt['bcurrent_item_linked'] || (is_paged() && $this->opt['bpaged_display']))
		{
			$breadcrumb->set_template($this->opt['Hhome_template']);
			//Figure out the anchor for home page
			$breadcrumb->set_url(get_home_url());
		}
		//If we have a multi site and are not on the main site we may need to add a breadcrumb for the main site
		if($this->opt['bmainsite_display'] && !is_main_site())
		{
			//Place the main site breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($this->opt['Smainsite_title'], $this->opt['Hmainsite_template'], array('mainsite-home'), get_home_url($current_site->blog_id)));
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for the home page.
	 */
	function do_home()
	{
		global $post, $current_site;
		//On everything else we need to link, but no current item (pre/suf)fixes
		if($this->opt['bhome_display'])
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($this->opt['Shome_title'], $this->opt['Hhome_template'], array('site-home'), get_home_url()));
			//If we have a multi site and are not on the main site we need to add a breadcrumb for the main site
			if($this->opt['bmainsite_display'] && !is_main_site())
			{
				//Place the main site breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
				$breadcrumb = $this->add(new bcn_breadcrumb($this->opt['Smainsite_title'], $this->opt['Hmainsite_template_no_anchor'], array('mainsite-home'), get_home_url($current_site->blog_id)));
			}
		}
	}
	/**
	 * A modified version of WordPress' function of the same name
	 * 
	 * @param object $object the post or taxonomy object used to attempt to find the title
	 * @return string the title
	 */
	function post_type_archive_title($object)
	{
		if(isset($object->labels->name))
		{
			return apply_filters('post_type_archive_title', $object->labels->name);
		}
	}
	/**
	 * Determines if a post type is a built in type or not
	 * 
	 * @param string $post_type the name of the post type
	 * @return bool
	 */
	function is_builtin($post_type)
	{
		$type = get_post_type_object($post_type);
		return $type->_builtin;
	}
	/**
	 * Determines if a post type has archives enabled or not
	 * 
	 * @param string $post_type the name of the post type
	 * @return bool
	 */
	function has_archive($post_type)
	{
		$type = get_post_type_object($post_type);
		return $type->has_archive;
	}
	/**
	 * This function populates our type_str and posts_id variables
	 * 
	 * @param post $type A post object we are using to figureout the type
	 * @param string $type_str The type string variable, passed by reference
	 * @param int $root_id The ID for the post type root
	 */
	function find_type($type, &$type_str, &$root_id)
	{
		global $wp_taxonomies;
		//We need to do special things for custom post types
		if(is_singular() && !$this->is_builtin($type->post_type))
		{
			//We need the type for later, so save it
			$type_str = $type->post_type;
			//This will assign a ID for root page of a custom post
			if(is_numeric($this->opt['apost_' . $type_str . '_root']))
			{
				$root_id = $this->opt['apost_' . $type_str . '_root'];
			}
		}
		//For CPT archives
		else if(is_post_type_archive() && !isset($type->taxonomy))
		{
			//We need the type for later, so save it
			$type_str = $type->name;
			//This will assign a ID for root page of a custom post's taxonomy archive
			if(is_numeric($this->opt['apost_' . $type_str . '_root']))
			{
				$root_id = $this->opt['apost_' . $type_str . '_root'];
			}
		}
		//We need to do special things for custom post type archives, but not author or date archives
		else if(is_archive() && !is_author() && !is_date() && !$this->is_builtin($wp_taxonomies[$type->taxonomy]->object_type[0]))
		{
			//We need the type for later, so save it
			$type_str = $wp_taxonomies[$type->taxonomy]->object_type[0];
			//This will assign a ID for root page of a custom post's taxonomy archive
			if(is_numeric($this->opt['apost_' . $type_str . '_root']))
			{
				$root_id = $this->opt['apost_' . $type_str . '_root'];
			}
		}
		else
		{
			$type_str = "post";
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function 
	 *
	 * Handles only the root page stuff for post types, including the "page for posts"
	 * 
	 * TODO: this still needs to be cleaned up
	 */
	function do_root()
	{
		global $post, $wp_query, $wp_taxonomies, $current_site;
		//If this is an attachment then we need to change the queried object to the parent post
		if(is_attachment())
		{
			$type = get_post($post->post_parent);
		}
		else
		{
			//Simmilar to using $post, but for things $post doesn't cover
			$type = $wp_query->get_queried_object();
		}
		$root_id = -1;
		//Find our type string and root_id
		$this->find_type($type, $type_str, $root_id);
		//These two are for taxonomy archives and for a single custom post type
		if(isset($type->post_type) && !$this->is_builtin($type->post_type) && $this->opt['bpost_' . $type->post_type . '_archive_display'] && $this->has_archive($type->post_type))
		{
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($this->post_type_archive_title(get_post_type_object($type->post_type)), $this->opt['Hpost_' . $type->post_type . '_template'], array('post-' . $type->post_type . '-archive'), get_post_type_archive_link($type->post_type)));
		}
		else if(isset($type->taxonomy) && !$this->is_builtin($wp_taxonomies[$type->taxonomy]->object_type[0]) && $this->opt['bpost_' . $wp_taxonomies[$type->taxonomy]->object_type[0] . '_archive_display'] && $this->has_archive($wp_taxonomies[$type->taxonomy]->object_type[0]))
		{
			//We end up using the post type in several places, give it a variable
			$post_type = $wp_taxonomies[$type->taxonomy]->object_type[0];
			//Place the breadcrumb in the trail, uses the constructor to set the title, prefix, and suffix, get a pointer to it in return
			$breadcrumb = $this->add(new bcn_breadcrumb($this->post_type_archive_title(get_post_type_object($post_type)), $this->opt['Hpost_' . $post_type . '_template'], array('post-' . $post_type . '-archive'), get_post_type_archive_link($post_type)));
		}
		//We only need the "blog" portion on members of the blog, and only if we're in a static frontpage environment
		if($root_id > 1 || $this->opt['bblog_display'] && get_option('show_on_front') == 'page' && (is_home() || is_single() || is_tax() || is_category() || is_tag()))
		{
			//If we entered here with a posts page, we need to set the id
			if($root_id < 0)
			{
				$root_id = get_option('page_for_posts');
			}
			$frontpage_id = get_option('page_on_front');
			//We'll have to check if this ID is valid, e.g. user has specified a posts page
			if($root_id && $root_id != $frontpage_id)
			{
				//Place the breadcrumb in the trail, uses the constructor to set the title, template, and type, we get a pointer to it in return
				$breadcrumb = $this->add(new bcn_breadcrumb(get_the_title($root_id), $this->opt['Hpost_' . $type_str . '_template_no_anchor'], array($type_str . '-root', 'post-' . $type_str)));
				//If we are at home, then we need to add the current item type
				if(is_home())
				{
					$breadcrumb->add_type('current-item');
				}
				//If we're not on the current item we need to setup the anchor
				if(!is_home() || (is_paged() && $this->opt['bpaged_display']) || (is_home() && $this->opt['bcurrent_item_linked']))
				{
					$breadcrumb->set_template($this->opt['Hpost_' . $type_str . '_template']);
					//Figure out the anchor for home page
					$breadcrumb->set_url(get_permalink($root_id));
				}
				//Done with the "root", now on to the parents
				//Get the blog page
				$bcn_post = get_post($root_id);
				//If there is a parent post let's find it
				if($bcn_post->post_parent && $bcn_post->ID != $bcn_post->post_parent && $frontpage_id != $bcn_post->post_parent)
				{
					$this->post_parents($bcn_post->post_parent, $frontpage_id);
				}
			}
		}
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for 404 pages.
	 */
	function do_404()
	{
		//Place the breadcrumb in the trail, uses the bcn_breadcrumb constructor to set the title, prefix, and suffix
		$this->trail[] = new bcn_breadcrumb($this->opt['S404_title'], $this->opt['H404_template'], array('404', 'current-item'));
	}
	/**
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for paged pages.
	 */
	function do_paged()
	{
		global $paged, $page;
		//Need to switch between paged and page for archives and singular (posts)
		if($paged > 0)
		{
			$page_number = $paged;
		}
		else
		{
			$page_number = $page;
		}
		//Place the breadcrumb in the trail, uses the bcn_breadcrumb constructor to set the title, prefix, and suffix
		$this->trail[] = new bcn_breadcrumb($page_number, $this->opt['Hpaged_template'], array('paged'));
	}
	/**
	 * Breadcrumb Trail Filling Function
	 * 
	 * This functions fills the breadcrumb trail.
	 */
	function fill()
	{
		global $wpdb, $post, $wp_query, $paged, $page;
		//Check to see if the trail is already populated
		if(count($this->trail) > 0)
		{
			//Exit early since we have breadcrumbs in the trail
			return null;
		}
		//Do any actions if necessary, we past through the current object instance to keep life simple
		do_action('bcn_before_fill', $this);
		//Do specific opperations for the various page types
		//Check if this isn't the first of a multi paged item
		if($this->opt['bpaged_display'] && (is_paged() || is_singular() && $page > 1))
		{
			$this->do_paged();
		}
		//For the front page, as it may also validate as a page, do it first
		if(is_front_page())
		{
			//Must have two seperate branches so that we don't evaluate it as a page
			if($this->opt['bhome_display'])
			{
				$this->do_front_page();
			}
		}
		//For posts
		else if(is_singular())
		{
			//For attachments
			if(is_attachment())
			{
				$this->do_attachment();
			}
			//For all other post types
			else
			{
				$this->do_post();
			}
		}
		//For searches
		else if(is_search())
		{
			$this->do_search();
		}
		//For author pages
		else if(is_author())
		{
			$this->do_author();
		}
		//For archives
		else if(is_archive())
		{
			$type = $wp_query->get_queried_object();
			//For date based archives
			if(is_date())
			{
				$this->do_archive_by_date();
			}
			else if(is_post_type_archive() && !isset($type->taxonomy))
			{
				$this->do_archive_by_post_type();
			}
			//For taxonomy based archives
			else if(is_category() || is_tag() || is_tax())
			{
				$this->do_archive_by_term();
			}
		}
		//For 404 pages
		else if(is_404())
		{
			$this->do_404();
		}
		//We always do the home link last, unless on the frontpage
		if(!is_front_page())
		{
			$this->do_root();
			$this->do_home();
		}
		//Do any actions if necessary, we past through the current object instance to keep life simple
		do_action('bcn_after_fill', $this);
	}
	/**
	 * This function will either set the order of the trail to reverse key 
	 * order, or make sure it is forward key ordered.
	 * 
	 * @param bool $reverse[optional] Whether to reverse the trail or not.
	 */
	function order($reverse = false)
	{
		if($reverse)
		{
			//Since there may be multiple calls our trail may be in a non-standard order
			ksort($this->trail);
		}
		else
		{
			//For normal opperation we must reverse the array by key
			krsort($this->trail);
		}
	}
	/**
	 * This functions outputs or returns the breadcrumb trail in string form.
	 *
	 * @return void Void if Option to print out breadcrumb trail was chosen.
	 * @return string String-Data of breadcrumb trail.
	 * @param bool $return Whether to return data or to echo it.
	 * @param bool $linked[optional] Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse[optional] Whether to reverse the output or not. 
	 */
	function display($return = false, $linked = true, $reverse = false)
	{
		//Set trail order based on reverse flag
		$this->order($reverse);
		//Initilize the string which will hold the assembled trail
		$trail_str = '';
		//The main compiling loop
		foreach($this->trail as $key => $breadcrumb)
		{
			//Must branch if we are reversing the output or not
			if($reverse)
			{
				//Add in the separator only if we are the 2nd or greater element
				if($key > 0)
				{
					$trail_str .= $this->opt['hseparator'];
				}
			}
			else
			{
				//Only show the separator when necessary
				if($key < count($this->trail) - 1)
				{
					$trail_str .= $this->opt['hseparator'];
				}
			}
			//Trim titles, if needed
			if($this->opt['amax_title_length'] > 0)
			{
				//Trim the breadcrumb's title
				$breadcrumb->title_trim($this->opt['amax_title_length']);
			}
			//Place in the breadcrumb's assembled elements
			$trail_str .= $breadcrumb->assemble($linked);
		}
		//Should we return or echo the assembled trail?
		if($return)
		{
			return $trail_str;
		}
		else
		{
			//Helps track issues, please don't remove it
			$credits = "<!-- Breadcrumb NavXT " . $this->version . " -->\n";
			echo $credits . $trail_str;
		}
	}
	/**
	 * This functions outputs or returns the breadcrumb trail in list form.
	 *
	 * @return void Void if Option to print out breadcrumb trail was chosen.
	 * @return string String-Data of breadcrumb trail.
	 * @param bool $return Whether to return data or to echo it.
	 * @param bool $linked[optional] Whether to allow hyperlinks in the trail or not.
	 * @param bool $reverse[optional] Whether to reverse the output or not. 
	 * 
	 * TODO: Can probably write this one in a smarter way now
	 */
	function display_list($return = false, $linked = true, $reverse = false)
	{
		//Set trail order based on reverse flag
		$this->order($reverse);
		//Initilize the string which will hold the assembled trail
		$trail_str = '';
		//The main compiling loop
		foreach($this->trail as $key => $breadcrumb)
		{
			$trail_str .= '<li';
			//On the first run we need to add in a class for the home breadcrumb
			if($trail_str === '<li')
			{
				$trail_str .= ' class="home';
				if($key === 0)
				{
					$trail_str .= ' current_item';
				}
				$trail_str .= '"';
			}
			//If we are on the current item there are some things that must be done
			else if($key === 0)
			{
				//Add in a class for current_item
				$trail_str .= ' class="current_item"';
			}
			//Trim titles, if needed
			if($this->opt['amax_title_length'] > 0)
			{
				//Trim the breadcrumb's title
				$breadcrumb->title_trim($this->opt['amax_title_length']);
			}
			//Place in the breadcrumb's assembled elements
			$trail_str .= '>' . $breadcrumb->assemble($linked);
			$trail_str .= "</li>\n";
		}
		//Should we return or echo the assembled trail?
		if($return)
		{
			return $trail_str;
		}
		else
		{
			//Helps track issues, please don't remove it
			$credits = "<!-- Breadcrumb NavXT " . $this->version . " -->\n";
			echo $credits . $trail_str;
		}
	}
}
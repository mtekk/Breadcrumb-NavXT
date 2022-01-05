<?php
/*  
	Copyright 2007-2022  John Havlik  (email : john.havlik@mtekk.us)

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
//The breadcrumb class
class bcn_breadcrumb
{
	//Our member variables
	const version = '7.0.2';
	//The main text that will be shown
	protected $title;
	//The breadcrumb's template, used durring assembly
	protected $template;
	//The breadcrumb's no anchor template, used durring assembly when there won't be an anchor
	protected $template_no_anchor;
	//Boolean, is this element linked
	protected $linked = false;
	//The link the breadcrumb leads to, null if $linked == false
	protected $url;
	//The corresponding resource ID
	protected $id = null;
	private $_title = null;
	//The type of this breadcrumb
	protected $type;
	const default_template_no_anchor = '<span property="itemListElement" typeof="ListItem"><span property="name" class="%type%">%htitle%</span><meta property="url" content="%link%"><meta property="position" content="%position%"></span>';
	/**
	 * The enhanced default constructor, ends up setting all parameters via the set_ functions
	 *
	 * @param string $title (optional) The title of the breadcrumb
	 * @param string $template (optional) The html template for the breadcrumb
	 * @param string $type (optional) The breadcrumb type
	 * @param string $url (optional) The url the breadcrumb links to
	 * @param bool $linked (optional) Whether or not the breadcrumb uses the linked or unlinked template
	 */
	public function __construct($title = '', $template = '', array $type = array(), $url = '', $id = null, $linked = false)
	{
		//The breadcrumb type
		$this->type = $type;
		//Set the resource id
		$this->set_id($id);
		//Set the title
		$this->set_title($title);
		//Set the default anchorless templates value
		$this->template_no_anchor = bcn_breadcrumb::default_template_no_anchor;
		//If we didn't get a good template, use a default template
		if($template == null)
		{
			$this->set_template(bcn_breadcrumb::get_default_template());
		}
		//If something was passed in template wise, update the appropriate internal template
		else
		{
			if($linked)
			{
				$this->set_template($template);
			}
			else
			{
				$this->template_no_anchor =  $this->run_template_kses(apply_filters('bcn_breadcrumb_template_no_anchor', $template, $this->type, $this->id));
				$this->set_template(bcn_breadcrumb::get_default_template());
			}
		}
		//Always null if unlinked
		$this->set_url($url);
		$this->set_linked($linked);
	}
	/**
	 * Function to return the translated default template
	 *
	 * @return string The default breadcrumb template 
	 */
	static public function get_default_template()
	{
		return sprintf('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="%1$s" href="%%link%%" class="%%type%%" bcn-aria-current><span property="name">%%htitle%%</span></a><meta property="position" content="%%position%%"></span>', esc_attr__('Go to %title%.','breadcrumb-navxt'));
	}
	/**
	 * Function to set the protected title member
	 *
	 * @param string $title The title of the breadcrumb
	 */
	public function set_title($title)
	{
		//Set the title
		$this->title = apply_filters('bcn_breadcrumb_title', $title, $this->type, $this->id);
		$this->_title = $this->title;
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
	 * @param string $url the URL to link to
	 */
	public function set_url($url)
	{
		$url = trim($url);
		$this->url = apply_filters('bcn_breadcrumb_url', $url, $this->type, $this->id);
	}
	/**
	 * Function to get the internal URL variable
	 * 
	 * @return string the URL that the breadcrumb links to
	 */
	public function get_url()
	{
		return $this->url;
	}
	/**
	 * Function to set the internal breadcrumb linked status
	 * 
	 * @param bool $linked whether or not the breadcrumb uses the linked or unlinked template
	 */
	public function set_linked($linked)
	{
		$this->linked = apply_filters('bcn_breadcrumb_linked', $linked, $this->type, $this->id);
	}
	/**
	 * Function to check if this breadcrumb will be linked
	 * 
	 * @return boolean whether or not this breadcrumb is linked
	 */
	public function is_linked()
	{
		return $this->linked;
	}
	/**
	 * A wrapper for wp_kses which handles getting the allowed html
	 * 
	 * @param string $template_str The tempalte string to run through kses
	 * @return string The template string post cleaning
	 */
	protected function run_template_kses($template_str)
	{
		return wp_kses($template_str, apply_filters('bcn_allowed_html', wp_kses_allowed_html('post')));
	}
	/**
	 * Function to set the internal breadcrumb template
	 *
	 * @param string $template the template to use durring assebly
	 */
	public function set_template($template)
	{
		//Assign the breadcrumb template
		$this->template = $this->run_template_kses(apply_filters('bcn_breadcrumb_template', $template, $this->type, $this->id));
	}
	/**
	 * Function to set the internal breadcrumb ID
	 *
	 * @param int $id the id of the resource this breadcrumb represents
	 */
	public function set_id($id)
	{
		$this->id = $id;
	}
	/**
	 * Function to get the internal breadcrumb ID
	 *
	 * @return int the id of the resource this breadcrumb represents
	 */
	public function get_id()
	{
		return $this->id;
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
	 * Return the type array
	 *
	 * @return array The type array
	 */
	public function get_types()
	{
		return $this->type;
	}
	/**
	 * This function will intelligently trim the title to the value passed in through $max_length. This function is deprecated, do not call.
	 *
	 * @param int $max_length of the title.
	 * @deprecated since 5.2.0
	 */
	public function title_trim($max_length)
	{
		_deprecated_function(__FUNCTION__, '5.2.0');
		//To preserve HTML entities, must decode before splitting
		$this->title = html_entity_decode($this->title, ENT_COMPAT, 'UTF-8');
		$title_length = mb_strlen($this->title);
		//Make sure that we are not making it longer with that ellipse
		if($title_length > $max_length && ($title_length + 2) > $max_length)
		{
			//Trim the title
			$this->title = mb_substr($this->title, 0, $max_length - 1);
			//Make sure we can split, but we want to limmit to cutting at max an additional 25%
			if(mb_strpos($this->title, ' ', .75 * $max_length) > 0)
			{
				//Don't split mid word
				while(mb_substr($this->title,-1) != ' ')
				{
					$this->title = mb_substr($this->title, 0, -1);
				}
			}
			//Remove the whitespace at the end and add the hellip
			$this->title = rtrim($this->title) . html_entity_decode('&hellip;', ENT_COMPAT, 'UTF-8');
		}
		//Return to the encoded version of all HTML entities (keep standards complance)
		$this->title = force_balance_tags(htmlentities($this->title, ENT_COMPAT, 'UTF-8'));
	}
	/**
	 * Assembles the parts of the breadcrumb into a html string
	 *
	 * @param bool $linked Allow the output to contain anchors?
	 * @param int $position The position of the breadcrumb in the trail (between 1 and n when there are n breadcrumbs in the trail)
	 * @param bool $is_current_item Whether or not this breadcrumb represents the current item
	 *
	 * @return string The compiled breadcrumb string
	 */
	public function assemble($linked, $position, $is_current_item = false)
	{
		if($is_current_item)
		{
			$aria_current_str = 'aria-current="page"';
		}
		else
		{
			$aria_current_str = '';
		}
		//Build our replacements array
		$replacements = array(
			'%title%' => esc_attr(strip_tags($this->title)),
			'%link%' => esc_url($this->url),
			'%htitle%' => $this->title,
			'%type%' => apply_filters('bcn_breadcrumb_types', $this->type, $this->id),
			'%ftitle%' => esc_attr(strip_tags($this->_title)),
			'%fhtitle%' => $this->_title,
			'%position%' => $position,
			'bcn-aria-current' => $aria_current_str
			);
		//The type may be an array, implode it if that is the case
		if(is_array($replacements['%type%']))
		{
			array_walk($replacements['%type%'], 'sanitize_html_class');
			$replacements['%type%'] = esc_attr(implode(' ', $replacements['%type%']));
		}
		else
		{
			_doing_it_wrong(__CLASS__ . '::' . __FUNCTION__, __('bcn_breadcrumb::type must be an array', 'breadcrumb-navxt'), '6.0.2');	
		}
		$replacements = apply_filters('bcn_template_tags', $replacements, $this->type, $this->id);
		//If we are linked we'll need to use the normal template
		if($this->linked && $linked)
		{
			//Return the assembled breadcrumb string
			return str_replace(array_keys($replacements), $replacements, $this->template);
		}
		//Otherwise we use the no anchor template
		else
		{
			//Return the assembled breadcrumb string
			return str_replace(array_keys($replacements), $replacements, $this->template_no_anchor);
		}
	}
	/**
	 * Assembles the parts of the breadcrumb into a JSON-LD ready object-array
	 *
	 * @param int $position The position of the breadcrumb in the trail (between 1 and n when there are n breadcrumbs in the trail)
	 *
	 * @return array(object) The prepared array object ready to pass into json_encode
	 */
	public function assemble_json_ld($position)
	{
		return (object) apply_filters('bcn_breadcrumb_assembled_json_ld_array', array(
			'@type' => 'ListItem',
			'position' => $position,
			'item' => (object)array(
				'@id' => esc_url($this->url),
				'name' => esc_attr($this->title))
		), $this->type, $this->id);
	}
}

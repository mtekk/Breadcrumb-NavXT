<?php
/*  
	Copyright 2007-2015  John Havlik  (email : john.havlik@mtekk.us)

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
	const version = '5.2.2';
	//The main text that will be shown
	protected $title;
	//The breadcrumb's template, used durring assembly
	protected $template;
	//The breadcrumb's no anchor template, used durring assembly when there won't be an anchor
	protected $template_no_anchor = '%title%';
	//Boolean, is this element linked
	protected $linked = false;
	//The link the breadcrumb leads to, null if $linked == false
	protected $url;
	//The corresponding resource ID
	protected $id = NULL;
	private $_title = NULL;
	//The type of this breadcrumb
	public $type;
	protected $allowed_html = array();
	/**
	 * The enhanced default constructor, ends up setting all parameters via the set_ functions
	 *  
	 * @param string $title (optional) The title of the breadcrumb
	 * @param string $template (optional) The html template for the breadcrumb
	 * @param string $type (optional) The breadcrumb type
	 * @param string $url (optional) The url the breadcrumb links to
	 */
	public function __construct($title = '', $template = '', array $type = array(), $url = NULL, $id = NULL)
	{
		//Filter allowed_html array to allow others to add acceptable tags
		$this->allowed_html = apply_filters('bcn_allowed_html', wp_kses_allowed_html('post'));
		//The breadcrumb type
		$this->type = $type;
		//Set the resource id
		$this->set_id($id);
		//Set the title
		$this->set_title($title);
		//Assign the breadcrumb template, need strict comparison as we only want to enter if we had a blank URL, not NULL URL
		if($template == NULL || $url === '')
		{
			if($url == NULL || $url === '')
			{
				$template = __('<span typeof="v:Breadcrumb"><span property="v:title">%htitle%</span></span>', 'breadcrumb-navxt');
			}
			else
			{
				$template = __('<span typeof="v:Breadcrumb"><a rel="v:url" property="v:title" title="Go to %title%." href="%link%" class="%type%">%htitle%</a></span>', 'breadcrumb-navxt');
			}
		}
		//Loose comparison, evaluates to true if URL is '' or NULL
		if($url == NULL)
		{
				$this->template_no_anchor = wp_kses(apply_filters('bcn_breadcrumb_template_no_anchor', $template, $this->type, $this->id), $this->allowed_html);
		}
		else
		{
				$this->set_template($template);
		}
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
	 * @param string $url the url to link to
	 */
	public function set_url($url)
	{
		$this->url = esc_url(apply_filters('bcn_breadcrumb_url', $url, $this->type, $this->id));
		//Set linked to true if we set a non-null $url
		if($url && $url != '')
		{
			$this->linked = true;
		}
	}
	/**
	 * Function to set the internal breadcrumb template
	 * 
	 * @param string $template the template to use durring assebly
	 */
	public function set_template($template)
	{
		//Assign the breadcrumb template
		$this->template = wp_kses(apply_filters('bcn_breadcrumb_template', $template, $this->type, $this->id), $this->allowed_html);
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
			//Make sure we can split a, but we want to limmit to cutting at max an additional 25%
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
		$this->title = htmlentities($this->title, ENT_COMPAT, 'UTF-8');
	}
	/**
	 * Assembles the parts of the breadcrumb into a html string
	 * 
	 * @param bool $linked (optional) Allow the output to contain anchors?
	 * @return string The compiled breadcrumb string
	 */
	public function assemble($linked = true)
	{
		//Build our replacements array
		$replacements = array(
			'%title%' => esc_attr(strip_tags($this->title)),
			'%link%' => $this->url,
			'%htitle%' => $this->title,
			'%type%' => apply_filters('bcn_breadcrumb_types', $this->type, $this->id),
			'%ftitle%' => esc_attr(strip_tags($this->_title)),
			'%fhtitle%' => $this->_title
			);
		//The type may be an array, implode it if that is the case
		if(is_array($replacements['%type%']))
		{
			$replacements['%type%'] = implode(' ', $replacements['%type%']);
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
}
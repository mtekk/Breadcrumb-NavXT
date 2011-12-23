<?php
/*
Plugin Name: Breadcrumb NavXT Display Supplicant
Plugin URI: http://mtekk.us/code/breadcrumb-navxt/
Description: This adds the bcn_display_nested function and related needed things.
Version: 3.9.20
Author: John Havlik
Author URI: http://mtekk.us/
License: GPL2
TextDomain: breadcrumb_navxt_sup
DomainPath: /languages/

*/
/*  Copyright 2007-2011  John Havlik  (email : mtekkmonkey@gmail.com)

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
class bcn_display_supplicant extends bcn_breadcrumb_trail
{
	function __construct()
	{
		//We're going to make sure we load the parent's constructor
		parent::__construct();
	}
	/**
	 * A supplimentary function for the display_nested function
	 */
	function nested_loop($linked, $tag, $mode)
	{
		//Grab the current breadcrumb from the trail, move the iterator forward one
		if(list($key, $breadcrumb) = each($this->trail))
		{
			//If we are on the current item there are some things that must be done
			if($key === 0)
			{
				$this->current_item($breadcrumb);
			}
			//Trim titles, if needed
			if($this->opt['amax_title_length'] > 0)
			{
				//Trim the breadcrumb's title
				$breadcrumb->title_trim($this->opt['amax_title_length']);
			}
			if($mode === 'rdfa')	
			{
				return sprintf('%1$s<%2$s rel="v:child"><%2$s typeof="v:Breadcrumb">%3$s%4$s</%2$s></%2$s>', $this->opt['hseparator'], $tag, $breadcrumb->assemble($linked), $this->nested_loop($linked, $tag, $mode));
			}
			else
			{
				return sprintf('%1$s<%2$s itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">%3$s%4$s</%2$s>', $this->opt['hseparator'], $tag, $breadcrumb->assemble($linked), $this->nested_loop($linked, $tag, $mode));
			}
		}
		else
		{
			return '';
		}
	}
	/**
	 * Breadcrumb Creation Function
	 * 
	 * This functions outputs or returns the breadcrumb trail in string form.
	 *
	 * @return void Void if Option to print out breadcrumb trail was chosen.
	 * @return string String-Data of breadcrumb trail.
	 * @param bool $return Whether to return data or to echo it.
	 * @param bool $linked[optional] Whether to allow hyperlinks in the trail or not.
	 * @param string $tag[optional] The tag to use for the nesting
	 * @param string $mode[optional] Whether to follow the rdfa or Microdata format
	 * 
	 * TODO: Split this off into a supplementary plugin
	 */
	function display_nested($return = false, $linked = true, $tag = 'span', $mode = 'rdfa')
	{
		//Set trail order based on reverse flag
		$this->order(false);
		//Makesure the iterator is pointing to the first element
		$breadcrumb = reset($this->trail);
		//Trim titles, if needed
		if($this->opt['amax_title_length'] > 0)
		{
			//Trim the breadcrumb's title
			$breadcrumb->title_trim($this->opt['amax_title_length']);
		}
		if($mode === 'rdfa')	
		{
			//Start up the recursive engine
			$trail_str = sprintf('<%1$s typeof="v:Breadcrumb">%2$s %3$s</%1$s>', $tag, $breadcrumb->assemble($linked), $this->nested_loop($linked, $tag, $mode));
		}
		else
		{
			//Start up the recursive engine
			$trail_str = sprintf('%2$s %3$s', $tag, $breadcrumb->assemble($linked), $this->nested_loop($linked, $tag, $mode));
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
/**
 * A wrapper for the internal function in the class
 * 
 * @param bool $return Whether to return data or to echo it.
 * @param bool $linked[optional] Whether to allow hyperlinks in the trail or not.
 * @param string $tag[optional] The tag to use for the nesting
 * @param string $mode[optional] Whether to follow the rdfa or Microdata format
 */
function bcn_display_nested($return = false, $linked = true, $tag = 'span', $mode = 'rdfa')
{
	global $bcn_admin;
	if($bcn_admin !== null)
	{
		return $bcn_admin->display_nested($return, $linked, $tag, $mode);
	}
}
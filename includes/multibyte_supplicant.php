<?php
/*
	A small library that adds in fallbacks for some of the PHP multibyte string
	functions. Mainly inteneded to be used with Breadcrumb NavXT

	Copyright 2009-2020  John Havlik  (email : john.havlik@mtekk.us)

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
if(!function_exists('mb_strlen'))
{
	/**
	 * Fallback for mb_strlen for users without multibyte support
	 * 
	 * @param string $string the string to determine the lenght of
	 * @return int the number of characters in the string
	 */
	function mb_strlen($string)
	{
		return strlen($string);
	}
}
if(!function_exists('mb_strpos'))
{
	/**
	 * Fallback for mb_strpos for users without multibyte support
	 * 
	 * @param string $haystack the string to search within
	 * @param string $needle the string to search for
	 * @return mixed position of the first instances of needle, or false if needle not found
	 */
	function mb_strpos($haystack, $needle, $offset = 0)
	{
		return strpos($haystack, $needle, $offset);
	}
}
if(!function_exists('mb_substr'))
{
	/**
	 * Fallback for mb_substr for users without multibyte support
	 * 
	 * @param string $string the input string
	 * @param int $start the start
	 * @param int length the length of the substring
	 * @return string the substring of specified length
	 */
	function mb_substr($string, $start, $length = 'a')
	{
		//This happens to be the easiest way to preserve the behavior of substr
		if($length = 'a')
		{
			return substr($string, $start);
		}
		else
		{
			return substr($string, $start, $length);
		}
	}
}
if(!function_exists('mb_strtolower'))
{
	/**
	 * Fallback for mb_strtolower for users without multibyte support
	 * 
	 * @param string $str the string to change to lowercase
	 * @param string $encoding the encoding of the string
	 * @return string the lowercase string
	 */
	function mb_strtolower($str, $encoding = 'UTF-8')
	{
		return strtolower($str);
	}
}
//We need this constant to be defined, otherwise things will break
if(!defined('MB_CASE_TITLE'))
{
	define('MB_CASE_TITLE', '1');
}
if(!function_exists('mb_convert_case'))
{
	/**
	 * A very hacky fallback for mb_convert_case for users without multibyte support
	 * 
	 * @param string $str the string to change the case on
	 * @param int $mode the mode of case convert to use
	 * @param string $encoding the encoding of the string
	 * @return string the case converted string
	 */
	function mb_convert_case($str, $mode = MB_CASE_TITLE, $encoding = 'UTF-8')
	{
		//Only implementing MB_CASE_TITLE
		if($mode = MB_CASE_TITLE)
		{
			return ucwords($str);
		}
		return $str;
	}
}
<?php
/*
	Copyright 2015-2019  John Havlik  (email : john.havlik@mtekk.us)

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
/**
 * Breadcrumb NavXT abstract plugin uninstaller class
 * 
 * @author Tom Klingenberg
 */
abstract class mtekk_adminKit_uninstaller
{
	protected $unique_prefix = '';
	protected $plugin_basename = null;
	protected $_uninstall_result = false;
	/**
	 * get plugin path
	 * 
	 * @return string full path to plugin file
	 */
	protected function _get_plugin_path()
	{
		return sprintf('%s/%s', dirname(dirname(__FILE__)), $this->plugin_basename);		
	}

	/**
	 * constructor 
	 * 
	 * @param  array $options class options
	 * 				plugin => 
	 */
	public function __construct()
	{
		$this->_uninstall_result = $this->uninstall();				
	}
	
	/**
	 * Result Getter
	 * 
	 * @return bool wether or not uninstall did run successfull.
	 */
	public function get_result()
	{
		return $this->_uninstall_result;	
	}
	
	public function is_installed()
	{
		return ((get_option($this->unique_prefix . '_options') !== false)
				&& (get_option($this->unique_prefix . '_options_bk') !== false)
				&& (get_option($this->unique_prefix . '_version') !== false)
				&& (get_site_option($this->unique_prefix . '_options') !== false)
				&& (get_site_option($this->unique_prefix . '_options_bk') !== false)
				&& (get_site_option($this->unique_prefix . '_version') !== false));
	}
} /// class bcn_uninstaller_abstract
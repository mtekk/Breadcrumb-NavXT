<?php
/*  
	Copyright 2015  John Havlik  (email : john.havlik@mtekk.us)

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
abstract class mtekk_adminKit_uninstaller {

	/**
	 * plugin base
	 * 
	 * @var string plugin dirname
	 */
	protected $_base = '';
	
	/**
	 * plugin name
	 *
	 * @var string plugin basename (the php-file including the .php suffix)
	 */
	protected $_plugin = '';

	/**
	 * uninstalled flag
	 * 
	 * @var bool uninstall flag, true if uninstall allready run, false on init
	 */
	protected $_uninstalled = false;
	
	/**
	 * uninstall result
	 * 
	 * @var bool wether or not uninstall worked
	 */
	protected $_uninstallResult = null;
	
	/**
	 * get plugin path
	 * 
	 * @return string full path to plugin file
	 */
	protected function _getPluginPath()
	{
		return sprintf('%s/%s/%s', WP_PLUGIN_DIR, $this->_base, $this->_plugin);		
	}

	/**
	 * constructor 
	 * 
	 * @param  array $options class options
	 * 				plugin => 
	 */
	public function __construct(array $options = null)
	{
		/* plugin setter */				
		if (isset($options['plugin']))
		{
			$this->setPlugin($options['plugin']);
		}
		
		/* init */		
		$this->_uninstallResult = $this->uninstall();				
	}
	
	/**
	 * Result Getter
	 * 
	 * @return bool wether or not uninstall did run successfull.
	 */
	public function getResult()
	{
		return $this->_uninstallResult;	
	}
	
	/**
	 * plugin setter
	 * 
	 * @param  string $plugin plugin name as common with wordpress as 'dir/file.php' 
	 * 				          e.g. 'breadcrumb-navxt/breadcrumb_navxt_admin.php'.
	 * @return this 
	 */
	public function setPlugin($plugin)
	{
		/* if plugin contains a base, check and process it. */		
		if (false !== strpos($plugin, '/'))
		{
			// check
			
			$compare = $this->_base . '/';
			
			if (substr($plugin, 0, strlen($compare)) != $compare)
			{
				throw new DomainException(sprintf('Plugin "%s" has the wrong base to fit the one of Uninstaller ("%").', $plugin, $this->_base), 30001);
			}
			
			// process
			
			$plugin = substr($plugin, strlen($compare));
		}
		
		/* set local store */
		
		$this->_plugin = $plugin;
		
		return $this;
	}

} /// class bcn_uninstaller_abstract
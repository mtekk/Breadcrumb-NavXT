<?php
/**
 * Breadcrumb NavXT - uninstall class
 *
 * uninstall class for WordPress Uninstall Plugin API
 * 
 * @see uninstall.php
 *
 * @author Tom Klingenberg
 */

require_once(dirname(__FILE__) . '/includes/class.mtekk_adminkit_uninstaller.php');

/**
 * Breadcrumb NavXT uninstaller class
 * 
 * @author Tom Klingenberg
 */
class bcn_uninstaller extends mtekk_adminKit_uninstaller {

	/**
	 * plugin base
	 * 
	 * @var string plugin dirname
	 */
	protected $_base = 'breadcrumb-navxt';
	
	/**
	 * uninstall breadcrumb navxt admin plugin
	 * 
	 * @return bool
	 */
	private function _uninstallAdmin()
	{	
		//Grab our global breadcrumb_navxt object
		global $breadcrumb_navxt;
		//Load dependencies if applicable
		if(!class_exists('breadcrumb_navxt'))
		{
			require_once($this->_getPluginPath());
		}
		//Initalize $breadcrumb_navxt so we can use it
		$bcn_breadcrumb_trail = new bcn_breadcrumb_trail();
		//Let's make an instance of our object takes care of everything
		$breadcrumb_navxt = new breadcrumb_navxt($bcn_breadcrumb_trail);
		//Uninstall
		$breadcrumb_navxt->uninstall();
	}	
	
	/**
	 * uninstall method
	 * 
	 * @return bool wether or not uninstall did run successfull.
	 */
	public function uninstall()
	{
		if ($this->_uninstalled)
		{
			throw new BadMethodCallException('Uninstall already exectuted. It can be executed only once.', 30101);
		}
		
		// decide what to do
		switch($this->_plugin)
		{
			case 'breadcrumb-navxt.php':
				return $this->_uninstallAdmin();
															
			default:
				throw new BadMethodCallException(sprintf('Invalid Plugin ("%s") in %s::uninstall().', $this->_plugin , get_class($this)), 30102);				
		}
		
		// flag object as uninstalled
				
		$this->_uninstalled = true;		
	}
	
} /// class bcn_uninstaller

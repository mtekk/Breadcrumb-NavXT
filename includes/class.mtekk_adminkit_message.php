<?php
/*  
	Copyright 2017  John Havlik  (email : john.havlik@mtekk.us)

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
class mtekk_adminKit_message
{
	const version = '1.0.0';
	protected $type = '';
	protected $contents = '';
	protected $dismissed = false;
	protected $dismissible = false;
	protected $uid;
	/**
	 * Default constructor function
	 * 
	 * @param string $contents The string to display in the message
	 * @param string $type The message type, 'error', 'warning', 'success', or 'info'
	 * @param bool $dismissible Whether or not the message is dismissable
	 * @param string $uid The message unique ID, only necessary if the message is dismissable
	 */
	public function __construct($contents, $type = 'info', $dismissible = false, $uid = '')
	{
		$this->contents = $contents;
		$this->type = $type;
		$this->dismissible = $dismissible;
		$this->unique_prefix = $unique_prefix;
		$this->uid = $uid;
	}
	public function is_dismissed()
	{
		get_transient($this->uid);
	}
	/**
	 * Dismisses the message, preventing it from being rendered
	 */
	public function dismiss()
	{
		if($this->dismissible)
		{
			$this->dismissed = true;
		}
	}
	/**
	 * Function that prints out the message if not already dismissed
	 */
	public function render()
	{
		//Don't render dismissed messages
		if($this->dismissed)
		{
			//If the message was dismissed, update the transient for 30 days
			set_transient($this->uid, true, 2592000);
			return;
		}
		if($this->dismissible)
		{
			
		}
		else
		{
			printf('<div class="notice notice-%s"><p>%s</p></div>', $this->type, $this->contents);
		}
	}
}
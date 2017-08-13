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
	protected $message_string = '';
	protected $dismissed = false;
	/**
	 * Default constructor function
	 * 
	 * @param string $message_string The string to display in the message
	 * @param string $type The message type
	 */
	public function __construct($message_string, $type = 'updated')
	{
		$this->message_string = $message_string;
		$this->type = $type;
	}
	public function dismiss()
	{
		$this->dismissed = true;
	}
	/**
	 * Function that prints out the message if not already dismissed
	 */
	public function render()
	{
		if(!$this->dismissed)
		{
			printf('<div class="%s fade"><p>%s</p></div>', $this->type, $this->message_string);
		}
	}
}
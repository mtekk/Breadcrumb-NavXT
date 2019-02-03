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
		$uid = sanitize_html_class($uid);
		//If the message is dismissable, the UID better not be null/empty
		if($dismissible === true && $uid === '')
		{
			//Let the user know they're doing it wrong
			_doing_it_wrong(__CLASS__ . '::' . __FUNCTION__, __('$uid must not be null if message is dismissible', 'mtekk_adminKit'), '1.0.0');
			//Treat the message as non-dismissible
			$dismissible = false;
		}
		$this->contents = $contents;
		$this->type = $type;
		$this->dismissible = $dismissible;
		$this->uid = $uid;
		if($this->dismissible)
		{
			$this->dismissed = $this->was_dismissed();
		}
	}
	/**
	 * Attempts to retrieve the dismissal transient for this message
	 * 
	 * @return bool Whether or not the message has been dismissed
	 */
	public function was_dismissed()
	{
		$this->dismissed = get_transient($this->uid);
		return $this->dismissed;
	}
	/**
	 * Dismisses the message, preventing it from being rendered
	 */
	public function dismiss()
	{
		if($this->dismissible && isset($_POST['uid']) && esc_attr($_POST['uid']) === $this->uid)
		{
			check_ajax_referer($this->uid . '_dismiss', 'nonce');
			$this->dismissed = true;
			//If the message was dismissed, update the transient for 30 days
			$result = set_transient($this->uid, $this->dismissed, 2592000);
		}
	}
	/**
	 * Function that prints out the message if not already dismissed
	 */
	public function render()
	{
		if($this->dismissible)
		{
			//Don't render dismissed messages
			if($this->was_dismissed())
			{
				return;
			}
			wp_enqueue_script('mtekk_adminkit_messages');
			printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p><meta property="uid" content="%3$s"><meta property="nonce" content="%4$s"></div>', esc_attr($this->type), $this->contents, esc_attr($this->uid), wp_create_nonce($this->uid . '_dismiss'));
		}
		else
		{
			printf('<div class="notice notice-%1$s"><p>%2$s</p></div>', esc_attr($this->type), $this->contents);
		}
	}
}
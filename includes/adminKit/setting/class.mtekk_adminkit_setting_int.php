<?php
/*
	Copyright 2020-2021  John Havlik  (email : john.havlik@mtekk.us)

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
require_once( __DIR__ . '/../../block_direct_access.php');
//Include setting base class
if(!class_exists('mtekk_adminKit_setting_base'))
{
	require_once( __DIR__ . '/class.mtekk_adminkit_setting_base.php');
}
class mtekk_adminKit_setting_int extends mtekk_adminKit_setting_base
{
	/**
	 * Default constructor function
	 * 
	 * @param string $title The display title of the setting
	 */
	public function __construct(string $name, string $value, string $title, bool $deprecated)
	{
		$this->name = $name;
		$this->value = $value;
		$this->title = $title;
		$this->deprecated = $deprecated;
	}
	/**
	 *
	 */
	public function validate($new_value, $allow_empty = false)
	{
		return (int) $nev_value;
	}
	/**
	 *
	 */
	public function setValue($new_value)
	{
		$this->value = $this->validate($new_value);
	}
}
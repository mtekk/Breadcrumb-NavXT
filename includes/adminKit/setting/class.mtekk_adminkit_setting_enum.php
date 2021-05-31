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
class mtekk_adminKit_setting_enum extends mtekk_adminKit_setting_base
{
	protected $allowed_vals = array();
	/**
	 * Default constructor function
	 * 
	 * @param string $title The display title of the setting
	 */
	public function __construct(string $name, string $value, string $title, bool $deprecated, array $allowed_vals)
	{
		$this->name = $name;
		$this->value = $value;
		$this->title = $title;
		$this->deprecated = $deprecated;
		$this->allowed_values = $allowed_vals;
	}
	/**
	 * Validates the new value against the allowed values for this setting
	 * 
	 * {@inheritDoc}
	 * @see mtekk_adminKit_setting::validate()
	 */
	public function validate($new_value, $allow_empty = false)
	{
		if(in_array($new_value, $allowed_vals))
		{
			return $new_value;
		}
		else
		{
			return $this->value;
		}
	}
	/**
	 *
	 */
	public function setValue($new_value)
	{
		$this->value = $this->validate($new_value);
	}
	/**
	 * Setter for the allowed values array
	 * 
	 * @param array $allowed_vals Array of allowed values
	 */
	public function setAllowedVals(array $allowed_vals)
	{
		$this->allowed_vals = $allowed_vals;
	}
	/**
	 * Getter of the allowed values array
	 * 
	 * @return array Allowed values used in validation of the setting
	 */
	public function getAllowedVals()
	{
		return $this->allowed_vals;
	}
}
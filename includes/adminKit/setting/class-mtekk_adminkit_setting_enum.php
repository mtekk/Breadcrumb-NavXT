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
namespace mtekk\adminKit\setting;
require_once( __DIR__ . '/../../block_direct_access.php');
//Include setting base class
if(!class_exists('\mtekk\adminKit\setting\setting_base'))
{
	require_once( __DIR__ . '/class-mtekk_adminkit_setting_base.php');
}
class setting_enum extends setting_base
{
	protected $allowed_vals = array();
	/**
	 * Default constructor function
	 * 
	 * @param string $title The display title of the setting
	 */
	public function __construct(string $name, string $value, string $title, $allow_empty = false, $deprecated = false, $allowed_vals = array())
	{
		$this->name = $name;
		$this->value = $value;
		$this->title = $title;
		$this->deprecated = $deprecated;
		$this->allow_empty = $allow_empty;
		$this->allowed_vals= $allowed_vals;
	}
	/**
	 * Validates the new value against the allowed values for this setting
	 * 
	 * {@inheritDoc}
	 * @see mtekk_adminKit_setting::validate()
	 */
	public function validate($new_value)
	{
		if(in_array($new_value, $this->allowed_vals))
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
	 * {@inheritDoc}
	 * @see \mtekk\adminKit\setting\setting::get_opt_name()
	 */
	public function get_opt_name()
	{
		return 'E' . $this->get_name();
	}
	/**
	 * Setter for the allowed values array
	 * 
	 * @param array $allowed_vals Array of allowed values
	 */
	public function set_allowed_vals(array $allowed_vals)
	{
		$this->allowed_vals = $allowed_vals;
	}
	/**
	 * Getter of the allowed values array
	 * 
	 * @return array Allowed values used in validation of the setting
	 */
	public function get_allowed_vals()
	{
		return $this->allowed_vals;
	}
}
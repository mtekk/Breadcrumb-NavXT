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
if(!class_exists('\mtekk\adminKit\setting\setting_int'))
{
	require_once( __DIR__ . '/class-mtekk_adminkit_setting_int.php');
}
class setting_absint extends setting_int
{
	/**
	 *
	 */
	public function validate($new_value)
	{
		return absint($new_value);
	}
	/**
	 * 
	 * {@inheritDoc}
	 * @see \mtekk\adminKit\setting\setting::get_opt_name()
	 */
	public function get_opt_name()
	{
		return 'a' . $this->get_name();
	}
}
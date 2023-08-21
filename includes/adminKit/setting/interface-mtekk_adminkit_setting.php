<?php
/*
	Copyright 2020-2023  John Havlik  (email : john.havlik@mtekk.us)

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
interface setting
{
	/**
	 * Validation method
	 * 
	 * @param unknown $new_value new setting value to validate
	 * @return the validated version of the setting (may be old/current value if new value was invalid)
	 */
	public function validate($new_value);
	public function is_deprecated();
	public function set_deprecated($deprecated);
	public function get_value();
	public function set_value($new_value);
	public function get_title();
	public function get_name();
	public function get_allow_empty();
	public function set_allow_empty($allow_empty);
	/**
	 * @return string The 'option' name for this setting, a combination of the type and name
	 */
	public function get_opt_name();
	/**
	 * Update from form values method
	 * 
	 * @param array $input Array of new values from well formatted form POST request
	 */
	public function maybe_update_from_form_input($input, $bool_ignore_missing = false);
	//public function render(); //This is a future item we'll add, maybe
}
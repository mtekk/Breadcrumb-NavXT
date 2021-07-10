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
interface mtekk_adminKit_setting
{
	/**
	 * Validation method
	 * 
	 * @param unknown $new_value new setting value to validate
	 * @param bool $allow_empty Whether or not the new setting value may be empty
	 * @return the validated version of the setting (may be old/current value if new value was invalid)
	 */
	public function validate($new_value, $allow_empty = false);
	public function is_deprecated();
	public function setDeprecated($deprecated);
	public function getValue();
	public function setValue($new_value);
	public function getTitle();
	public function getName();
	/**
	 * Update from form values method
	 * 
	 * @param array $input Array of new values from well formatted form POST request
	 * @param bool $allow_empty Whether or not the new setting value may be empty
	 */
	public function maybeUpdateFromFormInput($input, $allow_empty = false);
	//public function render(); //This is a future item we'll add, maybe
}
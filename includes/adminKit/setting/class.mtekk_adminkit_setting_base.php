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
//Include setting interface
if(!interface_exists('mtekk_adminKit_setting'))
{
	require_once( __DIR__ . '/interface.mtekk_adminkit_setting.php');
}
abstract class mtekk_adminKit_setting_base implements mtekk_adminKit_setting
{
	const version = '1.0.0';
	protected $name = '';
	protected $value = '';
	protected $title = '';
	protected $allow_empty = false;
	protected $deprecated = false;
	public function is_deprecated()
	{
		return $this->deprecated;
	}
	public function setDeprecated($deprecated)
	{
		$this->deprecated = $deprecated;
	}
	public function getValue()
	{
		return $this->value;
	}
	public function setValue($new_value)
	{
		$this->value = $new_value;
	}
	public function getTitle()
	{
		return $this->title;
	}
	public function getName()
	{
		return $this->name;
	}
	public function getAllowEmpty()
	{
		return $this->allow_empty;
	}
	public function setAllowEmpty($allow_empty)
	{
		$this->allow_empty = $allow_empty;
	}
	/**
	 * Basic updateFromFormInput method
	 * 
	 * {@inheritDoc}
	 * @see mtekk_adminKit_setting::updateFromFormInput()
	 */
	public function maybeUpdateFromFormInput($input)
	{
		if(isset($input[$this->name]))
		{
			$this->setValue($this->validate($input[$this->name]));
		}
	}
}
<?php
/*
Plugin Name: Breadcrumb NavXT 5.0 Migration Compatibility Layer DO NOT ACTIVATE
Plugin URI: http://mtekk.us/code/breadcrumb-navxt/
Description: This exists to ease the transition to the new 5.0 plugin layout. Will produce the 'Breadcrumb NavXT was just updated from a pre-5.0 version, please go to your plugins page and activate "Breadcrumb NavXT". Also, deactivate "Breadcrumb NavXT 5.0 Migration Compatibility Layer" to make this message disappear.' message. Do not activate or rely on this, it will not be included in Breadcrumb NavXT 5.2.
Version: 5.1.1
Author: John Havlik
Author URI: http://mtekk.us/
License: GPL2
TextDomain: breadcrumb-navxt
DomainPath: /languages/
*/
/*  Copyright 2007-2014  John Havlik  (email : john.havlik@mtekk.us)

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
require_once(dirname(__FILE__) . '/includes/block_direct_access.php');
//Let the user know they need to activate the regular plugin
function bcn_plugold()
{
	if(current_user_can('activate_plugins'))
	{
		printf('<div class="error"><p>' . __('Breadcrumb NavXT was just updated from a pre-5.0 version, please go to your plugins page and activate "Breadcrumb NavXT". Also, deactivate the "Breadcrumb NavXT 5.0 Migration Compatibility Layer" plugin to make this message disappear.', 'breadcrumb-navxt') . '</p></div>');
	}
}
//If we are in the admin, let's print a warning
if(is_admin())
{
	add_action('admin_notices', 'bcn_plugold');
}
if(!class_exists('bcn_breadcrumb'))
{
	//Keep things working for now
	include_once(dirname(__FILE__) . '/breadcrumb-navxt.php');
}
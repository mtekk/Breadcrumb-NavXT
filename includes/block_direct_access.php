<?php
/*  Copyright 2012-2017  John Havlik  (email : john.havlik@mtekk.us)

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
//If this file is included directly (e.g. WordPress isn't running), return 404
if(!defined('ABSPATH'))
{
	//First catches the Apache users
	header("HTTP/1.0 404 Not Found");
	//This should catch FastCGI users
	header("Status: 404 Not Found");
	die();
}
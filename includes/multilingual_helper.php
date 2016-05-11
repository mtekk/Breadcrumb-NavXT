<?php
/*  
	A small library that adds multilingual plugin support (e.g. polylang).
	Mainly inteneded to be used with Breadcrumb NavXT

	Copyright 2009-2016  Andreas Stefl  (email : stefl.andreas@gmail.com)

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

function bcn_translate_page_id($page_id)
{
	$query = new WP_Query( array(
		'posts_per_page' => -1,
		'post_type'      => 'page',
		'post__in'       => array( $page_id ),
		'orderby'        => 'post__in'
	) );
	if (!$query->have_posts()) return $page_id;
	return $query->post->ID;
}

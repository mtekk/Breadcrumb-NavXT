<?php

/**
 * Common bootstrap code for loading WordPress.
 *
 * The including file needs to properly set the $config_file_path and $is_multisite
 * variables.
 *
 * @package WP_Plugin_Uninstall_Tester
 * @since 0.2.0
 */

require $config_file_path;

unset( $config_file_path );

$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['HTTP_HOST'] = WP_TESTS_DOMAIN;
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

if ( $is_multisite ) {

	define( 'MULTISITE', true );
	define( 'SUBDOMAIN_INSTALL', false );
	define( 'DOMAIN_CURRENT_SITE', WP_TESTS_DOMAIN );
	define( 'PATH_CURRENT_SITE', '/' );
	define( 'SITE_ID_CURRENT_SITE', 1 );
	define( 'BLOG_ID_CURRENT_SITE', 1 );

	$GLOBALS['base'] = '/';
}

unset( $is_multisite );

define( 'WP_USE_THEMES', false );

require ABSPATH . '/wp-settings.php';

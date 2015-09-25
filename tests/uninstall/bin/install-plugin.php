<?php

/**
 * Install a plugin remotely.
 *
 * @package WP_Plugin_Uninstall_Tester
 * @since 0.1.0
 */

$plugin_file      = $argv[1];
$install_function = $argv[2];
$config_file_path = $argv[3];
$is_multisite     = $argv[4];

require dirname( __FILE__ ) . '/bootstrap.php';

require $plugin_file;

add_action( 'activate_' . $plugin_file, $install_function );

do_action( 'activate_' . $plugin_file, false );

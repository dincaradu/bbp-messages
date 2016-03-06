<?php

/*
Plugin Name: bbPress Messages
Plugin URI: http://samelh.com/plugins/
Description: bbPress Messages - User Private Messages with notifications, widgets and media with no BuddyPress needed.
Author: Samuel Elh
Version: 0.1
Author URI: http://samelh.com
*/

if( ! function_exists('bbp_messages') ) {

	function bbp_messages() {

		defined( 'BBPM_URL' )		|| define( 'BBPM_URL', plugin_dir_url(__FILE__) );
		defined( 'BBPM_PATH' )  	|| define( 'BBPM_PATH', plugin_dir_path(__FILE__) );
		defined( 'BBPM_FILE' )		|| define( 'BBPM_FILE', __FILE__ );
		defined( 'BBPM_TABLE' )		|| define( 'BBPM_TABLE', 'bbp_messages' );
		defined( 'BBPM_DIR_NAME' )	|| define( 'BBPM_DIR_NAME', str_replace( '/index.php', '', plugin_basename( __FILE__ ) ) );

		# load the loader class 
		require 'includes/core/loader.php';

	}

}

bbp_messages();
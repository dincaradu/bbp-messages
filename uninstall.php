<?php
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	exit;
}
// If uninstall not called from WordPress, then exit.
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
// Important: Check if the file is the one
// that was registered during the uninstall hook.
if ( basename(__DIR__) . '/index.php' !== WP_UNINSTALL_PLUGIN )  {
	exit;
}
// Check if the $_REQUEST content actually is the plugin name
if ( isset( $_REQUEST['checked'] ) && !in_array( basename(__DIR__) . '/index.php', $_REQUEST['checked'] ) ) {
	exit;
}
if ( !in_array( $_REQUEST['action'], array( 'delete-plugin', 'delete-selected' ) ) ) {
	exit;
}
// Check user roles.
if ( !current_user_can( 'activate_plugins' ) ) {
	exit;
}
// Run an admin referrer check to make sure it goes through authentication
if ( defined('DOING_AJAX') && DOING_AJAX ) {
	check_ajax_referer( 'updates' );
} else {
	check_admin_referer( 'bulk-plugins' );
}

// coming soon.
<?php


class BBP_messages_activate
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {

		$this->init();

	}


	public function init() {

		register_activation_hook( BBPM_FILE, function() {

			global $wpdb;
			$table = $wpdb->prefix . BBPM_TABLE; 
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE IF NOT EXISTS $table (
			  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
			  `PM_ID` bigint(20) NOT NULL,
			  `sender` bigint(20) NOT NULL,
			  `recipient` bigint(20) NOT NULL,
			  `message` LONGTEXT NOT NULL,
			  `date` bigint(20) NOT NULL,
			  `seen` bigint(20),
			  `deleted` varchar(10) DEFAULT '',
			  UNIQUE (`ID`)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

		});

	}

}

BBP_messages_activate::instance();
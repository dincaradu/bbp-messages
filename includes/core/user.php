<?php


class BBP_messages_user
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {


		add_action('wp', function() {

			//$this->update_status();

		});


	}

	public function update_status() {

		global $current_user;

		$meta = get_user_meta( $current_user->ID, '_bbpm_data', TRUE );

		if( '' == $meta ) {
			$meta = '{ "blocked": "", "notify": "", "archives": "", "last_seen": ""  }';
		}

		$ob = json_decode( html_entity_decode($meta), false );
		$_time_int = apply_filters('bbpm_core_update_user_status_time_int', time(), array());

		$object = '{ "blocked": "'.$ob->blocked.'", "notify": "'.$ob->notify.'", "archives": "'.$ob->archives.'", "last_seen": "'.$_time_int.'"  }';
		update_user_meta( $current_user->ID, '_bbpm_data', esc_attr($object) );

	}

}

BBP_messages_user::instance();
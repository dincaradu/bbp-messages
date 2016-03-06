<?php

class BBP_messages_shortcodes
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {

		//

	}

	public function init() {


		add_shortcode('bbpm-unread-count', function( $atts ) {
	
			global $current_user;

			$a = shortcode_atts( array(
				'user' => 0
		    ), $atts );

			$user_id = esc_attr( "{$a['user']}" );

			if( ! get_userdata( $user_id ) )
				$user_id = $current_user->ID;

			if( get_userdata( $user_id ) ) {
				return (int) bbpm_get_counts($user_id)->unreads;
			} else {
				return 0;
			}

		});

		add_shortcode('bbpm-archives-count', function( $atts ) {
	
			return 0; # PRO feature

		});

		add_shortcode('bbpm-messages-count', function( $atts ) {
	
			global $current_user;

			$a = shortcode_atts( array(
				'user' => 0
		    ), $atts );

			$user_id = esc_attr( "{$a['user']}" );

			if( ! get_userdata( $user_id ) )
				$user_id = $current_user->ID;

			if( get_userdata( $user_id ) ) {
				return (int) bbpm_get_counts($user_id)->all;
			} else {
				return 0;
			}

		});

		add_shortcode('bbpm-sent-messages-count', function( $atts ) {
	
			global $current_user;

			$a = shortcode_atts( array(
				'user' => 0
		    ), $atts );

			$user_id = esc_attr( "{$a['user']}" );

			if( ! get_userdata( $user_id ) )
				$user_id = $current_user->ID;

			if( get_userdata( $user_id ) ) {
				return (int) bbpm_get_counts($user_id)->sent;
			} else {
				return 0;
			}

		});

		add_shortcode('bbpm-received-messages-count', function( $atts ) {
	
			global $current_user;

			$a = shortcode_atts( array(
				'user' => 0
		    ), $atts );

			$user_id = esc_attr( "{$a['user']}" );

			if( ! get_userdata( $user_id ) )
				$user_id = $current_user->ID;

			if( get_userdata( $user_id ) ) {
				return (int) bbpm_get_counts($user_id)->received;
			} else {
				return 0;
			}

		});

		add_shortcode('bbpm-contact-link', function( $atts ) {
	
			global $current_user;

			$a = shortcode_atts( array(
				'user' => 0
		    ), $atts );

			$user_id = esc_attr( "{$a['user']}" );

			if( ! get_userdata( $user_id ) && ! is_user_logged_in() )
				return;

			if( is_user_logged_in() ) {

				if( ! get_userdata( $user_id ) || $current_user->ID == $user_id )
					return (string) bbpm_messages_base();

				return (string) bbp_get_user_profile_url( $current_user->ID ) . bbpm_get_bases()->messages . '/' . get_userdata( $user_id )->user_nicename . '/';

			} else {
				return wp_login_url();
			}

		});

		add_shortcode('bbpm-messages-link', function( $atts ) {
	
			$a = shortcode_atts( array(
				'user' => 0
		    ), $atts );

			$user_id = esc_attr( "{$a['user']}" );

			if( ! get_userdata( $user_id ) )
				$user_id = wp_get_current_user()->ID;
			
			if( ! get_userdata( $user_id ) )
				return;

			return bbp_get_user_profile_url( $user_id ) . bbpm_get_bases()->messages . '/';

		});

	}



}

BBP_messages_shortcodes::instance()->init();

// [bbpm-unread-count user=""]
// [bbpm-archives-count user=""]
// [bbpm-contact-link user=""]
// [bbpm-messages-link user=""]
// [bbpm-messages-count user=""]
// [bbpm-sent-messages-count user=""]
// [bbpm-received-messages-count user=""]
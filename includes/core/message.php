<?php


class BBP_messages_message
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {

		//

	}

	public function send( $recipient, $message ) {

		$recipient = (int) $recipient;
		$bail = false;

		if( !isset( $_POST['_bbpm_nonce'] ) || !wp_verify_nonce( $_POST['_bbpm_nonce'], '_bbpm_nonce' ) )
			$bail = false;

		if( ! bbpm_can_contact( $recipient ) )
			$bail = true;

		global $wpdb;
		$table = $wpdb->prefix . BBPM_TABLE;
		global $current_user;

		$message = preg_replace('#(<br */?>\s*)+#i', "\n", $message);
		$message = esc_attr( strip_tags( $message, '' ) );

		if( strlen( preg_replace('/\s+/', '', $message) ) < apply_filters( 'bbpm_min_message_lenght', 2 ) )
			$bail = true;

		$message = apply_filters( 'bbpm_pre_send_message_content', $message, $recipient );

		$pm_id = $this->pm_id( $recipient )->id;

		if( ! $bail ) {

			$_args = array(
				'recipient' => $recipient,
				'PM_ID'		=> $pm_id,
				'message'	=> $message
			);

			do_action('bbpm_pre_send_message', $_args );

			$wpdb->insert( 
				$table, 
				array( 

					'PM_ID'		=> $pm_id,
					'sender'	=> $current_user->ID,
					'recipient'	=> $recipient,
					'message'	=> $message,
					'date'		=> time()
					
				)
			);

		}

		if ( is_numeric( $wpdb->insert_id ) && ! $bail ) :;


			$this->archive( $pm_id, true, false, true ); // unarchiving if archived
			$this->archive( $pm_id, true, $recipient, true ); // unarchiving if archived

			$this->notify( $wpdb->insert_id );

			// pm_id | unarchive | user_id | no_redirect

			wp_redirect( bbpm_get_conversation_permalink( false, $recipient ) );
			exit;


		else :;

			wp_redirect( bbpm_get_conversation_permalink( '?done=err-sending', $recipient )  );
			exit;

		endif;


	}

	public function pm_id( $recipient, $sender = false ) {

		global $wpdb;
		$table = $wpdb->prefix . BBPM_TABLE;
		global $current_user;

		$sender = $sender ? $sender : $current_user->ID;
		$sender = (int) $sender;

		$query = $wpdb->get_results( "SELECT `PM_ID` FROM $table WHERE `sender` = '$sender' AND `recipient` = '$recipient' OR `recipient` = '$sender' AND `sender` = '$recipient' ORDER BY `ID` DESC LIMIT 1" );

		$args = new stdClass();

		if( ! empty( $query[0] ) && ! empty( $query[0]->PM_ID ) ) :;

			$args->id = (int) $query[0]->PM_ID;
			$args->exist = true;

		else:

			$args->id = (int) rand("1000000000","9999999999");
			$args->exist = false;

		endif;

		return $args;



	}

	public function notify( $message_id ) {

		if( ! $this->get_message( $message_id ) )
			return;

		$_m = $this->get_message( $message_id );

		ob_start();
		require bbpm_template_path( 'email-template' );
		$html = ob_get_clean();

		$recipient = $_m->recipient;
		$sender = $_m->sender;

		$replace = array(

			'{site_name}',
			'{site_description}',
			'{site_link}',
			'{sender_name}',
			'{user_name}',
			'{message_link}',
			'{message_id}'

		);

		$replaceWith = array(

			get_bloginfo('name'),
			get_bloginfo('description'),
			home_url(),
			get_userdata($sender)->user_nicename,
			get_userdata($recipient)->user_nicename,
			bbpm_messages_base( get_userdata($sender)->user_nicename . '/', $recipient ),
			$message_id

		);

		$html = preg_replace_callback(
			"(\{message_big_link\}(.*?)\{/message_big_link\})is",
			function($m) {
				return '<a href="{message_link}" style="color: #fff;border-radius: 3px;-webkit-border-radius: 3px;display:block;text-decoration:none;text-align:center;font-weight:normal;background: #B4B9B6;margin: 17px 0px 16px;padding:20px;">' . $m[1] . '</a>';
			},
			bbpm_nl2p( $html )
		);

		$_email = get_userdata( $recipient )->user_email;
		/**
		  * You can overwrite the recipient email address to which we are sending the notification
		  * Example you can set a field where users can add their preferred email address
		  * rather than the one they registered with, and then use it for notifications
		  */
		$_email = apply_filters('bbpm_user_notification_email', $_email, $recipient );
		$_subject = bbpm_settings()->notifications->subject;
		$_subject = str_replace( $replace, $replaceWith, $_subject );
		$_body = str_replace( $replace, $replaceWith, $html );

		/**
		  * You can use this action hook to perform other actions
		  * right before performing the email notifications
		  * for instance if you don't want to notify this recipient,
		  * you would just use 
		  * wp_redirect( bbpm_messages_base(false, get_userdata($recipient)->user_nicename) );
		  * followed by exit;
		  */
		do_action('bbpm_beofre_notify_user', $recipient, $message_id);

		add_filter( 'wp_mail_content_type', array( $this, 'bbpm_set_html_mail_content_type' ) );
		 
		wp_mail( $_email, $_subject, $_body );

		remove_filter( 'wp_mail_content_type', array( $this, 'bbpm_set_html_mail_content_type' ) );
		 
	}

	public function bbpm_set_html_mail_content_type() {
	    return 'text/html';
	}

	public function get_message( $message_id ) {

		global $wpdb;
		$table = $wpdb->prefix . BBPM_TABLE;
		global $current_user;

		$message_id = (int) $message_id;

		$query = $wpdb->get_results( "SELECT * FROM $table WHERE `ID` = '$message_id' LIMIT 1" );
		$args = new stdClass();

		if( ! empty( $query[0] ) && ! empty( $query[0]->PM_ID ) ) :;

			$args->ID = (int) $query[0]->ID;
			$args->PM_ID = (int) $query[0]->PM_ID;
			$args->sender = (int) $query[0]->sender;
			$args->sender_name = ! empty( get_userdata( $args->sender )->user_nicename ) ? get_userdata( $args->sender )->user_nicename : false;
			$args->recipient = (int) $query[0]->recipient;
			$args->recipient_name = ! empty( get_userdata( $args->recipient )->user_nicename ) ? get_userdata( $args->recipient )->user_nicename : false;
			$args->message = (string) $query[0]->message;
			$args->date = (int) $query[0]->date;
			$args->seen = ! is_null( $query[0]->seen ) ? (int) $query[0]->seen : false;
			$args->deleted = ! is_null( $query[0]->deleted ) ? (string) $query[0]->deleted : false;
			$args->contact = $current_user->ID == $args->sender ? $args->recipient : $args->sender;

		else :;

			$args = false;

		endif;

		return $args;


	}

	public function conversations( $return_all = false ) {

		global $wpdb;
		$table = $wpdb->prefix . BBPM_TABLE;
		global $current_user;

		$stmt = "SELECT `PM_ID` FROM $table WHERE ( `sender` = '$current_user->ID' OR `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC";

		if( isset( $_GET['view'] ) ) {
			if( 'unread' == (string) $_GET['view'] ) {
				$stmt = "SELECT `PM_ID` FROM $table WHERE ( `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) AND `seen` IS NULL ORDER BY `ID` DESC";
			}
		}

		if( isset( $_GET['q'] ) ) {

			$q = sanitize_text_field( $_GET['q'] );
			$q = strlen( $q ) > 0 ? $q : false;

			if( $q ) {
				$stmt = "SELECT `PM_ID` FROM $table WHERE ( `sender` = '$current_user->ID' OR `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) AND `message` LIKE '%$q%' ORDER BY `ID` DESC";
			}

		}

		$query = $wpdb->get_results( $stmt );

		$array = array();

		$return_all_index = false;
		if( 'index' == $return_all ) {
			$return_all = false;
			$return_all_index = true;
		}

		if( $return_all ) {
			foreach( $query as $q )
				$array[] = (int) $q->PM_ID;

			return array_filter( array_unique( $array ) );
		}

		if( ! empty( $query ) ) :;

			if( bbpm_is_archives() ) {
				foreach( $query as $q )
					if( bbpm_is_archived( $q->PM_ID ) ) $array[] = (int) $q->PM_ID;
			} else {
				foreach( $query as $q )
					if( ! bbpm_is_archived( $q->PM_ID ) ) $array[] = (int) $q->PM_ID;
			}

		endif;

		if( $return_all_index )
			return array_filter( array_unique( $array ) );

		return $this->paginate( array_filter( array_unique( $array ) ) );

	}

	public function get_conversation( $pm_id, $exists = false, $esc_filters = false ) {

		global $wpdb;
		$table = $wpdb->prefix . BBPM_TABLE;
		global $current_user;

		$pm_id = (int) $pm_id;

		$query = $wpdb->get_results( "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC LIMIT 1" );

		$args = new stdClass();

		if( ! empty( $query[0] ) && ! empty( $query[0]->PM_ID ) ) :;

			if( $exists )
				return true;

			$args->contact = $current_user->ID !== $this->get_message( $query[0]->ID )->sender ? $this->get_message( $query[0]->ID )->sender : $this->get_message( $query[0]->ID )->recipient;

			$args->contact_slug = get_userdata( $args->contact )->user_nicename;

			$args->last_message = $this->get_message( $query[0]->ID );

		else :;

			if( $exists )
				return false;

			$args = false;

		endif;

		if( $esc_filters )
			return $args;
		else
			return apply_filters('bbpm_get_conversation_array', $args);

	}

	public function snippet_classes( $pm_id ) {

		global $current_user;
		$_pm = $this->get_conversation( $pm_id, false, true );

		$classes = 'message-snippet';
		$classes .= ! $_pm->last_message->seen && $current_user->ID == $_pm->last_message->recipient ? ' unread' : ' read';
		$classes .= $current_user->ID == $_pm->last_message->sender ? ' sent' : ' received';

		return $classes;


	}

	public function message_classes( $ID ) {

		global $current_user;

		$classes = 'single-message';
		$classes .= $current_user->ID == $this->get_message( $ID )->sender ? ' mine' : ' their';

		return $classes;

	}

	public function messages( $pm_id, $count = false ) {

		global $wpdb;
		$table = $wpdb->prefix . BBPM_TABLE;
		global $current_user;

		$pm_id = (int) $pm_id;

		$stmt = "SELECT `ID`,`deleted` FROM $table WHERE `PM_ID` = '$pm_id' ORDER BY `ID` ASC";

		if( isset( $_GET['q'] ) ) {

			$q = sanitize_text_field( $_GET['q'] );
			$q = strlen( $q ) > 0 ? $q : false;

			if( $q ) $stmt = "SELECT `ID`,`deleted` FROM $table WHERE `PM_ID` = '$pm_id' AND `message` LIKE '%$q%' ORDER BY `ID` ASC";

		}

		$query = $wpdb->get_results( $stmt );
		$args = new stdClass();

		$array = array();

		if( ! empty( $query ) ) :;

			foreach( $query as $q ) {
				$_array = array_keys(explode(',', $q->deleted), $current_user->ID);
				if( empty( $_array ) )
					$array[] = (int) $q->ID;
			}

		endif;

		//if( ! bbpm_is_search_messages() )
		$this->mark_read();

		if( $count )
			return (int) count( $array );

		do_action( 'bbpm_pre_serve_conversation_messages', $array );

		$array = array_filter( array_unique( array_reverse($array) ) );

		return $this->paginate( $array );

	}

	public function _send() {

		if( isset( $_POST['_bbpm_send'] ) ) :;

			$_message = isset( $_POST['_bbpm_message'] ) ? $_POST['_bbpm_message'] : false;
		
			$_recipient = ! empty( bbpm_get_recipient()->ID ) ? bbpm_get_recipient()->ID : false;

			$this->send( $_recipient, $_message );

		endif;

	}

	public function mark_read( $pm_id = false, $unread = false, $rdr = false ) {

		global $current_user;
		$bail = false;

		if( ! $pm_id )
			$pm_id = bbpm_get_conversation_id();

		$_pm = $this->get_conversation( $pm_id );

		if( empty( $_pm->last_message->ID ) )
			$bail = true;

		$ID = $_pm->last_message->ID;

		if( ! $this->get_message( $ID ) || $current_user->ID == $_pm->last_message->sender  )
			$bail = true;

		if( $_pm->last_message->seen && ! $unread )
			$bail = true;

		$bail = apply_filters('bbpm_marking_unread_bail', $bail, $pm_id, $unread, $rdr );

		if( ! $bail ) :;

			$status = $unread ? null : time();

			global $wpdb;
			$table = $wpdb->prefix . BBPM_TABLE;

			if( $unread ) {

				$wpdb->update( 
					$table, 
					array( 
						'seen' => null
					),
					array(
						'ID' => $ID
					)
				);

			} else {

				$wpdb->update( 
					$table, 
					array( 
						'seen' => time()
					),
					array(
						'PM_ID' => $pm_id,
						'seen' => null
					)
				);

			}

		endif;

		if( $rdr ) {
			$_sub = bbpm_is_archived( $pm_id ) ? bbpm_get_bases()->archives . '/' : '';
			$_sub .= ! $bail ? '?done=unread' : '?done=err-unread';
			wp_redirect( bbpm_messages_base( $_sub ) );
			exit;
		}


	}

	public function block( $args ) {

		global $current_user;

		$_doing = ! empty( $args['doing'] ) && ( 'unblock' == $args['doing'] || 'block' == $args['doing'] ) ? (string) $args['doing'] : false;
		$_target = ! empty( $args['target'] ) && get_userdata( $args['target'] ) ? (int) $args['target'] : false;
		$_redirect = ! empty( $args['redirect'] ) ? (string) $args['redirect'] : false;

		if( ! $_redirect )
			$_redirect = bbpm_get_conversation_permalink( false, $_target );

		$_redirect .= is_numeric( strpos( $_redirect, '?' ) ) ? '&done=' : '?done=';

		$bail = $_doing && $_target;
		$bail = ! $bail;
		$_archives = implode(',', bbpm_get_archives_list( $current_user->ID ));

		$bail = apply_filters('bbpm_current_user_can_block', $bail, $_target);

		if( ! $bail ) :;

			switch( $_doing ) {

				case 'block':
					
					$array = bbpm_get_user_blocked_list();
					$_array = array_keys($array, $_target);

					if( empty( $_array ) ) :;

						array_push( $array, $_target );
						$array = array_filter( array_unique( $array ) );
						$args = array(
							'user'		=> $current_user->ID,
							'blocked'	=> implode( ',', $array ),
							'notify'	=> bbpm_can_notify(),
							'archives'	=> $_archives
						);
						bbpm_update_user_data( $args );

					endif;

					wp_redirect( $_redirect . 'block' );
					exit;

					break;

				case 'unblock':

					$array = bbpm_get_user_blocked_list();
					$_array = array_keys($array, $_target);
					if( ! empty( $_array ) ) :;

						foreach ( array_keys($array, $_target) as $key ) {
						    unset($array[$key]);
						}

						$array = array_filter( array_unique( $array ) );
						$args = array(
							'user'		=> $current_user->ID,
							'blocked'	=> implode( ',', $array ),
							'notify'	=> bbpm_can_notify(),
							'archives'	=> $_archives
						);
						bbpm_update_user_data( $args );

					endif;

					wp_redirect( $_redirect . 'unblock' );
					exit;

					break;

			}

		else :;

			wp_redirect( $_redirect . 'err-block' );
			exit;

		endif;

	}

	public function delete( $ID = false, $pm_id = false ) {

		global $current_user;

		if( ! $ID && isset( $_GET['m'] ) )
			$ID = (int) $_GET['m'];

		if( $ID ) {

			$_message = $this->get_message( $ID );

			if( ! empty( $_message ) ) {

				$is_deleted = $this->_delete( $_message->ID );

				if( true === $is_deleted ) {

					wp_redirect( bbpm_get_conversation_permalink( '?done=delete', $_message->contact ) );
					exit;

				} else {

					wp_redirect( bbpm_get_conversation_permalink( '?done=err-delete', $_message->contact ) );
					exit;

				}

			}

		} else {

			if( $pm_id ) {

				global $wpdb;
				$table = $wpdb->prefix . BBPM_TABLE;
				$pm_id = (int) $pm_id;

				$query = $wpdb->get_results( "SELECT `ID` FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`)" );
				$array = array();

				if( ! empty( $query ) ) {

					foreach( $query as $q ) {
						$this->_delete( $q->ID );
					}

					wp_redirect( bbpm_messages_base( '?done=delete' ) );
					exit;

				} else {

					wp_redirect( bbpm_messages_base( '?done=err-delete' ) );
					exit;

				}

			}

		}

	}

	public function _delete( $message_id ) {

		global $current_user;

		if( $this->get_message( $message_id ) ) {

			$_message = $this->get_message( $message_id );
			$_deleted = explode( ',', $_message->deleted );
			$_array = array_keys($_deleted, $current_user->ID);

			if( empty( $_array ) ) {

				array_push( $_deleted, $current_user->ID );

				global $wpdb;
				$table = $wpdb->prefix . BBPM_TABLE;

				$_deleted_val = implode(',', array_filter( array_unique( $_deleted ) ) );

				$sql = "UPDATE $table SET `deleted` = '$_deleted_val' WHERE `ID` = '$_message->ID' LIMIT 1";
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );

				return true;

			}

		} else {
			return false;
		}

	}

	public function archive( $pm_id = false, $unarchive = false, $user_id = false, $no_rdr = false ) {

		return; # PRO feature

	}

	public function archives( $return_all = false ) {

		return array(); # PRO feature


	}

	public function paginate( $array ) {

		if( empty( $array ) )
			return array();

		$current_page = bbpm_pagination(true)->current_page;
		$res_per_pg = bbpm_pagination(true)->messages_per_page;

		return array_slice( $array, ( $current_page * $res_per_pg ) - $res_per_pg, $res_per_pg );	

	}

	public function counts( $user_id = false, $pm_id = false ) {

		global $current_user;
		global $wpdb;

		$table = $wpdb->prefix . BBPM_TABLE;

		if( ! $user_id )
			$user_id = $current_user->ID;

		# archives

		$_archives = array(); # PRO feature

		# unreads

		$_unreads = array();
		$_conversation_arr = $this->conversations( true );

		if( ! empty( $_conversation_arr ) ) {

			foreach( (array) $this->conversations( true ) as $_pm_id ) {
				if( $this->get_conversation( $_pm_id )->last_message && ! $this->get_conversation( $_pm_id )->last_message->seen ) {
					if( $user_id == $this->get_conversation( $_pm_id )->last_message->recipient ) {
						if( bbpm_is_archives() ) {
							if( bbpm_is_archived($_pm_id) ) $_unreads[] = $_pm_id;
						} else {
							$_unreads[] = $_pm_id;
						}
					}
				}
			}

		}

		$_return = array();
		$_return['archives'] = count( $_archives );
		$_return['unreads'] = count( $_unreads );
		$_return['blocked'] = count( bbpm_get_user_blocked_list( $user_id ) );

		if( $pm_id ) {

			$query = $wpdb->get_results( "SELECT `ID` FROM $table WHERE `PM_ID` = '$pm_id' AND `recipient` = '$user_id' AND NOT FIND_IN_SET('$user_id', `deleted`) AND `seen` IS NULL" );

			$_return['unread_cnt'] = count( $query );

		}

		# messages

		$query = $wpdb->get_results( "SELECT `ID`,`sender`,`recipient` FROM $table WHERE `recipient` = '$user_id' OR `sender` = '$user_id'" );
		
		$_sent = 0;
		$_received = 0;
		$_count_all = 0;

		if( ! empty($query ) ) {

			foreach( array_filter( $query ) as $q ) {

				if( $user_id == $q->sender  )
					$_sent += 1;
				else
					$_received += 1;

				$_count_all += 1;

			}

		}

		$_return['all'] = $_count_all;
		$_return['sent'] = $_sent;
		$_return['received'] = $_received;

		return (object) $_return;

	}

}
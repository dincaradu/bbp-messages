<?php


class BBP_messages_admin_init
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function init() {

		require 'settings.php';

		add_action('admin_menu', function() {

			add_menu_page( 'bbPress Messages', 'bbP Messages', 'manage_options', 'bbpress-messages', array( &$this, 'screen' ) );

		});

		add_action('admin_head', function(){
			?>
				<style type="text/css">a[href*="admin.php?page=bbpress-messages"] .wp-menu-image:before {content: "\f466";}</style>
			<?php
		});

	}

	public function screen() {

		$this->update();
		
		?>

			<div class="wrap">
				
				<?php BBP_messages_admin_settings::instance()->html(); ?>

			</div>
		
		<?php

	}

	public function update() {

		if( isset( $_POST['submit'] ) ) {

			if( !isset( $_POST['_bbpm_settings_nonce'] ) || !wp_verify_nonce( $_POST['_bbpm_settings_nonce'], '_bbpm_settings' ) )
				return;

			$_ob = array();
			
			$_ob['slugs']['messages'] = isset( $_POST['_bbpm_settings_slugs_messages'] ) ? (string) $_POST['_bbpm_settings_slugs_messages'] : $this->settings()->defaults->slugs_messages;
			$_ob['slugs']['archives'] = isset( $_POST['_bbpm_settings_slugs_archives'] ) ? (string) $_POST['_bbpm_settings_slugs_archives'] : $this->settings()->defaults->slugs_archives;
			
			$_ob['pagination']['messages'] = isset( $_POST['_bbpm_settings_pagination_messages'] ) ? (int) $_POST['_bbpm_settings_pagination_messages'] : $this->settings()->defaults->pagination_messages;
			$_ob['pagination']['messages'] = is_numeric( $_ob['pagination']['messages'] ) && (int) $_ob['pagination']['messages'] > 0 ? $_ob['pagination']['messages'] : $this->settings()->defaults->pagination_messages;
			
			$_ob['pagination']['conversations'] = isset( $_POST['_bbpm_settings_pagination_conversations'] ) ? (int) $_POST['_bbpm_settings_pagination_conversations'] : $this->settings()->defaults->pagination_conversations;
			$_ob['pagination']['conversations'] = is_numeric( $_ob['pagination']['conversations'] ) && (int) $_ob['pagination']['conversations'] > 0 ? $_ob['pagination']['conversations'] : $this->settings()->defaults->pagination_conversations;

			$_ob['notifications']['subject'] = isset( $_POST['_bbpm_settings_notifications_subject'] ) ? (string) $_POST['_bbpm_settings_notifications_subject'] : $this->settings()->defaults->message_subject;
			$_ob['notifications']['subject'] = strlen( $_ob['notifications']['subject'] ) > 2 ? $_ob['notifications']['subject'] : $this->settings()->defaults->message_subject;
			$_ob['notifications']['subject'] = esc_attr( $_ob['notifications']['subject'] );

			$_ob['notifications']['body'] = isset( $_POST['_bbpm_settings_notifications_body'] ) ? (string) $_POST['_bbpm_settings_notifications_body'] : $this->settings()->defaults->message_body;
			$_ob['notifications']['body'] = strlen( $_ob['notifications']['body'] ) > 10 ? $_ob['notifications']['body'] : $this->settings()->defaults->message_body;
			$_ob['notifications']['body'] = esc_attr( $_ob['notifications']['body'] );

			$_ob['blocking'] = isset( $_POST['_bbpm_settings_blocking'] ) ? true : false;

			$_ob['help_text'] = isset( $_POST['_bbpm_settings_help_text'] ) ? (string) $_POST['_bbpm_settings_help_text'] : $this->settings()->defaults->help_text;
			$_ob['help_text'] = esc_attr( $_ob['help_text'] );

			$option = '{
				"slugs": { "messages": "' . $_ob['slugs']['messages'] . '", "archives": "' . $_ob['slugs']['archives'] . '" },
				"pagination": { "messages": "' . $_ob['pagination']['messages'] . '", "conversations": "' . $_ob['pagination']['conversations'] . '" },
				"notifications": { "subject": "' . $_ob['notifications']['subject'] . '" },
				"blocking": "' . $_ob['blocking'] . '"
			}';

			if( $_ob['slugs']['messages'] !== bbpm_settings()->slugs->messages || $_ob['slugs']['archives'] !== bbpm_settings()->slugs->archives )
				update_option('_bbpm_needs_flush', '1'); // flush rewrite rules to make the new slugs functional

			update_option( '_bbpm_options', $option );
			update_option( '_bbpm_options_notif_body', $_ob['notifications']['body'] );
			update_option( '_bbpm_options_help_text', $_ob['help_text'] );


		}

	}

	public function settings() {

		$help_text_def = 'Use the links in this top container to mark messages unread, delete entire conversation, or block messages from this recipient.<br/>To delete a single message, there is a delete link in the bottom of message text.<br/>Some media is automatically embedded, for example to embed a YouTube video, simply paste its link in the message text and send it and it will be embedded automatically.<br/>To embed images, you will need to wrap the image source URL in the <code>[img][/img]</code> tags.<br/>For additional information and instructions please contact the site moderators or admins.';

		$message_subject_def = '[{site_name}] {sender_name} has sent you a message';

		$message_body_def = '<p>Hi {user_name}!</p><p>{sender_name} has sent you a message on the forums!</p><p>To view this message, follow this link:</p><p>{message_big_link}View message{/message_big_link}</p><p>If links don\'t work, paste this URL in a new browser tab:<br/>{message_link}</p><p>Thank you.</p>';


		$ob = new stdClass();

		$ob->defaults = new stdClass();
		$ob->defaults->slugs_messages = 'messages';
		$ob->defaults->slugs_archives = 'archives';
		$ob->defaults->pagination_messages = 15;
		$ob->defaults->pagination_conversations = 10;
		$ob->defaults->help_text = $help_text_def;
		$ob->defaults->message_body = $message_body_def;
		$ob->defaults->message_subject = $message_subject_def;

		$_option = get_option('_bbpm_options');
		$_option_message_body = get_option('_bbpm_options_notif_body');
		$_option_help_text = get_option('_bbpm_options_help_text');

		if( '' !== $_option ) {

			$_json = json_decode( $_option, false );

			$ob->slugs = new stdClass();
			$ob->pagination = new stdClass();
			$ob->notifications = new stdClass();
			$ob->blocking = new stdClass();

			if( is_object( $_json ) && $_json > '' ) {

				if( ! empty( $_json->slugs ) ) {

					$ob->slugs->messages = ! empty( $_json->slugs->messages ) ? (string) $_json->slugs->messages : $ob->defaults->slugs_messages;
					$ob->slugs->archives = ! empty( $_json->slugs->archives ) ? (string) $_json->slugs->archives : $ob->defaults->slugs_archives;

				}

				if( ! empty( $_json->pagination ) ) {

					$ob->pagination->messages = ! empty( $_json->pagination->messages ) ? (int) $_json->pagination->messages : $ob->defaults->pagination_messages;
					$ob->pagination->conversations = ! empty( $_json->pagination->conversations ) ? (int) $_json->pagination->conversations : $ob->defaults->pagination_conversations;

				}

				if( ! empty( $_json->notifications ) ) {

					$ob->notifications->subject = ! empty( $_json->notifications->subject ) ? (string) $_json->notifications->subject : $ob->defaults->message_subject;

				}

				if( ! empty( $_json->blocking ) && '1' == (string) $_json->blocking ) {
					$ob->blocking = true;
				} else {
					$ob->blocking = false;					
				}

			}

		}

		if( '' !== $_option_message_body ) {

			$ob->notifications->body = stripslashes( html_entity_decode( $_option_message_body, ENT_QUOTES ) );

		}

		if( '' !== $_option_help_text ) {

			$ob->help_text = stripslashes( html_entity_decode( $_option_help_text, ENT_QUOTES ) );

		}

		if( empty( $ob->slugs->messages ) ) {
			$ob->slugs->messages = $ob->defaults->slugs_messages;
		}

		if( empty( $ob->slugs->archives ) ) {
			$ob->slugs->archives = $ob->defaults->slugs_archives;
		}

		if( empty( $ob->pagination->conversations ) ) {
			$ob->pagination->conversations = $ob->defaults->pagination_conversations;
		}

		if( empty( $ob->pagination->messages ) ) {
			$ob->pagination->messages = $ob->defaults->pagination_messages;
		}

		if( empty( $ob->notifications->subject ) ) {
			$ob->notifications->subject = $ob->defaults->message_subject;
		}

		if( ! is_bool( $ob->blocking ) ) {
			$ob->blocking = true;
		}

		if( empty( $ob->notifications->body ) ) {
			$ob->notifications->body = $ob->defaults->message_body;
		}

		if( empty( $ob->help_text ) ) {
			$ob->help_text = $ob->defaults->help_text;
		}

		return $ob;

	}


}

BBP_messages_admin_init::instance()->init();

# little function to get settings
if( ! function_exists('bbpm_settings') ) {
	function bbpm_settings() {
		return BBP_messages_admin_init::instance()->settings();
	}
}
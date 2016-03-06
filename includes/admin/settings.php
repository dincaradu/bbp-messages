<?php

class BBP_messages_admin_settings
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function html() {
		
		?>
				
			<h2>bbPress Messages &raquo; Settings</h2>

			<form method="post">

				<style type="text/css">table.bbpm th{text-align: left;}</style>

				<table class="bbpm _form-table">
					
					<tr>
						<th><h3>Slugs</h3></th>
					</tr>

					<tr>
						
						<th><h4>Messages</h4></th>

						<td>
							<input type="text" value="<?php echo bbpm_settings()->slugs->messages; ?>" name="_bbpm_settings_slugs_messages" size="60" />
						</td>

					</tr>

					<tr>
						<th><h3>Pagination</h3></th>
					</tr>

					<tr>
						
						<th><h4>Messages per page</h4></th>

						<td>
							<input type="number" name="_bbpm_settings_pagination_messages" value="<?php echo bbpm_settings()->pagination->messages; ?>" />
						</td>

					</tr>

					<tr>
						
						<th><h4>Conversations per page</h4></th>

						<td>
							<input type="number" name="_bbpm_settings_pagination_conversations" value="<?php echo bbpm_settings()->pagination->conversations; ?>" />
						</td>

					</tr>

					<tr>
						<td><h3>Email notification</h3></td>
					</tr>

					<tr>
						<td>
							
							<p><strong>Allowed shortcodes:</strong><br/>
							<code>{site_name}</code> for site name,<br/>
							<code>{site_description}</code> for site description,<br/>
							<code>{site_link}</code> for home link,<br/>
							<code>{sender_name}</code> for message sender name,<br/>
							<code>{user_name}</code> for message recipient (sent-to) name,<br/>
							<code>{message_link}</code> for message link,<br/>
							<code>{message_id}</code> the unique message ID,<br/>
							<code>{message_big_link}Text{/message_big_link}</code> for message big link</p>

						</td>
					</tr>

					<tr>
						
						<th><h4>Email subject</h4></th>

						<td>
							<input type="text" name="_bbpm_settings_notifications_subject" value="<?php echo bbpm_settings()->notifications->subject; ?>" size="60" />
						</td>

					</tr>

					<tr>
						
						<th><h4>Email content</h4></th>

						<td>
							<?php wp_editor( bbpm_settings()->notifications->body, '_bbpm_settings_notifications_body', array('quicktags' => false) ); ?></textarea>
						</td>

					</tr>

					<tr>
						<th><h3>Other settings</h3></th>
					</tr>

					<tr>
						
						<th><h4>User blocking</h4></th>
						<td>
							<label><input type="checkbox" name="_bbpm_settings_blocking" <?php echo checked( bbpm_settings()->blocking ); ?> />Allow user blocking</label>
						</td>

					</tr>

					<tr>
						
						<th><h4>Help text</h4></th>
						<td>
							<?php wp_editor( bbpm_settings()->help_text, '_bbpm_settings_help_text', array('quicktags' => false) ); ?></textarea>
						</td>

					</tr>

					<tr>
						<td>
							<?php wp_nonce_field( '_bbpm_settings', '_bbpm_settings_nonce' ); ?>
							<?php submit_button(); ?>
						</td>
					</tr>

				</table>

			</form>

		<?php

	}

}
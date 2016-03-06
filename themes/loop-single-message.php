<?php

// Prevent direct access
defined('ABSPATH') || exit;

$user_id = bbpm_get_conversation( $pm_id )->contact;

?>

<?php do_action('bbpm_before_snippet', $user_id); ?>

<div class="<?php bbpm_message_snippet_classes( $pm_id ); ?>" id="pm-<?php echo $pm_id; ?>" data-slug="<?php echo bbpm_get_conversation( $pm_id )->contact_slug; ?>/" title="view messages">
	
	<?php do_action('bbpm_before_snippet_content', $user_id); ?>

	<div class="avatar-cont">
		<?php do_action('bbpm_before_snippet_avatar', $user_id); ?>
		<?php echo get_avatar( $user_id, apply_filters('bbpm_message_avatar_size', 50) ); ?>
		<?php do_action('bbpm_after_snippet_avatar', $user_id); ?>
	</div>

	<div class="content-cont">
		
		<?php do_action('bbpm_before_snippet_message_details', $user_id); ?>

		<div class="contact-date">
			
			<span>
				<?php echo get_userdata( $user_id )->user_nicename; ?>
				<?php if( (int) bbpm_get_counts(false, $pm_id)->unread_cnt > 0 && bbpm_get_counts(false, $pm_id)->unread_cnt > 0 ) : ?>
					(<?php echo bbpm_get_counts(false, $pm_id)->unread_cnt; ?>)
				<?php endif; ?>
			</span>
			<span><?php echo bbpm_time_diff( bbpm_get_conversation( $pm_id )->last_message->date, false, ' ago' ); ?></span>

		</div>

		<div class="content-excerpt">

			<span class="bbpm-snippet-author">
				<span><?php echo get_avatar( bbpm_get_conversation( $pm_id )->last_message->sender, apply_filters('bbpm_message_snippet_author_avatar_size', 20) ); ?></span>
				<span><?php echo bbpm_get_conversation( $pm_id )->last_message->sender_name; ?></span>
			</span>

			<?php do_action('bbpm_before_snippet_message_excerpt', $user_id); ?>
			
			<?php echo bbpm_message_snippet_excerpt( bbpm_get_conversation( $pm_id )->last_message->ID ); ?>
			
			<?php do_action('bbpm_after_snippet_message_excerpt', $user_id); ?>

		</div>
	
		<?php do_action('bbpm_after_snippet_message_details', $user_id); ?>

	</div>

	<a href="<?php echo bbpm_messages_base( get_userdata($user_id)->user_nicename . '/' ); ?>" class="read"></a>

	<?php do_action('bbpm_after_snippet_content', $user_id); ?>

</div>


<?php do_action('bbpm_after_snippet', $user_id); ?>
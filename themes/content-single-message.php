<?php

// Prevent direct access
defined('ABSPATH') || exit;

?>


<div class="<?php bbpm_message_classes( $ID ); ?>" id="message-<?php echo $ID; ?>">
			
	<div class="avatar-container">

		<a href="<?php echo bbp_get_user_profile_url( bbpm_get_message( $ID )->sender ); ?>">
			<?php echo get_avatar( bbpm_get_message( $ID )->sender, apply_filters('bbpm_in_message_avatar_size', 44) ); ?>
			<span><?php echo bbpm_get_message( $ID )->sender_name; ?></span>
		</a>

		<?php do_action('bbpm_after_single_message_avatar', $ID ); ?>

	</div>

	<div class="message-content">
		
		<?php do_action('bbpm_before_single_message_content', $ID ); ?>

		<div class="message-content-text">

			<?php echo bbpm_output_message( bbpm_get_message( $ID )->message ); ?>

		</div>
		
		<div class="message-meta">
			
			<span><?php echo bbpm_time_diff( bbpm_get_message( $ID )->date, false, ' ago' ); ?></span>
			&middot;
			<span><a href="<?php echo bbpm_get_conversation_permalink( '?do=delete&m=' . $ID ); ?>" class="delete-message">delete</a></span>

			<?php do_action('bbpm_after_single_message_meta', $ID ); ?>

		</div>

		<?php do_action('bbpm_after_single_message_content', $ID ); ?>

	</div>

</div>
<?php

// Prevent direct access
defined('ABSPATH') || exit;

?>

<?php do_action( 'bbpm_before_conversation_content', bbpm_get_recipient()->ID ); ?>

<div class="single-pm">

	<?php bbpm_breadcrumb(); ?>

	<div class="bbpm-single-top bbpm-top-header">

		<?php do_action( 'bbpm_before_conversation_top_header', bbpm_get_recipient()->ID ); ?>

		<div class="bbpm-single-help"><?php echo bbpm_single_help_text(); ?></div>

		<a href="<?php echo bbp_get_user_profile_url( bbpm_get_recipient()->ID ); ?>" title="View <?php echo bbpm_get_recipient()->user_nicename; ?>'s profile">
			<?php echo get_avatar( bbpm_get_recipient()->ID, apply_filters( 'bbpm_conversation_recipient_avatar_size', 30 ) ); ?>
			<span><?php echo bbpm_get_recipient()->user_nicename; ?></span>
		</a>
		&middot;
		<span class="bbpm-user-status">

			<?php if( bbpm_get_user_last_seen(bbpm_get_recipient()->ID) ) : ?>
				
				<?php echo bbpm_get_user_last_seen( bbpm_get_recipient()->ID ); ?>
			
			<?php else : ?>
				
				<?php echo apply_filters('bbpm_user_never_online_text', 'Not recently active'); ?>

			<?php endif; ?>

		</span>

		<?php do_action( 'bbpm_after_conversation_avatar_top_header', bbpm_get_recipient()->ID ); ?>

		<div class="message-tools">
			
			<?php do_action( 'bbpm_before_conversation_top_header_links' ); ?>

			<?php if ( bbpm_get_conversation( bbpm_get_conversation_id() ) ) : ?>
			
				<?php bbpm_conversation_search_form(); ?>

				<a href="<?php echo bbpm_get_conversation_permalink( '?do=delete' ); ?>">delete</a>
				&middot;

			<?php endif; ?>

			<a href="<?php echo bbpm_get_conversation_permalink(); ?>" id="__refresh">refresh</a>

			<?php do_action( 'bbpm_after_conversation_top_header_links' ); ?>

			<?php if( bbpm_need_pagination() ) : ?>
				<div class="bbpm-pagination"><?php echo bbpm_pagination(); ?></div>
			<?php endif; ?>
	
		</div>

		<?php do_action( 'bbpm_after_conversation_top_header', bbpm_get_recipient()->ID ); ?>

		<span class="bbpm-toggle-help">Help</span>

	</div>

	<div class="bbpm-messages">

		<?php do_action('bbpm_before_messages_list'); ?>

		<?php if( count( bbpm_get_messages() ) > 0 ) : ?>

			<?php foreach( array_reverse( bbpm_get_messages() ) as $ID ) : ?>

				<?php require bbpm_template_path( 'content-single-message' ); ?>

			<?php endforeach; ?>

		<?php else : ?>
	
			<?php do_action( 'bbpm_conversations_no_messages' ); ?>

			<p class="no-messages">
				<?php echo apply_filters( 'bbpm_conversation_no_messages_notice', 'There are no messages to show.' ); ?>
			</p>

		<?php endif; ?>

		<?php do_action('bbpm_after_messages_list'); ?>

	</div>

	<?php bbpm_single_seen_notice(); ?>

	<div class="bbpm-input">
		
		<?php bbpm_conversation_form(); ?>

	</div>

</div>

<?php do_action( 'bbpm_after_conversation_content', bbpm_get_recipient()->ID ); ?>
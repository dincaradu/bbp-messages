<?php

// Prevent direct access
defined('ABSPATH') || exit;

?>

<?php do_action( 'bbpm_before_messages_template' ); ?>

<div class="bbpm-archive">
	
	<?php bbpm_breadcrumb(); ?>

	<?php do_action( 'bbpm_before_messages_top_header' ); ?>

	<div class="bbpm-index-top bbpm-top-header">

		<?php do_action( 'bbpm_before_messages_top_header_content' ); ?>

		<div class="message-tools">
			
			<?php bbpm_conversation_search_form(); ?>

			<?php do_action( 'bbpm_before_messages_top_header_links' ); ?>

			<a href="<?php echo bbpm_messages_base( bbpm_is_archives() ? 'archives/' : '' ); ?>?view=unread">
				unread <?php echo bbpm_get_counts()->unreads > 0 ? '('.bbpm_get_counts()->unreads.')' : ''; ?>
			</a>
			&middot;
			
			<a href="<?php echo bbpm_messages_base(); ?>" id="__refresh">refresh</a>

			<?php do_action( 'bbpm_after_messages_top_header_links' ); ?>

			<?php if( bbpm_need_pagination() ) : ?>
				<div class="bbpm-pagination"><?php echo bbpm_pagination(); ?></div>
			<?php endif; ?>
	
		</div>

		<?php do_action( 'bbpm_after_messages_top_header_content' ); ?>

	</div>

	<?php do_action( 'bbpm_after_messages_top_header' ); ?>

	<?php if( count( bbpm_my_conversations() ) > 0 ) : ?>

		<?php foreach( bbpm_my_conversations() as $pm_id ) : ?>

			<?php require bbpm_template_path( 'loop-single-message' ); ?>

		<?php endforeach; ?>

	<?php else : ?>

		<?php do_action( 'bbpm_messages_no_conversations' ); ?>

		<p class="no-messages">
			<?php echo apply_filters( 'bbpm_no_messages_notice', 'There are no conversations to show.' ); ?>
		</p>

	<?php endif; ?>

</div>

<?php do_action( 'bbpm_after_messages_template' ); ?>
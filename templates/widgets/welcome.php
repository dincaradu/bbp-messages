<?php echo $args['before_widget']; ?>

<?php if ( $title ) : ?>
    
    <?php echo $args['before_title'] . $title . $args['after_title']; ?>

<?php endif; ?>

<ul class="bbpm-widget welcome">

    <?php do_action('bbpm_widgets_welcome_list_items_pre'); ?>

    <li>
        <?php printf(
            __('Welcome, %s!', 'bbp-messages'),
            sprintf(
                '<a href="%s">%s%s</a>',
                bbpm_bbp_get_user_profile_url($current_user->ID),
                get_avatar($current_user->ID, 22),
                $current_user->display_name
            )
        ); ?>
    </li>

    <?php do_action('bbpm_widgets_welcome_before_list_items'); ?>

    <li>
        <a href="<?php echo bbpm_messages_url(null, $current_user->ID); ?>"><?php printf(__('Messages (%d)', 'bbp-messages'), do_shortcode('[bbpm-unread-count]')); ?></a>
    </li>

    <li>
        <a href="<?php echo bbpm_messages_url($bbpm_bases['new'], $current_user->ID); ?>"><?php _e('New message', 'bbp-messages'); ?></a>
    </li>
    
    <li>
        <a href="<?php echo bbpm_bbp_get_user_profile_url($current_user->ID), bbp_get_edit_rewrite_id(), '/'; ?>"><?php _e('Edit profile', 'bbp-messages'); ?></a>
    </li>
    
    <li>
        <a href="<?php echo wp_logout_url(); ?>"><?php _e('Log out', 'bbp-messages'); ?></a>
    </li>

    <?php do_action('bbpm_widgets_welcome_list_items'); ?>

</ul>

<?php echo $args['after_widget']; ?>
<?php echo $args['before_widget']; ?>

<?php if ( $title ) : ?>
    
    <?php echo $args['before_title'] . $title . $args['after_title']; ?>

<?php endif; ?>

<?php if ( $messages ) : ?>
    <ul class="bbpm-widget my-messages">

        <?php foreach ( $messages as $message ) : ?>

            <li>
                <a href="<?php echo bbp_get_user_profile_url($message['sender']); ?>">
                    <?php echo get_avatar($message['sender'], 33); ?>
                    <span><?php echo get_userdata($message['sender'])->display_name; ?></span>
                </a>
                <br/>
                <?php echo apply_filters('bbpm_excerpt', $m->excerpt($message['message'])); ?>
                <a href="<?php echo bbpm_messages_url($message['chat_id'], $current_user->ID); ?>">
                    <em>&mdash; <?php printf(__('%s ago', 'bbp-messages'), bbpm_time_diff($message['date'])); ?></em>
                </a>
            </li>

        <?php endforeach; ?>

    </ul>
<?php else : ?>

    <p><?php _e('No messages were found.', 'bbp-messages'); ?></p>

<?php endif; ?>

<?php echo $args['after_widget']; ?>
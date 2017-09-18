<?php echo $args['before_widget']; ?>

<?php if ( $title ) : ?>
    
    <?php echo $args['before_title'] . $title . $args['after_title']; ?>

<?php endif; ?>

<?php if ( $chats ) : ?>
    <ul class="bbpm-widget my-chats">

        <?php foreach ( $chats as $chat ) : ?>

            <li>
                <a href="<?php echo bbpm_messages_url($chat['chat_id'], $current_user->ID); ?>">
                    <img src="<?php echo $chat['avatar']; ?>" height="44" width="44" alt="<?php echo esc_attr($chat['name']); ?>" />
                    <span><?php echo esc_attr($chat['name']); ?></span>
                </a>

                <?php if ( !empty($chat['date']) ) : ?>
                    <br/><em><?php printf(__('Last activity: %s ago', 'bbp-messages'), bbpm_time_diff($chat['date'])); ?></em>
                <?php endif; ?>
            </li>

        <?php endforeach; ?>

    </ul>
<?php elseif ( $unread ) : ?>

    <p><?php _e('No unread chats were found.', 'bbp-messages'); ?></p>

<?php else : ?>

    <p><?php _e('No chats were found.', 'bbp-messages'); ?></p>

<?php endif; ?>

<?php echo $args['after_widget']; ?>
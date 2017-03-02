<?php global $bbpm_chat, $current_user, $bbpm_recipient;
?>

<div class="bbpm-chat-settings">

    <form action="<?php echo bbpm_messages_url($bbpm_chat->chat_id . '/actions/'); ?>" method="post">

        <h3><?php _e('Chat Settings', BBP_MESSAGES_DOMAIN); ?></h3>

        <p>
            <label><?php _e('Chat name:', BBP_MESSAGES_DOMAIN); ?><br/>
                <input type="text" name="name" value="<?php echo $bbpm_chat->is_custom_name ? $bbpm_chat->name : null; ?>" />
            </label>

            <em><?php _e('Assign a name to this chat', BBP_MESSAGES_DOMAIN); ?></em>
        </p>

        <p>
            <label>
                <input type="checkbox" name="subscribe" <?php checked(bbpm_is_user_subscribed_to_chat($bbpm_chat->chat_id, $current_user->ID)); ?> />
                <?php _e('Get email notifications about new messages in this chat', BBP_MESSAGES_DOMAIN); ?>
            </label>

            <em><?php _e('To unsubscribe from all, you can toggle off the email notifications from your profile edit screen.', BBP_MESSAGES_DOMAIN); ?></em>
        </p>

        <p>
            <a href="javascript:;" id="delete-chat"><?php _e('Delete Chat', BBP_MESSAGES_DOMAIN); ?></a>

            <em><?php _e('Warning: this action is irreversible and you cannot restore your chat messages once deleted.', BBP_MESSAGES_DOMAIN); ?></em>
        </p>

        <p class="submit">
            <input type="hidden" name="action" value="update_settings" />
            <?php wp_nonce_field("single_actions_{$bbpm_chat->chat_id}", 'bbpm_nonce'); ?>
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', BBP_MESSAGES_DOMAIN); ?>" />
            <a href="<?php echo bbpm_messages_url($bbpm_chat->chat_id); ?>"><?php _e('cancel', BBP_MESSAGES_DOMAIN); ?></a>
        </p>

    </form>

    <form data-action="<?php echo bbpm_messages_url($bbpm_chat->chat_id . '/actions/'); ?>" method="post" class="delete-chat-form" data-confirm="<?php esc_attr_e('Are you sure you want to proceed?', BBP_MESSAGES_DOMAIN); ?>" style="display:none">
        <?php wp_nonce_field("single_actions_{$bbpm_chat->chat_id}", 'bbpm_nonce'); ?>
        <?php wp_nonce_field("delete_chat_{$bbpm_chat->chat_id}", 'delete_chat_nonce'); ?>
        <input type="hidden" name="action" value="delete_chat" />
        <input type="submit" value="Delete Chat">
    </form>

</div>
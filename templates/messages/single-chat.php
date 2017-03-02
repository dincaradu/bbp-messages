<?php global $bbpm_inbox_ids, $bbpm_pagination, $bbpm_bases, $bbpm_chat_id, $bbpm_message, $bbpm_recipient, $bbpm_chat;

?>

<div class="bbpm-messages bbpm-items">

    <div class="bbpm-head">
        <span class="bbpm-left">
            <img src="<?php echo esc_url($bbpm_chat->avatar); ?>" alt="<?php esc_attr_e('Chat icon', BBP_MESSAGES_DOMAIN); ?>" height="44" width="44" />

            <h3 class="bbpm-heading"><?php echo esc_attr($bbpm_chat->name); ?></h3>

            <br/>

            <a href="<?php echo bbpm_messages_url(); ?>" class="bbpm-helper" title="<?php _e('Back to chats', BBP_MESSAGES_DOMAIN); ?>">&laquo;</a>

            <a href="<?php echo bbpm_messages_url(sprintf('%s/%s/', $bbpm_chat_id, $bbpm_bases['settings_base'])); ?>" class="bbpm-helper" title="<?php _e('Chat options', BBP_MESSAGES_DOMAIN); ?>"><?php _e('Options', BBP_MESSAGES_DOMAIN); ?></a>
        </span>

        <form method="get" action="<?php echo bbpm_messages_url($bbpm_chat_id); ?>">
            <input type="text" name="search" value="<?php echo esc_attr(bbpm_search_query()); ?>" placeholder="<?php esc_attr_e('Search', BBP_MESSAGES_DOMAIN); ?>" />
        </form>
    </div>

    <div class="bbpm-body">

        <?php if ( bbpm_search_query() ) : ?>
            <p><?php printf(__('Showing search results for "%s":', BBP_MESSAGES_DOMAIN), bbpm_search_query()); ?></p>
        <?php endif; ?>
        
        <form action="<?php echo bbpm_messages_url(sprintf('%s/actions/', $bbpm_chat_id)); ?>" method="post">

            <?php if ( $bbpm_inbox_ids ) : ?>

                <ul class="bbpm-list">
                    
                    <?php foreach ( $bbpm_inbox_ids as $bbpm_message ) : ?>

                        <?php bbpm_load_template('messages/loop-message.php'); ?>

                    <?php endforeach; ?>

                </ul>

                <div class="bbpm-actions-cont">
                    <?php if ( isset($bbpm_chat->can_mark_unread) && $bbpm_chat->can_mark_unread ) : ?>
                        <div class="bbpm-mark-unread">
                            <button name="mark_unread"><?php _e('Mark Unread', BBP_MESSAGES_DOMAIN); ?></button>
                        </div>
                    <?php endif;?>

                    <div class="bbpm-actions">
                        <?php wp_nonce_field("single_actions_{$bbpm_chat_id}", 'bbpm_nonce'); ?>
                        
                        <select name="action">
                            <option value=""><?php _ex('Bulk Actions', 'bulk actions menu', BBP_MESSAGES_DOMAIN); ?></option>
                            <option value="delete"><?php _ex('Delete', 'bulk actions menu', BBP_MESSAGES_DOMAIN); ?></option>
                            <?php do_action('bbpm_messages_bulk_actions'); ?>
                        </select>

                        <input type="submit" name="apply" value="<?php _ex('&check;', 'bulk actions menu', BBP_MESSAGES_DOMAIN); ?>" />
                    </div>
                </div>

                <?php if ( !empty($bbpm_chat->seen) ) : ?>
                    <p class="bbpm-read-receipts">
                        <?php _ex('&check; Seen', 'message read receipts', BBP_MESSAGES_DOMAIN); ?>
                        <?php foreach ( $bbpm_chat->seen as $user ) : ?>
                            <span title="<?php printf(__('Seen by %s', BBP_MESSAGES_DOMAIN), $user->display_name); ?>">
                                <?php echo get_avatar($user->ID, 15); ?>
                            </span>
                        <?php endforeach; ?>
                    </p>
                <?php else : ?>
                    <p>&nbsp;</p>
                <?php endif; ?>

            <?php elseif ( bbpm_search_query() ) : ?>

                <p class="bbpm-no-items"><?php _e('No messages have matched your search query, please try again with a different search term', BBP_MESSAGES_DOMAIN); ?></p>

            <?php else : ?>

                <p class="bbpm-empty-chat"><?php _e('There are no messages to show.', BBP_MESSAGES_DOMAIN); ?></p>

            <?php endif; ?>

        </form>

    </div>

    <div class="bbpm-foot">

        <?php if ( bbpm_can_contact($bbpm_recipient->ID) ) : ?>

            <form method="post" action="<?php echo bbpm_messages_url('send'); ?>">
                <p>
                    <?php echo bbpm_message_field(); ?>
                </p>
                <p>
                    <?php wp_nonce_field('send_message', 'bbpm_nonce'); ?>
                    <input type="hidden" name="chat_id" value="<?php echo $bbpm_chat_id; ?>" />
                    <input type="submit" name="send_message" value="Send" />
                </p>
            </form>

        <?php endif; ?>

        <div class="bbpm-pagi">
            <?php echo paginate_links($bbpm_pagination); ?>
        </div>

    </div>

</div>
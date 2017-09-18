<?php namespace BBP_MESSAGES\Inc\Core;

class Shortcodes
{
    public $shortcodes;

    public function __construct()
    {
        $this->shortcodes = array(
            'bbpm-unread-count' => array(
                'callback' => array($this, 'unreadCount'),
                'attributes' => array(
                    'user_id' => __('Specify which user to get the unread messages count. (Optional, defaults to current user ID if logged-in.)', 'bbp-messages'),
                    'unique' => __('If true, we will count only how many chats have unread messages, instead of total unread messages. (Optional, defaults to false)', 'bbp-messages')
                ),
                'about' => __('Get the unread messages/chats counts for a given user.', 'bbp-messages')
            ),
            'bbpm-contact-link' => array(
                'callback' => array($this, 'contactLink'),
                'attributes' => array(
                    'user_id' => __('Specify which user to return link to chat with. (required)', 'bbp-messages'),
                    'current_user' => __('Specify which for which user to return the contact link. (Optional, defaults to current logged-in user)', 'bbp-messages'),
                ),
                'about' => __('Returns an URL to chat with the user ID specified. If no user is logged-in, it will return a login URL.', 'bbp-messages')
            ),
            'bbpm-messages-link' => array(
                'callback' => array($this, 'messagesLink'),
                'attributes' => array(
                    'user_id' => __('Specify which user to return messages link. (Optional, defaults to current user if logged-in)', 'bbp-messages')
                ),
                'about' => __('Get the messages URL of a given user, where they can browse their messages.', 'bbp-messages')
            ),
            'bbpm-messages-count' => array(
                'callback' => array($this, 'messagesCount'),
                'attributes' => array(
                    'user_id' => __('Specify which user to return messages count. (Optional, defaults to current user if logged-in)', 'bbp-messages'),
                    'contact_or_chat_id' => __('Count total messages in a single chat, specify the chat ID or the user contact.', 'bbp-messages')
                ),
                'about' => __('Counts users total messages for all chats or a single chat', 'bbp-messages')
            ),
            'bbpm-chat-unread-count' => array(
                'callback' => array($this, 'chatUnreadCount'),
                'attributes' => array(
                    'user_id' => __('Specify which user to return unread messages count. (Optional, defaults to current user if logged-in)', 'bbp-messages'),
                    'contact_or_chat_id' => __('Count total unread messages in a single chat, specify the chat ID or the user contact.', 'bbp-messages')
                ),
                'about' => __('Counts users total unread messages for a single chat', 'bbp-messages')
            )
        );
    }

    public function init()
    {
        foreach ( $this->shortcodes as $t=>$s ) {
            add_shortcode($t, $s['callback'], 10, count($s['attributes']));
        }

        if ( is_admin() ) {
            add_filter('bbpm_admin_tabs', array($this, 'adminTab'));
            // bbpm_admin_screen()->registerTab(array(
            //     'shortcodes' => array(
            //         'id' => 'shortcodes',
            //         'name' => __('Shortcodes', 'bbp-messages'),
            //         'content_callback' => array($this, 'adminScreen')
            //     )
            // ))->prepare();
        }
    }

    public function unreadCount($atts) {
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'unique' => 0
        ), $atts);

        extract(array_map('intval', $atts));

        return (int) bbpm_messages()->getChatsUnreadCount($user_id, $unique);
    }
    
    public function contactLink($atts) {
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'current_user' => 0
        ), $atts);

        extract(array_map('intval', $atts));

        if ( !$user_id )
            return;

        if ( !$current_user ) {
            global $current_user;
            $current_user = $current_user->ID;
        }

        global $bbpm_bases;

        if ( $current_user ) {
            return bbpm_messages_url(sprintf(
                '%s/%d',
                $bbpm_bases['with'],
                $user_id
            ), $current_user);
        } else {
            return apply_filters(
                'bbpm_redirect_login_url',
                wp_login_url()
            );
        }
    }
    
    public function messagesLink($atts) {
        $atts = shortcode_atts(array(
            'user_id' => 0,
        ), $atts);

        extract(array_map('intval', $atts));

        if ( !$user_id ) {
            global $current_user;

            if ( !$current_user->ID )
                return;

            $user_id = $current_user->ID;
        }

        return bbpm_messages_url(null, $user_id);
    }
    
    public function messagesCount($atts) {
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'contact_or_chat_id' => null
        ), $atts);

        $atts['user_id'] = (int) $atts['user_id'];
        extract($atts);
        $m = bbpm_messages();

        if ( !$user_id ) {
            $user_id = $m->current_user;
        }

        if ( $contact_or_chat_id ) {
            if ( is_numeric($contact_or_chat_id) && get_userdata($contact_or_chat_id) ) {
                $chat_id = $m->getPrivateSharedChat($user_id, $contact_or_chat_id);
            } else {
                $chat_id = $contact_or_chat_id;
            }
        } else {
            $chat_id = null;
        }

        return (int) $m->getUserTotalMessages($user_id, $chat_id);
    }

    public function chatUnreadCount($atts) {
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'contact_or_chat_id' => null
        ), $atts);

        $atts['user_id'] = (int) $atts['user_id'];
        extract($atts);
        $m = bbpm_messages();

        if ( !$user_id ) {
            $user_id = $m->current_user;
        }

        if ( $contact_or_chat_id ) {
            if ( is_numeric($contact_or_chat_id) && get_userdata($contact_or_chat_id) ) {
                $chat_id = $m->getPrivateSharedChat($user_id, $contact_or_chat_id);
            } else {
                $chat_id = $contact_or_chat_id;
            }
        } else {
            $chat_id = null;
        }

        if ( !$chat_id )
            return;

        return (int) $m->getChatUnreadCount($chat_id, $user_id);
    }

    public function adminTab($tabs)
    {
        return array_merge((array) $tabs, array(
            'shortcodes' => array(
                'id' => 'shortcodes',
                'name' => __('Shortcodes', 'bbp-messages'),
                'content_callback' => array($this, 'adminScreen')
            )
        ));
    }

    public function adminScreen()
    {
        ?>
        <table class="form-table widefat striped">
            <thead>
                <tr>
                    <th style="padding-left:10px"><?php _ex('Shortcode', 'shortcodes admin', 'bbp-messages'); ?></th>
                    <th style="padding-left:10px"><?php _ex('Usage', 'shortcodes admin', 'bbp-messages'); ?></th>
                    <th style="padding-left:10px"><?php _ex('Attributes', 'shortcodes admin', 'bbp-messages'); ?></th>
                </tr>
            </thead>

            <?php foreach ( $this->shortcodes as $tag=>$data ) : ?>

                <tr>
                    <td style="vertical-align: top;">
                        <code><?php echo esc_attr("[$tag]"); ?></code>
                    </td>

                    <td style="vertical-align: top;">
                        <?php if ( isset($data['about']) && $data['about'] ) : ?>
                            <span><?php echo esc_attr($data['about']); ?></span>
                        <?php else : ?>
                            <?php _e('NULL', 'bbp-messages'); ?>
                        <?php endif; ?>
                    </td>

                    <td style="vertical-align: top;">
                        <?php if ( isset($data['attributes']) && $data['attributes'] ) : ?>
                            <?php foreach ( $data['attributes'] as $param=>$use ) : ?>
                                <li>
                                    <code><?php echo esc_attr($param); ?></code>:
                                    <span><?php echo esc_attr($use); ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php _e('NULL', 'bbp-messages'); ?>
                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>

            <tfoot>
                <tr>
                    <th style="padding-left:10px"><?php _ex('Shortcode', 'shortcodes admin', 'bbp-messages'); ?></th>
                    <th style="padding-left:10px"><?php _ex('Usage', 'shortcodes admin', 'bbp-messages'); ?></th>
                    <th style="padding-left:10px"><?php _ex('Attributes', 'shortcodes admin', 'bbp-messages'); ?></th>
                </tr>
            </tfoot>
        </table>

        <p><?php _e('See <a href="https://codex.wordpress.org/Shortcode">Shortcode</a> documentation if you need help with shortcodes.', 'bbp-messages'); ?></p>

        <?php
    }
}
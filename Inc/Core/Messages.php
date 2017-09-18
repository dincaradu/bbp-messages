<?php namespace BBP_MESSAGES\Inc\Core;

class Messages extends \WP_Messages
{
    public $table = 'bbp_messages_2p0';
    public $text_domain = 'bbp-messages';
    public $settings;
    public $transient_expiration;

    public function __construct()
    {
        parent::__construct();

        if ( !isset($this->autoload_meta['chat']) ) {
            $this->autoload_meta['chat'] = array();
        }

        $this->autoload_meta['chat'] = array_merge(
            $this->autoload_meta['chat'], array('name', 'unsubscribe')
        );

        $this->transient_expiration = apply_filters('bbpm_cache_transients_expiration', WEEK_IN_SECONDS*2);
        $opt = bbpm_options();
        
        if ( $opt['older_delete_days'] && intval($opt['older_delete_days']) ) {
            $this->older_messages_cleanup_interval = ($opt['older_delete_days'] * DAY_IN_SECONDS);
        }   
    }

    public function filterSet($v, $i=null)
    {
        switch ( $i ) {
            case 'paginate_links_args':
                if ( isset($v['base']) && !isset($v['omit_bbpm_custom_base']) ) {
                    global $wp_rewrite;

                    if ( $wp_rewrite && $wp_rewrite instanceof \WP_Rewrite )
                        $v['base'] = str_replace( 99999, '%#%', esc_url( get_pagenum_link(99999) ) );
                }
                break;
        }

        return $v;
    }

    public function prepareChat($chat)
    {
        if ( is_numeric($chat) || is_string($chat) ) {
            $chat = array('chat_id' => $chat);
        }

        $chat['recipients'] = $this->getChatRecipients($chat['chat_id']);
        $chat['avatar'] = $this->getChatAvatar($chat);
        $name = $this->getChatName($chat);
        $chat['name'] = $name['name'];
        $chat['is_custom_name'] = $name['is_custom_name'];
        $chat['sender_name'] = isset($chat['sender']) ? get_userdata($chat['sender'])->display_name : null;
        $chat['excerpt'] = isset($chat['message']) ? $this->excerpt($chat['message']) : null;
        $chat['classes'] = array();
        $chat['unread_count'] = $this->getChatUnreadCount($chat['chat_id']);

        if ( isset($chat['sender']) && $this->isUnread($chat['chat_id']) && $this->current_user !== $chat['sender'] ) {
            $chat['classes'][] = 'unread';
        }

        $deletes = (array) $this->get_chat_meta($chat['chat_id'], 'delete_scheduled', null);

        if ( in_array($this->current_user, $deletes) ) {
            $chat['classes'][] = 'pending-delete';
        }

        return $chat;
    }

    public function getChatAvatar($chat)
    {
        $avatar = null;

        if ( isset( $chat['recipients'] ) && count($chat['recipients']) < 3 ) {
            foreach ( $chat['recipients'] as $user_id ) {
                if ( $user_id !== $this->current_user ) {
                    $avatar = get_avatar_url($user_id);
                }
            }
        }

        return apply_filters('BBP_MESSAGES_getChatAvatar', $avatar, $chat);
    }

    public function getChatName($chat, $current_user=null)
    {
        if ( !$current_user ) {
            $current_user = $this->current_user;
        }

        $name = $this->get_chat_meta($chat['chat_id'], 'name', null);

        if ( $name && trim($name) ) {
            $name = esc_attr(wp_unslash($name));
            $is_custom_name = true;
        } else if ( isset( $chat['recipients'] ) && count($chat['recipients']) < 3 ) {
            foreach ( $chat['recipients'] as $user_id ) {
                if ( $user_id !== $current_user ) {
                    $name = get_userdata($user_id)->display_name;
                }
            }
        } else {
            $name = null;
        }

        return apply_filters('BBP_MESSAGES_getChatName', array(
            'name' => $name,
            'is_custom_name' => isset($is_custom_name)
        ), $chat, $name);
    }

    public function excerpt($raw)
    {
        $length = apply_filters('BBP_MESSAGES_chat_excerpt_length', 150);

        if ( strlen($raw) > $length ) {
            $excerpt = substr($raw, 0, $length) . ' ...';
        } else {
            $excerpt = $raw;
        }

        return apply_filters('BBP_MESSAGES_chat_excerpt', $excerpt, $raw, $length);
    }

    public function canContact($user_id, $current_user=null)
    {
        if ( !$current_user && $this->current_user ) {
            $current_user = $this->current_user;
        }

        $can = true;

        // a current user prop is required
        if ( !$current_user ) {
            $can = false;
        }

        // first, check if user is the same as their contact
        else if ( $current_user == $user_id ) {
            $can = false;
        }

        return apply_filters('bbpm_can_contact', $can, $user_id, $current_user);
    }

    public function prepareSearchTerm($wpdbprepare=null, $term=null)
    {
        if ( !$term && $this->search_term ) {
            $term = $this->search_term;
        }

        if ( $term  ) {
            $term = str_replace('\\', (
                $wpdbprepare ? '\\\\' : '\\\\\\\\'
            ), wp_slash($term));
        }

        return $term;
    }

    public function newMessageNotifySend($id, $chat_id, $sender, $recipients)
    {
        $m = (object) $this->getMessage($id);

        if ( empty($m->id) || empty($m->message) )
            return;

        global $bbpm_bases;

        $blogname = get_bloginfo('name');
        $sender = get_userdata($m->sender);
        $excerpt = $this->excerpt($m->message);
        $custom_name = $this->get_chat_meta($chat_id, 'name', null);
        $settings = !empty($bbpm_bases['settings_base']) ? $bbpm_bases['settings_base'] : 'settings';

        if ( $custom_name && trim($custom_name) ) {
            $custom_name = esc_attr($custom_name);
        }

        $opt = (object) bbpm_options();

        $title = sprintf(
            $opt->email_subject,
            $blogname,
            $custom_name ? $custom_name : $sender->display_name
        );
        $body = $opt->email_body;
        $headers = $opt->html_emails ? array('Content-Type: text/html; charset=' . get_option('blog_charset')) : '';

        if ( $opt->html_emails ) {
            $body = html_entity_decode($body);
        }

        $title = apply_filters('bbpm_notification_title', $title, func_get_args());
        $body = apply_filters('bbpm_notification_body', $body, func_get_args());
        $headers = apply_filters('bbpm_notification_headers', $headers, func_get_args());

        foreach ( $recipients as $user_id ) {
            $pref = bbpm_can_notify($user_id, $chat_id);

            if ( !$pref )
                continue;

            $deletes = (array) $this->get_chat_meta($chat_id, 'delete_scheduled', null);

            if ( in_array($user_id, $deletes) )
                continue;

            $user = get_userdata($user_id);

            if ( empty($user->ID) )
                continue;

            $_body = sprintf(
                $body,
                $user->display_name,
                $sender->display_name,
                $blogname,
                $excerpt,
                bbpm_messages_url($chat_id, $user->ID),
                bbpm_messages_url(sprintf('%s/%s/', $chat_id, $settings), $user->ID)
            );

            $user->user_email = apply_filters('bbpm_notification_user_email', $user->user_email, $user);

            bbpm_mail($user->user_email, $title, $_body, $headers);
        }
    }

    public function setCache($key, $data, $group=null)
    {
        $key = "WP_Messages_{$this->table}_{$key}";
        $opt = bbpm_options();

        switch ( $opt['cache_ctx'] ) {
            case 'transients':
                return Transients::set($data, $this->transient_expiration, $key);
                break;

            case 'none':
                return;
                break;

            default:
                return wp_cache_set($key, $data, $group);
                break;
        }
    }

    public function deleteCache($key, $group=null)
    {
        $key = "WP_Messages_{$this->table}_{$key}";
        $opt = bbpm_options();

        switch ( $opt['cache_ctx'] ) {
            case 'transients':
                return Transients::delete($key);
                break;

            case 'none':
                return false;
                break;

            default:
                return wp_cache_delete($key, $group);
                break;
        }
    }

    public function getCache($key, $group=null)
    {
        $key = "WP_Messages_{$this->table}_{$key}";
        $opt = bbpm_options();

        switch ( $opt['cache_ctx'] ) {
            case 'transients':
                return Transients::get($key);
                break;

            case 'none':
                return false;
                break;

            default:
                return wp_cache_get($key, $group);
                break;
        }
    }

    public function getUserContacts($user_id=null, $limit=10, $map='get_userdata')
    {
        if ( !$user_id ) {
            $user_id = $this->current_user;
        }
        
        if ( !$user_id )
            return array();

        if ( !intval($limit) ) {
            $limit = 10;
        }

        $chats = $this->getUserChatsRaw($user_id);

        $contacts = array();

        if ( $chats ) {
            foreach ( $chats as $chat_id ) {
                $found = $this->arrayWithout($this->getChatRecipients($chat_id), $user_id);

                if ( $found ) {
                    foreach ( $found as $uid ) {
                        if ( !in_array($uid, $contacts) ) {
                            $contacts[] = $uid;
                        }

                        if ( count($contacts) >= $limit )
                            break;
                    }
                }

                if ( count($contacts) >= $limit )
                    break;
            }
        }

        if ( $contacts && $map && is_callable($map) ) {
            $contacts = array_map($map, $contacts);
        }

        return $contacts;
    }

    public function getUserChatsRaw($user_id)
    {
        return apply_filters('bbpm_user_chats_raw', parent::getUserChatsRaw($user_id));
    }
}
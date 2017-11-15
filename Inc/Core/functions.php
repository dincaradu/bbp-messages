<?php

if ( !function_exists('bbp_messages') ) :
function bbp_messages() {
    global $bbpm_loader;
    $ins = $bbpm_loader;

    return apply_filters('bbp_messages', $ins);
}
endif;

if ( !function_exists('bbpm_messages') ) :
function bbpm_messages() {
    $bbpm = bbp_messages();

    return apply_filters('bbpm_messages', isset($bbpm->messages) ? $bbpm->messages : null);
}
endif;

function bbpm_is_messages() {
    return apply_filters('bbpm_is_messages', 'messages' === get_query_var('BPT_tab'));
}

function bbpm_is_single_chat() {
    return apply_filters('bbpm_is_single_chat', (bool) get_query_var('bbpm_chat'));    
}

function bbpm_is_chat_settings() {
    return apply_filters('bbpm_is_chat_settings', bbpm_is_single_chat() && get_query_var('bbpm_settings'));    
}

function bbpm_is_chats() {
    return apply_filters('bbpm_is_chats', (bool) get_query_var('bbpm_chats'));
}

function bbpm_messages_url($after='', $user_id=0) {
    $url = bbpm_bbp_get_user_profile_url($user_id);
    $url .= bbpm_bases('messages_base') . '/';

    if ( $after ) {
        $url = user_trailingslashit($url . $after);
    }

    return apply_filters('bbpm_messages_url', $url, $after, $user_id);
}

function bbpm_bbp_get_user_profile_url( $user_id = 0, $user_nicename = '' ) {
    global $wp_rewrite;

    // Use displayed user ID if there is one, and one isn't requested
    $user_id = bbp_get_user_id( $user_id );
    if ( empty( $user_id ) )
        return false;

    // Allow early overriding of the profile URL to cut down on processing
    $early_profile_url = apply_filters( 'bbpm_bbp_pre_get_user_profile_url', (int) $user_id );
    if ( is_string( $early_profile_url ) )
        return $early_profile_url;

    // Pretty permalinks
    if ( $wp_rewrite->using_permalinks() ) {
        $url = $wp_rewrite->root . bbp_get_user_slug() . '/%' . bbp_get_user_rewrite_id() . '%';

        // Get username if not passed
        if ( empty( $user_nicename ) ) {
            $user_nicename = bbp_get_user_nicename( $user_id );
        }

        $url = str_replace( '%' . bbp_get_user_rewrite_id() . '%', $user_nicename, $url );
        $url = home_url( user_trailingslashit( $url ) );

    // Unpretty permalinks
    } else {
        $url = add_query_arg( array( bbp_get_user_rewrite_id() => $user_id ), home_url( '/' ) );
    }

    return apply_filters( 'bbpm_bbp_get_user_profile_url', $url, $user_id, $user_nicename );
}

function bbpm_search_query() {
    $query = isset($_REQUEST['search']) ? $_REQUEST['search'] : null;

    return apply_filters('bbpm_search_query', $query);
}

function bbpm_load_template($file, $args=null) {
    return bbp_messages()->loadTemplate($file, $args);
}

function bbpm_get_current_page() {
    return apply_filters('bbpm_get_current_page', (int) get_query_var('bbpm_page', 1));        
}

function bbpm_profile_linkit($user_id, $html='', $atts='') {
    return apply_filters('bbpm_profile_linkit', sprintf(
        '<a href="%s"%s>%s</a>',
        bbpm_bbp_get_user_profile_url($user_id),
        trim($atts) ? " {$atts}" : '',
        $html
    ), $user_id, $html, $atts);    
}

function bbpm_time_diff( $target, $before='', $after='' ) {
    if( !isset( $target ) )
        return false;
    $target = new DateTime( date("Y-m-d H:i:s", $target) );
    $now = new DateTime( date("Y-m-d H:i:s", time()) );

    $delta = $now->diff($target);
    $quantities = array(
        'year' => $delta->y,
        'month' => $delta->m,
        'day' => $delta->d,
        'hour' => $delta->h,
        'minute' => $delta->i,
        'second' => $delta->s
    );
    $str = '';
    foreach($quantities as $unit => $value) {
        if($value == 0)
            continue;
        $unit = bbpm_time_diff_i18n( $unit . ( $value != 1 ? 's' : '' ) );
        $str .= $value . ' ' . $unit;
        $str .=  ', ';
        break;
    }
    $str = $str == '' ? _x('a moment', 'time quantities', 'bbp-messages') : substr($str, 0, -2);

    if( $before ) $before .= ' ';
    if( $after ) $after = ' ' . $after;

    $str = $before . $str .  $after;

    return apply_filters('bbpm_time_diff', $str, $target, $before, $after);
}

function bbpm_time_diff_i18n($unit) {
    $units = array(
        'year' => _x('year', 'time quantities', 'bbp-messages'),
        'month' => _x('month', 'time quantities', 'bbp-messages'),
        'day' => _x('day', 'time quantities', 'bbp-messages'),
        'hour' => _x('hour', 'time quantities', 'bbp-messages'),
        'minute' => _x('minute', 'time quantities', 'bbp-messages'),
        'second' => _x('second', 'time quantities', 'bbp-messages'),
        'years' => _x('years', 'time quantities', 'bbp-messages'),
        'months' => _x('months', 'time quantities', 'bbp-messages'),
        'days' => _x('days', 'time quantities', 'bbp-messages'),
        'hours' => _x('hours', 'time quantities', 'bbp-messages'),
        'minutes' => _x('minutes', 'time quantities', 'bbp-messages'),
        'seconds' => _x('seconds', 'time quantities', 'bbp-messages'),
    );

    return isset($units[$unit]) ? $units[$unit] : $unit;
}

function bbpm_date($time=null) {
    return apply_filters('bbpm_date', date('Y-m-d H:i:s', $time?$time:time()), $time);
}

function bbpm_errors( $group='default' ) {
    global $bbpm_errors;

    if ( !isset($bbpm_errors) || !($bbpm_errors instanceof \WP_Error) ) {
        $bbpm_errors = new \WP_Error;
    }

    if ( !trim($group) )
        $group = 'group';

    if ( !isset( $bbpm_errors->$group ) ) {
        $bbpm_errors->$group = new \WP_Error;
    }

    return $bbpm_errors->$group;
}

function bbpm_add_error( $code='', $message='', $data='error', $group='default' ) {
    $bbpm_errors = bbpm_errors($group);

    return $bbpm_errors->add( $code, $message, $data );
}

function bbpm_remove_error( $code='', $group='default' ) {
    $bbpm_errors = bbpm_errors($group);

    return $bbpm_errors->remove( $code );
}

function bbpm_has_errors( $code='', $group='default' ) {
    $bbpm_errors = bbpm_errors($group);

    $codes = $bbpm_errors->get_error_codes();

    if ( $code ) {
        $has_errors = $codes && in_array($code, $codes);
    } else {
        $has_errors = (bool) $codes;
    }

    return apply_filters( 'bbpm_has_errors', $has_errors, $code );
}

function bbpm_template_errors($exclude=array(), $group='default') {
    if ( !bbpm_has_errors() )
        return;

    $bbpm_errors = bbpm_errors($group);

    $codes = array_filter($bbpm_errors->get_error_codes(), 'trim');

    if ( $exclude && is_array($exclude) ) :
    foreach ( $codes as $i=>$code ) {
        if ( in_array($code, $exclude) ) {
            unset( $codes[$i] );
        }
    }
    endif;

    ?>

    <ul class="bbpm-errors">
    <?php foreach ( $codes as $code ) : ?>
        <?php $errors = array_unique($bbpm_errors->get_error_messages( $code ));?>
        <?php if ( !$errors ) continue; ?>
        <?php foreach ( $errors as $error ) : ?>
            <li class="<?php echo esc_attr($bbpm_errors->get_error_data($code)); ?>">
                <span><?php echo apply_filters( 'bbpm_error_display', esc_attr($error) ); ?></span>
            </li>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </ul>

    <?php
}

function bbpm_print_error( $code, $group='default' ) {
    if ( !bbpm_has_errors($code) )
        return;

    $bbpm_errors = bbpm_errors($group);
    $errors = $bbpm_errors->get_error_messages( $code );
    $errors = array_unique($errors);

    if ( $errors ) {
        foreach ( $errors as $error ) {
            printf(
                '<span class="inline-error %s">%s</span>',
                esc_attr($bbpm_errors->get_error_data($code)),
                apply_filters( 'bbpm_error_display', esc_attr($error) )
            );
        }
    }
}

if ( !function_exists('bbpm_old') ) :
function bbpm_old( $name, $return=null, $default=null, $method='request' ) {

    switch ( strtolower($method) ) {
        case 'get':
            $data = $_GET;
            break;

        case 'post':
            $data = $_POST;
            break;

        default:
            $data = $_REQUEST;
            break;
    }

    $value = isset($data[$name]) ? $data[$name] : $default;

    if ( is_string( $value ) ) {
        $value = esc_attr( $value );
    }

    $value = apply_filters( 'bbpm_old', $value, $name, $default, $method );
    $value = apply_filters( 'bbpm_old_' . $name, $value, $default, $method );

    if ( is_string( $value ) ) {
        $value = apply_filters( 'bbpm_old_string', $value, $name, $default, $method );
    }

    if ( $return ) {
        return $value;
    } else {
        echo $value;
    }
}
endif;

function bbpm_message_field($autosave='') {
    $html = '<textarea name="message" id="message" class="'.(bbpm_has_errors('message')?'has-errors':'').'" required="required" placeholder="' . esc_attr__('Type a message..', 'bbp-messages') . '">' . $autosave . '</textarea>';

    if ( bbpm_has_errors('message') ) {
        ob_start();
        bbpm_print_error('message');
        $html .= ob_get_clean();
    }

    return apply_filters('bbpm_message_field', $html, $autosave);
}

function bbpm_can_contact($user_id, $current_user=null) { 
    return bbpm_messages()->canContact($user_id, $current_user);   
}

if ( !function_exists('bbpm_trigger_404') ) :
function bbpm_trigger_404($nocache_headers=true) {
    global $wp_query;
    // trigger 404
    $wp_query->set_404();
    status_header( 404 );
    // no cache
    if ( $nocache_headers ) {
        nocache_headers();
    }
    // prevent redirect to identical object name
    remove_action( 'template_redirect', 'redirect_canonical' ); 
}
endif;

if ( !function_exists('bbpm_redirect') ) :
function bbpm_redirect($uri, $safe=false, $do=true) {
    // trigger hook
    do_action( 'bbpm_redirect_pre', $uri, $safe );

    if ( $do ) {
        return \BBP_MESSAGES\Inc\Core\Redirect::to($uri, $safe)->_do();
    } else {
        return \BBP_MESSAGES\Inc\Core\Redirect::to($uri, $safe);
    }
}
endif;

function bbpm_get_errors_array($group='default') {
    $list = array();
    $errors = bbpm_errors($group);

    if ( $errors instanceof \WP_Error && $errors->get_error_codes() ) {
        foreach ( $errors->get_error_codes() as $code ) {
            if ( $errors->get_error_messages($code) ) {
                foreach ( $errors->get_error_messages($code) as $message ) {
                    $list[] = array(
                        $code,
                        !is_array($message) ? $message : implode('<br/>', $message),
                        'error'
                    );
                }
            }
        }
    }

    return apply_filters('bbpm_get_errors_array', $list, $group);
}

if ( !function_exists('bbpm_verify_nonce') ) :
function bbpm_verify_nonce($action='bbpm_nonce', $tag='bbpm_nonce', $method='request') {
    switch ( strtolower($method) ) {
        case 'get':
            $data = $_GET;
            break;

        case 'post':
            $data = $_POST;
            break;

        default:
            $data = $_REQUEST;
            break;
    }

    $verify = isset($data[$tag]) && wp_verify_nonce($data[$tag], $action);

    return apply_filters('bbpm_verify_nonce', $verify, $action, $tag, $method);
}
endif;

function bbpm_parse_images_bb($str='') {
    $pattern = apply_filters('bbpm_parse_images_bb_pattern', "(\[img\](.*?)\[/img\])is");

    if ( preg_match($pattern, $str) ) {
        $str = preg_replace_callback($pattern, function($m){
            $url = trim(array_pop($m));
            return sprintf(
                '<div class="bbpm-inline-img"><a href="%1$s" title="%2$s"><img src="%1$s" alt="%2$s" /></a></div>',
                $url,
                basename($url)
            );
        }, $str);
    }

    return $str;
}

function bbpm_parse_images($str='') {
    $pattern = apply_filters('bbpm_parse_images_pattern', '~https?://[^/\s]+/\S+\.(jpg|png|gif|bmp|jpeg)(\z|\s)~');

    if ( preg_match($pattern, $str) ) {
        $str = preg_replace_callback($pattern, function($m){
            $url = trim(array_shift($m));
            return sprintf(
                '<div class="bbpm-inline-img"><a href="%1$s" title="%2$s"><img src="%1$s" alt="%2$s" /></a></div>',
                $url,
                basename($url)
            );
        }, $str);
    }

    return $str;
}

function bbpm_parse_youtube($str) {
    $UrlsPattern = '#(www\.|https?://)?[a-z0-9]+\.[a-z0-9]{2,4}\S*#i';
    $UrlsPattern = '~<a.*?</a>(*SKIP)(*F)|https?://\S+~';

    if ( preg_match($UrlsPattern, $str) ) {
        $pattern = apply_filters(
            'bbpm_parse_youtube_pattern',
            "/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/"
        );

        $str = preg_replace_callback($UrlsPattern, function($m) use ($pattern){
            $m = array_shift($m);

            preg_match($pattern, $m, $yt);

            if ( $yt && !empty($yt[1]) ) {
                return bbpm_parse_youtube_iframe($yt[1]);
            }

            return $m;
        }, $str);
    }

    return $str;
}

function bbpm_parse_youtube_iframe($videoId) {
    $html = sprintf(
        '<iframe width="560" height="315" src="//www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe>',
        $videoId
    );

    return apply_filters('bbpm_parse_youtube_iframe', $html, $videoId);
}

function bbpm_pending_delete($str, $m) {
    if ( $m->classes && in_array('pending-delete', $m->classes) ) {
        $str .= __('&nbsp;<em class="bbpm-inline-tooltip">[pending-delete]</em>', 'bbp-messages');
    }

    return $str;
}

function bbpm_pending_delete_excerpt($str, $c=null) {
    if ( !empty($c->classes) && in_array('pending-delete', $c->classes) ) {
        $str .= __('&nbsp;<em class="bbpm-inline-tooltip">[pending-delete]</em>', 'bbp-messages');
    }

    return $str;
}

function bbpm_search_query_prepared() {
    $prio = has_filter('bbpm_search_query', 'esc_attr');

    if ( is_numeric($prio) ) {
        remove_filter('bbpm_search_query', 'esc_attr', $prio);
    }

    $query = bbpm_search_query();

    if ( is_numeric($prio) ) {
        add_filter('bbpm_search_query', 'esc_attr', $prio);
    }

    return apply_filters('bbpm_search_query_prepared', $query);
}

function bbpm_is_user_subscribed_to_chat($chat_id, $user_id=null) {
    if ( !$user_id ) {
        $user_id = get_current_user_id();
    }

    if ( !$user_id )
        return;

    $unsub = (array) bbpm_messages()->get_chat_meta($chat_id, 'unsubscribe', null);
    $is_subscribed = !$unsub || !in_array($user_id, $unsub);

    return apply_filters('bbpm_is_user_subscribed_to_chat', $is_subscribed, $chat_id, $user_id);
}

function bbpm_get_dynamic_page_title($title='') {
    $raw_title = $title;

    if ( is_404() || !is_bbpress() || !bbpm_is_messages() )
        return $title;

    $messages_title = bbpm_get_dynamic_title();

    if ( !trim($messages_title) )
        return $title;

    $title = sprintf(_x('%1$s | %2$s', 'page title format', 'bbp-messages'), $messages_title, $title);

    return apply_filters('bbpm_get_dynamic_page_title', $title, $raw_title);
}

function bbpm_get_dynamic_title() {
    if ( bbpm_is_chats() ) {
        $title = _x('Messages', 'page title format', 'bbp-messages');
    } else if ( bbpm_is_chat_settings() ) {
        global $bbpm_chat;
        if ( isset($bbpm_chat->name) && $bbpm_chat->name ) {
            $title = sprintf(
                _x("%s &rsaquo; Settings", 'page title format', 'bbp-messages'),
                $bbpm_chat->name
            );
        } else {
            $title = _x("Edit chat", 'page title format', 'bbp-messages');
        }
    } else if ( bbpm_is_single_chat() ) {
        global $bbpm_chat;
        if ( isset($bbpm_chat->name) && $bbpm_chat->name ) {
            $title = sprintf(
                _x("Messages &rsaquo; %s", 'page title format', 'bbp-messages'),
                $bbpm_chat->name
            );
        } else {
            $title = _x("Messages", 'page title format', 'bbp-messages');
        }
    } else if ( get_query_var('bbpm_new') ) {
        $title = _x("New Message", 'page title format', 'bbp-messages');
    } else {
        $title = _x("Messages", 'page title format', 'bbp-messages');
    }

    return apply_filters('bbpm_get_dynamic_title', $title);
}

function bbpm_get_dynamic_page_title_add_count($title) {
    $raw_title = $title;

    if ( is_404() || !is_bbpress() || !bbp_is_user_home() )
        return $title;

    $count = bbpm_messages()->getChatsUnreadCount();

    if ( $count ) {
        $title = "(+{$count}) $title";
    }

    return apply_filters('bbpm_get_dynamic_page_title_add_count', $title, $raw_title);
}

function bbpm_can_notify($user_id, $chat_id=null) {
    // fallback to yes
    $can = true;

    if ( $chat_id ) {
        $can = bbpm_is_user_subscribed_to_chat($chat_id, $user_id);
    } else {
        if (get_user_meta($user_id, 'bbpm_mute_notifications', true)) {
            $can = false;
        }
    }

    return apply_filters('bbpm_can_notify', $can, $user_id, $chat_id);
}

/**
  * aliasing wp_mail to bbpm_mail
  * declare __bbpm_mail function in order to send out emails
  * your own way
  */
function bbpm_mail( $to, $subject, $message, $headers='' ) {
    if ( function_exists('__bbpm_mail') ) {
        return call_user_func_array('__bbpm_mail', func_get_args());
    }

    return wp_mail( $to, $subject, $message, $headers );
}

function bbpm_options_default() {
    global $bbpm_options_default;

    if ( isset($bbpm_options_default) )
        return $bbpm_options_default;

    $bbpm_options_default = apply_filters('bbpm_options_default', array(
        'pagi_messages' => 20,
        'pagi_chats' => 10,
        'email_subject' => '[%1$s] You have new messages from %2$s',
        'email_body' => trim(__('Dear %1$s,
%2$s has recently sent you new messages on %3$s:

"%4$s"

To read and reply to this message, click the link below:
%5$s

To unsubscribe from notifications from this chat, follow below-link:
%6$s

To unsubscribe from all notifications, please navigate to your profile edit screen.', 'bbp-messages')),
        'html_emails' => false,
        'cache_ctx' => 'object',
        'older_delete_days' => null,
        'menu_text' => 'Messages ([bbpm-unread-count unique=1])'
    ));

    return $bbpm_options_default;
}

function bbpm_options() {
    global $bbpm_options;

    if ( isset($bbpm_options) ) {
        return $bbpm_options;
    }

    $bbpm_options = wp_parse_args(
        (array) get_site_option('bbpm_settings', null),
        bbpm_options_default()
    );

    $bbpm_options = apply_filters('bbpm_options', $bbpm_options);

    return $bbpm_options;
}

function bbpm_get_list_keys($list, $key, $map=null, $filter=null, $setIndex=null) {
    $data = array();

    if ( $list ) {
        foreach ( (array) $list as $itm ) {
            $itm = (array) $itm;

            if ( isset( $itm[$key] ) ) {
                if ( $setIndex && isset( $itm[$setIndex] ) ) {
                    $data[$itm[$setIndex]] = $itm[$key];
                } else {
                    $data[] = $itm[$key];
                }
            }
        }
    }

    if ( $map && is_callable($map) && $data ) {
        $data = array_map($map, $data);
    }

    if ( $filter && is_callable($filter) && $data ) {
        $data = array_filter($data, $filter);
    }

    return $data;
}

function bbpm_prepare_contact_button($user_id) {
    $btn = array(
        'link' => null,
        'link_title' => null,
        'unread_count' => null,
        'inner_text' => null
    );

    $user = get_userdata($user_id);

    if ( !empty($user->ID) ) {
        $m = bbpm_messages();

        if ( $m->current_user ) {
            $chat_id = $m->getPrivateSharedChat($m->current_user, $user->ID);

            if ( $chat_id ) {
                $btn['link'] = bbpm_messages_url($chat_id, $m->current_user);
                $btn['unread_count'] = $m->getChatUnreadCount($chat_id);
            } else {
                global $bbpm_bases;
                $btn['link'] = bbpm_messages_url(sprintf('%s/%d/', $bbpm_bases['with'], $user->ID), $m->current_user);
            }
        } else {
            $btn['link'] = apply_filters(
                'bbpm_redirect_login_url',
                wp_login_url()
            );
        }

        $btn['link_title'] = sprintf(__('Send %s a Message', 'bbp-messages'), $user->display_name);
    }

    $btn['inner_text'] = __('Send a Message', 'bbp-messages');

    return apply_filters('bbpm_prepare_contact_button', $btn, $user_id);
}

function bbpm_admin_tabs() {
    return apply_filters('bbpm_admin_tabs', array());
}

function bbpm_bases($get=null) {
    global $bbpm_bases;

    if ( !$get )
        return $bbpm_bases;

    if ( isset($bbpm_bases[$get]) )
        return $bbpm_bases[$get];
}

function bbpm_get_current_chat_id() {
    global $bbpm_chat_id;
    return apply_filters(__FUNCTION__, $bbpm_chat_id);
}
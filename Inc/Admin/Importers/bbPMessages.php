<?php namespace BBP_MESSAGES\Inc\Admin\Importers;

use \BBP_MESSAGES\Inc\Admin\Admin;

class bbPMessages
{
    public $fields;
    public $admin;

    public function __construct()
    {
        $this->fields = array('ID','PM_ID','sender','recipient','message','date','seen','deleted');
    }

    public function admin()
    {
        if ( !$this->admin || !($this->admin instanceof Admin) ) {
            $this->admin = new Admin;
        }
        
        return $this->admin;
    }

    public function init($network_active)
    {
        add_filter('bbpm_admin_tabs', array($this, 'tab'));
        add_filter('bbpm_bbpm_importer_message_str', array($this, 'fixLtGt'));

        $this->admin()->feedback(sprintf(
            __('<strong>bbPress Messages v2:</strong> Import older messages now from legacy data table. Click <a href="%1$s">here</a> to begin import or <a href="%2$s">dismiss</a> this notice.', 'bbp-messages'),
            ($network_active ? network_admin_url('admin.php') : admin_url('admin.php')) . (
                '?page=bbpm-bbpm-import'
            ),
            ($network_active ? network_admin_url('admin.php') : admin_url('admin.php')) . (
                '?page=bbpm-bbpm-import&bbpm-dismiss=1'
            )
        ), true);
    }

    public function tab($tabs)
    {
        return array_merge($tabs, array(
            'bbpm-import' => array(
                'id' => 'bbpm-import',
                'name' => __('bbPM importer', 'bbp-messages'),
                'content_callback' => array($this, 'screen'),
                'update_callback' => array($this, 'update'),
                'request_handler' => array($this, 'request')
            )
        ));
    }

    public function request()
    {
        if ( isset($_GET['bbpm-dismiss']) ) {
            delete_option('bbpm_has_import_data_bbpmessages');

            return bbpm_redirect(esc_url('?page=bbpress-messages'), 1);
        }
    }

    public function update()
    {
        if ( !isset($_POST['_begin_import']) )
            return;

        if ( !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bbpm_import') ) {
            return $this->admin()->feedback(__('Error: Bad authentication!', 'bbp-messages'), false);
        }

        $this->extract();
    }

    public function screen()
    {
        if ( defined('BBP_MESSAGES_DOING_IMPORT') && BBP_MESSAGES_DOING_IMPORT )
            return;

        global $wpdb;
        $table = apply_filters('bbpm_importers_bbp_messages_table', "{$wpdb->prefix}bbp_messages");
        ?>

        <form method="post">

            <p><?php printf(__('Import older messages from legacy database table <code>%s</code>.', 'bbp-messages'), $table); ?></p>

            <p><?php _e('If you have already imported messages using this tool, please dismiss this as it will not prevent duplicate records from inserting.', 'bbp-messages'); ?></p>

            <p><?php _e('Once you click import, the plugin will begin fetching messages from older database table and appending them to the new one.', 'bbp-messages'); ?></p>

            <p><?php _e('Please make sure not to cancel the import by pressing the escape key, or browser refresh or back button, until it is fully done.', 'bbp-messages'); ?></p>

            <p><?php _e('Please note that this may take a while, depending on how many messages we\'re importing, just in case you were confused.', 'bbp-messages'); ?></p>

            <p><label><input type="checkbox" name="delete_after_done" /> <?php printf(__('Delete <code>%s</code> legacy database table after import done', 'bbp-messages'), $table); ?></label></p>

            <input type="hidden" name="_begin_import" value="1" />
            <?php wp_nonce_field('bbpm_import', '_wpnonce'); ?>
            <?php submit_button(__('Begin Import', 'bbp-messages')); ?>

        </form>

        <?php
    }

    public function check()
    {
        global $wpdb;
        $table = apply_filters('bbpm_importers_bbp_messages_table', "{$wpdb->prefix}bbp_messages");
        $exists = $wpdb->query("SHOW TABLES LIKE '$table'");

        if ( $exists ) {
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");

            if ( $count ) {
                $describe = $wpdb->get_results("DESCRIBE $table");
                $fields = bbpm_get_list_keys($describe, 'Field');
                if ( $fields && 8 == count($fields) ) {
                    foreach ( $this->fields as $field ) {
                        if ( !in_array($field, $fields) ) {
                            return false;
                        }
                    }

                    return true;
                }
            }
        }

        return false;
    }

    public function extract()
    {
        if ( !$this->check() )
            return;

        global $wpdb;
        $table = apply_filters('bbpm_importers_bbp_messages_table', "{$wpdb->prefix}bbp_messages");

        $raw = $wpdb->get_results(sprintf(
            "SELECT `%s` FROM $table",
            implode('`,`', $this->fields)
        ));

        $invalid_users = 0;
        $raw_count = count($raw);

        foreach ( (array) $raw as $i=>$m ) {
            if ( !get_userdata( $m->sender ) ) {
                $invalid_users++;
                unset($raw[$i]);
                continue;
            }

            if ( !get_userdata( $m->recipient ) ) {
                $invalid_users++;
                unset($raw[$i]);
                continue;
            }
        }

        $chats = array_unique(bbpm_get_list_keys($raw, 'PM_ID', 'intval'));
        $recipients = array();

        foreach ( $chats as $i=>$chat_id ) {
            $grouped[$chat_id] = array();

            foreach ( $raw as $i=>$m ) {
                if ( $m->PM_ID == $chat_id ) {
                    $grouped[$chat_id][] = $m;

                    if ( empty($recipients[$chat_id]) ) {
                        $recipients[$chat_id] = array(
                            (int) $m->sender,
                            (int) $m->recipient
                        );
                    }
                }
            }
        }

        $inserted = array();
        $failed = 0;

        if ( isset($grouped) && $grouped ) {
            if ( !defined('BBP_MESSAGES_DOING_IMPORT') ) {
                define('BBP_MESSAGES_DOING_IMPORT', true);
            }

            foreach ( $grouped as $chat_id=>$messages ) {
                $r = $recipients[$chat_id];
                // fresh instance
                $m = bbpm_messages();
                $_chat_id = $m->set('current_user', $r[0])->getPrivateSharedChat($m->current_user, $r[1]);

                if ( !$_chat_id ) {
                    $m->set(
                        'chat_id',
                        $m->set('chat_id', null)->getOrGenerateChatId()
                    )->addChatRecipient(array($m->current_user, $r[1]));
                    $_chat_id = $m->get('chat_id');
                }
                $m->set('chat_id', $_chat_id);

                if ( !$_chat_id )
                    continue;

                foreach ( $messages as $msg ) {
                    $msg = apply_filters('bbpm_bbpm_importer_message_data', $msg);
                    // insert here
                    if ( $msg->deleted && trim($msg->deleted) ) {
                        $m->set('deleted_raw', $msg->deleted);
                    }

                    $m->set(array(
                        'date_sent' => $msg->date,
                        'prepared_message' => $m->prepareMessageBeforeInsert(
                            apply_filters('bbpm_bbpm_importer_message_str', $msg->message)
                        )
                    ));

                    $m->insert(array('sender' => intval($msg->sender)));

                    if ( $m->last_insert_id ) {
                        $inserted[] = $m->last_insert_id;
                    } else {
                        $failed++;
                    }

                    if ( $msg == end($messages) ) {
                        // last key, set seen
                        $seen = array(
                            'int' => (int) $msg->seen,
                            'uid' => (int) $msg->recipient 
                        );
                    }
                }

                if ( isset($seen) && $seen['uid'] ) {
                    $unread = $m->getChatUnreads();

                    if ( $seen['int'] ) {
                        $i = array_search($seen['uid'], $unread);

                        if ( is_numeric($i) && isset($unread[$i]) ) {
                            unset($unread[$i]);

                            if ( $unread ) {
                                $m->update_chat_meta($m->chat_id, 'unread', $unread);
                            } else {
                                $m->delete_chat_meta($m->chat_id, 'unread');                
                            }
                        }
                    } else {
                        if ( !in_array($seen['uid'], $unread) ) {
                            $unread[] = $seen['uid'];
                            $m->update_chat_meta($m->chat_id, 'unread', $unread);
                        }
                    }
                }

                foreach ( $r as $uid ) {
                    $m->deleteCache("last_chat_message_{$m->chat_id}_{$uid}");
                    $m->deleteCache("inbox_ids_{$m->chat_id}_{$uid}");
                    $m->deleteCache("unread_chats_{$uid}");
                    $m->deleteCache("user_{$uid}_chats_raw");
                    $m->deleteCache("user_{$uid}_total_messages_chat_{$m->chat_id}");
                    $m->deleteCache("user_{$uid}_total_messages");
                }
            }
        }

        if ( empty($inserted) ) {
            $this->admin()->feedback(__('Error occured, no messages were imported.', 'bbp-messages'), false);
        } else {
            $this->admin()->feedback(sprintf(
                __('Import done: successfully imported %1$d messages from %2$d total (IDs from %3$d to %4$d)', 'bbp-messages'),
                count($inserted),
                $raw_count,
                isset($inserted[0]) ? $inserted[0] : 0,
                isset($inserted[count($inserted)-1]) ? $inserted[count($inserted)-1] : 0
            ), true);

            if ( isset($_POST['delete_after_done']) ) {
                if ( $wpdb->query("DROP TABLE {$table}") ) {
                    $this->admin()->feedback(sprintf(
                        __('Database table %s successfully deleted.', 'bbp-messages'),
                        $table
                    ), true);
                } else {
                    $this->admin()->feedback(sprintf(
                        __('Error: Could not delete database table %s.', 'bbp-messages'),
                        $table
                    ), false);
                }
            }

            // quit
            delete_option('bbpm_has_import_data_bbpmessages');
        }

        // trigger hook
        do_action('bbpm_importers_bbp_messages_post_import', $inserted, $failed);
    }

    public function fixLtGt($str)
    {
        if ( trim($str) ) {
            return str_replace(array(
                '&amp;_lt;', '&amp;_gt;', '_lt;', '_gt;'
            ), array(
                '&lt;', '&gt;', 'lt;', 'gt;'
            ), $str);
        }

        return $str;
    }
}
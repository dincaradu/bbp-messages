<?php

class WP_Messages
{
    /**
      * Class instance for static calls
      */
    protected static $instance = null;

    /**
      *
      */
    public $recipients;

    /**
      *
      */
    public $id;
    
    /**
      *
      */
    public $chat_id;
    
    /**
      *
      */
    public $search_term;
    
    /**
      *
      */
    public $per_page;
    
    /**
      *
      */
    public $current_page;
    
    /**
      *
      */
    public $current_user;
    
    /**
      *
      */
    public $table;

    /**
      *
      */
    private $defaults;

    /**
      *
      */
    public $errors;

    /**
      *
      */
    public $last_insert_id;

    /**
      *
      */
    public $last_insert_chat_id;

    /**
      * Merge $recipients variable to make a chat contacts
      * and unset current user (sender) from them
      */
    public $chat_contacts;

    /**
      *
      */
    private $prepared_message;

    /**
      *
      */
    public $is_new_chat;

    /**
      * self-cache chat recipients through instances
      */
    public $_chat_recipients;

    /**
      *
      */
    public $autoload_meta;

    /**
      *
      */
    public $fake_delete;

    /**
      *
      */
    private $event_time_add;

    /**
      *
      */
    private $event_interval;

    /**
      *
      */
    public $event_patterns;

    /**
      *
      */
    public $text_domain;

    /**
      *
      */
    public $pagi_current_data;

    /**
      *
      */
    public $page_arg;

    /**
      *
      */
    public $inbox_messages;

    /**
      *
      */
    public $chats;

    /**
      *
      */
    public $unread_chats;

    /**
      *
      */
    private $events_uniques_min_id;

    /**
      *
      */
    private $events_uniques_max_id;

    /**
      *
      */
    public $email_notifications;

    /**
      *
      */
    public $chat_id_generator;

    /**
      *
      */
    public $deleted_raw;

    /**
      *
      */
    public $date_sent;

    /**
      *
      */
    public $older_messages_cleanup_interval;

    /**
      *
      */
    public $orphaned_messages_cleanup_chats_per_batch;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    public function __construct()
    {
        $this->defaults = array(
            'recipients' => array(),
            'id' => 0,
            'chat_id' => null,
            'search_term' => null,
            'per_page' => 20,
            'current_page' => 1,
            'current_user' => null,
            'table' => 'my_messages',
            'errors' => new \WP_Error(),
            '_chat_recipients' => array(),
            'autoload_meta' => array(
                'chat' => array(
                    'recipients',
                    'unread',
                    'unread_count',
                    'delete_scheduled'
                ),
                'message' => array(
                    'delete_scheduled'
                )
            ),
            'event_time_add' => 10,
            'event_interval' => 60,
            'event_patterns' => array(),
            'page_arg' => 'page',
            'inbox_messages' => array(),
            'chats' => array(),
            'unread_chats' => array(),
            'events_uniques_min_id' => 1,
            'events_uniques_max_id' => 70,
            'email_notifications' => true,
            'wpdb_prefix' => $this->wpdb_prefix(),
            'orphaned_messages_cleanup_chats_per_batch' => 20
        );

        foreach ( $this->defaults as $prop=>$value ) {
            $this->$prop = isset($this->$prop) ? $this->$prop : $value;
        }

        if ( $this->table ) {
            $this->meta_table = "{$this->table}_meta";
        }

        return $this;
    }

    public function set( $args )
    {
        if ( is_object($args) ) {
            $args = (array) $args;
        }

        if ( !is_array($args) ) {
            $args = func_get_args();
            if ( isset($args[0]) ) {
                $args = array(
                    $args[0] => isset($args[1]) ? $args[1] : null
                );
            }
        }

        if ( !is_array($args) ) {
            return $this;
        }

        foreach ( (array) $args as $i=>$v ) {
            if ( method_exists($this, 'filterSet') ) {
                $v = call_user_func(array($this, 'filterSet'), $v, $i);
            }

            $this->$i = $v;
        }

        return $this;
    }

    public function get($prop, $default=null)
    {
        return isset($this->$prop) ? $this->$prop : $default;
    }

    public function init()
    {
        return $this
            ->filters()
            ->autoloadMeta()
            ->prepareClassObjects();
    }

    public function wpdb_prefix($wpdb=null)
    {
        if ( !$wpdb || !($wpdb instanceof \wpdb) ) {
            global $wpdb;
        }

        if ( !empty($wpdb->base_prefix) ) { // network active
            return $wpdb->base_prefix;
        }

        return $wpdb->prefix;
    }

    public function activation()
    {
        global $wpdb;

        /** messages table **/
        $table = $this->wpdb_prefix . $this->table; 
        /** messages meta table **/
        $table2 = $this->wpdb_prefix . $this->meta_table;

        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `chat_id` varchar(191) NOT NULL,
          `sender` bigint(20) NOT NULL,
          `message` LONGTEXT NOT NULL,
          `date` bigint(20) NOT NULL,
          `deleted` varchar(50) DEFAULT '',
          UNIQUE (`id`)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE IF NOT EXISTS $table2 (
          `meta_id` bigint(20) NOT NULL AUTO_INCREMENT,
          `id` bigint(20) DEFAULT 0,
          `chat_id` varchar(191) DEFAULT 0,
          `key` varchar(250) NOT NULL,
          `value` LONGTEXT DEFAULT '',
          UNIQUE (`meta_id`)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        dbDelta( $sql );
        dbDelta( $sql2 );

        // append 'WP_Messages_weekly' interval required for cleanup
        add_filter('cron_schedules', array($this, 'pushEventInterval'));

        // weekly cron
        if( !wp_next_scheduled('WP_Messages_weekly_cleanup') ) {  
            wp_schedule_event(time() + WEEK_IN_SECONDS, 'WP_Messages_weekly', 'WP_Messages_weekly_cleanup');  
        }

        return $this;
    }

    public function deactivation()
    {
        foreach ( range($this->events_uniques_min_id,$this->events_uniques_max_id) as $i ) {
            $tag = "WP_Messages_schedules_{$i}";
            wp_clear_scheduled_hook($tag);
        }

        wp_clear_scheduled_hook('WP_Messages_weekly_cleanup');
    }

    public function isValidUser($user_id)
    {
        if ( !$user_id )
            return false;

        $data = new \WP_User($user_id);
        
        return isset($data->ID) && (bool) $data->ID;
    }

    public function mergeErrors($errors)
    {
        if ( !$this->errors ) {
            $this->errors = new WP_Error();
        }

        $wp_errors = func_get_args();
        
        foreach ( $wp_errors as $wp_error ) {
            if ( !is_wp_error( $wp_error ) )
                continue;
            foreach ( $wp_error as $key => $errors ) {
                foreach ( $errors as $error ) {
                    $this->errors->add( $key, $error );
                }
                if ( isset( $wp_error->error_data[ $key ] ) ) {
                    $this->errors->add_data( $wp_error->error_data[ $key ], $key );
                }
            }
        }

        return $this;
    }

    public function getOrGenerateChatId()
    {
        if ( !$this->chat_id ) {
            $this->is_new_chat = true;
            if ( is_callable($this->chat_id_generator) ) {
                return call_user_func($this->chat_id_generator);
            }
            return $this->defaultchatIdGenerator();
        } else {
            return $this->chat_id;
        }
    }

    function defaultchatIdGenerator()
    {
        $id = base_convert(rand(1000000000,PHP_INT_MAX), 10, 36);

        if ( 8 < strlen($id) ) { // make short IDs
            $id = substr($id, 0, 8);
        }

        for ($i=0, $c=strlen($id); $i<$c; $i++) {
            $id[$i] = rand(0, 100) > 50? strtoupper($id[$i]) : $id[$i];
        }

        return $id;
    }

    public function getChatRecipients($chat_id=null)
    {
        if ( is_null($chat_id) && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !is_array($this->_chat_recipients) ) {
            $this->_chat_recipients = array();
        }

        if ( isset($this->_chat_recipients[$chat_id]) ) {
            return $this->_chat_recipients[$chat_id];
        }

        $this->_chat_recipients[$chat_id] = (array) $this->get_chat_meta($chat_id, 'recipients');
        $this->_chat_recipients[$chat_id] = array_map('intval', $this->_chat_recipients[$chat_id]);
        $this->_chat_recipients[$chat_id] = array_filter($this->_chat_recipients[$chat_id], 'trim');
        $this->_chat_recipients[$chat_id] = array_unique($this->_chat_recipients[$chat_id]);
        $this->_chat_recipients[$chat_id] = array_values($this->_chat_recipients[$chat_id]);

        return $this->_chat_recipients[$chat_id];
    }

    /**
      * @param $user_id int|Array user ID to add
      */
    public function addChatRecipient($user_id, $chat_id=null)
    {
        if ( is_null($chat_id) && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }
        
        if ( !is_array($user_id) ) {
            $user_ids = array( $user_id );
        } else {
            $user_ids = $user_id;
        }

        $user_ids = array_map('intval', $user_ids);
        $user_ids = array_filter($user_ids, array($this, 'isValidUser'));

        if ( !$user_ids )
            return $this;

        $recipients = $this->getChatRecipients($chat_id);
        $recipients_pre = $recipients;

        foreach ( $user_ids as $user_id ) {
            if ( !in_array($user_id, $recipients) ) {
                $recipients[] = $user_id;
            }
        }

        if ( $recipients === $recipients_pre )
            return $this;

        $this->update_chat_meta($chat_id, 'recipients', $recipients);
        $this->_chat_recipients[$chat_id] = $recipients;

        return $this;
    }

    /**
      * @param $user_id int|Array user ID to remove
      */
    public function removeChatRecipient($user_id, $chat_id=null)
    {
        if ( is_null($chat_id) && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !is_array($user_id) ) {
            $user_ids = array( $user_id );
        } else {
            $user_ids = $user_id;
        }

        $user_ids = array_map('intval', $user_ids);
        $user_ids = array_filter($user_ids, array($this, 'isValidUser'));

        if ( !$user_ids )
            return $this;

        $recipients = $this->getChatRecipients($chat_id);
        $recipients_pre = $recipients;

        foreach ( $user_ids as $user_id ) {
            $i = array_search($user_id, $recipients);
            if ( is_numeric($i) )
                unset( $recipients[$i] );
        }

        $recipients = array_unique($recipients);

        if ( $recipients === $recipients_pre )
            return $this;

        if ( $recipients ) {
            $this->update_chat_meta($chat_id, 'recipients', $recipients);
        } else {
            $this->delete_chat_meta($chat_id, 'recipients');
        }

        $this->_chat_recipients[$chat_id] = $recipients;

        return $this;
    }

    public function isChatRecipient($user_id, $chat_id=null)
    {
        return in_array($user_id, $this->getChatRecipients($chat_id));
    }

    public function prepareClassObjects()
    {
        /* current user */
        if ( !$this->current_user && is_user_logged_in() ) {
            $this->current_user = get_current_user_id();
        };

        /* recipients */
        if ( $this->recipients ) {
            if ( !is_array($this->recipients) ) {
                $this->recipients = array(
                    (int) $this->recipients
                );
            }

            $this->recipients = array_map('intval', $this->recipients);
            $this->recipients = array_filter($this->recipients, array($this, 'isValidUser'));

            if ( $this->current_user && !in_array($this->current_user, $this->recipients) ) {
                $this->recipients[] = $this->current_user;
            }
        }

        $this->chat_contacts = $this->recipients;
        $_search = array_search($this->current_user, $this->chat_contacts);
        if ( is_numeric($_search) ) {
            unset($this->chat_contacts[$_search]);
        }

        if ( !isset($this->_chat_recipients) || !is_array($this->_chat_recipients) ) {
            $this->_chat_recipients = array();
        }

        return $this;
    }

    public function send()
    {
        if ( !$this->chat_id ) {
            $this->chat_id = $this->getOrGenerateChatId();
        }

        if ( !$this->chat_id ) {
            return $this->mergeErrors(
                new \WP_Error('err_chatID', __('Chat ID missing: could not find or generate a chat ID.', $this->text_domain))
            );
        }

        if ( !isset($this->message) || !trim($this->message) ) {
            return $this->mergeErrors(
                new \WP_Error('err_message', __('Error: Cannot send an empty message.', $this->text_domain))
            );
        }

        $this->prepareClassObjects();

        if ( !$this->recipients ) {

            $this->recipients = $this->getChatRecipients($this->chat_id);

            if ( !$this->recipients ) {
                return $this->mergeErrors(
                    new \WP_Error(
                        'err_recipients',
                        __('Error: please set at least 1 recipient for this message.', $this->text_domain)
                    )
                );
            }
        }

        if ( !$this->current_user  ) {
            return $this->mergeErrors(
                new \WP_Error('err_sender', __('Error: Could not find a sender for this message.', $this->text_domain))
            );          
        }

        /** check if a valid user **/
        else if ( !$this->isValidUser($this->current_user) ) {
            return $this->mergeErrors(
                new \WP_Error('err_sender', __('Error: Could not find a sender for this message.', $this->text_domain))
            );
        }

        if ( $this->errors->get_error_codes() ) // if any errors
            return $this;

        $this->prepared_message = $this->prepareMessageBeforeInsert($this->message);

        if ( !in_array($this->current_user, $this->recipients) ) {
            $this->recipients[] = $this->current_user;
        }

        $this->insert();

        if ( $this->last_insert_id && $this->last_insert_chat_id ) {
            /** successful insert **/
            if ( $this->is_new_chat ) { // new chat
                // add chat meta
                $this->update_chat_meta($this->last_insert_chat_id, 'recipients', $this->recipients);
            } else { // existing chat
                //
            }
            // 
            $this->id = $this->last_insert_id;
            // self-cache recipients meta while we're at it
            $this->_chat_recipients[$this->last_insert_chat_id] = $this->recipients;

            $hook_args = array(
                'sender' => $this->current_user,
                'recipients' => $this->recipients,
                'chat_id' => $this->last_insert_chat_id,
                'id' => $this->last_insert_id,
                'message' => $this->prepared_message,
                'is_new' => $this->is_new_chat
            );

            $this->postSend($hook_args);

            // trigger hook
            do_action( "WP_Messages_{$this->table}_successful_send", $hook_args);
        } else {
            /** failed one **/

            do_action( "WP_Messages_{$this->table}_failed_send", array(
                'sender' => $this->current_user,
                'recipients' => $this->recipients,
                'message' => $this->prepared_message
            ));

            return $this->mergeErrors(
                new \WP_Error('err_sending', __('Error: Could not successfully insert this message into the database.', $this->text_domain))
            );
        }

        return $this;
    }

    public function insert($args=array())
    {
        $args = wp_parse_args(
            $args,
            array(
                'chat_id' => $this->chat_id,
                'sender'  => $this->current_user,
                'message' => $this->prepared_message
            )
        );

        $args['date'] = time();
        $args['deleted'] = null;

        if ( isset($this->deleted_raw) && $this->deleted_raw ) {
            if ( !is_array($this->deleted_raw) ) {
                $this->deleted_raw = explode(',', $this->deleted_raw);
            }

            $this->deleted_raw = array_map('intval', $this->deleted_raw);
            if ( $this->deleted_raw ) {
                $args['deleted'] = implode(',', $this->deleted_raw);
            }
        }

        if ( isset($this->date_sent) && intval($this->date_sent) ) {
            $args['date'] = (int) $this->date_sent;
        }

        global $wpdb;

        $wpdb->insert(
            $this->wpdb_prefix . $this->table,
            $args
        );

        $this->last_insert_id = $wpdb->insert_id;

        if ( $this->last_insert_id ) {
            $this->last_insert_chat_id = $args['chat_id'];
        }

        return $this;
    }

    public function prepareMessageBeforeInsert($string)
    {
        return $string;
    }

    public function prepareSearchTerm($wpdb_prepare=null, $term=null)
    {
        if ( !$term && $this->search_term ) {
            $term = $this->search_term;
        }

        $term = apply_filters(sprintf('WP_Messages_%s_%s', $this->table, __FUNCTION__), $term, $wpdb_prepare);

        return $term;
    }

    public function parseMessageFromRaw($string)
    {
        return $string;
    }

    public function parseExcerptFromRaw($string)
    {
        return $string;
    }

    public function getMeta($type, $id, $meta_key, $default=null)
    {
        global $wpdb, $WP_Messages_autoloaded_meta;

        $al = isset($WP_Messages_autoloaded_meta[$this->table]) ? $WP_Messages_autoloaded_meta[$this->table] : array();

        if ( 'message' === $type ) {
            $selector = 'id';
            $id = intval($id);
            if ( isset($al['message'][$id]) && isset($al['message'][$id][$meta_key]) ) {
                $cached = $al['message'][$id][$meta_key];
            } else {
                $cache = $this->getCache("meta_{$type}_{$id}_{$meta_key}");
            }
        } else {
            $selector = 'chat_id';
            if ( isset($al['chat'][$id]) && isset($al['chat'][$id][$meta_key]) ) {
                $cached = $al['chat'][$id][$meta_key];
            } else {
                $cache = $this->getCache("meta_{$type}_{$id}_{$meta_key}");
            }
        }

        if ( isset($cached) ) {
            $meta = $cached;
        } else if ( isset($cache) && false !== $cache ) {
            $meta = $cache;
        } else {
            $sql = $wpdb->prepare(
                "SELECT value FROM {$this->wpdb_prefix}{$this->meta_table} WHERE `$selector` = %s AND `key` = %s LIMIT 1",
                $id,
                $meta_key
            );
            $meta = $wpdb->get_var( $sql );
            // set cache
            $this->setCache("meta_{$type}_{$id}_{$meta_key}", $meta);
        }

        $meta = apply_filters('WP_Messages_get_meta_value', $meta, $type, $id, $meta_key);
        $meta = apply_filters("WP_Messages_get_{$type}_meta_value_{$meta_key}", $meta, $id);

        $meta = maybe_unserialize($meta);

        if ( !is_null($default) && is_null($meta) ) {
            return $default;
        }

        return $meta;
    }

    public function updateMeta($type, $id, $meta_key, $meta_value, $old_value=null)
    {
        if ( is_null($old_value) ) {
            $old_value = $this->getMeta($type, $id, $meta_key);
        }

        if ( $old_value == $meta_value )
            return;

        global $wpdb;

        if ( 'message' === $type ) {
            $selector = 'id';
            $id = intval($id);
        } else {
            $selector = 'chat_id';
        }

        $meta_value = apply_filters('WP_Messages_update_meta_value', $meta_value, $type, $id, $meta_key);
        $meta_value = apply_filters("WP_Messages_update_{$type}_meta_value_{$meta_key}", $meta_value, $id);

        $meta_value = maybe_serialize($meta_value);

        $updated = $wpdb->update(
            $this->wpdb_prefix . $this->meta_table,
            array(
                'value' => $meta_value
            ),
            array(
                $selector => $id,
                'key' => $meta_key
            )
        );

        if ( !$updated ) {
            #$this->deleteMeta($type, $id, $meta_key);
            $inserted = $wpdb->insert(
                $this->wpdb_prefix . $this->meta_table,
                array(
                    $selector => $id,
                    'key' => $meta_key,
                    'value' => $meta_value
                )
            );
        }

        if ( (isset($inserted) && $inserted) || $updated ) {
            $this->postUpdateMeta($type, $meta_key, $id, $meta_value);

            do_action("WP_Messages_update_meta", $type, $meta_key, $id, $meta_value);
            do_action("WP_Messages_update_{$type}_meta_{$meta_key}", $id, $meta_value);
        }

        if ( isset($inserted) ) {
            return (bool) $inserted;
        }

        return (bool) $updated;
    }

    public function deleteMeta($type, $id, $meta_key)
    {
        global $wpdb;

        if ( 'message' === $type ) {
            $selector = 'id';
            $id = intval($id);
        } else {
            $selector = 'chat_id';
        }

        $deleted = $wpdb->delete(
            $this->wpdb_prefix . $this->meta_table,
            array(
                'key' => $meta_key,
                $selector => $id
            )
        );

        if ( $deleted ) {
            $this->postDeleteMeta($type, $meta_key, $id);

            do_action("WP_Messages_delete_meta", $type, $meta_key, $id);
            do_action("WP_Messages_delete_{$type}_meta_{$meta_key}", $id);
        }

        return (bool) $deleted;
    }

    public function get_chat_meta($chat_id, $meta_key, $default=null)
    {
        return $this->getMeta('chat', $chat_id, $meta_key, $default);
    }

    public function update_chat_meta($chat_id, $meta_key, $meta_value)
    {
        return $this->updateMeta('chat', $chat_id, $meta_key, $meta_value);
    }

    public function delete_chat_meta($chat_id, $meta_key)
    {
        return $this->deleteMeta('chat', $chat_id, $meta_key);
    }

    public function get_message_meta($id, $meta_key, $default=null)
    {
        return $this->getMeta('message', $id, $meta_key, $default);
    }

    public function update_message_meta($id, $meta_key, $meta_value)
    {
        return $this->updateMeta('message', $id, $meta_key, $meta_value);
    }

    public function delete_message_meta($id, $meta_key)
    {
        return $this->deleteMeta('message', $id, $meta_key);
    }

    public function filters()
    {
        add_filter('WP_Messages_get_chat_meta_value_recipients', array($this, 'explodeUsers'));
        add_filter('WP_Messages_update_chat_meta_value_recipients', array($this, 'implodeUsers'));
        add_filter('WP_Messages_get_chat_meta_value_unread', array($this, 'explodeUsers'));
        add_filter('WP_Messages_update_chat_meta_value_unread', array($this, 'implodeUsers'));

        add_filter('cron_schedules', array($this, 'pushEventInterval'));

        foreach ( range($this->events_uniques_min_id,$this->events_uniques_max_id) as $i ) {
            add_action("WP_Messages_schedules_{$i}", array($this, 'events'));
        }

        // cleanup orphaned messages (ones deleted by all recipients)
        add_action('WP_Messages_weekly_cleanup', array($this, 'cleanupOrphanedMessagesSchedule'));
        // cleanup older messages
        add_action('WP_Messages_weekly_cleanup', array($this, 'cleanupOlderMessages'));

        if ( !wp_using_ext_object_cache() ) {
            // delete expired transients from options table
            add_action('WP_Messages_weekly_cleanup', array($this, 'transientsCleanup'));
        }

        return $this;
    }

    public function implodeUsers($list)
    {
        if ( $list && is_array($list) )
            $list = implode(',', $list);

        return $list;
    }

    public function explodeUsers($list)
    {
        if ( $list && !is_array($list) )
            $list = explode(',', $list);

        return $list;
    }

    public function isSent()
    {
        return (bool) $this->last_insert_id;
    }

    public function autoloadMeta()
    {
        global $WP_Messages_autoloaded_meta;

        if ( !is_array($WP_Messages_autoloaded_meta) ) {
            $WP_Messages_autoloaded_meta[$this->table] = array(
                'chat' => array(),
                'message' => array()
            );
        }

        $cached = $this->getCache('autoloaded_meta');

        if ( false !== $cached ) {
            $WP_Messages_autoloaded_meta[$this->table] = $cached;
            return $this;
        }

        if ( !empty($WP_Messages_autoloaded_meta[$this->table]['chat']) || !empty($WP_Messages_autoloaded_meta[$this->table]['message']) ) {
            return $this;
        }
        
        if ( $this->autoload_meta ) {
            foreach ( $this->autoload_meta as $type => $meta ) {
                switch (strtolower($type)) {
                    case 'chat':
                        global $wpdb;
                        $sql = (
                            "SELECT `chat_id`,`key`,`value` FROM {$this->wpdb_prefix}{$this->meta_table} WHERE (%s) AND `chat_id` > '0'"
                        );

                        $meta_pre = $meta;
                        $meta = array_map(function($m){
                            return sprintf("`key` = '%s'", sanitize_text_field($m));
                        }, $meta);

                        $sql = sprintf(
                            $sql,
                            implode(' OR ', $meta)
                        );

                        $all = $wpdb->get_results($sql);

                        if ( isset($all[0]) ) {
                            foreach ( $all as $data ) {
                                $data = (array) $data;
                                if ( isset($data['key']) && isset($data['value']) && isset($data['chat_id']) ) {
                                    $WP_Messages_autoloaded_meta[$this->table]['chat'][$data['chat_id']][$data['key']] = $data['value'];
                                }
                            }
                        }
                        break;
                    
                    case 'message':
                        global $wpdb;
                        $sql = (
                            "SELECT `id`,`key`,`value` FROM {$this->wpdb_prefix}{$this->meta_table} WHERE (%s) AND `id` > '0'"
                        );

                        $meta_pre = $meta;
                        $meta = array_map(function($m){
                            return sprintf("`key` = '%s'", sanitize_text_field($m));
                        }, $meta);

                        $sql = sprintf(
                            $sql,
                            implode(' OR ', $meta)
                        );

                        $all = $wpdb->get_results($sql);

                        if ( isset($all[0]) ) {
                            foreach ( $all as $data ) {
                                $data = (array) $data;
                                if ( isset($data['key']) && isset($data['value']) && isset($data['id']) ) {
                                    $WP_Messages_autoloaded_meta[$this->table]['message'][$data['id']][$data['key']] = $data['value'];
                                }
                            }
                        }
                        break;
                }
            }

            $this->setCache('autoloaded_meta', $WP_Messages_autoloaded_meta[$this->table]);
        }

        return $this;
    }

    function getListKeys($list, $key, $map=null, $filter=null, $setIndex=null, $handler=null)
    {
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

        if ( $handler && is_callable($handler) ) {
            $data = call_user_func($handler, $data);
        }

        return $data;
    }

    function arrayWithout($list, $values)
    {
        if ( !is_array($values) ) {
            $values = array($values);
        }

        if ( $list ) {
            $list = array_unique($list);
        }

        foreach ( $values as $value ) {
            $i = array_search($value, $list);
            if ( is_numeric($i) && isset($list[$i]) ) {
                unset($list[$i]);
            }
        }

        return $list;
    }

    /** updated cache on meta update **/
    public function postUpdateMeta($type, $meta_key, $id, $meta_value)
    {
        if ( isset( $this->autoload_meta[$type] ) && in_array($meta_key, $this->autoload_meta[$type]) ) {
            global $WP_Messages_autoloaded_meta;
            $WP_Messages_autoloaded_meta[$this->table][$type][$id][$meta_key] = $meta_value;
            $this->setCache('autoloaded_meta', $WP_Messages_autoloaded_meta[$this->table]);           
        } else {
            $this->setCache("meta_{$type}_{$id}_{$meta_key}", $meta_value);
        }
    }

    /** delete cache on meta delete **/
    public function postDeleteMeta($type, $meta_key, $id)
    {
        if ( isset( $this->autoload_meta[$type] ) && in_array($meta_key, $this->autoload_meta[$type]) ) {
            global $WP_Messages_autoloaded_meta;
            if ( isset( $WP_Messages_autoloaded_meta[$this->table][$type][$id][$meta_key] ) ) {
                unset($WP_Messages_autoloaded_meta[$this->table][$type][$id][$meta_key]);
                $this->setCache('autoloaded_meta', $WP_Messages_autoloaded_meta[$this->table]);           
            }
        } else {
            $this->deleteCache("meta_{$type}_{$id}_{$meta_key}", $meta_value);
        }
    }

    public function getUserChatsRaw($user_id)
    {
        $cacheKey = "user_{$user_id}_chats_raw";
        $cache = $this->getCache($cacheKey);

        if ( false !== $cache ) {
            return $cache;
        }

        global $WP_Messages_autoloaded_meta;
        $c = $WP_Messages_autoloaded_meta;
        if ( isset($c[$this->table]) && isset($c[$this->table]['chat']) ) {
            $c = $c[$this->table]['chat'];
        } else {
            return array();
        }

        if ( !$c ) {
            return array();
        }

        $chats = array();

        foreach ( $c as $chat_id => $meta ) {
            if ( empty( $meta['recipients'] ) ) {
                continue;
            }

            if ( !is_array($meta['recipients']) ) {
                $meta['recipients'] = explode(',', $meta['recipients']);
            }

            if ( in_array($user_id, $meta['recipients']) ) {
                $chats[] = $chat_id;
            }
        }

        if ( $chats ) {
            global $wpdb;
            $sql = sprintf(
                "SELECT max(`id`) as `id`,`chat_id` FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` IN ('%s') AND (
                    NOT FIND_IN_SET(%d, `deleted`) OR `deleted` IS NULL
                ) GROUP BY `chat_id` ORDER BY `id` DESC",
                implode("','", $chats),
                $user_id
            );

            $chats = $this->getListKeys($wpdb->get_results($sql), 'chat_id');
        }

        $this->setCache($cacheKey, $chats);

        return $chats;
    }

    public function fakeDelete($bool=false)
    {
        $this->fake_delete = $bool;

        return $this;
    }

    public function deleteChat($id, $current_user=null)
    {
        if ( is_null($current_user) ) {
            $current_user = $this->current_user;
        }

        if ( $this->fake_delete && !$current_user ) 
            return $this;

        if ( $this->fake_delete ) {
            $deletes = (array) $this->get_chat_meta($id, 'delete_scheduled', null);

            if ( $deletes && in_array($current_user, $deletes) )
                return $this;
        }

        $res = $this->scheduleEvent(
            ($this->fake_delete ? 'fake_' : '') . "delete_chat_{$id}" . ($this->fake_delete ? '_user_' . $current_user : '')
        );

        if ( $res instanceof $this && $this->fake_delete ) {
            $deletes = (array) $this->get_chat_meta($id, 'delete_scheduled', null);

            if ( !in_array($current_user, $deletes) ) {
                $deletes[] = $current_user;
                $this->update_chat_meta($id, 'delete_scheduled', $deletes);
            }
        }

        return $this;
    }

    public function deleteMessage($id, $current_user=null)
    {
        if ( is_null($current_user) ) {
            $current_user = $this->current_user;
        }

        if ( $this->fake_delete && !$current_user )
            return $this;

        if ( is_numeric($id) ) {
            $ids = array( intval($id) );
        } else if ( is_array($id) ) {
            $ids = array_map('intval', $id);
        }
        $ids = array_filter($ids, 'trim');

        if ( !$ids )
            return $this;

        $res = $this->scheduleEvent(
            ($this->fake_delete ? 'fake_' : '') . sprintf(
                'delete_message_%s',
                implode(',', $ids)
            ) . ($this->fake_delete ? '_user_' . $current_user : '')
        );

        if ( $res instanceof $this && $this->fake_delete ) {
            foreach ( $ids as $id ) {
                $deletes = (array) $this->get_message_meta($id, 'delete_scheduled', null);

                if ( !in_array($current_user, $deletes) ) {
                    $deletes[] = $current_user;
                    $deletes = array_filter($deletes, 'trim');
                    $this->update_message_meta($id, 'delete_scheduled', $deletes);
                }
            }
        }

        return $this;
    }    

    public function scheduleEvent($tagname, $seconds=null)
    {
        $i = rand($this->events_uniques_min_id,$this->events_uniques_max_id);
        $tag = "WP_Messages_schedules_{$i}";

        if ( !intval($seconds) ) {
            $seconds = $this->event_time_add;
        }

        if( !wp_next_scheduled( $tag ) ) {
            wp_schedule_event( time() + $seconds, 'WP_Messages_event_interval', $tag );  
        }

        $opt = "WP_Messages_{$this->table}_schedules_{$i}";
        $schedules = (array) get_site_option($opt, null);
        $schedules[] = $tagname;
        $schedules = array_filter($schedules);
        $schedules = array_unique($schedules);
        // save
        update_site_option($opt, $schedules);

        do_action("WP_Messages_event_scheduled", $tagname, $i);
        do_action("WP_Messages_event_scheduled_{$tagname}", $i);

        return $this;
    }

    function pushEventInterval($vals)
    {
        return array_merge(array(
            'WP_Messages_event_interval' => array(
                'interval' => $this->event_interval,
                'display' => __('WP Messages interval', $this->text_domain)
            ),
            'WP_Messages_weekly' => array(
                'interval' => WEEK_IN_SECONDS,
                'display' => __('Weekly', $this->text_domain)
            )
        ), $vals);
    }

    public function events()
    {
        $tag = current_filter();
        $id = (int) str_replace('WP_Messages_schedules_', '', $tag);

        if ( !$id )
            return;

        $opt = "WP_Messages_{$this->table}_schedules_{$id}";
        $schedules = (array) get_site_option($opt, null);

        if ( !$schedules ) {
            delete_site_option($opt);
        } else {
            foreach ( $schedules as $id ) {
                // core process
                $this->processEvent($id);
                // trigger hook
                do_action("WP_Messages_process_event", $id);
                // another identical hook
                do_action("WP_Messages_process_event_{$id}");
            }
        }

        // delete
        delete_site_option($opt);

        // clear out
        wp_unschedule_event( wp_next_scheduled( $tag ), $tag );
    }

    public function eventPatterns()
    {
        $patterns = array(
            '/^delete_chat_([^(\z|\s|_)]*)$/i' => array($this, 'evtDeleteChat'),
            '/^fake_delete_chat_(.*?)_user_([^(\z|\s|_)]*)$/i' => array($this, 'evtFakeDeleteChat'),
            '/^delete_message_([^(\z|\s|_)]*)$/i' => array($this, 'evtDeleteMessage'),
            '/^fake_delete_message_([^(\z|\s|_)]*)_user_([0-9]+)$/i' => array($this, 'evtFakeDeleteMessage'),
            '/^notify_id_([0-9]+)_chat_id_(.*?)_sender_([0-9]+)_recipients_(.*)/i' => array($this, 'newMessageNotify'),
            '/^delete_orphaned_messages_from_chat_([^(\z|\s|_)]*)$/i' => array($this, 'cleanupOrphanedMessages'),
            '/^delete_orphaned_messages_from_chats_([^(\z|\s|_)]*)$/i' => array($this, 'bulkCleanupOrphanedMessages'),
        );

        if ( $this->event_patterns ) {
            $patterns = wp_parse_args($this->event_patterns, $patterns);
        }

        return $patterns;
    }

    public function processEvent($id)
    {
        $patterns = $this->eventPatterns();

        if ( !$patterns )
            return;

        foreach ( $patterns as $p=>$c ) {
            if ( preg_match($p, $id) ) {
                preg_match($p, $id, $args);
                if ( isset($args[1]) ) {
                    $args = array_slice($args, 1);
                }
                if ( is_callable($c) ) {
                    call_user_func_array($c, $args);
                }
            }
        }
    }

    public function evtDeleteChat($id)
    {
        global $wpdb;
        // get IDs for meta
        $ids = $this->getListKeys($wpdb->get_results($wpdb->prepare(
            "SELECT `id` FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` = %s",
            $id
        )), 'id', 'intval');
        // delete messages
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` = %s",
            $id
        ));

        if ( $deleted ) {
            // get recipients
            $recipients = $this->getChatRecipients($id);
            // delete meta
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->wpdb_prefix}{$this->meta_table} WHERE `chat_id` = %s",
                $id
            ));

            if ( $ids ) {
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$this->wpdb_prefix}{$this->meta_table} WHERE `id` IN (%s)",
                    implode(",", $ids)
                ));
            }

            // now delete the cache
            if ( $recipients ) {
                foreach ( array_merge($recipients, array($this->current_user)) as $user_id ) {
                    // 1
                    $this->deleteCache("last_chat_message_{$id}_{$user_id}");
                    // 2
                    $this->deleteCache("inbox_ids_{$id}_{$user_id}");
                    // 3
                    $unread_cache = $this->getCache("unread_chats_{$user_id}");
                    if ( is_array($unread_cache) && in_array($id, $unread_cache) ) {
                        $i = array_search($id, $unread_cache);
                        if ( isset($unread_cache[$i]) ) {
                            unset($unread_cache[$i]);
                            $this->setCache("unread_chats_{$user_id}", $unread_cache);
                        }
                    }
                    // 4
                    $raw_cache = $this->getCache("user_{$user_id}_chats_raw");
                    if ( is_array($raw_cache) && in_array($id, $raw_cache) ) {
                        $i = array_search($id, $raw_cache);
                        if ( isset($raw_cache[$i]) ) {
                            unset($raw_cache[$i]);
                            $this->setCache("user_{$user_id}_chats_raw", $raw_cache);
                        }
                    }
                    // troubleshooting: remove uid from autoloaded recipients meta

                    // user_{$user_id}_total_messages
                    // user_{$user_id}_total_messages_chat_$id
                }
            }
        
            // a hook
            do_action("WP_Messages_{$this->table}_deleted_chat", $id);
            do_action("WP_Messages_{$this->table}_deleted_chat_{$id}");
        } else {
            do_action("WP_Messages_{$this->table}_delete_chat_fail", $id);
            do_action("WP_Messages_{$this->table}_delete_chat_fail_{$id}");
        }
    }

    public function evtFakeDeleteChat($id, $user_id)
    {
        $user_id = (int) $user_id;

        if ( !$user_id )
            return;

        $deletes = (array) $this->get_chat_meta($id, 'delete_scheduled', null);

        if ( !$deletes || !in_array($user_id, $deletes) )
            return;
                
        if ( $deletes && in_array($user_id, $deletes) ) {
            $i = array_search($user_id, $deletes);
            if ( is_numeric($i) && isset($deletes[$i]) ) {
                unset($deletes[$i]);

                if ( $deletes ) {
                    $this->update_chat_meta($id, 'delete_scheduled', $deletes);
                } else {
                    $this->delete_chat_meta($id, 'delete_scheduled');
                }
            }
        }

        // do it
        global $wpdb;
        $d = $wpdb->query($wpdb->prepare(
            "UPDATE {$this->wpdb_prefix}{$this->table} SET `deleted` = CONCAT_WS(',', `deleted`, %d) WHERE `chat_id` = %s AND (NOT FIND_IN_SET(%d, `deleted`) OR `deleted` IS NULL)",
            $user_id,
            $id,
            $user_id
        ));

        if ( !$d )
            return;

        /* flush caches */

        // 1
        $this->deleteCache("last_chat_message_{$id}_{$user_id}");

        // 2
        $this->deleteCache("inbox_ids_{$id}_{$user_id}");

        // 3
        $chats_raw_cacheKey = "user_{$user_id}_chats_raw";
        $chats_raw_cache = $this->getCache($chats_raw_cacheKey);

        if ( false !== $chats_raw_cache && is_array($chats_raw_cache) ) {
            $i = array_search($id, $chats_raw_cache);
            // unset old ID for an accurate sorting
            if ( isset($chats_raw_cache[$i]) ) {
                unset($chats_raw_cache[$i]);
            }
            // update cache
            $this->setCache($chats_raw_cacheKey, $chats_raw_cache);
        }

        // optimize
        $this->scheduleEvent("delete_orphaned_messages_from_chat_{$id}");

        // user_{$user_id}_total_messages
        // user_{$user_id}_total_messages_chat_$id
    }

    public function evtDeleteMessage($id)
    {
        global $wpdb;

        if ( !is_numeric($id) && strpos($id, ',') ) {
            $ids = array_map('intval', explode(',', $id));
        } else {
            $ids = array( (int) $id );
        }

        if ( !$ids ) {
            return;
        }

        $wpdb->query(sprintf(
            "DELETE FROM {$this->wpdb_prefix}{$this->table} WHERE `id` IN (%s)",
            implode(',', $ids)
        ));

        $wpdb->query(sprintf(
            "DELETE FROM {$this->wpdb_prefix}{$this->meta_table} WHERE `id` IN (%s)",
            implode(',', $ids)
        ));

        foreach ( $ids as $id ) {
            $this->deleteCache("message_{$id}");
        }
        # todo: delete caches

        // user_{$user_id}_total_messages
        // user_{$user_id}_total_messages_chat_$id
    }

    public function evtFakeDeleteMessage($id, $user_id)
    {
        global $wpdb;

        if ( !is_numeric($id) && strpos($id, ',') ) {
            $ids = array_map('intval', explode(',', $id));
        } else {
            $ids = array( (int) $id );
        }

        if ( !$ids ) {
            return;
        } else {
            $ids = array_values($ids);
        }

        $user_id = (int) $user_id;

        if ( !$user_id )
            return;

        foreach ( $ids as $id ) {
            $deleters = (array) $this->get_message_meta($id, 'delete_scheduled', null);
                
            if ( $deleters && in_array($user_id, $deleters) ) {
                $i = array_search($user_id, $deleters);
                if ( is_numeric($i) && isset($deleters[$i]) ) {
                    unset($deleters[$i]);

                    if ( $deleters ) {
                        $this->update_message_meta($id, 'delete_scheduled', $deleters);
                    } else {
                        $this->delete_message_meta($id, 'delete_scheduled');
                    }
                }
            }
        }

        $sql = str_replace('ID_IN', implode(',', $ids), $wpdb->prepare(
            "UPDATE {$this->wpdb_prefix}{$this->table} SET `deleted` = CONCAT_WS(',', `deleted`, %d) WHERE `id` IN (ID_IN) AND (
                NOT FIND_IN_SET(%d, `deleted`) OR `deleted` IS NULL
            )",
            $user_id,
            $user_id
        ));

        if ( !$sql )
            return;

        if ( $wpdb->query($sql) ) {
            $chat_id = $this->getMessageChatId($ids[0]);
            
            if ( $chat_id ) {
                $ids_cacheKey = "inbox_ids_{$chat_id}_{$user_id}";
                $ids_cached = $this->getCache($ids_cacheKey);
                $ids_cached_count = $ids_cached && is_array($ids_cached) ? count($ids_cached) : 0;
            }

            foreach ( $ids as $id ) {
                $message_cache_key = "message_{$id}";
                $message_cached = $this->getCache($message_cache_key);

                if ( false !== $message_cached && isset($message_cached['deleted']) ) {
                    if ( !in_array($user_id, $message_cached['deleted']) ) {
                        $message_cached['deleted'] = $user_id;
                        $this->setCache($message_cache_key, $message_cached);
                    }
                }

                if ( $chat_id && $ids_cached && is_array($ids_cached) ) {
                    $id_search = array_search($id, $ids_cached);
                    if ( is_numeric($id_search) ) {
                        unset($ids_cached[$id_search]);
                    }
                }

                if ( $chat_id ) {
                    $lmessage_cache_key = "last_chat_message_{$chat_id}_{$user_id}";
                    $lmessage_cached = $this->getCache($lmessage_cache_key);

                    if ( false !== $lmessage_cached && isset($lmessage_cached['id']) && in_array($lmessage_cached['id'], $ids) ) {
                        $this->deleteCache($lmessage_cache_key);
                    }
                }
            }

            if ( isset($ids_cached) && false !== $ids_cached) {
                $this->setCache($ids_cacheKey, array_unique($ids_cached));
            }
        }

        // user_{$user_id}_total_messages
        // user_{$user_id}_total_messages_chat_$id
    }

    public function setCache($key, $data, $group=null)
    {
        $key = "WP_Messages_{$this->table}_{$key}";
        return wp_cache_set($key, $data, $group);
    }

    public function deleteCache($key, $group=null)
    {
        $key = "WP_Messages_{$this->table}_{$key}";
        return wp_cache_delete($key, $group);
    }

    public function getCache($key, $group=null)
    {
        $key = "WP_Messages_{$this->table}_{$key}";
        return wp_cache_get($key, $group);
    }

    public function getMessage($id)
    {
        $cacheKey = "message_{$id}";
        $cache = $this->getCache($cacheKey);

        if ( false !== $cache ) {
            return $cache;
        }

        global $wpdb;
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->wpdb_prefix}{$this->table} WHERE `id` = %d LIMIT 1",
            $id
        ));

        if ( isset( $data[0] ) ) {
            $data = $this->getMessagePrepare((array) $data[0]);
        }

        $this->setCache($cacheKey, $data);

        return $data;
    }

    public function getMessageChatId($id)
    {
        $data = $this->getMessage($id);

        return isset($data['chat_id']) ? $data['chat_id'] : null;
    }

    function getMessagePrepare($data) {
        if ( !is_array($data) ) {
            $data = (array) $data;
        }

        if ( isset( $data['id'] ) ) {
            $data['id'] = (int) $data['id'];
        } else {
            $data['id'] = 0;
        }

        if ( isset( $data['sender'] ) ) {
            $data['sender'] = (int) $data['sender'];
        } else {
            $data['sender'] = 0;
        }

        if ( isset( $data['date'] ) ) {
            $data['date'] = (int) $data['date'];
        } else {
            $data['date'] = 0;
        }

        if ( isset( $data['deleted'] ) && $data['deleted'] ) {
            $data['deleted'] = explode(',', $data['deleted']);
            $data['deleted'] = array_filter($data['deleted']);
            $data['deleted'] = array_map('intval', $data['deleted']);
        } else {
            $data['deleted'] = array();
        }

        return $data;
    }

    public function markRead($chat_id=null, $user_id=null)
    {
        if ( !$chat_id && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !$chat_id )
            return $this;

        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$user_id || !$this->isChatRecipient($user_id, $chat_id) )
            return $this;

        #todo: cleanup old no-longer recipients
        #todo: unreadChats clean

        $unread = $this->getChatUnreads($chat_id);
        $i = array_search($user_id, $unread);

        if ( is_numeric($i) && isset($unread[$i]) ) {
            unset($unread[$i]);

            if ( $unread ) {
                $this->update_chat_meta($chat_id, 'unread', $unread);
            } else {
                $this->delete_chat_meta($chat_id, 'unread');                
            }
        }

        $count = (array) $this->get_chat_meta($chat_id, 'unread_count', null);

        if ( empty($count) || !is_array($count) || !array_filter($count) ) {
            return $this;
        }

        if ( isset($count[$user_id]) ) {
            unset($count[$user_id]);

            if ( $count ) {
                $this->update_chat_meta($chat_id, 'unread_count', $count);
            } else {
                $this->delete_chat_meta($chat_id, 'unread_count');
            }
        }

        return $this;
    }

    public function getChatUnreads($chat_id=null)
    {
        if ( !$chat_id && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !$chat_id )
            return array();

        return (array) $this->get_chat_meta($chat_id, 'unread', null);
    }

    public function isUnread($chat_id=null, $user_id=null)
    {
        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$user_id )
            return null;

        return in_array($user_id, $this->getChatUnreads($chat_id));
    }

    public function markUnread($chat_id=null, $user_id=null)
    {
        if ( !$chat_id && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !$chat_id )
            return $this;

        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$user_id || !$this->isChatRecipient($user_id, $chat_id) )
            return $this;

        #todo: cleanup old no-longer recipients

        $unread = $this->getChatUnreads($chat_id);

        if ( !in_array($user_id, $unread) ) {
            $unread[] = $user_id;
            $this->update_chat_meta($chat_id, 'unread', $unread);
        }

        return $this;
    }

    public function getLastChatMessage($chat_id=null,$user_id=null)
    {
        if ( !$chat_id && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$chat_id ) {
            return array();
        }

        global $wpdb;
        
        if ( $this->search_term ) {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$this->wpdb_prefix}{$this->table} WHERE `message` LIKE %s AND `chat_id` = %s AND (NOT FIND_IN_SET('%s', `deleted`) OR `deleted` IS NULL) ORDER BY id DESC LIMIT 1",
                $this->prepareSearchTerm(true, $this->search_term),
                $chat_id,
                $user_id
            );

            $data = $wpdb->get_results($sql);

            if ( isset( $data[0] ) ) {
                $data = $this->getMessagePrepare((array) $data[0]);
            }
        } else {
            $cacheKey = "last_chat_message_{$chat_id}_{$user_id}";
            $cached = $this->getCache($cacheKey);

            if ( false !== $cached ) {
                return $cached;
            }

            $sql = $wpdb->prepare(
                "SELECT * FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` = %s AND (NOT FIND_IN_SET('%s', `deleted`) OR `deleted` IS NULL) ORDER BY id DESC LIMIT 1",
                $chat_id,
                $user_id
            );

            $data = $wpdb->get_results($sql);

            if ( isset( $data[0] ) ) {
                $data = $this->getMessagePrepare((array) $data[0]);
            }

            $this->setCache($cacheKey, $data);
        }

        return $data;
    }

    public function search($term)
    {
        $this->search_term = $term ? "%{$term}%" : null;

        return $this;
    }

    public function inboxMessages($chat_id=null, $user_id=null)
    {
        if ( !$chat_id && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !$chat_id ) {
            return array();
        }

        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( $this->search_term ) {
            global $wpdb;

            $restSql = $wpdb->prepare(
                "FROM {$this->wpdb_prefix}{$this->table} WHERE `message` LIKE %s AND `chat_id` = %s AND (NOT FIND_IN_SET('%s', `deleted`) OR `deleted` IS NULL) ORDER BY `id` DESC",
                $this->prepareSearchTerm(true, $this->search_term),
                $chat_id,
                $user_id
            );

            $total = (int) $wpdb->get_var("SELECT COUNT(*) $restSql");

            $this->current_page = (int) $this->current_page;

            if ( !$this->current_page ) {
                $this->current_page = 1;
            }

            $start = 0;

            for ($i=2; $i<=$this->current_page; $i++) {
                $start += $this->per_page;
            }

            $restSql .= " LIMIT {$start},{$this->per_page}";

            $data = $wpdb->get_results("SELECT * $restSql");

            if ( isset( $data[0] ) ) {
                foreach ( $data as $i => $d ) {
                    $data[$i] = $this->getMessagePrepare($data[$i]);
                    // cache it, while we're at it
                    $cacheKey = "message_{$data[$i]['id']}";
                    $this->setCache($cacheKey, $data[$i]);
                }
            } else {
                $data = array();
            }

            $this->set('paginate_links_args', array(
                'base'      => add_query_arg( $this->page_arg, '%#%' ),
                'format'    => '',
                'total'     => ceil( $total / $this->per_page ),
                'current'   => $this->current_page,
                'prev_text' => _x('&larr;', 'Pagination previous', $this->text_domain),
                'next_text' => _x('&rarr;', 'Pagination next', $this->text_domain),
            ));

            $this->set('inbox_messages', $data);

        } else {
            $ids = $this->getInboxIds($chat_id, $user_id);

            $this->paginate($ids);
            $curr_ids = $this->pagi_current_data;

            if ( $curr_ids ) {
                $getCacheCtx = array($this, 'getCache');
                $curr_ids = array_map(function($id) use ($getCacheCtx){
                    if ( $cached = call_user_func($getCacheCtx, "message_{$id}") ) {
                        return $cached;
                    }
                    return $id;
                }, $curr_ids);

                $uncached = array();
                foreach ( $curr_ids as $i=>$_d ) {
                    if ( !is_object($_d) && is_numeric($_d) ) {
                        $uncached[$i] = (int) $_d;
                    }
                }

                if ( $uncached ) {
                    global $wpdb;
                    $data = $wpdb->get_results(sprintf(
                        "SELECT * FROM {$this->wpdb_prefix}{$this->table} WHERE `id` IN (%s) ORDER BY `id` DESC",
                        implode(",", $uncached)
                    ));
                    $uncachedData = array();
                    if ( isset( $data[0] ) ) {
                        foreach ( $data as $i=>$d ) {
                            $data[$i] = $this->getMessagePrepare($data[$i]);
                            $uncachedData[$data[$i]['id']] = $data[$i];
                            // cache it, while we're at it
                            $cacheKey = "message_{$data[$i]['id']}";
                            $this->setCache($cacheKey, $data[$i]);
                        }
                    } else {
                        $data = array();
                    }

                    if ( $uncachedData ) {
                        foreach ( $curr_ids as $i=>$id ) {
                            if (is_numeric($id) && isset($uncachedData[$id])) {
                                $curr_ids[$i] = $uncachedData[$id];
                            }
                        }
                    }
                }

                foreach ( $curr_ids as $i=>$d ) {
                    if ( !isset($d['id']) ) {
                        unset($curr_ids[$i]);
                    }
                }

                $data = $curr_ids;
            } else {
                $data = array();
            }

            $this->set('paginate_links_args', array(
                'base'      => add_query_arg( $this->page_arg, '%#%' ),
                'format'    => '',
                'total'     => ceil( count($ids) / $this->per_page ),
                'current'   => $this->current_page,
                'prev_text' => _x('&larr;', 'Pagination previous', $this->text_domain),
                'next_text' => _x('&rarr;', 'Pagination next', $this->text_domain)
            ));

            $this->set('inbox_messages', $data);
        }

        return $this;
    }

    function getInboxIds($chat_id=null, $user_id=null)
    {
        if ( !$chat_id && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$chat_id || !$user_id ) {
            return array();
        }

        $cacheKey = "inbox_ids_{$chat_id}_{$user_id}";
        $cached = $this->getCache($cacheKey);

        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT `id` FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` = %s AND (NOT FIND_IN_SET('%s', `deleted`) OR `deleted` IS NULL) ORDER BY `id` DESC",
            $chat_id,
            $user_id
        ));

        if ( isset($data[0]) ) {
            $data = $this->getListKeys($data, 'id', 'intval');
        } else {
            $data = array();
        }

        $this->setCache($cacheKey, $data);

        return $data;
    }

    public function paginate($data)
    {
        if ( !$this->per_page || count($data) <= $this->per_page || !$data ) {
            /**
              * Set pagi helpers
              */
            $this->set(array(
                //'pagi_prev_data' => null,
                'pagi_current_data' => $data,
                //'pagi_next_data' => null,
                //'last_page' => 1
            ));

            return $data;
        }

        $chunks = array_chunk($data, $this->per_page);
        // raise index by 1, for better sake of pagination
        $chunks = array_combine(range(1, count($chunks)), array_values($chunks));

        /**
          * Set pagi helpers
          */
        $this->set(array(
            //'pagi_prev_data' => isset($chunks[$this->current_page-1]) ? $chunks[$this->current_page-1] : null,
            'pagi_current_data' => isset($chunks[$this->current_page]) ? $chunks[$this->current_page] : null,
            //'pagi_next_data' => isset($chunks[$this->current_page+1]) ? $chunks[$this->current_page+1] : null,
            //'last_page' => count($chunks)
        ));

        return $chunks;
    }

    public function chats($user_id=null)
    {
        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$user_id ) {
            return array();
        }

        $ids = $this->getUserChatsRaw($user_id);

        if ( $this->search_term ) {
            global $wpdb;

            $this->current_page = (int) $this->current_page;

            if ( !$this->current_page ) {
                $this->current_page = 1;
            }

            $start = 0;

            for ($i=2; $i<=$this->current_page; $i++) {
                $start += $this->per_page;
            }

            $sql = $wpdb->prepare(sprintf(
                "FROM {$this->wpdb_prefix}{$this->table} WHERE `id` IN (
                    SELECT max(`id`) as `id` FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` IN (
                        SELECT DISTINCT `chat_id` FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` IN ('%s') AND (
                            NOT FIND_IN_SET(%s, `deleted`) OR `deleted` IS NULL
                        )
                    ) AND `message` LIKE %s GROUP BY `chat_id`
                ) ORDER BY `id` DESC",
                implode("','", $ids),
                '%d',
                '%s'
            ), $user_id, $this->prepareSearchTerm(true, $this->search_term));

            $sql = apply_filters("WP_Messages_{$this->table}_search_chats_sql", $sql, $this->search_term, $user_id);

            $search_count = (int) $wpdb->get_var("SELECT COUNT(*) $sql");
            $data = $wpdb->get_results("SELECT * $sql LIMIT {$start},{$this->per_page}");

            if ( isset( $data[0] ) ) {
                foreach ( $data as $i=>$d) {
                    $data[$i] = $this->getMessagePrepare((array) $data[$i]);
                    /** set cache while we're at it **/
                    $cacheKey = "message_{$data[$i]['id']}";
                    $cache = $this->getCache($cacheKey);
                    $this->setCache($cacheKey, $data[$i]);
                }
            } else {
                $data = array();
            }

            $chats = $data;
        } else {
            $chats = array();
            $this->paginate($ids);
            $curr_ids = (array) $this->get('pagi_current_data');

            if ( $curr_ids ) {
                foreach ( $curr_ids as $i=>$id ) {
                    $chats[$i] = $this->getLastChatMessage($id, $user_id);
                }
            }
        }

        $this->set('paginate_links_args', array(
            'base'      => add_query_arg( $this->page_arg, '%#%' ),
            'format'    => '',
            'total'     => ceil( (isset($search_count) ? $search_count : count($ids)) / $this->per_page ),
            'current'   => $this->current_page,
            'prev_text' => _x('&larr;', 'Pagination previous', $this->text_domain),
            'next_text' => _x('&rarr;', 'Pagination next', $this->text_domain),
        ));

        $this->set('chats', array_values($chats));

        return $this;
    }

    public function unreadChats($user_id=null, $sort=true, $withData=true)
    {
        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$user_id ) {
            return array();
        }

        global $wpdb, $WP_Messages_autoloaded_meta;

        $user_id = $this->current_user;
        $m = isset($WP_Messages_autoloaded_meta[$this->table]['chat']) ? $WP_Messages_autoloaded_meta[$this->table]['chat'] : array();

        $chats = array();
        if ( $m ) {
            foreach ( $m as $chat_id => $d ) {
                if ( !isset($d['unread']) || empty($d['unread']) )
                    continue;

                $d['unread'] = explode(',', $d['unread']);
                $d['unread'] = array_map('intval', $d['unread']);

                if ( $d['unread'] && in_array($user_id, $d['unread']) ) {
                    $chats[] = $chat_id;
                }
            }
        }

        if ( $chats && $sort ) {
            $cacheKey = "unread_chats_{$user_id}";
            $cached = $this->getCache($cacheKey);

            if ( false !== $cached ) {
                $chats = $cached;
            } else {
                // sorted
                $chats = $this->getListKeys($wpdb->get_results(sprintf(
                    "SELECT DISTINCT `chat_id` FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` IN ('%s')",
                    implode("','", $chats)
                )), 'chat_id');

                $this->setCache($cacheKey, $chats);
            }
        }

        if ( $chats ) {
            $this->paginate($chats);
            $chats = $this->set('unread_chats', (array) $this->get('pagi_current_data'))->get('unread_chats');

            if ( $chats && $withData ) {
                $chats = array_map(array($this, 'getLastChatMessage'), $chats);
                $this->set('unread_chats', $chats);
            }
        }

        $this->set('paginate_links_args', array(
            'base'      => add_query_arg( $this->page_arg, '%#%' ),
            'format'    => '',
            'total'     => ceil( count($chats) / $this->per_page ),
            'current'   => $this->current_page,
            'prev_text' => _x('&larr;', 'Pagination previous', $this->text_domain),
            'next_text' => _x('&rarr;', 'Pagination next', $this->text_domain),
        ));

        return $this;
    }

    public function notify($bool=true)
    {
        $this->email_notifications = (bool) $bool;

        return $this;
    }

    public function postSend($args)
    {
        /** first things first, schedule notifications **/
        if ( $this->email_notifications ) {
            $this->scheduleEvent(sprintf(
                'notify_id_%s_chat_id_%s_sender_%d_recipients_%s',
                $args['id'],
                $args['chat_id'],
                $args['sender'],
                implode(',', $args['recipients'])
            ));
        }
        /** add unread to recipients **/ # TODO: unset sender from these
        $this->update_chat_meta($args['chat_id'], 'unread', $this->arrayWithout($args['recipients'], $args['sender']));

        /** update unread count values **/
        $count = (array) $this->get_chat_meta($args['chat_id'], 'unread_count', null);
        // add values
        foreach ( $args['recipients'] as $user_id ) {
            if ( $user_id == $args['sender'] )
                continue;
            
            if ( !isset( $count[$user_id] ) ) {
                $count[$user_id] = 0;
            }
            $count[$user_id]++;
        }
        $this->update_chat_meta($args['chat_id'], 'unread_count', $count);

        /** last, clear out caches **/
        foreach ( array_unique(array_merge($args['recipients'], array($args['sender']))) as $user_id ) {
            // 1
            $last_message_cacheKey = "last_chat_message_{$args['chat_id']}_{$user_id}";
            $this->setCache($last_message_cacheKey, $this->getMessage($args['id']));

            // 2
            $inbox_ids_cacheKey = "inbox_ids_{$args['chat_id']}_{$user_id}";
            $inbox_ids = $this->getCache($inbox_ids_cacheKey);

            if ( false !== $inbox_ids && is_array($inbox_ids) ) {
                $inbox_ids = array_merge(array($args['id']), $inbox_ids);
                $this->setCache($inbox_ids_cacheKey, array_unique($inbox_ids));
            }

            // 3
            $unread_cache = $this->getCache("unread_chats_{$user_id}");
            if ( is_array($unread_cache) && !in_array($args['chat_id'], $unread_cache) ) {
                $unread_cache[] = $args['chat_id'];
                $this->setCache("unread_chats_{$user_id}", $unread_cache);
            }

            // 4
            $chats_raw_cacheKey = "user_{$user_id}_chats_raw";
            $chats_raw_cache = $this->getCache($chats_raw_cacheKey);

            if ( false !== $chats_raw_cache && is_array($chats_raw_cache) ) {
                $chats_raw_cache = array_unique($chats_raw_cache);
                $i = array_search($args['chat_id'], $chats_raw_cache);
                // unset old ID for an accurate sorting
                if ( is_numeric($i) ) {
                    unset($chats_raw_cache[$i]);
                }
                // append new chat ID
                $chats_raw_cache = array_merge(array($args['chat_id']), $chats_raw_cache);
                // update cache
                $this->setCache($chats_raw_cacheKey, $chats_raw_cache);
            }

            // 5
            $chat_count_cacheKey = "user_{$user_id}_total_messages_chat_{$args['chat_id']}";
            $chat_count_cache = $this->getCache($chat_count_cacheKey);
            $chats_count_cacheKey = "user_{$user_id}_total_messages";
            $chats_count_cache = $this->getCache($chats_count_cacheKey);

            if ( false !== $chat_count_cache ) {
                $chat_count_cache = (int) $chat_count_cache;
                $chat_count_cache++;
                $this->setCache($chat_count_cacheKey, $chat_count_cache);
            }

            if ( false !== $chats_count_cache ) {
                $chats_count_cache = (int) $chats_count_cache;
                $chats_count_cache++;
                $this->setCache($chats_count_cacheKey, $chats_count_cache);
            }
        }
    }

    public function newMessageNotify($id, $chat_id, $sender, $recipients)
    {
        $id = (int) $id;
        $sender = (int) $sender;
        $recipients = explode(',', $recipients);
        $recipients = array_map('intval', $recipients);
        $recipients = array_filter($recipients, 'trim');
        $recipients = array_filter($recipients, array($this, 'isValidUser'));

        if ( in_array($sender, $recipients) ) {
            $i = array_search($sender, $recipients);
            if ( is_numeric($i) && isset($recipients[$i]) ) {
                unset( $recipients[$i] );
            }
        }

        if ( !$recipients )
            return;

        return $this->newMessageNotifySend($id, $chat_id, $sender, $recipients);
    }

    public function newMessageNotifySend($id, $chat_id, $sender, $recipients)
    {
        $m = $this->getMessage($id);

        if ( empty($m->id) || empty($m->message) )
            return;

        /**
          * Copy this method to your custom class that extends
          * \WP_Messages and format your emails, loop through
          * $recipients(or use headers for multiple send)
          * and send out notifications with wp_mail or
          * your favorite mail methods.
          */
    }

    public function getChatUnreadCount($chat_id=null, $user_id=null)
    {
        if ( !$chat_id && $this->chat_id ) {
            $chat_id = $this->chat_id;
        }

        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$chat_id || !$user_id ) {
            return;
        }

        $count = (array) $this->get_chat_meta($chat_id, 'unread_count', null);

        if ( empty($count) || !is_array($count) ) {
            return 0;
        }

        return isset($count[$user_id]) ? (int) $count[$user_id] : 0;
    }

    public function cleanupOrphanedMessages($chat_id=null, $recipients=null)
    {
        if ( !$chat_id ) {
            if ( $this->chat_id ) {
                $chat_id = $this->chat_id;
            } else {
                return;
            }
        }

        if ( !$recipients || !is_array($recipients) ) {
            $recipients = $this->getChatRecipients($chat_id);
        }

        if ( !$recipients ) {
            return;
        }

        global $wpdb;
        $where = $wpdb->prepare("WHERE `chat_id` = %s", $chat_id);
        foreach ( $recipients as $uid ) {
            $where .= " AND FIND_IN_SET({$uid}, `deleted`)";
        }

        $ids = $this->getListKeys(
            $wpdb->get_results("SELECT `id` FROM {$this->wpdb_prefix}{$this->table} $where"),
            'id',
            'intval'
        );

        if ( !$ids ) {
            return;
        }

        // delete messages
        $d = $wpdb->query(sprintf(
            "DELETE FROM {$this->wpdb_prefix}{$this->table} WHERE `id` IN (%s)",
            implode(',', $ids)
        ));

        // delete messages meta
        $d = $wpdb->query(sprintf(
            "DELETE FROM {$this->wpdb_prefix}{$this->meta_table} WHERE `id` IN (%s)",
            implode(',', $ids)
        ));

        # todo: delete caches and all
    }

    public function bulkCleanupOrphanedMessages($ids)
    {
        if ( !is_array($ids) ) {
            $ids = explode(',', $ids);
            $ids = array_filter($ids);
        }

        foreach ( $ids as $chat_id ) {
            $this->cleanupOrphanedMessages($chat_id);
        }
    }

    public function getSharedChats($user_a, $user_b)
    {
        global $WP_Messages_autoloaded_meta;
        $m = $WP_Messages_autoloaded_meta;

        if ( !isset( $m[$this->table] ) || !isset($m[$this->table]['chat']) )
            return array();

        $m = $m[$this->table]['chat'];

        if ( empty($m) )
            return array();

        $chats = array();

        foreach ( $m as $chat_id => $meta ) {
            if ( empty($meta['recipients']) )
                continue;
        
            $r = explode(',', $meta['recipients']);
            $r = array_map('intval', $r);

            if ( empty($r) )
                continue;

            if ( in_array($user_a, $r) && in_array($user_b, $r) ) {
                $chats[] = $chat_id;
            }
        }

        return $chats;
    }

    public function getPrivateSharedChat($user_a, $user_b)
    {
        global $WP_Messages_autoloaded_meta;
        $m = $WP_Messages_autoloaded_meta;

        if ( !isset( $m[$this->table] ) || !isset($m[$this->table]['chat']) )
            return;

        $m = $m[$this->table]['chat'];

        if ( empty($m) )
            return;

        foreach ( $m as $chat_id => $meta ) {
            if ( empty($meta['recipients']) )
                continue;

            if ( !is_array($meta['recipients']) )
                $r = explode(',', $meta['recipients']);
            
            $r = array_map('intval', $r);

            if ( empty($r) )
                continue;

            if ( $r == array($user_a, $user_b) || $r == array($user_b, $user_a) )
                return $chat_id;
        }
    }

    /**
      * Delete older messages based on time difference
      *
      * @param int $interval older than X seconds
      */
    public function cleanupOlderMessages($interval=null)
    {
        if ( !$interval || !intval($interval) ) {
            $interval = $this->older_messages_cleanup_interval;
        }

        if ( !intval($interval) )
            return;

        global $wpdb;

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT `id`,`chat_id` FROM {$this->wpdb_prefix}{$this->table} WHERE `date` < (UNIX_TIMESTAMP() - %d)",
            $interval
        ));

        if ( !$items )
            return;

        $ids = $this->getListKeys($items, 'id', 'intval');
        $chats = $this->getListKeys($items, 'chat_id', null, null, null, 'array_unique');

        if ( !$ids )
            return;

        // delete messages
        $d = $wpdb->query(sprintf(
            "DELETE FROM {$this->wpdb_prefix}{$this->table} WHERE `id` IN (%s)",
            implode(',', $ids)
        ));

        // delete messages meta
        $d = $wpdb->query(sprintf(
            "DELETE FROM {$this->wpdb_prefix}{$this->meta_table} WHERE `id` IN (%s)",
            implode(',', $ids)
        ));

        if ( $chats ) {
            foreach ( $chats as $chat_id ) {
                $r = $this->getChatRecipients($chat_id);
                if ( $r && is_array($r) ) {
                    foreach ( $r as $uid ) {
                        $this->deleteCache("last_chat_message_{$chat_id}_{$uid}");
                        $this->deleteCache("inbox_ids_{$chat_id}_{$uid}");
                        $this->deleteCache("unread_chats_{$uid}");
                        $this->deleteCache("user_{$uid}_chats_raw");
                    }
                }
            }
        }

        foreach ( $ids as $id ) {
            $this->deleteCache("message_{$id}");
        }

        return $items;
    }

    public function cleanupOrphanedMessagesSchedule()
    {
        global $wpdb;

        $chats = $this->getListKeys(
            $wpdb->get_results("SELECT DISTINCT `chat_id` FROM {$this->wpdb_prefix}{$this->table}"),
            'chat_id'
        );

        if ( !$chats )
            return;

        if ( intval($this->orphaned_messages_cleanup_chats_per_batch) ) {
            $chats = array_chunk($chats, $this->orphaned_messages_cleanup_chats_per_batch);
        } else {
            $chats = array($chats);
        }

        if ( empty($chats[0]) )
            return;

        $seconds = $this->event_time_add;

        foreach ( $chats as $_chats ) {
            $this->scheduleEvent('delete_orphaned_messages_from_chats_'.implode(',', $_chats), $seconds);
            // increment cron schedule to perform the slowest possible
            $seconds += $this->event_time_add;
        }
    }

    public function transientsCleanup()
    {
        global $wpdb;

        $opts = $this->getListKeys($wpdb->get_results("SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '%_transient_timeout_se_transients__WP_Messages_{$this->table}%' AND `option_value` < UNIX_TIMESTAMP()"), 'option_name');

        $options = array();

        if ( $opts ) {
            foreach ( $opts as $opt ) {
                $options[] = $opt;
                $options[] = str_replace(
                    '_transient_timeout_se_transients__WP_Messages_',
                    '_transient_se_transients__WP_Messages_',
                    $opt
                );
            }
        }

        if ( $options ) {
            return $wpdb->query(sprintf(
                "DELETE FROM {$wpdb->options} WHERE `option_name` IN ('%s')",
                implode("','", $options)
            ));
        }
    }

    public function uninstall()
    {
        // purge transients from db
        $this->uninstallTransients();
    }

    public function uninstallTransients()
    {
        global $wpdb;

        $opts = $this->getListKeys($wpdb->get_results("SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '%_transient_timeout_se_transients__WP_Messages_{$this->table}%' AND `option_value` < UNIX_TIMESTAMP()"), 'option_name');

        return $wpdb->query("DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '%_se_transients__WP_Messages_{$this->table}%'");
    }

    public function getUserTotalMessages($user_id=null, $chat_id=null)
    {
        if ( !$user_id ) {
            if ( $this->current_user ) {
                $user_id = $this->current_user;
            } else {
                return;
            }
        } else {
            $user_id = (int) $user_id;
        }

        $cacheKey = "user_{$user_id}_total_messages";

        if ( $chat_id ) {
            $cacheKey .= "_chat_{$chat_id}"; 
        }

        $cached = $this->getCache($cacheKey);

        if ( false !== $cached ) {
            return (int) $cached;
        }

        global $wpdb;

        if ( !$chat_id ) {
            $chats = $this->getUserChatsRaw($user_id);

            if ( !$chats ) {
                $count = 0;
            } else {
                $count = (int) $wpdb->get_var($wpdb->prepare(sprintf(
                    "SELECT COUNT(*) FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` IN ('%s') AND (NOT FIND_IN_SET(%s, `deleted`) OR `deleted` IS NULL)",
                    implode("','", $chats),
                    '%d'
                ), $user_id));
            }
        } else {
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb_prefix}{$this->table} WHERE `chat_id` = %s AND (NOT FIND_IN_SET(%d, `deleted`) OR `deleted` IS NULL)",
                $chat_id,
                $user_id
            ));
        }

        $this->setCache($cacheKey, $count);

        return $count;
    }

    public function getChatsUnreadCount($user_id=null, $unique=false)
    {
        if ( !$user_id && $this->current_user ) {
            $user_id = $this->current_user;
        }

        if ( !$user_id )
            return;

        $chats = $this->getUserChatsRaw($user_id);

        if ( !$chats )
            return 0;

        $count = 0;

        foreach ( $chats as $chat_id ) {
            $uc = $this->getChatUnreadCount($chat_id, $user_id);

            if ( $uc )
                $count += $unique ? 1 : $uc;
        }

        return $count;
    }
}
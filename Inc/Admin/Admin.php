<?php namespace BBP_MESSAGES\Inc\Admin;

class Admin
{
    public $screen;
    public $bbpm_importer;
    public $get_page;

    public function __construct()
    {
        $this->bbpm_importer = new Importers\bbPMessages;
        $this->screen = new Screen;
        $this->get_page = isset($_GET['page']) ? esc_attr($_GET['page']) : null;
    }

    public function init()
    {
        $plugin = bbp_messages();
        $prefix = $plugin->isNetworkActive() ? 'network_' : '';
        add_action($prefix . 'admin_notices', array($this, 'uiFeedback'));
        add_action($prefix . 'admin_menu', array(new Settings, 'init'));

        if ( get_option('bbpm_has_import_data_bbpmessages') ) {
            if ( $this->bbpm_importer->check() ) {
                call_user_func(array($this->bbpm_importer, 'init'), $prefix);
            } else {
                delete_option('bbpm_has_import_data_bbpmessages');
            }
        }

        add_action($prefix . 'admin_menu', array($this->screen, 'setupPages'));
        add_action($prefix . 'admin_menu', array($this->screen, 'prepare'));

    
        if ( 'bbpm-' === substr($this->get_page, 0, 5) ) {
            $current_tab_id = substr($this->get_page, 5);
        } else if ( 'bbpress-messages' === $this->get_page ) {
            $current_tab_id = null;
        } else {
            $keep_quite = true;
        }

        if ( !isset($keep_quite) ) {
            add_action($prefix . 'admin_menu', array($this->screen, 'maybeUpdate'));
        }

        // if ( 'bbpress-messages' === $this->get_page ) {
        //     global $pagenow;
        //     switch ($pagenow) {
        //         case 'options-general.php':
        //             if ( 'network_' !== $prefix ) {
        //                 add_action($prefix . 'admin_menu', array($this->screen, 'maybeUpdate'));
        //             }
        //             break;

        //         case 'settings.php':
        //             if ( 'network_' === $prefix ) {
        //                 add_action($prefix . 'admin_menu', array($this->screen, 'maybeUpdate'));
        //             }
        //             break;
        //     }
        // }

        if ( 'network_' !== $prefix ) {
            add_filter('plugin_action_links_' . BBP_MESSAGES_BASE, array($this, 'actionLinks'));        
        } else {
            add_filter('network_admin_plugin_action_links_' . BBP_MESSAGES_BASE, array($this, 'actionLinks'));
        }

        add_filter('plugin_row_meta', array($this, 'rowMetaLinks'), 10, 2);

        add_action($prefix . 'admin_menu', array($this, 'welcome'));
        add_action($prefix . 'admin_menu', array($this, 'checkDb'));

        do_action('bbpm_admin_loaded', $this);

        return $this;
    }

    public function feedback($message, $success=true)
    {
        if ( trim($message) ) {
            global $bbpm_admin_feedback;
            if ( !is_array($bbpm_admin_feedback) ) {
                $bbpm_admin_feedback = array();
            }
            $bbpm_admin_feedback[] = array(
                'success' => (bool) $success,
                'message' => $message
            );
        }

        return $this;
    }

    public function uiFeedback()
    {
        global $bbpm_admin_feedback, $bbpm_admin_feedback_printed;
        if ( !isset( $bbpm_admin_feedback_printed ) || !is_array($bbpm_admin_feedback_printed) ) {
            $bbpm_admin_feedback_printed = array();
        }
        if ( $bbpm_admin_feedback && is_array($bbpm_admin_feedback) ) {
            foreach ( $bbpm_admin_feedback as $i => $res ) {
                if ( empty( $res['message'] ) ) continue;
                // duplicates check
                if ( isset($bbpm_admin_feedback_printed[$res['message']]) ) continue;
                $bbpm_admin_feedback_printed[$res['message']] = true;
                // print message
                printf(
                    '<div class="%s notice is-dismissible"><p>%s</p></div>',
                    !empty($res['success'])?'updated':'error',
                    $res['message']
                );
            }
        }

        return $this;
    }

    public function activation()
    {
        if ( $this->bbpm_importer->check() ) {
            add_option('bbpm_has_import_data_bbpmessages', time());
        }
    }

    public function actionLinks($l)
    {
        return array_merge(array(
            sprintf('<a href="%s">%s</a>', $this->screen->getLink(null), __('Settings', 'bbp-messages')),
            '<a href="index.php?page=bbpm-about">' . __('About', 'bbp-messages') . '</a>'
        ), $l);
    }

    public function rowMetaLinks($l, $f)
    {
        if ( $f !== BBP_MESSAGES_BASE )
            return $l;

        $tabs = bbpm_admin_tabs();

        if ( $tabs ) {
            foreach ( $tabs as $t ) {
                switch ( $t['id'] ) {
                    case '':
                        continue 2;
                        break;
                }

                $l[] = sprintf('<a href="%s">%s</a>', $this->screen->getLink($t), $t['name']);
            }
        }

        // others
        // $l[] = '<a href="index.php?page=bbpm-about">' . __('About', 'bbp-messages') . '</a>';

        return $l;
    }

    public function welcome()
    {
        add_submenu_page(
            null,
            __('Welcome to bbPress Messages', 'bbp-messages'),
            __('Welcome to bbPress Messages', 'bbp-messages'),
            'manage_options',
            'bbpm-about',
            array($this, 'welcomeDisplay')
        );

        return $this;
    }

    public function welcome2P0()
    {
        return 'admin/welcome-2.0.html';
    }

    public function welcomeDisplay()
    {
        ob_start();

        bbpm_load_template('admin/welcome.html');

        $welcome = ob_get_clean();

        echo str_replace(
            array(
                '{{SETTINGS_URL}}',
                '{{VERSION}}'
            ),
            array(
                $this->screen->getLink(null),
                BBP_MESSAGES_VER
            ),
            $welcome
        );
    }

    public function checkDb()
    {
        global $wpdb;
        $m = bbpm_messages();

        if ( !$wpdb->query("DESCRIBE {$m->wpdb_prefix}{$m->table}") ) {
            $this->feedback(
                __('<strong>bbPress Messages error</strong>: database error, the main database table was not inserted correctly. If you have just updated the plugin, please deactivate it and activate it once again, otherwise, please consult the support forums for help.', 'bbp-messages'),
                false
            );
        }
    }
}
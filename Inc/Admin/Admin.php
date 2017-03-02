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

        if ( 'bbpress-messages' === $this->get_page ) {
            global $pagenow;
            switch ($pagenow) {
                case 'options-general.php':
                    if ( 'network_' !== $prefix ) {
                        add_action($prefix . 'admin_menu', array($this->screen, 'maybeUpdate'));
                    }
                    break;

                case 'settings.php':
                    if ( 'network_' === $prefix ) {
                        add_action($prefix . 'admin_menu', array($this->screen, 'maybeUpdate'));
                    }
                    break;
            }
        }

        if ( 'network_' !== $prefix ) {
            add_filter('plugin_action_links_' . BBP_MESSAGES_BASE, array($this, 'actionLinks'));        
        } else {
            add_filter('network_admin_plugin_action_links_' . BBP_MESSAGES_BASE, array($this, 'actionLinks'));
        }

        add_action($prefix . 'admin_menu', array($this, 'welcome'));

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
        $tabs = $this->screen->tabs;

        if ( $tabs ) {
            foreach ( $tabs as $t ) {
                $l[] = sprintf('<a href="%s">%s</a>', $t['link'], $t['name']);
            }
        }

        return $l;
    }

    public function welcome()
    {
        add_submenu_page(
            null,
            __('Welcome to bbPress Messages', BBP_MESSAGES_DOMAIN),
            __('Welcome to bbPress Messages'),
            'manage_options',
            'bbpm-about',
            array($this, 'welcomeDisplay')
        );

        return $this;
    }

    public function welcomeDisplay()
    {
        bbpm_load_template(sprintf(
            'admin/welcome-%s.html',
            BBP_MESSAGES_VER
        ));
    }
}
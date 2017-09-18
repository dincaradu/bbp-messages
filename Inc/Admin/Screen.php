<?php namespace BBP_MESSAGES\Inc\Admin;

class Screen
{
    public $tabs;
    public $current_tab_id;
    public $current_tab;
    public $admin;

    public function setupPages()
    {
        add_menu_page(
            __('bbPress Messages', 'bbp-messages'),
            __('bbP Messages', 'bbp-messages'),
            'manage_options',
            'bbpress-messages',
            array($this, 'screen'),
            'dashicons-email-alt'
        );

        add_submenu_page(
            'bbpress-messages',
            sprintf(__('%s &lsaquo; bbPress Messages', 'bbp-messages'), __('Settings', 'bbp-messages')),
            __('Settings', 'bbp-messages'),
            'manage_options',
            'bbpress-messages'
        );

        $tabs = bbpm_admin_tabs();

        foreach ( $tabs as $tab ) {
            if ( !trim($tab['id']) )
                continue;

            add_submenu_page(
                'bbpress-messages',
                sprintf(__('%s &lsaquo; bbPress Messages', 'bbp-messages'), $tab['name']),
                $tab['name'],
                'manage_options',
                'bbpm-'.$tab['id'],
                array($this, 'screen')
            );
        }

        return $this;
    }

    public function admin()
    {
        if ( !$this->admin || !($this->admin instanceof Admin) ) {
            $this->admin = new Admin;
        }
        
        return $this->admin;
    }

    public function getLink($tab)
    {
        if ( is_string($tab) ) {
            $id = $tab;
        } else if (is_array($tab) && isset($tab['id'])) {
            $id = $tab['id'];
        } else {
            $id = null;
        }

        $link = bbp_messages()->isNetworkActive() ? (
            network_admin_url('admin.php?page=')
        ) : (
            admin_url('admin.php?page=')
        );

        if ( trim($id) ) {
            $link .= 'bbpm-' . $id;
        } else {
            $link .= 'bbpress-messages';
        }

        return esc_url($link);
    }

    public function prepare()
    {
        $tabs = bbpm_admin_tabs();

        if ( !$tabs || !is_array($tabs) ) {
            wp_die(__('No tabs loaded yet.', 'bbp-messages'));
        }

        $get_page = $this->admin()->get_page;
        $this->current_tab_id = $get_page;

        if ( 'bbpm-' === substr($this->current_tab_id, 0, 5) ) {
            $this->current_tab_id = substr($this->current_tab_id, 5);
        } else if ( 'bbpress-messages' === $this->current_tab_id ) {
            $this->current_tab_id = null;
        }

        foreach ( (array) $tabs as $tab ) {
            if ( $tab['id'] == $this->current_tab_id ) {
                $this->current_tab = $tab;
                break;
            }
        }
    }

    public function isCurrentTab($tab)
    {
        if ( empty($this->current_tab['name']) )
            return;

        $curr = (array) $this->current_tab;

        if ( !is_array($tab) )
            return;

        foreach ( (array) $tab as $prop=>$data ) {
            switch ($prop) {
                case 'id':
                case 'name':
                    break;
                
                default:
                    unset($tab[$prop]);
                    break;
            }
        }

        foreach ( $curr as $prop=>$data ) {
            switch ($prop) {
                case 'id':
                case 'name':
                    # code...
                    break;
                
                default:
                    unset($curr[$prop]);
                    break;
            }
        }

        return ($tab && $curr) && $tab == $curr;
    }

    public function menu()
    {
        $tabs = bbpm_admin_tabs();

        if ( !$tabs || !is_array($tabs) ) {
            return;
        }

        if ( empty($this->current_tab['name']) )
            return;

        ?>

        <?php if ( $this->current_tab && !empty($this->current_tab['name']) ) : ?>
            <h2><?php printf( __('%1$s &lsaquo; %2$s', 'bbp-messages'), $this->current_tab['name'], BBP_MESSAGES_NAME ); ?></h2>
        <?php endif; ?>

        <?php
    }

    public function screen()
    {
        // wrap
        print '<div class="wrap">';
        // top menu
        $this->menu();
        // print content
        $this->content();
        // close wrap
        print '</div>';
    }

    public function content()
    {
        if ( !empty($this->current_tab['content_callback']) && is_callable($this->current_tab['content_callback']) ) {
            // start buffer
            ob_start();
            // call content callback for this screen
            call_user_func($this->current_tab['content_callback']);
            // capture output
            $content = ob_get_clean();
            // append nonces
            $content = preg_replace_callback('/<\/form>/si', function($m){
                $html = wp_nonce_field('admin_post', 'bbpm_nonce', true, false) . PHP_EOL;
                $html .= '</form>';
                return $html;
            }, $content);
            // print
            if ( apply_filters('bbpm_admin_tab_with_sidebar', true, $this->current_tab['name']) ) {
                $this->css();
                print '<div class="bbpm-two">';
                printf ('<div class="bbpm-left">%s</div>', $content);
                print '<div class="bbpm-right">';
                $this->sidebar();
                print '</div>';
                print '</div>';
            } else {
                print $content;
            }

        } else {
            // print an error message
            $this->admin()->feedback(
                __('This custom tab does not appear to have a valid content callback.', 'bbp-messages'),
                false
            )->uiFeedback();
        }
    }

    public function maybeUpdate()
    {
        $this->handleRequest();

        if ( !isset( $_POST['bbpm_nonce'] ) )
            return;

        if ( !wp_verify_nonce($_POST['bbpm_nonce'], 'admin_post') )
            return;

        return $this->update();
    }

    public function handleRequest()
    {
        if ( !empty($this->current_tab['request_handler']) && is_callable($this->current_tab['request_handler']) ) {
            call_user_func($this->current_tab['request_handler']);
        }
    }

    public function update()
    {
        if ( !empty($this->current_tab['update_callback']) && is_callable($this->current_tab['update_callback']) ) {
            call_user_func($this->current_tab['update_callback']);
        }
    }

    public function css()
    {
        ?>
        <style type="text/css">
            @media (min-width: 600px) {
                .bbpm-two {
                    display: flex;
                }
                .bbpm-two .bbpm-right {
                    margin-right: 0;
                    padding-left: 2em;
                    max-width: 35%;
                }
            }
        </style>
        <?php

        print PHP_EOL;
    }

    public function sidebar()
    {
        if ( !empty($this->current_tab['sidebar']) && is_callable($this->current_tab['sidebar']) ) {
            // custom sidebar
            call_user_func($this->current_tab['sidebar']);
        } else {
            ?>

            <p>Thank you for using bbPress Messages plugin! Did you like the new update? Don't hesitate to leave us a rating and an optional review! That helps. <a target="_blank" href="https://wordpress.org/support/plugin/bbp-messages/reviews/?rate=5#new-post">&star;&star;&star;&star;&star; &rarr;</a></p>

            <strong>Support</strong>

            <p>bbPress Messages support is offered through wp-org support forums. <a target="_blank" href="https://wordpress.org/support/plugin/bbp-messages">Find Support</a></p>

            <strong>Help us out</strong>

            <p>Whether you found a bug and wanted to report it, or you had some ideas to improve this plugin, or features, or wanted to contribute to the core, please consult the Github repository for this plugin at <a target="_blank" href="https://github.com/elhardoum/bbp-messages">https://github.com/elhardoum/bbp-messages</a>. PRs are welcome!</p>

            <p>You can also contribute your translations and <a href="https://translate.wordpress.org/projects/wp-plugins/bbp-messages">help translate this plugin</a> to your language!</p>

            <strong>Stay tuned</strong>

            <p>Currently, we're working on preparing some free and premium addons for bbPress Messages 2.0, and tutorials on how to customize it to fit your needs. <a href="https://go.samelh.com/newsletter/" target="_blank">Sign up for the newsletter</a> to get notified!</p>

            <?php
        }
    }
}
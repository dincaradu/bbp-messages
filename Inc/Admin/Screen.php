<?php namespace BBP_MESSAGES\Inc\Admin;

class Screen
{
    public $tabs;
    public $current_tab_id;
    public $current_tab;
    public $admin;

    function __construct()
    {
        $this->tabs = array();
    }

    public function setupPages()
    {
        add_submenu_page(
            bbp_messages()->isNetworkActive() ? 'settings.php' : 'options-general.php',
            __('bbPress Messages', BBP_MESSAGES_DOMAIN),
            BBP_MESSAGES_NAME,
            'manage_options',
            'bbpress-messages',
            array($this, 'screen')
        );

        return $this;
    }

    public function admin()
    {
        if ( !$this->admin || !($this->admin instanceof Admin) ) {
            $this->admin = new Admin;
        }
        
        return $this->admin;
    }

    public function prepare()
    {
        $this->tabs = apply_filters('bbpm_admin_tabs', array());

        if ( !$this->tabs || !is_array($this->tabs) ) {
            wp_die(__('No tabs loaded yet.', BBP_MESSAGES_DOMAIN));
        }

        $plugin = bbp_messages();

        foreach ( (array) $this->tabs as $i=>$tab ) {
            $this->tabs[$i]['link'] = $plugin->isNetworkActive() ? (
                network_admin_url('settings.php?page=bbpress-messages')
            ) : (
                admin_url('options-general.php?page=bbpress-messages')
            );

            if ( trim($tab['id']) ) {
                $this->tabs[$i]['link'] = add_query_arg('tab', $tab['id'], $this->tabs[$i]['link']);
            }
        }

        $this->current_tab_id = isset($_GET['tab']) ? esc_attr($_GET['tab']) : null;

        foreach ( (array) $this->tabs as $tab ) {
            if ( $tab['id'] == $this->current_tab_id ) {
                $this->current_tab = $tab;
                break;
            }
        }

        if ( empty($_GET['page']) || 'bbpress-messages' !== $_GET['page'] )
            return;

        global $pagenow;

        if ( 'options-general.php' === $pagenow || ('settings.php' === $pagenow && is_network_admin()) ) {
            if ($this->current_tab_id && !$this->current_tab) {
                return bbpm_redirect(esc_url('?page=bbpress-messages'), 1);
            }
        }
    }

    public function menu()
    {
        if ( !$this->tabs || !is_array($this->tabs) ) {
            return;
        }
        ?>

        <?php if ( $this->current_tab && !empty($this->current_tab['name']) ) : ?>
            <h2><?php printf( __('%1$s &lsaquo; %2$s', BBP_MESSAGES_DOMAIN), $this->current_tab['name'], BBP_MESSAGES_NAME ); ?></h2>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper">
            <?php foreach ( $this->tabs as $name=>$tab ) : ?>
                <a 
                    class="nav-tab<?php echo $tab == $this->current_tab ?" nav-tab-active":"";?>"
                    href="<?php echo esc_url($tab['link']); ?>"
                >
                    <span><?php echo esc_attr($tab['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </h2>
        <p></p>

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
                __('This custom tab does not appear to have a valid content callback.', BBP_MESSAGES_DOMAIN),
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
<?php

if ( !class_exists('bbPMCheckReady') ) :

/**
  * Compares PHP versions
  * Making sure this blog is running enough required PHP software for
  * this plugin, currently requiring 5.3 at least for namespaces
  */

Class bbPMCheckReady
{
    public $min, $ver, $errors, $plugin;

    public function __construct( $minVersion='5.3', $plugins=array(), $name='' )
    {
        $this->min = $minVersion;
        $this->ver = PHP_VERSION;
        $this->errors = array();
        $this->plugin = trim($name) ? $name : '';

        if ( !version_compare($this->ver, $this->min, '>=') ) {
            $this->errors[] = sprintf(
                '%s plugin requires at least PHP %s (You have %s).',
                $this->plugin ? $this->plugin : 'This plugin',
                $this->min,
                $this->ver
            );
        }

        if ( $plugins ) {
            $this->getPlugins();
    
            foreach ( (array) $plugins as $name=>$plugin ) {
                if ( !in_array($plugin, $this->plugins) ) {
                    $error = sprintf(
                        '%s plugin is required!',
                        strlen($name) > 2 ? $name : $plugin
                    );

                    $this->errors[] = $error;
                }
            }
        }

        if ( $this->hasErrors() ) {
            $prefix = is_multisite() && is_network_admin() ? 'network_' : '';
            add_action($prefix . 'admin_notices', array($this, "notice"), 999);
        } else if ( 'cli' != php_sapi_name() ) {
            add_action('activated_plugin', array($this, 'redirect'));
        }
    }

    public function notice()
    {
        if ( !$this->errors )
            return;

        $this->errors = array_merge(array("<strong>$this->plugin notices</strong>:"), $this->errors);

        printf(
            '<div class="error notice is-dismissible"><p>%s</p></div>',
            implode('<br/> &mdash; ', $this->errors)
        );

        printf(
            '<div class="error notice is-dismissible"><p>%s</p></div>',
            __('Deactivating plugin..')
        );
    }

    public function hasErrors()
    {
        return (bool) $this->errors;
    }

    public function getPlugins()
    {
        $this->plugins = apply_filters('active_plugins', get_option('active_plugins'));

        if ( is_multisite() ) {
            $network_plugins = get_site_option('active_sitewide_plugins');
            if ( $network_plugins ) {
                $network_plugins = array_keys($network_plugins);
                $this->plugins = array_merge($this->plugins, $network_plugins);
            }
        }

        return $this;
    }

    public function check()
    {
        if ( $this->hasErrors() ) {
            update_site_option('bbpm_force_deactivate', true);
        } else {
            delete_site_option('bbpm_force_deactivate');
        }
    }

    public function redirect( $plugin ) {
        switch ( $plugin ) {
            case plugin_basename(BBP_MESSAGES_FILE):
                wp_redirect('index.php?page=bbpm-about');
                exit;
                break;
        }
    }
}

return new bbPMCheckReady(
    '5.3',
    array('bbPress (parent plugin)' => 'bbpress/bbpress.php'),
    'bbPress Messages'
);

endif;
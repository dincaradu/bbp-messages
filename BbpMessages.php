<?php namespace BBP_MESSAGES;

class BbpMessages
{
    /** messages class **/
    public $messages, $init_class, $admin_class;

    public function setup()
    {
        return $this
            ->defineConstants()
            ->loadLib()
            ->registerAutoload()
            ->loadMessages()
            ->activationHooks();
    }

    public function defineConstants()
    {
        $constants = array(
            'BBP_MESSAGES_DIR' => plugin_dir_path(BBP_MESSAGES_FILE),
            'BBP_MESSAGES_URL' => plugin_dir_url(BBP_MESSAGES_FILE),
            'BBP_MESSAGES_VER' => '2.0.9.1',
            'BBP_MESSAGES_NAME' => __('bbPress Messages', 'bbp-messages'),
            'BBP_MESSAGES_BASE' => plugin_basename(BBP_MESSAGES_FILE),
            'BBP_MESSAGES_DOMAIN' => 'bbp-messages'
        );

        foreach ( $constants as $constant => $def ) {
            if ( !defined( $constant ) ) {
                define( $constant, $def );
            }
        }

        return $this;
    }

    public function loadLib()
    {
        // load functions
        require_once BBP_MESSAGES_DIR . (
            'Inc/Core/functions.php'
        );
        
        // load WP_Messages class
        if ( class_exists('\WP_Messages') )
            return $this;

        require_once BBP_MESSAGES_DIR . (
            'Inc/Lib/wp-messages/src/wp-messages.php'
        );

        return $this;
    }

    public function registerAutoload()
    {
        spl_autoload_register(array(
            $this,
            'autload'
        ));

        return $this;
    }

    public function autload($className)
    {
        $classFile = $className;
        // main parent namespace
        $parentNamespace = __NAMESPACE__;

        if ( "\{$parentNamespace}\\" === substr( $classFile, 0, (strlen($parentNamespace)+2) ) ) {
            $classFile = substr( $classFile, (strlen($parentNamespace)+2) );
        }
        else if ( "{$parentNamespace}\\" === substr( $classFile, 0, (strlen($parentNamespace)+1) ) ) {
            $classFile = substr( $classFile, (strlen($parentNamespace)+1) );
        }

        $classFile = BBP_MESSAGES_DIR."{$classFile}.php";
        $classFile = str_replace( '\\', DIRECTORY_SEPARATOR, $classFile );

        if ( !class_exists( $className ) && file_exists($classFile) ) {
            return require( $classFile );
        }
    }

    public function loadMessages($ins=null)
    {
        $this->messages = is_object($ins) ? $ins : new \BBP_MESSAGES\Inc\Core\Messages;
        // return an instance
        return $this;
    }

    public function activationHooks()
    {
        // activation hooks
        register_activation_hook(BBP_MESSAGES_FILE, array($this->messages, 'activation'));
        register_activation_hook(BBP_MESSAGES_FILE, array($this, 'activation'));
        register_activation_hook(BBP_MESSAGES_FILE, array(new \BBP_MESSAGES\Inc\Admin\Admin, 'activation'));
        // deactivation hooks
        register_deactivation_hook(BBP_MESSAGES_FILE, array($this->messages, 'deactivation'));
        register_activation_hook(BBP_MESSAGES_FILE, array($this, 'deactivation'));

        return $this;
    }

    public function activation()
    {
        // flush rewrite rules
        $this->flushRewriteRules();
    }

    public function deactivation()
    {
        // flush rewrite rules
        $this->flushRewriteRules();
    }

    public function flushRewriteRules()
    {
        if ( $this->isNetworkActive() ) {
            switch_to_blog(get_network()->blog_id);
            delete_option('rewrite_rules');
            restore_current_blog();
        } else {
            delete_option('rewrite_rules');
        }
    }

    public function isNetworkActive()
    {
        global $bbpmIsNetworkActive;

        if ( isset($bbpmIsNetworkActive) && is_bool($bbpmIsNetworkActive) ) {
            return $bbpmIsNetworkActive;
        }

        if ( !is_multisite() ) {
            $bbpmIsNetworkActive = false;
        } else {
            $plugins = get_site_option( 'active_sitewide_plugins', array() );
            $bbpmIsNetworkActive = is_array($plugins) && isset($plugins[BBP_MESSAGES_BASE]);
        }

        return $bbpmIsNetworkActive;
    }

    public function init()
    {
        if ( is_admin() && get_site_option('bbpm_force_deactivate') ) {
            delete_site_option('bbpm_force_deactivate');
            // deactivate
            return add_action('admin_init', array($this, 'deactivate'));
        }
        // text domain
        $this->loadTextDomain();
        // extend messages class
        do_action_ref_array('bbpm_wp_messages', array( &$this->messages ));
        // WP_Messages class
        call_user_func(array($this->messages, 'init'));
        // init class
        $this->init_class = new \BBP_MESSAGES\Inc\Core\Init;
        // pluggable
        do_action_ref_array('bbpm_init_class', array( &$this->init_class ));
        // initialize
        call_user_func(array($this->init_class, 'init'));
        // shortcodes class
        $this->shortcodes_class = new \BBP_MESSAGES\Inc\Core\Shortcodes;
        // pluggable
        do_action_ref_array('bbpm_shortcodes_class', array( &$this->shortcodes_class ));
        // initialize
        call_user_func(array($this->shortcodes_class, 'init'));

        if ( is_admin() ) {
            // admin class
            $this->admin_class = new \BBP_MESSAGES\Inc\Admin\Admin;
            // pluggable
            do_action_ref_array('bbpm_admin_class', array( &$this->admin_class ));
            // init
            call_user_func(array($this->admin_class, 'init'));
        }

        // return an instance
        return $this;
    }

    public function deactivate() {
        return deactivate_plugins(plugin_basename(BBP_MESSAGES_FILE));
    }

    public function loadTemplate($file, $args=null)
    {
        $filePath = BBP_MESSAGES_DIR . "templates/{$file}";
        // pluggable
        $filePath = apply_filters('bbpm_load_template_file', $filePath, $file);
        $filePath = apply_filters("bbpm_load_template_file_{$file}", $filePath);

        // extract args
        if ( $args )
            extract($args);
        // include
        if ( file_exists( $filePath ) ) {
            include( $filePath );
        }
    }

    public function loadTextDomain()
    {
        load_plugin_textdomain('bbp-messages', false, dirname(BBP_MESSAGES_BASE).'/languages');

        return $this;
    }
}
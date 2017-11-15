<?php
/*
Plugin Name: bbPress Messages
Plugin URI: https://github.com/elhardoum/bbp-messages
Description: Simple yet powerful private messaging system tailored for bbPress.
Author: Samuel Elh
Version: 2.0.9.1
Author URI: https://samelh.com
Text Domain: bbp-messages
Donate link: https://go.samelh.com/buy-me-a-coffee
*/

if ( !defined('BBP_MESSAGES_FILE') ) {
    define('BBP_MESSAGES_FILE', __FILE__);
}

/**
  * Require version and dependencies check class
  *
  * Making sure client has PHP 5.3 at least, required
  * for PHP namespaces and closures.
  *
  * Making sure client has bbPress parent plugin
  * installed and activated
  */
$bbPMCheckReady = require('CheckReady.php');

if ($bbPMCheckReady instanceof bbPMCheckReady) {
    if ( method_exists($bbPMCheckReady, 'check') ) {
        // activation check
        register_activation_hook(BBP_MESSAGES_FILE, array($bbPMCheckReady, 'check'));
    }

    // load plugin
    require('BbpMessages.php');

    // init
    global $bbpm_loader;
    // loader class
    $bbpm_loader = new \BBP_MESSAGES\BbpMessages;
    // setup
    $bbpm_loader->setup();
}

function bbp_messages_loaded(){
    global $bbpm_loader;

    if( ! class_exists('bbPress') ) {
        return add_action('admin_init', array($bbpm_loader, 'deactivate'));
    }

    $bbpm_loader->init();
}
add_action('plugins_loaded', 'bbp_messages_loaded', 10, 0);
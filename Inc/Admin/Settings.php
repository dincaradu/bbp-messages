<?php namespace BBP_MESSAGES\Inc\Admin;

class Settings
{
    public $admin;

    public function init()
    {
        // bbpm_admin_screen()->registerTab(array(
        //     'settings' => array(
        //         'id' => null,
        //         'name' => __('Settings', 'bbp-messages'),
        //         'content_callback' => array($this, 'screen'),
        //         'update_callback' => array($this, 'update')
        //     )
        // ))->prepare();

        add_filter('bbpm_admin_tabs', array($this, 'tab'), 0);
        add_filter('bbpm_settings_email_body_editor_value', 'wp_unslash');
        add_filter('bbpm_settings_email_subject_editor_value', 'wp_unslash');
    }

    public function admin()
    {
        if ( !$this->admin || !($this->admin instanceof Admin) ) {
            $this->admin = new Admin;
        }

        return $this->admin;
    }

    public function tab($tabs)
    {
        return array_merge(array(
            'settings' => array(
                'id' => null,
                'name' => __('Settings', 'bbp-messages'),
                'content_callback' => array($this, 'screen'),
                'update_callback' => array($this, 'update')
            )
        ), $tabs);
    }

    public function screen()
    {
        $opt = (object) bbpm_options();

        ?>

        <form method="post" id="poststuff">
            <div id="postbox-container" class="postbox-container">
                <div class="meta-box-sortables ui-sortable" id="normal-sortables">

                    <div class="postbox">
                        <h3 class="hndle"><span><?php _e('Pagination', 'bbp-messages'); ?></span></h3>
                        <div class="inside">
                            <p>
                                <label>
                                    <strong><?php _e('Messages per page:', 'bbp-messages'); ?></strong><br/>
                                    <input type="number" min="1" name="pagi_messages" value="<?php echo $opt->pagi_messages; ?>" />
                                </label>
                            </p>

                            <p>
                                <label>
                                    <strong><?php _e('Chats per page:', 'bbp-messages'); ?></strong><br/>
                                    <input type="number" min="1" name="pagi_chats" value="<?php echo $opt->pagi_chats; ?>" />
                                </label>
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h3 class="hndle"><span><?php _e('Notification email', 'bbp-messages'); ?></span></h3>
                        <div class="inside">
                            <p>
                                <label>
                                    <strong><?php _e('Email subject:', 'bbp-messages'); ?></strong><br/>
                                    <input type="text" name="email_subject" value="<?php echo apply_filters(
                                        'bbpm_settings_email_subject_editor_value',
                                        $opt->email_subject
                                    ); ?>"><br/>
                                    <em>
                                        <?php _e('Patterns:<br/><code>%1$s</code>: Blog name,<br/><code>%2$s</code>: Chat/sender name', 'bbp-messages'); ?>
                                    </em>
                                </label>
                            </p>

                            <p>
                                <strong><?php _e('Email body:', 'bbp-messages'); ?></strong><br/>
                                <em>
                                    <?php _e('Patterns:<br/><code>%1$s</code>: Current user name,<br/><code>%2$s</code>: Chat/sender name,<br/><code>%3$s</code>: blog name,<br/><code>%4$s</code>: message excerpt,<br/><code>%5$s</code>: chat url,<br/><code>%6$s</code>: chat settings url', 'bbp-messages'); ?>
                                </em>
                            </p>

                            <?php wp_editor(apply_filters('bbpm_settings_email_body_editor_value', $opt->email_body), 'email_body'); ?>

                            <p>
                                <label>
                                    <input type="checkbox" name="html_emails" <?php checked($opt->html_emails, true); ?> />
                                    <?php _e('Send out HTML emails (mail headers setting)', 'bbp-messages'); ?>
                                </label>
                            </p>
                        </div>
                    </div>

                    <?php if ( $menus = get_terms('nav_menu') ) : ?>
                        <div class="postbox">
                            <h3 class="hndle"><span><?php _e('Menu Settings', 'bbp-messages'); ?></span></h3>
                            <div class="inside">
                                <p>
                                    <label>
                                        <strong><?php _e('Add messages menu item to menu:', 'bbp-messages'); ?></strong><br/>
                                        <select name="menu[]" multiple="multiple">
                                            <option><?php _e('&mdash; Select &mdash;', 'bbp-messages'); ?></option>
                                            <?php foreach ( $menus as $menu ) : ?>
                                                <option value="<?php echo $menu->term_id; ?>" <?php selected(isset($opt->menu_locations) && in_array($menu->term_id, $opt->menu_locations)); ?>>
                                                    <?php echo $menu->name; ?></option>
                                            <?php endforeach; ?> 
                                        </select>
                                    </label>
                                </p>

                                <p>
                                    <label>
                                        <strong><?php _e('Menu item text:', 'bbp-messages'); ?></strong><br/>
                                        <input type="text" name="menu_text" value="<?php echo isset($opt->menu_text) ? esc_attr($opt->menu_text) : null; ?>" />
                                        <br/>
                                        <em><?php _e('Use <a href="?page=bbpm-shortcodes"><code>[bbpm-unread-count]</code></a> shortcode to insert the unread counter.', 'bbp-messages'); ?></em>
                                    </label>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php do_action('bbpm_admin_settings_before_advanced'); ?>

                    <div class="postbox">
                        <h3 class="hndle">
                            <span>
                                <a href="javascript:;" onclick="jQuery('.bbpm-advanced').fadeToggle();"><?php _e('Advanced settings', 'bbp-messages'); ?></a>
                            </span>
                        </h3>
                    </div>

                    <div class="postbox bbpm-advanced" style="display:none;">
                        <h3 class="hndle"><span><?php _e('Cache Settings', 'bbp-messages'); ?></span></h3>
                        <div class="inside">

                            <p>
                                <label>
                                    <strong><?php _e('Cache context:', 'bbp-messages'); ?></strong><br/>
                                    <select name="cache_ctx">
                                        <option value="object" <?php selected($opt->cache_ctx, 'object'); ?>>Use WP Object Cache</option>
                                        <option value="transients" <?php selected($opt->cache_ctx, 'transients'); ?>>Use WP Transients</option>
                                        <option value="none" <?php selected($opt->cache_ctx, 'none'); ?>>Disable cache</option>
                                    </select>
                                </label>
                            </p>

                            <em><?php _e('Current cache status:', 'bbp-messages'); ?></em><br/>
                            <?php _e('- WP Object Cache:', 'bbp-messages'); ?> <?php echo wp_using_ext_object_cache() ? __('External object cache is available', 'bbp-messages') : __('External object cache is not available', 'bbp-messages'); ?><br/>
                            <?php _e('- Transients:', 'bbp-messages'); ?> <?php echo wp_using_ext_object_cache() ? __('Same as WP Object Cache, performs external object caching', 'bbp-messages') : __('Uses options API to store cache', 'bbp-messages'); ?>

                            <p>
                                <?php _e('Delete transients from database:', 'bbp-messages'); ?>
                                <label class="button" for="purge_expired_transients"><?php _ex('expired', 'delete transients', 'bbp-messages'); ?></label>
                                <label class="button" for="purge_all_transients"><?php _ex('all', 'delete transients', 'bbp-messages'); ?></label>
                            </p>

                        </div>
                    </div>

                    <div class="postbox bbpm-advanced" style="display:none;">
                        <h3 class="hndle"><span><?php _e('Auto-delete older messages', 'bbp-messages'); ?></span></h3>
                        <div class="inside">

                            <p>
                                <label>
                                    <?php printf(
                                        __('Purge messages older than %s days. (leave empty to disable)', 'bbp-messages'),
                                        sprintf('<input type="number" name="older_delete_days" value="%s" />', $opt->older_delete_days)
                                    ); ?>
                                </label>
                            </p>

                            <em><?php _e('This setting allows you to purge older messages from the database. Choose how many days the message sent date is older than, and the weekly cleanup task will cleanup messages accordingly.', 'bbp-messages'); ?></em>

                        </div>
                    </div>

                    <?php do_action('bbpm_admin_settings'); ?>

                    <div class="postbox">
                        <h3 class="hndle"><?php _e('Save Changes', 'bbp-messages'); ?></h3>
                        <div class="inside">
                            <p>
                                <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Changes', 'bbp-messages'); ?>" />
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </form>

        <form method="post" style="display: none">
            <input type="hidden" name="purge_all_transients" />
            <input type="submit" id="purge_all_transients" style="display: none">
        </form>

        <form method="post" style="display: none">
            <input type="hidden" name="purge_expired_transients" />
            <input type="submit" id="purge_expired_transients" style="display: none">
        </form>

        <?php
    }

    public function update()
    {
        /** purge transients **/
        if ( isset($_POST['purge_expired_transients']) ) {
            return $this->admin()->feedback(sprintf(
                __('Successfully deleted %d expired transients.', 'bbp-messages'),
                bbpm_messages()->transientsCleanup()
            ), true);
        } else if ( isset($_POST['purge_all_transients']) ) {
            return $this->admin()->feedback(sprintf(
                __('Successfully deleted %d transients.', 'bbp-messages'),
                bbpm_messages()->uninstallTransients()
            ), true);
        }
        /** update settings **/
        global $bbpm_options;
        $s = array();
        // messages per page
        if ( isset($_POST['pagi_messages']) && intval($_POST['pagi_messages']) ) {
            $s['pagi_messages'] = intval($_POST['pagi_messages']);
        }
        // chats per page
        if ( isset($_POST['pagi_chats']) && intval($_POST['pagi_chats']) ) {
            $s['pagi_chats'] = intval($_POST['pagi_chats']);
        }
        // email subject
        if ( isset($_POST['email_subject']) && trim($_POST['email_subject']) ) {
            $s['email_subject'] = esc_attr($_POST['email_subject']);
        }
        // email body
        if ( isset($_POST['email_body']) && trim($_POST['email_body']) ) {
            $s['email_body'] = esc_attr($_POST['email_body']);
        }
        // enable HTML emails
        $s['html_emails'] = isset($_POST['html_emails']);
        // email body
        if ( isset($_POST['cache_ctx']) && trim($_POST['cache_ctx']) ) {
            switch (strtolower($_POST['cache_ctx'])) {
                case 'transients':
                    $s['cache_ctx'] = 'transients';
                    break;

                case 'none':
                    $s['cache_ctx'] = 'none';
                    break;
                
                default:
                    $s['cache_ctx'] = 'object';
                    break;
            }
        }
        // delete older messages
        if ( isset($_POST['older_delete_days']) && intval($_POST['older_delete_days']) ) {
            $s['older_delete_days'] = intval($_POST['older_delete_days']);
        }

        if ( isset($_POST['menu']) && is_array($_POST['menu']) ) {
            $_POST['menu'] = array_map('intval', (array) $_POST['menu']);
            $_POST['menu'] = array_filter($_POST['menu']);
            $_POST['menu'] = array_filter($_POST['menu'], 'get_term');
            if ( $_POST['menu'] ) {
                $s['menu_locations'] = $_POST['menu'];
            }
        }

        if ( isset($_POST['menu_text']) && trim($_POST['menu_text']) ) {
            $s['menu_text'] = sanitize_text_field($_POST['menu_text']);
        }

        // save to db
        update_site_option('bbpm_settings', $s);
        // update global variable
        $bbpm_options = wp_parse_args($s, bbpm_options_default());
        // print feedback
        $this->admin()->feedback(__('Changes saved successfully!', 'bbp-messages'));
        // trigger hook
        do_action('bbpm_update_admin_settings');
    }
}
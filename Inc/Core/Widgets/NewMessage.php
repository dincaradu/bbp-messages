<?php namespace BBP_MESSAGES\Inc\Core\Widgets;

class NewMessage extends \WP_Widget
{
    public function __construct() {
        parent::__construct(
            'bbPMNewMessage', 
            __('bbPM new message', 'bbp-messages'), 
            array(
                'description' => __('Allows current user to easily compose a new message selecting recipients from the dropdown', 'bbp-messages')
            ) 
        );
    }

    public function widget($args, $instance)
    {
        if( !is_user_logged_in() )
            return;

        do_action('bbpm_widget_start_output');
        do_action('bbpm_widget_new_message_start_output');

        global $current_user, $bbpm_bases;

        $title = apply_filters( 'widget_title', $instance['title'] );
        $getUsers = $this->getUsers();

        bbpm_load_template('widgets/new-message.php', compact('current_user', 'title', 'args', 'bbpm_bases', 'getUsers'));
    }

    public function form($instance)
    {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>" style="font-weight:bold;"><?php _e('Widget Title:', 'bbp-messages'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();

        if ( isset($new_instance['title']) && trim($new_instance['title']) ) {
            $instance['title'] = sanitize_text_field($new_instance['title']);
        }

        return $instance;
    }

    public function getUsers()
    {
        $args = array(
            'exclude' => array(get_current_user_id()),
            'fields' => array('ID', 'display_name'),
            'orderby' => 'display_name'
        );
        return get_users(apply_filters('bbpm_widgets_new_message_get_users', $args));
    }
}
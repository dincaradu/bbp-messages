<?php namespace BBP_MESSAGES\Inc\Core\Widgets;

class Welcome extends \WP_Widget
{
    public function __construct() {
        parent::__construct(
            'bbPMWelcome', 
            __('bbPM Welcome', 'bbp-messages'), 
            array(
                'description' => __('Widget with user welcoming note, messages links with unread counts, and a logout link', 'bbp-messages')
            ) 
        );
    }

    public function widget($args, $instance)
    {
        if( !is_user_logged_in() )
            return;

        do_action('bbpm_widget_start_output');

        global $current_user, $bbpm_bases;

        $title = apply_filters( 'widget_title', $instance['title'] );
        
        bbpm_load_template('widgets/welcome.php', compact('current_user', 'title', 'args', 'bbpm_bases'));
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
}
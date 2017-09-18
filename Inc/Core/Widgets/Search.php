<?php namespace BBP_MESSAGES\Inc\Core\Widgets;

class Search extends \WP_Widget
{
    public function __construct() {
        parent::__construct(
            'bbPMSearch', 
            __('bbPM Chats Search Form', 'bbp-messages'), 
            array(
                'description' => __('Prints out a search form for searching user chats', 'bbp-messages')
            ) 
        );
    }

    public function widget($args, $instance)
    {
        if( !is_user_logged_in() )
            return;

        do_action('bbpm_widget_start_output');

        global $current_user;

        $title = apply_filters( 'widget_title', $instance['title'] );
        
        bbpm_load_template('widgets/search.php', compact('current_user', 'title', 'args'));
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
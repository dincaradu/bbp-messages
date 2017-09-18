<?php namespace BBP_MESSAGES\Inc\Core\Widgets;

class MyContacts extends \WP_Widget
{
    public function __construct() {
        parent::__construct(
            'bbPMMyContacts', 
            __('bbPM My Contacts', 'bbp-messages'), 
            array(
                'description' => __('Lists current user\'s contacts', 'bbp-messages')
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
        $limit = isset($instance['limit']) ? intval($instance['limit']) : 0;

        if ( !intval($limit) ) {
            $limit = 10;
        }

        $m = bbpm_messages();
        $chats = $m->getUserChatsRaw($m->current_user);
        $contacts = $m->getUserContacts($m->current_user, $limit);
        
        bbpm_load_template('widgets/my-contacts.php', compact('current_user', 'title', 'args', 'contacts'));
    }

    public function form($instance)
    {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $limit = isset($instance['limit']) ? intval($instance['limit']) : 10;
        ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>" style="font-weight:bold;"><?php _e('Widget Title:', 'bbp-messages'); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>

            <p>
                <label for="<?php echo $this->get_field_id( 'limit' ); ?>" style="font-weight:bold;"><?php _e('Max items:', 'bbp-messages'); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="number" value="<?php echo $limit; ?>" />
            </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();

        if ( isset($new_instance['title']) && trim($new_instance['title']) ) {
            $instance['title'] = sanitize_text_field($new_instance['title']);
        }

        if ( isset($new_instance['limit']) && intval($new_instance['limit']) ) {
            $instance['limit'] = intval($new_instance['limit']);
        }

        return $instance;
    }
}
<?php namespace BBP_MESSAGES\Inc\Core\Widgets;

class MyChats extends \WP_Widget
{
    public function __construct() {
        parent::__construct(
            'bbPMMyChats', 
            __('bbPM My Chats', 'bbp-messages'), 
            array(
                'description' => __('Lists current user\'s recent chats', 'bbp-messages')
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
        $unread = isset($instance['unread']);

        if ( !intval($limit) ) {
            $limit = 10;
        }

        $messages = bbpm_messages();
        $messages->set(array(
            'per_page' => $limit
        ));

        if ( $unread ) {
            $chats = $messages->unreadChats(null, true, false)->get('unread_chats');
        } else {
            $chats = $messages->chats()->get('chats');
        }

        if ( $chats ) {
            $chats = array_map(array($messages, 'prepareChat'), $chats);
        }

        bbpm_load_template('widgets/my-chats.php', compact('current_user', 'title', 'args', 'chats', 'unread'));
    }

    public function form($instance)
    {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $limit = isset($instance['limit']) ? intval($instance['limit']) : 10;
        $unread = isset($instance['unread']);
        ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>" style="font-weight:bold;"><?php _e('Widget Title:', 'bbp-messages'); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>

            <p>
                <label for="<?php echo $this->get_field_id( 'limit' ); ?>" style="font-weight:bold;"><?php _e('Max items:', 'bbp-messages'); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="number" value="<?php echo $limit; ?>" />
            </p>

            <p>
                <input id="<?php echo $this->get_field_id('unread'); ?>" name="<?php echo $this->get_field_name( 'unread' ); ?>" type="checkbox" <?php checked($unread); ?> />
                <label for="<?php echo $this->get_field_id( 'unread' ); ?>"><?php _e('List unread chats only', 'bbp-messages'); ?></label> 
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

        if ( isset($new_instance['unread']) ) {
            $instance['unread'] = 1;
        }

        return $instance;
    }
}
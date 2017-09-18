<?php echo $args['before_widget']; ?>

<?php if ( $title ) : ?>
    
    <?php echo $args['before_title'] . $title . $args['after_title']; ?>

<?php endif; ?>

<form method="post" action="<?php echo bbpm_messages_url('send', $current_user->ID); ?>" class="bbpm">

    <ul class="bbpm-widget new-message">

        <li class="form-section">
            <?php echo bbpm_message_field(); ?>
        </li>

        <li class="form-section">
            <select name="recipient">
                <option value=""><?php _e('Select a Contact', 'bbp-messages'); ?></option>
                <?php if ( $getUsers ) : ?>
                    <?php foreach ( $getUsers as $user ) : ?>

                        <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>

                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <?php if ( bbpm_has_errors('recipient') ) : ?>
                <?php bbpm_print_error('recipient'); ?>
            <?php endif; ?>
        </li>

        <li class="form-section">
            <?php wp_nonce_field('send_message', 'bbpm_nonce'); ?>
            <input type="submit" value="<?php esc_attr_e('Send', 'bbp-messages'); ?>" />
        </li>
    </ul>

</form>

<?php echo $args['after_widget']; ?>

<?php global $bbpm_bases;

?>

<?php echo $args['before_widget']; ?>

<?php if ( $title ) : ?>
    
    <?php echo $args['before_title'] . $title . $args['after_title']; ?>

<?php endif; ?>

<?php if ( $contacts ) : ?>
    <ul class="bbpm-widget my-contacts">

        <?php foreach ( $contacts as $user ) : ?>

            <li>
                <a href="<?php echo bbp_get_user_profile_url($user->ID); ?>">
                    <?php echo get_avatar($user->ID, 33); ?>
                    <span><?php echo $user->display_name; ?></span>
                </a>
                <br/>
                <a href="<?php echo bbpm_messages_url($bbpm_bases['with'] . '/' . $user->ID, $current_user->ID); ?>">
                    <?php _e('View Messages &rarr;', 'bbp-messages'); ?>
                </a>
            </li>

        <?php endforeach; ?>

    </ul>
<?php else : ?>

    <p><?php _e('No contacts were found.', 'bbp-messages'); ?></p>

<?php endif; ?>

<?php echo $args['after_widget']; ?>
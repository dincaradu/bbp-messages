<?php echo $args['before_widget']; ?>

<?php if ( $title ) : ?>
    
    <?php echo $args['before_title'] . $title . $args['after_title']; ?>

<?php endif; ?>

<ul class="bbpm-widget search">

    <form method="get" action="<?php echo bbpm_messages_url(null, $current_user->ID); ?>">
        <input type="text" name="search" value="<?php echo esc_attr(bbpm_search_query()); ?>" placeholder="<?php esc_attr_e('Search', 'bbp-messages'); ?>" />
    </form>

</ul>
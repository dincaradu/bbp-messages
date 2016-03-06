<?php

class bbpm_widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'bbpm_widget', 
			__('bbPress Messages', 'wordpress'), 
			array( 'description' => __( 'Widget with user welcoming note, messages and archives links and counts, and a logout link', 'wordpress' ), ) 
		);
	}
	public function widget( $args, $instance ) {
		
		if( ! is_user_logged_in() )
			return;

		$user = wp_get_current_user();

		?>

			<style type="text/css">
				#bbpm_welcome_widget li {
				  list-style: inherit;
				  margin-left: 5px;
				}
				#bbpm_welcome_widget .avatar {
				  float: right;
			      position: absolute;
			      top: 3px;
			      right: 3px;
				}
				#bbpm_welcome_widget li:nth-child(2) {
				  margin-top: 5px;
				}
				#bbpm_welcome_widget {
				  border: 1px solid #F3F3F3;
				  padding: 16px 13px;
				  position: relative;
				}
				#bbpm_welcome_widget .top {
				    margin: 0 -14px 7px -14px;
				    border-bottom: 1px solid #F3F3F3;
				    height: 23px;
				}
				#bbpm_welcome_widget h6 {
				    padding-left: 14px;
				    position: relative;
				    top: -11px;
				    display: inline-block;
				}
			</style>

			<div id="bbpm_welcome_widget">
				<div class="top">
					<a href="<?php echo bbp_get_user_profile_url($user->ID); ?>" title="View profile"><?php echo get_avatar($user->ID, '32'); ?></a>
					<h6>Welcome, <a href="<?php echo bbp_get_user_profile_url($user->ID); ?>" title="View profile"><?php echo $user->user_nicename; ?></a>!</h6>
				</div>
				<li><a href="<?php echo bbpm_messages_base(false, $user->ID); ?>">Messages (<?php echo bbpm_get_counts($user->ID)->unreads; ?>)</a></li>
				<li><a href="<?php echo bbp_get_user_profile_url($user->ID); ?>edit/" title="Edit profile">Edit profile</a></li>
				<li><a href="<?php echo wp_logout_url(); ?>">Log out</a></li>
			</div>

		<?php
		
	}
}

/*class BBP_messages_widget
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {

		//

	}



}*/
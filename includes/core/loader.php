<?php

class BBP_messages_init
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {

		require_once BBPM_PATH . 'includes/admin/loader.php';
		require_once BBPM_PATH . 'includes/core/activate.php';
		require_once BBPM_PATH . 'includes/core/message.php';
		require_once BBPM_PATH . 'includes/core/hooks.php';
		require_once BBPM_PATH . 'includes/core/user.php';
		require_once BBPM_PATH . 'includes/core/widget.php';
		require_once BBPM_PATH . 'includes/core/shortcodes.php';

		$this->functions();

	}

	function init() {

		add_action('init', function() {

		    add_rewrite_rule(
		    	'forums/users/([^/]+)/' . bbpm_get_bases()->messages . '/?$',
		    	'index.php?bbp_user=$matches[1]&bbp_messages=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	'forums/users/([^/]+)/' . bbpm_get_bases()->messages . '/([^/]+)/?$',
		    	'index.php?bbp_user=$matches[1]&bbp_messages=1&bbp_messages_recipient=$matches[2]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	'forums/users/([^/]+)/' . bbpm_get_bases()->messages . '/([^/]+)/page/([0-9]+)/?$',
		    	'index.php?bbp_user=$matches[1]&bbp_messages=1&bbp_messages_recipient=$matches[2]&bbp_messages_page=$matches[3]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	'forums/users/([^/]+)/' . bbpm_get_bases()->messages . '/page/([0-9]+)/?$',
		    	'index.php?bbp_user=$matches[1]&bbp_messages=1&bbp_messages_page=$matches[2]',
		    	'top'
		    );

		});

		add_filter('query_vars', function($vars) {
		    $vars[] = "bbp_messages";
		    $vars[] = "bbp_messages_recipient";
		    $vars[] = "bbp_messages_page";
			return $vars;
		});

		add_action('wp', function() {

			global $current_user;

			if( bbpm_is_single_message() ) :;

				if( ! is_object( bbpm_get_recipient() ) || is_object( bbpm_get_recipient() ) && $current_user->ID == bbpm_get_recipient()->ID ) {

					wp_redirect( bbpm_messages_base() );
					exit;

				}

				if( isset( $_GET['do'] ) ) {

					$_do = (string) $_GET['do'];

					switch( $_do ) {
			
						case 'delete':
							
							$_message = isset( $_GET['m'] ) ? (int) $_GET['m'] : false;

							if( $_message )
								BBP_messages_message::instance()->delete( $_message );

							if( ! $_message )
								BBP_messages_message::instance()->delete(false, bbpm_get_conversation_id());

							break;
					}

				}

			endif;

			if( bbpm_is_messages() ) :;

				if( isset( $_GET['q'] ) && $q = strlen( sanitize_text_field( $_GET['q'] ) ) <= 0 ) {

					$url = $_SERVER['REQUEST_URI'];
					$url = substr( $url, 0, strpos( $url, 'q=' ) - 1 );
					wp_redirect( $url );
					exit;

				}

				if( ! is_user_logged_in() ) {

					$url = apply_filters('bbpmp_messages_logged_out_redirect_url', wp_login_url( $_SERVER['REQUEST_URI'] ));
					wp_redirect( $url );
					exit;

				}

				if( bbp_get_displayed_user_id() && $current_user->ID !== bbp_get_displayed_user_id() ) {

					wp_redirect( bbpm_messages_base( false, $current_user->ID ) );
					exit;

				}

			endif;

			bbpm_update_user_status();

			if( '1' == get_option('_bbpm_needs_flush') ) {
				flush_rewrite_rules();
				delete_option('_bbpm_needs_flush');
			}

		});

		add_action( 'wp_enqueue_scripts', function() {

			$base = get_stylesheet_directory_uri() . '/' . BBPM_DIR_NAME . '/assets/';
			$_base = get_stylesheet_directory() . '/' . BBPM_DIR_NAME . '/assets/';

			$child_css = $base . 'css/style.css';
			$core_css = BBPM_URL . 'assets/css/style.css';

			$css_file = file_exists( $_base . 'css/style.css' ) ? $child_css : $core_css;

			$child_js = $base . 'js/functions.js';
			$core_js = BBPM_URL . 'assets/js/functions.js';

			$js_file = file_exists( $_base . 'js/functions.js' ) ? $child_js : $core_js;

			wp_enqueue_style( 'bbpm', $css_file );
			wp_enqueue_script( 'bbpm', $js_file, array(), '1.0', true );

		});

		// solving possible redirect issues
		add_action('init', function() {ob_start();});

		add_action( 'wp_footer', function() {

			?>

				<script type="text/javascript">
					<?php if( bbp_get_user_profile_url() ) : ?>var bbpm_messages_base = '<?php echo bbpm_messages_base(); ?>';<?php endif; ?>
					var _bbpm_conf = {
						"del_m": "<?php echo apply_filters( 'bbpm_js_confirm_dialogs_delete_message', 'Are you sure you want to delete this message?' ); ?>",
						"del_c": "<?php echo apply_filters( 'bbpm_js_confirm_dialogs_delete_conversation', 'Are you sure you want to delete this conversation? Remember that this can\'t be undone.' ); ?>",
						"block": "<?php echo apply_filters( 'bbpm_js_confirm_dialogs_block', 'Are you sure you want to block this user?' ); ?>",
						"unblock": "<?php echo apply_filters( 'bbpm_js_confirm_dialogs_unblock', 'Are you sure you want to unblock this user?' ); ?>"
					}
				</script>

			<?php

		});

		add_action( 'wp_head', function() {

			if( bbpm_is_messages() ) :;
				
				ob_start();

					?>

						<style type="text/css">/* a CSS hack to hide other profile content while viewing messages. bbPress is kinda evil to not provide useful profile hooks for making this process much simple.. T_T */<?= "\n"; ?>#bbp-user-body .bbpm,#bbp-user-body .bbpm * {display: inherit;}#bbp-user-body * {display:none}</style>

					<?php

				echo apply_filters( 'bbpm_bbp_profile_css_hack', ob_get_clean() );

			endif;

		});

		add_action('bbp_template_before_user_profile', function() {
			if( bbpm_is_messages() ) {

				$classes = 'bbpm no-js';
				$classes .= ! bbpm_is_single_message() ? ' index' : '';
				$classes .= bbpm_get_conversation_id() ? ' pm-' . bbpm_get_conversation_id() : '';
				$classes .= ! bbpm_is_single_message() && ! bbpm_is_archives() ? ' conversations' : '';
				$classes .= ! bbpm_is_single_message() && bbpm_is_archives() ? ' archived' : '';
				$classes .= '' !== bbpm_get_search_query() ? ' search' : '';
				if( isset( $_GET['view'] ) && 'unread' == (string) $_GET['view'] )
							$classes .= ' unread';
				if( bbpm_get_conversation_id() )
					$classes .= bbpm_can_contact( bbpm_get_recipient_id() ) ? ' can-contact': ' cant-contact';

				?>
					<div class="<?php echo $classes; ?>">

						<?php bbpm_load_template(); ?>

					</div>
				<?php
			}
		});

		add_action('wp_footer', function() {

			global $current_user;

			if( $current_user->ID == bbp_get_displayed_user_id() ) :;

				$_msgs_inner = apply_filters( 'bbpm_js_profile_nav_messages_item_inner', 'Messages' );

				?>

					<script type="text/javascript">
						
						window.addEventListener('load', function() {

							var _list = document.querySelector('#bbp-user-navigation > ul');
							var _tar = document.querySelector('#bbp-user-navigation > ul > li:nth-child(2)');

							if( null !== _list && null !== _tar ) {

								var _li = document.createElement("LI");
								_li.innerHTML = '<li><span class="bbpm-view-messages"><a href="<?php echo bbpm_messages_base(); ?>" title="<?php echo $_msgs_inner; ?>"><?php echo $_msgs_inner; ?></a></span></li>';
								_list.insertBefore(_li, _tar);

							}

							<?php if( bbpm_is_messages() ) : ?>
								
								var _lis = document.querySelectorAll('#bbp-user-navigation > ul > li');
								
								for (var i=0;i<_lis.length;i++) {

									var _classes = null !== _lis[i].getAttribute('class') ? _lis[i].getAttribute('class') : '';

									if( _classes.indexOf('current') > -1 ) {
										_lis[i].setAttribute('class', _classes.replace('current', ''));
									}

								}

								var _msgs = document.querySelector('#bbp-user-navigation > ul > li:nth-child(2)');
								null == _msgs || _msgs.setAttribute('class', 'current');

								var _title = document.title;
								var _title_tag = document.getElementsByTagName('title')[0];
								var _replaceWith = '<?php bbpm_breadcrumb(); ?>';
								var _replaceWith = _replaceWith.replace(/<(?:.|\n)*?>/gm, '');
								var _replaceWith = <?php echo apply_filters('bbpm_messages_title_js_replace_with', 'decodeTitle( _replaceWith );'); ?>
								_title_tag.innerText = _title.replace('<?php echo translate( 'Your Profile', 'bbpress' ); ?>', _replaceWith);

							<?php endif; ?>

						}, false);

					</script>

				<?php

			endif;

		});

		add_action( 'widgets_init', function() {
			register_widget( 'bbpm_widget' );
		});


	}

	public function functions() {

		function bbpm_is_messages() {
			return '1' == get_query_var( 'bbp_messages', false );
		}

		function bbpm_is_single_message() {
			$_excluded = array( bbpm_settings()->slugs->archives, 'page' );
			/** in other words, 'archives' and 'page' can not be user nicenames, meaning 
			  * that you can't contact a user if their user nicename is 'archives', just
			  * because it is reserved for the archives directory
			  * You can use array_push to push more strings to ignore into (array) $_excluded
			  */
			$_excluded = apply_filters( 'bbpm_sub_page_ignored_possible_user_slugs', $_excluded );
			$_var = get_query_var( 'bbp_messages_recipient', false );
			$_array = array_keys($_excluded, $_var);
			return $_var && empty( $_array );
		}
		function bbpm_get_recipient() {
			
			$object = false;

			if( bbpm_is_single_message() ) {
				$object = get_user_by( 'slug', get_query_var( 'bbp_messages_recipient', false ) );
			}

			return $object;

		}

		function bbpm_get_recipient_id() {

			return is_object( bbpm_get_recipient() ) ? bbpm_get_recipient()->ID : false;

		}

		function bbpm_load_template() {

			do_action('bbpm_before_template_load');

			if( bbpm_is_single_message() ) :;

				include_once bbpm_template_path( 'content-messages' );

			elseif( bbpm_is_messages() ) :;

				include_once bbpm_template_path( 'loop-messages' );

			elseif( bbpm_is_archives() ) :;

				include_once bbpm_template_path( 'loop-messages' );

			endif;

			do_action('bbpm_after_template_load');

		}

		function bbpm_messages_base( $sub = '', $user_id = false ) {

			if( ! $user_id )
				$user_id = bbp_get_displayed_user_id();

			if( ! get_userdata( $user_id ) )
				$user_id = wp_get_current_user()->ID;

			return bbp_get_user_profile_url( $user_id ) . bbpm_get_bases()->messages . '/' . $sub;

		}

		function bbpm_conversation_form( $recipient = false ) {

			$bail = ! bbpm_can_contact( $recipient );

			if( ! $bail)
				BBP_messages_message::instance()->_send();

			do_action('bbpm_before_conversation_form', $recipient);

			ob_start();

				?>
					
					<?php echo $bail ? apply_filters('bbpm_cant_contact_notice', '<span class="bbpm-cant-contact">Sorry, you can not contact this user for the moment.<span>') : ''; ?>
					<form action="<?php echo bbpm_get_conversation_permalink( false, $recipient ); ?>" method="post" <?php echo $bail ? 'class="disabled" ' : ' ' ?>>
						<textarea name="_bbpm_message" <?php echo $bail ? 'disabled="disabled" ' : ' ' ?> placeholder="Write a message.."></textarea>
						<?php wp_nonce_field( '_bbpm_nonce', '_bbpm_nonce' ); ?>
						<input type="submit" name="_bbpm_send" value="Send" <?php echo $bail ? 'disabled="disabled" ' : ' ' ?>/>
					</form>

				<?php

			echo ob_get_clean();

			do_action('bbpm_after_conversation_form', $recipient);

		}

		function bbpm_get_conversation_permalink( $sub = '', $recipient = false ) {

			if( bbpm_get_recipient() ) :;

				if( ! $recipient )
					return bbpm_messages_base( get_query_var( 'bbp_messages_recipient', false ) ) . '/' . $sub;

				else
					return bbpm_messages_base( get_userdata($recipient)->user_nicename ) . '/' . $sub;

			endif;


		}

		function bbpm_can_contact( $recipient = false ) {
			
			$bail = false; # Pro feature for user blocking

			/**
			  * You can filter this boolean to set it to true|false as you want
			  * An example use is that let's say you don't want users to contact admins
			  * then we are setting $bail to true if $recipient is an admin
			  * to check if is admin use in_array('administrator', get_userdata($recipient)->roles )
			  */
			$bail = apply_filters('bbpm_can_contact', $bail, $recipient);

			return ! $bail;

		}

		function bbpm_my_conversations( $return_all = false ) {
			
			return BBP_messages_message::instance()->conversations( $return_all );

		}

		function bbpm_template_path( $name ) {

			$base = get_stylesheet_directory() . '/' . BBPM_DIR_NAME . '/themes/';

			#! $base = apply_filters('bbpm_template_path_directory', $base);

			$child_file = $base . $name . '.php';
			$core_file = BBPM_PATH . 'themes/' . $name . '.php';

			return file_exists( $child_file ) ? $child_file : $core_file;

		}

		function bbpm_message_snippet_classes( $pm_id ) {

			echo BBP_messages_message::instance()->snippet_classes( $pm_id );

		}
		function bbpm_message_classes( $ID ) {

			echo BBP_messages_message::instance()->message_classes( $ID );

		}

		function bbpm_get_conversation( $pm_id, $exists = false ) {
			return BBP_messages_message::instance()->get_conversation( $pm_id, $exists );
		}

		function bbpm_get_message( $ID ) {
			return BBP_messages_message::instance()->get_message( $ID );
		}

		function bbpm_message_snippet_excerpt( $ID ) {

			$message_body = bbpm_get_message( $ID )->message;
			$lenght = apply_filters( 'bbpm_message_snippet_excerpt_lenght', 150 );

			$message = substr( $message_body, 0, $lenght );
			$message .= strlen( $message_body ) > $lenght ? '...' : '';

			$message = stripslashes( $message );

			return apply_filters( 'bbpm_message_snippet_excerpt_content', $message, $ID );

		}

		function bbpm_time_diff( $target, $before = '', $after = '' ) {

				if( !isset( $target ) )
					return false;
				$target = new DateTime( date("Y-m-d H:i:s", $target) );
				$now = new DateTime( date("Y-m-d H:i:s", time()) );

				$delta = $now->diff($target);
				$quantities = array(
				    'year' => $delta->y,
				    'month' => $delta->m,
				    'day' => $delta->d,
				    'hour' => $delta->h,
				    'minute' => $delta->i,
				    'second' => $delta->s
				    );
				$str = '';
				foreach($quantities as $unit => $value) {
				    if($value == 0) continue;
				    $str .= $value . ' ' . $unit;
				    if($value != 1) {
				        $str .= 's';
				    }
				    $str .=  ', ';
				    break;
				}
				$str = $str == '' ? 'a moment' : substr($str, 0, -2);
			
				if( $before ) $before .= ' ';
				if( $after ) $after = ' ' . $after;

				$str = $before . $str .  $after;

				return apply_filters( 'bbpm_time_diff_string', $str, $target, $before, $after );

		}

		function bbpm_get_messages( $pm_id = false, $count = false ) {

			if( ! $pm_id )
				$pm_id = bbpm_get_conversation_id();

			return BBP_messages_message::instance()->messages( $pm_id, $count );

		}

		function bbpm_get_conversation_id( $recipient = false ) {

			if( ! $recipient )
				$recipient = bbpm_get_recipient_id() ? bbpm_get_recipient_id() : false;

			$pm = BBP_messages_message::instance()->pm_id( $recipient );
			return ! empty( $pm->exist ) && $pm->exist ? $pm->id : false;

		}

		function bbpm_output_message( $string ) {

			$string = stripslashes($string);

			$string = preg_replace_callback(
				"(\[img\](.*?)\[/img\])is",
				function($m) {
					return '<img src="'. str_replace( 'http', 'httpee', $m[1] ) .'" alt="image attachment" width="auto" />';
				},
				$string
			);

			$reg_exUrl = "/(http|https)\:\/\/(www.youtu|youtu)([a-zA-Z]|)+\.[a-zA-Z]{2,3}(\/\S*)?/";
		    preg_match_all($reg_exUrl, $string, $matches);
		    $usedPatterns = array();
		    foreach($matches[0] as $pattern){
		        if(!array_key_exists($pattern, $usedPatterns)){
		            $usedPatterns[$pattern]=true;
		            $parts = parse_url($pattern);
					if( ! empty( $parts['query'] ) )
						parse_str($parts['query'], $query);

					if( !empty( $query['v'] ) ) {

						$videoID = $query['v'];

					} else {

						$videoID = array_filter( explode( '/', $pattern ) );
						$videoID = end( $videoID );

					}

		            $string = str_replace( $pattern, '<iframe width="400" height="300" src="//www.youtube.com/embed/'.$videoID.'?rel=0" frameborder="0"></iframe>', $string );   

		        }
		    }

		    $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		    preg_match_all($reg_exUrl, $string, $matches);
		    $usedPatterns = array();
		    foreach($matches[0] as $pattern){
		        if(!array_key_exists($pattern, $usedPatterns)){
		            $usedPatterns[$pattern]=true;
		            $string = str_replace( $pattern, "<a href=\"".$pattern."\" rel=\"nofollow\" target=\"_blank\">$pattern</a> ", $string );   
		        }
		    }

		    $string = str_replace( 'httpee', 'http', $string );
			#$string = preg_replace('/\v+/','<br>', $string);

			$string = bbpm_nl2p( $string );
			
			return apply_filters( 'bbpm_the_message', $string );

		}

		function bbpm_single_help_text() {

			ob_start();

			?>
				<?php echo bbpm_settings()->help_text; ?>
				<span class="bbpm-toggle-help">[x]</span>
			<?php

			echo ob_get_clean();

		}

		function bbpm_last_message_from_me( $pm_id = false ) {

			if( !$pm_id ) $pm_id = bbpm_get_conversation_id();

			$object = BBP_messages_message::instance()->get_conversation( $pm_id );
			
			$bool = false;

			if( ! empty( $object->last_message ) && ! empty( $object->last_message->sender ) ) :;

				global $current_user;
				$bool = $current_user->ID == $object->last_message->sender;

			endif;

			return $bool;

		}

		function bbpm_get_single_seen_diff( $pm_id = false, $before = false, $after = false ) {

			if( !$pm_id ) $pm_id = bbpm_get_conversation_id();

			$object = BBP_messages_message::instance()->get_conversation( $pm_id );

			if( ! empty( $object->last_message ) && ! empty( $object->last_message->seen ) ) :;

				if( $object->last_message->seen ) {

					return bbpm_time_diff( $object->last_message->seen, $before, $after );

				}

			endif;

		}

		function bbpm_single_seen_notice( $pm_id = false ) {

			if( !$pm_id ) $pm_id = bbpm_get_conversation_id();

			$object = BBP_messages_message::instance()->get_conversation( $pm_id );

			if( bbpm_get_single_seen_diff( $pm_id ) && bbpm_last_message_from_me( $pm_id ) ) :;

				ob_start();

				?>

					<p class="bbpm-seen-notice"><?php echo bbpm_get_single_seen_diff( $pm_id, 'Seen', 'ago' ); ?></p>

				<?php

				echo apply_filters('bbpm_conversation_seen_notice', ob_get_clean(), $pm_id);

			endif;

		}

		function bbpm_conversation_search_form() {

			ob_start();

				?>
					<form method="get" action="<?php echo bbpm_get_conversation_permalink(); ?>">
						<input type="text" name="q" placeholder="search" value="<?php echo bbpm_get_search_query(); ?>" />
					</form>
				<?php

			echo ob_get_clean();

		}

		function bbpm_is_search_messages() {
			return bbpm_is_messages() && isset( $_GET['q'] );
		}

		function bbpm_get_user_blocked_list( $user_id = false ) {

			return array(); # This is a PRO feature;

		}

		function bbpm_can_notify( $user_id = false ) {

			global $current_user;

			if( ! $user_id )
				$user_id = $current_user->ID;

			return ! empty( bbpm_get_user_data( $user_id )->notify ) && 1 == (int) bbpm_get_user_data( $user_id )->notify;

		}

		function bbpm_is_user_blocked( $user_id = false, $_current_user = false ) {

			return false; # Pro feature

		}

		function bbpm_is_user_blocked_by( $user_id = false, $_current_user = false ) {

			return false; # Pro feature

		}


		function bbpm_block_link( $user_id = false ) {

			return false; # Pro feature

		}

		function bbpm_update_user_data( $args ) {

			$notify = $args['notify'] ? '1' : '0';
			$_time_int = apply_filters('bbpm_core_update_user_status_time_int', time(), $args);
			$object = '{ "blocked": "", "notify": "' . $notify . '", "archives": "", "last_seen": "' . $_time_int . '"  }';
			update_user_meta( $args['user'], '_bbpm_data', esc_attr($object) );

		}

		function bbpm_is_archived( $pm_id = false, $user_id = false ) {

			return false; # Pro feature

		}

		function bbpm_get_archives_list( $user_id ) {

			return array(); # Pro feature

		}

		function bbpm_archive_pm( $pm_id, $unarchive = false , $user_id = false ) {
			return false; # Pro feature
		}

		function bbpm_is_archives() {
			return false; # Pro feature
		}

		function bbpm_get_breadcrumb() {

			$_args = array(
				'before'	=> '<h2 class="entry-title">',
				'separator'	=> '&raquo;',
				'after'		=> '</h2>'
			);
			$_args = apply_filters('bbpm_breadcrumb_args', $_args);

			$_args_before = empty( $_args['before'] ) ? '<h2 class="entry-title">' : (string) $_args['before'];
			$_args_sep 	= empty( $_args['separator'] ) ? '&raquo;' : (string) $_args['separator'];
			$_args_after = empty( $_args['after'] ) ? '</h2>' : (string) $_args['after'];


			$_markup = $_args_before;

			if ( bbpm_is_messages() ) {

				if( ! bbpm_is_archives() ) {

					$_markup .= '<a href="' . bbpm_messages_base() . '">Messages</a>';

				} else {

					$_markup .= '<a href="' . bbpm_messages_base( bbpm_settings()->slugs->archives . '/' ) . '">Archives</a>';

				}

			}

			if ( bbpm_is_single_message() ) {

				$_pm_id = bbpm_get_conversation_id();

				if( bbpm_is_archived( $_pm_id ) ) {

					$_markup = $_args_before;
					$_markup .= '<a href="' . bbpm_messages_base( bbpm_settings()->slugs->archives . '/' ) . '">Archives</a>';

				}

				$_markup .= '&nbsp;' . $_args_sep . '&nbsp;' . bbpm_get_recipient()->user_nicename;

			}

			$_markup .= $_args_after;

			return $_markup;

		}

		function bbpm_breadcrumb() {
			echo bbpm_get_breadcrumb();
		}

		function bbpm_get_archives_link( $pm_id = false ) {

			return false; # Pro feature

		}

		function bbpm_archives_link( $pm_id = false ) {
			echo ''; # PRO feature;
		}

		function bbpm_get_search_query() {
			return isset( $_GET['q'] ) ? sanitize_text_field($_GET['q']) : '';
		}

		function bbpm_pagination( $return_args = false ) {

			$_per_pg = bbpm_is_single_message() ? bbpm_settings()->pagination->messages : bbpm_settings()->pagination->conversations;
			$_per_pg = apply_filters('bbpm_messages_per_page', $_per_pg);
			$return_all = ! bbpm_is_archives() ? 'index' : true;
			$_found_cnt = bbpm_is_single_message() ? bbpm_get_messages(false, true) : count( bbpm_my_conversations( $return_all ) );
			$_last = $_found_cnt / $_per_pg;
			$_last += is_float( $_last ) ? 1 : 0;
			$_last = (int) $_last;

			$_current = get_query_var('bbp_messages_page', false);
			$_current = is_numeric( $_current ) ? $_current : 1;
			$_current = $_current > $_last ? $_last : $_current;
			$_current = $_current > 0 ? (int) $_current : 1;

			if( $return_args )
				return (object) array(
					'total_found' => $_found_cnt,
					'last_page'	=> $_last,
					'messages_per_page'	=> $_per_pg,
					'current_page' => $_current
				);

			$_next = $_current + 1;
			$_prev = $_current - 1;


			$_sub = bbpm_is_search_messages() ? bbpm_get_search_query() : false;
			$_sub = $_sub ? '?q=' . $_sub : '';

			$_prev_sub = 'page/' . $_prev . '/' . $_sub;
			
			if( $_prev < 2 )
				$_prev_sub = $_sub;

			function _base( $__sub = false) {
				if( bbpm_is_single_message() ) {
					$link = bbpm_get_conversation_permalink( $__sub );
				} else {
					if( bbpm_is_archives() )
						$__sub = bbpm_settings()->slugs->archives . '/' . $__sub;
					$link = bbpm_messages_base( $__sub );
				}

				if( isset( $_GET['view'] ) && 'unread' == (string) $_GET['view'] ) {
					$link .= '?view=unread';
				}

				return $link;
			}

			ob_start(); 
			
				?>

					<?php if( $_current > 2 ) : ?>
						<a href="<?php echo _base( $_sub ); ?>" title="First page">&laquo;</a>
					<?php endif; ?>
					
					<?php if( $_current > 1 ) : ?>
						<a href="<?php echo _base( $_prev_sub ); ?>" title="Previous page">&lsaquo;</a>
					<?php endif; ?>

					<span class="current">page <?php echo $_current; ?> / <?php echo $_last; ?></span>

					<?php if( $_last > $_current ) : ?>
						<a href="<?php echo _base('page/' . $_next . '/' . $_sub); ?>" title="Next page">&rsaquo;</a>
					<?php endif; ?>

					<?php if( $_last > $_current && ( ( $_current + 1 ) < $_last ) ) : ?>
						<a href="<?php echo _base('page/' . $_last . '/' . $_sub); ?>" title="Last page">&raquo;</a>
					<?php endif; ?>

				<?php

			return ob_get_clean();

		}

		function bbpm_update_user_status() {
			return BBP_messages_user::instance()->update_status();
		}

		function bbpm_get_user_last_seen( $user_id = false, $return_args = false ) {

			global $current_user;

			if( ! $user_id )
				$user_id = $current_user->ID;

			$meta = get_user_meta( $user_id, '_bbpm_data', TRUE );
			$return = array();

			$last_seen = false;

			if( '' !== $meta ) {

				$ob = json_decode( html_entity_decode($meta), false );

				if( ! empty( $ob->last_seen ) )
					$last_seen = (int) $ob->last_seen;

			}

			$args = array(
				'before' => 'last seen',
				'after' => 'ago'
			);

			$args = apply_filters('bbpm_user_last_seen_args', $args);

			$_diff = bbpm_time_diff( $last_seen, $args['before'], $args['after'] );

			if( $return_args )
				return (object) array(
					'integer' => $last_seen,
					'difference' => $last_seen ? bbpm_time_diff( $last_seen ) : false,
					'full_difference' => $last_seen ? $_diff : false
				);

			if( $last_seen ) {
				return $_diff;
			} else {
				return false;
			}

		}

		function bbpm_get_counts( $user_id = false, $pm_id = false ) {
			return BBP_messages_message::instance()->counts( $user_id, $pm_id );
		}

		function bbpm_need_pagination() {
			$object = bbpm_pagination(true);
			return ! empty( $object ) && bbpm_is_messages() && ( $object->total_found > $object->messages_per_page );
		}

		function bbpm_is_blocking_allowed() {
			$bool = bbpm_settings()->blocking;
			return apply_filters( 'bbpm_is_blocking_allowed', $bool );
		}
		function bbpm_can_mark_unread() {
			return apply_filters( 'bbpm_can_mark_unread', false ); # PRO feature
		}

		function bbpm_get_bases() {

			return (object) array(
				'messages' => bbpm_settings()->slugs->messages,
				'archives' => bbpm_settings()->slugs->archives
			);

		}

		function bbpm_get_user_data( $user_id = false ) {

			if( ! $user_id )
				$user_id = wp_get_current_user()->ID;

			$meta = get_user_meta( $user_id, '_bbpm_data', TRUE );

			if( '' !== $meta ) {

				$ob = json_decode( html_entity_decode($meta), false );
				$ob->notify = (int) $ob->notify;
				$return = $ob;

			} else {

				$return = new stdClass();
				$return->blocked = false;
				$return->notify = 1;
				$return->archives = false;
				$return->last_seen = false;
			}

			return (object) $return;
			
		}

		function bbpm_nl2p( $string ) {
			# credit: http://stackoverflow.com/questions/7409512/new-line-to-paragraph-function#7409591
			$string = str_replace(array('<p>', '</p>', '<br>', '<br />'), '', $string);
			$string = '<p>'.preg_replace( array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"), array("</p>\n<p>", "</p>\n<p>", '$1<br/>$2'), trim($string)).'</p>'; 
		    return str_replace( '<br/>', "</p>\n<p>", $string );
		}

		function bbpm_notice( $identifier ) {

			switch( $identifier ) {

				case 'delete':
					if( bbpm_is_single_message() ) {
						$notice = apply_filters('bbpm_notice_message_delete', 'Message deleted successfully.');
					} else {
						$notice = apply_filters('bbpm_notice_conversation_delete', 'Conversation deleted successfully.');
					}
					$done = true;
					break;

				case 'err-delete':
					$notice = apply_filters('bbpm_notice_error_delete', 'Error deleting message(s). Please try again.');
					$done = false;
					break;

				case 'err-sending':
					$notice = apply_filters('bbpm_notice_error_sending', 'Error sending message. Please try again.');
					$done = false;
					break;

				$notice = apply_filters('bbpm_filter_notice', $notice, $identifier);
				$done = apply_filters('bbpm_filter_notice_success', $done, $identifier);

			}

			return (object) array(
				'notice' => $notice,
				'success' => $done
			);

		}

	}

}

BBP_messages_init::instance()->init();
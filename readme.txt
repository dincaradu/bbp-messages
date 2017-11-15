=== bbPress Messages ===
Contributors: elhardoum
Tags: messages, bbPress, forums, private messages, buddypress, contact, widget, embed, conversations, notifications, email, child themes
Requires at least: 3.0.1
Tested up to: 4.8.3
Stable tag: 2.0.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Author URI: https://samelh.com
Donate link: https://go.samelh.com/buy-me-a-coffee

bbPress Messages - Simple yet powerful private messaging system tailored for bbPress.

== Description ==

bbPress Messages - Simple yet powerful private messaging system tailored for bbPress.

<h3>What's New in 2.0?</h3>

<h4>Fully Optimized</h4>
<p>Now bbPress Messages loads faster due to optimization and messages caching.</p>

<h4>Background Schedules</h4>
<p>For better experience and load time, now the tasks are processed in the background. Tasks that would take some time to finish such as email notifications, after send events, cleanup schedules and others.</p>

<h4>Translation ready</h4>
<p>Now you can use the languages files to <a href="https://codex.wordpress.org/I18n_for_WordPress_Developers">translate</a> bbPress Messages to your language. You can also contribute your translations and <a href="https://translate.wordpress.org/projects/wp-plugins/bbp-messages">help translate this plugin</a>.</p>

<h4>Shortcodes</h4>
<p>We've added couple shortcodes and rewrote older shortcodes, you can find out about shortcodes in the shortcodes tab in the settings page.</p>

<h4>Widgets</h4>
<p>We've redesigned the widgets for 2.0! Navigate to your dashboard and search for bbPM widgets now.</p>

<p>Other than that, bbPress Messages 2.0 is easy to extend, supports multisite installations, and we will be working on some free addons to extend its features and improve more. Please consult the project Github page <a href="https://github.com/elhardoum/bbp-messages">https://github.com/elhardoum/bbp-messages</a> to report a bug, contribute to the project or make suggestions.</p>

<p>If this is the first time you update, you should be migrating your older messages from the legacy database table to the new one. This could be done using the import tab in settings.</p>

We will cover some free and premium addons to power group chats and other features, sign up for the newsletter to get notified.

<em>More documentation coming soon..</em>

For more WordPress/bbPress/BuddyPress free and premium plugins, sign up for the newsletter: http://go.samelh.com/newsletter

== Installation ==

* Install and activate the plugin:

1. Upload the plugin to your plugins directory, or use the WordPress installer.
2. Activate the plugin through the \'Plugins\' menu in WordPress.
3. Once done, use the plugin settings link to access settings, or go to Dashboard > Users > Restrict Registration

Enjoy!

== Screenshots ==

1. Single chat view
2. Chats view
3. Chat settings
4. Basic email notification
5. Admin settings screen

== Changelog ==
= 2.0.9 =
* PHP <5.4: fix `Using $this when not in object context` (see https://wordpress.org/support/?p=9307124)

= 2.0.8 =
* A quick update to merge the github updates into wp
* Add messages counter to menu (see https://wordpress.org/support/?p=9319644)

= 2.0.7 =
* See Github <a href="https://github.com/elhardoum/bbp-messages/issues/1">#1</a> 

= 2.0.4 =
* Added widgets with minor improvements to core

= 2.0 =
* Rewrote the plugin from scratch

= 0.2.3 =
* Made it more extensible, and fixed couple bugs and done few improvements.

= 0.2.2 =
* Added couple hooks

= 0.2.1 =
* Fixed bug related to user email notification preferences
* Fixed 404 issues related to bbPress users base in the forums

= 0.1.1.3 =
* Fixed bug related to user email notification preferences

= 0.1.1.2 =
* Fixed a bug related to user email confirmation
* Fixed a bug related to bbPress user profile saving edits, Thanks nuzik for the heads up!

= 0.1.1.1 =
* Forgot to include flushing rewrite rules upon plugin activation, which will fix the 404 issues.

= 0.1.1 =
* Fixed a bug: when using BuddyPress, the bbPress profile link is overwritten thus the messages page gives 404.
* Other few improvements.

= 0.1 =
* Initial release.
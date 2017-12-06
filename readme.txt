=== BuddyPress Activity Comment Notifier ===
Contributors: buddydev, sbrajesh
Tags: buddypress, notification, activity, activity comment, activity comment notification
Requires at least: BuddyPress 2.0+
Tested up to: BuddyPress 2.9.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BuddyPress Activity Comment Notifier plugin emulates the facebook style notification for the comments made on user activity. 

== Description ==
BuddyPress Activity Comment Notifier plugin emulates the facebook style notification for the comments made on user activity. It will show the notification to a user in following scenario

*	When a user has an update and someone else comments on it(It is handled by BuddyPress Now)
*	When a user comments on someone’s update and other users also comment on that update, all the users are notified
*	When a user favorites your activity

For more details, please visit [BuddyPress Activity Comment Notifier plugin page](http://buddydev.com/plugins/buddypress-activity-comment-notifier/ "Plugin page" )

Free & paid supports are available via [BuddyDev Support Forum](http://buddydev.com/support/forums/ "BuddyDev support forums")

== Installation ==

1. Download `bp-activity-comment-notifier-x.y.z.zip` , x.y.z are version numbers eg. 1.0.0
1. Extract the zip file
1. Upload `bp-activity-comment-notifier` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= How do I get support? =

Please use [BuddyDev forums](http://buddydev.com/support/forums/) for any support question. We are helpful people and stand behind our plugins firmly.

= Does it sends a mail notification? = 
No. It creates local BuddyPress notification only.


== Screenshots ==

1. Comment Notification list on user notifications page screenshot-1.png.
2. Comment notification in the adminbar notification list screenshot-2.png

== Changelog ==

= 1.2.0 =
* Fix double notification bug for original author of the activity.

= 1.1.4 =
* Links to the actual comment fragment on the page
* a little bit of code cleanup

= 1.1.3 =
* Add translation support. Thank to @cadic and @bazalt
* Add a filter to skip notification for favorites. Thanks to Jamie

= 1.1.2 =
* Fix the issue with unfavorite notification not being deleted
* Do not delete notification on read, mark them as read instead
* Add filter 'ac_notifier_skip_notification' to allow skipping notifications for some activity types if needed

= 1.1.1 =
* Initial release on wporg repo

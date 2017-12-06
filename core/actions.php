<?php
// Do not allow direct access over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Notify users ( original poster+all the users who commented on the activity )
 *
 * @param int   $comment_id comment id.
 * @param array $params params.
 */
function ac_notifier_notify_on_new_comment( $comment_id, $params ) {

	$bp = buddypress();

	$activity_id = $params['activity_id'];

	$users    = ac_notifier_find_involved_persons( $activity_id );
	$activity = new BP_Activity_Activity( $activity_id );

	// Let us add a filter to allow skipping some of the activity types if required.
	if ( apply_filters( 'ac_notifier_skip_notification', false, $activity ) ) {
		return;
	}

	// These user ids won't be notified.
	$excluded_ids = array();
	// Do not notify original poster as BuddyPress does it for us.
	$excluded_ids[] = $activity->user_id;
	/*
	if ( ! in_array( $activity->user_id, $users ) && ( get_current_user_id() != $activity->user_id ) ) {
		// push the original poster user_id, if the original poster has not commented on his/her status yet.
		array_push( $users, $activity->user_id );
	}*/

	// Is it a reply?
	if ( ! empty( $params['parent_id'] ) && ( $params['activity_id'] != $params['parent_id'] ) ) {
		$parent_comment = new BP_Activity_Activity( $params['parent_id'] );
		$excluded_ids[] = $parent_comment->user_id;
	}

	// Remove the excluded ids from list.
	$users = array_diff( $users, $excluded_ids );

	foreach ( (array) $users as $user_id ) {

		// create a notification for each of the user.
		// We are creating the dynamic component_action to bypass the BuddyPress grouping of notifications.
		bp_notifications_add_notification( array(
			'item_id'           => $activity_id,
			'user_id'           => $user_id,
			'component_name'    => $bp->ac_notifier->id,
			'component_action'  => 'new_activity_comment_' . $activity_id,
			'secondary_item_id' => $comment_id,
		) );
	}
}

add_action( 'bp_activity_comment_posted', 'ac_notifier_notify_on_new_comment', 10, 2 );


/**
 * Notify user on favorite.
 *
 * @param int $activity_id activity id.
 * @param int $user_id user id.
 */
function ac_notify_on_favorite( $activity_id, $user_id ) {

	$bp       = buddypress();
	$activity = new BP_Activity_Activity( $activity_id );

	if ( empty( $activity->id ) || $activity->user_id == $user_id ) {
		return;
	}

	if ( apply_filters( 'ac_notifier_skip_favorite_notification', false, $activity ) ) {
		return;
	}

	bp_notifications_add_notification( array(
		'item_id'           => $activity_id,
		'user_id'           => $activity->user_id,
		'component_name'    => $bp->ac_notifier->id,
		'component_action'  => 'new_activity_favorite_' . $activity_id,
		'secondary_item_id' => $user_id,
	) );
}

add_action( 'bp_activity_add_user_favorite', 'ac_notify_on_favorite', 10, 2 );

/**
 * Delete notification on un-favorite.
 *
 * @param int $activity_id activity id.
 * @param int $user_id user id.
 */
function ac_notifier_delete_notification_on_unfavorite( $activity_id, $user_id ) {
	$bp       = buddypress();
	$activity = new BP_Activity_Activity( $activity_id );

	bp_notifications_delete_notifications_by_item_id( $activity->user_id, $activity_id, $bp->ac_notifier->id, 'new_activity_favorite_' . $activity_id, $user_id );

}

add_action( 'bp_activity_remove_user_favorite', 'ac_notifier_delete_notification_on_unfavorite', 10, 2 );

/**
 * Delete comment notification when activity is deleted
 *
 * @param int $activity_id activity id.
 * @param int $user_id user id.
 */
function ac_notifier_remove_notification_on_activity_delete( $activity_id, $user_id ) {
	ac_notifier_delete_notification( $activity_id );
}

add_action( 'bp_activity_action_delete_activity', 'ac_notifier_remove_notification_on_activity_delete', 10, 2 );

/**
 * Delete activity comment notification when the comment is deleted
 *
 * @param int $activity_id activity id.
 * @param int $comment_id activity comment id.
 */
function ac_notifier_remove_notification_on_activity_comment_delete( $activity_id, $comment_id ) {

	ac_notifier_delete_notification( $activity_id );
}

add_action( 'bp_activity_delete_comment', 'ac_notifier_remove_notification_on_activity_comment_delete', 10, 2 );


/**
 * For single activity view, Remove the notification(s) associated with current user and current activity item.
 *
 * @param BP_Activity_Activity $activity activity object.
 * @param bool                 $has_access current user has access.
 */
function ac_notifier_remove_notification( $activity, $has_access ) {

	$bp = buddypress();

	$user_id = get_current_user_id();

	if ( $has_access ) {
		// if user can view this activity, remove notification(just a safeguard for hidden activity).
		bp_notifications_mark_notifications_by_item_id( $user_id, $activity->id, $bp->ac_notifier->id, 'new_activity_comment_' . $activity->id );
		bp_notifications_mark_notifications_by_item_id( $user_id, $activity->id, $bp->ac_notifier->id, 'new_activity_favorite_' . $activity->id );
	}
}

add_action( 'bp_activity_screen_single_activity_permalink', 'ac_notifier_remove_notification', 10, 2 );


/**
 * Delete notification when an activity is deleted, thanks to @kat_uk for pointing the issue
 *
 * @param array $ac_ids An array of activity ids.
 */
function bp_ac_clear_notification_on_activity_delete( $ac_ids ) {

	$bp = buddypress();

	foreach ( (array) $ac_ids as $activity_id ) {
		bp_notifications_delete_all_notifications_by_type( $activity_id, $bp->ac_notifier->id, 'new_activity_comment_' . $activity_id );
		bp_notifications_delete_all_notifications_by_type( $activity_id, $bp->ac_notifier->id, 'new_activity_favorite_' . $activity_id );
	}
}
add_action( 'bp_activity_deleted_activities', 'bp_ac_clear_notification_on_activity_delete' );

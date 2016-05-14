<?php


/**
 * Notify users ( original poster+all the users who commented on the activity )
 * 
 * @param type $comment_id
 * @param type $params
 */
function ac_notifier_notify_on_new_comment ( $comment_id, $params ) {

	$bp = buddypress();
	
	extract( $params );

	$users = ac_notifier_find_involved_persons( $activity_id );
	$activity = new BP_Activity_Activity( $activity_id );
	
	//Let us add a filter to allow skipping some of the activity types if required
	if ( apply_filters( 'ac_notifier_skip_notification', false, $activity ) ) {
		return ;
	}
	//delete any existing notification for the current user
	//ac_notifier_remove_notification( $activity, true );
	//since there is a bug in bp 1.2.9 and causes trouble with private notificatin, so let us  not notify for any of the private activities
	//if ( $activity->hide_sitewide )
	//	return;
	
	
	if ( ! in_array( $activity->user_id, $users ) && ( get_current_user_id() != $activity->user_id ) ) {
		
		//push the original poster user_id, if the original poster has not commented on his/her status yet
		array_push( $users, $activity->user_id );
		
	}
	
	foreach ( (array) $users as $user_id ) {
		
		//create a notification for each of the user
		bp_notifications_add_notification( array(
			'item_id'			=> $activity_id,
			'user_id'			=> $user_id,
			'component_name'	=> $bp->ac_notifier->id,
			'component_action'	=> 'new_activity_comment_' . $activity_id,
			'secondary_item_id' => $comment_id,
		) ); 
		// We are creating the dynamic component_action to bypass the buddyprsess grouping of notifications
	}
}

add_action( 'bp_activity_comment_posted', 'ac_notifier_notify_on_new_comment', 10, 2 ); ///hook to comment_posted for adding notification



//notify on favorite
function ac_notify_on_favorite( $activity_id, $user_id ) {

	$bp = buddypress();
	$activity = new BP_Activity_Activity( $activity_id );
	
	if ( empty( $activity->id ) || $activity->user_id == $user_id )
		return ;
	
	if ( apply_filters( 'ac_notifier_skip_favorite_notification', false, $activity ) ) {
		return ;
	}

	bp_notifications_add_notification( array(
			'item_id'			=> $activity_id,
			'user_id'			=> $activity->user_id,
			'component_name'	=> $bp->ac_notifier->id,
			'component_action'	=> 'new_activity_favorite_' . $activity_id,
			'secondary_item_id' => $user_id
		) );
	
	
}
add_action( 'bp_activity_add_user_favorite', 'ac_notify_on_favorite', 10, 2 );


function ac_notifier_delete_notification_on_unfavorite( $activity_id, $user_id ) {
	$bp = buddypress();
	$activity = new BP_Activity_Activity( $activity_id );
	
	bp_notifications_delete_notifications_by_item_id( $activity->user_id,  $activity_id, $bp->ac_notifier->id, 'new_activity_favorite_' . $activity_id, $user_id ); 
	
}
add_action( 'bp_activity_remove_user_favorite', 'ac_notifier_delete_notification_on_unfavorite', 10, 2 );




//del favorite on unfav

/**
 * Delete comment notification when activity is deleted
 * 
 */
function ac_notifier_remove_notification_on_activity_delete ( $activity_id, $user_id ) {
	
	ac_notifier_delete_notification( $activity_id );
	
}

add_action( 'bp_activity_action_delete_activity', 'ac_notifier_remove_notification_on_activity_delete', 10, 2 );

/**
 * Delete activity comment notification when the comment is deleted
 */
function ac_notifier_remove_notification_on_activity_comment_delete ( $activity_id, $comment_id ) {

	ac_notifier_delete_notification( $activity_id );
}

add_action( 'bp_activity_delete_comment', 'ac_notifier_remove_notification_on_activity_comment_delete', 10, 2 );




/*
 * For single activity view, we will remove the notification associated with current user and current activity item
 */

function ac_notifier_remove_notification ( $activity, $has_access ) {
	
	$bp = buddypress();
	
	$user_id = get_current_user_id();
	
	if ( $has_access ) {//if user can view this activity, remove notification(just a safeguard for hidden activity)
		
		bp_notifications_mark_notifications_by_item_id( $user_id, $activity->id, $bp->ac_notifier->id, 'new_activity_comment_' . $activity->id );
		bp_notifications_mark_notifications_by_item_id( $user_id,  $activity->id, $bp->ac_notifier->id, 'new_activity_favorite_' . $activity->id ); 
	}	
}

add_action( 'bp_activity_screen_single_activity_permalink', 'ac_notifier_remove_notification', 10, 2 );

/**
 * @since v 1.0.2
 * @desc delete notification when an activity is deleted, thanks to @kat_uk for pointing the issue
 * @param ac_ids:we get an arry of activity ids
 */
function bp_ac_clear_notification_on_activity_delete ( $ac_ids ) {
	
	$bp = buddypress();
	
	//bp_core_delete_notifications_by_item_id(  $bp->loggedin_user->id, $activity->id, $bp->activity->id,  'new_activity_comment_'.$activity->id);
	foreach ( (array) $ac_ids as $activity_id ) {
		
		bp_notifications_delete_all_notifications_by_type( $activity_id, $bp->ac_notifier->id, 'new_activity_comment_' . $activity_id );
		bp_notifications_delete_all_notifications_by_type( $activity_id, $bp->ac_notifier->id, 'new_activity_favorite_' . $activity_id );
		
		
	}	
}

add_action( 'bp_activity_deleted_activities', 'bp_ac_clear_notification_on_activity_delete' );

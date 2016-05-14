<?php

/**
 * Get teh permalink for activity
 * 
 * @global $bp $bp
 * @param type $activity_id
 * @param BP_Activity_Activity $activity_obj
 * @return string url
 */
function ac_notifier_activity_get_permalink ( $activity_id, $activity_obj = false ) {

	if ( ! $activity_obj ) {
		$activity_obj = new BP_Activity_Activity( $activity_id );
	}

	if ( 'activity_comment' == $activity_obj->type ) {
		$link = bp_get_activity_directory_permalink() . 'p/' . $activity_obj->item_id . '/';
	} else {
		$link = bp_get_activity_directory_permalink() . 'p/' . $activity_obj->id . '/';
	}


	return apply_filters( 'ac_notifier_activity_get_permalink', $link, $activity_obj );
}


/**
 * @desc: find all the unique user_ids who have commented on this activity
 */
function ac_notifier_find_involved_persons ( $activity_id ) {

	global $bp, $wpdb;

	return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT(user_id) FROM {$bp->activity->table_name} WHERE item_id = %d and user_id != %d ", $activity_id, get_current_user_id() ) ); //it finds all uses who commted on the activity
}

/**
 * Delete the notifications for an activity
 * 
 * @global $bp $bp
 * @global type $wpdb
 * @param type $activity_id
 * @param type $action_name
 * @return type
 */
function ac_notifier_delete_notification ( $activity_id, $action_name = false ) {
	global $bp, $wpdb;
	$component_name = $bp->ac_notifier->id;


	$and_condition = '';

	if ( ! empty( $action_name ) ) {
		$and_condition = $wpdb->prepare( ' AND component_action = %s', $component_action );
	}

	return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE item_id = %d AND component_name = %s {$and_condition}", $activity_id, $component_name ) );
}

/** 
 * our notification format function which shows notification to user
 *
 * @global  $bp
 * @param <type> $action
 * @param <type> $activity_id
 * @param <type> $secondary_item_id
 * @param <type> $total_items
 * @return <type>
 * @since 1.0.2
 * @desc format and show the notification to the user
 */
function ac_notifier_format_notifications ( $action, $activity_id, $secondary_item_id, $total_items, $format = 'string' ) {

	$glue = '';
	$user_names = array();

	$activity = new BP_Activity_Activity( $activity_id );

	$link = ac_notifier_activity_get_permalink( $activity_id );

	//if it is the original poster, say your, else say %s's post
	if ( get_current_user_id() == $activity->user_id ) {
		$self_post = true;
	} else {
		$self_post = false;
	}

	$ac_action = 'new_activity_comment_' . $activity_id;
	$ac_action_favorite = 'new_activity_favorite_' . $activity_id;
	
	if ( $action == $ac_action ) {
		$link = $link . '#acomment-'. $secondary_item_id;
		//if ( (int)$total_items > 1 ) {
		$users = ac_notifier_find_involved_persons( $activity_id );

		$total_user = $count = count( $users ); //how many unique users have commented

		if ( $count > 2 ) {
			$users = array_slice( $users, $count - 2 ); //just show name of two poster, rest should be as and 'n' other also commeted
			$count = $count - 2;
			$glue = ", ";
		} elseif ( $total_user == 2 ) {
			$glue = __(' and ', 'bp-activity-comment-notifier'); //if there are 2 unique users , say x and y commented
		}

		foreach ( (array) $users as $user_id ) {
			$user_names[] = bp_core_get_user_displayname( $user_id );
		}

		$commenting_users = '';

		if ( ! empty( $user_names ) ) {
			$commenting_users = join( $glue, $user_names );
		}

		if ( $self_post ) {
			
			if ( $total_user <= 2) {
				$text = sprintf( __('%s commented on your post', 'bp-activity-comment-notifier' ), $commenting_users );
			} else {
				$text = sprintf( __('%s and %s others commented on your post', 'bp-activity-comment-notifier'), $commenting_users, $count );
			}

		} else {

			if ( $total_user == 1) {
				if ( $activity->user_id == reset($users) ) {
					$text = sprintf( __('%s also commented on his/her own post', 'bp-activity-comment-notifier'), $commenting_users, bp_core_get_user_displayname( $activity->user_id ) );
				} else {
					$text = sprintf( __('%s also commented on %s\'s post', 'bp-activity-comment-notifier'), $commenting_users, bp_core_get_user_displayname( $activity->user_id ) );
				}
			}
			if ( $total_user == 2) {
				$text = sprintf( __('%s also commented on %s\'s post', 'bp-activity-comment-notifier'), $commenting_users, bp_core_get_user_displayname( $activity->user_id ) );
			} else {
				$text = sprintf( __('%s and %s others also commented on %s\'s post', 'bp-activity-comment-notifier'), $commenting_users, $count, bp_core_get_user_displayname( $activity->user_id ) );
			}

		}

	} elseif (  $action == $ac_action_favorite ) {
	
		$label = __( 'post', 'bp-activity-comment-notifier' );
		
		if ( $activity->type == 'activity-comment' ) {
			$label = __('comment', 'bp-activity-comment-notifier' );
		}
		
		$name = bp_core_get_user_displayname( $secondary_item_id );
		$text = sprintf( __('%s favorited your %s', 'bp-activity-comment-notifier'), $name, $label ); 
		
	}

	if ( empty( $text ) ) {
		return false;
	}

	if ( $format == 'string' ) {
		return apply_filters( 'bp_activity_multiple_new_comment_notification', '<a href="' . $link . '">' . $text . '</a>' );
	} else {
		return array(
			'link' => $link,
			'text' => $text
		);
	}

	return false;
}



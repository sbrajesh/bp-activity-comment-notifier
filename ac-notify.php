<?php
/**
 * Plugin Name:BuddyPress Activity Comment Notifier
 * Plugin URI: http://buddydev.com/plugins/buddypress-activity-comment-notifier/
 * Author: Brajesh Singh
 * Author URI:http://buddydev.com/members/sbrajesh
 * Description: Show facebook like notification in the notification drop down when some user comment on your update or on other users update where you have commented
 * Version: 1.0.4
 * License: GPL
 * Network: true
 * Date Updated: September 09, 2011
 * 
 */
/**
 * Special desclaimer: BuddyPress does not allows multiple items from same component with similar action(It is grouped by buddypress before delivering to the notfication formatting function), so I have hacked the component_action to be unique per item, because we want update for individual action items
 * Most important: It is just for fun :) hope you would like it
 */

// we are not much concerned with the slug, it is not visible
define("BP_ACTIVITY_NOTIFIER_SLUG","ac_notification");

//register a dummy notifier component, I don't want to do it, but bp has no other mechanism for passing the notification data to function, so we need the format_notification_function
function ac_notifier_setup_globals() {
    global $bp, $current_blog;

    $bp->ac_notifier->id = 'ac_notifier';//I asume others are not going to use this is
    $bp->ac_notifier->slug = BP_ACTIVITY_NOTIFIER_SLUG;
    $bp->ac_notifier->format_notification_function = 'ac_notifier_format_notifications';//show the notification
    /* Register this in the active components array */
    $bp->active_components[$bp->ac_notifier->slug] = $bp->ac_notifier->id;

    do_action( 'ac_notifier_setup_globals' );
}
add_action( 'bp_setup_globals', 'ac_notifier_setup_globals' );


/**
 * storing notification for users
 * notify all the users who have commented, or who was the original poster of the update, when someone comments
 * hook to activity_comment_posted action
 */


function ac_notifier_notify($comment_id, $params){
   global $bp;
   extract( $params );

   $users=ac_notifier_find_involved_persons($activity_id);
   $activity=new BP_Activity_Activity($activity_id);
   //since there is a bug in bp 1.2.9 and causes trouble with private notificatin, so let us  not notify for any of the private activities
   if($activity->hide_sitewide)
           return;
   //push the original poster user_id, if the original poster has not commented on his/her status yet
   if(!in_array($activity->user_id, $users)&&($bp->loggedin_user->id!=$activity->user_id))//if someone else is commenting
       array_push ($users, $activity->user_id);
   
    foreach((array)$users as $user_id){
               //create a notification
               bp_core_add_notification( $activity_id, $user_id, $bp->ac_notifier->id, 'new_activity_comment_'.$activity_id );//a hack to not allow grouping by component,action, rather group by component and individual action item
           }
  }
add_action("bp_activity_comment_posted","ac_notifier_notify",10,2);///hook to comment_posted for adding notification

 
/** our notification format function which shows notification to user
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
function ac_notifier_format_notifications( $action, $activity_id, $secondary_item_id, $total_items ) {
   
    global $bp;
    $glue='';
    $user_names=array();
      $activity = new BP_Activity_Activity( $activity_id );
    $link=ac_notifier_activity_get_permalink( $activity_id );
  
       //if it is the original poster, say your, else say %s's post
    if($activity->user_id==$bp->loggedin_user->id){
                $text=__("your");
                $also="";
        }
    else{
         $text=sprintf(__("%s's"),  bp_core_get_user_displayname ($activity->user_id));//somone's
         $also=" also";
          
        }
    $ac_action='new_activity_comment_'.$activity_id;

    if($action==$ac_action){
	//if ( (int)$total_items > 1 ) {
        $users=ac_notifier_find_involved_persons($activity_id);
        $total_user= $count=count($users);//how many unique users have commented
        if($count>2){
              $users=array_slice($users, $count-2);//just show name of two poster, rest should be as and 'n' other also commeted
              $count=$count-2;
              $glue=", ";
             }
        else if($total_user==2)
             $glue=" and ";//if there are 2 unique users , say x and y commented

       foreach((array)$users as $user_id)
             $user_names[]=bp_core_get_user_displayname ($user_id);
                
      if(!empty($user_names))
            $commenting_users=join ($glue, $user_names);
                   
                
     if($total_user>2)
        $text=$commenting_users." and ".$count." others".$also." commented on ".$text. " post";//can we change post to some meaningfull thing depending on the activity item ?
     else
        $text=$commenting_users.$also ." commented on ".$text. " post";

                //sprintf( __('%s commented on your update' ),$commenting_users)
      return apply_filters( 'bp_activity_multiple_new_comment_notification', '<a href="' . $link. '">' . $text . '</a>');
   }
return false;
}

/**
 *  This section delas with removing notification when a notified item is viewed
 */

/*
 * For single activity view, we will remove the notification associated with current user and current activity item
 */


function ac_notifier_remove_notification($activity,$has_access){
    global $bp;
    if($has_access)//if user can view this activity, remove notification(just a safeguard for hidden activity)
        bp_core_delete_notifications_by_item_id(  $bp->loggedin_user->id, $activity->id, $bp->ac_notifier->id,  'new_activity_comment_'.$activity->id);
    
}
add_action("bp_activity_screen_single_activity_permalink","ac_notifier_remove_notification",10,2);

/*
 * Remove activity for the comments on new_blog_post &new_blog_comment activity item.
 * Since these items do not have a single activity view and are linked to the single post screen, we will do the needed on single post view
 * not ued anymore because we are linking to threadv view and the ac_notifier_remove_notification will do it for us
 */

function ac_notifier_remove_notification_for_blog_posts(){
    if(!(is_user_logged_in()&&  is_singular()))
        return;

    global $bp,$wpdb;
    $blog_id = (int)$wpdb->blogid;
    $post=wp_get_single_post();
    
    $activity_id=  bp_activity_get_activity_id( array(
		'user_id' => $post->post_author,
		'component' => $bp->blogs->id,
		'type' => "new_blog_post",
                "item_id"=>$blog_id,
		'secondary_item_id' => $post->ID
		
	));
    //delete the notification for activity comment on new_blog_post
    if(!empty($activity_id))
        bp_core_delete_notifications_by_item_id(  $bp->loggedin_user->id, $activity_id, $bp->ac_notifier->id,  'new_activity_comment_'.$activity_id);

     //for replies on blog comments in activity stream
      $comments=ac_notifier_get_all_blog_post_comment_ids($post->ID);//get all the comment ids as array
      //added in v 1.0.3 for better database performance, no more looping to get individual activity ids
      $activities=ac_notifier_get_activity_ids(array("type"=>"new_blog_comment",
                                                     "component" => $bp->blogs->id,
                                                  "item_id"=>$blog_id,
                                                  "secondary_ids"=>$comments
                                                ));


    foreach((array)$activities as $ac_id)
         bp_core_delete_notifications_by_item_id(  $bp->loggedin_user->id, $ac_id, $bp->ac_notifier->id,  'new_activity_comment_'.$ac_id);

}
//add_action("wp_head","ac_notifier_remove_notification_for_blog_posts");
/**
 * @since 1.0.3
 * it is not used any more because qwe are linking to threaded view
 * Remove notification for group forum posts/new topic
 */

function ac_notifier_remove_notification_for_topic(){
    global $bp;
    //is group & group forum active ?
    if(!(bp_is_active("groups")&&bp_is_active("forums")))
        return;//if either group or forum is not actuive do not do anything
  
    //check if we are on single topic screen, right?
    if(bp_is_group_forum_topic()&&  is_user_logged_in()){
        $topic_id = bp_forums_get_topic_id_from_slug( $bp->action_variables[1] );//get id from slug
        $topic=get_topic($topic_id);
        $group_id= $bp->groups->current_group->id;

        //find activity id for this topic
         $activity_id=  bp_activity_get_activity_id( array(
                        'user_id' => $topic->poster_id,
                        'component' => $bp->groups->id,
                        'type' => "new_forum_topic",
                        "item_id"=>$group_id,
                        'secondary_item_id' => $topic_id

                ));

         //remove notification for new topic comments: easy
        if(!empty ($activity_id))
               bp_core_delete_notifications_by_item_id(  $bp->loggedin_user->id, $activity_id, $bp->ac_notifier->id,  'new_activity_comment_'.$activity_id);


        $posts=ac_notifier_get_forum_post_ids($topic_id);
        if(!empty($posts)){
             //find all activities for the post
            $activities=ac_notifier_get_activity_ids(array( "item_id"=>$group_id,
                                                            "secondary_ids"=>$posts,
                                                            "component"=>$bp->groups->id,
                                                            "type"=>"new_forum_post"

                                                            ));//pass the array
            foreach((array)$activities as $ac_id)
                bp_core_delete_notifications_by_item_id(  $bp->loggedin_user->id, $ac_id, $bp->ac_notifier->id,  'new_activity_comment_'.$ac_id);

             }
    }
   
}
//add_action("bp_before_group_forum_topic","ac_notifier_delete_notification_for_topic");
//add_action("wp_head","ac_notifier_remove_notification_for_topic");//removed in v 1.0.3 as we are now looking at the thread view

/**
 * @since v 1.0.2
 * @desc delete notification when an activity is deleted, thanks to @kat_uk for pointing the issue
 * @param ac_ids:we get an arry of activity ids
 */

function bp_ac_clear_notification_on_activity_delete($ac_ids){
    global $bp;

    //bp_core_delete_notifications_by_item_id(  $bp->loggedin_user->id, $activity->id, $bp->activity->id,  'new_activity_comment_'.$activity->id);
    foreach((array)$ac_ids as $activity_id)
        bp_core_delete_all_notifications_by_type( $activity_id, $bp->ac_notifier->id, 'new_activity_comment_'.$activity_id, $secondary_item_id = false );
}
add_action("bp_activity_deleted_activities","bp_ac_clear_notification_on_activity_delete");

/************************************ HELPER FUNCTIONS ********************************************************/
/**
  * @desc: find all the unique user_ids who have commented on this activity
 */

function ac_notifier_find_involved_persons($activity_id){
    global $bp,$wpdb;
   return $wpdb->get_col($wpdb->prepare("select DISTINCT(user_id) from {$bp->activity->table_name} where item_id=%d and user_id!=%d ",$activity_id,$bp->loggedin_user->id));//it finds all uses who commted on the activity
 }

/**
 * @desc : return an arry of comment ids for the post
 */
function ac_notifier_get_all_blog_post_comment_ids($post_id) {
    global $wpdb;
    return $wpdb->get_col($wpdb->prepare("SELECT comment_ID as id FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1' ORDER BY comment_date", $post_id));
}

/*
 * @desc find all the posts ids in the current topic
 * returns array of ids
 */
function ac_notifier_get_forum_post_ids($topic_id){
    global $wpdb,$bbdb;
    return $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$bbdb->posts} where topic_id=%d",$topic_id));
}

/**
 * @desc get activity ids when typ, component, secondary_ids,item_id is specified
 */

function ac_notifier_get_activity_ids($params){
    global $bp,$wpdb;
    extract($params);
    $list="(".join(",", $secondary_ids).")";//create a set to use in the query;

    return $wpdb->get_col($wpdb->prepare("SELECT id from {$bp->activity->table_name} where type=%s and component=%s and item_id=%d and secondary_item_id in {$list}",$type,$component,$item_id));
   
}
//get the thread permalink for activity
function ac_notifier_activity_get_permalink( $activity_id, $activity_obj = false ) {
	global $bp;

	if ( !$activity_obj )
		$activity_obj = new BP_Activity_Activity( $activity_id );

	
		if ( 'activity_comment' == $activity_obj->type )
			$link = $bp->root_domain . '/' . BP_ACTIVITY_SLUG . '/p/' . $activity_obj->item_id . '/';
		else
			$link = $bp->root_domain . '/' . BP_ACTIVITY_SLUG . '/p/' . $activity_obj->id . '/';
	

	return apply_filters( 'ac_notifier_activity_get_permalink', $link );
}
?>
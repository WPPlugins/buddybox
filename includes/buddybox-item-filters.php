<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * filters wp_upload_dir to replace its datas by buddybox ones
 * 
 * @param  array $upload_data  wp_upload dir datas
 * @uses   wp_parse_args() to merge datas
 * @return array  $r the filtered array
 */
function buddybox_temporarly_filters_wp_upload_dir( $upload_data ) {
	$path = buddybox()->upload_dir;
	$url = buddybox()->upload_url;
	
	$buddybox_args = apply_filters( 'buddybox_upload_datas', 
		array( 
			'path'    => $path,
			'url'     => $url,
			'subdir'  => false,
			'basedir' => $path,
			'baseurl' => $url,
		) );
	
	$r = wp_parse_args( $buddybox_args, $upload_data );
	
	return $r;
}


/**
 * filters WordPress mime types
 * 
 * @param  array $allowed_file_types the WordPress mime types
 * @uses   buddybox_allowed_file_types() to get the option defined by admin 
 * @return array mime types allowed by BuddyBox
 */
function buddybox_allowed_upload_mimes( $allowed_file_types ) {

	return buddybox_allowed_file_types( $allowed_file_types );
}


/**
 * Checks file uploaded size upon user's space left and max upload size
 * 
 * @param  array $file $_FILE array
 * @uses   buddybox_get_user_space_left() to get user's space left
 * @uses   buddybox_max_upload_size() to get max upload size
 * @return array $file the $_FILE array with an eventual error
 */
function buddybox_check_upload_size( $file ) {

	if ( $file['error'] != '0' ) // there's already an error
		return $file;

	// what's left in user's quota ?
	$space_left = buddybox_get_user_space_left( 'diff' );

	$file_size = filesize( $file['tmp_name'] );

	if ( $space_left < $file_size )
		$file['error'] = sprintf( __( 'Not enough space to upload. %1$s KB needed.', 'buddybox' ), number_format( ($file_size - $space_left) /1024 ) );
	if ( $file_size > buddybox_max_upload_size( true ) )
		$file['error'] = sprintf( __('This file is too big. Files must be less than %1$s MB in size.', 'buddybox' ), buddybox_max_upload_size() );
	if ( $space_left <= 0 ) {
		$file['error'] = __( 'You have used your space quota. Please delete files before uploading.', 'buddybox' );
	}

	return $file;
}


/**
 * temporarly filters buddybox_get_user_space_left to only output the quota with no html tags
 * 
 * @param  string $output html
 * @param  string $quota  the space left without html tags
 * @return string $quota
 */
function buddybox_filter_user_space_left( $output, $quota ) {
	return $quota;
}


/**
 * filters bp_get_message_get_recipient_usernames if needed
 * 
 * @param  string $recipients the message recipients
 * @uses   friends_get_friend_user_ids() to get the friends list
 * @uses   bp_loggedin_user_id() to get the current logged in user
 * @uses   bp_core_get_username() to get the usernames of the friends.
 * @return string list of the usernames of the friends of the loggedin users
 */
function buddybox_add_friend_to_recipients( $recipients ) {
	
	if( empty( $_REQUEST['buddyitem'] ) )
		return $recipients;
	
	$ids = friends_get_friend_user_ids( bp_loggedin_user_id() );
	
	$usernames = false;
	
	foreach( $ids as $id ) {
		$usernames[] = bp_core_get_username( $id );
	}
	
	if( is_array( $usernames ) )
		return implode( ' ', $usernames );
		
	else
		return $recipients;
	
}


/**
 * removes the BuddyBox directory page from wp header menu
 * 
 * @param  array $args the menu args
 * @uses   buddybox_get_slug() to get the slug of the BuddyBox directory page
 * @uses   bp_core_get_directory_page_ids() to get an array of BP Components page ids
 * @return args  $args with a new arg to exclude BuddyBox page.
 */
function buddybox_hide_item( $args ) {

	$buddybox_slug = buddybox_get_slug();
	
	$directory_pages = bp_core_get_directory_page_ids();
	
	$args['exclude'] = $directory_pages[$buddybox_slug];
	return $args;
}

add_filter( 'wp_page_menu_args', 'buddybox_hide_item', 9, 1 );
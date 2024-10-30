<?php
/*
Plugin Name: Brag Box
Plugin URI:  http://example.com/wordpress-plugins/my-plugin 
Description: Creates a module that allows logged-in users to post something nice about a fellow user, friend, or co-worker. To get started: 1 - Activate the plugin, 2 - Copy the shortcode: [brag-box], 3 - Paste the shortcode into a content editor or widget area within your theme.
Version:     0.1
Author:      Sparxoo
Author URI:  http://www.sparxoo.com/
License:     GPLv2
*/

register_activation_hook( __FILE__, 'spxo_bb_install'); 
function spxo_bb_install() {
	// Insert DB table and columns here
	global $wpdb;

	$table_name = "spxo_brag_box";

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
		user_id int(11) NOT NULL,
		testimonial text NOT NULL,
		person_recognized text NOT NULL,
		created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		is_trashed tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

register_deactivation_hook(__FILE__, 'spxo_bb_uninstall');
function spxo_bb_uninstall() {

}

/**
 * Adds a posting user and pretty date property to an object passed through.
 *
 * @param  Object $brag A singular object containing brag information. 
 * @return Void 
*/

function spxo_bb_add_meta_properties_to_object($spxo_bb_brag) {
	// Get users nicenames and add them to pulled JS object
	$spxo_bb_posting_user = get_user_by("ID", $spxo_bb_brag->user_id);
	$spxo_bb_brag->{"postingUser"} = $spxo_bb_posting_user->display_name;

	// Format created post date into pretty format and add to JS object
	$spxo_bb_brag_creation_date = date_create($spxo_bb_brag->created);
	$spxo_bb_pretty_date = date_format($spxo_bb_brag_creation_date, "Y/m/d"); 
	$spxo_bb_brag->{"prettyDate"} = $spxo_bb_pretty_date;

	$spxo_bb_current_user_first_name = get_user_meta($spxo_bb_brag->user_id, 'first_name', true);
	$spxo_bb_current_user_last_name = get_user_meta($spxo_bb_brag->user_id, 'last_name', true); 
	$spxo_bb_brag->{"currentUsersName"} = $spxo_bb_current_user_first_name . " " . $spxo_bb_current_user_last_name;
}


add_action( 'wp_ajax_spxo_bb_move_brag_to_trash', 'spxo_bb_move_brag_to_trash' );
add_action( 'wp_ajax_nopriv_spxo_bb_move_brag_to_trash', 'spxo_bb_move_brag_to_trash');

function spxo_bb_move_brag_to_trash() { 
	global $wpdb;
	$spxo_bb_brag_id = absint($_POST["bragID"]);
	$spxo_bb_user_id = absint($_POST["userID"]);

	$current_user_id = absint(wp_get_current_user()->data->ID);

	// Ensure user can only update backend if they are the current user
	if (empty($current_user_id) || $current_user_id != $spxo_bb_user_id) {
		throw new Exception("You don't have permission to move this post to trash.");
	}

	// Update brag's trash value to true
	$wpdb->update('spxo_brag_box', array('is_trashed' => '1'), array('id' => $spxo_bb_brag_id));

	wp_die();
}

add_action( 'wp_ajax_spxo_bb_restore_brag', 'spxo_bb_restore_brag' );
add_action( 'wp_ajax_nopriv_spxo_bb_restore_brag', 'spxo_bb_restore_brag');

function spxo_bb_restore_brag() { 
	global $wpdb;
	$spxo_bb_brag_id = absint($_POST["bragID"]);

	// Update brag's trash value to true
	$wpdb->update('spxo_brag_box', array('is_trashed' => '0'), array('id' => $spxo_bb_brag_id));

	wp_die();
}

add_action( 'wp_ajax_spxo_bb_delete_brag', 'spxo_bb_delete_brag' );
add_action( 'wp_ajax_nopriv_spxo_bb_delete_brag', 'spxo_bb_delete_brag');

function spxo_bb_delete_brag() { 
	global $wpdb;
	$spxo_bb_brag_id = absint($_POST["bragID"]);

	// Update brag's trash value to true
	$wpdb->delete('spxo_brag_box', array('id' => $spxo_bb_brag_id));

	wp_die();
}

add_action( 'wp_ajax_spxo_bb_add_brag', 'spxo_bb_add_brag' );
add_action( 'wp_ajax_nopriv_spxo_bb_add_brag', 'spxo_bb_add_brag');

function spxo_bb_add_brag() { 
	global $wpdb;

	$spxo_bb_brag = sanitize_text_field( $_POST["brag"] );
	$spxo_bb_brag = stripslashes($spxo_bb_brag);

	if (strlen($spxo_bb_brag) > 300) {
		throw new Exception("Brag cannot be greater than 300 characters.");
	}

	$spxo_bb_person_recognized = sanitize_text_field( $_POST["person_recognized"] );
	$spxo_bb_person_recognized = stripslashes($spxo_bb_person_recognized);

	if (strlen($spxo_bb_person_recognized) > 30) {
		throw new Exception("Person recognized cannot be greater than 30 characters.");
	}

	$spxo_bb_user_id = wp_get_current_user()->data->ID; 

	$wpdb->insert('spxo_brag_box', 
		array(
			'user_id' => $spxo_bb_user_id,
			'testimonial' => $spxo_bb_brag,
			'person_recognized' => $spxo_bb_person_recognized,
			'created' => current_time('mysql', 1)
		), 
		array(
			'%d',
			'%s',
			'%s',
			'%s'
		)
	);

	$spxo_bb_inserted_brag_id = $wpdb->insert_id;
	$spxo_bb_newly_created_brag = $wpdb->get_results("
			SELECT * FROM spxo_brag_box WHERE id = {$spxo_bb_inserted_brag_id} 
		", OBJECT);

	spxo_bb_add_meta_properties_to_object($spxo_bb_newly_created_brag[0]);
	$spxo_bb_newly_created_brag = json_encode($spxo_bb_newly_created_brag);
	

	echo $spxo_bb_newly_created_brag;

	wp_die();
}

require 'inc/admin.php';
require 'inc/frontend.php';

?>
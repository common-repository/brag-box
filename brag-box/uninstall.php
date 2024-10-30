<?php
// Remove DB table and columns here

// If uninstall not called from WordPress exit 
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
exit ();

//remove any additional options and custom tables 
global $wpdb;
$table = "spxo_brag_box";

//Delete any options thats stored also?
//delete_option('wp_yourplugin_version');
$wpdb->query("DROP TABLE IF EXISTS $table");

?>
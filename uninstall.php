<?php
/**
 * @package AutomaticSubmenu
 * Uninstall Script
 */
 
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ){
	die;
}

global $wpdb;
$wpdb->query( "DELETE FROM wp_postmeta WHERE meta_key LIKE '_menu_item_automati%'" );
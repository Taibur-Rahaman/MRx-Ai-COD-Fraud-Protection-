<?php
/**
 * Uninstall MRx AI COD Fraud Protection
 * 
 * This file is called when the plugin is uninstalled
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('mrx_ai_high_threshold');
delete_option('mrx_ai_medium_threshold');
delete_option('mrx_ai_high_risk_action');
delete_option('mrx_ai_fb_pixel_id');
delete_option('mrx_ai_fb_access_token');
delete_option('mrx_ai_send_verified_only');

// Delete database tables (optional - uncomment if you want to delete data on uninstall)
/*
global $wpdb;

$tables = array(
    $wpdb->prefix . 'mrx_ai_phones',
    $wpdb->prefix . 'mrx_ai_ips',
    $wpdb->prefix . 'mrx_ai_devices',
    $wpdb->prefix . 'mrx_ai_order_intelligence'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}
*/

// Clear scheduled events
wp_clear_scheduled_hook('mrx_ai_sync_courier_status');


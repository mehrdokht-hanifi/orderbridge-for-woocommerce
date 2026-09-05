<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Preserve logs and settings by default. Set this constant in wp-config.php for a clean removal.
if ( ! defined( 'OBWC_REMOVE_DATA_ON_UNINSTALL' ) || ! OBWC_REMOVE_DATA_ON_UNINSTALL ) {
	return;
}

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'obwc_logs' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
delete_option( 'obwc_api_url' );
delete_option( 'obwc_api_key' );
delete_option( 'obwc_webhook_secret' );
delete_option( 'obwc_log_retention' );

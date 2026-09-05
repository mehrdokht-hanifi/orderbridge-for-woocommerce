<?php
defined( 'ABSPATH' ) || exit;

final class OBWC_Logger {
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'obwc_logs';
	}

	public static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE {$table} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint unsigned NOT NULL DEFAULT 0,
			direction varchar(12) NOT NULL,
			event varchar(80) NOT NULL,
			status varchar(20) NOT NULL,
			http_code smallint unsigned NOT NULL DEFAULT 0,
			attempt tinyint unsigned NOT NULL DEFAULT 1,
			message text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id), KEY order_id (order_id), KEY created_at (created_at)
		) {$charset};" );
	}

	public function add( $order_id, $direction, $event, $status, $message = '', $http_code = 0, $attempt = 1 ) {
		global $wpdb;
		$wpdb->insert( self::table(), array(
			'order_id' => absint( $order_id ), 'direction' => sanitize_key( $direction ),
			'event' => sanitize_key( $event ), 'status' => sanitize_key( $status ),
			'http_code' => absint( $http_code ), 'attempt' => absint( $attempt ),
			'message' => sanitize_textarea_field( $message ), 'created_at' => current_time( 'mysql', true ),
		), array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ) );
	}

	public function recent( $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT %d', min( 200, absint( $limit ) ) ) );
	}

	public function cleanup() {
		global $wpdb;
		$days = max( 7, absint( get_option( 'obwc_log_retention', 30 ) ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days ) ) );
	}
}

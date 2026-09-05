<?php
defined( 'ABSPATH' ) || exit;

final class OBWC_Plugin {
	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$logger = new OBWC_Logger();
		$client = new OBWC_Client( $logger );
		$sync   = new OBWC_Sync( $client, $logger );

		new OBWC_REST( $sync, $logger );
		new OBWC_Admin( $client, $sync, $logger );

		add_action( 'woocommerce_order_status_changed', array( $sync, 'queue_order' ), 10, 1 );
		add_action( 'obwc_process_order', array( $sync, 'process_order' ), 10, 2 );
		add_action( 'obwc_cleanup_logs', array( $logger, 'cleanup' ) );
	}

	public static function activate() {
		OBWC_Logger::create_table();
		if ( ! wp_next_scheduled( 'obwc_cleanup_logs' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'obwc_cleanup_logs' );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'obwc_cleanup_logs' );
	}
}

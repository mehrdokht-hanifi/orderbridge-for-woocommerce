<?php
defined( 'ABSPATH' ) || exit;

final class OBWC_Sync {
	private $client;
	private $logger;
	private $applying_remote_update = false;
	public function __construct( OBWC_Client $client, OBWC_Logger $logger ) { $this->client = $client; $this->logger = $logger; }

	public function queue_order( $order_id ) {
		if ( $this->applying_remote_update || ! $this->client->configured() ) { return; }
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'obwc_process_order', array( $order_id, 1 ), 'orderbridge' );
		} else {
			wp_schedule_single_event( time() + 1, 'obwc_process_order', array( $order_id, 1 ) );
		}
		$this->logger->add( $order_id, 'outbound', 'order_upserted', 'queued' );
	}

	public function process_order( $order_id, $attempt = 1 ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) { return; }
		$payload = OBWC_Payload::from_order( $order );
		$response = $this->client->send( 'orders', $payload, $payload['idempotency_key'] );
		if ( is_wp_error( $response ) ) {
			$this->failed( $order_id, $attempt, $response->get_error_message() ); return;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$this->failed( $order_id, $attempt, 'Remote API returned HTTP ' . $code, $code ); return;
		}
		$order->update_meta_data( '_obwc_last_synced_at', gmdate( 'c' ) );
		$order->update_meta_data( '_obwc_sync_status', 'synced' );
		$order->save();
		$this->logger->add( $order_id, 'outbound', 'order_upserted', 'success', 'Order synchronized.', $code, $attempt );
	}

	private function failed( $order_id, $attempt, $message, $code = 0 ) {
		$this->logger->add( $order_id, 'outbound', 'order_upserted', 'failed', $message, $code, $attempt );
		$order = wc_get_order( $order_id );
		if ( $order ) { $order->update_meta_data( '_obwc_sync_status', $attempt < 6 ? 'retrying' : 'failed' ); $order->save(); }
		if ( $attempt < 6 ) {
			$args = array( $order_id, $attempt + 1 );
			$time = time() + OBWC_Crypto::retry_delay( $attempt );
			function_exists( 'as_schedule_single_action' ) ? as_schedule_single_action( $time, 'obwc_process_order', $args, 'orderbridge' ) : wp_schedule_single_event( $time, 'obwc_process_order', $args );
		}
	}

	public function apply_remote_status( $order_id, $status, $remote_id = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) { return new WP_Error( 'not_found', 'Order not found.', array( 'status' => 404 ) ); }
		$map = array( 'accepted' => 'processing', 'fulfilled' => 'completed', 'cancelled' => 'cancelled', 'on_hold' => 'on-hold' );
		if ( ! isset( $map[ $status ] ) ) { return new WP_Error( 'invalid_status', 'Unsupported ERP status.', array( 'status' => 422 ) ); }
		$this->applying_remote_update = true;
		try {
			$order->update_status( $map[ $status ], 'OrderBridge ERP update:', true );
		} finally {
			$this->applying_remote_update = false;
		}
		if ( $remote_id ) { $order->update_meta_data( '_obwc_remote_id', sanitize_text_field( $remote_id ) ); $order->save(); }
		return $order;
	}
}

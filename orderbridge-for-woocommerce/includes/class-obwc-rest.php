<?php
defined( 'ABSPATH' ) || exit;

final class OBWC_REST {
	private $sync; private $logger;
	public function __construct( OBWC_Sync $sync, OBWC_Logger $logger ) { $this->sync = $sync; $this->logger = $logger; add_action( 'rest_api_init', array( $this, 'routes' ) ); }
	public function routes() {
		register_rest_route( 'orderbridge/v1', '/webhook', array( 'methods' => 'POST', 'callback' => array( $this, 'webhook' ), 'permission_callback' => '__return_true' ) );
	}
	public function webhook( WP_REST_Request $request ) {
		$body = $request->get_body();
		$timestamp = $request->get_header( 'x-orderbridge-timestamp' );
		$signature = $request->get_header( 'x-orderbridge-signature' );
		$secret = (string) get_option( 'obwc_webhook_secret', '' );
		if ( strlen( $secret ) < 16 ) {
			return new WP_Error( 'not_configured', 'Webhook authentication is not configured.', array( 'status' => 503 ) );
		}
		if ( ! OBWC_Crypto::verify( $body, $timestamp, $signature, $secret ) ) {
			$this->logger->add( 0, 'inbound', 'status_updated', 'rejected', 'Invalid or expired signature.', 401 );
			return new WP_Error( 'invalid_signature', 'Invalid or expired signature.', array( 'status' => 401 ) );
		}
		$data = $request->get_json_params();
		$order_id = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : 0;
		$status = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : '';
		$result = $this->sync->apply_remote_status( $order_id, $status, $data['remote_id'] ?? '' );
		if ( is_wp_error( $result ) ) { return $result; }
		$this->logger->add( $order_id, 'inbound', 'status_updated', 'success', 'ERP status applied.', 200 );
		return new WP_REST_Response( array( 'ok' => true, 'order_id' => $order_id, 'status' => $result->get_status() ), 200 );
	}
}

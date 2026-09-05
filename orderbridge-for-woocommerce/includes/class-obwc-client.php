<?php
defined( 'ABSPATH' ) || exit;

final class OBWC_Client {
	private $logger;
	public function __construct( OBWC_Logger $logger ) { $this->logger = $logger; }

	public function configured() {
		return (bool) ( get_option( 'obwc_api_url' ) && get_option( 'obwc_api_key' ) );
	}

	public function send( $path, array $payload, $idempotency_key = '' ) {
		if ( ! $this->configured() ) {
			return new WP_Error( 'obwc_not_configured', 'OrderBridge API settings are incomplete.' );
		}
		$url = trailingslashit( esc_url_raw( get_option( 'obwc_api_url' ) ) ) . ltrim( $path, '/' );
		$body = wp_json_encode( $payload );
		$timestamp = (string) time();
		$headers = array(
			'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . get_option( 'obwc_api_key' ),
			'X-OrderBridge-Timestamp' => $timestamp,
			'X-OrderBridge-Signature' => OBWC_Crypto::signature( $body, $timestamp, get_option( 'obwc_webhook_secret', '' ) ),
		);
		if ( $idempotency_key ) { $headers['Idempotency-Key'] = sanitize_text_field( $idempotency_key ); }
		return wp_remote_post( $url, array( 'timeout' => 15, 'headers' => $headers, 'body' => $body, 'data_format' => 'body' ) );
	}

	public function test() {
		return $this->send( 'health', array( 'source' => 'woocommerce', 'version' => OBWC_VERSION ) );
	}
}

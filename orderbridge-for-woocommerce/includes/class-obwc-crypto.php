<?php
defined( 'ABSPATH' ) || exit;

final class OBWC_Crypto {
	public static function signature( $body, $timestamp, $secret ) {
		return hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );
	}

	public static function verify( $body, $timestamp, $signature, $secret, $tolerance = 300 ) {
		if ( ! ctype_digit( (string) $timestamp ) || abs( time() - (int) $timestamp ) > $tolerance ) {
			return false;
		}
		return hash_equals( self::signature( $body, $timestamp, $secret ), (string) $signature );
	}

	public static function retry_delay( $attempt ) {
		$attempt = max( 1, min( 6, (int) $attempt ) );
		return min( HOUR_IN_SECONDS, 30 * ( 2 ** ( $attempt - 1 ) ) );
	}
}

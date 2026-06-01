<?php
/**
 * Simple rate limiting for public quote submissions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Rate_Limiter {

	const TRANSIENT_PREFIX = 'pcd_submit_rl_';

	/**
	 * Max submissions per window per IP.
	 */
	const MAX_REQUESTS = 10;

	/**
	 * Window length in seconds.
	 */
	const WINDOW_SECONDS = 900;

	/**
	 * @return string
	 */
	public static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( ! $ip ) {
			return 'unknown';
		}
		return $ip;
	}

	/**
	 * @return true|WP_Error
	 */
	public static function check() {
		$key   = self::TRANSIENT_PREFIX . md5( self::client_ip() );
		$count = (int) get_transient( $key );

		if ( $count >= self::MAX_REQUESTS ) {
			return new WP_Error(
				'rate_limited',
				__( 'Too many submissions. Please wait a few minutes and try again.', 'pcd-pricing-calculator' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $key, $count + 1, self::WINDOW_SECONDS );
		return true;
	}
}

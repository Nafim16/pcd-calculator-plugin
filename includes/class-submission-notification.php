<?php
/**
 * Email admin when a new quote submission is saved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Submission_Notification {

	const OPTION_KEY = 'pcd_calculator_notifications';

	/**
	 * @return array{enabled: bool, email: string}
	 */
	public static function get_settings() {
		$defaults = array(
			'enabled' => true,
			'email'   => (string) get_option( 'admin_email' ),
		);

		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$enabled = array_key_exists( 'enabled', $saved )
			? (bool) $saved['enabled']
			: $defaults['enabled'];

		$email = isset( $saved['email'] ) ? sanitize_text_field( (string) $saved['email'] ) : $defaults['email'];
		if ( '' === $email ) {
			$email = $defaults['email'];
		}

		return array(
			'enabled' => $enabled,
			'email'   => $email,
		);
	}

	/**
	 * @param array $settings Settings from form.
	 * @return void
	 */
	public static function save_settings( array $settings ) {
		$email = isset( $settings['email'] ) ? sanitize_text_field( (string) $settings['email'] ) : '';
		if ( '' === $email ) {
			$email = (string) get_option( 'admin_email' );
		}

		update_option(
			self::OPTION_KEY,
			array(
				'enabled' => ! empty( $settings['enabled'] ),
				'email'   => $email,
			),
			false
		);
	}

	/**
	 * Send notification after a successful insert.
	 *
	 * @param int   $id      Submission ID.
	 * @param array $payload Original payload.
	 * @return bool Whether wp_mail reported success.
	 */
	public static function send_for_submission( $id, array $payload ) {
		$settings = self::get_settings();
		if ( ! $settings['enabled'] ) {
			return false;
		}

		$recipients = self::parse_recipients( $settings['email'] );
		if ( empty( $recipients ) ) {
			return false;
		}

		$row = PCD_Calculator_Submission_Repository::get_by_id( (int) $id );
		if ( ! $row ) {
			$indexed = PCD_Calculator_Submission_Repository::extract_indexed_columns( $payload );
			$row     = (object) array_merge(
				array( 'id' => (int) $id, 'submitted_at' => current_time( 'mysql' ) ),
				$indexed
			);
		}

		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject   = sprintf(
			/* translators: 1: site name, 2: submission ID */
			__( '[%1$s] New PCD quote request #%2$d', 'pcd-pricing-calculator' ),
			$site_name,
			(int) $id
		);

		$view_url = add_query_arg(
			array(
				'page' => 'pcd-quote-view',
				'id'   => (int) $id,
			),
			admin_url( 'admin.php' )
		);

		$mode_label = 'complex' === $row->calculator_mode
			? __( 'Complex', 'pcd-pricing-calculator' )
			: __( 'Simple', 'pcd-pricing-calculator' );

		$total_label = self::format_total_label( $row );

		$lines = array(
			__( 'A new pricing calculator quote was submitted on your site.', 'pcd-pricing-calculator' ),
			'',
			sprintf( __( 'Submission ID: #%d', 'pcd-pricing-calculator' ), (int) $id ),
			sprintf( __( 'Type: %s', 'pcd-pricing-calculator' ), $mode_label ),
			sprintf( __( 'Submitted: %s', 'pcd-pricing-calculator' ), mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row->submitted_at ) ),
			'',
			__( 'Contact', 'pcd-pricing-calculator' ),
			sprintf( __( 'Name: %s', 'pcd-pricing-calculator' ), $row->contact_name ),
			sprintf( __( 'Email: %s', 'pcd-pricing-calculator' ), $row->contact_email ),
		);

		if ( ! empty( $row->contact_phone ) ) {
			$lines[] = sprintf( __( 'Phone: %s', 'pcd-pricing-calculator' ), $row->contact_phone );
		}

		$lines[] = sprintf( __( 'Address: %s', 'pcd-pricing-calculator' ), $row->contact_address );

		if ( ! empty( $row->location_label ) ) {
			$lines[] = sprintf( __( 'Location: %s', 'pcd-pricing-calculator' ), $row->location_label );
		}

		if ( null !== $row->property_sqm ) {
			$lines[] = sprintf( __( 'Property size: %d m²', 'pcd-pricing-calculator' ), (int) $row->property_sqm );
		}

		$lines[] = sprintf( __( 'Total: %s', 'pcd-pricing-calculator' ), $total_label );
		$lines[] = '';
		$lines[] = __( 'View full details in WordPress:', 'pcd-pricing-calculator' );
		$lines[] = $view_url;

		$body = implode( "\n", $lines );

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = wp_mail( $recipients, $subject, $body, $headers );

		if ( ! $sent && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'PCD Calculator: failed to send new submission notification for #' . (int) $id );
		}

		return $sent;
	}

	/**
	 * @param string $raw Comma-separated addresses.
	 * @return string[]
	 */
	private static function parse_recipients( $raw ) {
		$parts = preg_split( '/\s*,\s*/', trim( (string) $raw ) );
		if ( ! is_array( $parts ) ) {
			return array();
		}

		$out = array();
		foreach ( $parts as $part ) {
			$part = sanitize_email( $part );
			if ( $part && is_email( $part ) ) {
				$out[] = $part;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @param object $row DB row or row-like object.
	 * @return string
	 */
	private static function format_total_label( $row ) {
		if ( ! empty( $row->quote_on_request ) ) {
			return __( 'Price on request', 'pcd-pricing-calculator' );
		}
		if ( null !== $row->grand_total && '' !== $row->grand_total ) {
			return '£' . number_format( (float) $row->grand_total, 0 );
		}
		return '—';
	}
}

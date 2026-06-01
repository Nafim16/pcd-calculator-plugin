<?php
/**
 * REST API: public quote submission.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_REST_Controller {

	const NAMESPACE = 'pcd-calculator/v1';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_submit' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
	}

	/**
	 * Verify REST nonce (public form).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public function permission_check( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			$nonce = $request->get_param( '_wpnonce' );
		}
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Handle POST /submit.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_submit( $request ) {
		$raw = $request->get_body();
		if ( strlen( $raw ) > 512000 ) {
			return new WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Payload too large.', 'pcd-pricing-calculator' ) ),
				413
			);
		}

		$payload = json_decode( $raw, true );
		if ( ! is_array( $payload ) ) {
			return new WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Invalid JSON.', 'pcd-pricing-calculator' ) ),
				400
			);
		}

		$validation = $this->validate_payload( $payload );
		if ( is_wp_error( $validation ) ) {
			return new WP_REST_Response(
				array( 'success' => false, 'message' => $validation->get_error_message() ),
				400
			);
		}

		$rate = PCD_Calculator_Rate_Limiter::check();
		if ( is_wp_error( $rate ) ) {
			return new WP_REST_Response(
				array( 'success' => false, 'message' => $rate->get_error_message() ),
				429
			);
		}

		$id = PCD_Calculator_Submission_Repository::insert_from_payload( $payload );
		if ( ! $id ) {
			return new WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Could not save submission.', 'pcd-pricing-calculator' ) ),
				500
			);
		}

		PCD_Calculator_Submission_Notification::send_for_submission( $id, $payload );

		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $id,
			),
			200
		);
	}

	/**
	 * @param array $payload Payload.
	 * @return true|WP_Error
	 */
	private function validate_payload( array $payload ) {
		$mode = PCD_Calculator_Submission_Repository::get_calculator_mode( $payload );
		if ( ! in_array( $mode, array( 'simple', 'complex' ), true ) ) {
			return new WP_Error( 'invalid_mode', __( 'Invalid calculator mode.', 'pcd-pricing-calculator' ) );
		}

		$contact = isset( $payload['contact'] ) && is_array( $payload['contact'] ) ? $payload['contact'] : array();
		$name    = isset( $contact['name'] ) ? trim( (string) $contact['name'] ) : '';
		$email   = isset( $contact['email'] ) ? trim( (string) $contact['email'] ) : '';
		$address = isset( $contact['address'] ) ? trim( (string) $contact['address'] ) : '';
		$phone   = isset( $contact['phone'] ) ? trim( (string) $contact['phone'] ) : '';

		if ( '' === $name ) {
			return new WP_Error( 'missing_name', __( 'Name is required.', 'pcd-pricing-calculator' ) );
		}
		if ( '' === $email || ! is_email( $email ) ) {
			return new WP_Error( 'missing_email', __( 'Valid email is required.', 'pcd-pricing-calculator' ) );
		}
		if ( '' === $address ) {
			return new WP_Error( 'missing_address', __( 'Address is required.', 'pcd-pricing-calculator' ) );
		}

		if ( strlen( $name ) > 200 ) {
			return new WP_Error( 'name_too_long', __( 'Name is too long.', 'pcd-pricing-calculator' ) );
		}
		if ( strlen( $email ) > 254 ) {
			return new WP_Error( 'email_too_long', __( 'Email is too long.', 'pcd-pricing-calculator' ) );
		}
		if ( strlen( $phone ) > 64 ) {
			return new WP_Error( 'phone_too_long', __( 'Phone number is too long.', 'pcd-pricing-calculator' ) );
		}
		if ( strlen( $address ) > 2000 ) {
			return new WP_Error( 'address_too_long', __( 'Address is too long.', 'pcd-pricing-calculator' ) );
		}

		// Honeypot: bots often fill hidden fields.
		if ( ! empty( $payload['website'] ) || ! empty( $payload['_hp'] ) ) {
			return new WP_Error( 'spam', __( 'Submission rejected.', 'pcd-pricing-calculator' ) );
		}

		return true;
	}
}

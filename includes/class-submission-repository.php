<?php
/**
 * Database access for quote submissions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Submission_Repository {

	/**
	 * Full table name including prefix.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'pcd_quote_submissions';
	}

	/**
	 * Insert submission from decoded JSON payload.
	 *
	 * @param array $payload Decoded request body.
	 * @return int|false Insert ID or false.
	 */
	public static function insert_from_payload( array $payload ) {
		global $wpdb;

		$indexed = self::extract_indexed_columns( $payload );
		$now     = current_time( 'mysql' );
		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$result = $wpdb->insert(
			self::table_name(),
			array(
				'calculator_mode'  => $indexed['calculator_mode'],
				'contact_name'     => $indexed['contact_name'],
				'contact_email'    => $indexed['contact_email'],
				'contact_phone'    => $indexed['contact_phone'],
				'contact_address'  => $indexed['contact_address'],
				'location_label'   => $indexed['location_label'],
				'property_sqm'     => $indexed['property_sqm'],
				'grand_total'      => $indexed['grand_total'],
				'quote_on_request' => $indexed['quote_on_request'],
				'payload_json'     => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE ),
				'submitted_at'     => $now,
				'ip_address'       => $ip,
			),
			array(
				'%s', '%s', '%s', '%s', '%s', '%s',
				'%s', '%s', '%d', '%s', '%s', '%s',
			)
		);

		return false === $result ? false : (int) $wpdb->insert_id;
	}

	/**
	 * @param int $id Submission ID.
	 * @return object|null
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		$table = self::table_name();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id )
		);
		return $row ? $row : null;
	}

	/**
	 * Total submission count.
	 *
	 * @return int
	 */
	public static function count_all() {
		global $wpdb;
		$table = self::table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * @param int $per_page Items per page.
	 * @param int $page     Page number (1-based).
	 * @return array{items: array, total: int}
	 */
	public static function get_paginated( $per_page = 20, $page = 1 ) {
		global $wpdb;
		$table  = self::table_name();
		$offset = max( 0, ( (int) $page - 1 ) * (int) $per_page );
		$limit  = (int) $per_page;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);

		return array(
			'items' => $items ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * @param int $id Submission ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;
		$deleted = $wpdb->delete(
			self::table_name(),
			array( 'id' => (int) $id ),
			array( '%d' )
		);
		return false !== $deleted && $deleted > 0;
	}

	/**
	 * Decode payload_json from row.
	 *
	 * @param object $row DB row.
	 * @return array
	 */
	public static function decode_payload( $row ) {
		if ( empty( $row->payload_json ) ) {
			return array();
		}
		$data = json_decode( $row->payload_json, true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Extract list-table columns from payload.
	 *
	 * @param array $payload Payload.
	 * @return array
	 */
	public static function extract_indexed_columns( array $payload ) {
		$mode = self::get_calculator_mode( $payload );

		$contact = isset( $payload['contact'] ) && is_array( $payload['contact'] ) ? $payload['contact'] : array();

		$location_label = '';
		$property_sqm   = null;
		$quote_on_req   = 0;
		$grand_total    = null;

		if ( 'complex' === $mode ) {
			$meta = isset( $payload['meta'] ) && is_array( $payload['meta'] ) ? $payload['meta'] : array();
			$location_label = isset( $meta['locationLabel'] ) ? (string) $meta['locationLabel'] : '';
			if ( isset( $meta['sizeM2'] ) && is_numeric( $meta['sizeM2'] ) ) {
				$property_sqm = (int) $meta['sizeM2'];
			}
			$quote_on_req = ! empty( $meta['quoteOnRequest'] ) ? 1 : 0;
			if ( isset( $payload['totals']['grandTotal'] ) && is_numeric( $payload['totals']['grandTotal'] ) && ! $quote_on_req ) {
				$grand_total = (float) $payload['totals']['grandTotal'];
			}
		} else {
			$location = isset( $payload['location'] ) && is_array( $payload['location'] ) ? $payload['location'] : array();
			$location_label = isset( $location['label'] ) ? (string) $location['label'] : '';
			$size           = isset( $payload['propertySize'] ) && is_array( $payload['propertySize'] ) ? $payload['propertySize'] : array();
			if ( isset( $size['sizeM2'] ) && is_numeric( $size['sizeM2'] ) ) {
				$property_sqm = (int) $size['sizeM2'];
			}
			$pricing = isset( $payload['pricing'] ) && is_array( $payload['pricing'] ) ? $payload['pricing'] : array();
			if ( isset( $pricing['totalPrice'] ) && is_numeric( $pricing['totalPrice'] ) ) {
				$grand_total = (float) $pricing['totalPrice'];
			}
		}

		return array(
			'calculator_mode'  => $mode,
			'contact_name'     => isset( $contact['name'] ) ? sanitize_text_field( $contact['name'] ) : '',
			'contact_email'    => isset( $contact['email'] ) ? sanitize_email( $contact['email'] ) : '',
			'contact_phone'    => isset( $contact['phone'] ) ? sanitize_text_field( $contact['phone'] ) : '',
			'contact_address'  => isset( $contact['address'] ) ? sanitize_textarea_field( $contact['address'] ) : '',
			'location_label'   => sanitize_text_field( $location_label ),
			'property_sqm'     => $property_sqm,
			'grand_total'      => $grand_total,
			'quote_on_request' => $quote_on_req,
		);
	}

	/**
	 * @param array $payload Payload.
	 * @return string simple|complex
	 */
	public static function get_calculator_mode( array $payload ) {
		if ( ! empty( $payload['calculatorMode'] ) && in_array( $payload['calculatorMode'], array( 'simple', 'complex' ), true ) ) {
			return $payload['calculatorMode'];
		}
		if ( ! empty( $payload['meta']['calculatorMode'] ) && in_array( $payload['meta']['calculatorMode'], array( 'simple', 'complex' ), true ) ) {
			return $payload['meta']['calculatorMode'];
		}
		return 'simple';
	}
}

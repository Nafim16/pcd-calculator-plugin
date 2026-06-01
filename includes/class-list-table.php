<?php
/**
 * Admin submissions list table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PCD_Calculator_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'submission',
				'plural'   => 'submissions',
				'ajax'     => false,
			)
		);
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return array(
			'id'              => __( 'ID', 'pcd-pricing-calculator' ),
			'submitted_at'    => __( 'Submitted', 'pcd-pricing-calculator' ),
			'calculator_mode' => __( 'Type', 'pcd-pricing-calculator' ),
			'contact_name'    => __( 'Name', 'pcd-pricing-calculator' ),
			'contact_email'   => __( 'Email', 'pcd-pricing-calculator' ),
			'contact_phone'   => __( 'Phone', 'pcd-pricing-calculator' ),
			'contact_address' => __( 'Address', 'pcd-pricing-calculator' ),
			'location_label'  => __( 'Location', 'pcd-pricing-calculator' ),
			'property_sqm'    => __( 'Size (sqm)', 'pcd-pricing-calculator' ),
			'grand_total'     => __( 'Total', 'pcd-pricing-calculator' ),
			'actions'         => __( 'Actions', 'pcd-pricing-calculator' ),
		);
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'submitted_at' => array( 'submitted_at', true ),
		);
	}

	/**
	 * Primary column for WP_List_Table row markup.
	 *
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'contact_name';
	}

	/**
	 * Empty state message.
	 */
	public function no_items() {
		esc_html_e( 'No quote submissions yet.', 'pcd-pricing-calculator' );
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		$per_page = 20;
		$page     = $this->get_pagenum();
		$data     = PCD_Calculator_Submission_Repository::get_paginated( $per_page, $page );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $data['items'];
		$this->set_pagination_args(
			array(
				'total_items' => $data['total'],
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $data['total'] / $per_page ) ),
			)
		);
	}

	/**
	 * Primary column: name links to detail view.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_contact_name( $item ) {
		$view_url = add_query_arg(
			array(
				'page' => 'pcd-quote-view',
				'id'   => (int) $item->id,
			),
			admin_url( 'admin.php' )
		);

		$title = esc_html( $item->contact_name );
		return '<strong><a class="row-title" href="' . esc_url( $view_url ) . '">' . $title . '</a></strong>';
	}

	/**
	 * @param object $item Row.
	 * @param string $column_name Column.
	 * @return string
	 */
	protected function column_id( $item ) {
		$view_url = add_query_arg(
			array(
				'page' => 'pcd-quote-view',
				'id'   => (int) $item->id,
			),
			admin_url( 'admin.php' )
		);
		return '<a class="pcd-id-link" href="' . esc_url( $view_url ) . '">#' . esc_html( (string) $item->id ) . '</a>';
	}

	/**
	 * @param object $item Row.
	 * @param string $column_name Column.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'submitted_at':
				$date = mysql2date( get_option( 'date_format' ), $item->submitted_at );
				$time = mysql2date( get_option( 'time_format' ), $item->submitted_at );
				return '<span class="pcd-date-cell"><span class="pcd-date">' . esc_html( $date ) . '</span><span class="pcd-time">' . esc_html( $time ) . '</span></span>';
			case 'calculator_mode':
				$label = 'complex' === $item->calculator_mode
					? __( 'Complex', 'pcd-pricing-calculator' )
					: __( 'Simple', 'pcd-pricing-calculator' );
				$class = 'complex' === $item->calculator_mode ? 'pcd-badge-complex' : 'pcd-badge-simple';
				return '<span class="pcd-badge ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
			case 'contact_email':
				return '<a class="pcd-email-link" href="mailto:' . esc_attr( $item->contact_email ) . '">' . esc_html( $item->contact_email ) . '</a>';
			case 'contact_phone':
				if ( ! empty( $item->contact_phone ) ) {
					$tel = preg_replace( '/\s+/', '', $item->contact_phone );
					return '<a class="pcd-phone-link" href="tel:' . esc_attr( $tel ) . '">' . esc_html( $item->contact_phone ) . '</a>';
				}
				return '<span class="pcd-muted">—</span>';
			case 'contact_address':
				return self::format_address_cell( $item->contact_address );
			case 'location_label':
				return $item->location_label
					? '<span class="pcd-location">' . esc_html( $item->location_label ) . '</span>'
					: '<span class="pcd-muted">—</span>';
			case 'property_sqm':
				return null !== $item->property_sqm
					? '<span class="pcd-sqm">' . esc_html( (string) $item->property_sqm ) . '</span>'
					: '<span class="pcd-muted">—</span>';
			case 'grand_total':
				if ( (int) $item->quote_on_request ) {
					return '<span class="pcd-total pcd-total-por">' . esc_html__( 'Price on request', 'pcd-pricing-calculator' ) . '</span>';
				}
				if ( null !== $item->grand_total ) {
					return '<span class="pcd-total">£' . esc_html( number_format( (float) $item->grand_total, 0 ) ) . '</span>';
				}
				return '<span class="pcd-muted">—</span>';
			default:
				return '';
		}
	}

	/**
	 * Actions column (method name must match column id for WP_List_Table).
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_actions( $item ) {
		$view_url = add_query_arg(
			array(
				'page' => 'pcd-quote-view',
				'id'   => (int) $item->id,
			),
			admin_url( 'admin.php' )
		);
		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'   => 'pcd-quotes',
					'action' => 'delete',
					'id'     => (int) $item->id,
				),
				admin_url( 'admin.php' )
			),
			'pcd_delete_submission_' . (int) $item->id
		);

		return sprintf(
			'<a href="%1$s" class="button button-small">%2$s</a> <a href="%3$s" class="button button-small button-link-delete" onclick="return confirm(%4$s);">%5$s</a>',
			esc_url( $view_url ),
			esc_html__( 'View details', 'pcd-pricing-calculator' ),
			esc_url( $delete_url ),
			wp_json_encode( __( 'Delete this submission?', 'pcd-pricing-calculator' ) ),
			esc_html__( 'Delete', 'pcd-pricing-calculator' )
		);
	}

	/**
	 * Address cell with full text in tooltip when truncated.
	 *
	 * @param string $address Address.
	 * @return string
	 */
	private static function format_address_cell( $address ) {
		$address = trim( (string) $address );
		if ( '' === $address ) {
			return '<span class="pcd-muted">—</span>';
		}
		$max = 48;
		if ( strlen( $address ) > $max ) {
			$short = substr( $address, 0, $max ) . '…';
			return '<span class="pcd-address" title="' . esc_attr( $address ) . '">' . esc_html( $short ) . '</span>';
		}
		return '<span class="pcd-address">' . esc_html( $address ) . '</span>';
	}
}

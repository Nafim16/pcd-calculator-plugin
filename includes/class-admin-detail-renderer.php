<?php
/**
 * Renders submission detail pages (all fields).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Admin_Detail_Renderer {

	/**
	 * @param object     $row        DB row.
	 * @param string|null $delete_url Delete URL.
	 */
	public static function render( $row, $delete_url = null ) {
		$payload = PCD_Calculator_Submission_Repository::decode_payload( $row );
		$mode    = $row->calculator_mode;

		echo '<div class="pcd-admin-detail">';

		self::render_toolbar( $delete_url );
		self::render_hero( $row );

		if ( 'complex' === $mode ) {
			self::render_complex( $payload, $row );
		} else {
			self::render_simple( $payload, $row );
		}

		self::render_raw_json( $payload );
		echo '</div>';
	}

	/**
	 * @param string|null $delete_url Delete URL.
	 */
	private static function render_toolbar( $delete_url ) {
		echo '<div class="pcd-toolbar">';
		echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=pcd-quotes' ) ) . '">&larr; ';
		esc_html_e( 'Back to list', 'pcd-pricing-calculator' );
		echo '</a>';
		if ( $delete_url ) {
			echo '<a class="button button-link-delete" href="' . esc_url( $delete_url ) . '" onclick="return confirm(';
			echo wp_json_encode( __( 'Delete this submission?', 'pcd-pricing-calculator' ) );
			echo ');">';
			esc_html_e( 'Delete submission', 'pcd-pricing-calculator' );
			echo '</a>';
		}
		echo '</div>';
	}

	/**
	 * @param object $row Row.
	 */
	private static function render_hero( $row ) {
		$type_label = 'complex' === $row->calculator_mode
			? __( 'Complex calculator', 'pcd-pricing-calculator' )
			: __( 'Simple calculator', 'pcd-pricing-calculator' );
		$badge_class = 'complex' === $row->calculator_mode ? 'pcd-badge-complex' : 'pcd-badge-simple';

		if ( (int) $row->quote_on_request ) {
			$total_html = esc_html__( 'Price on request', 'pcd-pricing-calculator' );
		} elseif ( null !== $row->grand_total ) {
			$total_html = esc_html( self::money( $row->grand_total ) );
		} else {
			$total_html = '—';
		}

		echo '<div class="pcd-hero">';
		echo '<div class="pcd-hero-main">';
		echo '<h1>' . esc_html( $row->contact_name ) . '</h1>';
		echo '<div class="pcd-hero-meta">';
		echo '<span class="pcd-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $type_label ) . '</span>';
		echo '<span>#' . esc_html( (string) $row->id ) . '</span>';
		echo '<span>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row->submitted_at ) ) . '</span>';
		echo '</div></div>';
		echo '<div class="pcd-hero-total">';
		echo '<div class="pcd-hero-total-label">' . esc_html__( 'Estimated total', 'pcd-pricing-calculator' ) . '</div>';
		echo '<div class="pcd-hero-total-value">' . $total_html . '</div>';
		echo '</div></div>';
	}

	/**
	 * @param array  $payload Payload.
	 * @param object $row     Row.
	 */
	private static function render_simple( array $payload, $row ) {
		$contact  = self::arr( $payload, 'contact' );
		$location = self::arr( $payload, 'location' );
		$size     = self::arr( $payload, 'propertySize' );
		$services = self::arr( $payload, 'services' );
		$pricing  = self::arr( $payload, 'pricing' );

		self::render_summary_grid( $row, $contact, $location, $size );

		$ms = self::arr( $services, 'measuredSurvey' );
		$ts = self::arr( $services, 'topographicalSurvey' );
		$ex = self::arr( $services, 'existingDrawingsPrep' );
		$pr = self::arr( $services, 'proposedDrawingsPrep' );

		self::section(
			__( 'Services selected', 'pcd-pricing-calculator' ),
			array(
				__( 'Measured survey', 'pcd-pricing-calculator' )         => self::pill_yes_no( ! empty( $ms['selected'] ) ),
				__( 'Topographical survey', 'pcd-pricing-calculator' )    => self::pill_yes_no( ! empty( $ts['selected'] ) ),
				__( 'Existing drawings prep', 'pcd-pricing-calculator' ) => self::pill_yes_no( ! empty( $ex['selected'] ) ),
				__( 'Floor count', 'pcd-pricing-calculator' )               => self::str( $ex, 'floorCount' ),
				__( 'Elevations count', 'pcd-pricing-calculator' )        => self::str( $ex, 'elevationsCount' ),
				__( 'Sections count', 'pcd-pricing-calculator' )          => self::str( $ex, 'sectionsCount' ),
				__( 'Proposed drawings prep', 'pcd-pricing-calculator' )  => self::pill_yes_no( ! empty( $pr['selected'] ) ),
				__( 'Proposed types', 'pcd-pricing-calculator' )            => self::format_list( isset( $pr['selectedTypes'] ) ? $pr['selectedTypes'] : array() ),
			)
		);

		self::section(
			__( 'Pricing breakdown', 'pcd-pricing-calculator' ),
			array(
				__( 'Measured survey', 'pcd-pricing-calculator' )      => self::money_val( $pricing, 'basePrice' ),
				__( 'Topographical survey', 'pcd-pricing-calculator' ) => self::money_val( $pricing, 'topographicalSurvey' ),
				__( 'Existing drawings', 'pcd-pricing-calculator' )  => self::money_val( $pricing, 'existingDrawingsPrep' ),
				__( 'Proposed drawings', 'pcd-pricing-calculator' )    => self::money_val( $pricing, 'proposedDrawingsPrep' ),
				__( 'Grand total', 'pcd-pricing-calculator' )          => '<strong>' . self::money_val( $pricing, 'totalPrice' ) . '</strong>',
			),
			true
		);

		if ( ! empty( $payload['rawFormData'] ) && is_array( $payload['rawFormData'] ) ) {
			self::section( __( 'Raw form fields', 'pcd-pricing-calculator' ), self::sanitize_raw_form_pairs( $payload['rawFormData'] ) );
		}
	}

	/**
	 * @param array  $payload Payload.
	 * @param object $row     Row.
	 */
	private static function render_complex( array $payload, $row ) {
		$contact  = self::arr( $payload, 'contact' );
		$meta     = self::arr( $payload, 'meta' );
		$services = self::arr( $payload, 'services' );
		$pricing  = self::arr( $payload, 'pricing' );
		$totals   = self::arr( $payload, 'totals' );

		self::render_summary_grid(
			$row,
			$contact,
			array(
				'label' => self::str( $meta, 'locationLabel' ),
			),
			array(
				'sizeM2'      => self::str( $meta, 'sizeM2' ),
				'sizeM2Input' => self::str( $meta, 'sizeM2Input' ),
			)
		);

		$ms = self::arr( $services, 'measuredSurvey' );
		$ts = self::arr( $services, 'topographicalSurvey' );
		self::section(
			__( 'Surveys', 'pcd-pricing-calculator' ),
			array(
				__( 'Measured survey', 'pcd-pricing-calculator' )      => self::pill_yes_no( ! empty( $ms['selected'] ) ) . ' · ' . self::money_nullable( isset( $ms['total'] ) ? $ms['total'] : null ),
				__( 'Topographical survey', 'pcd-pricing-calculator' ) => self::pill_yes_no( ! empty( $ts['selected'] ) ) . ' · ' . self::money_nullable( isset( $ts['total'] ) ? $ts['total'] : null ),
				__( 'Measured unit factor', 'pcd-pricing-calculator' ) => self::str( $meta, 'measuredUnitFactor' ),
				__( 'Topographical base fee', 'pcd-pricing-calculator' ) => self::str( $meta, 'topographicalBaseFee' ),
			)
		);

		$existing = self::arr( $services, 'existingDrawings' );
		$drawings = self::arr( $existing, 'drawings' );
		self::section( __( 'Existing drawings — quantities', 'pcd-pricing-calculator' ), self::label_drawings_existing( $drawings ) );
		self::section_flags( __( 'Existing complexity flags', 'pcd-pricing-calculator' ), self::arr( $existing, 'complexityFlags' ) );
		self::section(
			__( 'Existing drawings — options', 'pcd-pricing-calculator' ),
			array(
				__( 'Repetition level', 'pcd-pricing-calculator' ) => self::str( $existing, 'repetitionLevel' ),
			)
		);
		self::section( __( 'Existing drawings — pricing', 'pcd-pricing-calculator' ), self::label_result_existing( self::arr( $existing, 'result' ) ), true );

		$proposed = self::arr( $services, 'proposedDrawings' );
		self::section( __( 'Proposed drawings — quantities', 'pcd-pricing-calculator' ), self::label_drawings_proposed( self::arr( $proposed, 'drawingsAffected' ) ) );
		self::section_flags( __( 'Proposed scope flags', 'pcd-pricing-calculator' ), self::arr( $proposed, 'scope' ) );
		self::section(
			__( 'Proposed drawings — options', 'pcd-pricing-calculator' ),
			array(
				__( 'Clear instructions (10% discount)', 'pcd-pricing-calculator' ) => self::pill_yes_no( ! empty( $proposed['hasClearInstructions'] ) ),
			)
		);
		self::section( __( 'Proposed drawings — pricing', 'pcd-pricing-calculator' ), self::label_result_proposed( self::arr( $proposed, 'result' ) ), true );

		self::section(
			__( 'Pricing summary', 'pcd-pricing-calculator' ),
			array(
				__( 'Measured survey', 'pcd-pricing-calculator' )      => self::money_nullable( isset( $pricing['measuredSurvey'] ) ? $pricing['measuredSurvey'] : null ),
				__( 'Topographical survey', 'pcd-pricing-calculator' ) => self::money_nullable( isset( $pricing['topographicalSurvey'] ) ? $pricing['topographicalSurvey'] : null ),
				__( 'Existing drawings', 'pcd-pricing-calculator' )    => self::money_nullable( isset( $pricing['existingDrawings'] ) ? $pricing['existingDrawings'] : null ),
				__( 'Proposed drawings', 'pcd-pricing-calculator' )    => self::money_nullable( isset( $pricing['proposedDrawings'] ) ? $pricing['proposedDrawings'] : null ),
				__( 'Grand total', 'pcd-pricing-calculator' )          => '<strong>' . self::money_nullable( isset( $pricing['grandTotal'] ) ? $pricing['grandTotal'] : ( isset( $totals['grandTotal'] ) ? $totals['grandTotal'] : null ) ) . '</strong>',
			),
			true
		);
	}

	/**
	 * Contact / property / quick info cards.
	 *
	 * @param object $row      Row.
	 * @param array  $contact  Contact.
	 * @param array  $location Location or meta slice.
	 * @param array  $size     Size info.
	 */
	private static function render_summary_grid( $row, array $contact, array $location, array $size = array() ) {
		$loc_label = isset( $location['label'] ) ? $location['label'] : ( isset( $location['locationLabel'] ) ? $location['locationLabel'] : '' );
		$sqm       = null !== $row->property_sqm ? $row->property_sqm . ' sqm' : '—';
		if ( isset( $size['sizeM2'] ) && '—' !== $size['sizeM2'] ) {
			$sqm = esc_html( (string) $size['sizeM2'] ) . ' sqm';
			if ( ! empty( $size['sizeM2Input'] ) && $size['sizeM2Input'] !== $size['sizeM2'] ) {
				$sqm .= ' (' . esc_html__( 'input', 'pcd-pricing-calculator' ) . ': ' . esc_html( (string) $size['sizeM2Input'] ) . ')';
			}
		}

		echo '<div class="pcd-grid-3">';
		self::card(
			__( 'Contact', 'pcd-pricing-calculator' ),
			array(
				__( 'Name', 'pcd-pricing-calculator' )    => self::str( $contact, 'name' ),
				__( 'Email', 'pcd-pricing-calculator' )   => self::email_link( self::str( $contact, 'email' ) ),
				__( 'Phone', 'pcd-pricing-calculator' )     => self::str( $contact, 'phone' ),
				__( 'Address', 'pcd-pricing-calculator' )   => self::str( $contact, 'address' ),
			)
		);
		self::card(
			__( 'Property', 'pcd-pricing-calculator' ),
			array(
				__( 'Location', 'pcd-pricing-calculator' ) => $loc_label ? esc_html( $loc_label ) : '—',
				__( 'Size', 'pcd-pricing-calculator' )     => esc_html( $sqm ),
			)
		);
		self::card(
			__( 'Submission', 'pcd-pricing-calculator' ),
			array(
				__( 'Reference', 'pcd-pricing-calculator' ) => '#' . (int) $row->id,
				__( 'Type', 'pcd-pricing-calculator' )      => 'complex' === $row->calculator_mode ? __( 'Complex', 'pcd-pricing-calculator' ) : __( 'Simple', 'pcd-pricing-calculator' ),
				__( 'IP address', 'pcd-pricing-calculator' ) => $row->ip_address ? esc_html( $row->ip_address ) : '—',
			)
		);
		echo '</div>';
	}

	/**
	 * @param string $title Card title.
	 * @param array  $pairs Pairs (values may contain safe HTML).
	 */
	private static function card( $title, array $pairs ) {
		echo '<div class="pcd-card"><h3 class="pcd-card-title">' . esc_html( $title ) . '</h3>';
		self::kv_list( $pairs );
		echo '</div>';
	}

	/**
	 * @param string $title   Section title.
	 * @param array  $pairs   Pairs.
	 * @param bool   $highlight Highlight card.
	 */
	private static function section( $title, array $pairs, $highlight = false ) {
		echo '<div class="pcd-section">';
		echo '<h2>' . esc_html( $title ) . '</h2>';
		$class = 'pcd-card' . ( $highlight ? ' pcd-card-highlight' : '' );
		echo '<div class="' . esc_attr( $class ) . '">';
		self::kv_list( $pairs );
		echo '</div></div>';
	}

	/**
	 * @param string $title Section title.
	 * @param array  $flags Flags.
	 */
	private static function section_flags( $title, array $flags ) {
		echo '<div class="pcd-section"><h2>' . esc_html( $title ) . '</h2>';
		echo '<div class="pcd-card"><div class="pcd-flag-grid">';
		if ( empty( $flags ) ) {
			echo '<p style="margin:0;color:#646970;">' . esc_html__( 'None recorded', 'pcd-pricing-calculator' ) . '</p>';
		} else {
			foreach ( $flags as $key => $val ) {
				echo '<div class="pcd-flag-item"><span>' . esc_html( self::humanize_key( $key ) ) . '</span>';
				echo self::pill_yes_no( ! empty( $val ) );
				echo '</div>';
			}
		}
		echo '</div></div></div>';
	}

	/**
	 * @param array $pairs Label => value (trusted HTML only via wp_kses).
	 */
	private static function kv_list( array $pairs ) {
		echo '<ul class="pcd-kv-list">';
		foreach ( $pairs as $label => $value ) {
			if ( is_array( $value ) ) {
				$value = esc_html( wp_json_encode( $value ) );
			} else {
				$value = wp_kses( (string) $value, self::allowed_value_html() );
			}
			echo '<li><span class="pcd-k">' . esc_html( (string) $label ) . '</span>';
			echo '<span class="pcd-v">' . $value . '</span></li>';
		}
		echo '</ul>';
	}

	/**
	 * Sanitize user-submitted raw form key/value pairs for safe admin display.
	 *
	 * @param array $raw Raw form data from payload.
	 * @return array
	 */
	private static function sanitize_raw_form_pairs( array $raw ) {
		$out = array();
		foreach ( $raw as $key => $value ) {
			$label = sanitize_text_field( (string) $key );
			if ( '' === $label ) {
				continue;
			}
			if ( is_scalar( $value ) ) {
				$out[ $label ] = sanitize_text_field( (string) $value );
			} else {
				$out[ $label ] = wp_json_encode( $value );
			}
		}
		return $out;
	}

	/**
	 * HTML tags allowed in admin detail values (plugin-generated markup only).
	 *
	 * @return array
	 */
	private static function allowed_value_html() {
		return array(
			'strong' => array(),
			'span'   => array(
				'class' => true,
			),
			'a'      => array(
				'class' => true,
				'href'  => true,
			),
		);
	}

	/**
	 * @param array $payload Payload.
	 */
	private static function render_raw_json( array $payload ) {
		echo '<details class="pcd-raw-json">';
		echo '<summary>' . esc_html__( 'Developer: view raw JSON', 'pcd-pricing-calculator' ) . '</summary>';
		echo '<pre>' . esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre>';
		echo '</details>';
	}

	/**
	 * @param string $email Email.
	 * @return string
	 */
	private static function email_link( $email ) {
		if ( ! $email || '—' === $email ) {
			return '—';
		}
		return '<a class="pcd-email-link" href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
	}

	/**
	 * @param bool $val Value.
	 * @return string HTML.
	 */
	private static function pill_yes_no( $val ) {
		if ( $val ) {
			return '<span class="pcd-pill pcd-pill-yes">' . esc_html__( 'Yes', 'pcd-pricing-calculator' ) . '</span>';
		}
		return '<span class="pcd-pill pcd-pill-no">' . esc_html__( 'No', 'pcd-pricing-calculator' ) . '</span>';
	}

	/**
	 * @param array $drawings Drawings.
	 * @return array
	 */
	private static function label_drawings_existing( array $drawings ) {
		$labels = array(
			'floorPlans'         => __( 'Floor plans', 'pcd-pricing-calculator' ),
			'elevations'         => __( 'Elevations', 'pcd-pricing-calculator' ),
			'sections'           => __( 'Sections', 'pcd-pricing-calculator' ),
			'roofPlans'          => __( 'Roof plans', 'pcd-pricing-calculator' ),
			'topographicalPlans' => __( 'Topographical plans', 'pcd-pricing-calculator' ),
			'interiorElevations' => __( 'Interior elevations', 'pcd-pricing-calculator' ),
			'basicSitePlans'     => __( 'Basic site plans', 'pcd-pricing-calculator' ),
		);
		$out = array();
		foreach ( $labels as $key => $label ) {
			$out[ $label ] = isset( $drawings[ $key ] ) ? (string) $drawings[ $key ] : '0';
		}
		return $out;
	}

	/**
	 * @param array $drawings Drawings affected.
	 * @return array
	 */
	private static function label_drawings_proposed( array $drawings ) {
		$labels = array(
			'floorPlansAffected'           => __( 'Floor plans affected', 'pcd-pricing-calculator' ),
			'elevationsAffected'           => __( 'Elevations affected', 'pcd-pricing-calculator' ),
			'sectionsAffected'             => __( 'Sections affected', 'pcd-pricing-calculator' ),
			'roofPlansAffected'            => __( 'Roof plans affected', 'pcd-pricing-calculator' ),
			'basicSitePlansAffected'       => __( 'Basic site plans affected', 'pcd-pricing-calculator' ),
			'topographicalPlansAffected'   => __( 'Topographical plans affected', 'pcd-pricing-calculator' ),
			'interiorElevationsAffected'   => __( 'Interior elevations affected', 'pcd-pricing-calculator' ),
			'basicLocationPlansAffected'   => __( 'Basic location plans affected', 'pcd-pricing-calculator' ),
		);
		$out = array();
		foreach ( $labels as $key => $label ) {
			$out[ $label ] = isset( $drawings[ $key ] ) ? (string) $drawings[ $key ] : '0';
		}
		return $out;
	}

	/**
	 * @param array $result Result.
	 * @return array
	 */
	private static function label_result_existing( array $result ) {
		return array(
			__( 'Base fees', 'pcd-pricing-calculator' )             => self::money_nullable( isset( $result['baseFees'] ) ? $result['baseFees'] : null ),
			__( 'Complexity count', 'pcd-pricing-calculator' )      => self::str( $result, 'complexityCount' ),
			__( 'Complexity level', 'pcd-pricing-calculator' )      => self::str( $result, 'complexityLevel' ),
			__( 'Complexity multiplier', 'pcd-pricing-calculator' ) => self::str( $result, 'complexityMultiplier' ),
			__( 'Size multiplier', 'pcd-pricing-calculator' )       => self::str( $result, 'sizeMultiplier' ),
			__( 'Total', 'pcd-pricing-calculator' )                 => '<strong>' . self::money_nullable( isset( $result['total'] ) ? $result['total'] : null ) . '</strong>',
			__( 'Ask for quote', 'pcd-pricing-calculator' )         => self::pill_yes_no( ! empty( $result['askForQuote'] ) ),
		);
	}

	/**
	 * @param array $result Result.
	 * @return array
	 */
	private static function label_result_proposed( array $result ) {
		return array(
			__( 'Base fees', 'pcd-pricing-calculator' )             => self::money_nullable( isset( $result['baseFees'] ) ? $result['baseFees'] : null ),
			__( 'Complexity level', 'pcd-pricing-calculator' )      => self::str( $result, 'complexityLevel' ),
			__( 'Complexity multiplier', 'pcd-pricing-calculator' ) => self::str( $result, 'complexityMultiplier' ),
			__( 'Size multiplier', 'pcd-pricing-calculator' )       => self::str( $result, 'sizeMultiplier' ),
			__( 'Subtotal', 'pcd-pricing-calculator' )              => self::money_nullable( isset( $result['subtotal'] ) ? $result['subtotal'] : null ),
			__( 'Discount', 'pcd-pricing-calculator' )              => self::money_nullable( isset( $result['discount'] ) ? $result['discount'] : null ),
			__( 'Total', 'pcd-pricing-calculator' )                 => '<strong>' . self::money_nullable( isset( $result['total'] ) ? $result['total'] : null ) . '</strong>',
			__( 'Ask for quote', 'pcd-pricing-calculator' )         => self::pill_yes_no( ! empty( $result['askForQuote'] ) ),
		);
	}

	/**
	 * @param array  $arr Array.
	 * @param string $key Key.
	 * @return array
	 */
	private static function arr( array $arr, $key ) {
		return ( isset( $arr[ $key ] ) && is_array( $arr[ $key ] ) ) ? $arr[ $key ] : array();
	}

	/**
	 * @param array  $arr Array.
	 * @param string $key Key.
	 * @return string
	 */
	private static function str( array $arr, $key ) {
		if ( ! isset( $arr[ $key ] ) || '' === $arr[ $key ] ) {
			return '—';
		}
		return (string) $arr[ $key ];
	}

	/**
	 * @param mixed $val Value.
	 * @return string
	 */
	private static function money_nullable( $val ) {
		if ( null === $val || '' === $val ) {
			return esc_html__( 'Price on request', 'pcd-pricing-calculator' );
		}
		return esc_html( self::money( $val ) );
	}

	/**
	 * @param mixed $val Value.
	 * @return string
	 */
	private static function money( $val ) {
		return '£' . number_format( (float) $val, 0 );
	}

	/**
	 * @param array  $arr Array.
	 * @param string $key Key.
	 * @return string
	 */
	private static function money_val( array $arr, $key ) {
		return isset( $arr[ $key ] ) ? self::money( $arr[ $key ] ) : '—';
	}

	/**
	 * @param mixed $list List.
	 * @return string
	 */
	private static function format_list( $list ) {
		if ( ! is_array( $list ) ) {
			return sanitize_text_field( (string) $list );
		}
		if ( empty( $list ) ) {
			return '—';
		}
		return implode( ', ', array_map( 'sanitize_text_field', array_map( 'strval', $list ) ) );
	}

	/**
	 * @param string $key Key.
	 * @return string
	 */
	private static function humanize_key( $key ) {
		return ucwords( str_replace( array( '_', '-' ), ' ', preg_replace( '/([a-z])([A-Z])/', '$1 $2', $key ) ) );
	}
}

<?php
/**
 * Drawing pricing options (simple + complex calculators).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Pricing_Settings {

	const OPTION_KEY = 'pcd_calculator_drawing_pricing';

	/**
	 * Size bands used by the simple calculator.
	 *
	 * @return string[]
	 */
	public static function get_size_bands() {
		return array( '50-100', '100-150', '150-200', '200-300', '300-400', '400-500' );
	}

	/**
	 * Default pricing structure.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$bands = self::get_size_bands();
		$band_prices_floor = array(
			'50-100'  => 30,
			'100-150' => 35,
			'150-200' => 40,
			'200-300' => 50,
			'300-400' => 60,
			'400-500' => 70,
		);

		return array(
			'simple'  => array(
				'existing' => array(
					'floor'      => $band_prices_floor,
					'elevations' => $band_prices_floor,
					'sections'   => $band_prices_floor,
				),
				'proposed' => array(
					'default'            => 100,
					'rearExtension'      => 100,
					'sideExtension'      => 100,
					'frontExtension'     => 100,
					'gardenExtension'    => 100,
					'garageConversion'   => 100,
					'loftConversion'     => 100,
					'dormerExtension'    => 100,
					'singleStoreyDormers' => 100,
					'doubleStoreyDormers' => 160,
					'others'             => 80,
				),
			),
			'complex' => array(
				'existing' => array(
					'floorPlans'          => 40,
					'elevations'          => 40,
					'sections'            => 40,
					'roofPlans'           => 40,
					'topographicalPlans'  => 40,
					'interiorElevations'  => 20,
					'basicSitePlans'      => 100,
				),
				'proposed' => array(
					'floorPlansAffected'           => 30,
					'elevationsAffected'           => 30,
					'sectionsAffected'             => 30,
					'roofPlansAffected'            => 30,
					'basicSitePlansAffected'       => 30,
					'topographicalPlansAffected'   => 30,
					'interiorElevationsAffected'   => 10,
					'basicLocationPlansAffected'   => 20,
				),
			),
		);
	}

	/**
	 * @return array
	 */
	public static function get_all() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return self::array_merge_deep( self::get_defaults(), $saved );
	}

	/**
	 * @return array
	 */
	public static function get_for_frontend() {
		return self::get_all();
	}

	/**
	 * @param array $input Raw POST pricing array.
	 * @return array
	 */
	public static function sanitize_from_post( array $input ) {
		$defaults = self::get_defaults();
		$clean    = self::get_defaults();

		if ( isset( $input['simple']['existing'] ) && is_array( $input['simple']['existing'] ) ) {
			foreach ( array( 'floor', 'elevations', 'sections' ) as $key ) {
				if ( empty( $input['simple']['existing'][ $key ] ) || ! is_array( $input['simple']['existing'][ $key ] ) ) {
					continue;
				}
				foreach ( self::get_size_bands() as $band ) {
					if ( isset( $input['simple']['existing'][ $key ][ $band ] ) ) {
						$clean['simple']['existing'][ $key ][ $band ] = self::sanitize_price( $input['simple']['existing'][ $key ][ $band ] );
					}
				}
			}
		}

		if ( isset( $input['simple']['proposed'] ) && is_array( $input['simple']['proposed'] ) ) {
			foreach ( $defaults['simple']['proposed'] as $prop_key => $prop_val ) {
				if ( isset( $input['simple']['proposed'][ $prop_key ] ) ) {
					$clean['simple']['proposed'][ $prop_key ] = self::sanitize_price( $input['simple']['proposed'][ $prop_key ] );
				}
			}
		}

		if ( isset( $input['complex']['existing'] ) && is_array( $input['complex']['existing'] ) ) {
			foreach ( $defaults['complex']['existing'] as $key => $val ) {
				if ( isset( $input['complex']['existing'][ $key ] ) ) {
					$clean['complex']['existing'][ $key ] = self::sanitize_price( $input['complex']['existing'][ $key ] );
				}
			}
		}

		if ( isset( $input['complex']['proposed'] ) && is_array( $input['complex']['proposed'] ) ) {
			foreach ( $defaults['complex']['proposed'] as $key => $val ) {
				if ( isset( $input['complex']['proposed'][ $key ] ) ) {
					$clean['complex']['proposed'][ $key ] = self::sanitize_price( $input['complex']['proposed'][ $key ] );
				}
			}
		}

		return $clean;
	}

	/**
	 * @param mixed $value Value.
	 * @return float
	 */
	private static function sanitize_price( $value ) {
		$n = is_numeric( $value ) ? (float) $value : 0;
		return max( 0, round( $n, 2 ) );
	}

	/**
	 * Deep merge arrays (saved values override defaults).
	 *
	 * @param array $defaults Defaults.
	 * @param array $saved Saved.
	 * @return array
	 */
	private static function array_merge_deep( array $defaults, array $saved ) {
		$merged = $defaults;
		foreach ( $saved as $key => $value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = self::array_merge_deep( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}
		return $merged;
	}

	/**
	 * Human labels for admin form.
	 *
	 * @return array
	 */
	public static function get_labels() {
		return array(
			'complex' => array(
				'existing' => array(
					'floorPlans'         => __( 'Floor plans', 'pcd-pricing-calculator' ),
					'elevations'         => __( 'Elevations', 'pcd-pricing-calculator' ),
					'sections'           => __( 'Sections', 'pcd-pricing-calculator' ),
					'roofPlans'          => __( 'Roof plans', 'pcd-pricing-calculator' ),
					'topographicalPlans' => __( 'Topographical plans', 'pcd-pricing-calculator' ),
					'interiorElevations' => __( 'Interior elevations', 'pcd-pricing-calculator' ),
					'basicSitePlans'     => __( 'Basic site plans', 'pcd-pricing-calculator' ),
				),
				'proposed' => array(
					'floorPlansAffected'         => __( 'Floor plans affected', 'pcd-pricing-calculator' ),
					'elevationsAffected'         => __( 'Elevations affected', 'pcd-pricing-calculator' ),
					'sectionsAffected'           => __( 'Sections affected', 'pcd-pricing-calculator' ),
					'roofPlansAffected'          => __( 'Roof plans affected', 'pcd-pricing-calculator' ),
					'basicSitePlansAffected'     => __( 'Basic site plans affected', 'pcd-pricing-calculator' ),
					'topographicalPlansAffected' => __( 'Topographical plans affected', 'pcd-pricing-calculator' ),
					'interiorElevationsAffected' => __( 'Interior elevations affected', 'pcd-pricing-calculator' ),
					'basicLocationPlansAffected' => __( 'Basic location plans affected', 'pcd-pricing-calculator' ),
				),
			),
			'simple'  => array(
				'proposed' => array(
					'default'            => __( 'Default proposed item', 'pcd-pricing-calculator' ),
					'rearExtension'      => __( 'Rear extension', 'pcd-pricing-calculator' ),
					'sideExtension'      => __( 'Side extension', 'pcd-pricing-calculator' ),
					'frontExtension'     => __( 'Front extension', 'pcd-pricing-calculator' ),
					'gardenExtension'    => __( 'Garden extension', 'pcd-pricing-calculator' ),
					'garageConversion'   => __( 'Garage conversion', 'pcd-pricing-calculator' ),
					'loftConversion'     => __( 'Loft conversion', 'pcd-pricing-calculator' ),
					'dormerExtension'    => __( 'Dormer extension', 'pcd-pricing-calculator' ),
					'singleStoreyDormers' => __( 'Single storey dormers', 'pcd-pricing-calculator' ),
					'doubleStoreyDormers' => __( 'Double storey dormers', 'pcd-pricing-calculator' ),
					'others'             => __( 'Others', 'pcd-pricing-calculator' ),
				),
			),
		);
	}
}

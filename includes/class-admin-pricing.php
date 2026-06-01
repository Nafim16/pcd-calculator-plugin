<?php
/**
 * Admin: drawing pricing settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Admin_Pricing {

	/**
	 * Hook admin.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 20 );
		add_action( 'admin_post_pcd_save_drawing_pricing', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	/**
	 * Submenu under PCD Quotes.
	 */
	public static function register_menu() {
		add_submenu_page(
			'pcd-quotes',
			__( 'Drawing pricing', 'pcd-pricing-calculator' ),
			__( 'Drawing pricing', 'pcd-pricing-calculator' ),
			'manage_options',
			'pcd-drawing-pricing',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * @param string $hook Hook.
	 */
	public static function enqueue_styles( $hook ) {
		if ( false === strpos( $hook, 'pcd-drawing-pricing' ) ) {
			return;
		}
		wp_add_inline_style(
			'wp-admin',
			'.pcd-pricing-wrap .pcd-pricing-card{background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:16px 20px;margin:16px 0}
			.pcd-pricing-wrap .pcd-pricing-card h2{margin:0 0 12px;font-size:15px;color:#08473c}
			.pcd-pricing-wrap table.widefat input[type=number]{width:90px}
			.pcd-pricing-wrap .pcd-pricing-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
			.pcd-pricing-wrap .pcd-field label{display:block;font-weight:600;margin-bottom:4px;font-size:13px}'
		);
	}

	/**
	 * Save handler.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'pcd-pricing-calculator' ) );
		}
		check_admin_referer( 'pcd_save_drawing_pricing' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw = isset( $_POST['pcd_pricing'] ) && is_array( $_POST['pcd_pricing'] ) ? wp_unslash( $_POST['pcd_pricing'] ) : array();
		$clean = PCD_Calculator_Pricing_Settings::sanitize_from_post( $raw );
		update_option( PCD_Calculator_Pricing_Settings::OPTION_KEY, $clean );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'pcd-drawing-pricing',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pricing = PCD_Calculator_Pricing_Settings::get_all();
		$labels  = PCD_Calculator_Pricing_Settings::get_labels();
		$bands   = PCD_Calculator_Pricing_Settings::get_size_bands();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Drawing prices saved.', 'pcd-pricing-calculator' ) . '</p></div>';
		}

		echo '<div class="wrap pcd-pricing-wrap">';
		echo '<h1>' . esc_html__( 'Drawing pricing', 'pcd-pricing-calculator' ) . '</h1>';
		echo '<p>' . esc_html__( 'Set per-drawing fees used by the simple and complex calculators. Survey pricing is unchanged.', 'pcd-pricing-calculator' ) . '</p>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="pcd_save_drawing_pricing" />';
		wp_nonce_field( 'pcd_save_drawing_pricing' );

		/* Simple — existing by size band */
		echo '<div class="pcd-pricing-card">';
		echo '<h2>' . esc_html__( 'Simple calculator — existing drawings (per unit, by property size band)', 'pcd-pricing-calculator' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Size band (sqm)', 'pcd-pricing-calculator' ) . '</th>';
		echo '<th>' . esc_html__( 'Floor (£)', 'pcd-pricing-calculator' ) . '</th>';
		echo '<th>' . esc_html__( 'Elevations (£)', 'pcd-pricing-calculator' ) . '</th>';
		echo '<th>' . esc_html__( 'Sections (£)', 'pcd-pricing-calculator' ) . '</th></tr></thead><tbody>';
		foreach ( $bands as $band ) {
			echo '<tr><td><strong>' . esc_html( $band ) . '</strong></td>';
			foreach ( array( 'floor', 'elevations', 'sections' ) as $col ) {
				$val = $pricing['simple']['existing'][ $col ][ $band ];
				echo '<td><input type="number" min="0" step="0.01" name="pcd_pricing[simple][existing][' . esc_attr( $col ) . '][' . esc_attr( $band ) . ']" value="' . esc_attr( (string) $val ) . '" /></td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table></div>';

		/* Simple — proposed */
		echo '<div class="pcd-pricing-card">';
		echo '<h2>' . esc_html__( 'Simple calculator — proposed drawing types (£ each)', 'pcd-pricing-calculator' ) . '</h2>';
		echo '<div class="pcd-pricing-grid">';
		foreach ( $labels['simple']['proposed'] as $key => $label ) {
			$val = $pricing['simple']['proposed'][ $key ];
			echo '<div class="pcd-field"><label for="simple-proposed-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
			echo '<input type="number" min="0" step="0.01" id="simple-proposed-' . esc_attr( $key ) . '" name="pcd_pricing[simple][proposed][' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $val ) . '" /></div>';
		}
		echo '</div></div>';

		/* Complex — existing */
		echo '<div class="pcd-pricing-card">';
		echo '<h2>' . esc_html__( 'Complex calculator — existing drawings (£ each)', 'pcd-pricing-calculator' ) . '</h2>';
		echo '<div class="pcd-pricing-grid">';
		foreach ( $labels['complex']['existing'] as $key => $label ) {
			$val = $pricing['complex']['existing'][ $key ];
			echo '<div class="pcd-field"><label for="complex-existing-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
			echo '<input type="number" min="0" step="0.01" id="complex-existing-' . esc_attr( $key ) . '" name="pcd_pricing[complex][existing][' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $val ) . '" /></div>';
		}
		echo '</div></div>';

		/* Complex — proposed */
		echo '<div class="pcd-pricing-card">';
		echo '<h2>' . esc_html__( 'Complex calculator — proposed drawings affected (£ each)', 'pcd-pricing-calculator' ) . '</h2>';
		echo '<div class="pcd-pricing-grid">';
		foreach ( $labels['complex']['proposed'] as $key => $label ) {
			$val = $pricing['complex']['proposed'][ $key ];
			echo '<div class="pcd-field"><label for="complex-proposed-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
			echo '<input type="number" min="0" step="0.01" id="complex-proposed-' . esc_attr( $key ) . '" name="pcd_pricing[complex][proposed][' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $val ) . '" /></div>';
		}
		echo '</div></div>';

		submit_button( __( 'Save drawing prices', 'pcd-pricing-calculator' ) );
		echo '</form></div>';
	}
}

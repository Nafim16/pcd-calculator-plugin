<?php
/**
 * WordPress admin: submissions list, view, delete.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Admin_Menu {

	/**
	 * Hook admin.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	/**
	 * Register top-level menu.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'PCD Quotes', 'pcd-pricing-calculator' ),
			__( 'PCD Quotes', 'pcd-pricing-calculator' ),
			'manage_options',
			'pcd-quotes',
			array( __CLASS__, 'render_list_page' ),
			'dashicons-calculator',
			26
		);

		add_submenu_page(
			'pcd-quotes',
			__( 'All submissions', 'pcd-pricing-calculator' ),
			__( 'All submissions', 'pcd-pricing-calculator' ),
			'manage_options',
			'pcd-quotes',
			array( __CLASS__, 'render_list_page' )
		);

		add_submenu_page(
			null,
			__( 'View submission', 'pcd-pricing-calculator' ),
			__( 'View submission', 'pcd-pricing-calculator' ),
			'manage_options',
			'pcd-quote-view',
			array( __CLASS__, 'render_view_page' )
		);
	}

	/**
	 * Admin styles.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function enqueue_styles( $hook ) {
		if ( false === strpos( $hook, 'pcd-quotes' ) && false === strpos( $hook, 'pcd-quote-view' ) ) {
			return;
		}
		$css_file = PCD_CALCULATOR_PLUGIN_DIR . 'assets/css/admin.css';
		$version  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : PCD_CALCULATOR_VERSION;
		wp_enqueue_style(
			'pcd-calculator-admin',
			PCD_CALCULATOR_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$version
		);
	}

	/**
	 * Handle delete action.
	 */
	public static function handle_delete() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['page'] ) || 'pcd-quotes' !== $_GET['page'] ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['action'] ) || 'delete' !== $_GET['action'] || empty( $_GET['id'] ) ) {
			return;
		}

		$id = (int) $_GET['id'];
		check_admin_referer( 'pcd_delete_submission_' . $id );

		PCD_Calculator_Submission_Repository::delete( $id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'pcd-quotes',
					'deleted' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * List page.
	 */
	public static function render_list_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['deleted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Submission deleted.', 'pcd-pricing-calculator' ) . '</p></div>';
		}

		$table = new PCD_Calculator_List_Table();
		$table->prepare_items();
		$total = PCD_Calculator_Submission_Repository::count_all();

		echo '<div class="wrap pcd-quotes-wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'PCD Quote Submissions', 'pcd-pricing-calculator' ) . '</h1>';
		echo '<span class="pcd-subtitle">';
		echo esc_html(
			sprintf(
				/* translators: %d: number of submissions */
				_n( '%d submission', '%d submissions', $total, 'pcd-pricing-calculator' ),
				$total
			)
		);
		echo '</span>';
		echo '<hr class="wp-header-end">';

		echo '<div class="notice notice-info" style="margin-top:12px;"><p>';
		echo '<strong>' . esc_html__( 'Shortcode:', 'pcd-pricing-calculator' ) . '</strong> ';
		echo '<code>[pcd_calculator]</code> — ';
		echo esc_html__( 'Add to any page or Elementor Shortcode widget.', 'pcd-pricing-calculator' );
		echo '</p></div>';

		echo '<form method="get" style="margin-top:16px;">';
		echo '<input type="hidden" name="page" value="pcd-quotes" />';
		$table->display();
		echo '</form></div>';
	}

	/**
	 * View detail page.
	 */
	public static function render_view_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$row = PCD_Calculator_Submission_Repository::get_by_id( $id );

		echo '<div class="wrap">';
		if ( ! $row ) {
			echo '<h1>' . esc_html__( 'Submission not found', 'pcd-pricing-calculator' ) . '</h1>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=pcd-quotes' ) ) . '">' . esc_html__( 'Back to list', 'pcd-pricing-calculator' ) . '</a></p>';
			echo '</div>';
			return;
		}

		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'   => 'pcd-quotes',
					'action' => 'delete',
					'id'     => (int) $row->id,
				),
				admin_url( 'admin.php' )
			),
			'pcd_delete_submission_' . (int) $row->id
		);

		PCD_Calculator_Admin_Detail_Renderer::render( $row, $delete_url );
		echo '</div>';
	}
}

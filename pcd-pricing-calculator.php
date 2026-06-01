<?php
/**
 * Plugin Name: PCD Pricing Calculator
 * Description: Embeds the PCD simple/complex pricing calculator and stores quote submissions in WordPress.
 * Version: 1.1.2
 * Author: ByteBlazeIT
 * Author URI: https://byteblazeit.com/
 * Text Domain: pcd-pricing-calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PCD_CALCULATOR_VERSION', '1.1.2' );
define( 'PCD_CALCULATOR_PLUGIN_FILE', __FILE__ );
define( 'PCD_CALCULATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PCD_CALCULATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-activator.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-submission-repository.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-rate-limiter.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-rest-controller.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-admin-detail-renderer.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-list-table.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-pricing-settings.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-admin-pricing.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-submission-notification.php';
require_once PCD_CALCULATOR_PLUGIN_DIR . 'includes/class-admin-notifications.php';

register_activation_hook( __FILE__, array( 'PCD_Calculator_Activator', 'activate' ) );

/**
 * Main plugin bootstrap.
 */
final class PCD_Pricing_Calculator_Plugin {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		if ( is_admin() ) {
			PCD_Calculator_Admin_Menu::init();
			PCD_Calculator_Admin_Pricing::init();
			PCD_Calculator_Admin_Notifications::init();
		}
	}

	/**
	 * Register REST routes.
	 */
	public function register_rest_routes() {
		$controller = new PCD_Calculator_REST_Controller();
		$controller->register_routes();
	}

	/**
	 * Shortcode output.
	 *
	 * @return string
	 */
	public function render_shortcode() {
		$asset = PCD_CALCULATOR_PLUGIN_DIR . 'assets/calculator-v2.html';
		if ( ! file_exists( $asset ) ) {
			return '<p>' . esc_html__( 'Calculator asset missing.', 'pcd-pricing-calculator' ) . '</p>';
		}

		$config = array(
			'restUrl'  => rest_url( 'pcd-calculator/v1/submit' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'pricing'  => PCD_Calculator_Pricing_Settings::get_for_frontend(),
		);

		$bridge = PCD_CALCULATOR_PLUGIN_DIR . 'assets/js/pcd-pricing-bridge.js';
		$bridge_ver = file_exists( $bridge ) ? (string) filemtime( $bridge ) : PCD_CALCULATOR_VERSION;

		ob_start();
		echo '<script>window.pcdCalculator = ' . wp_json_encode( $config ) . ';</script>';
		echo '<script src="' . esc_url( PCD_CALCULATOR_PLUGIN_URL . 'assets/js/pcd-pricing-bridge.js?v=' . rawurlencode( $bridge_ver ) ) . '"></script>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static HTML asset.
		echo file_get_contents( $asset );
		return ob_get_clean();
	}

	/**
	 * Register [pcd_calculator] shortcode.
	 */
	public function register_shortcode() {
		add_shortcode( 'pcd_calculator', array( $this, 'render_shortcode' ) );
	}
}

PCD_Pricing_Calculator_Plugin::instance();

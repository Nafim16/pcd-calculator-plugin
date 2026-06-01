<?php
/**
 * Admin: email notification settings for new submissions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Admin_Notifications {

	/**
	 * Hook admin.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 20 );
		add_action( 'admin_post_pcd_save_notification_settings', array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * Submenu under PCD Quotes.
	 */
	public static function register_menu() {
		add_submenu_page(
			'pcd-quotes',
			__( 'Email notifications', 'pcd-pricing-calculator' ),
			__( 'Email notifications', 'pcd-pricing-calculator' ),
			'manage_options',
			'pcd-quote-notifications',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Save settings.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'pcd-pricing-calculator' ) );
		}

		check_admin_referer( 'pcd_save_notification_settings' );

		PCD_Calculator_Submission_Notification::save_settings(
			array(
				'enabled' => isset( $_POST['pcd_notify_enabled'] ),
				'email'   => isset( $_POST['pcd_notify_email'] ) ? wp_unslash( $_POST['pcd_notify_email'] ) : '',
			)
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'pcd-quote-notifications',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = PCD_Calculator_Submission_Notification::get_settings();

		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notification settings saved.', 'pcd-pricing-calculator' ) . '</p></div>';
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Email notifications', 'pcd-pricing-calculator' ) . '</h1>';
		echo '<p>' . esc_html__( 'When someone submits a quote through the calculator, WordPress sends an email with the key details and a link to view the submission.', 'pcd-pricing-calculator' ) . '</p>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="pcd_save_notification_settings" />';
		wp_nonce_field( 'pcd_save_notification_settings' );

		echo '<table class="form-table" role="presentation">';
		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Enable notifications', 'pcd-pricing-calculator' ) . '</th>';
		echo '<td>';
		printf(
			'<label><input type="checkbox" name="pcd_notify_enabled" value="1" %s /> %s</label>',
			checked( $settings['enabled'], true, false ),
			esc_html__( 'Send an email when a new quote is submitted', 'pcd-pricing-calculator' )
		);
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<th scope="row"><label for="pcd_notify_email">' . esc_html__( 'Notify email address', 'pcd-pricing-calculator' ) . '</label></th>';
		echo '<td>';
		printf(
			'<input type="text" class="regular-text" id="pcd_notify_email" name="pcd_notify_email" value="%s" />',
			esc_attr( $settings['email'] )
		);
		echo '<p class="description">' . esc_html__( 'Defaults to the site admin email. Use commas to notify multiple addresses.', 'pcd-pricing-calculator' ) . '</p>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';

		submit_button( __( 'Save notification settings', 'pcd-pricing-calculator' ) );
		echo '</form>';
		echo '</div>';
	}
}

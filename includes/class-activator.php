<?php
/**
 * Plugin activation: database table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCD_Calculator_Activator {

	const DB_VERSION = '1.0.0';

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'pcd_calculator_db_version', self::DB_VERSION );
	}

	/**
	 * Create or update submissions table.
	 */
	public static function create_tables() {
		global $wpdb;

		$table_name      = PCD_Calculator_Submission_Repository::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			calculator_mode varchar(16) NOT NULL DEFAULT '',
			contact_name varchar(255) NOT NULL DEFAULT '',
			contact_email varchar(255) NOT NULL DEFAULT '',
			contact_phone varchar(64) NOT NULL DEFAULT '',
			contact_address text NOT NULL,
			location_label varchar(128) NOT NULL DEFAULT '',
			property_sqm int(11) DEFAULT NULL,
			grand_total decimal(10,2) DEFAULT NULL,
			quote_on_request tinyint(1) NOT NULL DEFAULT 0,
			payload_json longtext NOT NULL,
			submitted_at datetime NOT NULL,
			ip_address varchar(45) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY calculator_mode (calculator_mode),
			KEY submitted_at (submitted_at),
			KEY contact_email (contact_email)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

<?php
/**
 * Database Installation & Schema Migration Routine
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installs and upgrades core database tables for the Fleet Manager plugin.
 *
 * @return void
 */
function dev_nahian_install_database() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// 1. Cars Table
	$table_cars = $wpdb->prefix . 'dev_nahian_cars';
	$sql_cars   = "CREATE TABLE $table_cars (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(191) NOT NULL,
		brand varchar(100) NOT NULL DEFAULT '',
		year int(4) NOT NULL DEFAULT 2026,
		vin varchar(64) NOT NULL,
		plate varchar(64) NOT NULL DEFAULT '',
		category varchar(64) NOT NULL DEFAULT 'sports-gt',
		color varchar(64) NOT NULL DEFAULT '',
		status varchar(32) NOT NULL DEFAULT 'ready',
		location varchar(191) NOT NULL DEFAULT '',
		horsepower int(11) NOT NULL DEFAULT 0,
		acceleration varchar(32) NOT NULL DEFAULT '',
		speed varchar(32) NOT NULL DEFAULT '',
		drivetrain varchar(32) NOT NULL DEFAULT 'AWD',
		transmission varchar(32) NOT NULL DEFAULT 'automatic',
		fuel varchar(32) NOT NULL DEFAULT 'gasoline',
		seats int(3) NOT NULL DEFAULT 2,
		doors int(3) NOT NULL DEFAULT 2,
		bags int(3) NOT NULL DEFAULT 2,
		price decimal(10,2) NOT NULL DEFAULT '0.00',
		deposit decimal(10,2) NOT NULL DEFAULT '0.00',
		odometer int(11) NOT NULL DEFAULT 0,
		fuel_level int(3) NOT NULL DEFAULT 100,
		image text DEFAULT NULL,
		notes text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY vin (vin)
	) $charset_collate;";
	dbDelta( $sql_cars );

	// 2. Bookings Table
	$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
	$sql_bookings   = "CREATE TABLE $table_bookings (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		booking_code varchar(64) NOT NULL,
		customer_name varchar(191) NOT NULL,
		customer_email varchar(191) NOT NULL,
		customer_phone varchar(64) NOT NULL DEFAULT '',
		license_number varchar(64) NOT NULL DEFAULT '',
		emergency_contact varchar(191) NOT NULL DEFAULT '',
		passenger_count int(3) NOT NULL DEFAULT 1,
		car_id bigint(20) unsigned NOT NULL DEFAULT 0,
		car_name varchar(191) NOT NULL DEFAULT '',
		booking_status varchar(32) NOT NULL DEFAULT 'active',
		pickup_date date NOT NULL,
		pickup_time time NOT NULL DEFAULT '10:00:00',
		pickup_location varchar(191) NOT NULL DEFAULT '',
		return_date date NOT NULL,
		return_time time NOT NULL DEFAULT '10:00:00',
		return_location varchar(191) NOT NULL DEFAULT '',
		flight_number varchar(64) NOT NULL DEFAULT '',
		payment_method varchar(32) NOT NULL DEFAULT 'card',
		total_amount decimal(10,2) NOT NULL DEFAULT '0.00',
		customer_notes text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY booking_code (booking_code),
		KEY booking_status (booking_status)
	) $charset_collate;";
	dbDelta( $sql_bookings );

	// 3. Categories Table
	$table_categories = $wpdb->prefix . 'dev_nahian_categories';
	$sql_categories   = "CREATE TABLE $table_categories (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(191) NOT NULL,
		slug varchar(64) NOT NULL,
		base_price decimal(10,2) NOT NULL DEFAULT '0.00',
		status varchar(32) NOT NULL DEFAULT 'active',
		description text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY slug (slug)
	) $charset_collate;";
	dbDelta( $sql_categories );

	// 4. Extras Table
	$table_extras = $wpdb->prefix . 'dev_nahian_extras';
	$sql_extras   = "CREATE TABLE $table_extras (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(191) NOT NULL,
		price decimal(10,2) NOT NULL DEFAULT '0.00',
		billing_type varchar(32) NOT NULL DEFAULT 'daily',
		notice_period varchar(32) NOT NULL DEFAULT 'none',
		status varchar(32) NOT NULL DEFAULT 'active',
		description text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) $charset_collate;";
	dbDelta( $sql_extras );

	// 5. Customers Table
	$table_customers = $wpdb->prefix . 'dev_nahian_customers';
	$sql_customers   = "CREATE TABLE $table_customers (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		customer_code varchar(64) NOT NULL,
		name varchar(191) NOT NULL,
		email varchar(191) NOT NULL,
		phone varchar(64) NOT NULL DEFAULT '',
		dob date DEFAULT NULL,
		country varchar(100) NOT NULL DEFAULT '',
		license_number varchar(64) NOT NULL DEFAULT '',
		license_expiry date DEFAULT NULL,
		tier varchar(32) NOT NULL DEFAULT 'standard',
		status varchar(32) NOT NULL DEFAULT 'verified',
		notes text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY email (email)
	) $charset_collate;";
	dbDelta( $sql_customers );

	// 6. Promos Table
	$table_promos = $wpdb->prefix . 'dev_nahian_promos';
	$sql_promos   = "CREATE TABLE $table_promos (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		code varchar(64) NOT NULL,
		discount_type varchar(32) NOT NULL DEFAULT 'percentage',
		discount_value decimal(10,2) NOT NULL DEFAULT '0.00',
		expiry_date date DEFAULT NULL,
		usage_limit int(11) NOT NULL DEFAULT 100,
		times_used int(11) NOT NULL DEFAULT 0,
		status varchar(32) NOT NULL DEFAULT 'active',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY code (code)
	) $charset_collate;";
	dbDelta( $sql_promos );

	// 7. Maintenance Table
	$table_maint = $wpdb->prefix . 'dev_nahian_maintenance';
	$sql_maint   = "CREATE TABLE $table_maint (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		car_id bigint(20) unsigned NOT NULL,
		car_name varchar(191) NOT NULL DEFAULT '',
		service_type varchar(128) NOT NULL,
		cost decimal(10,2) NOT NULL DEFAULT '0.00',
		service_date date NOT NULL,
		status varchar(32) NOT NULL DEFAULT 'scheduled',
		notes text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY car_id (car_id)
	) $charset_collate;";
	dbDelta( $sql_maint );

	// 8. Inspections Table
	$table_insp = $wpdb->prefix . 'dev_nahian_inspections';
	$sql_insp   = "CREATE TABLE $table_insp (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		booking_code varchar(64) NOT NULL,
		car_name varchar(191) NOT NULL DEFAULT '',
		inspector_name varchar(191) NOT NULL,
		inspection_type varchar(32) NOT NULL DEFAULT 'checkout',
		fuel_level int(3) NOT NULL DEFAULT 100,
		odometer int(11) NOT NULL DEFAULT 0,
		condition_notes text DEFAULT NULL,
		inspected_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY booking_code (booking_code)
	) $charset_collate;";
	dbDelta( $sql_insp );

	// 9. Staff Table
	$table_staff = $wpdb->prefix . 'dev_nahian_staff';
	$sql_staff   = "CREATE TABLE $table_staff (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		staff_code varchar(64) NOT NULL,
		name varchar(191) NOT NULL,
		email varchar(191) NOT NULL,
		phone varchar(64) NOT NULL DEFAULT '',
		role_type varchar(64) NOT NULL DEFAULT 'chauffeur',
		license_no varchar(64) NOT NULL DEFAULT '',
		status varchar(32) NOT NULL DEFAULT 'active',
		notes text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY email (email)
	) $charset_collate;";
	dbDelta( $sql_staff );

	// 10. Finances Table
	$table_finances = $wpdb->prefix . 'dev_nahian_finances';
	$sql_finances   = "CREATE TABLE $table_finances (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		title varchar(191) NOT NULL,
		type varchar(32) NOT NULL DEFAULT 'income',
		category varchar(64) NOT NULL DEFAULT 'rental',
		amount decimal(10,2) NOT NULL DEFAULT '0.00',
		txn_date date NOT NULL,
		reference_code varchar(64) NOT NULL DEFAULT '',
		notes text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY txn_date (txn_date)
	) $charset_collate;";
	dbDelta( $sql_finances );

	if ( defined( 'DEV_NAHIAN_DB_VERSION' ) ) {
		update_option( 'dev_nahian_db_version', DEV_NAHIAN_DB_VERSION );
	}
}
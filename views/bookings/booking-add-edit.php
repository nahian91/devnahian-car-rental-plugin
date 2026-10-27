<?php
/**
 * Bookings Module: All-in-One Operations View (`tab-booking.php`)
 * Handles request routing, database CRUD operations, and unified responsive UI.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.2.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dev-nahian-car' ), 403 );
}

global $wpdb;
$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
$table_cars     = $wpdb->prefix . 'dev_nahian_cars';
$table_extras   = $wpdb->prefix . 'dev_nahian_extras';

$base     = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'booking',
	),
	admin_url( 'admin.php' )
);
$action   = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$currency = (string) get_option( 'dev_nahian_currency', 'USD' );

// -----------------------------------------------------------------------------
// 1. Process Deletion Request
// -----------------------------------------------------------------------------
if ( 'delete' === $action && $id > 0 ) {
	check_admin_referer( 'dev_nahian_delete_booking_' . $id );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete( $table_bookings, array( 'id' => $id ), array( '%d' ) );

	wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking', 'deleted' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}

// -----------------------------------------------------------------------------
// 2. Process Create POST Request
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_add_booking_submit'] ) ) {
	check_admin_referer( 'dev_nahian_add_booking_verify', 'dev_nahian_booking_nonce' );

	$car_id       = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;
	$car_name_val = isset( $_POST['car_name'] ) ? sanitize_text_field( wp_unslash( $_POST['car_name'] ) ) : '';

	if ( empty( $car_name_val ) && $car_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$car_name_val = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
	}

	$booking_code = 'BK-' . strtoupper( wp_generate_password( 6, false, false ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$table_bookings,
		array(
			'booking_code'      => $booking_code,
			'customer_name'     => isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '',
			'customer_email'    => isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '',
			'customer_phone'    => isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '',
			'license_number'    => isset( $_POST['license_number'] ) ? sanitize_text_field( wp_unslash( $_POST['license_number'] ) ) : '',
			'emergency_contact' => isset( $_POST['emergency_contact'] ) ? sanitize_text_field( wp_unslash( $_POST['emergency_contact'] ) ) : '',
			'passenger_count'   => isset( $_POST['passenger_count'] ) ? max( 1, absint( wp_unslash( $_POST['passenger_count'] ) ) ) : 1,
			'car_id'            => $car_id,
			'car_name'          => $car_name_val,
			'booking_status'    => isset( $_POST['booking_status'] ) ? sanitize_key( wp_unslash( $_POST['booking_status'] ) ) : 'pending',
			'pickup_date'       => isset( $_POST['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) ) : '',
			'pickup_time'       => isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '10:00:00',
			'pickup_location'   => isset( $_POST['pickup_location'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_location'] ) ) : '',
			'return_date'       => isset( $_POST['return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_date'] ) ) : '',
			'return_time'       => isset( $_POST['return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['return_time'] ) ) : '10:00:00',
			'return_location'   => isset( $_POST['return_location'] ) ? sanitize_text_field( wp_unslash( $_POST['return_location'] ) ) : '',
			'flight_number'     => isset( $_POST['flight_number'] ) ? sanitize_text_field( wp_unslash( $_POST['flight_number'] ) ) : '',
			'payment_method'    => isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'card',
			'total_amount'      => isset( $_POST['total_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['total_amount'] ) ) : 0.00,
			'customer_notes'    => isset( $_POST['customer_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_notes'] ) ) : '',
			'created_at'        => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
	);

	if ( false !== $inserted ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}

// -----------------------------------------------------------------------------
// 3. Process Update POST Request
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_edit_booking_submit'] ) && $id > 0 ) {
	check_admin_referer( 'dev_nahian_edit_booking_' . $id, 'dev_nahian_booking_nonce' );

	$car_id       = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;
	$car_name_val = isset( $_POST['car_name'] ) ? sanitize_text_field( wp_unslash( $_POST['car_name'] ) ) : '';

	if ( empty( $car_name_val ) && $car_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$car_name_val = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$updated = $wpdb->update(
		$table_bookings,
		array(
			'customer_name'     => isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '',
			'customer_email'    => isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '',
			'customer_phone'    => isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '',
			'license_number'    => isset( $_POST['license_number'] ) ? sanitize_text_field( wp_unslash( $_POST['license_number'] ) ) : '',
			'emergency_contact' => isset( $_POST['emergency_contact'] ) ? sanitize_text_field( wp_unslash( $_POST['emergency_contact'] ) ) : '',
			'passenger_count'   => isset( $_POST['passenger_count'] ) ? max( 1, absint( wp_unslash( $_POST['passenger_count'] ) ) ) : 1,
			'car_id'            => $car_id,
			'car_name'          => $car_name_val,
			'booking_status'    => isset( $_POST['booking_status'] ) ? sanitize_key( wp_unslash( $_POST['booking_status'] ) ) : 'pending',
			'pickup_date'       => isset( $_POST['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) ) : '',
			'pickup_time'       => isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '10:00:00',
			'pickup_location'   => isset( $_POST['pickup_location'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_location'] ) ) : '',
			'return_date'       => isset( $_POST['return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_date'] ) ) : '',
			'return_time'       => isset( $_POST['return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['return_time'] ) ) : '10:00:00',
			'return_location'   => isset( $_POST['return_location'] ) ? sanitize_text_field( wp_unslash( $_POST['return_location'] ) ) : '',
			'flight_number'     => isset( $_POST['flight_number'] ) ? sanitize_text_field( wp_unslash( $_POST['flight_number'] ) ) : '',
			'payment_method'    => isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'card',
			'total_amount'      => isset( $_POST['total_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['total_amount'] ) ) : 0.00,
			'customer_notes'    => isset( $_POST['customer_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_notes'] ) ) : '',
		),
		array( 'id' => $id ),
		array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s' ),
		array( '%d' )
	);

	if ( false !== $updated ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}

// -----------------------------------------------------------------------------
// 4. Data Preparation for Forms
// -----------------------------------------------------------------------------
$available_cars   = array();
$location_options = array();

if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$available_cars = $wpdb->get_results( "SELECT id, name, price, location FROM {$table_cars} ORDER BY name ASC", ARRAY_A );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$location_options = $wpdb->get_col( "SELECT DISTINCT title FROM {$table_extras} WHERE status = 'active' ORDER BY title ASC" );
	if ( empty( $location_options ) ) {
		$location_options = array(
			__( 'Executive Terminal Alpha', 'dev-nahian-car' ),
			__( 'Downtown Dispatch Hub', 'dev-nahian-car' ),
			__( 'International Airport Bay 4', 'dev-nahian-car' ),
			__( 'Northside Service Center', 'dev-nahian-car' ),
		);
	}
}

$settings_payment_methods = get_option(
	'dev_nahian_payment_methods',
	array(
		'card'      => __( 'Credit Card (Stripe Terminal)', 'dev-nahian-car' ),
		'wallet'    => __( 'Apple Pay / Google Pay', 'dev-nahian-car' ),
		'corporate' => __( 'Corporate Direct Account', 'dev-nahian-car' ),
		'wire'      => __( 'Direct Bank Wire Transfer', 'dev-nahian-car' ),
	)
);

$is_add_active = ( 'add' === $subtab || 'edit' === $action );
$add_url       = add_query_arg( array( 'subtab' => 'add' ), $base );
$all_url       = add_query_arg( array( 'subtab' => 'all' ), $base );
$csv_url       = wp_nonce_url( admin_url( 'admin-post.php?action=dev_nahian_export_csv' ), 'dev_nahian_csv_nonce' );
?>

<style>
	/* =========================================================================
	   Dev Nahian Unified CSS System Tokens & Modern Form Input Styling
	   ========================================================================= */
	:root {
		--dnf-primary: #0f172a;
		--dnf-primary-light: #1e293b;
		--dnf-accent-teal: #0d9488;
		--dnf-accent-teal-glow: rgba(13, 148, 136, 0.14);
		--dnf-accent-gold: #b88e1e;
		--dnf-accent-blue: #2563eb;
		--dnf-accent-green: #16a34a;
		--dnf-danger: #dc2626;
		--dnf-danger-bg: #fef2f2;
		--dnf-danger-border: #fecaca;
		--dnf-bg-card: #ffffff;
		--dnf-bg-subtle: #f8fafc;
		--dnf-border: #e2e8f0;
		--dnf-border-hover: #cbd5e1;
		--dnf-text-primary: #0f172a;
		--dnf-text-secondary: #475569;
		--dnf-text-tertiary: #94a3b8;
		--dnf-radius-sm: 6px;
		--dnf-radius-md: 10px;
		--dnf-radius-lg: 16px;
		--dnf-radius-pill: 9999px;
		--dnf-shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.04);
		--dnf-shadow-md: 0 4px 16px -2px rgba(15, 23, 42, 0.06);
	}

	.dnf-booking-wrap {
		display: flex;
		flex-direction: column;
		gap: 22px;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
		color: var(--dnf-text-primary);
		-webkit-font-smoothing: antialiased;
		margin-top: 10px;
	}

	/* Executive Header */
	.dnf-booking-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: #ffffff;
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-lg);
		padding: 22px 28px;
		box-shadow: var(--dnf-shadow-sm);
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-booking-title-area {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-badge-row {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-booking-title {
		margin: 0;
		font-size: 22px;
		font-weight: 800;
		letter-spacing: -0.02em;
		color: var(--dnf-text-primary);
	}

	/* Pulsing Status Chip */
	.dnf-status-chip.gold {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		padding: 3px 10px;
		border-radius: var(--dnf-radius-pill);
		background: #fefce8;
		border: 1px solid #fef08a;
		color: var(--dnf-accent-gold);
	}
	.dnf-radar-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: var(--dnf-accent-gold);
		box-shadow: 0 0 0 3px rgba(184, 142, 30, 0.2);
		animation: dnfPulseDot 2s infinite ease-in-out;
	}
	@keyframes dnfPulseDot {
		0%, 100% { opacity: 1; transform: scale(1); }
		50% { opacity: 0.4; transform: scale(0.85); }
	}

	/* Subnav / Action Strip */
	.dnf-action-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 14px;
		border-bottom: 1px solid var(--dnf-border);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-action-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Button Design Tokens */
	.dnf-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		padding: 9px 18px;
		border-radius: var(--dnf-radius-md);
		font-size: 13px;
		font-weight: 700;
		text-decoration: none;
		cursor: pointer;
		transition: all 0.15s ease-in-out;
		line-height: 1.2;
		border: 1px solid transparent;
		box-sizing: border-box;
	}
	.dnf-btn-primary {
		background: var(--dnf-primary);
		color: #ffffff !important;
		border-color: var(--dnf-primary);
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
	}
	.dnf-btn-primary:hover {
		background: var(--dnf-primary-light);
		border-color: var(--dnf-primary-light);
		transform: translateY(-1px);
	}
	.dnf-btn-secondary {
		background: #ffffff;
		color: var(--dnf-text-secondary) !important;
		border-color: var(--dnf-border);
	}
	.dnf-btn-secondary:hover {
		background: var(--dnf-bg-subtle);
		color: var(--dnf-text-primary) !important;
		border-color: var(--dnf-border-hover);
		transform: translateY(-1px);
	}
	.dnf-btn-danger {
		background: var(--dnf-danger-bg);
		color: var(--dnf-danger) !important;
		border-color: var(--dnf-danger-border);
	}
	.dnf-btn-danger:hover {
		background: #fee2e2;
		border-color: #fca5a5;
	}
	.dnf-btn-sm {
		padding: 6px 12px;
		font-size: 12px;
		border-radius: var(--dnf-radius-sm);
	}

	/* =========================================================================
	   Elite SaaS Modern Input and Select Box Controls
	   ========================================================================= */
	.dnf-form-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-lg);
		padding: 28px;
		box-shadow: var(--dnf-shadow-sm);
	}
	.dnf-form-card-header {
		margin-bottom: 22px;
		padding-bottom: 14px;
		border-bottom: 1px solid #f1f5f9;
		display: flex;
		align-items: center;
		gap: 10px;
	}
	.dnf-form-card-title {
		margin: 0;
		font-size: 13px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: var(--dnf-accent-teal);
	}
	.dnf-form-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px;
	}
	@media (max-width: 1024px) {
		.dnf-form-grid { grid-template-columns: repeat(2, 1fr); }
	}
	@media (max-width: 640px) {
		.dnf-form-grid { grid-template-columns: 1fr; }
	}
	.col-span-full { grid-column: 1 / -1; }
	.col-span-2 { grid-column: span 2; }
	@media (max-width: 640px) {
		.col-span-2 { grid-column: 1 / -1; }
	}

	.dnf-form-group {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-label {
		font-size: 11.5px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-secondary);
		display: flex;
		align-items: center;
		gap: 4px;
	}

	/* Form Input Styling */
	.dnf-input {
		width: 100%;
		height: 42px;
		background: var(--dnf-bg-subtle);
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-md);
		padding: 0 14px;
		font-size: 13.5px;
		color: var(--dnf-text-primary);
		font-weight: 500;
		outline: none;
		box-sizing: border-box;
		transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		font-family: inherit;
	}
	.dnf-input:hover {
		border-color: var(--dnf-border-hover);
		background: #ffffff;
	}
	.dnf-input:focus {
		background: #ffffff;
		border-color: var(--dnf-accent-teal);
		box-shadow: 0 0 0 3px var(--dnf-accent-teal-glow);
	}
	.dnf-input::placeholder {
		color: var(--dnf-text-tertiary);
		font-weight: 400;
	}

	/* Form Select Styling with Custom SVG Chevron */
	.dnf-select-wrapper {
		position: relative;
		width: 100%;
	}
	.dnf-select {
		width: 100%;
		height: 42px;
		background: var(--dnf-bg-subtle);
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-md);
		padding: 0 38px 0 14px;
		font-size: 13.5px;
		color: var(--dnf-text-primary);
		font-weight: 500;
		outline: none;
		box-sizing: border-box;
		cursor: pointer;
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
		transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		font-family: inherit;
	}
	.dnf-select:hover {
		border-color: var(--dnf-border-hover);
		background: #ffffff;
	}
	.dnf-select:focus {
		background: #ffffff;
		border-color: var(--dnf-accent-teal);
		box-shadow: 0 0 0 3px var(--dnf-accent-teal-glow);
	}
	.dnf-select-wrapper::after {
		content: "";
		position: absolute;
		right: 14px;
		top: 50%;
		transform: translateY(-50%);
		width: 10px;
		height: 6px;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 6'%3E%3Cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.8' d='m1 1 4 4 4-4'/%3E%3C/svg%3E");
		background-repeat: no-repeat;
		background-position: center;
		pointer-events: none;
		transition: transform 0.2s ease;
	}
	.dnf-select-wrapper:focus-within::after {
		transform: translateY(-50%) rotate(180deg);
	}

	/* Textarea Styling */
	.dnf-textarea {
		width: 100%;
		height: 96px;
		background: var(--dnf-bg-subtle);
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-md);
		padding: 12px 14px;
		font-size: 13.5px;
		color: var(--dnf-text-primary);
		font-weight: 500;
		outline: none;
		box-sizing: border-box;
		transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		resize: vertical;
		font-family: inherit;
	}
	.dnf-textarea:hover {
		border-color: var(--dnf-border-hover);
		background: #ffffff;
	}
	.dnf-textarea:focus {
		background: #ffffff;
		border-color: var(--dnf-accent-teal);
		box-shadow: 0 0 0 3px var(--dnf-accent-teal-glow);
	}

	/* Dynamic Rate Quotation Badge */
	.dnf-quote-preview-pill {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		background: #f0fdf4;
		border: 1px solid #bbf7d0;
		color: #16a34a;
		padding: 6px 14px;
		border-radius: var(--dnf-radius-pill);
		font-size: 12px;
		font-weight: 700;
		margin-top: 6px;
	}

	/* Table & Action Groups */
	.dnf-table-container {
		background: #ffffff;
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-lg);
		box-shadow: var(--dnf-shadow-sm);
		overflow-x: auto;
	}
	.dnf-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 12px;
		padding: 3px 8px;
		background: #f1f5f9;
		border-radius: var(--dnf-radius-sm);
		border: 1px solid #e2e8f0;
		font-weight: 700;
	}
	.dnf-status-pill {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		font-size: 11px;
		font-weight: 700;
		padding: 3px 10px;
		border-radius: var(--dnf-radius-pill);
		text-transform: uppercase;
		letter-spacing: 0.04em;
		line-height: 1.2;
	}
	.dnf-status-pill.active,
	.dnf-status-pill.confirmed { background: #f0fdfa; color: var(--dnf-accent-teal); border: 1px solid #ccfbf1; }
	.dnf-status-pill.completed { background: #f1f5f9; color: var(--dnf-text-secondary); border: 1px solid #cbd5e1; }
	.dnf-status-pill.pending   { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
	.dnf-status-pill.cancelled { background: var(--dnf-danger-bg); color: var(--dnf-danger); border: 1px solid var(--dnf-danger-border); }

	.dnf-actions-group {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		justify-content: flex-end;
	}
</style>

<div class="dnf-booking-wrap">

	<!-- Header Panel -->
	<div class="dnf-booking-header">
		<div class="dnf-booking-title-area">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'Live Dispatch Ledger', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext" style="font-size: 12px; color: var(--dnf-text-secondary);"><?php esc_html_e( 'Fleet Reservation Terminal & Dynamic Quotation', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-booking-title"><?php esc_html_e( 'Bookings & Dispatches', 'dev-nahian-car' ); ?></h2>
		</div>

		<div class="dnf-hud-controls">
			<?php if ( ! $is_add_active ) : ?>
				<a href="<?php echo esc_url( $add_url ); ?>" class="dnf-btn dnf-btn-primary">
					<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Add Booking', 'dev-nahian-car' ); ?></span>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $all_url ); ?>" class="dnf-btn dnf-btn-secondary">
					<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Back to Ledger', 'dev-nahian-car' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php
	// Contextual Feedback Notices
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible" style="margin:0 !important; border-radius:8px;"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Booking created and dispatched successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible" style="margin:0 !important; border-radius:8px;"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Booking dossier updated successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success is-dismissible" style="margin:0 !important; border-radius:8px;"><p><strong>' . esc_html__( 'Notice:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Booking record permanently removed.', 'dev-nahian-car' ) . '</p></div>';
	}
	?>

	<!-- Action & Sub-Navigation Bar -->
	<div class="dnf-action-bar">
		<div class="dnf-action-group">
			<a href="<?php echo esc_url( $all_url ); ?>" class="dnf-btn <?php echo ( ! $is_add_active && 'all' === $subtab ) ? 'dnf-btn-primary' : 'dnf-btn-secondary'; ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
				<span><?php esc_html_e( 'All Manifests', 'dev-nahian-car' ); ?></span>
			</a>
			<a href="<?php echo esc_url( $csv_url ); ?>" class="dnf-btn dnf-btn-secondary">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
				<span><?php esc_html_e( 'Export CSV Manifest', 'dev-nahian-car' ); ?></span>
			</a>
		</div>

		<div class="dnf-action-group">
			<span class="dnf-count-chip">
				<?php
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$total_records = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_bookings}" );
				printf(
					/* translators: %s: number of records */
					esc_html__( '%s Records Synced', 'dev-nahian-car' ),
					esc_html( number_format_i18n( $total_records ) )
				);
				?>
			</span>
		</div>
	</div>

	<?php
	// -------------------------------------------------------------------------
	// View Mode: Detail Dossier
	// -------------------------------------------------------------------------
	if ( 'view' === $action && $id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $id ), ARRAY_A );
		if ( $booking ) :
			$view_status = sanitize_key( $booking['booking_status'] ?? 'pending' );
			?>
			<div class="dnf-form-card">
				<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--dnf-border); padding-bottom:18px; margin-bottom: 20px;">
					<div>
						<span style="font-size:11.5px; font-weight:700; color:var(--dnf-accent-gold); text-transform:uppercase; letter-spacing:0.06em;">
							<?php esc_html_e( 'Manifest Audit File', 'dev-nahian-car' ); ?>
						</span>
						<h3 style="margin:4px 0 0; font-size:20px; font-weight:800; color:var(--dnf-text-primary);">
							<?php
							printf(
								/* translators: %s: booking code */
								esc_html__( 'Reservation Dossier: #%s', 'dev-nahian-car' ),
								esc_html( (string) $booking['booking_code'] )
							);
							?>
						</h3>
					</div>
					<span class="dnf-status-pill <?php echo esc_attr( $view_status ); ?>" style="font-size:12px; padding:5px 14px;">
						&bull; <?php echo esc_html( ucfirst( $view_status ) ); ?>
					</span>
				</div>

				<table class="wp-list-table widefat fixed striped" style="border-radius:10px; overflow:hidden;">
					<tr>
						<th style="width:240px; font-weight:700;"><?php esc_html_e( 'Client Profile', 'dev-nahian-car' ); ?></th>
						<td>
							<strong style="font-size:14.5px; color:var(--dnf-text-primary);"><?php echo esc_html( (string) $booking['customer_name'] ); ?></strong><br>
							<span style="color:var(--dnf-text-secondary); font-size:13px;">
								<?php echo esc_html( $booking['customer_email'] . ' &bull; ' . $booking['customer_phone'] ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th style="font-weight:700;"><?php esc_html_e( 'License & Passengers', 'dev-nahian-car' ); ?></th>
						<td>
							<code class="dnf-code"><?php echo esc_html( ! empty( $booking['license_number'] ) ? $booking['license_number'] : __( 'N/A', 'dev-nahian-car' ) ); ?></code>
							&bull; <?php printf( esc_html__( '%d Passengers Allocated', 'dev-nahian-car' ), absint( $booking['passenger_count'] ) ); ?>
						</td>
					</tr>
					<tr>
						<th style="font-weight:700;"><?php esc_html_e( 'Allocated Vehicle Chassis', 'dev-nahian-car' ); ?></th>
						<td><strong><?php echo esc_html( ! empty( $booking['car_name'] ) ? $booking['car_name'] : __( 'Fleet Asset', 'dev-nahian-car' ) ); ?></strong></td>
					</tr>
					<tr>
						<th style="font-weight:700;"><?php esc_html_e( 'Reservation Window', 'dev-nahian-car' ); ?></th>
						<td>
							<?php echo esc_html( $booking['pickup_date'] . ' ' . $booking['pickup_time'] ); ?>
							&rarr;
							<?php echo esc_html( $booking['return_date'] . ' ' . $booking['return_time'] ); ?>
						</td>
					</tr>
					<tr>
						<th style="font-weight:700;"><?php esc_html_e( 'Terminals / Route', 'dev-nahian-car' ); ?></th>
						<td>
							<strong><?php esc_html_e( 'Pickup:', 'dev-nahian-car' ); ?></strong> <?php echo esc_html( (string) $booking['pickup_location'] ); ?><br>
							<strong><?php esc_html_e( 'Return:', 'dev-nahian-car' ); ?></strong> <?php echo esc_html( (string) $booking['return_location'] ); ?>
						</td>
					</tr>
					<?php if ( ! empty( $booking['flight_number'] ) ) : ?>
						<tr>
							<th style="font-weight:700;"><?php esc_html_e( 'Flight Identification', 'dev-nahian-car' ); ?></th>
							<td><code class="dnf-code"><?php echo esc_html( (string) $booking['flight_number'] ); ?></code></td>
						</tr>
					<?php endif; ?>
					<tr>
						<th style="font-weight:700;"><?php esc_html_e( 'Financial Settlement', 'dev-nahian-car' ); ?></th>
						<td>
							<strong style="font-size:18px; color:var(--dnf-text-primary);">
								<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $booking['total_amount'], 2 ) ); ?>
							</strong>
							<span style="font-size:12px; color:var(--dnf-text-secondary); margin-left: 6px;">
								(<?php echo esc_html( strtoupper( (string) $booking['payment_method'] ) ); ?>)
							</span>
						</td>
					</tr>
					<?php if ( ! empty( $booking['customer_notes'] ) ) : ?>
						<tr>
							<th style="font-weight:700;"><?php esc_html_e( 'Special Instructions', 'dev-nahian-car' ); ?></th>
							<td><em><?php echo nl2br( esc_html( (string) $booking['customer_notes'] ) ); ?></em></td>
						</tr>
					<?php endif; ?>
				</table>

				<div style="margin-top: 24px; display: flex; gap: 10px;">
					<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $booking['id'], 'subtab' => 'add' ), $base ) ); ?>" class="dnf-btn dnf-btn-primary">
						<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Edit This Dossier', 'dev-nahian-car' ); ?></span>
					</a>
					<a href="<?php echo esc_url( $all_url ); ?>" class="dnf-btn dnf-btn-secondary">
						<span><?php esc_html_e( 'Back to Manifest Ledger', 'dev-nahian-car' ); ?></span>
					</a>
				</div>
			</div>
		<?php else : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'The requested dispatch booking record could not be found.', 'dev-nahian-car' ); ?></p></div>
		<?php endif; ?>

	<?php
	// -------------------------------------------------------------------------
	// Add / Edit Form Mode (SaaS Workstation)
	// -------------------------------------------------------------------------
	} elseif ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
		$is_edit = ( 'edit' === $action && $id > 0 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$booking = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $id ), ARRAY_A ) : array();
		?>
		<form method="post" action="" id="dnfBookingForm" style="display:flex; flex-direction:column; gap:20px;">
			<?php
			if ( $is_edit ) {
				wp_nonce_field( 'dev_nahian_edit_booking_' . $id, 'dev_nahian_booking_nonce' );
				echo '<input type="hidden" name="dev_nahian_edit_booking_submit" value="1">';
			} else {
				wp_nonce_field( 'dev_nahian_add_booking_verify', 'dev_nahian_booking_nonce' );
				echo '<input type="hidden" name="dev_nahian_add_booking_submit" value="1">';
			}
			?>
			<input type="hidden" id="car_name" name="car_name" value="<?php echo esc_attr( $booking['car_name'] ?? '' ); ?>">

			<!-- Card 1: Client Profile -->
			<div class="dnf-form-card">
				<div class="dnf-form-card-header">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					<h3 class="dnf-form-card-title"><?php esc_html_e( '1. Client Identity & KYC Verification', 'dev-nahian-car' ); ?></h3>
				</div>

				<div class="dnf-form-grid">
					<div class="dnf-form-group">
						<label class="dnf-label" for="customer_name">
							<?php esc_html_e( 'Full Legal Name', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger);">*</span>
						</label>
						<input type="text" id="customer_name" name="customer_name" class="dnf-input" value="<?php echo esc_attr( $booking['customer_name'] ?? '' ); ?>" required placeholder="<?php esc_attr_e( 'e.g. Johnathan Vance', 'dev-nahian-car' ); ?>">
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="customer_email">
							<?php esc_html_e( 'Client Email Address', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger);">*</span>
						</label>
						<input type="email" id="customer_email" name="customer_email" class="dnf-input" value="<?php echo esc_attr( $booking['customer_email'] ?? '' ); ?>" required placeholder="<?php esc_attr_e( 'vance@enterprise.com', 'dev-nahian-car' ); ?>">
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="customer_phone">
							<?php esc_html_e( 'Direct Mobile Number', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger);">*</span>
						</label>
						<input type="tel" id="customer_phone" name="customer_phone" class="dnf-input" value="<?php echo esc_attr( $booking['customer_phone'] ?? '' ); ?>" required placeholder="<?php esc_attr_e( '+1 (555) 234-5678', 'dev-nahian-car' ); ?>">
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="license_number"><?php esc_html_e( 'Driver License Number', 'dev-nahian-car' ); ?></label>
						<input type="text" id="license_number" name="license_number" class="dnf-input" value="<?php echo esc_attr( $booking['license_number'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'DL-84920-USA', 'dev-nahian-car' ); ?>">
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="emergency_contact"><?php esc_html_e( 'Emergency Contact', 'dev-nahian-car' ); ?></label>
						<input type="text" id="emergency_contact" name="emergency_contact" class="dnf-input" value="<?php echo esc_attr( $booking['emergency_contact'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Contact Person / Phone', 'dev-nahian-car' ); ?>">
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="passenger_count"><?php esc_html_e( 'Passenger Capacity', 'dev-nahian-car' ); ?></label>
						<input type="number" id="passenger_count" name="passenger_count" class="dnf-input" value="<?php echo esc_attr( (string) ( $booking['passenger_count'] ?? 1 ) ); ?>" min="1" max="14">
					</div>
				</div>
			</div>

			<!-- Card 2: Vehicle & Logistics Schedule -->
			<div class="dnf-form-card">
				<div class="dnf-form-card-header">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
					<h3 class="dnf-form-card-title"><?php esc_html_e( '2. Asset Allocation & Schedule Window', 'dev-nahian-car' ); ?></h3>
				</div>

				<div class="dnf-form-grid">
					<div class="dnf-form-group col-span-2">
						<label class="dnf-label" for="car_id">
							<?php esc_html_e( 'Assign Vehicle Asset', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger);">*</span>
						</label>
						<div class="dnf-select-wrapper">
							<select id="car_id" name="car_id" class="dnf-select" required>
								<option value="0" data-price="0">-- <?php esc_html_e( 'Choose Vehicle Asset', 'dev-nahian-car' ); ?> --</option>
								<?php if ( ! empty( $available_cars ) ) : ?>
									<?php foreach ( $available_cars as $c ) : ?>
										<option value="<?php echo esc_attr( (string) $c['id'] ); ?>" data-price="<?php echo esc_attr( (string) $c['price'] ); ?>" <?php selected( (int) ( $booking['car_id'] ?? 0 ), (int) $c['id'] ); ?>>
											<?php echo esc_html( $c['name'] . ' (' . $currency . ' ' . number_format_i18n( (float) $c['price'], 2 ) . '/day)' ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="booking_status"><?php esc_html_e( 'Dispatch Status', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="booking_status" name="booking_status" class="dnf-select">
								<option value="active" <?php selected( $booking['booking_status'] ?? '', 'active' ); ?>><?php esc_html_e( 'Active • Dispatched', 'dev-nahian-car' ); ?></option>
								<option value="pending" <?php selected( $booking['booking_status'] ?? 'pending', 'pending' ); ?>><?php esc_html_e( 'Pending Authorization', 'dev-nahian-car' ); ?></option>
								<option value="completed" <?php selected( $booking['booking_status'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed • Returned', 'dev-nahian-car' ); ?></option>
								<option value="cancelled" <?php selected( $booking['booking_status'] ?? '', 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'dev-nahian-car' ); ?></option>
							</select>
						</div>
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="pickup_date"><?php esc_html_e( 'Pickup Date', 'dev-nahian-car' ); ?></label>
						<input type="date" id="pickup_date" name="pickup_date" class="dnf-input" value="<?php echo esc_attr( $booking['pickup_date'] ?? current_time( 'Y-m-d' ) ); ?>" required>
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="pickup_time"><?php esc_html_e( 'Pickup Time', 'dev-nahian-car' ); ?></label>
						<input type="time" id="pickup_time" name="pickup_time" class="dnf-input" value="<?php echo esc_attr( $booking['pickup_time'] ?? '10:00' ); ?>" required>
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="pickup_location"><?php esc_html_e( 'Pickup Hub / Location', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="pickup_location" name="pickup_location" class="dnf-select" required>
								<option value=""><?php esc_html_e( '-- Select Pickup Hub --', 'dev-nahian-car' ); ?></option>
								<?php foreach ( $location_options as $loc ) : ?>
									<option value="<?php echo esc_attr( $loc ); ?>" <?php selected( $booking['pickup_location'] ?? '', $loc ); ?>>
										<?php echo esc_html( $loc ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="return_date"><?php esc_html_e( 'Return Date', 'dev-nahian-car' ); ?></label>
						<input type="date" id="return_date" name="return_date" class="dnf-input" value="<?php echo esc_attr( $booking['return_date'] ?? '' ); ?>" required>
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="return_time"><?php esc_html_e( 'Return Time', 'dev-nahian-car' ); ?></label>
						<input type="time" id="return_time" name="return_time" class="dnf-input" value="<?php echo esc_attr( $booking['return_time'] ?? '10:00' ); ?>" required>
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="return_location"><?php esc_html_e( 'Return Hub / Location', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="return_location" name="return_location" class="dnf-select" required>
								<option value=""><?php esc_html_e( '-- Select Return Hub --', 'dev-nahian-car' ); ?></option>
								<?php foreach ( $location_options as $loc ) : ?>
									<option value="<?php echo esc_attr( $loc ); ?>" <?php selected( $booking['return_location'] ?? '', $loc ); ?>>
										<?php echo esc_html( $loc ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- Card 3: Settlement & Logistics Notes -->
			<div class="dnf-form-card">
				<div class="dnf-form-card-header">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
					<h3 class="dnf-form-card-title"><?php esc_html_e( '3. Settlement, Flight & Chauffeur Directives', 'dev-nahian-car' ); ?></h3>
				</div>

				<div class="dnf-form-grid">
					<div class="dnf-form-group">
						<label class="dnf-label" for="flight_number"><?php esc_html_e( 'Flight Identification', 'dev-nahian-car' ); ?></label>
						<input type="text" id="flight_number" name="flight_number" class="dnf-input" value="<?php echo esc_attr( $booking['flight_number'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. DL-1492', 'dev-nahian-car' ); ?>">
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="payment_method"><?php esc_html_e( 'Payment Gateway Rail', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="payment_method" name="payment_method" class="dnf-select">
								<?php foreach ( $settings_payment_methods as $pm_key => $pm_label ) : ?>
									<option value="<?php echo esc_attr( $pm_key ); ?>" <?php selected( $booking['payment_method'] ?? 'card', $pm_key ); ?>>
										<?php echo esc_html( $pm_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="dnf-form-group">
						<label class="dnf-label" for="total_amount">
							<?php
							printf(
								/* translators: %s: currency symbol/code */
								esc_html__( 'Total Settlement Amount (%s)', 'dev-nahian-car' ),
								esc_html( $currency )
							);
							?> <span style="color:var(--dnf-danger);">*</span>
						</label>
						<input type="number" step="0.01" id="total_amount" name="total_amount" class="dnf-input" value="<?php echo esc_attr( (string) ( $booking['total_amount'] ?? '0.00' ) ); ?>" required>
						<div id="dnfCalculatedNotice" class="dnf-quote-preview-pill" style="display:none;">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
							<span id="dnfCalculatedNoticeText">--</span>
						</div>
					</div>

					<div class="dnf-form-group col-span-full">
						<label class="dnf-label" for="customer_notes"><?php esc_html_e( 'VIP Logistics Notes & Special Instructions', 'dev-nahian-car' ); ?></label>
						<textarea id="customer_notes" name="customer_notes" class="dnf-textarea" placeholder="<?php esc_attr_e( 'Add chauffeur flight tracking, luggage space allocation, child seats, or special route instructions...', 'dev-nahian-car' ); ?>"><?php echo esc_textarea( $booking['customer_notes'] ?? '' ); ?></textarea>
					</div>
				</div>

				<div style="margin-top: 28px; display: flex; gap: 12px; align-items: center;">
					<button type="submit" name="<?php echo $is_edit ? 'dev_nahian_edit_booking_submit' : 'dev_nahian_add_booking_submit'; ?>" class="dnf-btn dnf-btn-primary">
						<?php echo $is_edit ? esc_html__( 'Save Dossier Changes', 'dev-nahian-car' ) : esc_html__( 'Generate & Dispatch Booking', 'dev-nahian-car' ); ?>
					</button>
					<a href="<?php echo esc_url( $all_url ); ?>" class="dnf-btn dnf-btn-secondary">
						<?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?>
					</a>
				</div>
			</div>
		</form>

		<!-- Dynamic Pricing & Car Selection Engine -->
		<script>
		(function() {
			'use strict';
			var carSelect      = document.getElementById('car_id');
			var carNameHidden  = document.getElementById('car_name');
			var pickupDate     = document.getElementById('pickup_date');
			var returnDate     = document.getElementById('return_date');
			var totalInput     = document.getElementById('total_amount');
			var noticePill     = document.getElementById('dnfCalculatedNotice');
			var noticeText     = document.getElementById('dnfCalculatedNoticeText');
			var currencySymbol = <?php echo wp_json_encode( $currency ); ?>;

			function updateCarMeta() {
				if (!carSelect || !carNameHidden) return;
				var selectedOpt = carSelect.options[carSelect.selectedIndex];
				if (selectedOpt && selectedOpt.value !== '0') {
					var rawText = selectedOpt.text.split(' (')[0];
					carNameHidden.value = rawText.trim();
				}
			}

			function calculateDaysAndPrice() {
				if (!carSelect || !pickupDate || !returnDate || !totalInput) return;
				var opt = carSelect.options[carSelect.selectedIndex];
				var dailyRate = parseFloat(opt.getAttribute('data-price')) || 0;

				if (dailyRate > 0 && pickupDate.value && returnDate.value) {
					var pDate = new Date(pickupDate.value);
					var rDate = new Date(returnDate.value);
					var diffTime = rDate - pDate;
					var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
					if (diffDays <= 0) diffDays = 1;

					var computedTotal = (diffDays * dailyRate).toFixed(2);

					if (parseFloat(totalInput.value) <= 0 || !totalInput.getAttribute('data-user-edited')) {
						totalInput.value = computedTotal;
					}

					if (noticePill && noticeText) {
						noticePill.style.display = 'inline-flex';
						noticeText.textContent = diffDays + ' Day' + (diffDays > 1 ? 's' : '') + ' @ ' + currencySymbol + ' ' + dailyRate.toFixed(2) + '/day = ' + currencySymbol + ' ' + computedTotal;
					}
				} else if (noticePill) {
					noticePill.style.display = 'none';
				}
			}

			if (carSelect) {
				carSelect.addEventListener('change', function() {
					updateCarMeta();
					calculateDaysAndPrice();
				});
			}

			if (pickupDate) pickupDate.addEventListener('change', calculateDaysAndPrice);
			if (returnDate) returnDate.addEventListener('change', calculateDaysAndPrice);

			if (totalInput) {
				totalInput.addEventListener('input', function() {
					totalInput.setAttribute('data-user-edited', 'true');
				});
			}

			// Run on initial load if editing
			calculateDaysAndPrice();
		})();
		</script>

	<?php
	// -------------------------------------------------------------------------
	// Default Mode: DataTables Manifest Ledger
	// -------------------------------------------------------------------------
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bookings = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC", ARRAY_A );
		?>
		<div class="dnf-table-container">
			<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border:none;">
				<thead>
					<tr>
						<th style="width: 110px;"><?php esc_html_e( 'Code', 'dev-nahian-car' ); ?></th>
						<th><?php esc_html_e( 'Customer', 'dev-nahian-car' ); ?></th>
						<th><?php esc_html_e( 'Contact Phone', 'dev-nahian-car' ); ?></th>
						<th><?php esc_html_e( 'Fleet Asset', 'dev-nahian-car' ); ?></th>
						<th><?php esc_html_e( 'Pickup Window', 'dev-nahian-car' ); ?></th>
						<th><?php esc_html_e( 'Return Window', 'dev-nahian-car' ); ?></th>
						<th><?php esc_html_e( 'Total Amount', 'dev-nahian-car' ); ?></th>
						<th style="width: 120px;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
						<th style="text-align: right; width: 150px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $bookings ) ) : ?>
						<?php
						foreach ( $bookings as $b ) :
							$status_key = sanitize_key( $b['booking_status'] ?? 'pending' );
							$view_url   = add_query_arg( array( 'action' => 'view', 'id' => $b['id'] ), $base );
							$edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $b['id'], 'subtab' => 'add' ), $base );
							$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $b['id'] ), $base ), 'dev_nahian_delete_booking_' . $b['id'] );

							$pickup_ts = ! empty( $b['pickup_date'] ) ? strtotime( (string) $b['pickup_date'] ) : false;
							$return_ts = ! empty( $b['return_date'] ) ? strtotime( (string) $b['return_date'] ) : false;
							?>
							<tr>
								<td><code class="dnf-code">#<?php echo esc_html( (string) $b['booking_code'] ); ?></code></td>
								<td>
									<strong><?php echo esc_html( (string) $b['customer_name'] ); ?></strong><br>
									<span style="font-size:11.5px; color:var(--dnf-text-secondary);"><?php echo esc_html( (string) $b['customer_email'] ); ?></span>
								</td>
								<td><?php echo esc_html( (string) $b['customer_phone'] ); ?></td>
								<td><strong><?php echo esc_html( ! empty( $b['car_name'] ) ? $b['car_name'] : __( 'Fleet Asset', 'dev-nahian-car' ) ); ?></strong></td>
								<td>
									<?php echo esc_html( $pickup_ts ? wp_date( 'M j, Y', $pickup_ts ) : (string) $b['pickup_date'] ); ?><br>
									<span style="font-size:11px; color:var(--dnf-text-secondary);"><?php echo esc_html( (string) $b['pickup_time'] ); ?></span>
								</td>
								<td>
									<?php echo esc_html( $return_ts ? wp_date( 'M j, Y', $return_ts ) : (string) $b['return_date'] ); ?><br>
									<span style="font-size:11px; color:var(--dnf-text-secondary);"><?php echo esc_html( (string) $b['return_time'] ); ?></span>
								</td>
								<td><strong style="color:var(--dnf-text-primary); font-feature-settings:'tnum';"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $b['total_amount'], 2 ) ); ?></strong></td>
								<td>
									<span class="dnf-status-pill <?php echo esc_attr( $status_key ); ?>">
										&bull; <?php echo esc_html( ucfirst( $status_key ) ); ?>
									</span>
								</td>
								<td style="text-align: right;">
									<div class="dnf-actions-group">
										<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm" title="<?php esc_attr_e( 'View Booking Dossier', 'dev-nahian-car' ); ?>">
											<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
										<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm" title="<?php esc_attr_e( 'Edit Booking', 'dev-nahian-car' ); ?>">
											<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
										<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn dnf-btn-danger dnf-btn-sm" title="<?php esc_attr_e( 'Delete Booking Record', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this booking record permanently?', 'dev-nahian-car' ); ?>');">
											<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="9" style="text-align:center; padding:36px; color:var(--dnf-text-tertiary);">
								<?php esc_html_e( 'No reservation manifests recorded in database yet.', 'dev-nahian-car' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php } ?>

</div>
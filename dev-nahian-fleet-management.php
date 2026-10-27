<?php
/**
 * Plugin Name:       Dev Nahian Fleet Management System
 * Plugin URI:        https://example.com/dev-nahian-fleet
 * Description:       Executive vehicle dispatch, booking, telematics, maintenance, inspections, and fleet analytics terminal.
 * Version:           1.1.0
 * Author:            Dev Nahian
 * Author URI:        https://example.com
 * Text Domain:       dev-nahian-car
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package           Dev_Nahian_Fleet_Manager
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Plugin Constants
// -----------------------------------------------------------------------------
define( 'DEV_NAHIAN_FLEET_VERSION', '1.1.0' );
define( 'DEV_NAHIAN_DB_VERSION', '1.1.0' );
define( 'DEV_NAHIAN_FLEET_DIR', plugin_dir_path( __FILE__ ) );
define( 'DEV_NAHIAN_FLEET_URL', plugin_dir_url( __FILE__ ) );

// -----------------------------------------------------------------------------
// 1. Database Schema & Lifecycle Hooks
// -----------------------------------------------------------------------------
require_once DEV_NAHIAN_FLEET_DIR . 'includes/database.php';

register_activation_hook( __FILE__, 'dev_nahian_fleet_activate' );

/**
 * Handle activation routine.
 *
 * @return void
 */
function dev_nahian_fleet_activate(): void {
	if ( function_exists( 'dev_nahian_install_database' ) ) {
		dev_nahian_install_database();
	}
	update_option( 'dev_nahian_db_version', DEV_NAHIAN_DB_VERSION );
}

/**
 * Check for DB schema migrations on upgrade.
 *
 * @return void
 */
function dev_nahian_check_db_version(): void {
	if ( get_option( 'dev_nahian_db_version' ) !== DEV_NAHIAN_DB_VERSION ) {
		if ( function_exists( 'dev_nahian_install_database' ) ) {
			dev_nahian_install_database();
		}
		update_option( 'dev_nahian_db_version', DEV_NAHIAN_DB_VERSION );
	}
}
add_action( 'plugins_loaded', 'dev_nahian_check_db_version' );

// -----------------------------------------------------------------------------
// 2. Menu Registration
// -----------------------------------------------------------------------------
/**
 * Register top-level admin menu.
 *
 * @return void
 */
function dev_nahian_register_fleet_menu(): void {
	add_menu_page(
		__( 'Fleet Management', 'dev-nahian-car' ),
		__( 'Fleet Management', 'dev-nahian-car' ),
		'manage_options',
		'dev-nahian-fleet-manager',
		'dev_nahian_render_fleet_page',
		'dashicons-car',
		26
	);
}
add_action( 'admin_menu', 'dev_nahian_register_fleet_menu' );

// -----------------------------------------------------------------------------
// 3. Asset Management (CSS Tokens, Components & DataTables)
// -----------------------------------------------------------------------------
/**
 * Enqueue scripts and modern CSS system for inputs, switches, selects, and buttons.
 *
 * @param string $hook Page hook suffix.
 * @return void
 */
function dev_nahian_enqueue_fleet_assets( string $hook ): void {
	if ( false === strpos( $hook, 'dev-nahian-fleet-manager' ) ) {
		return;
	}

	// Register core layout and modern UI component CSS.
	wp_register_style( 'dev-nahian-fleet-core', false, array(), DEV_NAHIAN_FLEET_VERSION );
	wp_enqueue_style( 'dev-nahian-fleet-core' );

	$custom_css = '
		:root {
			--dnf-bg-card: #ffffff;
			--dnf-bg-subtle: #f8fafc;
			--dnf-border: #e2e8f0;
			--dnf-border-hover: #cbd5e1;
			--dnf-border-focus: #0f172a;
			--dnf-text-primary: #0f172a;
			--dnf-text-secondary: #475569;
			--dnf-text-tertiary: #94a3b8;
			--dnf-primary: #0f172a;
			--dnf-primary-hover: #1e293b;
			--dnf-accent-gold: #b88e1e;
			--dnf-accent-gold-bg: #fefce8;
			--dnf-accent-teal: #0d9488;
			--dnf-danger: #dc2626;
			--dnf-danger-bg: #fef2f2;
			--dnf-danger-border: #fecaca;
			--dnf-success: #16a34a;
			--dnf-radius-sm: 6px;
			--dnf-radius-md: 8px;
			--dnf-radius-lg: 12px;
			--dnf-radius-pill: 9999px;
			--dnf-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
			--dnf-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
			--dnf-focus-ring: 0 0 0 3px rgba(15, 23, 42, 0.12);
		}

		.dev-nahian-wrap {
			max-width: 1440px;
			margin: 20px 20px 0 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			color: var(--dnf-text-primary);
		}
		.dev-nahian-layout {
			display: grid;
			grid-template-columns: 240px 1fr;
			gap: 24px;
			align-items: start;
		}
		@media (max-width: 960px) {
			.dev-nahian-layout { grid-template-columns: 1fr; }
		}

		/* Sidebar & Navigation */
		.dev-nahian-sidebar {
			background: var(--dnf-bg-card);
			border: 1px solid var(--dnf-border);
			border-radius: var(--dnf-radius-lg);
			padding: 12px;
			box-shadow: var(--dnf-shadow-sm);
		}
		.dev-nahian-nav {
			list-style: none;
			margin: 0;
			padding: 0;
			display: flex;
			flex-direction: column;
			gap: 4px;
		}
		.dev-nahian-nav-item a {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 10px 14px;
			border-radius: var(--dnf-radius-md);
			text-decoration: none;
			color: var(--dnf-text-secondary);
			font-size: 13.5px;
			font-weight: 600;
			transition: all 0.15s ease-in-out;
		}
		.dev-nahian-nav-item a:hover {
			background: var(--dnf-bg-subtle);
			color: var(--dnf-text-primary);
		}
		.dev-nahian-nav-item a.active {
			background: var(--dnf-primary);
			color: #ffffff;
			box-shadow: var(--dnf-shadow-md);
		}
		.dev-nahian-nav-item a.active svg {
			color: var(--dnf-accent-gold);
		}
		.dev-nahian-badge {
			font-size: 10px;
			font-weight: 700;
			padding: 2px 7px;
			border-radius: var(--dnf-radius-pill);
			background: #f1f5f9;
			color: var(--dnf-text-secondary);
		}
		.dev-nahian-nav-item a.active .dev-nahian-badge {
			background: var(--dnf-accent-gold);
			color: var(--dnf-primary);
		}
		.dev-nahian-content {
			background: var(--dnf-bg-card);
			border: 1px solid var(--dnf-border);
			border-radius: var(--dnf-radius-lg);
			padding: 32px;
			box-shadow: var(--dnf-shadow-sm);
			min-height: 560px;
		}

		/* =====================================================================
		   AWESOME UI SYSTEM: Inputs, Selects, Switches, Buttons
		   ===================================================================== */

		/* Form Row & Groups */
		.dnf-form-group {
			margin-bottom: 20px;
		}
		.dnf-label {
			display: block;
			font-size: 13px;
			font-weight: 600;
			color: var(--dnf-text-primary);
			margin-bottom: 6px;
		}
		.dnf-help-text {
			font-size: 12px;
			color: var(--dnf-text-secondary);
			margin-top: 4px;
		}

		/* Inputs & Textareas */
		.dnf-input,
		.dnf-textarea {
			width: 100%;
			height: 42px;
			padding: 0 14px;
			background: #ffffff;
			border: 1px solid var(--dnf-border);
			border-radius: var(--dnf-radius-md);
			font-size: 14px;
			color: var(--dnf-text-primary);
			box-shadow: var(--dnf-shadow-sm);
			transition: border-color 0.15s ease, box-shadow 0.15s ease;
			box-sizing: border-box;
		}
		.dnf-textarea {
			height: auto;
			min-height: 100px;
			padding: 10px 14px;
			resize: vertical;
		}
		.dnf-input:hover,
		.dnf-textarea:hover {
			border-color: var(--dnf-border-hover);
		}
		.dnf-input:focus,
		.dnf-textarea:focus {
			outline: none;
			border-color: var(--dnf-border-focus);
			box-shadow: var(--dnf-focus-ring);
		}
		.dnf-input:disabled {
			background: var(--dnf-bg-subtle);
			cursor: not-allowed;
			opacity: 0.7;
		}

		/* Custom Select */
		.dnf-select-wrapper {
			position: relative;
			display: block;
			width: 100%;
		}
		.dnf-select {
			appearance: none;
			-webkit-appearance: none;
			width: 100%;
			height: 42px;
			padding: 0 38px 0 14px;
			background: #ffffff;
			border: 1px solid var(--dnf-border);
			border-radius: var(--dnf-radius-md);
			font-size: 14px;
			color: var(--dnf-text-primary);
			box-shadow: var(--dnf-shadow-sm);
			cursor: pointer;
			transition: border-color 0.15s ease, box-shadow 0.15s ease;
			box-sizing: border-box;
		}
		.dnf-select:hover {
			border-color: var(--dnf-border-hover);
		}
		.dnf-select:focus {
			outline: none;
			border-color: var(--dnf-border-focus);
			box-shadow: var(--dnf-focus-ring);
		}
		.dnf-select-wrapper::after {
			content: "";
			position: absolute;
			right: 14px;
			top: 50%;
			transform: translateY(-50%);
			width: 9px;
			height: 6px;
			background-color: var(--dnf-text-tertiary);
			clip-path: polygon(100% 0%, 0 0%, 50% 100%);
			pointer-events: none;
		}

		/* iOS-style Toggle Switch */
		.dnf-switch-label {
			display: inline-flex;
			align-items: center;
			cursor: pointer;
			user-select: none;
			gap: 12px;
		}
		.dnf-switch {
			position: relative;
			display: inline-block;
			width: 44px;
			height: 24px;
			flex-shrink: 0;
		}
		.dnf-switch input {
			opacity: 0;
			width: 0;
			height: 0;
			position: absolute;
		}
		.dnf-slider {
			position: absolute;
			cursor: pointer;
			inset: 0;
			background-color: #cbd5e1;
			transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
			border-radius: var(--dnf-radius-pill);
		}
		.dnf-slider:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 3px;
			bottom: 3px;
			background-color: #ffffff;
			transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
			border-radius: 50%;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
		}
		.dnf-switch input:checked + .dnf-slider {
			background-color: var(--dnf-primary);
		}
		.dnf-switch input:focus + .dnf-slider {
			box-shadow: var(--dnf-focus-ring);
		}
		.dnf-switch input:checked + .dnf-slider:before {
			transform: translateX(20px);
		}

		/* Modern Buttons */
		.dnf-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			height: 38px;
			padding: 0 16px;
			font-size: 13px;
			font-weight: 600;
			border-radius: var(--dnf-radius-md);
			cursor: pointer;
			text-decoration: none;
			border: 1px solid transparent;
			transition: all 0.15s ease-in-out;
			line-height: 1;
		}
		.dnf-btn-primary {
			background: var(--dnf-primary);
			color: #ffffff;
			border-color: var(--dnf-primary);
		}
		.dnf-btn-primary:hover {
			background: var(--dnf-primary-hover);
			color: #ffffff;
			box-shadow: var(--dnf-shadow-md);
		}
		.dnf-btn-secondary {
			background: #ffffff;
			color: var(--dnf-text-secondary);
			border-color: var(--dnf-border);
		}
		.dnf-btn-secondary:hover {
			background: var(--dnf-bg-subtle);
			color: var(--dnf-text-primary);
			border-color: var(--dnf-border-hover);
		}
		.dnf-btn-danger {
			background: var(--dnf-danger-bg);
			color: var(--dnf-danger);
			border-color: var(--dnf-danger-border);
		}
		.dnf-btn-danger:hover {
			background: #fee2e2;
			color: #b91c1c;
		}
		.dnf-btn-sm {
			height: 30px;
			padding: 0 10px;
			font-size: 12px;
			border-radius: var(--dnf-radius-sm);
		}
	';

	wp_add_inline_style( 'dev-nahian-fleet-core', $custom_css );

	// DataTables Assets.
	wp_enqueue_style(
		'dev-nahian-datatables-css',
		'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
		array(),
		'1.13.6'
	);

	wp_enqueue_script(
		'dev-nahian-datatables-js',
		'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
		array( 'jquery' ),
		'1.13.6',
		true
	);

	$l10n_data = array(
		'searchPlaceholder' => esc_html__( 'Search records...', 'dev-nahian-car' ),
		'emptyTable'        => esc_html__( 'No records found in database.', 'dev-nahian-car' ),
	);

	wp_add_inline_script(
		'dev-nahian-datatables-js',
		'jQuery(document).ready(function($) {
			$.fn.dataTable.ext.errorMode = "none";
			$(".dev-nahian-datatable").DataTable({
				pageLength: 10,
				responsive: true,
				language: {
					search: "_INPUT_",
					searchPlaceholder: "' . esc_js( $l10n_data['searchPlaceholder'] ) . '",
					emptyTable: "' . esc_js( $l10n_data['emptyTable'] ) . '"
				}
			});
		});'
	);
}
add_action( 'admin_enqueue_scripts', 'dev_nahian_enqueue_fleet_assets' );

// -----------------------------------------------------------------------------
// 4. Dedicated CSV Export Handler
// -----------------------------------------------------------------------------
/**
 * Export Fleet Bookings to CSV with CSV Injection Safeguard.
 *
 * @return void
 */
function dev_nahian_export_bookings_csv(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
	}

	check_admin_referer( 'dev_nahian_csv_nonce' );

	global $wpdb;
	$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC", ARRAY_A );

	if ( ob_get_level() ) {
		ob_end_clean();
	}

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=fleet-bookings-' . gmdate( 'Y-m-d' ) . '.csv' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$out = fopen( 'php://output', 'w' );
	if ( false === $out ) {
		exit;
	}

	fputcsv( $out, array( 'Booking ID', 'Customer', 'Email', 'Phone', 'Vehicle', 'Pickup Location', 'Dates', 'Amount', 'Status' ) );

	if ( ! empty( $rows ) ) {
		// Helper to sanitize formulas in CSV export.
		$escape_formula = static function ( $val ) {
			if ( is_string( $val ) && isset( $val[0] ) && in_array( $val[0], array( '=', '+', '-', '@' ), true ) ) {
				return "'" . $val;
			}
			return $val;
		};

		foreach ( $rows as $r ) {
			fputcsv(
				$out,
				array(
					$escape_formula( $r['booking_code'] ?? '' ),
					$escape_formula( $r['customer_name'] ?? '' ),
					$escape_formula( $r['customer_email'] ?? '' ),
					$escape_formula( $r['customer_phone'] ?? '' ),
					$escape_formula( $r['car_name'] ?? '' ),
					$escape_formula( $r['pickup_location'] ?? '' ),
					( $r['pickup_date'] ?? '' ) . ' to ' . ( $r['return_date'] ?? '' ),
					'$' . number_format( (float) ( $r['total_amount'] ?? 0 ), 2 ),
					$escape_formula( $r['booking_status'] ?? '' ),
				)
			);
		}
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
	fclose( $out );
	exit;
}
add_action( 'admin_post_dev_nahian_export_csv', 'dev_nahian_export_bookings_csv' );

// -----------------------------------------------------------------------------
// 5. Settings Save Handler
// -----------------------------------------------------------------------------
/**
 * Handle Fleet settings submissions.
 *
 * @return void
 */
function dev_nahian_handle_settings_save(): void {
	if ( ! isset( $_POST['dev_nahian_save_settings'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
	}

	check_admin_referer( 'dev_nahian_settings_verify', 'dev_nahian_settings_nonce' );

	$currency       = isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : 'USD';
	$company_name   = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
	$company_email  = isset( $_POST['company_email'] ) ? sanitize_email( wp_unslash( $_POST['company_email'] ) ) : '';
	$company_phone  = isset( $_POST['company_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['company_phone'] ) ) : '';
	$auto_key       = isset( $_POST['auto_key'] ) ? 1 : 0;
	$no_deposit     = isset( $_POST['no_deposit'] ) ? 1 : 0;
	$deposit_amount = isset( $_POST['deposit_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['deposit_amount'] ) ) : 0.00;
	$min_driver_age = isset( $_POST['min_driver_age'] ) ? absint( wp_unslash( $_POST['min_driver_age'] ) ) : 21;
	$api_key        = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
	$webhook_url    = isset( $_POST['webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['webhook_url'] ) ) : '';

	update_option( 'dev_nahian_currency', $currency );
	update_option( 'dev_nahian_company_name', $company_name );
	update_option( 'dev_nahian_company_email', $company_email );
	update_option( 'dev_nahian_company_phone', $company_phone );
	update_option( 'dev_nahian_auto_key', $auto_key );
	update_option( 'dev_nahian_no_deposit', $no_deposit );
	update_option( 'dev_nahian_deposit_amount', number_format( $deposit_amount, 2, '.', '' ) );
	update_option( 'dev_nahian_min_driver_age', $min_driver_age );
	update_option( 'dev_nahian_api_key', $api_key );
	update_option( 'dev_nahian_webhook_url', $webhook_url );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'  => 'dev-nahian-fleet-manager',
				'tab'   => 'settings',
				'saved' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'dev_nahian_handle_settings_save' );

// -----------------------------------------------------------------------------
// 6. SVG Icon Helper
// -----------------------------------------------------------------------------
/**
 * Render raw SVG icon safely.
 *
 * @param string $name Icon name identifier.
 * @return string Safe SVG markup.
 */
function dev_nahian_get_icon( string $name ): string {
	$icons = array(
		'view'   => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
		'edit'   => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>',
		'delete' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>',
		'add'    => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
		'back'   => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>',
	);

	return $icons[ $name ] ?? '';
}

// -----------------------------------------------------------------------------
// 7. Master Router & Layout Renderer
// -----------------------------------------------------------------------------
/**
 * Render admin fleet view.
 *
 * @return void
 */
function dev_nahian_render_fleet_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dev-nahian-car' ) );
	}

	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';

	$nav_items = array(
		'dashboard'   => array(
			'label' => __( 'Dashboard', 'dev-nahian-car' ),
			'badge' => 'LIVE',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>',
		),
		'booking'     => array(
			'label' => __( 'Booking', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
		),
		'cars'        => array(
			'label' => __( 'Cars', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14v-5l-2-6H7L5 12v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>',
		),
		'categories'  => array(
			'label' => __( 'Categories', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
		),
		'extras'      => array(
			'label' => __( 'Extras', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
		),
		'customers'   => array(
			'label' => __( 'Customers', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
		),
		'staff'       => array(
			'label' => __( 'Staff', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
		),
		'maintenance' => array(
			'label' => __( 'Maintenance', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		),
		'inspections' => array(
			'label' => __( 'Inspections', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
		),
		'promos'      => array(
			'label' => __( 'Promo Codes', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>',
		),
		'finances'    => array(
			'label' => __( 'Income & Expense', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
		),
		'reports'     => array(
			'label' => __( 'Reports', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
		),
		'settings'    => array(
			'label' => __( 'Settings', 'dev-nahian-car' ),
			'badge' => '',
			'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
		),
	);

	if ( ! array_key_exists( $tab, $nav_items ) ) {
		$tab = 'dashboard';
	}
	?>
	<div class="wrap dev-nahian-wrap">
		<div class="dev-nahian-layout">
			<aside class="dev-nahian-sidebar">
				<ul class="dev-nahian-nav">
					<?php foreach ( $nav_items as $item_key => $item ) :
						$url    = add_query_arg(
							array(
								'page' => 'dev-nahian-fleet-manager',
								'tab'  => $item_key,
							),
							admin_url( 'admin.php' )
						);
						$active = ( $tab === $item_key ) ? 'active' : '';
						?>
						<li class="dev-nahian-nav-item">
							<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $active ); ?>">
								<span style="display: flex; align-items: center; gap: 10px;">
									<?php echo $item['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php echo esc_html( $item['label'] ); ?></span>
								</span>
								<?php if ( ! empty( $item['badge'] ) ) : ?>
									<span class="dev-nahian-badge"><?php echo esc_html( $item['badge'] ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</aside>

			<main class="dev-nahian-content">
				<?php
				$view_file = DEV_NAHIAN_FLEET_DIR . 'views/' . sanitize_file_name( $tab ) . '.php';
				if ( file_exists( $view_file ) ) {
					include $view_file;
				} else {
					$default_file = DEV_NAHIAN_FLEET_DIR . 'views/dashboard.php';
					if ( file_exists( $default_file ) ) {
						include $default_file;
					}
				}
				?>
			</main>
		</div>
	</div>
	<?php
}
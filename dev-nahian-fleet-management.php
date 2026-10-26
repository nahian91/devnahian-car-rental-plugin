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
 * @package Dev_Nahian_Fleet_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin Constants
define( 'DEV_NAHIAN_FLEET_VERSION', '1.1.0' );
define( 'DEV_NAHIAN_DB_VERSION', '1.1.0' );
define( 'DEV_NAHIAN_FLEET_DIR', plugin_dir_path( __FILE__ ) );
define( 'DEV_NAHIAN_FLEET_URL', plugin_dir_url( __FILE__ ) );

/**
 * 1. Load Separate Database Schema & Migration Routine
 */
require_once DEV_NAHIAN_FLEET_DIR . 'includes/database.php';
register_activation_hook( __FILE__, 'dev_nahian_install_database' );

/**
 * Fallback Database Version Check
 */
function dev_nahian_check_db_installed() {
    if ( get_option( 'dev_nahian_db_version' ) !== DEV_NAHIAN_DB_VERSION ) {
        dev_nahian_install_database();
    }
}
add_action( 'admin_init', 'dev_nahian_check_db_installed' );

/**
 * 2. Admin Menu Registration
 */
function dev_nahian_register_fleet_menu() {
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

/**
 * 3. Enqueue Admin Scripts and Styles (DataTables via CDN with Warning Suppression)
 */
function dev_nahian_enqueue_fleet_assets( $hook ) {
    if ( false === strpos( $hook, 'dev-nahian-fleet-manager' ) ) {
        return;
    }

    // DataTables CSS CDN
    wp_enqueue_style(
        'dev-nahian-datatables-css',
        'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
        array(),
        '1.13.6'
    );

    // DataTables JS CDN
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
            // Completely suppress DataTables warnings & alerts (e.g., column count mismatches when tables are empty)
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

/**
 * 4. Dedicated CSV Export Handler via admin_post
 */
function dev_nahian_export_bookings_csv() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
    }

    check_admin_referer( 'dev_nahian_csv_nonce' );

    global $wpdb;
    $table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
    $rows           = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC", ARRAY_A );

    if ( ob_get_level() ) {
        ob_end_clean();
    }

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=fleet-bookings-' . gmdate( 'Y-m-d' ) . '.csv' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'Booking ID', 'Customer', 'Email', 'Phone', 'Vehicle', 'Pickup Location', 'Dates', 'Amount', 'Status' ) );

    if ( ! empty( $rows ) ) {
        foreach ( $rows as $r ) {
            fputcsv( $out, array(
                $r['booking_code'],
                $r['customer_name'],
                $r['customer_email'],
                $r['customer_phone'],
                $r['car_name'],
                $r['pickup_location'],
                $r['pickup_date'] . ' to ' . $r['return_date'],
                '$' . number_format( (float) $r['total_amount'], 2 ),
                $r['booking_status'],
            ) );
        }
    }
    fclose( $out );
    exit;
}
add_action( 'admin_post_dev_nahian_export_csv', 'dev_nahian_export_bookings_csv' );

/**
 * 5. Handle Settings Form Submissions
 */
function dev_nahian_handle_settings_save() {
    if ( ! isset( $_POST['dev_nahian_save_settings'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
    }

    check_admin_referer( 'dev_nahian_settings_verify', 'dev_nahian_settings_nonce' );

    update_option( 'dev_nahian_currency', sanitize_text_field( $_POST['currency'] ?? 'USD' ) );
    update_option( 'dev_nahian_company_name', sanitize_text_field( $_POST['company_name'] ?? '' ) );
    update_option( 'dev_nahian_company_email', sanitize_email( $_POST['company_email'] ?? '' ) );
    update_option( 'dev_nahian_company_phone', sanitize_text_field( $_POST['company_phone'] ?? '' ) );
    update_option( 'dev_nahian_auto_key', isset( $_POST['auto_key'] ) ? 1 : 0 );
    update_option( 'dev_nahian_no_deposit', isset( $_POST['no_deposit'] ) ? 1 : 0 );

    $deposit_val = isset( $_POST['deposit_amount'] ) ? (float) sanitize_text_field( $_POST['deposit_amount'] ) : 0.00;
    update_option( 'dev_nahian_deposit_amount', number_format( $deposit_val, 2, '.', '' ) );

    update_option( 'dev_nahian_min_driver_age', absint( $_POST['min_driver_age'] ?? 21 ) );
    update_option( 'dev_nahian_api_key', sanitize_text_field( $_POST['api_key'] ?? '' ) );
    update_option( 'dev_nahian_webhook_url', esc_url_raw( $_POST['webhook_url'] ?? '' ) );

    wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'settings', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
    exit;
}
add_action( 'admin_init', 'dev_nahian_handle_settings_save' );

/**
 * 6. Global SVG Icon Helper
 *
 * @param string $name Name of icon.
 * @return string HTML SVG element.
 */
function dev_nahian_get_icon( $name ) {
    switch ( $name ) {
        case 'view':
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        case 'edit':
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>';
        case 'delete':
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
        case 'add':
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
        case 'back':
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>';
        default:
            return '';
    }
}

/**
 * 7. Master Router Layout & Centralized Tab Styles
 */
function dev_nahian_render_fleet_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dev-nahian-car' ) );
    }

    $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

    // Strict tab whitelist
    $nav_items = array(
        'dashboard'   => array(
            'label' => __( 'Dashboard', 'dev-nahian-car' ),
            'badge' => 'LIVE',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>'
        ),
        'booking'     => array(
            'label' => __( 'Booking', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'
        ),
        'cars'        => array(
            'label' => __( 'Cars', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14v-5l-2-6H7L5 12v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>'
        ),
        'categories'  => array(
            'label' => __( 'Categories', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>'
        ),
        'extras'      => array(
            'label' => __( 'Extras', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'
        ),
        'customers'   => array(
            'label' => __( 'Customers', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'
        ),
        'staff'       => array(
            'label' => __( 'Staff', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>'
        ),
        'maintenance' => array(
            'label' => __( 'Maintenance', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>'
        ),
        'inspections' => array(
            'label' => __( 'Inspections', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
        ),
        'promos'      => array(
            'label' => __( 'Promo Codes', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>'
        ),
        'finances'    => array(
            'label' => __( 'Income & Expense', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'
        ),
        'reports'     => array(
            'label' => __( 'Reports', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>'
        ),
        'settings'    => array(
            'label' => __( 'Settings', 'dev-nahian-car' ),
            'badge' => '',
            'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>'
        ),
    );

    // Fall back to dashboard if key does not exist
    if ( ! array_key_exists( $tab, $nav_items ) ) {
        $tab = 'dashboard';
    }
    ?>

    <style id="dev-nahian-admin-central-styles">
        /* Master Design Tokens */
        :root {
            --dev-nahian-bg-card: #ffffff;
            --dev-nahian-border-color: #e2e8f0;
            --dev-nahian-border-subtle: #f1f5f9;
            --dev-nahian-text-primary: #0f172a;
            --dev-nahian-text-secondary: #475569;
            --dev-nahian-text-tertiary: #94a3b8;
            --dev-nahian-gold: #b88e1e;
            --dev-nahian-gold-surface: #fefce8;
            --dev-nahian-gold-border: #fef08a;
            --dev-nahian-teal: #0d9488;
            --dev-nahian-teal-surface: #f0fdfa;
            --dev-nahian-teal-border: #ccfbf1;
            --dev-nahian-blue: #2563eb;
            --dev-nahian-blue-surface: #eff6ff;
            --dev-nahian-blue-border: #dbeafe;
            --dev-nahian-green: #16a34a;
            --dev-nahian-green-surface: #f0fdf4;
            --dev-nahian-green-border: #bbf7d0;
            --dev-nahian-radius-lg: 12px;
            --dev-nahian-radius-md: 8px;
            --dev-nahian-radius-sm: 6px;
            --dev-nahian-radius-pill: 9999px;
        }

        /* Base Shell & Navigation */
        .dev-nahian-wrap {
            max-width: 1400px;
            margin: 20px 20px 0 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--dev-nahian-text-primary);
        }
        .dev-nahian-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .dev-nahian-layout {
                grid-template-columns: 1fr;
            }
        }
        .dev-nahian-sidebar {
            background: #ffffff;
            border: 1px solid var(--dev-nahian-border-color);
            border-radius: var(--dev-nahian-radius-lg);
            padding: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
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
            border-radius: var(--dev-nahian-radius-md);
            text-decoration: none;
            color: var(--dev-nahian-text-secondary);
            font-size: 13.5px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .dev-nahian-nav-item a svg {
            color: var(--dev-nahian-text-tertiary);
            flex-shrink: 0;
        }
        .dev-nahian-nav-item a:hover {
            background: #f8fafc;
            color: var(--dev-nahian-text-primary);
        }
        .dev-nahian-nav-item a:hover svg {
            color: var(--dev-nahian-text-primary);
        }
        .dev-nahian-nav-item a.active {
            background: #0f172a;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
        }
        .dev-nahian-nav-item a.active svg {
            color: var(--dev-nahian-gold);
        }
        .dev-nahian-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: var(--dev-nahian-radius-pill);
            background: #f1f5f9;
            color: #475569;
        }
        .dev-nahian-nav-item a.active .dev-nahian-badge {
            background: var(--dev-nahian-gold);
            color: #0f172a;
        }
        .dev-nahian-content {
            background: #ffffff;
            border: 1px solid var(--dev-nahian-border-color);
            border-radius: var(--dev-nahian-radius-lg);
            padding: 28px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            min-height: 520px;
        }

        /* Action Buttons */
        .dev-nahian-btn-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .dev-nahian-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: var(--dev-nahian-radius-sm);
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .dev-nahian-btn-add {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
            padding: 7px 14px;
            font-size: 13px;
        }
        .dev-nahian-btn-add:hover {
            background: #1e293b;
            color: #ffffff;
        }
        .dev-nahian-btn-view {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }
        .dev-nahian-btn-view:hover {
            background: #dbeafe;
            color: #1e40af;
        }
        .dev-nahian-btn-edit {
            background: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }
        .dev-nahian-btn-edit:hover {
            background: #fef3c7;
            color: #92400e;
        }
        .dev-nahian-btn-delete {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .dev-nahian-btn-delete:hover {
            background: #fee2e2;
            color: #991b1b;
        }
        .dev-nahian-btn-back {
            background: #f8fafc;
            color: #475569;
            border-color: #cbd5e1;
            padding: 6px 12px;
        }
        .dev-nahian-btn-back:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* DataTables Styling */
        table.dataTable.no-footer {
            border-bottom: 1px solid var(--dev-nahian-border-color) !important;
            margin-top: 14px !important;
            margin-bottom: 14px !important;
        }
        table.dataTable thead th {
            background-color: #f8fafc;
            color: #334155;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            border-bottom: 1px solid var(--dev-nahian-border-color) !important;
            padding: 10px 14px;
        }
        table.dataTable tbody td {
            padding: 12px 14px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #cbd5e1;
            border-radius: var(--dev-nahian-radius-sm);
            padding: 6px 10px;
            margin-left: 6px;
            font-size: 13px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0f172a !important;
            color: #fff !important;
            border: 1px solid #0f172a !important;
            border-radius: var(--dev-nahian-radius-sm);
        }
    </style>

    <div class="wrap dev-nahian-wrap">
        <div class="dev-nahian-layout">
            <aside class="dev-nahian-sidebar">
                <ul class="dev-nahian-nav">
                    <?php foreach ( $nav_items as $item_key => $item ) : 
                        $url   = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => $item_key ), admin_url( 'admin.php' ) );
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
                $view_file = DEV_NAHIAN_FLEET_DIR . 'views/' . $tab . '.php';
                if ( file_exists( $view_file ) ) {
                    include $view_file;
                } else {
                    $default_file = DEV_NAHIAN_FLEET_DIR . 'views/tab-dashboard.php';
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
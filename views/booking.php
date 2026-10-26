<?php
/**
 * Admin View Tab: Bookings Management (Master Router)
 * Pure orchestrator file containing only request routing and include statements.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
$table_cars     = $wpdb->prefix . 'dev_nahian_cars';

$base     = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking' ), admin_url( 'admin.php' ) );
$action   = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$id       = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency = get_option( 'dev_nahian_currency', 'USD' );

// 1. Route Deletion Request
if ( 'delete' === $action && $id > 0 ) {
    require_once plugin_dir_path( __FILE__ ) . 'bookings/booking-delete.php';
}

// 2. Route Create / Update POST Request
if ( isset( $_POST['dev_nahian_add_booking_submit'] ) || ( isset( $_POST['dev_nahian_edit_booking_submit'] ) && $id > 0 ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'bookings/booking-add-edit.php';
}

// Fetch active vehicles conditionally (when adding or editing)
$available_cars = array();
if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
    $available_cars = $wpdb->get_results( "SELECT id, name, price, location FROM {$table_cars} ORDER BY name ASC", ARRAY_A );
}
?>

<style>
    .dev-nahian-booking-scope {
        --b-dark: #0f172a;
        --b-slate: #475569;
        --b-muted: #64748b;
        --b-light: #f8fafc;
        --b-border: #e2e8f0;
        --b-border-subtle: #f1f5f9;
        --b-gold: #b88e1e;
        --b-teal: #0d9488;
        --b-teal-bg: #f0fdfa;
        --b-teal-border: #ccfbf1;
        --b-radius: 10px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .dev-nahian-subnav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--b-border);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dev-nahian-subnav-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dev-nahian-subnav-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--b-dark);
        letter-spacing: -0.02em;
    }
    .dev-nahian-count-chip {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        font-weight: 700;
        color: var(--b-slate);
        background: var(--b-border-subtle);
        border: 1px solid var(--b-border);
        padding: 2px 8px;
        border-radius: 999px;
    }
    .dev-nahian-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
    }
    .dev-nahian-status-pill.active { background: var(--b-teal-bg); color: var(--b-teal); border: 1px solid var(--b-teal-border); }
    .dev-nahian-status-pill.completed { background: var(--b-border-subtle); color: var(--b-slate); border: 1px solid var(--b-border); }
    .dev-nahian-status-pill.pending { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
    .dev-nahian-status-pill.cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
</style>

<div class="dev-nahian-booking-scope">

    <?php
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Booking created successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Booking updated successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Booking record deleted.', 'dev-nahian-car' ) . '</p></div>';
    }

    // 1. Include Subnav Header Bar
    include plugin_dir_path( __FILE__ ) . 'bookings/booking-tabs.php';

    // 2. Route Active View
    if ( 'view' === $action && $id > 0 ) {
        include plugin_dir_path( __FILE__ ) . 'bookings/booking-view.php';
    } elseif ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
        include plugin_dir_path( __FILE__ ) . 'bookings/booking-add-edit.php';
    } else {
        include plugin_dir_path( __FILE__ ) . 'bookings/booking-all.php';
    }
    ?>

</div>
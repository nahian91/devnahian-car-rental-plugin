<?php
/**
 * Admin View Tab: Bookings Management (Master Router)
 * Pure orchestrator file containing only request routing and include statements.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.2.0
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
        --b-gold-surface: #fefce8;
        --b-gold-border: #fef08a;
        --b-teal: #0d9488;
        --b-teal-bg: #f0fdfa;
        --b-teal-border: #ccfbf1;
        --b-radius: 12px;
        --b-radius-sm: 8px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--b-dark);
    }

    /* Executive Header Panel */
    .dev-nahian-booking-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        border: 1px solid var(--b-border);
        border-radius: var(--b-radius);
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        flex-wrap: wrap;
        gap: 16px;
    }
    .dev-nahian-booking-meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .dev-nahian-badge-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dev-nahian-live-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--b-gold);
        background: var(--b-gold-surface);
        border: 1px solid var(--b-gold-border);
        padding: 3px 10px;
        border-radius: 9999px;
    }
    .dev-nahian-live-chip .radar-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--b-gold);
        box-shadow: 0 0 0 3px rgba(184, 142, 30, 0.2);
        animation: devNahianPulse 2s infinite ease-in-out;
    }
    @keyframes devNahianPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.85); }
    }
    .dev-nahian-booking-title {
        margin: 2px 0 0 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--b-dark);
        letter-spacing: -0.02em;
    }

    /* Subnav Control Bar */
    .dev-nahian-subnav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--b-border);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dev-nahian-subnav-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Chips & Pills */
    .dev-nahian-count-chip {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        font-weight: 700;
        color: var(--b-slate);
        background: var(--b-border-subtle);
        border: 1px solid var(--b-border);
        padding: 4px 10px;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .dev-nahian-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .dev-nahian-status-pill.active,
    .dev-nahian-status-pill.confirmed { background: var(--b-teal-bg); color: var(--b-teal); border: 1px solid var(--b-teal-border); }
    .dev-nahian-status-pill.completed { background: #f1f5f9; color: var(--b-slate); border: 1px solid #cbd5e1; }
    .dev-nahian-status-pill.pending { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
    .dev-nahian-status-pill.cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

    /* Notice Overrides for Premium Theme Alignment */
    .dev-nahian-booking-scope .notice {
        margin: 0 0 20px 0 !important;
        border-radius: var(--b-radius-sm);
        border-left-width: 4px !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
    }
</style>

<div class="dev-nahian-booking-scope">

    <!-- Executive Header Panel -->
    <div class="dev-nahian-booking-header">
        <div class="dev-nahian-booking-meta">
            <div class="dev-nahian-badge-row">
                <span class="dev-nahian-live-chip">
                    <span class="radar-dot"></span>
                    <?php esc_html_e( 'Dispatch Feed', 'dev-nahian-car' ); ?>
                </span>
                <span style="font-size: 11px; color: var(--b-muted);">&bull;</span>
                <span style="font-size: 12px; font-weight: 600; color: var(--b-slate);"><?php esc_html_e( 'Reservation Ledger', 'dev-nahian-car' ); ?></span>
            </div>
            <h2 class="dev-nahian-booking-title"><?php esc_html_e( 'Bookings Management', 'dev-nahian-car' ); ?></h2>
        </div>
    </div>

    <?php
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Booking created successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Booking updated successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Notice:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Booking record deleted.', 'dev-nahian-car' ) . '</p></div>';
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
<?php
/**
 * Bookings Module: All-in-One Operations View (`tab-booking.php`)
 * Handles request routing, database create/update/delete operations, and UI rendering.
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
$table_extras   = $wpdb->prefix . 'dev_nahian_extras';

$base     = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking' ), admin_url( 'admin.php' ) );
$action   = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$id       = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency = get_option( 'dev_nahian_currency', 'USD' );

// 1. Process Deletion Request
if ( 'delete' === $action && $id > 0 ) {
    if ( current_user_can( 'manage_options' ) ) {
        check_admin_referer( 'dev_nahian_delete_booking_' . $id );
        $wpdb->delete( $table_bookings, array( 'id' => $id ), array( '%d' ) );
        wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking', 'deleted' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }
}

// 2. Process Save Action (Create POST)
if ( isset( $_POST['dev_nahian_add_booking_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_booking_verify', 'dev_nahian_booking_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $car_id       = absint( $_POST['car_id'] ?? 0 );
        $car_name_val = sanitize_text_field( wp_unslash( $_POST['car_name'] ?? '' ) );

        if ( empty( $car_name_val ) && $car_id > 0 ) {
            $car_name_val = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
        }

        $booking_code = 'BK-' . strtoupper( wp_generate_password( 6, false, false ) );

        $inserted = $wpdb->insert(
            $table_bookings,
            array(
                'booking_code'      => $booking_code,
                'customer_name'     => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) ),
                'customer_email'    => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
                'customer_phone'    => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
                'license_number'    => sanitize_text_field( wp_unslash( $_POST['license_number'] ?? '' ) ),
                'emergency_contact' => sanitize_text_field( wp_unslash( $_POST['emergency_contact'] ?? '' ) ),
                'passenger_count'   => max( 1, absint( $_POST['passenger_count'] ?? 1 ) ),
                'car_id'            => $car_id,
                'car_name'          => $car_name_val,
                'booking_status'    => sanitize_key( $_POST['booking_status'] ?? 'pending' ),
                'pickup_date'       => sanitize_text_field( wp_unslash( $_POST['pickup_date'] ?? '' ) ),
                'pickup_time'       => sanitize_text_field( wp_unslash( $_POST['pickup_time'] ?? '10:00:00' ) ),
                'pickup_location'   => sanitize_text_field( wp_unslash( $_POST['pickup_location'] ?? '' ) ),
                'return_date'       => sanitize_text_field( wp_unslash( $_POST['return_date'] ?? '' ) ),
                'return_time'       => sanitize_text_field( wp_unslash( $_POST['return_time'] ?? '10:00:00' ) ),
                'return_location'   => sanitize_text_field( wp_unslash( $_POST['return_location'] ?? '' ) ),
                'flight_number'     => sanitize_text_field( wp_unslash( $_POST['flight_number'] ?? '' ) ),
                'payment_method'    => sanitize_key( $_POST['payment_method'] ?? 'card' ),
                'total_amount'      => floatval( $_POST['total_amount'] ?? 0.00 ),
                'customer_notes'    => sanitize_textarea_field( wp_unslash( $_POST['customer_notes'] ?? '' ) ),
                'created_at'        => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
        );

        if ( false !== $inserted ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

// 3. Process Update Action (Edit POST)
if ( isset( $_POST['dev_nahian_edit_booking_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_booking_' . $id, 'dev_nahian_booking_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $car_id       = absint( $_POST['car_id'] ?? 0 );
        $car_name_val = sanitize_text_field( wp_unslash( $_POST['car_name'] ?? '' ) );

        if ( empty( $car_name_val ) && $car_id > 0 ) {
            $car_name_val = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
        }

        $updated = $wpdb->update(
            $table_bookings,
            array(
                'customer_name'     => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) ),
                'customer_email'    => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
                'customer_phone'    => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
                'license_number'    => sanitize_text_field( wp_unslash( $_POST['license_number'] ?? '' ) ),
                'emergency_contact' => sanitize_text_field( wp_unslash( $_POST['emergency_contact'] ?? '' ) ),
                'passenger_count'   => max( 1, absint( $_POST['passenger_count'] ?? 1 ) ),
                'car_id'            => $car_id,
                'car_name'          => $car_name_val,
                'booking_status'    => sanitize_key( $_POST['booking_status'] ?? 'pending' ),
                'pickup_date'       => sanitize_text_field( wp_unslash( $_POST['pickup_date'] ?? '' ) ),
                'pickup_time'       => sanitize_text_field( wp_unslash( $_POST['pickup_time'] ?? '10:00:00' ) ),
                'pickup_location'   => sanitize_text_field( wp_unslash( $_POST['pickup_location'] ?? '' ) ),
                'return_date'       => sanitize_text_field( wp_unslash( $_POST['return_date'] ?? '' ) ),
                'return_time'       => sanitize_text_field( wp_unslash( $_POST['return_time'] ?? '10:00:00' ) ),
                'return_location'   => sanitize_text_field( wp_unslash( $_POST['return_location'] ?? '' ) ),
                'flight_number'     => sanitize_text_field( wp_unslash( $_POST['flight_number'] ?? '' ) ),
                'payment_method'    => sanitize_key( $_POST['payment_method'] ?? 'card' ),
                'total_amount'      => floatval( $_POST['total_amount'] ?? 0.00 ),
                'customer_notes'    => sanitize_textarea_field( wp_unslash( $_POST['customer_notes'] ?? '' ) ),
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
}

// Fetch active vehicles and location options
$available_cars   = array();
$location_options = array();
if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
    $available_cars   = $wpdb->get_results( "SELECT id, name, price, location FROM {$table_cars} ORDER BY name ASC", ARRAY_A );
    $location_options = $wpdb->get_col( "SELECT DISTINCT title FROM {$table_extras} WHERE status = 'active' ORDER BY title ASC" );
    if ( empty( $location_options ) ) {
        $location_options = array( 'Executive Terminal Alpha', 'Downtown Dispatch Hub', 'International Airport Bay 4', 'Northside Service Center' );
    }
}

// Fetch payment methods from settings or fallback
$settings_payment_methods = get_option( 'dev_nahian_payment_methods', array(
    'card'      => __( 'Credit Card (Stripe Terminal)', 'dev-nahian-car' ),
    'wallet'    => __( 'Apple Pay / Google Pay', 'dev-nahian-car' ),
    'corporate' => __( 'Corporate Direct Account', 'dev-nahian-car' ),
    'wire'      => __( 'Direct Bank Wire Transfer', 'dev-nahian-car' ),
) );

$is_add_active = ( 'add' === $subtab || 'edit' === $action );
$add_url       = add_query_arg( array( 'subtab' => 'add' ), $base );
$all_url       = add_query_arg( array( 'subtab' => 'all' ), $base );
$csv_url       = wp_nonce_url( admin_url( 'admin-post.php?action=dev_nahian_export_csv' ), 'dev_nahian_csv_nonce' );
?>

<style>
    .dev-nahian-booking-scope {
        --b-dark: #0f172a;
        --b-slate: #475569;
        --b-muted: #64748b;
        --b-light: #f8fafc;
        --b-border: #cbd5e1;
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

    /* Action Bar */
    .dev-nahian-action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--b-border);
        flex-wrap: wrap;
        gap: 14px;
    }
    .dev-nahian-action-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Premium Buttons */
    .dn-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: var(--b-dark);
        color: #ffffff !important;
        border: 1px solid var(--b-dark);
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 600;
        border-radius: var(--b-radius-sm);
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        cursor: pointer;
    }
    .dn-btn-primary:hover {
        background: #1e293b;
        border-color: #1e293b;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
    }
    .dn-btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #ffffff;
        color: var(--b-dark) !important;
        border: 1px solid var(--b-border);
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 600;
        border-radius: var(--b-radius-sm);
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .dn-btn-secondary:hover {
        background: var(--b-light);
        color: var(--b-teal) !important;
        border-color: #94a3b8;
        transform: translateY(-1px);
    }
    .dn-btn-secondary.active {
        background: var(--b-teal-bg);
        color: var(--b-teal) !important;
        border-color: var(--b-teal-border);
    }

    /* Status Pills & Chips */
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
        margin-bottom: 12px;
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

    /* Ultra-Clean Modern Form Controls */
    .dev-nahian-form-surface {
        background: #ffffff;
        border: 1px solid var(--b-border);
        border-radius: var(--b-radius);
        padding: 32px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    }
    .dev-nahian-form-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    @media (max-width: 1024px) {
        .dev-nahian-form-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .dev-nahian-form-grid { grid-template-columns: 1fr; }
    }
    .col-full { grid-column: 1 / -1; }
    .col-2 { grid-column: span 2; }
    @media (max-width: 640px) {
        .col-2 { grid-column: 1 / -1; }
    }
    .dev-nahian-form-section-heading {
        grid-column: 1 / -1;
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--b-teal);
        padding-top: 18px;
        margin-top: 10px;
        border-top: 1px solid var(--b-border);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dev-nahian-form-section-heading:first-of-type {
        border-top: none;
        padding-top: 0;
        margin-top: 0;
    }
    .dev-nahian-field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .dev-nahian-field-group label {
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--b-slate);
    }
    .dev-nahian-field-input, 
    .dev-nahian-field-select,
    .dev-nahian-field-textarea {
        height: 42px;
        padding: 0 14px;
        border: 1px solid var(--b-border);
        border-radius: var(--b-radius-sm);
        background: #f8fafc;
        font-size: 13.5px;
        color: var(--b-dark);
        outline: none;
        box-sizing: border-box;
        width: 100%;
        transition: all 0.2s ease;
    }
    .dev-nahian-field-input:hover,
    .dev-nahian-field-select:hover,
    .dev-nahian-field-textarea:hover {
        border-color: #94a3b8;
    }
    .dev-nahian-field-input:focus, 
    .dev-nahian-field-select:focus,
    .dev-nahian-field-textarea:focus {
        border-color: var(--b-teal);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
    }
    .dev-nahian-field-textarea {
        height: 90px;
        padding: 12px 14px;
        resize: vertical;
    }
    .dev-nahian-scope .notice {
        margin: 0 0 20px 0 !important;
        border-radius: var(--b-radius-sm);
        border-left-width: 4px !important;
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
    ?>

    <!-- Subnavigation & Action Bar -->
    <div class="dev-nahian-action-bar">
        <div class="dev-nahian-action-group">
            <a href="<?php echo esc_url( $all_url ); ?>" class="dn-btn-secondary <?php echo ( ! $is_add_active && 'all' === $subtab ) ? 'active' : ''; ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <?php esc_html_e( 'All Reservations', 'dev-nahian-car' ); ?>
            </a>
            <a href="<?php echo esc_url( $csv_url ); ?>" class="dn-btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <?php esc_html_e( 'Export CSV Manifest', 'dev-nahian-car' ); ?>
            </a>
        </div>

        <div class="dev-nahian-action-group">
            <?php if ( ! $is_add_active ) : ?>
                <a href="<?php echo esc_url( $add_url ); ?>" class="dn-btn-primary">
                    <?php echo dev_nahian_get_icon( 'add' ); ?>
                    <?php esc_html_e( 'Add New Booking', 'dev-nahian-car' ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( $all_url ); ?>" class="dn-btn-secondary">
                    <?php echo dev_nahian_get_icon( 'back' ); ?>
                    <?php esc_html_e( 'Back to Ledger', 'dev-nahian-car' ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Route Active View
    if ( 'view' === $action && $id > 0 ) {
        $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $id ), ARRAY_A );
        if ( $booking ) :
        ?>
            <div style="background: #ffffff; border: 1px solid var(--b-border); border-radius: var(--b-radius); padding: 28px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);">
                <h3 style="margin-top: 0; font-size: 16px; font-weight: 700; color: var(--b-dark); border-bottom: 1px solid var(--b-border); padding-bottom: 12px;">
                    <?php printf( esc_html__( 'Reservation Dossier: #%s', 'dev-nahian-car' ), esc_html( $booking['booking_code'] ) ); ?>
                </h3>
                <table class="widefat striped" style="margin-top: 15px;">
                    <tr>
                        <th style="width: 200px;"><?php esc_html_e( 'Customer Name', 'dev-nahian-car' ); ?></th>
                        <td><strong><?php echo esc_html( $booking['customer_name'] ); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Email & Phone', 'dev-nahian-car' ); ?></th>
                        <td><?php echo esc_html( $booking['customer_email'] . ' | ' . $booking['customer_phone'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Assigned Vehicle', 'dev-nahian-car' ); ?></th>
                        <td><?php echo esc_html( ! empty( $booking['car_name'] ) ? $booking['car_name'] : '-' ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Schedule Window', 'dev-nahian-car' ); ?></th>
                        <td><?php echo esc_html( $booking['pickup_date'] . ' (' . $booking['pickup_time'] . ') &rarr; ' . $booking['return_date'] . ' (' . $booking['return_time'] . ')' ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Locations (Pickup / Return)', 'dev-nahian-car' ); ?></th>
                        <td><?php echo esc_html( $booking['pickup_location'] . ' &rarr; ' . $booking['return_location'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Total Amount', 'dev-nahian-car' ); ?></th>
                        <td><strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $booking['total_amount'], 2 ) ); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
                        <td><span class="dev-nahian-status-pill <?php echo esc_attr( $booking['booking_status'] ); ?>">&bull; <?php echo esc_html( ucfirst( $booking['booking_status'] ) ); ?></span></td>
                    </tr>
                </table>
                <p style="margin-top: 24px;">
                    <a href="<?php echo esc_url( $all_url ); ?>" class="dn-btn-secondary"><?php esc_html_e( '&larr; Return to Manifest Ledger', 'dev-nahian-car' ); ?></a>
                </p>
            </div>
        <?php else : ?>
            <div class="notice notice-error"><p><?php esc_html_e( 'Requested booking dossier not found.', 'dev-nahian-car' ); ?></p></div>
        <?php endif; ?>

    <?php } elseif ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
        $is_edit = ( 'edit' === $action && $id > 0 );
        $booking = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $id ), ARRAY_A ) : array();
        ?>
        <div class="dev-nahian-form-surface">
            <form method="post" action="">
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

                <div class="dev-nahian-form-grid">
                    <div class="dev-nahian-form-section-heading"><?php esc_html_e( '1. Customer Profile Information', 'dev-nahian-car' ); ?></div>

                    <div class="dev-nahian-field-group">
                        <label for="customer_name"><?php esc_html_e( 'Customer Name', 'dev-nahian-car' ); ?></label>
                        <input type="text" id="customer_name" name="customer_name" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['customer_name'] ?? '' ); ?>" required placeholder="e.g., Alexander Wright">
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="customer_email"><?php esc_html_e( 'Customer Email', 'dev-nahian-car' ); ?></label>
                        <input type="email" id="customer_email" name="customer_email" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['customer_email'] ?? '' ); ?>" required placeholder="client@example.com">
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="customer_phone"><?php esc_html_e( 'Phone Number', 'dev-nahian-car' ); ?></label>
                        <input type="text" id="customer_phone" name="customer_phone" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['customer_phone'] ?? '' ); ?>" required placeholder="+1 (555) 019-2834">
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="license_number"><?php esc_html_e( 'Driver License Number', 'dev-nahian-car' ); ?></label>
                        <input type="text" id="license_number" name="license_number" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['license_number'] ?? '' ); ?>" placeholder="DL-98234-X">
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="emergency_contact"><?php esc_html_e( 'Emergency Contact', 'dev-nahian-car' ); ?></label>
                        <input type="text" id="emergency_contact" name="emergency_contact" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['emergency_contact'] ?? '' ); ?>" placeholder="Name & Phone">
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="passenger_count"><?php esc_html_e( 'Passengers Count', 'dev-nahian-car' ); ?></label>
                        <input type="number" id="passenger_count" name="passenger_count" class="dev-nahian-field-input" value="<?php echo esc_attr( (string) ( $booking['passenger_count'] ?? 1 ) ); ?>" min="1" max="12">
                    </div>

                    <div class="dev-nahian-form-section-heading"><?php esc_html_e( '2. Fleet Asset & Schedule Window', 'dev-nahian-car' ); ?></div>

                    <div class="dev-nahian-field-group col-2">
                        <label for="car_id"><?php esc_html_e( 'Select Fleet Vehicle Asset', 'dev-nahian-car' ); ?></label>
                        <select id="car_id" name="car_id" class="dev-nahian-field-select" required onchange="document.getElementById('car_name').value = this.options[this.selectedIndex].text.split(' (')[0];">
                            <option value="0">-- <?php esc_html_e( 'Choose Vehicle Asset', 'dev-nahian-car' ); ?> --</option>
                            <?php if ( ! empty( $available_cars ) ) : ?>
                                <?php foreach ( $available_cars as $c ) : ?>
                                    <option value="<?php echo esc_attr( (string) $c['id'] ); ?>" <?php selected( (int) ( $booking['car_id'] ?? 0 ), (int) $c['id'] ); ?>>
                                        <?php echo esc_html( $c['name'] . ' (' . $currency . ' ' . number_format_i18n( (float) $c['price'], 2 ) . '/day)' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="booking_status"><?php esc_html_e( 'Dispatch Status', 'dev-nahian-car' ); ?></label>
                        <select id="booking_status" name="booking_status" class="dev-nahian-field-select">
                            <option value="active" <?php selected( $booking['booking_status'] ?? '', 'active' ); ?>><?php esc_html_e( 'Active • Dispatched', 'dev-nahian-car' ); ?></option>
                            <option value="pending" <?php selected( $booking['booking_status'] ?? 'pending', 'pending' ); ?>><?php esc_html_e( 'Pending Authorization', 'dev-nahian-car' ); ?></option>
                            <option value="completed" <?php selected( $booking['booking_status'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed • Returned', 'dev-nahian-car' ); ?></option>
                            <option value="cancelled" <?php selected( $booking['booking_status'] ?? '', 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'dev-nahian-car' ); ?></option>
                        </select>
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="pickup_date"><?php esc_html_e( 'Pickup Date', 'dev-nahian-car' ); ?></label>
                        <input type="date" id="pickup_date" name="pickup_date" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['pickup_date'] ?? current_time( 'Y-m-d' ) ); ?>" required>
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="pickup_time"><?php esc_html_e( 'Pickup Time', 'dev-nahian-car' ); ?></label>
                        <input type="time" id="pickup_time" name="pickup_time" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['pickup_time'] ?? '10:00' ); ?>" required>
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="pickup_location"><?php esc_html_e( 'Pickup Hub Location (From Extras)', 'dev-nahian-car' ); ?></label>
                        <select id="pickup_location" name="pickup_location" class="dev-nahian-field-select" required>
                            <option value=""><?php esc_html_e( '-- Select Pickup Location --', 'dev-nahian-car' ); ?></option>
                            <?php foreach ( $location_options as $loc ) : ?>
                                <option value="<?php echo esc_attr( $loc ); ?>" <?php selected( $booking['pickup_location'] ?? '', $loc ); ?>>
                                    <?php echo esc_html( $loc ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="return_date"><?php esc_html_e( 'Return Date', 'dev-nahian-car' ); ?></label>
                        <input type="date" id="return_date" name="return_date" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['return_date'] ?? '' ); ?>" required>
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="return_time"><?php esc_html_e( 'Return Time', 'dev-nahian-car' ); ?></label>
                        <input type="time" id="return_time" name="return_time" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['return_time'] ?? '10:00' ); ?>" required>
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="return_location"><?php esc_html_e( 'Return Hub Location (From Extras)', 'dev-nahian-car' ); ?></label>
                        <select id="return_location" name="return_location" class="dev-nahian-field-select" required>
                            <option value=""><?php esc_html_e( '-- Select Return Location --', 'dev-nahian-car' ); ?></option>
                            <?php foreach ( $location_options as $loc ) : ?>
                                <option value="<?php echo esc_attr( $loc ); ?>" <?php selected( $booking['return_location'] ?? '', $loc ); ?>>
                                    <?php echo esc_html( $loc ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dev-nahian-form-section-heading"><?php esc_html_e( '3. Flight, Payment Settings & Logistics', 'dev-nahian-car' ); ?></div>

                    <div class="dev-nahian-field-group">
                        <label for="flight_number"><?php esc_html_e( 'Flight Tail / Number', 'dev-nahian-car' ); ?></label>
                        <input type="text" id="flight_number" name="flight_number" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['flight_number'] ?? '' ); ?>" placeholder="e.g., EK-202">
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="payment_method"><?php esc_html_e( 'Payment Gateway (From Settings)', 'dev-nahian-car' ); ?></label>
                        <select id="payment_method" name="payment_method" class="dev-nahian-field-select">
                            <?php foreach ( $settings_payment_methods as $pm_key => $pm_label ) : ?>
                                <option value="<?php echo esc_attr( $pm_key ); ?>" <?php selected( $booking['payment_method'] ?? 'card', $pm_key ); ?>>
                                    <?php echo esc_html( $pm_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dev-nahian-field-group">
                        <label for="total_amount"><?php printf( esc_html__( 'Total Invoiced Amount (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></label>
                        <input type="number" step="0.01" id="total_amount" name="total_amount" class="dev-nahian-field-input" value="<?php echo esc_attr( (string) ( $booking['total_amount'] ?? 0.00 ) ); ?>" required>
                    </div>

                    <div class="dev-nahian-field-group col-full">
                        <label for="customer_notes"><?php esc_html_e( 'Driver Notes & Special Logistics', 'dev-nahian-car' ); ?></label>
                        <textarea id="customer_notes" name="customer_notes" class="dev-nahian-field-textarea" placeholder="Add specific delivery instructions, child seats, or VIP handling notes..."><?php echo esc_textarea( $booking['customer_notes'] ?? '' ); ?></textarea>
                    </div>
                </div>

                <p style="margin: 28px 0 0 0; display: flex; gap: 12px;">
                    <button type="submit" name="<?php echo $is_edit ? 'dev_nahian_edit_booking_submit' : 'dev_nahian_add_booking_submit'; ?>" class="dn-btn-primary">
                        <?php echo $is_edit ? esc_html__( 'Save Booking Changes', 'dev-nahian-car' ) : esc_html__( 'Save & Generate Booking', 'dev-nahian-car' ); ?>
                    </button>
                    <a href="<?php echo esc_url( $all_url ); ?>" class="dn-btn-secondary"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
                </p>
            </form>
        </div>

    <?php } else {
        $bookings      = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC", ARRAY_A );
        $booking_count = is_array( $bookings ) ? count( $bookings ) : 0;
        ?>
        <div>
            <span class="dev-nahian-count-chip">
                <?php printf( esc_html__( '%s Total Records', 'dev-nahian-car' ), esc_html( number_format_i18n( $booking_count ) ) ); ?>
            </span>
        </div>

        <table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border-radius:8px; overflow:hidden;">
            <thead>
                <tr>
                    <th style="width: 100px;"><?php esc_html_e( 'Booking ID', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Customer', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Phone', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Vehicle', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Pickup', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Return', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
                    <th style="text-align: right; width: 170px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $bookings ) ) : ?>
                    <?php foreach ( $bookings as $b ) : 
                        $status_key = sanitize_key( $b['booking_status'] );
                        $view_url   = add_query_arg( array( 'action' => 'view', 'id' => $b['id'] ), $base );
                        $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $b['id'], 'subtab' => 'add' ), $base );
                        $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $b['id'] ), $base ), 'dev_nahian_delete_booking_' . $b['id'] );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $b['booking_code'] ); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html( $b['customer_name'] ); ?></strong><br>
                                <small style="color: var(--b-muted);"><?php echo esc_html( $b['customer_email'] ); ?></small>
                            </td>
                            <td><?php echo esc_html( $b['customer_phone'] ); ?></td>
                            <td><?php echo esc_html( ! empty( $b['car_name'] ) ? $b['car_name'] : '-' ); ?></td>
                            <td><?php echo esc_html( $b['pickup_date'] . ' (' . $b['pickup_time'] . ')' ); ?></td>
                            <td><?php echo esc_html( $b['return_date'] . ' (' . $b['return_time'] . ')' ); ?></td>
                            <td><strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $b['total_amount'], 2 ) ); ?></strong></td>
                            <td><span class="dev-nahian-status-pill <?php echo esc_attr( $status_key ); ?>">&bull; <?php echo esc_html( ucfirst( $status_key ) ); ?></span></td>
                            <td style="text-align: right;">
                                <div class="dev-nahian-btn-group">
                                    <a href="<?php echo esc_url( $view_url ); ?>" class="dev-nahian-btn dev-nahian-btn-view" title="<?php esc_attr_e( 'View Booking Dossier', 'dev-nahian-car' ); ?>">
                                        <?php echo dev_nahian_get_icon( 'view' ); ?> <?php esc_html_e( 'View', 'dev-nahian-car' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( $edit_url ); ?>" class="dev-nahian-btn dev-nahian-btn-edit" title="<?php esc_attr_e( 'Edit Booking', 'dev-nahian-car' ); ?>">
                                        <?php echo dev_nahian_get_icon( 'edit' ); ?> <?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( $delete_url ); ?>" class="dev-nahian-btn dev-nahian-btn-delete" title="<?php esc_attr_e( 'Delete Booking Record', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this booking record permanently?', 'dev-nahian-car' ); ?>');">
                                        <?php echo dev_nahian_get_icon( 'delete' ); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No bookings recorded in the system yet.', 'dev-nahian-car' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php } ?>

</div>
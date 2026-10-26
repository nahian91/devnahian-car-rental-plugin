<?php
/**
 * Bookings Module: Manifest Inspector View (`booking-view.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $id ), ARRAY_A );

if ( ! $booking ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking record not found.', 'dev-nahian-car' ) . '</p></div>';
    return;
}

$status_slug = sanitize_key( $booking['booking_status'] );
?>
<style>
    .dev-nahian-manifest-card {
        background: #ffffff;
        border: 1px solid var(--b-border);
        border-radius: var(--b-radius);
        overflow: hidden;
        box-shadow: 0 4px 14px -3px rgba(15, 23, 42, 0.05);
    }
    .dev-nahian-manifest-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        padding: 22px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dev-nahian-manifest-body {
        padding: 28px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 28px;
    }
    @media (max-width: 900px) {
        .dev-nahian-manifest-body { grid-template-columns: 1fr; }
    }
    .dev-nahian-manifest-section-title {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--b-muted);
        margin: 0 0 12px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dev-nahian-manifest-section-title::after {
        content: '';
        flex-grow: 1;
        height: 1px;
        background: var(--b-border);
    }
    .dev-nahian-spec-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed var(--b-border);
        font-size: 13px;
    }
    .dev-nahian-spec-label { color: var(--b-muted); }
    .dev-nahian-spec-val { font-weight: 700; color: var(--b-dark); text-align: right; }
</style>

<div class="dev-nahian-subnav-bar">
    <div class="dev-nahian-subnav-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">
            <?php esc_html_e( 'Back to All Bookings', 'dev-nahian-car' ); ?>
        </a>
        <span class="dev-nahian-count-chip"><?php esc_html_e( 'Manifest Inspector', 'dev-nahian-car' ); ?></span>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $id ), $base ) ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700; margin-right:6px;">
            <?php esc_html_e( 'Edit Booking', 'dev-nahian-car' ); ?>
        </a>
        <button type="button" class="button button-secondary" onclick="window.print();"><?php esc_html_e( 'Print Manifest', 'dev-nahian-car' ); ?></button>
    </div>
</div>

<div class="dev-nahian-manifest-card">
    <div class="dev-nahian-manifest-header">
        <div>
            <span style="font-family: monospace; font-size: 12px; color: var(--b-gold); font-weight: 700;">
                <?php printf( esc_html__( 'MANIFEST #%s', 'dev-nahian-car' ), esc_html( $booking['booking_code'] ) ); ?>
            </span>
            <h2 style="margin: 4px 0 0; font-size: 20px; font-weight: 800; color: #fff;">
                <?php echo esc_html( ! empty( $booking['car_name'] ) ? $booking['car_name'] : __( 'Chassis Assigned', 'dev-nahian-car' ) ); ?>
            </h2>
        </div>
        <div>
            <span class="dev-nahian-status-pill <?php echo esc_attr( $status_slug ); ?>">
                &bull; <?php echo esc_html( ucfirst( $status_slug ) ); ?>
            </span>
        </div>
    </div>

    <div class="dev-nahian-manifest-body">
        <div>
            <div class="dev-nahian-manifest-section-title"><?php esc_html_e( '1. Customer & Driver Details', 'dev-nahian-car' ); ?></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Customer Name', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['customer_name'] ); ?></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Email Address', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['customer_email'] ); ?></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Phone Number', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['customer_phone'] ); ?></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Driver License #', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><code><?php echo esc_html( $booking['license_number'] ); ?></code></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Emergency Contact', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['emergency_contact'] ); ?></span></div>

            <div class="dev-nahian-manifest-section-title" style="margin-top: 24px;"><?php esc_html_e( '2. Schedule & Flight Logistics', 'dev-nahian-car' ); ?></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Pickup Schedule', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['pickup_date'] . ' ' . $booking['pickup_time'] ); ?></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Return Schedule', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['return_date'] . ' ' . $booking['return_time'] ); ?></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Pickup Location', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['pickup_location'] ); ?></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Return Location', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['return_location'] ); ?></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Flight Tail #', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><code><?php echo esc_html( ! empty( $booking['flight_number'] ) ? $booking['flight_number'] : '-' ); ?></code></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Passengers', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( number_format_i18n( (int) $booking['passenger_count'] ) ); ?></span></div>

            <div class="dev-nahian-manifest-section-title" style="margin-top: 24px;"><?php esc_html_e( '3. Notes & Special Logistics', 'dev-nahian-car' ); ?></div>
            <div style="background: var(--b-light); border: 1px solid var(--b-border); border-radius: 6px; padding: 12px; font-size: 13px; color: var(--b-dark);">
                <?php echo ! empty( $booking['customer_notes'] ) ? esc_html( $booking['customer_notes'] ) : esc_html__( 'No operational notes registered.', 'dev-nahian-car' ); ?>
            </div>
        </div>

        <div>
            <div class="dev-nahian-manifest-section-title"><?php esc_html_e( 'Assigned Vehicle', 'dev-nahian-car' ); ?></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Chassis Model', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( $booking['car_name'] ); ?></span></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Chassis ID', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val">#<?php echo esc_html( (string) $booking['car_id'] ); ?></span></div>

            <div class="dev-nahian-manifest-section-title" style="margin-top: 24px;"><?php esc_html_e( 'Billing Ledger', 'dev-nahian-car' ); ?></div>
            <div class="dev-nahian-spec-row"><span class="dev-nahian-spec-label"><?php esc_html_e( 'Payment Method', 'dev-nahian-car' ); ?></span><span class="dev-nahian-spec-val"><?php echo esc_html( strtoupper( $booking['payment_method'] ) ); ?></span></div>
            <div class="dev-nahian-spec-row" style="border-bottom: none; padding-top: 12px;">
                <span class="dev-nahian-spec-label" style="font-size: 15px; font-weight: 800; color: var(--b-dark);"><?php esc_html_e( 'Total Amount', 'dev-nahian-car' ); ?></span>
                <span class="dev-nahian-spec-val" style="font-size: 20px; font-weight: 800; color: var(--b-dark);">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $booking['total_amount'], 2 ) ); ?>
                </span>
            </div>
        </div>
    </div>
</div>
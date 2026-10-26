<?php
/**
 * Customers Module: Driver Dossier Inspector (`customer-view.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cust = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $id ), ARRAY_A );

if ( ! $cust ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer profile not found.', 'dev-nahian-car' ) . '</p></div>';
    return;
}

$cust_status_slug = sanitize_key( $cust['status'] );
$cust_tier_slug   = sanitize_key( $cust['tier'] );

$booking_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_bookings} WHERE customer_email = %s", $cust['email'] ) );
$total_spent   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total_amount) FROM {$table_bookings} WHERE customer_email = %s", $cust['email'] ) );
?>
<style>
    .dev-nahian-view-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .dev-nahian-view-header {
        background: var(--dark);
        color: #ffffff;
        padding: 22px 26px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dev-nahian-view-body {
        padding: 26px;
        display: grid;
        grid-template-columns: 2fr 1.2fr;
        gap: 24px;
    }
    @media (max-width: 860px) {
        .dev-nahian-view-body { grid-template-columns: 1fr; }
    }
    .dev-nahian-view-section {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--muted);
        margin: 0 0 10px 0;
        border-bottom: 1px solid var(--border);
        padding-bottom: 6px;
    }
    .dev-nahian-row-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed var(--border);
        font-size: 13px;
    }
    .dev-nahian-row-item .label { color: var(--muted); }
    .dev-nahian-row-item .value { font-weight: 700; color: var(--dark); text-align: right; }
</style>

<div class="dev-nahian-cust-bar">
    <div class="dev-nahian-cust-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to Customers', 'dev-nahian-car' ); ?></a>
        <span class="dev-nahian-cust-chip"><?php esc_html_e( 'Driver Dossier', 'dev-nahian-car' ); ?></span>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $id ), $base ) ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
            <?php esc_html_e( 'Edit Customer', 'dev-nahian-car' ); ?>
        </a>
    </div>
</div>

<div class="dev-nahian-view-card">
    <div class="dev-nahian-view-header">
        <div>
            <span style="font-family: monospace; font-size: 12px; color: var(--gold); font-weight: 700;">
                <?php printf( esc_html__( 'CLIENT ID: #%s', 'dev-nahian-car' ), esc_html( $cust['customer_code'] ) ); ?>
            </span>
            <h2 style="margin: 4px 0 0; font-size: 20px; font-weight: 800; color: #fff;">
                <?php echo esc_html( $cust['name'] ); ?>
            </h2>
        </div>
        <div>
            <span class="dev-nahian-status-pill <?php echo esc_attr( $cust_status_slug ); ?>">
                &bull; <?php echo esc_html( ucfirst( $cust_status_slug ) ); ?>
            </span>
        </div>
    </div>

    <div class="dev-nahian-view-body">
        <div>
            <div class="dev-nahian-view-section"><?php esc_html_e( '1. Identity & Contact', 'dev-nahian-car' ); ?></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Full Legal Name', 'dev-nahian-car' ); ?></span><span class="value"><?php echo esc_html( $cust['name'] ); ?></span></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Verified Email', 'dev-nahian-car' ); ?></span><span class="value"><?php echo esc_html( $cust['email'] ); ?></span></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Phone Number', 'dev-nahian-car' ); ?></span><span class="value"><?php echo esc_html( ! empty( $cust['phone'] ) ? $cust['phone'] : '-' ); ?></span></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Date of Birth', 'dev-nahian-car' ); ?></span><span class="value"><?php echo esc_html( ! empty( $cust['dob'] ) ? $cust['dob'] : '-' ); ?></span></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Country / Region', 'dev-nahian-car' ); ?></span><span class="value"><?php echo esc_html( ! empty( $cust['country'] ) ? $cust['country'] : '-' ); ?></span></div>

            <div class="dev-nahian-view-section" style="margin-top: 20px;"><?php esc_html_e( '2. Driver License Credentials', 'dev-nahian-car' ); ?></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'License Number', 'dev-nahian-car' ); ?></span><span class="value"><code><?php echo esc_html( ! empty( $cust['license_number'] ) ? $cust['license_number'] : '-' ); ?></code></span></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Expiration Date', 'dev-nahian-car' ); ?></span><span class="value"><?php echo esc_html( ! empty( $cust['license_expiry'] ) ? $cust['license_expiry'] : '-' ); ?></span></div>

            <div class="dev-nahian-view-section" style="margin-top: 20px;"><?php esc_html_e( '3. Notes & Preferences', 'dev-nahian-car' ); ?></div>
            <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 6px; padding: 12px; font-size: 13px; color: var(--text);">
                <?php echo ! empty( $cust['notes'] ) ? esc_html( $cust['notes'] ) : esc_html__( 'No operational notes recorded.', 'dev-nahian-car' ); ?>
            </div>
        </div>

        <div>
            <div class="dev-nahian-view-section"><?php esc_html_e( 'Membership & Account', 'dev-nahian-car' ); ?></div>
            <div class="dev-nahian-row-item">
                <span class="label"><?php esc_html_e( 'Membership Tier', 'dev-nahian-car' ); ?></span>
                <span class="value">
                    <span class="dev-nahian-tier-badge <?php echo esc_attr( $cust_tier_slug ); ?>">
                        <?php echo esc_html( ucfirst( $cust_tier_slug ) ); ?>
                    </span>
                </span>
            </div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Total Bookings', 'dev-nahian-car' ); ?></span><span class="value"><?php printf( esc_html__( '%s Completed', 'dev-nahian-car' ), esc_html( number_format_i18n( $booking_count ) ) ); ?></span></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Lifetime Spent', 'dev-nahian-car' ); ?></span><span class="value"><?php echo esc_html( $currency . ' ' . number_format_i18n( $total_spent, 2 ) ); ?></span></div>

            <div class="dev-nahian-view-section" style="margin-top: 20px;"><?php esc_html_e( 'Account Meta', 'dev-nahian-car' ); ?></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Client Key Tag', 'dev-nahian-car' ); ?></span><span class="value"><code><?php echo esc_html( $cust['customer_code'] ); ?></code></span></div>
            <div class="dev-nahian-row-item"><span class="label"><?php esc_html_e( 'Registered On', 'dev-nahian-car' ); ?></span><span class="value"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $cust['created_at'] ) ) ); ?></span></div>
        </div>
    </div>
</div>
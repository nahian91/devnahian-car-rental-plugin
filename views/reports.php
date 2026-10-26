<?php
/**
 * Admin View Tab: Financial Reports & Operational Audits
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
$currency       = get_option( 'dev_nahian_currency', 'USD' );

$base = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'reports' ), admin_url( 'admin.php' ) );

// Dedicated CSV Export URL for full bookings
$export_bookings_url = wp_nonce_url(
    admin_url( 'admin-post.php?action=dev_nahian_export_csv&export_type=bookings' ),
    'dev_nahian_csv_nonce'
);

// 1. Dynamic Metric Calculations from MySQL
$gross_revenue    = (float) $wpdb->get_var( "SELECT SUM(total_amount) FROM {$table_bookings} WHERE booking_status != 'cancelled'" );
$settled_bookings = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_bookings} WHERE booking_status IN ('completed', 'active')" );
$avg_order_value  = ( $settled_bookings > 0 ) ? ( $gross_revenue / $settled_bookings ) : 0.00;
$tax_provision    = $gross_revenue * 0.08;

// 2. Dynamic Filter Parameters
$filter_from = isset( $_GET['filter_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_from'] ) ) : '';
$filter_to   = isset( $_GET['filter_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_to'] ) ) : '';
$filter_car  = isset( $_GET['filter_car'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_car'] ) ) : '';

// 3. Query Audited Settlements from Database
$where_clauses = array( '1=1' );
if ( ! empty( $filter_from ) ) {
    $where_clauses[] = $wpdb->prepare( 'pickup_date >= %s', $filter_from );
}
if ( ! empty( $filter_to ) ) {
    $where_clauses[] = $wpdb->prepare( 'return_date <= %s', $filter_to );
}
if ( ! empty( $filter_car ) ) {
    $where_clauses[] = $wpdb->prepare( 'car_name = %s', $filter_car );
}

$where_sql   = implode( ' AND ', $where_clauses );
$settlements = $wpdb->get_results( "SELECT * FROM {$table_bookings} WHERE {$where_sql} ORDER BY id DESC LIMIT 50", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Fetch vehicles for filter dropdown
$cars_dropdown = $wpdb->get_col( "SELECT DISTINCT name FROM {$table_cars} ORDER BY name ASC" );
?>

<style>
    .dev-nahian-rep-scope {
        --dark: #0f172a;
        --text: #334155;
        --muted: #64748b;
        --bg: #f8fafc;
        --border: #e2e8f0;
        --border-subtle: #f1f5f9;
        --teal: #0d9488;
        --teal-bg: #f0fdfa;
        --teal-border: #ccfbf1;
    }

    .dev-nahian-rep-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dev-nahian-rep-bar-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dev-nahian-rep-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--dark);
    }
    .dev-nahian-rep-chip {
        font-size: 11px;
        font-weight: 700;
        color: var(--muted);
        background: var(--bg);
        border: 1px solid var(--border);
        padding: 3px 10px;
        border-radius: 999px;
    }

    /* KPI Metric Cards */
    .dev-nahian-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }
    @media (max-width: 1024px) {
        .dev-nahian-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .dev-nahian-kpi-grid { grid-template-columns: 1fr; }
    }
    .dev-nahian-kpi-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .dev-nahian-kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        display: block;
        margin-bottom: 6px;
    }
    .dev-nahian-kpi-val {
        font-size: 24px;
        font-weight: 800;
        color: var(--dark);
        letter-spacing: -0.02em;
        display: block;
        line-height: 1.1;
    }
    .dev-nahian-kpi-sub {
        font-size: 11px;
        color: var(--teal);
        font-weight: 600;
        margin-top: 8px;
        display: block;
    }

    /* Filter Toolbar */
    .dev-nahian-filter-box {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
    }
    .dev-nahian-filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .dev-nahian-filter-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text);
    }
    .dev-nahian-filter-input,
    .dev-nahian-filter-select {
        height: 34px;
        padding: 0 10px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: #ffffff;
        font-size: 13px;
        color: var(--dark);
        outline: none;
    }

    /* Table Badges */
    .dev-nahian-status-pill {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
    }
    .dev-nahian-status-pill.active {
        background: var(--teal-bg);
        color: var(--teal);
        border: 1px solid var(--teal-border);
    }
    .dev-nahian-status-pill.completed {
        background: #f1f5f9;
        color: var(--muted);
        border: 1px solid var(--border);
    }
    .dev-nahian-status-pill.pending {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fef3c7;
    }
    .dev-nahian-status-pill.cancelled {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
</style>

<div class="dev-nahian-rep-scope">

    <!-- Top Bar -->
    <div class="dev-nahian-rep-bar">
        <div class="dev-nahian-rep-bar-left">
            <h2 class="dev-nahian-rep-title"><?php esc_html_e( 'Reports & Data Audits', 'dev-nahian-car' ); ?></h2>
            <span class="dev-nahian-rep-chip"><?php esc_html_e( 'Audit Ledger Active', 'dev-nahian-car' ); ?></span>
        </div>
        <div>
            <a href="<?php echo esc_url( $export_bookings_url ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; margin-right:4px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <?php esc_html_e( 'Download Full Bookings CSV', 'dev-nahian-car' ); ?>
            </a>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="dev-nahian-kpi-grid">
        <div class="dev-nahian-kpi-card">
            <span class="dev-nahian-kpi-label"><?php esc_html_e( 'Gross Invoiced Revenue', 'dev-nahian-car' ); ?></span>
            <strong class="dev-nahian-kpi-val"><?php echo esc_html( $currency . ' ' . number_format_i18n( $gross_revenue, 2 ) ); ?></strong>
            <span class="dev-nahian-kpi-sub"><?php esc_html_e( 'Calculated from active/completed manifests', 'dev-nahian-car' ); ?></span>
        </div>
        <div class="dev-nahian-kpi-card">
            <span class="dev-nahian-kpi-label"><?php esc_html_e( 'Settled Manifests', 'dev-nahian-car' ); ?></span>
            <strong class="dev-nahian-kpi-val"><?php printf( esc_html__( '%s Bookings', 'dev-nahian-car' ), esc_html( number_format_i18n( $settled_bookings ) ) ); ?></strong>
            <span class="dev-nahian-kpi-sub"><?php esc_html_e( 'Completed & active reservations', 'dev-nahian-car' ); ?></span>
        </div>
        <div class="dev-nahian-kpi-card">
            <span class="dev-nahian-kpi-label"><?php esc_html_e( 'Average Booking Value', 'dev-nahian-car' ); ?></span>
            <strong class="dev-nahian-kpi-val"><?php echo esc_html( $currency . ' ' . number_format_i18n( $avg_order_value, 2 ) ); ?></strong>
            <span class="dev-nahian-kpi-sub"><?php esc_html_e( 'Average yield per reservation', 'dev-nahian-car' ); ?></span>
        </div>
        <div class="dev-nahian-kpi-card">
            <span class="dev-nahian-kpi-label"><?php esc_html_e( 'Tax / VAT Provision (Est. 8%)', 'dev-nahian-car' ); ?></span>
            <strong class="dev-nahian-kpi-val"><?php echo esc_html( $currency . ' ' . number_format_i18n( $tax_provision, 2 ) ); ?></strong>
            <span class="dev-nahian-kpi-sub"><?php esc_html_e( 'Estimated tax reserve calculation', 'dev-nahian-car' ); ?></span>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
        <input type="hidden" name="page" value="dev-nahian-fleet-manager">
        <input type="hidden" name="tab" value="reports">
        
        <div class="dev-nahian-filter-box">
            <div class="dev-nahian-filter-group">
                <span class="dev-nahian-filter-label"><?php esc_html_e( 'Filter Ledger:', 'dev-nahian-car' ); ?></span>
                <input type="date" name="filter_from" class="dev-nahian-filter-input" value="<?php echo esc_attr( $filter_from ); ?>">
                <span style="font-size: 12px; color: var(--muted);">&ndash;</span>
                <input type="date" name="filter_to" class="dev-nahian-filter-input" value="<?php echo esc_attr( $filter_to ); ?>">
                <select name="filter_car" class="dev-nahian-filter-select">
                    <option value=""><?php esc_html_e( 'All Vehicles', 'dev-nahian-car' ); ?></option>
                    <?php if ( ! empty( $cars_dropdown ) ) : ?>
                        <?php foreach ( $cars_dropdown as $car_name ) : ?>
                            <option value="<?php echo esc_attr( $car_name ); ?>" <?php selected( $filter_car, $car_name ); ?>>
                                <?php echo esc_html( $car_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="dev-nahian-filter-group">
                <button type="submit" class="button button-secondary"><?php esc_html_e( 'Apply Filter', 'dev-nahian-car' ); ?></button>
                <a href="<?php echo esc_url( $base ); ?>" class="button"><?php esc_html_e( 'Reset Filter', 'dev-nahian-car' ); ?></a>
            </div>
        </div>
    </form>

    <!-- Recent Audited Transactions Table -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--dark);"><?php esc_html_e( 'Recent Audited Settlements', 'dev-nahian-car' ); ?></h3>
        <span style="font-size: 12px; color: var(--muted);"><?php printf( esc_html__( '%s Records Loaded', 'dev-nahian-car' ), esc_html( number_format_i18n( count( $settlements ) ) ) ); ?></span>
    </div>

    <table class="wp-list-table widefat fixed striped" style="width:100%; border-radius:8px; overflow:hidden;">
        <thead>
            <tr>
                <th style="width: 100px;"><?php esc_html_e( 'Manifest ID', 'dev-nahian-car' ); ?></th>
                <th><?php esc_html_e( 'Client Name', 'dev-nahian-car' ); ?></th>
                <th><?php esc_html_e( 'Assigned Chassis', 'dev-nahian-car' ); ?></th>
                <th><?php esc_html_e( 'Window Dates', 'dev-nahian-car' ); ?></th>
                <th><?php esc_html_e( 'Rate Total', 'dev-nahian-car' ); ?></th>
                <th><?php esc_html_e( 'Payment Method', 'dev-nahian-car' ); ?></th>
                <th><?php esc_html_e( 'Audit Status', 'dev-nahian-car' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $settlements ) ) : ?>
                <?php foreach ( $settlements as $s ) : 
                    $status_key = sanitize_key( $s['booking_status'] );
                ?>
                    <tr>
                        <td><strong>#<?php echo esc_html( $s['booking_code'] ); ?></strong></td>
                        <td><?php echo esc_html( $s['customer_name'] ); ?></td>
                        <td><?php echo esc_html( ! empty( $s['car_name'] ) ? $s['car_name'] : '-' ); ?></td>
                        <td><?php echo esc_html( $s['pickup_date'] . ' – ' . $s['return_date'] ); ?></td>
                        <td><strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $s['total_amount'], 2 ) ); ?></strong></td>
                        <td><code><?php echo esc_html( strtoupper( $s['payment_method'] ) ); ?></code></td>
                        <td><span class="dev-nahian-status-pill <?php echo esc_attr( $status_key ); ?>">&bull; <?php echo esc_html( ucfirst( $status_key ) ); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No audited settlement records found matching the filter criteria.', 'dev-nahian-car' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>
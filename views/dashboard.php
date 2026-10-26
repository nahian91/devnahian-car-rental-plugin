<?php
/**
 * Dashboard View Tab: Operations & Dispatch Overview
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

// Get Current Logged-In User for Welcome Greeting
$current_user = wp_get_current_user();
$username     = ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->user_login;

// 1. Initialize Metrics
$total_vehicles  = 0;
$ready_vehicles  = 0;
$rented_vehicles = 0;
$maint_vehicles  = 0;

$total_bookings  = 0;
$today_bookings  = 0;
$today_income    = 0.00;

// Resolve WordPress site current local dates
$today_start = current_time( 'Y-m-d' ) . ' 00:00:00';
$today_end   = current_time( 'Y-m-d' ) . ' 23:59:59';
$today_date  = current_time( 'Y-m-d' );

// 2. Query Vehicle Fleet Analytics
$total_vehicles  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_cars}" );
$ready_vehicles  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_cars} WHERE status = %s", 'ready' ) );
$rented_vehicles = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_cars} WHERE status IN (%s, %s)", 'rented', 'active' ) );
$maint_vehicles  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_cars} WHERE status IN (%s, %s)", 'maintenance', 'in_workshop' ) );

// 3. Query Bookings & Today's Revenue
$total_bookings = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_bookings}" );

$today_count_query = $wpdb->prepare(
    "SELECT COUNT(id) FROM {$table_bookings} 
     WHERE pickup_date = %s 
        OR (created_at >= %s AND created_at <= %s)",
    $today_date,
    $today_start,
    $today_end
);
$today_bookings = (int) $wpdb->get_var( $today_count_query );

$today_income_query = $wpdb->prepare(
    "SELECT SUM(total_amount) FROM {$table_bookings} 
     WHERE created_at >= %s AND created_at <= %s",
    $today_start,
    $today_end
);
$today_income = (float) $wpdb->get_var( $today_income_query );

// 4. Compute Dynamic Rates
$active_ratio = ( $total_vehicles > 0 ) ? round( ( ( $rented_vehicles > 0 ? $rented_vehicles : $today_bookings ) / $total_vehicles ) * 100, 1 ) : 0;
$avg_yield    = ( $today_bookings > 0 ) ? ( $today_income / $today_bookings ) : 0.00;

// 5. Fetch Recent Dispatches / Bookings Manifest
$recent_dispatches = $wpdb->get_results( 
    "SELECT * FROM {$table_bookings} ORDER BY id DESC LIMIT 5", 
    ARRAY_A 
);
?>

<style>
    .dev-nahian-hud-panel {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        flex-wrap: wrap;
        gap: 16px;
    }
    .dev-nahian-hud-meta-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .dev-nahian-hud-badge-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .dev-nahian-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #b88e1e;
        background: #fefce8;
        border: 1px solid #fef08a;
        padding: 3px 10px;
        border-radius: 9999px;
    }
    .dev-nahian-status-chip .dev-nahian-radar-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #b88e1e;
        box-shadow: 0 0 0 3px rgba(184, 142, 30, 0.2);
        animation: devNahianPulse 2s infinite ease-in-out;
    }
    @keyframes devNahianPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.85); }
    }
    .dev-nahian-hud-heading {
        margin: 2px 0 0 0;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }
    .dev-nahian-hud-heading span {
        color: #0d9488;
    }
    .dev-nahian-hud-controls {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .dev-nahian-live-clock-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        padding: 5px 12px;
        border-radius: 8px;
        font-feature-settings: "tnum";
    }
    .dev-nahian-live-clock-badge svg {
        color: #0d9488;
    }
    .dev-nahian-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #0f172a;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .dev-nahian-btn-action:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .dev-nahian-btn-action.is-rotating svg {
        animation: devNahianSpin 0.75s ease;
    }
    @keyframes devNahianSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Primary Metric KPI Grid */
    .dev-nahian-metric-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    @media (max-width: 1200px) {
        .dev-nahian-metric-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .dev-nahian-metric-grid { grid-template-columns: 1fr; }
    }
    .dev-nahian-metric-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 22px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .dev-nahian-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.06);
    }
    .dev-nahian-metric-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 14px;
    }
    .dev-nahian-metric-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
        margin: 0;
    }
    .dev-nahian-metric-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .dev-nahian-metric-icon-box.gold  { background: #fefce8; border: 1px solid #fef08a; color: #b88e1e; }
    .dev-nahian-metric-icon-box.teal  { background: #f0fdfa; border: 1px solid #ccfbf1; color: #0d9488; }
    .dev-nahian-metric-icon-box.blue  { background: #eff6ff; border: 1px solid #dbeafe; color: #2563eb; }
    .dev-nahian-metric-icon-box.green { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

    .dev-nahian-metric-value-row {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 12px;
    }
    .dev-nahian-metric-number {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.03em;
        line-height: 1;
    }
    .dev-nahian-metric-tag {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        background: #f1f5f9;
        color: #475569;
    }
    .dev-nahian-metric-tag.trending-up {
        background: #f0fdf4;
        color: #16a34a;
    }
    .dev-nahian-metric-footer {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #475569;
        font-weight: 500;
    }
    .dev-nahian-metric-footer-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #cbd5e1;
    }
    .dev-nahian-metric-footer-dot.active { background: #0d9488; }

    /* Operational Performance Ribbon */
    .dev-nahian-summary-strip {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
    }
    @media (max-width: 768px) {
        .dev-nahian-summary-strip { grid-template-columns: 1fr; gap: 16px; }
    }
    .dev-nahian-strip-item {
        display: flex;
        flex-direction: column;
        padding: 0 16px;
        border-right: 1px solid #e2e8f0;
    }
    .dev-nahian-strip-item:last-child { border-right: none; }
    .dev-nahian-strip-label {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    .dev-nahian-strip-value {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
    }
    .dev-nahian-strip-meta {
        font-size: 11px;
        color: #475569;
        margin-top: 2px;
    }

    /* Dashboard Split Layout (Status Widget + Recent Table) */
    .dev-nahian-dashboard-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 24px;
    }
    @media (max-width: 1024px) {
        .dev-nahian-dashboard-grid { grid-template-columns: 1fr; }
    }
    .dev-nahian-card-pane {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .dev-nahian-card-title {
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #0f172a;
        margin: 0 0 16px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dev-nahian-status-breakdown {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .dev-nahian-status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    .dev-nahian-status-row-left {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
    }
    .dev-nahian-status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .dev-nahian-status-indicator.ready { background: #0d9488; }
    .dev-nahian-status-indicator.rented { background: #2563eb; }
    .dev-nahian-status-indicator.maint { background: #d97706; }
    
    .dev-nahian-status-count {
        font-size: 13px;
        font-weight: 800;
        color: #0f172a;
    }
</style>

<!-- Operational HUD Header -->
<div class="dev-nahian-hud-panel">
    <div class="dev-nahian-hud-meta-group">
        <div class="dev-nahian-hud-badge-row">
            <span class="dev-nahian-status-chip">
                <span class="dev-nahian-radar-dot"></span>
                <?php esc_html_e( 'System Active • v1.0', 'dev-nahian-car' ); ?>
            </span>
            <span style="font-size: 11px; color: #94a3b8;">&bull;</span>
            <span style="font-size: 12px; font-weight: 600; color: #475569;"><?php esc_html_e( 'Enterprise Fleet Terminal', 'dev-nahian-car' ); ?></span>
        </div>
        <h2 class="dev-nahian-hud-heading">
            <?php printf( esc_html__( 'Welcome, %s', 'dev-nahian-car' ), '<span>' . esc_html( $username ) . '</span>' ); ?>
        </h2>
    </div>
    <div class="dev-nahian-hud-controls">
        <!-- Live Synchronized Clock Readout -->
        <div class="dev-nahian-live-clock-badge" id="devNahianLiveClock" aria-label="<?php esc_attr_e( 'Live Local Time', 'dev-nahian-car' ); ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span id="devNahianClockText">--:--:-- --</span>
        </div>

        <button type="button" class="dev-nahian-btn-action" id="devNahianSyncDashboardBtn" title="<?php esc_attr_e( 'Refresh telemetry feed', 'dev-nahian-car' ); ?>">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
            </svg>
            <?php esc_html_e( 'Sync Gateway', 'dev-nahian-car' ); ?>
        </button>
    </div>
</div>

<!-- Primary Metric KPI Cards -->
<div class="dev-nahian-metric-grid">

    <!-- Total Vehicles -->
    <div class="dev-nahian-metric-card">
        <div class="dev-nahian-metric-header">
            <h3 class="dev-nahian-metric-title"><?php esc_html_e( 'Fleet Chassis Assets', 'dev-nahian-car' ); ?></h3>
            <div class="dev-nahian-metric-icon-box gold">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 17h14v-5l-2-6H7L5 12v5z"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/>
                </svg>
            </div>
        </div>
        <div class="dev-nahian-metric-value-row">
            <span class="dev-nahian-metric-number"><?php echo esc_html( number_format_i18n( $total_vehicles ) ); ?></span>
            <span class="dev-nahian-metric-tag"><?php esc_html_e( 'Units', 'dev-nahian-car' ); ?></span>
        </div>
        <div class="dev-nahian-metric-footer">
            <span class="dev-nahian-metric-footer-dot active"></span>
            <span><?php esc_html_e( 'Registered in Active Stable', 'dev-nahian-car' ); ?></span>
        </div>
    </div>

    <!-- Total Bookings -->
    <div class="dev-nahian-metric-card">
        <div class="dev-nahian-metric-header">
            <h3 class="dev-nahian-metric-title"><?php esc_html_e( 'All-Time Dispatches', 'dev-nahian-car' ); ?></h3>
            <div class="dev-nahian-metric-icon-box teal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="dev-nahian-metric-value-row">
            <span class="dev-nahian-metric-number"><?php echo esc_html( number_format_i18n( $total_bookings ) ); ?></span>
            <span class="dev-nahian-metric-tag"><?php esc_html_e( 'Manifests', 'dev-nahian-car' ); ?></span>
        </div>
        <div class="dev-nahian-metric-footer">
            <span class="dev-nahian-metric-footer-dot"></span>
            <span><?php esc_html_e( 'Total Recorded Bookings', 'dev-nahian-car' ); ?></span>
        </div>
    </div>

    <!-- Today Bookings -->
    <div class="dev-nahian-metric-card">
        <div class="dev-nahian-metric-header">
            <h3 class="dev-nahian-metric-title"><?php esc_html_e( "Today's Deployments", 'dev-nahian-car' ); ?></h3>
            <div class="dev-nahian-metric-icon-box blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
        </div>
        <div class="dev-nahian-metric-value-row">
            <span class="dev-nahian-metric-number"><?php echo esc_html( number_format_i18n( $today_bookings ) ); ?></span>
            <span class="dev-nahian-metric-tag trending-up"><?php echo esc_html( $active_ratio ); ?>% <?php esc_html_e( 'Load', 'dev-nahian-car' ); ?></span>
        </div>
        <div class="dev-nahian-metric-footer">
            <span class="dev-nahian-metric-footer-dot active"></span>
            <span><?php printf( esc_html__( 'Active Window: %s', 'dev-nahian-car' ), esc_html( date_i18n( get_option( 'date_format' ) ) ) ); ?></span>
        </div>
    </div>

    <!-- Today Income -->
    <div class="dev-nahian-metric-card">
        <div class="dev-nahian-metric-header">
            <h3 class="dev-nahian-metric-title"><?php esc_html_e( "Today's Revenue", 'dev-nahian-car' ); ?></h3>
            <div class="dev-nahian-metric-icon-box green">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
        </div>
        <div class="dev-nahian-metric-value-row">
            <span class="dev-nahian-metric-number"><?php echo esc_html( $currency . ' ' . number_format_i18n( $today_income, 2 ) ); ?></span>
            <span class="dev-nahian-metric-tag trending-up"><?php esc_html_e( 'Cleared', 'dev-nahian-car' ); ?></span>
        </div>
        <div class="dev-nahian-metric-footer">
            <span class="dev-nahian-metric-footer-dot active"></span>
            <span><?php esc_html_e( 'PCI Bank-Grade Handshake', 'dev-nahian-car' ); ?></span>
        </div>
    </div>

</div>

<!-- Operational Performance Ribbon -->
<div class="dev-nahian-summary-strip">
    <div class="dev-nahian-strip-item">
        <span class="dev-nahian-strip-label"><?php esc_html_e( 'Daily Fleet Utilization', 'dev-nahian-car' ); ?></span>
        <span class="dev-nahian-strip-value"><?php echo esc_html( $active_ratio ); ?>% <?php esc_html_e( 'Active Deploy', 'dev-nahian-car' ); ?></span>
        <span class="dev-nahian-strip-meta">
            <?php 
            printf( 
                esc_html__( '%1$s of %2$s units deployed today', 'dev-nahian-car' ), 
                esc_html( number_format_i18n( $today_bookings ) ), 
                esc_html( number_format_i18n( $total_vehicles ) ) 
            ); 
            ?>
        </span>
    </div>
    <div class="dev-nahian-strip-item">
        <span class="dev-nahian-strip-label"><?php esc_html_e( 'Average Asset Yield', 'dev-nahian-car' ); ?></span>
        <span class="dev-nahian-strip-value"><?php echo esc_html( $currency . ' ' . number_format_i18n( $avg_yield, 2 ) ); ?> / <?php esc_html_e( 'Booking', 'dev-nahian-car' ); ?></span>
        <span class="dev-nahian-strip-meta"><?php esc_html_e( 'Excluding security deposit holds', 'dev-nahian-car' ); ?></span>
    </div>
    <div class="dev-nahian-strip-item">
        <span class="dev-nahian-strip-label"><?php esc_html_e( 'Access Pass Readiness', 'dev-nahian-car' ); ?></span>
        <span class="dev-nahian-strip-value" style="color: #0d9488;"><?php esc_html_e( 'Instant Sync Active', 'dev-nahian-car' ); ?></span>
        <span class="dev-nahian-strip-meta"><?php esc_html_e( 'NFC cryptographic token engine operational', 'dev-nahian-car' ); ?></span>
    </div>
</div>

<!-- Secondary Split Grid: Asset Breakdown + Recent Dispatches -->
<div class="dev-nahian-dashboard-grid">

    <!-- Left Pane: Fleet Status Breakdown -->
    <div class="dev-nahian-card-pane">
        <h3 class="dev-nahian-card-title">
            <span><?php esc_html_e( 'Fleet Status', 'dev-nahian-car' ); ?></span>
            <span style="font-size: 11px; font-weight: 500; color: #64748b;"><?php echo esc_html( $total_vehicles ); ?> Total</span>
        </h3>
        <div class="dev-nahian-status-breakdown">
            <div class="dev-nahian-status-row">
                <div class="dev-nahian-status-row-left">
                    <span class="dev-nahian-status-indicator ready"></span>
                    <span><?php esc_html_e( 'Ready for Dispatch', 'dev-nahian-car' ); ?></span>
                </div>
                <span class="dev-nahian-status-count"><?php echo esc_html( number_format_i18n( $ready_vehicles ) ); ?></span>
            </div>
            <div class="dev-nahian-status-row">
                <div class="dev-nahian-status-row-left">
                    <span class="dev-nahian-status-indicator rented"></span>
                    <span><?php esc_html_e( 'On Active Rental', 'dev-nahian-car' ); ?></span>
                </div>
                <span class="dev-nahian-status-count"><?php echo esc_html( number_format_i18n( $rented_vehicles ) ); ?></span>
            </div>
            <div class="dev-nahian-status-row">
                <div class="dev-nahian-status-row-left">
                    <span class="dev-nahian-status-indicator maint"></span>
                    <span><?php esc_html_e( 'In Workshop / Maint.', 'dev-nahian-car' ); ?></span>
                </div>
                <span class="dev-nahian-status-count"><?php echo esc_html( number_format_i18n( $maint_vehicles ) ); ?></span>
            </div>
        </div>
    </div>

    <!-- Right Pane: Recent Dispatches Ledger -->
    <div class="dev-nahian-card-pane">
        <h3 class="dev-nahian-card-title">
            <span><?php esc_html_e( 'Recent Dispatch Manifests', 'dev-nahian-car' ); ?></span>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=dev-nahian-fleet-manager&tab=booking' ) ); ?>" style="font-size: 11px; text-decoration: none; color: #2563eb; font-weight: 700;"><?php esc_html_e( 'View All &rarr;', 'dev-nahian-car' ); ?></a>
        </h3>

        <table class="wp-list-table widefat fixed striped" style="width: 100%; border-radius: 8px; overflow: hidden; margin-top: 4px;">
            <thead>
                <tr>
                    <th style="width: 90px;"><?php esc_html_e( 'Code', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Client', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Vehicle Asset', 'dev-nahian-car' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'dev-nahian-car' ); ?></th>
                    <th style="text-align: right;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $recent_dispatches ) ) : ?>
                    <?php foreach ( $recent_dispatches as $rd ) : 
                        $status_key = sanitize_key( $rd['booking_status'] ?? 'active' );
                    ?>
                        <tr>
                            <td><code>#<?php echo esc_html( $rd['booking_code'] ); ?></code></td>
                            <td><strong><?php echo esc_html( $rd['customer_name'] ); ?></strong></td>
                            <td><?php echo esc_html( $rd['car_name'] ?: 'Fleet Asset' ); ?></td>
                            <td><strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $rd['total_amount'], 2 ) ); ?></strong></td>
                            <td style="text-align: right;">
                                <span class="dev-nahian-status-chip" style="font-size: 10px; padding: 2px 8px;">
                                    &bull; <?php echo esc_html( ucfirst( $status_key ) ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 24px; color: #64748b;"><?php esc_html_e( 'No dispatch manifests recorded yet.', 'dev-nahian-car' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Live Clock Ticker
    function updateLiveClock() {
        var clockEl = document.getElementById('devNahianClockText');
        if (clockEl) {
            var now = new Date();
            clockEl.textContent = now.toLocaleTimeString();
        }
    }
    updateLiveClock();
    setInterval(updateLiveClock, 1000);

    // 2. Gateway Sync Animation
    var syncBtn = document.getElementById('devNahianSyncDashboardBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', function() {
            syncBtn.classList.add('is-rotating');
            syncBtn.setAttribute('disabled', 'disabled');
            setTimeout(function() {
                window.location.reload();
            }, 350);
        });
    }
});
</script>
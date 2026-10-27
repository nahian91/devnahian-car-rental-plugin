<?php
/**
 * Dashboard View Tab: Operations & Dispatch Overview
 * Executive Operations Terminal with live telemetry, asset distribution gauges, and quick action hubs.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
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
$currency       = (string) get_option( 'dev_nahian_currency', 'USD' );

// Current Logged-In User greeting.
$current_user = wp_get_current_user();
$username     = ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->user_login;

// WordPress site localized date ranges.
$now         = current_datetime();
$today_date  = $now->format( 'Y-m-d' );
$today_start = $now->format( 'Y-m-d 00:00:00' );
$today_end   = $now->format( 'Y-m-d 23:59:59' );

// -----------------------------------------------------------------------------
// 1. Single-Query Aggregation for Vehicles
// -----------------------------------------------------------------------------
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$car_metrics = $wpdb->get_row(
	"SELECT 
		COUNT(id) AS total_vehicles,
		SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) AS ready_vehicles,
		SUM(CASE WHEN status IN ('rented', 'active') THEN 1 ELSE 0 END) AS rented_vehicles,
		SUM(CASE WHEN status IN ('maintenance', 'in_workshop') THEN 1 ELSE 0 END) AS maint_vehicles
	FROM {$table_cars}",
	ARRAY_A
);

$total_vehicles  = (int) ( $car_metrics['total_vehicles'] ?? 0 );
$ready_vehicles  = (int) ( $car_metrics['ready_vehicles'] ?? 0 );
$rented_vehicles = (int) ( $car_metrics['rented_vehicles'] ?? 0 );
$maint_vehicles  = (int) ( $car_metrics['maint_vehicles'] ?? 0 );

// -----------------------------------------------------------------------------
// 2. Query Bookings & Today's Performance
// -----------------------------------------------------------------------------
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_bookings = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_bookings}" );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$today_bookings = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(id) FROM {$table_bookings} 
		WHERE pickup_date = %s 
		   OR (created_at >= %s AND created_at <= %s)",
		$today_date,
		$today_start,
		$today_end
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$today_income = (float) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COALESCE(SUM(total_amount), 0) FROM {$table_bookings} 
		WHERE created_at >= %s AND created_at <= %s AND booking_status != 'cancelled'",
		$today_start,
		$today_end
	)
);

// -----------------------------------------------------------------------------
// 3. Computed Rates & Utilization Telemetry
// -----------------------------------------------------------------------------
$active_ratio = ( $total_vehicles > 0 ) ? round( ( ( $rented_vehicles > 0 ? $rented_vehicles : $today_bookings ) / $total_vehicles ) * 100, 1 ) : 0.0;
$active_ratio = min( 100.0, max( 0.0, $active_ratio ) );
$avg_yield    = ( $today_bookings > 0 ) ? ( $today_income / $today_bookings ) : 0.00;

// SVG Donut Circle Math (Radius = 38, Circumference = 2 * PI * 38 ≈ 238.76).
$circumference = 238.76;
$dash_offset   = $circumference - ( ( $active_ratio / 100 ) * $circumference );

// -----------------------------------------------------------------------------
// 4. Fetch Recent Dispatches / Bookings Manifest
// -----------------------------------------------------------------------------
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$recent_dispatches = $wpdb->get_results(
	"SELECT * FROM {$table_bookings} ORDER BY id DESC LIMIT 6",
	ARRAY_A
);
?>

<style>
	/* =========================================================================
	   Dev Nahian Design System Tokens & Base Scope
	   ========================================================================= */
	:root {
		--dnf-primary: #0f172a;
		--dnf-primary-light: #1e293b;
		--dnf-accent-teal: #0d9488;
		--dnf-accent-teal-glow: rgba(13, 148, 136, 0.15);
		--dnf-accent-gold: #b88e1e;
		--dnf-accent-blue: #2563eb;
		--dnf-accent-green: #16a34a;
		--dnf-accent-red: #dc2626;
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
		--dnf-shadow-lg: 0 10px 25px -4px rgba(15, 23, 42, 0.08);
	}

	.dnf-dashboard-terminal {
		display: flex;
		flex-direction: column;
		gap: 24px;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
		color: var(--dnf-text-primary);
		-webkit-font-smoothing: antialiased;
		margin-top: 10px;
	}

	/* HUD Header Panel */
	.dnf-hud-banner {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
		border-radius: var(--dnf-radius-lg);
		padding: 28px 32px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		color: #ffffff;
		box-shadow: 0 12px 30px -6px rgba(15, 23, 42, 0.18);
		position: relative;
		overflow: hidden;
		flex-wrap: wrap;
		gap: 20px;
	}
	.dnf-hud-banner::after {
		content: "";
		position: absolute;
		right: -60px;
		top: -60px;
		width: 240px;
		height: 240px;
		background: radial-gradient(circle, rgba(13, 148, 136, 0.25) 0%, rgba(15, 23, 42, 0) 70%);
		border-radius: 50%;
		pointer-events: none;
	}
	.dnf-hud-meta {
		display: flex;
		flex-direction: column;
		gap: 8px;
		z-index: 1;
	}
	.dnf-radar-pill {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		background: rgba(255, 255, 255, 0.08);
		border: 1px solid rgba(255, 255, 255, 0.14);
		padding: 4px 12px;
		border-radius: var(--dnf-radius-pill);
		width: fit-content;
		backdrop-filter: blur(8px);
	}
	.dnf-radar-pulse {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: #10b981;
		box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
		animation: dnfPulseEffect 2s infinite;
	}
	@keyframes dnfPulseEffect {
		0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
		70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
		100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
	}
	.dnf-hud-title {
		margin: 0;
		font-size: 26px;
		font-weight: 800;
		letter-spacing: -0.03em;
		line-height: 1.2;
		color: #ffffff;
	}
	.dnf-hud-title span {
		color: #38bdf8;
	}
	.dnf-hud-subtitle {
		margin: 0;
		font-size: 13px;
		color: #94a3b8;
		font-weight: 500;
	}
	.dnf-hud-actions {
		display: flex;
		align-items: center;
		gap: 12px;
		z-index: 1;
	}
	.dnf-clock-chip {
		display: flex;
		align-items: center;
		gap: 8px;
		background: rgba(255, 255, 255, 0.07);
		border: 1px solid rgba(255, 255, 255, 0.12);
		padding: 8px 16px;
		border-radius: var(--dnf-radius-md);
		font-size: 13px;
		font-weight: 700;
		font-feature-settings: "tnum";
		color: #f8fafc;
	}
	.dnf-clock-chip svg {
		color: #38bdf8;
	}
	.dnf-hud-refresh-btn {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 9px 18px;
		background: #ffffff;
		color: #0f172a;
		border: none;
		border-radius: var(--dnf-radius-md);
		font-size: 13px;
		font-weight: 700;
		cursor: pointer;
		transition: all 0.2s ease;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
	}
	.dnf-hud-refresh-btn:hover {
		background: #f1f5f9;
		transform: translateY(-1px);
	}
	.dnf-hud-refresh-btn.is-syncing svg {
		animation: dnfSpin 0.75s linear infinite;
	}
	@keyframes dnfSpin {
		from { transform: rotate(0deg); }
		to { transform: rotate(360deg); }
	}

	/* Quick Action Operator Strip */
	.dnf-quick-actions-bar {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 14px;
	}
	@media (max-width: 900px) {
		.dnf-quick-actions-bar { grid-template-columns: repeat(2, 1fr); }
	}
	@media (max-width: 480px) {
		.dnf-quick-actions-bar { grid-template-columns: 1fr; }
	}
	.dnf-quick-btn {
		display: flex;
		align-items: center;
		gap: 12px;
		background: #ffffff;
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-md);
		padding: 12px 16px;
		text-decoration: none;
		color: var(--dnf-text-primary);
		transition: all 0.2s ease;
		box-shadow: var(--dnf-shadow-sm);
	}
	.dnf-quick-btn:hover {
		border-color: var(--dnf-border-hover);
		transform: translateY(-2px);
		box-shadow: var(--dnf-shadow-md);
		color: var(--dnf-accent-teal);
	}
	.dnf-quick-icon {
		width: 36px;
		height: 36px;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
	}
	.dnf-quick-icon.teal { background: #f0fdfa; color: #0d9488; }
	.dnf-quick-icon.blue { background: #eff6ff; color: #2563eb; }
	.dnf-quick-icon.gold { background: #fefce8; color: #b88e1e; }
	.dnf-quick-icon.purple { background: #f5f3ff; color: #7c3aed; }
	.dnf-quick-text-title {
		font-size: 13px;
		font-weight: 700;
		line-height: 1.2;
		display: block;
	}
	.dnf-quick-text-sub {
		font-size: 11px;
		color: var(--dnf-text-tertiary);
		font-weight: 500;
	}

	/* Primary Metric KPI Grid */
	.dnf-metric-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 18px;
	}
	@media (max-width: 1200px) {
		.dnf-metric-grid { grid-template-columns: repeat(2, 1fr); }
	}
	@media (max-width: 640px) {
		.dnf-metric-grid { grid-template-columns: 1fr; }
	}
	.dnf-metric-card {
		background: var(--dnf-bg-card);
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-lg);
		padding: 24px;
		box-shadow: var(--dnf-shadow-sm);
		transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		display: flex;
		flex-direction: column;
		justify-content: space-between;
	}
	.dnf-metric-card:hover {
		border-color: var(--dnf-border-hover);
		transform: translateY(-3px);
		box-shadow: var(--dnf-shadow-md);
	}
	.dnf-metric-top {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 16px;
	}
	.dnf-metric-badge {
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: var(--dnf-text-secondary);
	}
	.dnf-metric-avatar {
		width: 40px;
		height: 40px;
		border-radius: var(--dnf-radius-md);
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.dnf-metric-avatar.gold { background: #fefce8; color: #b88e1e; border: 1px solid #fef08a; }
	.dnf-metric-avatar.teal { background: #f0fdfa; color: #0d9488; border: 1px solid #ccfbf1; }
	.dnf-metric-avatar.blue { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
	.dnf-metric-avatar.green { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

	.dnf-metric-num-wrap {
		display: flex;
		align-items: baseline;
		gap: 8px;
		margin-bottom: 8px;
	}
	.dnf-metric-number {
		font-size: 32px;
		font-weight: 800;
		letter-spacing: -0.03em;
		color: var(--dnf-text-primary);
		line-height: 1;
		font-feature-settings: "tnum";
	}
	.dnf-metric-growth {
		font-size: 11.5px;
		font-weight: 700;
		padding: 2px 8px;
		border-radius: var(--dnf-radius-pill);
		display: inline-flex;
		align-items: center;
		gap: 3px;
	}
	.dnf-metric-growth.positive {
		background: #f0fdf4;
		color: #16a34a;
		border: 1px solid #dcfce7;
	}
	.dnf-metric-bottom {
		display: flex;
		align-items: center;
		justify-content: space-between;
		font-size: 12px;
		color: var(--dnf-text-secondary);
		padding-top: 12px;
		border-top: 1px solid #f1f5f9;
		margin-top: 8px;
	}

	/* Main Split Workstation (Donut Gauge + Real-Time Manifest) */
	.dnf-workstation-grid {
		display: grid;
		grid-template-columns: 360px 1fr;
		gap: 24px;
	}
	@media (max-width: 1100px) {
		.dnf-workstation-grid { grid-template-columns: 1fr; }
	}

	.dnf-panel {
		background: var(--dnf-bg-card);
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-lg);
		padding: 26px;
		box-shadow: var(--dnf-shadow-sm);
	}
	.dnf-panel-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20px;
	}
	.dnf-panel-title {
		margin: 0;
		font-size: 14px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-primary);
	}
	.dnf-panel-action {
		font-size: 12.5px;
		font-weight: 700;
		color: var(--dnf-accent-blue);
		text-decoration: none;
		transition: color 0.15s ease;
	}
	.dnf-panel-action:hover {
		color: #1d4ed8;
		text-decoration: underline;
	}

	/* Gauge Visualization Box */
	.dnf-gauge-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 10px 0 20px;
	}
	.dnf-gauge-svg {
		width: 140px;
		height: 140px;
		transform: rotate(-90deg);
	}
	.dnf-gauge-bg {
		fill: none;
		stroke: #f1f5f9;
		stroke-width: 8;
	}
	.dnf-gauge-val {
		fill: none;
		stroke: var(--dnf-accent-teal);
		stroke-width: 8;
		stroke-linecap: round;
		stroke-dasharray: 238.76;
		transition: stroke-dashoffset 1.2s cubic-bezier(0.16, 1, 0.3, 1);
	}
	.dnf-gauge-center-text {
		position: absolute;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
	}
	.dnf-gauge-percent {
		font-size: 26px;
		font-weight: 800;
		color: var(--dnf-text-primary);
		letter-spacing: -0.02em;
	}
	.dnf-gauge-sub {
		font-size: 10px;
		font-weight: 700;
		text-transform: uppercase;
		color: var(--dnf-text-tertiary);
		letter-spacing: 0.05em;
	}

	/* Status Breakdown Rows */
	.dnf-gauge-breakdown {
		display: flex;
		flex-direction: column;
		gap: 10px;
		width: 100%;
		margin-top: 10px;
	}
	.dnf-breakdown-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 12px 16px;
		background: var(--dnf-bg-subtle);
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-md);
	}
	.dnf-item-left {
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: 13px;
		font-weight: 600;
		color: var(--dnf-text-secondary);
	}
	.dnf-circle-dot {
		width: 8px;
		height: 8px;
		border-radius: 50%;
	}
	.dnf-circle-dot.ready { background: var(--dnf-accent-teal); box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
	.dnf-circle-dot.rented { background: var(--dnf-accent-blue); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
	.dnf-circle-dot.maint { background: var(--dnf-accent-gold); box-shadow: 0 0 0 3px rgba(184, 142, 30, 0.15); }
	.dnf-item-count {
		font-size: 13.5px;
		font-weight: 800;
		color: var(--dnf-text-primary);
		font-feature-settings: "tnum";
	}

	/* Manifest Table Modern Styling */
	.dnf-table-wrap {
		width: 100%;
		overflow-x: auto;
		border: 1px solid var(--dnf-border);
		border-radius: var(--dnf-radius-md);
	}
	.dnf-dispatch-table {
		width: 100%;
		border-collapse: collapse;
		text-align: left;
	}
	.dnf-dispatch-table th {
		background: var(--dnf-bg-subtle);
		padding: 14px 16px;
		font-size: 11px;
		text-transform: uppercase;
		font-weight: 700;
		letter-spacing: 0.05em;
		color: var(--dnf-text-secondary);
		border-bottom: 1px solid var(--dnf-border);
	}
	.dnf-dispatch-table td {
		padding: 14px 16px;
		font-size: 13px;
		color: var(--dnf-text-primary);
		border-bottom: 1px solid #f1f5f9;
		vertical-align: middle;
	}
	.dnf-dispatch-table tr:last-child td {
		border-bottom: none;
	}
	.dnf-dispatch-table tr:hover td {
		background: #fafbfc;
	}

	.dnf-manifest-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 11.5px;
		font-weight: 700;
		color: var(--dnf-text-primary);
		background: #f1f5f9;
		padding: 3px 8px;
		border-radius: var(--dnf-radius-sm);
		border: 1px solid #e2e8f0;
	}
	.dnf-customer-name {
		font-weight: 700;
		color: var(--dnf-text-primary);
	}
	.dnf-chassis-label {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-weight: 600;
		color: var(--dnf-text-secondary);
	}
	.dnf-status-pill {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		font-size: 11px;
		font-weight: 700;
		padding: 3px 10px;
		border-radius: var(--dnf-radius-pill);
		text-transform: uppercase;
		letter-spacing: 0.04em;
	}
	.dnf-status-pill.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
	.dnf-status-pill.warning { background: #fffbeb; color: #b45309; border: 1px solid #fef3c7; }
	.dnf-status-pill.danger  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
	.dnf-empty-row {
		text-align: center;
		padding: 36px 16px;
		color: var(--dnf-text-tertiary);
		font-size: 13px;
	}
</style>

<div class="dnf-dashboard-terminal">

	<!-- 1. Executive Telematics HUD Header -->
	<div class="dnf-hud-banner">
		<div class="dnf-hud-meta">
			<div class="dnf-radar-pill">
				<span class="dnf-radar-pulse"></span>
				<?php esc_html_e( 'Telemetry Stream Live', 'dev-nahian-car' ); ?>
			</div>
			<h1 class="dnf-hud-title">
				<?php
				printf(
					/* translators: %s: username */
					esc_html__( 'Executive Terminal &mdash; %s', 'dev-nahian-car' ),
					'<span>' . esc_html( $username ) . '</span>'
				);
				?>
			</h1>
			<p class="dnf-hud-subtitle">
				<?php esc_html_e( 'Real-time asset telemetry, dispatch load ratios, and active reservation yield.', 'dev-nahian-car' ); ?>
			</p>
		</div>

		<div class="dnf-hud-actions">
			<div class="dnf-clock-chip" id="dnfTerminalClock" aria-label="<?php esc_attr_e( 'Terminal Local Time', 'dev-nahian-car' ); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
				<span id="dnfClockDigits">--:--:--</span>
			</div>
			<button type="button" class="dnf-hud-refresh-btn" id="dnfSyncTerminalBtn" title="<?php esc_attr_e( 'Sync Gateway Feed', 'dev-nahian-car' ); ?>">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
				</svg>
				<span><?php esc_html_e( 'Sync Gateway', 'dev-nahian-car' ); ?></span>
			</button>
		</div>
	</div>

	<!-- 2. Fast Operator Dispatch Shortcuts -->
	<div class="dnf-quick-actions-bar">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=dev-nahian-fleet-manager&tab=booking&subtab=add' ) ); ?>" class="dnf-quick-btn">
			<div class="dnf-quick-icon teal">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>
			</div>
			<div>
				<span class="dnf-quick-text-title"><?php esc_html_e( 'Create Booking', 'dev-nahian-car' ); ?></span>
				<span class="dnf-quick-text-sub"><?php esc_html_e( 'Dispatch reservation', 'dev-nahian-car' ); ?></span>
			</div>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=dev-nahian-fleet-manager&tab=cars&action=add' ) ); ?>" class="dnf-quick-btn">
			<div class="dnf-quick-icon blue">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 17h14v-5l-2-6H7L5 12v5z"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
			</div>
			<div>
				<span class="dnf-quick-text-title"><?php esc_html_e( 'Register Vehicle', 'dev-nahian-car' ); ?></span>
				<span class="dnf-quick-text-sub"><?php esc_html_e( 'Add fleet asset', 'dev-nahian-car' ); ?></span>
			</div>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=dev-nahian-fleet-manager&tab=maintenance&subtab=add' ) ); ?>" class="dnf-quick-btn">
			<div class="dnf-quick-icon gold">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
			</div>
			<div>
				<span class="dnf-quick-text-title"><?php esc_html_e( 'Service Order', 'dev-nahian-car' ); ?></span>
				<span class="dnf-quick-text-sub"><?php esc_html_e( 'Log maintenance', 'dev-nahian-car' ); ?></span>
			</div>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=dev-nahian-fleet-manager&tab=inspections&subtab=add' ) ); ?>" class="dnf-quick-btn">
			<div class="dnf-quick-icon purple">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
			</div>
			<div>
				<span class="dnf-quick-text-title"><?php esc_html_e( 'Condition Walkaround', 'dev-nahian-car' ); ?></span>
				<span class="dnf-quick-text-sub"><?php esc_html_e( 'File inspection', 'dev-nahian-car' ); ?></span>
			</div>
		</a>
	</div>

	<!-- 3. Primary Metric KPI Grid -->
	<div class="dnf-metric-grid">
		<!-- Total Vehicles -->
		<div class="dnf-metric-card">
			<div class="dnf-metric-top">
				<span class="dnf-metric-badge"><?php esc_html_e( 'Fleet Inventory', 'dev-nahian-car' ); ?></span>
				<div class="dnf-metric-avatar gold">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 17h14v-5l-2-6H7L5 12v5z"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
				</div>
			</div>
			<div class="dnf-metric-num-wrap">
				<span class="dnf-metric-number"><?php echo esc_html( (string) number_format_i18n( $total_vehicles ) ); ?></span>
				<span class="dnf-metric-growth positive">
					<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
					100%
				</span>
			</div>
			<div class="dnf-metric-bottom">
				<span><?php esc_html_e( 'Total Units Registered', 'dev-nahian-car' ); ?></span>
				<strong><?php echo esc_html( (string) $ready_vehicles ); ?> <?php esc_html_e( 'Available', 'dev-nahian-car' ); ?></strong>
			</div>
		</div>

		<!-- All-Time Bookings -->
		<div class="dnf-metric-card">
			<div class="dnf-metric-top">
				<span class="dnf-metric-badge"><?php esc_html_e( 'Total Manifests', 'dev-nahian-car' ); ?></span>
				<div class="dnf-metric-avatar teal">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
				</div>
			</div>
			<div class="dnf-metric-num-wrap">
				<span class="dnf-metric-number"><?php echo esc_html( (string) number_format_i18n( $total_bookings ) ); ?></span>
				<span class="dnf-metric-growth positive"><?php esc_html_e( 'Lifetime', 'dev-nahian-car' ); ?></span>
			</div>
			<div class="dnf-metric-bottom">
				<span><?php esc_html_e( 'Historical Trip Bookings', 'dev-nahian-car' ); ?></span>
				<strong><?php esc_html_e( 'Cleared', 'dev-nahian-car' ); ?></strong>
			</div>
		</div>

		<!-- Today Bookings -->
		<div class="dnf-metric-card">
			<div class="dnf-metric-top">
				<span class="dnf-metric-badge"><?php esc_html_e( "Today's Deployments", 'dev-nahian-car' ); ?></span>
				<div class="dnf-metric-avatar blue">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
				</div>
			</div>
			<div class="dnf-metric-num-wrap">
				<span class="dnf-metric-number"><?php echo esc_html( (string) number_format_i18n( $today_bookings ) ); ?></span>
				<span class="dnf-metric-growth positive"><?php echo esc_html( (string) $active_ratio ); ?>% <?php esc_html_e( 'Load', 'dev-nahian-car' ); ?></span>
			</div>
			<div class="dnf-metric-bottom">
				<span><?php esc_html_e( 'Pickup & Created Today', 'dev-nahian-car' ); ?></span>
				<strong><?php echo esc_html( (string) $rented_vehicles ); ?> <?php esc_html_e( 'In Motion', 'dev-nahian-car' ); ?></strong>
			</div>
		</div>

		<!-- Today Revenue -->
		<div class="dnf-metric-card">
			<div class="dnf-metric-top">
				<span class="dnf-metric-badge"><?php esc_html_e( "Today's Net Revenue", 'dev-nahian-car' ); ?></span>
				<div class="dnf-metric-avatar green">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
				</div>
			</div>
			<div class="dnf-metric-num-wrap">
				<span class="dnf-metric-number"><?php echo esc_html( $currency . ' ' . number_format_i18n( $today_income, 2 ) ); ?></span>
			</div>
			<div class="dnf-metric-bottom">
				<span><?php esc_html_e( 'Average Yield Per Trip', 'dev-nahian-car' ); ?></span>
				<strong><?php echo esc_html( $currency . ' ' . number_format_i18n( $avg_yield, 2 ) ); ?></strong>
			</div>
		</div>
	</div>

	<!-- 4. Workstation: Asset Donut Telematics + Recent Manifests -->
	<div class="dnf-workstation-grid">
		<!-- Left Panel: Live Utilization Gauge & Status Breakdown -->
		<div class="dnf-panel">
			<div class="dnf-panel-header">
				<h3 class="dnf-panel-title"><?php esc_html_e( 'Fleet Telematics Load', 'dev-nahian-car' ); ?></h3>
				<span style="font-size:12px; font-weight:700; color:var(--dnf-text-tertiary);"><?php echo esc_html( (string) $total_vehicles ); ?> <?php esc_html_e( 'Chassis', 'dev-nahian-car' ); ?></span>
			</div>

			<div class="dnf-gauge-container">
				<div style="position:relative; display:flex; align-items:center; justify-content:center;">
					<svg class="dnf-gauge-svg" viewBox="0 0 100 100">
						<circle class="dnf-gauge-bg" cx="50" cy="50" r="38"/>
						<circle class="dnf-gauge-val" id="dnfUtilizationCircle" cx="50" cy="50" r="38" style="stroke-dashoffset: <?php echo esc_attr( (string) $dash_offset ); ?>;"/>
					</svg>
					<div class="dnf-gauge-center-text">
						<span class="dnf-gauge-percent"><?php echo esc_html( (string) $active_ratio ); ?>%</span>
						<span class="dnf-gauge-sub"><?php esc_html_e( 'Deployed', 'dev-nahian-car' ); ?></span>
					</div>
				</div>
			</div>

			<div class="dnf-gauge-breakdown">
				<div class="dnf-breakdown-item">
					<div class="dnf-item-left">
						<span class="dnf-circle-dot ready"></span>
						<span><?php esc_html_e( 'Available / Staged', 'dev-nahian-car' ); ?></span>
					</div>
					<span class="dnf-item-count"><?php echo esc_html( (string) number_format_i18n( $ready_vehicles ) ); ?></span>
				</div>
				<div class="dnf-breakdown-item">
					<div class="dnf-item-left">
						<span class="dnf-circle-dot rented"></span>
						<span><?php esc_html_e( 'Active Rental Deployments', 'dev-nahian-car' ); ?></span>
					</div>
					<span class="dnf-item-count"><?php echo esc_html( (string) number_format_i18n( $rented_vehicles ) ); ?></span>
				</div>
				<div class="dnf-breakdown-item">
					<div class="dnf-item-left">
						<span class="dnf-circle-dot maint"></span>
						<span><?php esc_html_e( 'Maintenance & Detailing', 'dev-nahian-car' ); ?></span>
					</div>
					<span class="dnf-item-count"><?php echo esc_html( (string) number_format_i18n( $maint_vehicles ) ); ?></span>
				</div>
			</div>
		</div>

		<!-- Right Panel: Recent Dispatch Manifests -->
		<div class="dnf-panel">
			<div class="dnf-panel-header">
				<h3 class="dnf-panel-title"><?php esc_html_e( 'Recent Dispatch Manifests', 'dev-nahian-car' ); ?></h3>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dev-nahian-fleet-manager&tab=booking' ) ); ?>" class="dnf-panel-action">
					<?php esc_html_e( 'View All Bookings &rarr;', 'dev-nahian-car' ); ?>
				</a>
			</div>

			<div class="dnf-table-wrap">
				<table class="dnf-dispatch-table">
					<thead>
						<tr>
							<th style="width: 110px;"><?php esc_html_e( 'Manifest Code', 'dev-nahian-car' ); ?></th>
							<th><?php esc_html_e( 'Client Name', 'dev-nahian-car' ); ?></th>
							<th><?php esc_html_e( 'Allocated Vehicle', 'dev-nahian-car' ); ?></th>
							<th><?php esc_html_e( 'Invoiced Amount', 'dev-nahian-car' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $recent_dispatches ) ) : ?>
							<?php foreach ( $recent_dispatches as $rd ) :
								$raw_status   = sanitize_key( $rd['booking_status'] ?? 'pending' );
								$status_class = in_array( $raw_status, array( 'completed', 'active', 'paid' ), true ) ? 'success' : ( 'cancelled' === $raw_status ? 'danger' : 'warning' );
								$booking_url  = admin_url( 'admin.php?page=dev-nahian-fleet-manager&tab=booking&action=view&id=' . absint( $rd['id'] ) );
								?>
								<tr>
									<td>
										<a href="<?php echo esc_url( $booking_url ); ?>" style="text-decoration:none;">
											<code class="dnf-manifest-code">#<?php echo esc_html( (string) ( $rd['booking_code'] ?? $rd['id'] ) ); ?></code>
										</a>
									</td>
									<td>
										<span class="dnf-customer-name"><?php echo esc_html( (string) ( $rd['customer_name'] ?? '—' ) ); ?></span>
									</td>
									<td>
										<span class="dnf-chassis-label">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17h14v-5l-2-6H7L5 12v5z"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
											<?php echo esc_html( ! empty( $rd['car_name'] ) ? $rd['car_name'] : __( 'Fleet Asset', 'dev-nahian-car' ) ); ?>
										</span>
									</td>
									<td>
										<strong style="color:var(--dnf-text-primary); font-feature-settings:'tnum';">
											<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $rd['total_amount'] ?? 0 ), 2 ) ); ?>
										</strong>
									</td>
									<td style="text-align: right;">
										<span class="dnf-status-pill <?php echo esc_attr( $status_class ); ?>">
											&bull; <?php echo esc_html( ucfirst( $raw_status ) ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5" class="dnf-empty-row">
									<?php esc_html_e( 'No recent bookings recorded in the system ledger.', 'dev-nahian-car' ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

</div>

<!-- 5. Internal Vanilla JS Telemetry Controller -->
<script>
(function() {
	'use strict';

	// 1. Live Terminal Synchronized Clock
	function startTerminalClock() {
		var clockEl = document.getElementById('dnfClockDigits');
		if (!clockEl) {
			return;
		}

		function tick() {
			var d = new Date();
			clockEl.textContent = d.toLocaleTimeString([], {
				hour: '2-digit',
				minute: '2-digit',
				second: '2-digit'
			});
		}

		tick();
		setInterval(tick, 1000);
	}

	// 2. Gateway Refresh Action
	function initSyncTrigger() {
		var btn = document.getElementById('dnfSyncTerminalBtn');
		if (!btn) {
			return;
		}

		btn.addEventListener('click', function(e) {
			e.preventDefault();
			btn.classList.add('is-syncing');
			btn.setAttribute('disabled', 'disabled');

			setTimeout(function() {
				window.location.reload();
			}, 350);
		});
	}

	// Initialize on DOM load
	document.addEventListener('DOMContentLoaded', function() {
		startTerminalClock();
		initSyncTrigger();
	});
})();
</script>
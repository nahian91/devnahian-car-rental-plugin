<?php
/**
 * Admin View Tab: Financial Reports & Operational Audits
 * Comprehensive executive reporting ledger, KPI analytics, dynamic filters, and Chart.js telemetry.
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

// 1. Safe Scope Fallbacks.
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$table_cars     = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$currency       = (string) get_option( 'dev_nahian_currency', 'USD' );

$base = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'reports',
	),
	admin_url( 'admin.php' )
);

// CSV Export URL with nonce.
$export_bookings_url = wp_nonce_url(
	add_query_arg(
		array(
			'action'      => 'dev_nahian_export_csv',
			'export_type' => 'bookings',
		),
		admin_url( 'admin-post.php' )
	),
	'dev_nahian_csv_nonce'
);

// 2. High-Performance KPI Aggregation.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$kpi_summary = $wpdb->get_row(
	"SELECT 
		COALESCE(SUM(CASE WHEN booking_status != 'cancelled' THEN total_amount ELSE 0 END), 0) AS gross_revenue,
		COUNT(CASE WHEN booking_status IN ('completed', 'active') THEN 1 END) AS settled_bookings
	FROM {$table_bookings}",
	ARRAY_A
);

$gross_revenue    = (float) ( $kpi_summary['gross_revenue'] ?? 0.0 );
$settled_bookings = (int) ( $kpi_summary['settled_bookings'] ?? 0 );
$avg_order_value  = ( $settled_bookings > 0 ) ? ( $gross_revenue / $settled_bookings ) : 0.00;
$tax_provision    = $gross_revenue * 0.08; // Estimated 8% Tax / VAT reserve.

// 3. Chart Analytics Aggregations.
// Monthly Revenue Progression (Past 6 Months).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$monthly_trends = $wpdb->get_results(
	"SELECT 
		DATE_FORMAT(pickup_date, '%Y-%m') AS ym,
		COALESCE(SUM(total_amount), 0) AS total_rev,
		COUNT(id) AS trips_count
	FROM {$table_bookings}
	WHERE booking_status != 'cancelled' AND pickup_date IS NOT NULL
	GROUP BY ym
	ORDER BY ym ASC
	LIMIT 6",
	ARRAY_A
);

$chart_months   = array();
$chart_revenues = array();
$chart_trips    = array();

foreach ( $monthly_trends as $trend ) {
	$ts               = strtotime( (string) $trend['ym'] . '-01' );
	$chart_months[]   = $ts ? wp_date( 'M Y', $ts ) : (string) $trend['ym'];
	$chart_revenues[] = round( (float) $trend['total_rev'], 2 );
	$chart_trips[]    = (int) $trend['trips_count'];
}

// Top Chassis by Yield Revenue (Top 5 Assets).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$top_chassis_results = $wpdb->get_results(
	"SELECT 
		car_name,
		COALESCE(SUM(total_amount), 0) AS chassis_rev
	FROM {$table_bookings}
	WHERE booking_status != 'cancelled' AND car_name != ''
	GROUP BY car_name
	ORDER BY chassis_rev DESC
	LIMIT 5",
	ARRAY_A
);

$chassis_labels = array();
$chassis_values = array();

foreach ( $top_chassis_results as $c_row ) {
	$chassis_labels[] = (string) $c_row['car_name'];
	$chassis_values[] = round( (float) $c_row['chassis_rev'], 2 );
}

// 4. Filtering Parameters for Settlement Ledger.
$filter_from = isset( $_GET['filter_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_from'] ) ) : '';
$filter_to   = isset( $_GET['filter_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_to'] ) ) : '';
$filter_car  = isset( $_GET['filter_car'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_car'] ) ) : '';

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

$where_sql = implode( ' AND ', $where_clauses );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$settlements = $wpdb->get_results( "SELECT * FROM {$table_bookings} WHERE {$where_sql} ORDER BY id DESC LIMIT 50", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Fetch vehicles for dropdown.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cars_dropdown = $wpdb->get_col( "SELECT DISTINCT name FROM {$table_cars} ORDER BY name ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
?>

<style>
	/* Scoped Reports Styles */
	.dnf-rep-scope {
		display: flex;
		flex-direction: column;
		gap: 24px;
	}

	/* Top Executive Header */
	.dnf-rep-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--dnf-bg-card, #ffffff);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 20px 24px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-rep-title-area {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-rep-heading {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
	}

	/* KPI Analytics Summary Grid */
	.dnf-kpi-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 16px;
	}
	@media (max-width: 1100px) {
		.dnf-kpi-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 600px) {
		.dnf-kpi-grid {
			grid-template-columns: 1fr;
		}
	}
	.dnf-kpi-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 20px 22px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-kpi-label {
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-secondary, #64748b);
	}
	.dnf-kpi-val {
		font-size: 24px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
		line-height: 1.1;
		font-feature-settings: 'tnum';
	}
	.dnf-kpi-sub {
		font-size: 11.5px;
		color: var(--dnf-accent-teal, #0d9488);
		font-weight: 600;
		margin-top: 4px;
	}

	/* Charts Layout Grid */
	.dnf-chart-grid {
		display: grid;
		grid-template-columns: 2fr 1fr;
		gap: 20px;
	}
	@media (max-width: 1024px) {
		.dnf-chart-grid {
			grid-template-columns: 1fr;
		}
	}
	.dnf-chart-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 24px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
		display: flex;
		flex-direction: column;
		gap: 16px;
	}
	.dnf-chart-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 12px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-chart-title {
		margin: 0;
		font-size: 14px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		text-transform: uppercase;
		letter-spacing: 0.04em;
	}
	.dnf-chart-canvas-wrap {
		position: relative;
		height: 280px;
		width: 100%;
	}

	/* Filter Surface */
	.dnf-filter-box {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 16px 20px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		flex-wrap: wrap;
		gap: 14px;
	}
	.dnf-filter-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Table Surface */
	.dnf-rep-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-rep-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}

	/* Status Pills */
	.dnf-status-pill {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		font-size: 11px;
		font-weight: 700;
		padding: 3px 10px;
		border-radius: var(--dnf-radius-pill, 9999px);
		text-transform: uppercase;
		letter-spacing: 0.04em;
		line-height: 1.2;
	}
	.dnf-status-pill.active {
		background: #f0fdfa;
		color: var(--dnf-accent-teal, #0d9488);
		border: 1px solid #ccfbf1;
	}
	.dnf-status-pill.completed {
		background: #f1f5f9;
		color: var(--dnf-text-secondary, #475569);
		border: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-status-pill.pending {
		background: #fffbeb;
		color: #d97706;
		border: 1px solid #fef3c7;
	}
	.dnf-status-pill.cancelled {
		background: var(--dnf-danger-bg, #fef2f2);
		color: var(--dnf-danger, #dc2626);
		border: 1px solid var(--dnf-danger-border, #fecaca);
	}

	/* Print Styles */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-hud-controls,
		.dnf-filter-box {
			display: none !important;
		}
		.dnf-rep-scope {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
		}
		.dnf-chart-card,
		.dnf-rep-table-card {
			box-shadow: none !important;
			border: 1px solid #000000 !important;
		}
	}
</style>

<div class="dnf-rep-scope">

	<!-- Executive Header Panel -->
	<div class="dnf-rep-header">
		<div class="dnf-rep-title-area">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'Audit Ledger Active', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext"><?php esc_html_e( 'Yield Diagnostics, Booking Velocity & Tax Provisioning', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-rep-heading"><?php esc_html_e( 'Financial Reports & Operational Audits', 'dev-nahian-car' ); ?></h2>
		</div>

		<div class="dnf-hud-controls" style="display:flex; gap:10px;">
			<a href="<?php echo esc_url( $export_bookings_url ); ?>" class="dnf-btn dnf-btn-primary">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
					<polyline points="7 10 12 15 17 10"></polyline>
					<line x1="12" y1="15" x2="12" y2="3"></line>
				</svg>
				<span><?php esc_html_e( 'Download Full Bookings CSV', 'dev-nahian-car' ); ?></span>
			</a>
			<button type="button" class="dnf-btn dnf-btn-secondary" onclick="window.print();">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span><?php esc_html_e( 'Print Audit Dossier', 'dev-nahian-car' ); ?></span>
			</button>
		</div>
	</div>

	<!-- 4 KPI Summary Cards -->
	<div class="dnf-kpi-grid">
		<div class="dnf-kpi-card">
			<span class="dnf-kpi-label"><?php esc_html_e( 'Gross Invoiced Revenue', 'dev-nahian-car' ); ?></span>
			<strong class="dnf-kpi-val" style="color:var(--dnf-accent-teal, #0d9488);">
				<?php echo esc_html( $currency . ' ' . number_format_i18n( $gross_revenue, 2 ) ); ?>
			</strong>
			<span class="dnf-kpi-sub"><?php esc_html_e( 'Active & settled reservations', 'dev-nahian-car' ); ?></span>
		</div>
		<div class="dnf-kpi-card">
			<span class="dnf-kpi-label"><?php esc_html_e( 'Settled Manifests', 'dev-nahian-car' ); ?></span>
			<strong class="dnf-kpi-val">
				<?php
				printf(
					/* translators: %s: number of bookings */
					esc_html__( '%s Bookings', 'dev-nahian-car' ),
					esc_html( (string) number_format_i18n( $settled_bookings ) )
				);
				?>
			</strong>
			<span class="dnf-kpi-sub"><?php esc_html_e( 'Completed & active trips', 'dev-nahian-car' ); ?></span>
		</div>
		<div class="dnf-kpi-card">
			<span class="dnf-kpi-label"><?php esc_html_e( 'Average Booking Value', 'dev-nahian-car' ); ?></span>
			<strong class="dnf-kpi-val">
				<?php echo esc_html( $currency . ' ' . number_format_i18n( $avg_order_value, 2 ) ); ?>
			</strong>
			<span class="dnf-kpi-sub"><?php esc_html_e( 'Average yield per trip reservation', 'dev-nahian-car' ); ?></span>
		</div>
		<div class="dnf-kpi-card">
			<span class="dnf-kpi-label"><?php esc_html_e( 'Tax / VAT Provision (Est. 8%)', 'dev-nahian-car' ); ?></span>
			<strong class="dnf-kpi-val">
				<?php echo esc_html( $currency . ' ' . number_format_i18n( $tax_provision, 2 ) ); ?>
			</strong>
			<span class="dnf-kpi-sub"><?php esc_html_e( 'Estimated tax reserve calculation', 'dev-nahian-car' ); ?></span>
		</div>
	</div>

	<!-- Interactive Chart.js Analytics Grid -->
	<div class="dnf-chart-grid">
		<!-- Monthly Cashflow & Booking Volume Trend -->
		<div class="dnf-chart-card">
			<div class="dnf-chart-header">
				<h3 class="dnf-chart-title">
					<?php esc_html_e( 'Monthly Revenue Progression & Booking Velocity', 'dev-nahian-car' ); ?>
				</h3>
				<span class="dnf-count-chip"><?php esc_html_e( 'Past 6 Months', 'dev-nahian-car' ); ?></span>
			</div>
			<div class="dnf-chart-canvas-wrap">
				<canvas id="dnfRevenueTrendChart"></canvas>
			</div>
		</div>

		<!-- Top Chassis Yield Distribution -->
		<div class="dnf-chart-card">
			<div class="dnf-chart-header">
				<h3 class="dnf-chart-title">
					<?php esc_html_e( 'Top Yield Chassis Share', 'dev-nahian-car' ); ?>
				</h3>
				<span class="dnf-count-chip"><?php esc_html_e( 'By Revenue', 'dev-nahian-car' ); ?></span>
			</div>
			<div class="dnf-chart-canvas-wrap">
				<canvas id="dnfChassisShareChart"></canvas>
			</div>
		</div>
	</div>

	<!-- Filter Toolbar -->
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="dev-nahian-fleet-manager">
		<input type="hidden" name="tab" value="reports">

		<div class="dnf-filter-box">
			<div class="dnf-filter-group">
				<span class="dnf-filter-label" style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--dnf-text-secondary, #475569);">
					<?php esc_html_e( 'Audit Window:', 'dev-nahian-car' ); ?>
				</span>
				<input type="date" name="filter_from" class="dnf-input" style="height:36px; padding:0 10px; width:auto;" value="<?php echo esc_attr( $filter_from ); ?>">
				<span style="font-size: 12px; color: var(--dnf-text-tertiary, #94a3b8);">&ndash;</span>
				<input type="date" name="filter_to" class="dnf-input" style="height:36px; padding:0 10px; width:auto;" value="<?php echo esc_attr( $filter_to ); ?>">
				
				<div class="dnf-select-wrapper" style="min-width: 180px;">
					<select name="filter_car" class="dnf-select" style="height:36px;">
						<option value=""><?php esc_html_e( '-- All Fleet Assets --', 'dev-nahian-car' ); ?></option>
						<?php if ( ! empty( $cars_dropdown ) ) : ?>
							<?php foreach ( $cars_dropdown as $car_name ) : ?>
								<option value="<?php echo esc_attr( (string) $car_name ); ?>" <?php selected( $filter_car, $car_name ); ?>>
									<?php echo esc_html( (string) $car_name ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
			</div>

			<div class="dnf-filter-group">
				<button type="submit" class="dnf-btn dnf-btn-primary">
					<?php esc_html_e( 'Apply Filter', 'dev-nahian-car' ); ?>
				</button>
				<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
					<?php esc_html_e( 'Reset Filters', 'dev-nahian-car' ); ?>
				</a>
			</div>
		</div>
	</form>

	<!-- Audited Settlements Table Surface -->
	<div class="dnf-rep-table-card">
		<div class="dnf-rep-meta-bar">
			<div class="dnf-chart-title">
				<?php esc_html_e( 'Recent Audited Settlements & Rate Realization', 'dev-nahian-car' ); ?>
			</div>
			<span class="dnf-count-chip">
				<?php
				printf(
					/* translators: %s: number of records loaded */
					esc_html__( '%s Records Loaded', 'dev-nahian-car' ),
					esc_html( (string) number_format_i18n( count( $settlements ) ) )
				);
				?>
			</span>
		</div>

		<div class="dnf-rep-table-wrap" style="width:100%; overflow-x:auto;">
			<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
				<thead>
					<tr>
						<th style="width: 120px;"><?php esc_html_e( 'Manifest Code', 'dev-nahian-car' ); ?></th>
						<th><?php esc_html_e( 'Client Name', 'dev-nahian-car' ); ?></th>
						<th><?php esc_html_e( 'Assigned Chassis', 'dev-nahian-car' ); ?></th>
						<th style="width: 180px;"><?php esc_html_e( 'Schedule Window', 'dev-nahian-car' ); ?></th>
						<th style="width: 140px;"><?php esc_html_e( 'Invoiced Total', 'dev-nahian-car' ); ?></th>
						<th style="width: 140px;"><?php esc_html_e( 'Payment Method', 'dev-nahian-car' ); ?></th>
						<th style="width: 140px;"><?php esc_html_e( 'Audit Status', 'dev-nahian-car' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $settlements ) ) : ?>
						<?php
						foreach ( $settlements as $s ) :
							$status_key = sanitize_key( (string) ( $s['booking_status'] ?? 'pending' ) );
							$pickup     = ! empty( $s['pickup_date'] ) ? strtotime( (string) $s['pickup_date'] ) : false;
							$return     = ! empty( $s['return_date'] ) ? strtotime( (string) $s['return_date'] ) : false;
							?>
							<tr>
								<td>
									<code class="dnf-code">#<?php echo esc_html( (string) ( $s['booking_code'] ?? $s['id'] ) ); ?></code>
								</td>
								<td>
									<strong><?php echo esc_html( (string) ( $s['customer_name'] ?? '—' ) ); ?></strong>
								</td>
								<td>
									<?php echo esc_html( ! empty( $s['car_name'] ) ? (string) $s['car_name'] : '—' ); ?>
								</td>
								<td style="font-size:12.5px; color:var(--dnf-text-secondary, #475569);">
									<?php
									echo esc_html(
										( $pickup ? wp_date( 'M j', $pickup ) : '—' )
										. ' → ' .
										( $return ? wp_date( 'M j, Y', $return ) : '—' )
									);
									?>
								</td>
								<td>
									<strong style="color:var(--dnf-text-primary, #0f172a); font-feature-settings:'tnum';">
										<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $s['total_amount'] ?? 0.00 ), 2 ) ); ?>
									</strong>
								</td>
								<td>
									<code class="dnf-code"><?php echo esc_html( strtoupper( (string) ( $s['payment_method'] ?? 'CARD' ) ) ); ?></code>
								</td>
								<td>
									<span class="dnf-status-pill <?php echo esc_attr( $status_key ); ?>">
										&bull; <?php echo esc_html( ucfirst( $status_key ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="7" class="dnf-table-empty" style="text-align:center; padding:32px; color:var(--dnf-text-secondary, #64748b);">
								<?php esc_html_e( 'No audited settlement records found matching the filter criteria.', 'dev-nahian-car' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

</div>

<!-- Chart.js Dependencies & Initialization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
	if (typeof Chart === 'undefined') {
		return;
	}

	var currencySymbol = <?php echo wp_json_encode( $currency ); ?>;
	var trendLabels    = <?php echo wp_json_encode( $chart_months ); ?>;
	var trendRevenues  = <?php echo wp_json_encode( $chart_revenues ); ?>;
	var trendTrips     = <?php echo wp_json_encode( $chart_trips ); ?>;

	var chassisLabels  = <?php echo wp_json_encode( $chassis_labels ); ?>;
	var chassisValues  = <?php echo wp_json_encode( $chassis_values ); ?>;

	// 1. Revenue & Velocity Progression Chart
	var ctxTrend = document.getElementById('dnfRevenueTrendChart');
	if (ctxTrend) {
		new Chart(ctxTrend, {
			type: 'bar',
			data: {
				labels: trendLabels,
				datasets: [
					{
						label: 'Gross Invoiced (' + currencySymbol + ')',
						data: trendRevenues,
						backgroundColor: 'rgba(13, 148, 136, 0.85)',
						borderColor: '#0d9488',
						borderRadius: 6,
						yAxisID: 'y'
					},
					{
						label: 'Trip Volume',
						data: trendTrips,
						type: 'line',
						borderColor: '#2563eb',
						backgroundColor: 'rgba(37, 99, 235, 0.1)',
						borderWidth: 2.5,
						tension: 0.35,
						fill: false,
						yAxisID: 'y1'
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
						labels: { boxWidth: 12, font: { weight: '600', size: 11 } }
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								if (context.dataset.yAxisID === 'y') {
									return context.dataset.label + ': ' + currencySymbol + ' ' + Number(context.raw).toLocaleString();
								}
								return context.dataset.label + ': ' + context.raw;
							}
						}
					}
				},
				scales: {
					y: {
						type: 'linear',
						position: 'left',
						grid: { color: '#f1f5f9' },
						ticks: {
							callback: function(value) {
								return currencySymbol + ' ' + value.toLocaleString();
							}
						}
					},
					y1: {
						type: 'linear',
						position: 'right',
						grid: { drawOnChartArea: false },
						ticks: { precision: 0 }
					},
					x: {
						grid: { display: false }
					}
				}
			}
		});
	}

	// 2. Chassis Yield Share Doughnut Chart
	var ctxChassis = document.getElementById('dnfChassisShareChart');
	if (ctxChassis) {
		new Chart(ctxChassis, {
			type: 'doughnut',
			data: {
				labels: chassisLabels.length ? chassisLabels : ['No Bookings Logged'],
				datasets: [{
					data: chassisValues.length ? chassisValues : [1],
					backgroundColor: [
						'#0d9488',
						'#2563eb',
						'#b88e1e',
						'#6366f1',
						'#0f172a'
					],
					borderWidth: 2,
					borderColor: '#ffffff'
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
						labels: { boxWidth: 10, font: { size: 10 } }
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								return context.label + ': ' + currencySymbol + ' ' + Number(context.raw).toLocaleString();
							}
						}
					}
				},
				cutout: '68%'
			}
		});
	}
});
</script>
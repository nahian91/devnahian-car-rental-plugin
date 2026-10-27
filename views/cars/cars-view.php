<?php
/**
 * Cars Module: Executive Dossier View (`cars-view.php`)
 * Displays structured vehicle telematics, specifications, cabin amenities, and media.
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

// Ensure variables have robust fallbacks.
$id                = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_cars        = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$table_categories  = isset( $table_categories ) ? $table_categories : $wpdb->prefix . 'dev_nahian_categories';
$table_bookings    = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$table_maint       = $wpdb->prefix . 'dev_nahian_maintenance';
$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';

$base = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'cars',
	),
	admin_url( 'admin.php' )
);
$currency = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

if ( $id <= 0 ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid vehicle ID supplied.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$car = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cars} WHERE id = %d", $id ), ARRAY_A );

if ( ! $car ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Vehicle asset record not found in database.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

$car_id     = absint( $car['id'] );
$meta       = (array) get_option( "dev_nahian_car_meta_{$car_id}", array() );
$status_key = sanitize_key( (string) ( $car['status'] ?? 'ready' ) );
$specs      = (array) ( $meta['technical_specs'] ?? array() );
$amenities  = (array) ( $meta['amenities'] ?? array() );

// Cross-reference related active manifests & telemetry.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$active_booking = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT id, booking_code, customer_name, return_date FROM {$table_bookings} WHERE car_id = %d AND booking_status IN ('active', 'confirmed') ORDER BY id DESC LIMIT 1",
		$car_id
	),
	ARRAY_A
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$open_maint = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT id, service_type, date_in FROM {$table_maint} WHERE car_name = %s ORDER BY id DESC LIMIT 1",
		$car['name']
	),
	ARRAY_A
);

// Resolve human-readable category name.
$cat_slug = sanitize_key( (string) ( $car['category'] ?? '' ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cat_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_categories} WHERE slug = %s", $cat_slug ) );
if ( empty( $cat_name ) ) {
	$cat_name = ucwords( str_replace( array( '-', '_' ), ' ', $cat_slug ) );
}

$edit_url = add_query_arg(
	array(
		'action' => 'edit',
		'id'     => $car_id,
		'subtab' => 'add',
	),
	$base
);

$delete_url = wp_nonce_url(
	add_query_arg(
		array(
			'action' => 'delete',
			'id'     => $car_id,
		),
		$base
	),
	'dev_nahian_delete_car_' . $car_id
);

$status_label = ucfirst( $status_key );
if ( 'ready' === $status_key ) {
	$status_label = __( 'Ready for Dispatch', 'dev-nahian-car' );
} elseif ( 'dispatched' === $status_key || 'rented' === $status_key ) {
	$status_label = __( 'On Active Rental', 'dev-nahian-car' );
} elseif ( 'service' === $status_key || 'maintenance' === $status_key ) {
	$status_label = __( 'In Workshop / Maint', 'dev-nahian-car' );
}

$fuel_level = (int) ( $car['fuel_level'] ?? 100 );
$fuel_color = $fuel_level >= 70 ? 'var(--dnf-accent-green, #16a34a)' : ( $fuel_level >= 30 ? '#d97706' : 'var(--dnf-danger, #dc2626)' );
?>

<style>
	/* Scoped Vehicle Dossier Styles */
	.dnf-dossier-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Navigation Toolbar */
	.dnf-dossier-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-dossier-nav-left {
		display: flex;
		align-items: center;
		gap: 12px;
		flex-wrap: wrap;
	}

	/* Two-Column Dossier Grid */
	.dnf-dossier-layout {
		display: grid;
		grid-template-columns: 1fr 380px;
		gap: 24px;
		align-items: start;
	}
	@media (max-width: 1080px) {
		.dnf-dossier-layout {
			grid-template-columns: 1fr;
		}
	}

	/* Card Shell */
	.dnf-dossier-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 26px 28px;
		margin-bottom: 24px;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
	}
	.dnf-dossier-card:last-child {
		margin-bottom: 0;
	}

	.dnf-dossier-card-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 20px;
		padding-bottom: 14px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 10px;
	}
	.dnf-dossier-title {
		font-size: 14px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-primary, #0f172a);
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-dossier-title svg {
		color: var(--dnf-accent-teal, #0d9488);
	}

	/* Highlight Metric Grid */
	.dnf-kpi-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 14px;
		margin-bottom: 24px;
	}
	@media (max-width: 768px) {
		.dnf-kpi-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 480px) {
		.dnf-kpi-grid {
			grid-template-columns: 1fr;
		}
	}

	.dnf-kpi-box {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 14px 16px;
		display: flex;
		flex-direction: column;
		gap: 4px;
	}
	.dnf-kpi-label {
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-secondary, #64748b);
	}
	.dnf-kpi-val {
		font-size: 15px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		font-feature-settings: "tnum";
	}
	.dnf-kpi-val.accent {
		color: var(--dnf-accent-teal, #0d9488);
	}

	/* Section Mini-Headings */
	.dnf-dossier-subheading {
		font-size: 12px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: var(--dnf-text-secondary, #475569);
		margin: 24px 0 12px;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-dossier-subheading::after {
		content: '';
		flex-grow: 1;
		height: 1px;
		background: var(--dnf-border, #e2e8f0);
	}

	/* Spec Row List */
	.dnf-spec-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 10px;
		margin-bottom: 24px;
	}
	@media (max-width: 600px) {
		.dnf-spec-grid {
			grid-template-columns: 1fr;
		}
	}

	.dnf-spec-tile {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-sm, 6px);
		padding: 11px 14px;
		font-size: 13px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.dnf-spec-label {
		color: var(--dnf-text-secondary, #64748b);
		font-weight: 600;
	}
	.dnf-spec-val {
		color: var(--dnf-text-primary, #0f172a);
		font-weight: 700;
	}

	/* Amenities Tag Cloud */
	.dnf-amenity-tags {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		margin-bottom: 24px;
	}
	.dnf-amenity-tag {
		background: #f0fdfa;
		color: var(--dnf-accent-teal, #0d9488);
		border: 1px solid #ccfbf1;
		padding: 6px 12px;
		border-radius: var(--dnf-radius-pill, 9999px);
		font-size: 12px;
		font-weight: 700;
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}

	/* Description Surface */
	.dnf-notes-card {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 16px;
		font-size: 13.5px;
		line-height: 1.6;
		color: var(--dnf-text-primary, #0f172a);
	}

	/* Sidebar Cover Visual */
	.dnf-cover-showcase {
		width: 100%;
		height: 240px;
		border-radius: var(--dnf-radius-md, 8px);
		overflow: hidden;
		border: 1px solid var(--dnf-border, #e2e8f0);
		background: #0f172a;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 16px;
	}
	.dnf-cover-showcase img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.dnf-cover-fallback {
		color: var(--dnf-text-tertiary, #94a3b8);
		font-size: 13px;
		font-weight: 600;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 8px;
	}

	/* Active Dispatch Telematics Surface */
	.dnf-dispatch-sync-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 14px 16px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 12px;
	}

	/* Print Stylesheet Overrides */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-dossier-nav,
		.dev-nahian-sidebar {
			display: none !important;
		}
		.dev-nahian-content,
		.dnf-cars-scope,
		.dnf-dossier-wrap {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
			border: none !important;
		}
		.dnf-dossier-card {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
			page-break-inside: avoid;
		}
	}
</style>

<div class="dnf-dossier-wrap">

	<!-- Top Navigation Toolbar -->
	<div class="dnf-dossier-nav">
		<div class="dnf-dossier-nav-left">
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back to Fleet Stable', 'dev-nahian-car' ); ?></span>
			</a>
			<span class="dnf-count-chip"><?php esc_html_e( 'Chassis Dossier', 'dev-nahian-car' ); ?></span>
			<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
				<?php echo esc_html( (string) $car['name'] ); ?>
			</h2>
		</div>

		<div style="display: flex; gap: 8px;">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn dnf-btn-primary">
				<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Specifications', 'dev-nahian-car' ); ?></span>
			</a>
			<button type="button" class="dnf-btn dnf-btn-secondary" onclick="window.print();">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span><?php esc_html_e( 'Print Dossier', 'dev-nahian-car' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn dnf-btn-danger" onclick="return confirm('<?php esc_attr_e( 'Permanently retire and purge this vehicle from fleet records?', 'dev-nahian-car' ); ?>');">
				<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Retire', 'dev-nahian-car' ); ?></span>
			</a>
		</div>
	</div>

	<!-- Two-Column Dossier Workspace -->
	<div class="dnf-dossier-layout">
		<!-- Left Main Column: Telematics, Specs, Amenities -->
		<div class="dnf-main-col">
			<div class="dnf-dossier-card">
				<!-- Header with Badge -->
				<div class="dnf-dossier-card-header">
					<h3 class="dnf-dossier-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 17h14v-5l-2-6H7L5 12v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
						<?php esc_html_e( 'Asset Overview & Chassis Identity', 'dev-nahian-car' ); ?>
					</h3>
					<span class="dnf-car-badge <?php echo esc_attr( $status_key ); ?>">
						&bull; <?php echo esc_html( $status_label ); ?>
					</span>
				</div>

				<!-- Metric Highlight Grid -->
				<div class="dnf-kpi-grid">
					<div class="dnf-kpi-box">
						<span class="dnf-kpi-label"><?php esc_html_e( 'Make / Brand', 'dev-nahian-car' ); ?></span>
						<span class="dnf-kpi-val"><?php echo esc_html( ! empty( $car['brand'] ) ? (string) $car['brand'] : '—' ); ?></span>
					</div>
					<div class="dnf-kpi-box">
						<span class="dnf-kpi-label"><?php esc_html_e( 'Model Year', 'dev-nahian-car' ); ?></span>
						<span class="dnf-kpi-val"><?php echo esc_html( (string) ( $car['year'] ?? gmdate( 'Y' ) ) ); ?></span>
					</div>
					<div class="dnf-kpi-box">
						<span class="dnf-kpi-label"><?php esc_html_e( 'License Plate #', 'dev-nahian-car' ); ?></span>
						<span class="dnf-kpi-val">
							<code class="dnf-code"><?php echo esc_html( ! empty( $car['plate'] ) ? (string) $car['plate'] : '—' ); ?></code>
						</span>
					</div>
					<div class="dnf-kpi-box">
						<span class="dnf-kpi-label"><?php esc_html_e( 'VIN Identifier', 'dev-nahian-car' ); ?></span>
						<span class="dnf-kpi-val">
							<code class="dnf-code"><?php echo esc_html( (string) ( $car['vin'] ?? '—' ) ); ?></code>
						</span>
					</div>
					<div class="dnf-kpi-box">
						<span class="dnf-kpi-label"><?php esc_html_e( 'Base Daily Tariff', 'dev-nahian-car' ); ?></span>
						<span class="dnf-kpi-val accent">
							<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $car['price'] ?? 0.00 ), 2 ) ); ?>
						</span>
					</div>
					<div class="dnf-kpi-box">
						<span class="dnf-kpi-label"><?php esc_html_e( 'Security Deposit', 'dev-nahian-car' ); ?></span>
						<span class="dnf-kpi-val">
							<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $car['deposit'] ?? 0.00 ), 2 ) ); ?>
						</span>
					</div>
				</div>

				<!-- Live Dispatch or Maintenance Synchronization -->
				<?php if ( ! empty( $active_booking ) || ! empty( $open_maint ) ) : ?>
					<div class="dnf-dossier-subheading" style="margin-top: 10px;">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
						<?php esc_html_e( 'Live Dispatch & Service Work Order Telematics', 'dev-nahian-car' ); ?>
					</div>

					<div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
						<?php if ( ! empty( $active_booking ) ) :
							$booking_url = add_query_arg(
								array(
									'page'   => 'dev-nahian-fleet-manager',
									'tab'    => 'booking',
									'action' => 'view',
									'id'     => absint( $active_booking['id'] ),
								),
								admin_url( 'admin.php' )
							);
							?>
							<div class="dnf-dispatch-sync-card">
								<div>
									<div style="font-size: 13.5px; font-weight: 700; color: var(--dnf-text-primary, #0f172a);">
										<?php esc_html_e( 'Active Client:', 'dev-nahian-car' ); ?> <?php echo esc_html( (string) $active_booking['customer_name'] ); ?>
									</div>
									<div style="font-size: 12px; color: var(--dnf-text-secondary, #64748b); margin-top: 2px;">
										<span><?php esc_html_e( 'Manifest:', 'dev-nahian-car' ); ?> <code class="dnf-code">#<?php echo esc_html( (string) $active_booking['booking_code'] ); ?></code></span>
										<span style="margin: 0 6px;">&bull;</span>
										<span><?php esc_html_e( 'Expected Return:', 'dev-nahian-car' ); ?> <?php echo esc_html( (string) $active_booking['return_date'] ); ?></span>
									</div>
								</div>
								<a href="<?php echo esc_url( $booking_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm">
									<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php esc_html_e( 'Inspect Manifest', 'dev-nahian-car' ); ?></span>
								</a>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $open_maint ) ) :
							$maint_url = add_query_arg(
								array(
									'page'   => 'dev-nahian-fleet-manager',
									'tab'    => 'maintenance',
									'action' => 'view',
									'id'     => absint( $open_maint['id'] ),
								),
								admin_url( 'admin.php' )
							);
							?>
							<div class="dnf-dispatch-sync-card">
								<div>
									<div style="font-size: 13.5px; font-weight: 700; color: var(--dnf-text-primary, #0f172a);">
										<?php esc_html_e( 'Workshop Order:', 'dev-nahian-car' ); ?> <?php echo esc_html( (string) $open_maint['service_type'] ); ?>
									</div>
									<div style="font-size: 12px; color: var(--dnf-text-secondary, #64748b); margin-top: 2px;">
										<span><?php esc_html_e( 'Ticket:', 'dev-nahian-car' ); ?> <code class="dnf-code">#<?php echo esc_html( (string) $open_maint['id'] ); ?></code></span>
										<span style="margin: 0 6px;">&bull;</span>
										<span><?php esc_html_e( 'Logged Date:', 'dev-nahian-car' ); ?> <?php echo esc_html( (string) $open_maint['date_in'] ); ?></span>
									</div>
								</div>
								<a href="<?php echo esc_url( $maint_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm">
									<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php esc_html_e( 'Inspect Service Ticket', 'dev-nahian-car' ); ?></span>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- Powertrain & Telematics -->
				<div class="dnf-dossier-subheading">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
					<?php esc_html_e( 'Powertrain & Mechanical Specifications', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-spec-grid">
					<div class="dnf-spec-tile">
						<span class="dnf-spec-label"><?php esc_html_e( 'Horsepower', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( ! empty( $car['horsepower'] ) ? $car['horsepower'] . ' HP' : '—' ); ?></span>
					</div>
					<div class="dnf-spec-tile">
						<span class="dnf-spec-label"><?php esc_html_e( '0-60 Acceleration', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( ! empty( $car['acceleration'] ) ? (string) $car['acceleration'] : '—' ); ?></span>
					</div>
					<div class="dnf-spec-tile">
						<span class="dnf-spec-label"><?php esc_html_e( 'Top Speed', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( ! empty( $car['speed'] ) ? (string) $car['speed'] : '—' ); ?></span>
					</div>
					<div class="dnf-spec-tile">
						<span class="dnf-spec-label"><?php esc_html_e( 'Drivetrain Layout', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( (string) ( $car['drivetrain'] ?? 'AWD' ) ); ?></span>
					</div>
					<div class="dnf-spec-tile">
						<span class="dnf-spec-label"><?php esc_html_e( 'Transmission', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( ucfirst( (string) ( $car['transmission'] ?? 'automatic' ) ) ); ?></span>
					</div>
					<div class="dnf-spec-tile">
						<span class="dnf-spec-label"><?php esc_html_e( 'Energy / Fuel Type', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( ucfirst( (string) ( $car['fuel'] ?? 'gasoline' ) ) ); ?></span>
					</div>
					<div class="dnf-spec-tile">
						<span class="dnf-spec-label"><?php esc_html_e( 'Cabin Capacity', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val">
							<?php
							printf(
								/* translators: 1: seat count, 2: door count */
								esc_html__( '%1$d Seats • %2$d Doors', 'dev-nahian-car' ),
								absint( $car['seats'] ?? 2 ),
								absint( $car['doors'] ?? 4 )
							);
							?>
						</span>
					</div>
					<div class="dnf-spec-tile">
						<span class="dnf-spec-label"><?php esc_html_e( 'Odometer Log', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val">
							<?php
							printf(
								/* translators: %s: mileage */
								esc_html__( '%s Miles', 'dev-nahian-car' ),
								esc_html( number_format_i18n( (int) ( $car['odometer'] ?? 0 ) ) )
							);
							?>
						</span>
					</div>
				</div>

				<!-- Custom Technical Specifications -->
				<?php if ( ! empty( $specs ) ) : ?>
					<div class="dnf-dossier-subheading">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
						<?php esc_html_e( 'Configured Technical Attributes', 'dev-nahian-car' ); ?>
					</div>
					<div class="dnf-spec-grid">
						<?php foreach ( $specs as $s_label => $s_val ) : ?>
							<div class="dnf-spec-tile">
								<span class="dnf-spec-label"><?php echo esc_html( (string) $s_label ); ?></span>
								<span class="dnf-spec-val"><?php echo esc_html( (string) $s_val ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<!-- Included Cabin Amenities -->
				<?php if ( ! empty( $amenities ) ) : ?>
					<div class="dnf-dossier-subheading">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
						<?php esc_html_e( 'Included Cabin Amenities', 'dev-nahian-car' ); ?>
					</div>
					<div class="dnf-amenity-tags">
						<?php foreach ( $amenities as $amenity ) : ?>
							<span class="dnf-amenity-tag">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<?php echo esc_html( (string) $amenity ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<!-- Editorial Description -->
				<div class="dnf-dossier-subheading">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
					<?php esc_html_e( 'Public Customer Facing Description', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-notes-card">
					<?php
					if ( ! empty( $car['notes'] ) ) {
						echo wp_kses_post( $car['notes'] );
					} else {
						echo '<em style="color:var(--dnf-text-tertiary, #94a3b8);">' . esc_html__( 'No editorial description registered for this chassis.', 'dev-nahian-car' ) . '</em>';
					}
					?>
				</div>
			</div>
		</div>

		<!-- Right Sidebar Column: Visual & Logistics Dock -->
		<div class="dnf-side-col">
			<!-- Visual Showcase Card -->
			<div class="dnf-dossier-card">
				<div class="dnf-dossier-card-header">
					<h3 class="dnf-dossier-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
						<?php esc_html_e( 'Primary Cover Photo', 'dev-nahian-car' ); ?>
					</h3>
				</div>

				<div class="dnf-cover-showcase">
					<?php if ( ! empty( $car['image'] ) ) : ?>
						<img src="<?php echo esc_url( (string) $car['image'] ); ?>" alt="<?php echo esc_attr( (string) $car['name'] ); ?>">
					<?php else : ?>
						<div class="dnf-cover-fallback">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
							<span><?php esc_html_e( 'No photo attached', 'dev-nahian-car' ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Terminal Logistics Card -->
			<div class="dnf-dossier-card">
				<div class="dnf-dossier-card-header">
					<h3 class="dnf-dossier-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"></path><circle cx="12" cy="10" r="3"></circle></svg>
						<?php esc_html_e( 'Staging & Telematics', 'dev-nahian-car' ); ?>
					</h3>
				</div>

				<div class="dnf-spec-tile" style="margin-bottom:8px;">
					<span class="dnf-spec-label"><?php esc_html_e( 'Staging Hub', 'dev-nahian-car' ); ?></span>
					<span class="dnf-spec-val"><?php echo esc_html( ! empty( $car['location'] ) ? (string) $car['location'] : __( 'Unassigned', 'dev-nahian-car' ) ); ?></span>
				</div>
				<div class="dnf-spec-tile" style="margin-bottom:8px;">
					<span class="dnf-spec-label"><?php esc_html_e( 'Fuel / Battery State', 'dev-nahian-car' ); ?></span>
					<span class="dnf-spec-val" style="color: <?php echo esc_attr( $fuel_color ); ?>;">
						<?php echo esc_html( (string) $fuel_level ); ?>%
					</span>
				</div>
				<div class="dnf-spec-tile" style="margin-bottom:8px;">
					<span class="dnf-spec-label"><?php esc_html_e( 'Min Booking Duration', 'dev-nahian-car' ); ?></span>
					<span class="dnf-spec-val"><?php printf( esc_html__( '%d Days', 'dev-nahian-car' ), absint( $meta['min_booking_days'] ?? 1 ) ); ?></span>
				</div>
				<div class="dnf-spec-tile">
					<span class="dnf-spec-label"><?php esc_html_e( 'Classification', 'dev-nahian-car' ); ?></span>
					<span class="dnf-spec-val"><?php echo esc_html( (string) $cat_name ); ?></span>
				</div>
			</div>
		</div>
	</div>

</div>
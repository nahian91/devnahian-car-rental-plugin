<?php
/**
 * Maintenance Module: Single Service Ticket View (`maint-view.php`)
 * Detailed inspection dossier with service specifications, vehicle telemetry links, and mechanic diagnosis.
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

// Ensure scope variables have safe fallbacks.
$id          = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_maint = isset( $table_maint ) ? $table_maint : $wpdb->prefix . 'dev_nahian_maintenance';
$table_cars  = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$base        = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'maintenance',
	),
	admin_url( 'admin.php' )
);
$currency    = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

if ( $id <= 0 ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid maintenance ticket identifier supplied.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$maint = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_maint} WHERE id = %d", $id ), ARRAY_A );

if ( ! $maint ) :
	?>
	<div class="notice notice-error"><p><?php esc_html_e( 'Maintenance ticket not found or already deleted from workshop logs.', 'dev-nahian-car' ); ?></p></div>
	<p><a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">&larr; <?php esc_html_e( 'Back to Maintenance Ledger', 'dev-nahian-car' ); ?></a></p>
	<?php
	return;
endif;

$ticket_id  = absint( $maint['id'] );
$car_id     = absint( $maint['car_id'] ?? 0 );
$status_key = sanitize_key( (string) ( $maint['status'] ?? 'scheduled' ) );

// Fetch associated vehicle asset details for quick-inspect reference.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$linked_car = $car_id > 0 ? $wpdb->get_row( $wpdb->prepare( "SELECT id, name, plate, vin, status FROM {$table_cars} WHERE id = %d", $car_id ), ARRAY_A ) : null;

$edit_url = add_query_arg(
	array(
		'action' => 'edit',
		'id'     => $ticket_id,
		'subtab' => 'add',
	),
	$base
);

$delete_url = wp_nonce_url(
	add_query_arg(
		array(
			'action' => 'delete',
			'id'     => $ticket_id,
		),
		$base
	),
	'dev_nahian_delete_maint_' . $ticket_id
);
?>

<style>
	/* Scoped Maintenance Inspector Styles */
	.dnf-maint-inspector-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Navigation Toolbar */
	.dnf-maint-inspector-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-maint-inspector-nav-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Main Dossier Card */
	.dnf-maint-dossier-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		overflow: hidden;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
	}

	/* Executive Header Banner */
	.dnf-maint-dossier-header {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
		color: #ffffff;
		padding: 24px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-maint-dossier-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 11.5px;
		font-weight: 700;
		color: var(--dnf-accent-gold, #b88e1e);
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.dnf-maint-dossier-heading {
		margin: 4px 0 0;
		font-size: 22px;
		font-weight: 800;
		color: #ffffff;
		letter-spacing: -0.02em;
	}

	/* Body Content Grid */
	.dnf-maint-dossier-body {
		padding: 30px;
		display: flex;
		flex-direction: column;
		gap: 24px;
	}

	/* Highlight Metric Grid */
	.dnf-kpi-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 14px;
	}
	@media (max-width: 900px) {
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
	}

	/* Section Headings */
	.dnf-maint-section-title {
		font-size: 11px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: var(--dnf-accent-teal, #0d9488);
		margin: 0 0 12px 0;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-maint-section-title::after {
		content: '';
		flex-grow: 1;
		height: 1px;
		background: var(--dnf-border, #e2e8f0);
	}

	/* Notes & Diagnosis Box */
	.dnf-maint-notes-surface {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 18px;
		font-size: 13.5px;
		line-height: 1.6;
		color: var(--dnf-text-primary, #0f172a);
		white-space: pre-wrap;
	}

	/* Vehicle Asset Surface */
	.dnf-asset-surface {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 16px 20px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 12px;
	}

	/* Print Styles for Job Sheets */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-maint-inspector-nav,
		.dev-nahian-sidebar {
			display: none !important;
		}
		.dev-nahian-content,
		.dnf-maint-scope,
		.dnf-maint-inspector-wrap {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
			border: none !important;
		}
		.dnf-maint-dossier-card {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
		}
	}
</style>

<div class="dnf-maint-inspector-wrap">

	<!-- Inspector Navigation Toolbar -->
	<div class="dnf-maint-inspector-nav">
		<div class="dnf-maint-inspector-nav-left">
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back to Maintenance Ledger', 'dev-nahian-car' ); ?></span>
			</a>
			<span class="dnf-count-chip"><?php esc_html_e( 'Service Ticket Dossier', 'dev-nahian-car' ); ?></span>
			<h2 style="margin:0; font-size:18px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
				<?php printf( esc_html__( 'Service Ticket #%d', 'dev-nahian-car' ), absint( $ticket_id ) ); ?>
			</h2>
		</div>

		<div style="display:flex; gap:8px;">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn dnf-btn-primary">
				<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Ticket', 'dev-nahian-car' ); ?></span>
			</a>
			<button type="button" class="dnf-btn dnf-btn-secondary" onclick="window.print();">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span><?php esc_html_e( 'Print Job Sheet', 'dev-nahian-car' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn dnf-btn-danger" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this maintenance ticket record?', 'dev-nahian-car' ); ?>');">
				<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Delete', 'dev-nahian-car' ); ?></span>
			</a>
		</div>
	</div>

	<!-- Main Dossier Card -->
	<div class="dnf-maint-dossier-card">
		<!-- Executive Banner Header -->
		<div class="dnf-maint-dossier-header">
			<div>
				<div class="dnf-maint-dossier-code">
					<?php
					printf(
						/* translators: %d: ticket ID */
						esc_html__( 'WORKSHOP WORK ORDER: #%d', 'dev-nahian-car' ),
						absint( $ticket_id )
					);
					?>
				</div>
				<h2 class="dnf-maint-dossier-heading">
					<?php echo esc_html( (string) $maint['service_type'] ); ?>
				</h2>
			</div>
			<div>
				<span class="dnf-status-pill <?php echo esc_attr( $status_key ); ?>" style="font-size:12px; padding:4px 12px;">
					&bull; <?php echo esc_html( ucwords( str_replace( '_', ' ', $status_key ) ) ); ?>
				</span>
			</div>
		</div>

		<!-- Body Grid -->
		<div class="dnf-maint-dossier-body">
			<!-- KPI Highlights Grid -->
			<div class="dnf-kpi-grid">
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Workshop Service Cost', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="color:var(--dnf-accent-teal, #0d9488); font-feature-settings:'tnum';">
						<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $maint['cost'] ?? 0.00 ), 2 ) ); ?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Scheduled Service Date', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-feature-settings:'tnum';">
						<?php
						$svc_date = ! empty( $maint['service_date'] ) ? strtotime( (string) $maint['service_date'] ) : false;
						echo esc_html( $svc_date ? wp_date( (string) get_option( 'date_format', 'F j, Y' ), $svc_date ) : '—' );
						?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Target Vehicle', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-size:13.5px;">
						<?php echo esc_html( ! empty( $maint['car_name'] ) ? (string) $maint['car_name'] : sprintf( __( 'Chassis #%d', 'dev-nahian-car' ), $car_id ) ); ?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Order Registered', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-size:13px; font-feature-settings:'tnum';">
						<?php
						$created_ts = ! empty( $maint['created_at'] ) ? strtotime( (string) $maint['created_at'] ) : false;
						echo esc_html( $created_ts ? wp_date( (string) get_option( 'date_format', 'F j, Y' ), $created_ts ) : '—' );
						?>
					</span>
				</div>
			</div>

			<!-- Linked Fleet Asset Dossier -->
			<div>
				<div class="dnf-maint-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
					<?php esc_html_e( 'Target Vehicle Asset Telematics', 'dev-nahian-car' ); ?>
				</div>

				<?php
				if ( ! empty( $linked_car ) ) :
					$car_view_url = add_query_arg(
						array(
							'page'   => 'dev-nahian-fleet-manager',
							'tab'    => 'cars',
							'action' => 'view',
							'id'     => $car_id,
						),
						admin_url( 'admin.php' )
					);
					$car_status   = sanitize_key( (string) ( $linked_car['status'] ?? 'ready' ) );
					?>
					<div class="dnf-asset-surface">
						<div>
							<h4 style="margin:0 0 4px; font-size:15px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
								<?php echo esc_html( (string) $linked_car['name'] ); ?>
							</h4>
							<div style="font-size:12px; color:var(--dnf-text-secondary, #64748b); display:flex; gap:12px; align-items:center;">
								<span><?php esc_html_e( 'Plate:', 'dev-nahian-car' ); ?> <code class="dnf-code"><?php echo esc_html( ! empty( $linked_car['plate'] ) ? (string) $linked_car['plate'] : '—' ); ?></code></span>
								<span><?php esc_html_e( 'VIN:', 'dev-nahian-car' ); ?> <code class="dnf-code"><?php echo esc_html( ! empty( $linked_car['vin'] ) ? (string) $linked_car['vin'] : '—' ); ?></code></span>
							</div>
						</div>
						<div style="display:flex; align-items:center; gap:10px;">
							<span class="dnf-status-pill <?php echo esc_attr( $car_status ); ?>">
								&bull; <?php echo esc_html( ucfirst( $car_status ) ); ?>
							</span>
							<a href="<?php echo esc_url( $car_view_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm">
								<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Inspect Chassis Dossier', 'dev-nahian-car' ); ?></span>
							</a>
						</div>
					</div>
				<?php else : ?>
					<div class="dnf-maint-notes-surface" style="color:var(--dnf-text-tertiary, #94a3b8); padding:16px;">
						<?php printf( esc_html__( 'Target chassis #%d is no longer active or unassigned in the database.', 'dev-nahian-car' ), absint( $car_id ) ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Technician Work Notes & Diagnosis -->
			<div>
				<div class="dnf-maint-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					<?php esc_html_e( 'Mechanic Diagnosis, Replaced Parts & Workshop Notes', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-maint-notes-surface">
					<?php
					if ( ! empty( $maint['notes'] ) ) {
						echo esc_html( (string) $maint['notes'] );
					} else {
						echo '<em>' . esc_html__( 'No mechanic diagnostic notes or replaced parts recorded for this service ticket.', 'dev-nahian-car' ) . '</em>';
					}
					?>
				</div>
			</div>
		</div>
	</div>

</div>
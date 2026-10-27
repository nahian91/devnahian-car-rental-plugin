<?php
/**
 * Inspections Module: Single Report View (`inspection-view.php`)
 * Detailed inspection dossier with condition telemetry, linked booking manifest, and damage records.
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
$id             = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_insp     = isset( $table_insp ) ? $table_insp : $wpdb->prefix . 'dev_nahian_inspections';
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$table_cars     = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'inspections',
	),
	admin_url( 'admin.php' )
);

if ( $id <= 0 ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid inspection record identifier supplied.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$insp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_insp} WHERE id = %d", $id ), ARRAY_A );

if ( ! $insp ) :
	?>
	<div class="notice notice-error"><p><?php esc_html_e( 'Inspection report not found or already deleted from condition ledger.', 'dev-nahian-car' ); ?></p></div>
	<p><a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">&larr; <?php esc_html_e( 'Back to Condition Ledger', 'dev-nahian-car' ); ?></a></p>
	<?php
	return;
endif;

$insp_id      = absint( $insp['id'] );
$type_key     = sanitize_key( (string) ( $insp['inspection_type'] ?? 'checkout' ) );
$booking_code = (string) ( $insp['booking_code'] ?? '' );

// Fetch associated booking manifest details.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking = '' !== $booking_code ? $wpdb->get_row( $wpdb->prepare( "SELECT id, car_id, car_name, customer_name, pickup_date, return_date, booking_status FROM {$table_bookings} WHERE booking_code = %s", $booking_code ), ARRAY_A ) : null; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$edit_url = add_query_arg(
	array(
		'action' => 'edit',
		'id'     => $insp_id,
		'subtab' => 'add',
	),
	$base
);

$delete_url = wp_nonce_url(
	add_query_arg(
		array(
			'action' => 'delete',
			'id'     => $insp_id,
		),
		$base
	),
	'dev_nahian_delete_insp_' . $insp_id
);
?>

<style>
	/* Scoped Inspection Inspector Styles */
	.dnf-insp-inspector-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Navigation Toolbar */
	.dnf-insp-inspector-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-insp-inspector-nav-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Main Dossier Card */
	.dnf-insp-dossier-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		overflow: hidden;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
	}

	/* Executive Header Banner */
	.dnf-insp-dossier-header {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
		color: #ffffff;
		padding: 24px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-insp-dossier-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 11.5px;
		font-weight: 700;
		color: var(--dnf-accent-gold, #b88e1e);
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.dnf-insp-dossier-heading {
		margin: 4px 0 0;
		font-size: 22px;
		font-weight: 800;
		color: #ffffff;
		letter-spacing: -0.02em;
	}

	/* Body Content Grid */
	.dnf-insp-dossier-body {
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
	.dnf-insp-section-title {
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
	.dnf-insp-section-title::after {
		content: '';
		flex-grow: 1;
		height: 1px;
		background: var(--dnf-border, #e2e8f0);
	}

	/* Notes & Damage Log Box */
	.dnf-insp-notes-surface {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 18px;
		font-size: 13.5px;
		line-height: 1.6;
		color: var(--dnf-text-primary, #0f172a);
		white-space: pre-wrap;
	}

	/* Booking Manifest Surface */
	.dnf-manifest-surface {
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

	/* Print Styles for Condition Sheets */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-insp-inspector-nav,
		.dev-nahian-sidebar {
			display: none !important;
		}
		.dev-nahian-content,
		.dnf-insp-scope,
		.dnf-insp-inspector-wrap {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
			border: none !important;
		}
		.dnf-insp-dossier-card {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
		}
	}
</style>

<div class="dnf-insp-inspector-wrap">

	<!-- Inspector Navigation Toolbar -->
	<div class="dnf-insp-inspector-nav">
		<div class="dnf-insp-inspector-nav-left">
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back to Condition Ledger', 'dev-nahian-car' ); ?></span>
			</a>
			<span class="dnf-count-chip"><?php esc_html_e( 'Condition Report', 'dev-nahian-car' ); ?></span>
			<h2 style="margin:0; font-size:18px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
				<?php printf( esc_html__( 'Inspection Audit #%d', 'dev-nahian-car' ), absint( $insp_id ) ); ?>
			</h2>
		</div>

		<div style="display:flex; gap:8px;">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn dnf-btn-primary">
				<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Report', 'dev-nahian-car' ); ?></span>
			</a>
			<button type="button" class="dnf-btn dnf-btn-secondary" onclick="window.print();">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span><?php esc_html_e( 'Print Inspection Sheet', 'dev-nahian-car' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn dnf-btn-danger" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this inspection audit from the condition ledger?', 'dev-nahian-car' ); ?>');">
				<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Delete', 'dev-nahian-car' ); ?></span>
			</a>
		</div>
	</div>

	<!-- Main Dossier Card -->
	<div class="dnf-insp-dossier-card">
		<!-- Executive Banner Header -->
		<div class="dnf-insp-dossier-header">
			<div>
				<div class="dnf-insp-dossier-code">
					<?php
					printf(
						/* translators: %s: booking manifest code */
						esc_html__( 'MANIFEST REF: #%s', 'dev-nahian-car' ),
						esc_html( (string) ( $insp['booking_code'] ?? 'GENERAL' ) )
					);
					?>
				</div>
				<h2 class="dnf-insp-dossier-heading">
					<?php echo esc_html( (string) $insp['car_name'] ); ?>
				</h2>
			</div>
			<div>
				<span class="dnf-status-pill <?php echo esc_attr( $type_key ); ?>" style="font-size:12px; padding:4px 12px;">
					&bull; <?php echo esc_html( ucwords( str_replace( '_', ' ', $type_key ) ) ); ?>
				</span>
			</div>
		</div>

		<!-- Body Grid -->
		<div class="dnf-insp-dossier-body">
			<!-- KPI Highlights Grid -->
			<div class="dnf-kpi-grid">
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Fuel / Battery Level', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="color:var(--dnf-accent-teal, #0d9488); font-feature-settings:'tnum';">
						<?php echo esc_html( (string) ( $insp['fuel_level'] ?? 100 ) . '%' ); ?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Odometer Mileage', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-feature-settings: 'tnum';">
						<?php echo esc_html( number_format_i18n( absint( $insp['odometer'] ?? 0 ) ) . ' mi' ); ?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Auditing Inspector', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-size:13.5px;">
						<?php echo esc_html( ! empty( $insp['inspector_name'] ) ? (string) $insp['inspector_name'] : '—' ); ?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Audit Timestamp', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-size:13px; font-feature-settings:'tnum';">
						<?php
						$insp_ts = ! empty( $insp['inspected_at'] ) ? strtotime( (string) $insp['inspected_at'] ) : false;
						echo esc_html(
							$insp_ts
								? wp_date( (string) get_option( 'date_format', 'F j, Y' ) . ' ' . (string) get_option( 'time_format', 'H:i' ), $insp_ts )
								: '—'
						);
						?>
					</span>
				</div>
			</div>

			<!-- Linked Reservation Manifest Surface -->
			<div>
				<div class="dnf-insp-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					<?php esc_html_e( 'Associated Reservation Manifest Telematics', 'dev-nahian-car' ); ?>
				</div>

				<?php
				if ( ! empty( $booking ) ) :
					$booking_view_url = add_query_arg(
						array(
							'page'   => 'dev-nahian-fleet-manager',
							'tab'    => 'booking',
							'action' => 'view',
							'id'     => absint( $booking['id'] ),
						),
						admin_url( 'admin.php' )
					);
					$bk_status        = sanitize_key( (string) ( $booking['booking_status'] ?? 'pending' ) );
					?>
					<div class="dnf-manifest-surface">
						<div>
							<h4 style="margin:0 0 4px; font-size:15px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
								<?php echo esc_html( (string) $booking['customer_name'] ); ?>
							</h4>
							<div style="font-size:12px; color:var(--dnf-text-secondary, #64748b); display:flex; gap:12px; align-items:center;">
								<span><?php esc_html_e( 'Manifest:', 'dev-nahian-car' ); ?> <code class="dnf-code">#<?php echo esc_html( (string) $booking_code ); ?></code></span>
								<span style="font-feature-settings:'tnum';"><?php esc_html_e( 'Schedule:', 'dev-nahian-car' ); ?> <?php echo esc_html( (string) ( $booking['pickup_date'] ?? '' ) ); ?> &rarr; <?php echo esc_html( (string) ( $booking['return_date'] ?? '' ) ); ?></span>
							</div>
						</div>
						<div style="display:flex; align-items:center; gap:10px;">
							<span class="dnf-status-pill <?php echo esc_attr( $bk_status ); ?>">
								&bull; <?php echo esc_html( ucfirst( $bk_status ) ); ?>
							</span>
							<a href="<?php echo esc_url( $booking_view_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm">
								<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Inspect Reservation', 'dev-nahian-car' ); ?></span>
							</a>
						</div>
					</div>
				<?php else : ?>
					<div class="dnf-insp-notes-surface" style="color:var(--dnf-text-tertiary, #94a3b8); padding:16px;">
						<?php printf( esc_html__( 'Manifest code #%s is not linked to an active booking manifest in the database.', 'dev-nahian-car' ), esc_html( $booking_code ) ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Technician Walkaround & Scratch Log -->
			<div>
				<div class="dnf-insp-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
					<?php esc_html_e( 'Walkaround Condition, Scratch Log & Body Assessment', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-insp-notes-surface">
					<?php
					if ( ! empty( $insp['condition_notes'] ) ) {
						echo esc_html( (string) $insp['condition_notes'] );
					} else {
						echo '<em>' . esc_html__( 'No body scratches, glass chips, rim scuffs, or interior wear noted during this inspection.', 'dev-nahian-car' ) . '</em>';
					}
					?>
				</div>
			</div>
		</div>
	</div>

</div>
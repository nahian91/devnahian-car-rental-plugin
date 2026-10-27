<?php
/**
 * Staff Module: Single Personnel Dossier View (`staff-view.php`)
 * Detailed inspection dossier with duty credentials, contact details, and assigned dispatch manifests.
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
$table_staff    = isset( $table_staff ) ? $table_staff : $wpdb->prefix . 'dev_nahian_staff';
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'staff',
	),
	admin_url( 'admin.php' )
);
$currency       = (string) get_option( 'dev_nahian_currency', 'USD' );

if ( $id <= 0 ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid staff member identifier supplied.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $id ), ARRAY_A );

if ( ! $staff ) :
	?>
	<div class="notice notice-error"><p><?php esc_html_e( 'Staff profile not found or already deleted from roster.', 'dev-nahian-car' ); ?></p></div>
	<p><a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">&larr; <?php esc_html_e( 'Back to Staff Directory', 'dev-nahian-car' ); ?></a></p>
	<?php
	return;
endif;

$status_key = sanitize_key( (string) ( $staff['status'] ?? 'active' ) );
$role_key   = sanitize_key( (string) ( $staff['role_type'] ?? 'chauffeur' ) );
$staff_id   = absint( $staff['id'] );

// 1. Query Assigned Bookings History for Chauffeur/Dispatcher.
$assigned_bookings = array();
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$column_exists = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table_bookings} LIKE %s", 'driver_id' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

if ( ! empty( $column_exists ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$assigned_bookings = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE driver_id = %d ORDER BY id DESC LIMIT 5", $staff_id ), ARRAY_A );
}
$assigned_trips_count = count( $assigned_bookings );

$edit_url = add_query_arg(
	array(
		'action' => 'edit',
		'id'     => $staff_id,
		'subtab' => 'add',
	),
	$base
);

$delete_url = wp_nonce_url(
	add_query_arg(
		array(
			'action' => 'delete',
			'id'     => $staff_id,
		),
		$base
	),
	'dev_nahian_delete_staff_' . $staff_id
);
?>

<style>
	/* Scoped Staff Inspector Styles */
	.dnf-staff-inspector-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Navigation Toolbar */
	.dnf-staff-inspector-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-staff-inspector-nav-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Main Dossier Card */
	.dnf-staff-dossier-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		overflow: hidden;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
	}

	/* Executive Header */
	.dnf-staff-dossier-header {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
		color: #ffffff;
		padding: 24px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-staff-dossier-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 11.5px;
		font-weight: 700;
		color: var(--dnf-accent-gold, #b88e1e);
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.dnf-staff-dossier-heading {
		margin: 4px 0 0;
		font-size: 22px;
		font-weight: 800;
		color: #ffffff;
		letter-spacing: -0.02em;
	}

	/* Body Content Grid */
	.dnf-staff-dossier-body {
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
		font-size: 14.5px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
	}

	/* Section Headings */
	.dnf-staff-section-title {
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
	.dnf-staff-section-title::after {
		content: '';
		flex-grow: 1;
		height: 1px;
		background: var(--dnf-border, #e2e8f0);
	}

	/* Notes Box */
	.dnf-staff-notes-surface {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 16px;
		font-size: 13.5px;
		line-height: 1.6;
		color: var(--dnf-text-primary, #0f172a);
		white-space: pre-wrap;
	}

	/* Assigned Manifests Mini Table */
	.dnf-manifest-table-wrap {
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		overflow: hidden;
	}

	/* Print Overrides */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-staff-inspector-nav,
		.dev-nahian-sidebar {
			display: none !important;
		}
		.dev-nahian-content,
		.dnf-staff-scope,
		.dnf-staff-inspector-wrap {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
			border: none !important;
		}
		.dnf-staff-dossier-card {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
		}
	}
</style>

<div class="dnf-staff-inspector-wrap">

	<!-- Top Navigation Toolbar -->
	<div class="dnf-staff-inspector-nav">
		<div class="dnf-staff-inspector-nav-left">
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back to Staff Roster', 'dev-nahian-car' ); ?></span>
			</a>
			<span class="dnf-count-chip"><?php esc_html_e( 'Staff Dossier', 'dev-nahian-car' ); ?></span>
			<h2 style="margin:0; font-size:18px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
				<?php printf( esc_html__( 'Personnel Record #%d', 'dev-nahian-car' ), esc_html( (string) $staff['id'] ) ); ?>
			</h2>
		</div>

		<div style="display:flex; gap:8px;">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn dnf-btn-primary">
				<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Profile', 'dev-nahian-car' ); ?></span>
			</a>
			<button type="button" class="dnf-btn dnf-btn-secondary" onclick="window.print();">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span><?php esc_html_e( 'Print Dossier', 'dev-nahian-car' ); ?></span>
			</button>
			<?php if ( $assigned_trips_count > 0 ) : ?>
				<span class="dnf-btn dnf-btn-danger" style="opacity:0.4; cursor:not-allowed;" title="<?php esc_attr_e( 'Cannot retire personnel with assigned dispatch manifests.', 'dev-nahian-car' ); ?>">
					<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Retire', 'dev-nahian-car' ); ?></span>
				</span>
			<?php else : ?>
				<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn dnf-btn-danger" onclick="return confirm('<?php esc_attr_e( 'Permanently retire this staff member from the roster?', 'dev-nahian-car' ); ?>');">
					<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Retire', 'dev-nahian-car' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Main Dossier Card -->
	<div class="dnf-staff-dossier-card">
		<!-- Executive Banner Header -->
		<div class="dnf-staff-dossier-header">
			<div>
				<div class="dnf-staff-dossier-code">
					<?php
					printf(
						/* translators: %s: staff code identifier */
						esc_html__( 'OFFICIAL STAFF CODE: %s', 'dev-nahian-car' ),
						esc_html( (string) $staff['staff_code'] )
					);
					?>
				</div>
				<h2 class="dnf-staff-dossier-heading">
					<?php echo esc_html( (string) $staff['name'] ); ?>
				</h2>
			</div>
			<div style="display:flex; align-items:center; gap:8px;">
				<span class="dnf-role-badge">
					<?php echo esc_html( ucwords( str_replace( '_', ' ', $role_key ) ) ); ?>
				</span>
				<span class="dnf-status-pill <?php echo esc_attr( $status_key ); ?>">
					&bull; <?php echo esc_html( ucwords( str_replace( '_', ' ', $status_key ) ) ); ?>
				</span>
			</div>
		</div>

		<!-- Body Grid -->
		<div class="dnf-staff-dossier-body">
			<!-- KPI Highlights Grid -->
			<div class="dnf-kpi-grid">
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Email Address', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-size:13px; font-weight:600;"><?php echo esc_html( (string) $staff['email'] ); ?></span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Contact Phone', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val"><?php echo esc_html( ! empty( $staff['phone'] ) ? (string) $staff['phone'] : '—' ); ?></span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Driver License No.', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val">
						<code class="dnf-code"><?php echo esc_html( ! empty( $staff['license_no'] ) ? (string) $staff['license_no'] : __( 'None Logged', 'dev-nahian-car' ) ); ?></code>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Onboarded Date', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-size:13px; font-feature-settings:'tnum';">
						<?php
						$created_ts = ! empty( $staff['created_at'] ) ? strtotime( (string) $staff['created_at'] ) : false;
						echo esc_html( $created_ts ? wp_date( (string) get_option( 'date_format', 'F j, Y' ), $created_ts ) : '—' );
						?>
					</span>
				</div>
			</div>

			<!-- Operational Notes & Clearances -->
			<div>
				<div class="dnf-staff-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					<?php esc_html_e( 'Credentials, Language Proficiencies & Security Clearances', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-staff-notes-surface">
					<?php
					if ( ! empty( $staff['notes'] ) ) {
						echo esc_html( (string) $staff['notes'] );
					} else {
						echo '<em>' . esc_html__( 'No specific credentials, language proficiencies, or handling notes recorded for this personnel.', 'dev-nahian-car' ) . '</em>';
					}
					?>
				</div>
			</div>

			<!-- Assigned Bookings Manifest -->
			<div>
				<div class="dnf-staff-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 17h14v-5l-2-6H7L5 12v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
					<?php esc_html_e( 'Recent Dispatch Assignments', 'dev-nahian-car' ); ?>
				</div>

				<?php if ( ! empty( $assigned_bookings ) ) : ?>
					<div class="dnf-manifest-table-wrap">
						<table class="wp-list-table widefat fixed striped" style="width:100%; border:none; margin:0;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Booking ID', 'dev-nahian-car' ); ?></th>
									<th><?php esc_html_e( 'Assigned Chassis', 'dev-nahian-car' ); ?></th>
									<th><?php esc_html_e( 'Client', 'dev-nahian-car' ); ?></th>
									<th><?php esc_html_e( 'Schedule Window', 'dev-nahian-car' ); ?></th>
									<th><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
									<th style="text-align:right; width:80px;"><?php esc_html_e( 'Inspect', 'dev-nahian-car' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $assigned_bookings as $b ) :
									$b_status = sanitize_key( (string) ( $b['booking_status'] ?? 'pending' ) );
									$b_url    = add_query_arg(
										array(
											'page'   => 'dev-nahian-fleet-manager',
											'tab'    => 'booking',
											'action' => 'view',
											'id'     => absint( $b['id'] ),
										),
										admin_url( 'admin.php' )
									);
									?>
									<tr>
										<td><code class="dnf-code">#<?php echo esc_html( (string) $b['booking_code'] ); ?></code></td>
										<td><strong><?php echo esc_html( ! empty( $b['car_name'] ) ? (string) $b['car_name'] : __( 'Vehicle Asset', 'dev-nahian-car' ) ); ?></strong></td>
										<td><?php echo esc_html( (string) ( $b['customer_name'] ?? ( $b['customer_email'] ?? '' ) ) ); ?></td>
										<td style="font-size:12px; font-feature-settings:'tnum';"><?php echo esc_html( (string) ( $b['pickup_date'] ?? '' ) ); ?> &rarr; <?php echo esc_html( (string) ( $b['return_date'] ?? '' ) ); ?></td>
										<td><span class="dnf-status-pill <?php echo esc_attr( $b_status ); ?>">&bull; <?php echo esc_html( ucfirst( $b_status ) ); ?></span></td>
										<td style="text-align:right;">
											<a href="<?php echo esc_url( $b_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm" title="<?php esc_attr_e( 'Inspect Reservation', 'dev-nahian-car' ); ?>">
												<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="dnf-staff-notes-surface" style="text-align:center; color:var(--dnf-text-tertiary, #94a3b8); padding:20px;">
						<?php esc_html_e( 'No dispatch manifests currently assigned to this staff member.', 'dev-nahian-car' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

</div>
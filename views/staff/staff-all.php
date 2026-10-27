<?php
/**
 * Staff Module: All Personnel List Table (`staff-all.php`)
 * Displays tabulated staff directory with duty filter awareness, assignments check, and action controls.
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
$table_staff    = isset( $table_staff ) ? $table_staff : $wpdb->prefix . 'dev_nahian_staff';
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'staff',
	),
	admin_url( 'admin.php' )
);
$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Staff Members (Respecting Subtab Duty Status Filter)
// -----------------------------------------------------------------------------
$allowed_statuses = array( 'active', 'on_duty', 'off_duty', 'suspended' );

if ( in_array( $current_subtab, $allowed_statuses, true ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$staff_members = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_staff} WHERE status = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_subtab
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$staff_members = $wpdb->get_results( "SELECT * FROM {$table_staff} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$staff_count = is_array( $staff_members ) ? count( $staff_members ) : 0;

// -----------------------------------------------------------------------------
// 2. Aggregate Assigned Bookings (Prevent N+1 Queries)
// -----------------------------------------------------------------------------
$staff_assignments_map = array();
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$column_exists = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table_bookings} LIKE %s", 'driver_id' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

if ( ! empty( $column_exists ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$counts_raw = $wpdb->get_results( "SELECT driver_id, COUNT(*) as total FROM {$table_bookings} WHERE driver_id IS NOT NULL AND driver_id > 0 GROUP BY driver_id", ARRAY_A );
	if ( ! empty( $counts_raw ) ) {
		foreach ( $counts_raw as $cnt ) {
			$staff_assignments_map[ absint( $cnt['driver_id'] ) ] = absint( $cnt['total'] );
		}
	}
}
?>

<style>
	/* Scoped Staff Table Card */
	.dnf-staff-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-staff-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-staff-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-staff-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-staff-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-staff-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}

	/* Personnel Formatting */
	.dnf-personnel-name-link {
		font-size: 13.5px;
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		text-decoration: none;
		transition: color 0.15s ease;
	}
	.dnf-personnel-name-link:hover {
		color: var(--dnf-accent-teal, #0d9488);
	}
	.dnf-staff-license-sub {
		font-size: 11px;
		color: var(--dnf-text-tertiary, #94a3b8);
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
	}

	/* Actions */
	.dnf-actions-group {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		justify-content: flex-end;
	}
	.dnf-btn-icon {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		border-radius: var(--dnf-radius-md, 8px);
		border: 1px solid var(--dnf-border, #e2e8f0);
		background: #ffffff;
		color: var(--dnf-text-secondary, #475569);
		text-decoration: none;
		transition: all 0.15s ease-in-out;
		cursor: pointer;
	}
	.dnf-btn-icon:hover {
		border-color: var(--dnf-border-hover, #cbd5e1);
		color: var(--dnf-text-primary, #0f172a);
		transform: translateY(-1px);
	}
	.dnf-btn-icon.view:hover {
		background: #eff6ff;
		color: #2563eb;
		border-color: #bfdbfe;
	}
	.dnf-btn-icon.edit:hover {
		background: #fefce8;
		color: #b45309;
		border-color: #fef08a;
	}
	.dnf-btn-icon.delete:hover {
		background: var(--dnf-danger-bg, #fef2f2);
		color: var(--dnf-danger, #dc2626);
		border-color: var(--dnf-danger-border, #fecaca);
	}
	.dnf-btn-icon.disabled {
		opacity: 0.35;
		cursor: not-allowed;
		pointer-events: none;
	}
	.dnf-table-empty {
		text-align: center;
		padding: 36px 16px !important;
		color: var(--dnf-text-tertiary, #94a3b8);
		font-size: 13px;
	}
</style>

<div class="dnf-staff-table-card">
	<div class="dnf-staff-meta-bar">
		<div class="dnf-staff-counter">
			<?php
			printf(
				/* translators: %s: number of personnel */
				esc_html__( 'Active Personnel: %s staff registered', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $staff_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( str_replace( '_', ' ', $current_subtab ) ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-staff-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 110px;"><?php esc_html_e( 'Staff ID', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Full Name', 'dev-nahian-car' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Role / Designation', 'dev-nahian-car' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'License No.', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Duty Status', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $staff_members ) ) : ?>
					<?php
					foreach ( $staff_members as $m ) :
						$staff_id   = absint( $m['id'] );
						$status_key = sanitize_key( (string) ( $m['status'] ?? 'active' ) );
						$role_key   = sanitize_key( (string) ( $m['role_type'] ?? 'chauffeur' ) );
						$has_trips  = ( ( $staff_assignments_map[ $staff_id ] ?? 0 ) > 0 );

						$view_url = add_query_arg(
							array(
								'action' => 'view',
								'id'     => $staff_id,
							),
							$base
						);
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
						<tr>
							<!-- Staff ID -->
							<td>
								<code class="dnf-code"><?php echo esc_html( (string) $m['staff_code'] ); ?></code>
							</td>

							<!-- Name -->
							<td>
								<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-personnel-name-link">
									<?php echo esc_html( (string) $m['name'] ); ?>
								</a>
							</td>

							<!-- Role -->
							<td>
								<span class="dnf-role-badge">
									<?php echo esc_html( ucwords( str_replace( '_', ' ', $role_key ) ) ); ?>
								</span>
							</td>

							<!-- License Number -->
							<td>
								<span class="dnf-staff-license-sub">
									<?php echo esc_html( ! empty( $m['license_no'] ) ? (string) $m['license_no'] : __( 'No License', 'dev-nahian-car' ) ); ?>
								</span>
							</td>

							<!-- Status Pill -->
							<td>
								<span class="dnf-status-pill <?php echo esc_attr( $status_key ); ?>">
									&bull; <?php echo esc_html( ucwords( str_replace( '_', ' ', $status_key ) ) ); ?>
								</span>
							</td>

							<!-- Actions -->
							<td style="text-align: right;">
								<div class="dnf-actions-group">
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'Inspect Personnel Dossier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Staff Profile', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<?php if ( $has_trips ) : ?>
										<span class="dnf-btn-icon delete disabled" title="<?php esc_attr_e( 'Cannot retire personnel with assigned dispatch manifests.', 'dev-nahian-car' ); ?>">
											<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
									<?php else : ?>
										<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Remove Personnel Record', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Permanently retire this staff member from the roster?', 'dev-nahian-car' ); ?>');">
											<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6" class="dnf-table-empty">
							<?php esc_html_e( 'No personnel profiles found matching this duty status.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
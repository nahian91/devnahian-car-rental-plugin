<?php
/**
 * Maintenance Module: All Service Tickets List Table (`maint-all.php`)
 * Displays tabulated workshop tickets with live subtab status filtering and action controls.
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
$table_maint    = isset( $table_maint ) ? $table_maint : $wpdb->prefix . 'dev_nahian_maintenance';
$table_cars     = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'maintenance',
	),
	admin_url( 'admin.php' )
);
$currency       = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );
$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Maintenance Records (Respecting Subtab Status Filter)
// -----------------------------------------------------------------------------
if ( 'in_workshop' === $current_subtab ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$maint_records = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_maint} WHERE status IN (%s, %s) ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'in_workshop',
			'in_progress'
		),
		ARRAY_A
	);
} elseif ( in_array( $current_subtab, array( 'scheduled', 'completed', 'cancelled' ), true ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$maint_records = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_maint} WHERE status = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_subtab
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$maint_records = $wpdb->get_results( "SELECT * FROM {$table_maint} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$maint_count = is_array( $maint_records ) ? count( $maint_records ) : 0;
?>

<style>
	/* Scoped Maintenance Table Card */
	.dnf-maint-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-maint-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-maint-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-maint-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-maint-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-maint-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}

	/* Target Chassis Link */
	.dnf-chassis-name-link {
		font-size: 13.5px;
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		text-decoration: none;
		transition: color 0.15s ease;
	}
	.dnf-chassis-name-link:hover {
		color: var(--dnf-accent-teal, #0d9488);
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
	.dnf-table-empty {
		text-align: center;
		padding: 36px 16px !important;
		color: var(--dnf-text-tertiary, #94a3b8);
		font-size: 13px;
	}
</style>

<div class="dnf-maint-table-card">
	<div class="dnf-maint-meta-bar">
		<div class="dnf-maint-counter">
			<?php
			printf(
				/* translators: %s: number of service tickets */
				esc_html__( 'Workshop Ledger: %s service orders logged', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $maint_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( str_replace( '_', ' ', $current_subtab ) ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-maint-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 90px;"><?php esc_html_e( 'Order #', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Target Fleet Chassis', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Service Procedure', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Service Date', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Workshop Cost', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Service Status', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $maint_records ) ) : ?>
					<?php
					foreach ( $maint_records as $m ) :
						$maint_id   = absint( $m['id'] );
						$status_key = sanitize_key( (string) ( $m['status'] ?? 'scheduled' ) );

						$view_url = add_query_arg(
							array(
								'action' => 'view',
								'id'     => $maint_id,
							),
							$base
						);
						$edit_url = add_query_arg(
							array(
								'action' => 'edit',
								'id'     => $maint_id,
								'subtab' => 'add',
							),
							$base
						);
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'delete',
									'id'     => $maint_id,
								),
								$base
							),
							'dev_nahian_delete_maint_' . $maint_id
						);

						$service_time = ! empty( $m['service_date'] ) ? strtotime( (string) $m['service_date'] ) : false;
						?>
						<tr>
							<!-- Ticket # -->
							<td>
								<code class="dnf-code">#<?php echo esc_html( (string) $maint_id ); ?></code>
							</td>

							<!-- Target Vehicle Chassis -->
							<td>
								<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-chassis-name-link">
									<?php echo esc_html( ! empty( $m['car_name'] ) ? (string) $m['car_name'] : sprintf( __( 'Chassis #%d', 'dev-nahian-car' ), absint( $m['car_id'] ?? 0 ) ) ); ?>
								</a>
							</td>

							<!-- Service Procedure -->
							<td>
								<span style="font-size: 13px; font-weight: 600; color: var(--dnf-text-primary, #0f172a);">
									<?php echo esc_html( (string) $m['service_type'] ); ?>
								</span>
							</td>

							<!-- Service Date -->
							<td>
								<span style="font-size: 13px; color: var(--dnf-text-secondary, #475569); font-feature-settings: 'tnum';">
									<?php echo esc_html( $service_time ? wp_date( (string) get_option( 'date_format', 'Y-m-d' ), $service_time ) : (string) ( $m['service_date'] ?? '—' ) ); ?>
								</span>
							</td>

							<!-- Cost -->
							<td>
								<strong style="color: var(--dnf-text-primary, #0f172a); font-feature-settings: 'tnum';">
									<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $m['cost'] ?? 0.00 ), 2 ) ); ?>
								</strong>
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
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'Inspect Service Order Dossier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Service Ticket', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Delete Maintenance Record', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this maintenance log record?', 'dev-nahian-car' ); ?>');">
										<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="7" class="dnf-table-empty">
							<?php esc_html_e( 'No maintenance service orders found matching this workshop status.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
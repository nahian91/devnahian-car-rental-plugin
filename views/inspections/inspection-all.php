<?php
/**
 * Inspections Module: All Reports List Table (`inspection-all.php`)
 * Displays tabulated walkaround audit logs with live subtab phase filtering and action controls.
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
$table_insp     = isset( $table_insp ) ? $table_insp : $wpdb->prefix . 'dev_nahian_inspections';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'inspections',
	),
	admin_url( 'admin.php' )
);
$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Inspection Records (Respecting Subtab Phase Filter)
// -----------------------------------------------------------------------------
$allowed_phases = array( 'checkout', 'checkin', 'spot_check' );

if ( in_array( $current_subtab, $allowed_phases, true ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$inspections = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_insp} WHERE inspection_type = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_subtab
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$inspections = $wpdb->get_results( "SELECT * FROM {$table_insp} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$insp_count = is_array( $inspections ) ? count( $inspections ) : 0;
?>

<style>
	/* Scoped Inspections Table Card */
	.dnf-insp-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-insp-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-insp-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-insp-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-insp-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-insp-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}

	/* Telemetry Stack */
	.dnf-telemetry-stack {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}
	.dnf-odo-sub {
		font-size: 11px;
		color: var(--dnf-text-tertiary, #94a3b8);
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-feature-settings: 'tnum';
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

<div class="dnf-insp-table-card">
	<div class="dnf-insp-meta-bar">
		<div class="dnf-insp-counter">
			<?php
			printf(
				/* translators: %s: number of inspection reports */
				esc_html__( 'Condition Ledger: %s reports filed', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $insp_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( str_replace( '_', ' ', $current_subtab ) ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-insp-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 120px;"><?php esc_html_e( 'Manifest Code', 'dev-nahian-car' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Audit Phase', 'dev-nahian-car' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Fuel / Odometer', 'dev-nahian-car' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Inspector Agent', 'dev-nahian-car' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Timestamp', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $inspections ) ) : ?>
					<?php
					foreach ( $inspections as $ins ) :
						$insp_id  = absint( $ins['id'] );
						$type_key = sanitize_key( (string) ( $ins['inspection_type'] ?? 'checkout' ) );

						$view_url = add_query_arg(
							array(
								'action' => 'view',
								'id'     => $insp_id,
							),
							$base
						);
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

						$timestamp = ! empty( $ins['inspected_at'] ) ? strtotime( (string) $ins['inspected_at'] ) : false;
						?>
						<tr>
							<!-- Manifest Code -->
							<td>
								<code class="dnf-code">#<?php echo esc_html( (string) ( $ins['booking_code'] ?? 'GENERAL' ) ); ?></code>
							</td>

							<!-- Audit Phase -->
							<td>
								<span class="dnf-status-pill <?php echo esc_attr( $type_key ); ?>">
									&bull; <?php echo esc_html( ucwords( str_replace( '_', ' ', $type_key ) ) ); ?>
								</span>
							</td>

							<!-- Fuel & Odometer Telematics -->
							<td>
								<div class="dnf-telemetry-stack">
									<code class="dnf-code"><?php echo esc_html( (string) ( $ins['fuel_level'] ?? 100 ) . '% Fuel' ); ?></code>
									<span class="dnf-odo-sub">
										<?php echo esc_html( number_format_i18n( absint( $ins['odometer'] ?? 0 ) ) . ' mi' ); ?>
									</span>
								</div>
							</td>

							<!-- Inspector Agent -->
							<td>
								<span style="font-size: 13px; font-weight: 500; color: var(--dnf-text-secondary, #475569);">
									<?php echo esc_html( (string) ( $ins['inspector_name'] ?? '—' ) ); ?>
								</span>
							</td>

							<!-- Timestamp -->
							<td>
								<span style="font-size: 12.5px; color: var(--dnf-text-secondary, #475569); font-feature-settings: 'tnum';">
									<?php
									echo esc_html(
										$timestamp
											? wp_date( (string) get_option( 'date_format', 'M j, Y' ) . ' ' . (string) get_option( 'time_format', 'H:i' ), $timestamp )
											: '—'
									);
									?>
								</span>
							</td>

							<!-- Actions -->
							<td style="text-align: right;">
								<div class="dnf-actions-group">
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'Inspect Condition Dossier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Report', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Delete Inspection Log', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this inspection record from the condition ledger?', 'dev-nahian-car' ); ?>');">
										<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6" class="dnf-table-empty">
							<?php esc_html_e( 'No vehicle condition reports found matching this inspection phase.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
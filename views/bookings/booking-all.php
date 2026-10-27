<?php
/**
 * Bookings Module: All Bookings List Table (`booking-all.php`)
 * Displays tabulated reservations with filtering, DataTables compatibility, and action controls.
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

// Ensure variables are defined with safe fallbacks.
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'booking',
	),
	admin_url( 'admin.php' )
);
$currency       = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );
$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Records (Respecting Subtab Filter)
// -----------------------------------------------------------------------------
$allowed_statuses = array( 'active', 'pending', 'completed', 'cancelled' );

if ( in_array( $current_subtab, $allowed_statuses, true ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$bookings = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_bookings} WHERE booking_status = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_subtab
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$bookings = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$booking_count = is_array( $bookings ) ? count( $bookings ) : 0;
?>

<style>
	/* Scoped Table & Action Controls */
	.dnf-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
		overflow: hidden;
	}
	.dnf-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-record-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-record-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}
	.dnf-customer-cell {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}
	.dnf-customer-name {
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		font-size: 13px;
	}
	.dnf-customer-sub {
		font-size: 11.5px;
		color: var(--dnf-text-secondary, #64748b);
	}
	.dnf-datetime-stack {
		display: flex;
		flex-direction: column;
		font-size: 12px;
		line-height: 1.4;
	}
	.dnf-datetime-date {
		font-weight: 600;
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-datetime-time {
		color: var(--dnf-text-secondary, #64748b);
		font-size: 11px;
	}
	.dnf-amount-val {
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		font-feature-settings: "tnum";
	}

	/* Action Buttons */
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
		background: #fef2f2;
		color: #dc2626;
		border-color: #fecaca;
	}
	.dnf-table-empty {
		text-align: center;
		padding: 36px 16px !important;
		color: var(--dnf-text-tertiary, #94a3b8);
		font-size: 13px;
	}
</style>

<div class="dnf-table-card">
	<div class="dnf-meta-bar">
		<div class="dnf-record-counter">
			<?php
			printf(
				/* translators: %s: number of records */
				esc_html__( 'Displaying: %s manifests', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $booking_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( $current_subtab ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 105px;"><?php esc_html_e( 'Code', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Client Profile', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Contact', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Fleet Asset', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Pickup Window', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Return Window', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Invoiced Total', 'dev-nahian-car' ); ?></th>
					<th style="width: 110px;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $bookings ) ) : ?>
					<?php
					foreach ( $bookings as $b ) :
						$booking_id = absint( $b['id'] );
						$raw_status = sanitize_key( $b['booking_status'] ?? 'pending' );

						$view_url   = add_query_arg(
							array(
								'action' => 'view',
								'id'     => $booking_id,
							),
							$base
						);
						$edit_url   = add_query_arg(
							array(
								'action' => 'edit',
								'id'     => $booking_id,
								'subtab' => 'add',
							),
							$base
						);
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'delete',
									'id'     => $booking_id,
								),
								$base
							),
							'dev_nahian_delete_booking_' . $booking_id
						);

						$pickup_ts = ! empty( $b['pickup_date'] ) ? strtotime( (string) $b['pickup_date'] ) : false;
						$return_ts = ! empty( $b['return_date'] ) ? strtotime( (string) $b['return_date'] ) : false;
						?>
						<tr>
							<!-- Booking Identifier -->
							<td>
								<code class="dnf-code">#<?php echo esc_html( (string) ( $b['booking_code'] ?? $booking_id ) ); ?></code>
							</td>

							<!-- Customer Profile -->
							<td>
								<div class="dnf-customer-cell">
									<span class="dnf-customer-name"><?php echo esc_html( (string) ( $b['customer_name'] ?? '—' ) ); ?></span>
									<span class="dnf-customer-sub"><?php echo esc_html( (string) ( $b['customer_email'] ?? '—' ) ); ?></span>
								</div>
							</td>

							<!-- Contact Number -->
							<td>
								<span style="font-size: 13px; font-weight: 500; color: var(--dnf-text-secondary, #475569);">
									<?php echo esc_html( ! empty( $b['customer_phone'] ) ? (string) $b['customer_phone'] : '—' ); ?>
								</span>
							</td>

							<!-- Assigned Fleet Vehicle -->
							<td>
								<strong style="color: var(--dnf-text-primary, #0f172a); font-size: 13px;">
									<?php echo esc_html( ! empty( $b['car_name'] ) ? (string) $b['car_name'] : __( 'Fleet Asset', 'dev-nahian-car' ) ); ?>
								</strong>
							</td>

							<!-- Pickup Window -->
							<td>
								<div class="dnf-datetime-stack">
									<span class="dnf-datetime-date">
										<?php echo esc_html( $pickup_ts ? wp_date( (string) get_option( 'date_format', 'M j, Y' ), $pickup_ts ) : (string) ( $b['pickup_date'] ?? '—' ) ); ?>
									</span>
									<?php if ( ! empty( $b['pickup_time'] ) ) : ?>
										<span class="dnf-datetime-time"><?php echo esc_html( (string) $b['pickup_time'] ); ?></span>
									<?php endif; ?>
								</div>
							</td>

							<!-- Return Window -->
							<td>
								<div class="dnf-datetime-stack">
									<span class="dnf-datetime-date">
										<?php echo esc_html( $return_ts ? wp_date( (string) get_option( 'date_format', 'M j, Y' ), $return_ts ) : (string) ( $b['return_date'] ?? '—' ) ); ?>
									</span>
									<?php if ( ! empty( $b['return_time'] ) ) : ?>
										<span class="dnf-datetime-time"><?php echo esc_html( (string) $b['return_time'] ); ?></span>
									<?php endif; ?>
								</div>
							</td>

							<!-- Invoiced Total -->
							<td>
								<span class="dnf-amount-val">
									<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $b['total_amount'] ?? 0 ), 2 ) ); ?>
								</span>
							</td>

							<!-- Dispatch Status Pill -->
							<td>
								<span class="dnf-status-pill <?php echo esc_attr( $raw_status ); ?>">
									&bull; <?php echo esc_html( ucfirst( $raw_status ) ); ?>
								</span>
							</td>

							<!-- Action Buttons -->
							<td style="text-align: right;">
								<div class="dnf-actions-group">
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'View Booking Dossier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Booking', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Delete Booking Record', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to permanently delete this reservation record?', 'dev-nahian-car' ); ?>');">
										<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="9" class="dnf-table-empty">
							<?php esc_html_e( 'No reservation manifests found matching the selected filter.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
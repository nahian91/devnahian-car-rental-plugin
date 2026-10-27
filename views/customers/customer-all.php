<?php
/**
 * Customers Module: All Customers List Table (`customer-all.php`)
 * Displays tabulated customer directories with live subtab filtering and action triggers.
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
$table_customers = isset( $table_customers ) ? $table_customers : $wpdb->prefix . 'dev_nahian_customers';
$table_bookings  = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$base            = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'customers',
	),
	admin_url( 'admin.php' )
);
$current_subtab  = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Customers (Respecting Subtab Status Filter)
// -----------------------------------------------------------------------------
$allowed_statuses = array( 'verified', 'pending', 'suspended' );

if ( in_array( $current_subtab, $allowed_statuses, true ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$customers = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_customers} WHERE status = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_subtab
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$customers = $wpdb->get_results( "SELECT * FROM {$table_customers} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$cust_count = is_array( $customers ) ? count( $customers ) : 0;

// -----------------------------------------------------------------------------
// 2. Aggregate Booking Allocations (Prevent N+1 Queries)
// -----------------------------------------------------------------------------
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking_counts_raw = $wpdb->get_results( "SELECT customer_email, COUNT(*) as total FROM {$table_bookings} GROUP BY customer_email", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$booking_counts_map = array();

if ( ! empty( $booking_counts_raw ) ) {
	foreach ( $booking_counts_raw as $b_row ) {
		$booking_counts_map[ strtolower( trim( (string) $b_row['customer_email'] ) ) ] = absint( $b_row['total'] );
	}
}
?>

<style>
	/* Scoped Customer Table Card */
	.dnf-cust-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-cust-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-cust-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-cust-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-cust-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-cust-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}

	/* Customer Cell Formatting */
	.dnf-customer-profile-cell {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}
	.dnf-cust-name-link {
		font-size: 13.5px;
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		text-decoration: none;
		transition: color 0.15s ease;
	}
	.dnf-cust-name-link:hover {
		color: var(--dnf-accent-teal, #0d9488);
	}
	.dnf-cust-sub {
		font-size: 11.5px;
		color: var(--dnf-text-secondary, #64748b);
	}
	.dnf-license-stack {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}
	.dnf-country-tag {
		font-size: 11px;
		color: var(--dnf-text-tertiary, #94a3b8);
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

<div class="dnf-cust-table-card">
	<div class="dnf-cust-meta-bar">
		<div class="dnf-cust-counter">
			<?php
			printf(
				/* translators: %s: number of client profiles */
				esc_html__( 'Directory Records: %s profiles registered', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $cust_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( $current_subtab ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-cust-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Customer Name', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Email Address', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Contact Phone', 'dev-nahian-car' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'License / Country', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $customers ) ) : ?>
					<?php
					foreach ( $customers as $c ) :
						$cust_id   = absint( $c['id'] );
						$c_email   = strtolower( trim( (string) ( $c['email'] ?? '' ) ) );
						$has_trips = ( ( $booking_counts_map[ $c_email ] ?? 0 ) > 0 );

						$view_url = add_query_arg(
							array(
								'action' => 'view',
								'id'     => $cust_id,
							),
							$base
						);
						$edit_url = add_query_arg(
							array(
								'action' => 'edit',
								'id'     => $cust_id,
								'subtab' => 'add',
							),
							$base
						);
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'delete',
									'id'     => $cust_id,
								),
								$base
							),
							'dev_nahian_delete_customer_' . $cust_id
						);
						?>
						<tr>
							<!-- Customer Profile Name -->
							<td>
								<div class="dnf-customer-profile-cell">
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-cust-name-link">
										<?php echo esc_html( (string) $c['name'] ); ?>
									</a>
									<?php if ( ! empty( $c['customer_code'] ) ) : ?>
										<span class="dnf-cust-sub"><code><?php echo esc_html( (string) $c['customer_code'] ); ?></code></span>
									<?php endif; ?>
								</div>
							</td>

							<!-- Email Address -->
							<td>
								<span style="font-size: 13px; color: var(--dnf-text-secondary, #475569);">
									<?php echo esc_html( (string) $c['email'] ); ?>
								</span>
							</td>

							<!-- Contact Phone -->
							<td>
								<span style="font-size: 13px; font-weight: 500; color: var(--dnf-text-secondary, #475569);">
									<?php echo esc_html( ! empty( $c['phone'] ) ? (string) $c['phone'] : '—' ); ?>
								</span>
							</td>

							<!-- License Number & Country -->
							<td>
								<div class="dnf-license-stack">
									<code class="dnf-code"><?php echo esc_html( ! empty( $c['license_number'] ) ? (string) $c['license_number'] : '—' ); ?></code>
									<span class="dnf-country-tag"><?php echo esc_html( ! empty( $c['country'] ) ? (string) $c['country'] : 'Global Jurisdiction' ); ?></span>
								</div>
							</td>

							<!-- Action Controls -->
							<td style="text-align: right;">
								<div class="dnf-actions-group">
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'Inspect Customer Dossier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Customer Profile', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<?php if ( $has_trips ) : ?>
										<span class="dnf-btn-icon delete disabled" title="<?php esc_attr_e( 'Cannot delete customer profile with registered booking manifests.', 'dev-nahian-car' ); ?>">
											<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
									<?php else : ?>
										<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Permanently Remove Profile', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to permanently delete this customer dossier?', 'dev-nahian-car' ); ?>');">
											<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" class="dnf-table-empty">
							<?php esc_html_e( 'No customer profiles found matching this status filter.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
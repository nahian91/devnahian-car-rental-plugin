<?php
/**
 * Promos Module: All Coupons List Table (`promo-all.php`)
 * Displays tabulated promotional vouchers with live status filtering, redemption metering, and action controls.
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
$table_promos   = isset( $table_promos ) ? $table_promos : $wpdb->prefix . 'dev_nahian_promos';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'promos',
	),
	admin_url( 'admin.php' )
);
$currency       = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );
$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Promotional Coupons (Respecting Subtab Status Filter)
// -----------------------------------------------------------------------------
$allowed_statuses = array( 'active', 'expired', 'disabled' );

if ( in_array( $current_subtab, $allowed_statuses, true ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$promos = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_promos} WHERE status = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_subtab
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$promos = $wpdb->get_results( "SELECT * FROM {$table_promos} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$promo_count = is_array( $promos ) ? count( $promos ) : 0;
?>

<style>
	/* Scoped Promos Table Card */
	.dnf-promo-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-promo-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-promo-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-promo-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-promo-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-promo-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}

	/* Usage & Redemption Progress Meter */
	.dnf-redemption-meter {
		display: flex;
		flex-direction: column;
		gap: 4px;
		min-width: 130px;
	}
	.dnf-meter-track {
		width: 100%;
		height: 6px;
		background: var(--dnf-bg-subtle, #f1f5f9);
		border-radius: var(--dnf-radius-pill, 9999px);
		overflow: hidden;
	}
	.dnf-meter-fill {
		height: 100%;
		background: var(--dnf-accent-teal, #0d9488);
		border-radius: var(--dnf-radius-pill, 9999px);
		transition: width 0.3s ease;
	}
	.dnf-meter-fill.near-cap {
		background: var(--dnf-accent-gold, #d97706);
	}
	.dnf-meter-fill.maxed {
		background: var(--dnf-danger, #dc2626);
	}
	.dnf-meter-text {
		font-size: 11.5px;
		font-weight: 600;
		color: var(--dnf-text-secondary, #475569);
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
</style>

<div class="dnf-promo-table-card">
	<div class="dnf-promo-meta-bar">
		<div class="dnf-promo-counter">
			<?php
			printf(
				/* translators: %s: number of promo vouchers */
				esc_html__( 'Promotions Ledger: %s vouchers active', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $promo_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( str_replace( '_', ' ', $current_subtab ) ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-promo-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 160px;"><?php esc_html_e( 'Coupon Code', 'dev-nahian-car' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Discount Yield', 'dev-nahian-car' ); ?></th>
					<th><?php esc_html_e( 'Redemption Meter', 'dev-nahian-car' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Expiration Window', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 110px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $promos ) ) : ?>
					<?php
					foreach ( $promos as $p ) :
						$promo_id   = absint( $p['id'] );
						$status_key = sanitize_key( (string) ( $p['status'] ?? 'active' ) );
						$edit_url   = add_query_arg(
							array(
								'action' => 'edit',
								'id'     => $promo_id,
								'subtab' => 'add',
							),
							$base
						);
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'delete',
									'id'     => $promo_id,
								),
								$base
							),
							'dev_nahian_delete_promo_' . $promo_id
						);

						$discount_display = ( 'percentage' === $p['discount_type'] )
							? number_format_i18n( (float) $p['discount_value'], 1 ) . '%'
							: $currency . ' ' . number_format_i18n( (float) $p['discount_value'], 2 );

						$times_used  = absint( $p['times_used'] ?? 0 );
						$usage_limit = max( 1, absint( $p['usage_limit'] ?? 100 ) );
						$pct_used    = min( 100, round( ( $times_used / $usage_limit ) * 100 ) );

						$meter_class = '';
						if ( $pct_used >= 100 ) {
							$meter_class = 'maxed';
						} elseif ( $pct_used >= 80 ) {
							$meter_class = 'near-cap';
						}

						$expiry_time = ! empty( $p['expiry_date'] ) ? strtotime( (string) $p['expiry_date'] ) : false;
						$is_expired  = ( $expiry_time && $expiry_time < current_time( 'timestamp' ) );
						?>
						<tr>
							<!-- Coupon Code -->
							<td>
								<code class="dnf-code" style="font-size: 13.5px; font-weight: 800; letter-spacing: 0.04em;">
									<?php echo esc_html( (string) $p['code'] ); ?>
								</code>
							</td>

							<!-- Discount Yield -->
							<td>
								<strong style="font-size: 14px; color: var(--dnf-text-primary, #0f172a); font-feature-settings: 'tnum';">
									<?php echo esc_html( $discount_display ); ?>
								</strong>
								<span style="font-size: 11px; color: var(--dnf-text-tertiary, #94a3b8); display: block; text-transform: uppercase;">
									<?php echo esc_html( (string) $p['discount_type'] ); ?>
								</span>
							</td>

							<!-- Redemption Meter -->
							<td>
								<div class="dnf-redemption-meter">
									<div class="dnf-meter-track">
										<div class="dnf-meter-fill <?php echo esc_attr( $meter_class ); ?>" style="width: <?php echo esc_attr( (string) $pct_used ); ?>%;"></div>
									</div>
									<span class="dnf-meter-text">
										<?php
										printf(
											/* translators: 1: times used, 2: usage limit */
											esc_html__( '%1$s / %2$s redeemed', 'dev-nahian-car' ),
											esc_html( (string) number_format_i18n( $times_used ) ),
											esc_html( (string) number_format_i18n( $usage_limit ) )
										);
										?>
									</span>
								</div>
							</td>

							<!-- Expiration Window -->
							<td>
								<?php if ( $expiry_time ) : ?>
									<span style="font-size: 13px; font-weight: 500; color: <?php echo $is_expired ? 'var(--dnf-danger, #dc2626)' : 'var(--dnf-text-secondary, #475569)'; ?>;">
										<?php echo esc_html( wp_date( (string) get_option( 'date_format', 'M j, Y' ), $expiry_time ) ); ?>
									</span>
									<?php if ( $is_expired ) : ?>
										<span style="font-size: 10.5px; color: var(--dnf-danger, #dc2626); display: block; font-weight: 700;">
											<?php esc_html_e( '(Expired Window)', 'dev-nahian-car' ); ?>
										</span>
									<?php endif; ?>
								<?php else : ?>
									<span style="font-size: 12.5px; color: var(--dnf-text-tertiary, #94a3b8);">
										<?php esc_html_e( 'No Expiration', 'dev-nahian-car' ); ?>
									</span>
								<?php endif; ?>
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
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Promo Voucher', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Delete Promo Code', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this promo code from the promotions ledger?', 'dev-nahian-car' ); ?>');">
										<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6" class="dnf-table-empty">
							<?php esc_html_e( 'No promotional coupons found matching this lifecycle status.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
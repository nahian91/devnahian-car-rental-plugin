<?php
/**
 * Finances Module: All Transactions List Table (`finance-all.php`)
 * Displays tabulated treasury entries with live accounting type filtering and action controls.
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
$table_finances = isset( $table_finances ) ? $table_finances : $wpdb->prefix . 'dev_nahian_finances';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'finances',
	),
	admin_url( 'admin.php' )
);
$currency       = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );
$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Financial Transactions (Respecting Subtab Type Filter)
// -----------------------------------------------------------------------------
$allowed_types = array( 'income', 'expense' );

if ( in_array( $current_subtab, $allowed_types, true ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$transactions = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_finances} WHERE type = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_subtab
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$transactions = $wpdb->get_results( "SELECT * FROM {$table_finances} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$txn_count = is_array( $transactions ) ? count( $transactions ) : 0;
?>

<style>
	/* Scoped Financial Table Card */
	.dnf-fin-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-fin-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-fin-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-fin-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-fin-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-fin-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}

	/* Amount Typography */
	.dnf-amount-val {
		font-size: 13.5px;
		font-weight: 800;
		font-feature-settings: 'tnum';
	}
	.dnf-amount-val.income {
		color: #16a34a;
	}
	.dnf-amount-val.expense {
		color: var(--dnf-danger, #dc2626);
	}

	/* Category Chip */
	.dnf-cat-badge {
		font-size: 11.5px;
		font-weight: 600;
		color: var(--dnf-text-secondary, #475569);
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		padding: 2px 8px;
		border-radius: var(--dnf-radius-sm, 6px);
		display: inline-block;
		text-transform: capitalize;
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
</style>

<div class="dnf-fin-table-card">
	<div class="dnf-fin-meta-bar">
		<div class="dnf-fin-counter">
			<?php
			printf(
				/* translators: %s: number of transactions */
				esc_html__( 'Treasury Registry: %s entries recorded', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $txn_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( str_replace( '_', ' ', $current_subtab ) ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-fin-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 90px;"><?php esc_html_e( 'Entry ID', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Category', 'dev-nahian-car' ); ?></th>
					<th style="width: 120px;"><?php esc_html_e( 'Flow Type', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Amount', 'dev-nahian-car' ); ?></th>
					<th style="width: 130px;"><?php esc_html_e( 'Logged Date', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $transactions ) ) : ?>
					<?php
					foreach ( $transactions as $t ) :
						$txn_id   = absint( $t['id'] );
						$type_key = sanitize_key( (string) ( $t['type'] ?? 'income' ) );
						$sign     = ( 'expense' === $type_key ) ? '-' : '+';

						$view_url   = add_query_arg(
							array(
								'action' => 'view',
								'id'     => $txn_id,
							),
							$base
						);
						$edit_url   = add_query_arg(
							array(
								'action' => 'edit',
								'id'     => $txn_id,
								'subtab' => 'add',
							),
							$base
						);
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'delete',
									'id'     => $txn_id,
								),
								$base
							),
							'dev_nahian_delete_txn_' . $txn_id
						);

						$txn_time = ! empty( $t['txn_date'] ) ? strtotime( (string) $t['txn_date'] ) : false;
						?>
						<tr>
							<!-- Entry ID -->
							<td>
								<code class="dnf-code">#<?php echo esc_html( (string) $txn_id ); ?></code>
							</td>

							<!-- Category -->
							<td>
								<span class="dnf-cat-badge"><?php echo esc_html( (string) ( $t['category'] ?? 'rental' ) ); ?></span>
							</td>

							<!-- Type Pill -->
							<td>
								<span class="dnf-status-pill <?php echo esc_attr( $type_key ); ?>">
									&bull; <?php echo esc_html( ucfirst( $type_key ) ); ?>
								</span>
							</td>

							<!-- Amount -->
							<td>
								<strong class="dnf-amount-val <?php echo esc_attr( $type_key ); ?>">
									<?php echo esc_html( $sign . ' ' . $currency . ' ' . number_format_i18n( (float) ( $t['amount'] ?? 0.00 ), 2 ) ); ?>
								</strong>
							</td>

							<!-- Logged Date -->
							<td>
								<span style="font-size: 13px; color: var(--dnf-text-secondary, #475569);">
									<?php echo esc_html( $txn_time ? wp_date( (string) get_option( 'date_format', 'Y-m-d' ), $txn_time ) : (string) ( $t['txn_date'] ?? '—' ) ); ?>
								</span>
							</td>

							<!-- Actions -->
							<td style="text-align: right;">
								<div class="dnf-actions-group">
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'Inspect Transaction Dossier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Entry', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Delete Ledger Entry', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this financial entry from the treasury ledger?', 'dev-nahian-car' ); ?>');">
										<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6" class="dnf-table-empty">
							<?php esc_html_e( 'No financial income or expense entries logged matching this view.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
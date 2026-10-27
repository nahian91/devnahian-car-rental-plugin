<?php
/**
 * Finances Module: Single Transaction Detail View (`finance-view.php`)
 * Detailed treasury dossier with accounting classification, cross-reference links, and audit notes.
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
$table_finances = isset( $table_finances ) ? $table_finances : $wpdb->prefix . 'dev_nahian_finances';
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$table_maint    = isset( $table_maint ) ? $table_maint : $wpdb->prefix . 'dev_nahian_maintenance';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'finances',
	),
	admin_url( 'admin.php' )
);
$currency       = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

if ( $id <= 0 ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid transaction identifier supplied.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$txn = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_finances} WHERE id = %d", $id ), ARRAY_A );

if ( ! $txn ) :
	?>
	<div class="notice notice-error"><p><?php esc_html_e( 'Transaction record not found or already deleted from ledger.', 'dev-nahian-car' ); ?></p></div>
	<p><a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">&larr; <?php esc_html_e( 'Back to Treasury Ledger', 'dev-nahian-car' ); ?></a></p>
	<?php
	return;
endif;

$txn_id    = absint( $txn['id'] );
$type_key  = sanitize_key( (string) ( $txn['type'] ?? 'income' ) );
$sign      = ( 'expense' === $type_key ) ? '-' : '+';
$ref_code  = sanitize_text_field( (string) ( $txn['reference_code'] ?? '' ) );

// Check for cross-referenced booking or maintenance ticket.
$linked_booking = null;
$linked_maint   = null;

if ( '' !== $ref_code ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$linked_booking = $wpdb->get_row( $wpdb->prepare( "SELECT id, booking_code, car_name, customer_name FROM {$table_bookings} WHERE booking_code = %s", ltrim( $ref_code, '#' ) ), ARRAY_A );

	if ( ! $linked_booking && is_numeric( ltrim( $ref_code, '#MO-' ) ) ) {
		$maint_id = absint( ltrim( $ref_code, '#MO-' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$linked_maint = $wpdb->get_row( $wpdb->prepare( "SELECT id, car_name, service_type FROM {$table_maint} WHERE id = %d", $maint_id ), ARRAY_A );
	}
}

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
?>

<style>
	/* Scoped Financial Inspector Styles */
	.dnf-fin-inspector-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Navigation Toolbar */
	.dnf-fin-inspector-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-fin-inspector-nav-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Main Dossier Card */
	.dnf-fin-dossier-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		overflow: hidden;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
	}

	/* Executive Header Banner */
	.dnf-fin-dossier-header {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
		color: #ffffff;
		padding: 24px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-fin-dossier-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 11.5px;
		font-weight: 700;
		color: var(--dnf-accent-gold, #b88e1e);
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.dnf-fin-dossier-heading {
		margin: 4px 0 0;
		font-size: 22px;
		font-weight: 800;
		color: #ffffff;
		letter-spacing: -0.02em;
	}

	/* Body Content Grid */
	.dnf-fin-dossier-body {
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
		font-feature-settings: 'tnum';
	}
	.dnf-kpi-val.income {
		color: #16a34a;
	}
	.dnf-kpi-val.expense {
		color: var(--dnf-danger, #dc2626);
	}

	/* Section Headings */
	.dnf-fin-section-title {
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
	.dnf-fin-section-title::after {
		content: '';
		flex-grow: 1;
		height: 1px;
		background: var(--dnf-border, #e2e8f0);
	}

	/* Notes Surface */
	.dnf-fin-notes-surface {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 18px;
		font-size: 13.5px;
		line-height: 1.6;
		color: var(--dnf-text-primary, #0f172a);
		white-space: pre-wrap;
	}

	/* Linked Manifest Surface */
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

	/* Print Styles for Vouchers & Receipts */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-fin-inspector-nav,
		.dev-nahian-sidebar {
			display: none !important;
		}
		.dev-nahian-content,
		.dnf-fin-scope,
		.dnf-fin-inspector-wrap {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
			border: none !important;
		}
		.dnf-fin-dossier-card {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
		}
	}
</style>

<div class="dnf-fin-inspector-wrap">

	<!-- Inspector Navigation Toolbar -->
	<div class="dnf-fin-inspector-nav">
		<div class="dnf-fin-inspector-nav-left">
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Back to Treasury Ledger', 'dev-nahian-car' ); ?>
			</a>
			<span class="dnf-count-chip"><?php esc_html_e( 'Financial Dossier', 'dev-nahian-car' ); ?></span>
			<h2 style="margin:0; font-size:18px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
				<?php printf( esc_html__( 'Transaction Audit #%d', 'dev-nahian-car' ), esc_html( (string) $txn_id ) ); ?>
			</h2>
		</div>

		<div style="display:flex; gap:8px;">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn dnf-btn-primary">
				<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Edit Entry', 'dev-nahian-car' ); ?>
			</a>
			<button type="button" class="dnf-btn dnf-btn-secondary" onclick="window.print();">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<?php esc_html_e( 'Print Voucher', 'dev-nahian-car' ); ?>
			</button>
			<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn dnf-btn-danger" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this ledger transaction from records?', 'dev-nahian-car' ); ?>');">
				<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Delete', 'dev-nahian-car' ); ?>
			</a>
		</div>
	</div>

	<!-- Main Dossier Card -->
	<div class="dnf-fin-dossier-card">
		<!-- Executive Banner Header -->
		<div class="dnf-fin-dossier-header">
			<div>
				<div class="dnf-fin-dossier-code">
					<?php
					printf(
						/* translators: %s: reference invoice code */
						esc_html__( 'AUDIT REFERENCE: %s', 'dev-nahian-car' ),
						esc_html( '' !== $ref_code ? $ref_code : 'N/A' )
					);
					?>
				</div>
				<h2 class="dnf-fin-dossier-heading">
					<?php echo esc_html( (string) $txn['title'] ); ?>
				</h2>
			</div>
			<div>
				<span class="dnf-status-pill <?php echo esc_attr( $type_key ); ?>" style="font-size:12px; padding:4px 12px;">
					&bull; <?php echo esc_html( ucfirst( $type_key ) ); ?>
				</span>
			</div>
		</div>

		<!-- Body Grid -->
		<div class="dnf-fin-dossier-body">
			<!-- KPI Highlights Grid -->
			<div class="dnf-kpi-grid">
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Net Recorded Flow', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val <?php echo esc_attr( $type_key ); ?>" style="font-size:18px;">
						<?php echo esc_html( $sign . ' ' . $currency . ' ' . number_format_i18n( (float) ( $txn['amount'] ?? 0.00 ), 2 ) ); ?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Accounting Category', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="text-transform: capitalize;">
						<?php echo esc_html( (string) ( $txn['category'] ?? 'rental' ) ); ?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'Effective Date', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val" style="font-size:13.5px;">
						<?php
						$txn_time = ! empty( $txn['txn_date'] ) ? strtotime( (string) $txn['txn_date'] ) : false;
						echo esc_html( $txn_time ? wp_date( (string) get_option( 'date_format', 'F j, Y' ), $txn_time ) : '—' );
						?>
					</span>
				</div>
				<div class="dnf-kpi-box">
					<span class="dnf-kpi-label"><?php esc_html_e( 'System Entry ID', 'dev-nahian-car' ); ?></span>
					<span class="dnf-kpi-val">
						<code class="dnf-code">#<?php echo esc_html( (string) $txn_id ); ?></code>
					</span>
				</div>
			</div>

			<!-- Linked Parent Record (Booking or Maintenance) -->
			<?php if ( ! empty( $linked_booking ) || ! empty( $linked_maint ) ) : ?>
				<div>
					<div class="dnf-fin-section-title">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
						<?php esc_html_e( 'Associated Manifest / Work Order Telematics', 'dev-nahian-car' ); ?>
					</div>

					<?php if ( ! empty( $linked_booking ) ) :
						$booking_url = add_query_arg(
							array(
								'page'   => 'dev-nahian-fleet-manager',
								'tab'    => 'booking',
								'action' => 'view',
								'id'     => $linked_booking['id'],
							),
							admin_url( 'admin.php' )
						);
						?>
						<div class="dnf-manifest-surface">
							<div>
								<h4 style="margin:0 0 4px; font-size:15px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
									<?php echo esc_html( (string) $linked_booking['car_name'] ); ?>
								</h4>
								<div style="font-size:12px; color:var(--dnf-text-secondary, #64748b);">
									<span><?php esc_html_e( 'Client:', 'dev-nahian-car' ); ?> <strong><?php echo esc_html( (string) $linked_booking['customer_name'] ); ?></strong></span>
									<span style="margin: 0 6px;">&bull;</span>
									<span><?php esc_html_e( 'Manifest Code:', 'dev-nahian-car' ); ?> <code class="dnf-code">#<?php echo esc_html( (string) $linked_booking['booking_code'] ); ?></code></span>
								</div>
							</div>
							<a href="<?php echo esc_url( $booking_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm">
								<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Inspect Reservation', 'dev-nahian-car' ); ?>
							</a>
						</div>
					<?php elseif ( ! empty( $linked_maint ) ) :
						$maint_url = add_query_arg(
							array(
								'page'   => 'dev-nahian-fleet-manager',
								'tab'    => 'maintenance',
								'action' => 'view',
								'id'     => $linked_maint['id'],
							),
							admin_url( 'admin.php' )
						);
						?>
						<div class="dnf-manifest-surface">
							<div>
								<h4 style="margin:0 0 4px; font-size:15px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
									<?php echo esc_html( (string) $linked_maint['service_type'] ); ?>
								</h4>
								<div style="font-size:12px; color:var(--dnf-text-secondary, #64748b);">
									<span><?php esc_html_e( 'Target Vehicle:', 'dev-nahian-car' ); ?> <strong><?php echo esc_html( (string) $linked_maint['car_name'] ); ?></strong></span>
									<span style="margin: 0 6px;">&bull;</span>
									<span><?php esc_html_e( 'Ticket #:', 'dev-nahian-car' ); ?> <code class="dnf-code">#<?php echo esc_html( (string) $linked_maint['id'] ); ?></code></span>
								</div>
							</div>
							<a href="<?php echo esc_url( $maint_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm">
								<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Inspect Service Order', 'dev-nahian-car' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Operational Notes & Audit Documentation -->
			<div>
				<div class="dnf-fin-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					<?php esc_html_e( 'Operational Notes, Receipts & Accounting Ledger Audit', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-fin-notes-surface">
					<?php
					if ( ! empty( $txn['notes'] ) ) {
						echo esc_html( (string) $txn['notes'] );
					} else {
						echo '<em>' . esc_html__( 'No additional operational notes, vendor receipts, or accounting memos attached to this transaction.', 'dev-nahian-car' ) . '</em>';
					}
					?>
				</div>
			</div>
		</div>
	</div>

</div>
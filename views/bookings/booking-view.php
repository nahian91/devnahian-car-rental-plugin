<?php
/**
 * Bookings Module: Manifest Inspector View (`booking-view.php`)
 * Detailed dispatch dossier with printable receipt layout and action buttons.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.2.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dev-nahian-car' ), 403 );
}

global $wpdb;

// Ensure variables have robust fallbacks.
$id             = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$table_cars     = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$table_finances = isset( $table_finances ) ? $table_finances : $wpdb->prefix . 'dev_nahian_finances';

$base     = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'booking',
	),
	admin_url( 'admin.php' )
);
$currency = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

if ( $id <= 0 ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid booking ID supplied.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $id ), ARRAY_A );

if ( ! $booking ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking record not found in database.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

$booking_id   = absint( $booking['id'] );
$booking_code = sanitize_text_field( (string) ( $booking['booking_code'] ?? $booking_id ) );
$status_slug  = sanitize_key( (string) ( $booking['booking_status'] ?? 'pending' ) );

$edit_url = add_query_arg(
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

// Cross-reference linked vehicle asset and financial transaction.
$car_id       = absint( $booking['car_id'] ?? 0 );
$car_edit_url = $car_id > 0 ? add_query_arg(
	array(
		'page'   => 'dev-nahian-fleet-manager',
		'tab'    => 'cars',
		'action' => 'edit',
		'id'     => $car_id,
	),
	admin_url( 'admin.php' )
) : '';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$linked_txn = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT id, amount, txn_date FROM {$table_finances} WHERE reference_code = %s OR reference_code = %s LIMIT 1",
		$booking_code,
		'#' . $booking_code
	),
	ARRAY_A
);

$pickup_ts  = ! empty( $booking['pickup_date'] ) ? strtotime( (string) $booking['pickup_date'] ) : false;
$return_ts  = ! empty( $booking['return_date'] ) ? strtotime( (string) $booking['return_date'] ) : false;
$created_ts = ! empty( $booking['created_at'] ) ? strtotime( (string) $booking['created_at'] ) : false;
?>

<style>
	/* Scoped Manifest Inspector Styles */
	.dnf-inspector-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Navigation Toolbar */
	.dnf-inspector-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-inspector-nav-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Main Dossier Card */
	.dnf-manifest-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		overflow: hidden;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
	}

	/* Executive Header */
	.dnf-manifest-header {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
		color: #ffffff;
		padding: 26px 32px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-manifest-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 11.5px;
		font-weight: 700;
		color: var(--dnf-accent-gold, #b88e1e);
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.dnf-manifest-heading {
		margin: 6px 0 0;
		font-size: 22px;
		font-weight: 800;
		color: #ffffff;
		letter-spacing: -0.02em;
	}

	/* Body Layout Grid */
	.dnf-manifest-body {
		padding: 32px;
		display: grid;
		grid-template-columns: 2fr 1fr;
		gap: 32px;
	}
	@media (max-width: 960px) {
		.dnf-manifest-body {
			grid-template-columns: 1fr;
		}
	}

	/* Section Headings */
	.dnf-manifest-section-title {
		font-size: 11px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: var(--dnf-accent-teal, #0d9488);
		margin: 0 0 14px 0;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-manifest-section-title::after {
		content: '';
		flex-grow: 1;
		height: 1px;
		background: var(--dnf-border, #e2e8f0);
	}

	/* Spec Row List */
	.dnf-spec-list {
		display: flex;
		flex-direction: column;
	}
	.dnf-spec-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 10px 0;
		border-bottom: 1px dashed var(--dnf-border, #e2e8f0);
		font-size: 13.5px;
	}
	.dnf-spec-row:last-child {
		border-bottom: none;
	}
	.dnf-spec-label {
		color: var(--dnf-text-secondary, #64748b);
		font-weight: 500;
	}
	.dnf-spec-val {
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		text-align: right;
	}

	/* Sidebar Summary Card */
	.dnf-manifest-sidebar-box {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 20px;
		display: flex;
		flex-direction: column;
		gap: 20px;
	}
	.dnf-notes-surface {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-sm, 6px);
		padding: 14px;
		font-size: 13px;
		line-height: 1.6;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-total-highlight {
		display: flex;
		justify-content: space-between;
		align-items: baseline;
		padding-top: 14px;
		border-top: 2px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-total-number {
		font-size: 22px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		font-feature-settings: "tnum";
	}

	/* Linked Manifest Surface */
	.dnf-linked-surface {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-sm, 6px);
		padding: 12px 14px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 10px;
	}

	/* Print Stylesheet Overrides */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-inspector-nav,
		.dev-nahian-sidebar {
			display: none !important;
		}
		.dev-nahian-content,
		.dnf-booking-wrap,
		.dnf-inspector-wrap {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
			border: none !important;
		}
		.dnf-manifest-card {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
		}
		.dnf-manifest-header {
			background: #0f172a !important;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
	}
</style>

<div class="dnf-inspector-wrap">

	<!-- Inspector Navigation & Action Bar -->
	<div class="dnf-inspector-nav">
		<div class="dnf-inspector-nav-left">
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back to All Bookings', 'dev-nahian-car' ); ?></span>
			</a>
			<span class="dnf-count-chip"><?php esc_html_e( 'Manifest Inspector', 'dev-nahian-car' ); ?></span>
			<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
				<?php
				printf(
					/* translators: %s: booking code */
					esc_html__( 'Manifest #%s', 'dev-nahian-car' ),
					esc_html( $booking_code )
				);
				?>
			</h2>
		</div>

		<div style="display: flex; gap: 8px;">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn dnf-btn-primary">
				<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Dossier', 'dev-nahian-car' ); ?></span>
			</a>
			<button type="button" class="dnf-btn dnf-btn-secondary" onclick="window.print();">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span><?php esc_html_e( 'Print Manifest', 'dev-nahian-car' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn dnf-btn-danger" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this dispatch manifest record?', 'dev-nahian-car' ); ?>');">
				<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Delete', 'dev-nahian-car' ); ?></span>
			</a>
		</div>
	</div>

	<!-- Main Manifest Sheet -->
	<div class="dnf-manifest-card">
		<!-- Header Banner -->
		<div class="dnf-manifest-header">
			<div>
				<div class="dnf-manifest-code">
					<?php
					printf(
						/* translators: %s: booking code */
						esc_html__( 'OFFICIAL DISPATCH MANIFEST #%s', 'dev-nahian-car' ),
						esc_html( $booking_code )
					);
					?>
				</div>
				<h2 class="dnf-manifest-heading">
					<?php echo esc_html( ! empty( $booking['car_name'] ) ? (string) $booking['car_name'] : __( 'Fleet Asset Assigned', 'dev-nahian-car' ) ); ?>
				</h2>
			</div>
			<div>
				<span class="dnf-status-pill <?php echo esc_attr( $status_slug ); ?>" style="font-size: 12px; padding: 5px 14px;">
					&bull; <?php echo esc_html( ucfirst( $status_slug ) ); ?>
				</span>
			</div>
		</div>

		<!-- Body Grid -->
		<div class="dnf-manifest-body">
			<!-- Left Column: Passenger & Logistics Specifications -->
			<div>
				<!-- Section 1: Customer Profile -->
				<div class="dnf-manifest-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					<?php esc_html_e( '1. Client Profile & Driver Credentials', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-spec-list">
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Full Legal Name', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( (string) $booking['customer_name'] ); ?></span>
					</div>
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Email Address', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( (string) $booking['customer_email'] ); ?></span>
					</div>
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Contact Phone', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( (string) $booking['customer_phone'] ); ?></span>
					</div>
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Driver License ID', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><code class="dnf-code"><?php echo esc_html( ! empty( $booking['license_number'] ) ? (string) $booking['license_number'] : __( 'N/A', 'dev-nahian-car' ) ); ?></code></span>
					</div>
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Emergency Contact', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( ! empty( $booking['emergency_contact'] ) ? (string) $booking['emergency_contact'] : '—' ); ?></span>
					</div>
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Registered Passengers', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( (string) number_format_i18n( (int) ( $booking['passenger_count'] ?? 1 ) ) ); ?></span>
					</div>
				</div>

				<!-- Section 2: Window & Logistics -->
				<div class="dnf-manifest-section-title" style="margin-top: 28px;">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
					<?php esc_html_e( '2. Schedule Window & Routing Hubs', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-spec-list">
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Pickup Schedule', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val">
							<?php
							echo esc_html(
								( $pickup_ts ? wp_date( (string) get_option( 'date_format', 'M j, Y' ), $pickup_ts ) : (string) ( $booking['pickup_date'] ?? '—' ) )
								. ( ! empty( $booking['pickup_time'] ) ? ' @ ' . $booking['pickup_time'] : '' )
							);
							?>
						</span>
					</div>
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Return Schedule', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val">
							<?php
							echo esc_html(
								( $return_ts ? wp_date( (string) get_option( 'date_format', 'M j, Y' ), $return_ts ) : (string) ( $booking['return_date'] ?? '—' ) )
								. ( ! empty( $booking['return_time'] ) ? ' @ ' . $booking['return_time'] : '' )
							);
							?>
						</span>
					</div>
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Pickup Terminal / Location', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( (string) $booking['pickup_location'] ); ?></span>
					</div>
					<div class="dnf-spec-row">
						<span class="dnf-spec-label"><?php esc_html_e( 'Return Terminal / Location', 'dev-nahian-car' ); ?></span>
						<span class="dnf-spec-val"><?php echo esc_html( (string) $booking['return_location'] ); ?></span>
					</div>
					<?php if ( ! empty( $booking['flight_number'] ) ) : ?>
						<div class="dnf-spec-row">
							<span class="dnf-spec-label"><?php esc_html_e( 'Flight Identification', 'dev-nahian-car' ); ?></span>
							<span class="dnf-spec-val"><code class="dnf-code"><?php echo esc_html( (string) $booking['flight_number'] ); ?></code></span>
						</div>
					<?php endif; ?>
				</div>

				<!-- Section 3: Notes -->
				<div class="dnf-manifest-section-title" style="margin-top: 28px;">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					<?php esc_html_e( '3. Driver & Handling Instructions', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-notes-surface">
					<?php
					if ( ! empty( $booking['customer_notes'] ) ) {
						echo nl2br( esc_html( (string) $booking['customer_notes'] ) );
					} else {
						echo '<em>' . esc_html__( 'No operational or handling instructions provided for this dispatch manifest.', 'dev-nahian-car' ) . '</em>';
					}
					?>
				</div>
			</div>

			<!-- Right Column: Vehicle Telematics & Billing Summary -->
			<div>
				<div class="dnf-manifest-sidebar-box">
					<!-- Vehicle Card -->
					<div>
						<div class="dnf-manifest-section-title">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 17h14v-5l-2-6H7L5 12v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
							<?php esc_html_e( 'Fleet Asset', 'dev-nahian-car' ); ?>
						</div>
						<div class="dnf-spec-list">
							<div class="dnf-spec-row">
								<span class="dnf-spec-label"><?php esc_html_e( 'Model', 'dev-nahian-car' ); ?></span>
								<span class="dnf-spec-val"><?php echo esc_html( (string) $booking['car_name'] ); ?></span>
							</div>
							<div class="dnf-spec-row">
								<span class="dnf-spec-label"><?php esc_html_e( 'Asset Database ID', 'dev-nahian-car' ); ?></span>
								<span class="dnf-spec-val">
									<?php if ( $car_edit_url ) : ?>
										<a href="<?php echo esc_url( $car_edit_url ); ?>" style="text-decoration:none; font-weight:700;">
											#<?php echo esc_html( (string) $car_id ); ?>
										</a>
									<?php else : ?>
										#<?php echo esc_html( (string) $car_id ); ?>
									<?php endif; ?>
								</span>
							</div>
						</div>
					</div>

					<!-- Settlement Card -->
					<div>
						<div class="dnf-manifest-section-title">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
							<?php esc_html_e( 'Financial Ledger', 'dev-nahian-car' ); ?>
						</div>
						<div class="dnf-spec-list">
							<div class="dnf-spec-row">
								<span class="dnf-spec-label"><?php esc_html_e( 'Gateway Method', 'dev-nahian-car' ); ?></span>
								<span class="dnf-spec-val"><?php echo esc_html( strtoupper( (string) ( $booking['payment_method'] ?? 'card' ) ) ); ?></span>
							</div>
							<div class="dnf-spec-row">
								<span class="dnf-spec-label"><?php esc_html_e( 'Date Created', 'dev-nahian-car' ); ?></span>
								<span class="dnf-spec-val">
									<?php echo esc_html( $created_ts ? wp_date( (string) get_option( 'date_format', 'M j, Y' ), $created_ts ) : (string) ( $booking['created_at'] ?? '—' ) ); ?>
								</span>
							</div>
							<?php if ( ! empty( $linked_txn ) ) :
								$fin_url = add_query_arg(
									array(
										'page'   => 'dev-nahian-fleet-manager',
										'tab'    => 'finances',
										'action' => 'view',
										'id'     => absint( $linked_txn['id'] ),
									),
									admin_url( 'admin.php' )
								);
								?>
								<div class="dnf-spec-row" style="padding-top:10px;">
									<span class="dnf-spec-label"><?php esc_html_e( 'Ledger Audit Record', 'dev-nahian-car' ); ?></span>
									<span class="dnf-spec-val">
										<a href="<?php echo esc_url( $fin_url ); ?>" class="dnf-btn dnf-btn-secondary dnf-btn-sm" style="font-size:11px; padding:3px 8px;">
											#<?php echo esc_html( (string) $linked_txn['id'] ); ?> &rarr;
										</a>
									</span>
								</div>
							<?php endif; ?>
						</div>

						<div class="dnf-total-highlight">
							<span style="font-size: 13px; font-weight: 800; color: var(--dnf-text-secondary, #475569); text-transform: uppercase;">
								<?php esc_html_e( 'Invoiced Total', 'dev-nahian-car' ); ?>
							</span>
							<span class="dnf-total-number">
								<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $booking['total_amount'] ?? 0.00 ), 2 ) ); ?>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
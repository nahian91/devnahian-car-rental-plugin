<?php
/**
 * Customers Module: Driver Dossier Inspector (`customer-view.php`)
 * Detailed inspection dossier with KYC credentials, lifetime analytics, and booking history.
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
$id              = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_customers = isset( $table_customers ) ? $table_customers : $wpdb->prefix . 'dev_nahian_customers';
$table_bookings  = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$base            = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'customers',
	),
	admin_url( 'admin.php' )
);
$currency        = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

if ( $id <= 0 ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid customer ID supplied.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cust = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $id ), ARRAY_A );

if ( ! $cust ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer profile record not found in database.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

$cust_email       = strtolower( trim( (string) ( $cust['email'] ?? '' ) ) );
$cust_status_slug = sanitize_key( (string) ( $cust['kyc_status'] ?? ( $cust['status'] ?? 'pending' ) ) );
$cust_tier_slug   = sanitize_key( (string) ( $cust['tier'] ?? 'standard' ) );

// 1. Query Lifetime Fleet Metrics for Customer.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_bookings} WHERE LOWER(customer_email) = %s", $cust_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_spent = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total_amount), 0) FROM {$table_bookings} WHERE LOWER(customer_email) = %s", $cust_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// 2. Query Recent Dispatch Manifests for Customer.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$recent_bookings = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE LOWER(customer_email) = %s ORDER BY id DESC LIMIT 5", $cust_email ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$edit_url = add_query_arg(
	array(
		'action' => 'edit',
		'id'     => $id,
		'subtab' => 'add',
	),
	$base
);
?>

<style>
	/* Scoped Customer Inspector Styles */
	.dnf-cust-inspector-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Navigation Toolbar */
	.dnf-cust-inspector-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-cust-inspector-nav-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Main Dossier Card */
	.dnf-cust-dossier-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		overflow: hidden;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
	}

	/* Executive Header */
	.dnf-cust-dossier-header {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
		color: #ffffff;
		padding: 24px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-cust-dossier-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 11.5px;
		font-weight: 700;
		color: var(--dnf-accent-gold, #b88e1e);
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.dnf-cust-dossier-heading {
		margin: 4px 0 0;
		font-size: 22px;
		font-weight: 800;
		color: #ffffff;
		letter-spacing: -0.02em;
	}

	/* Body Layout Grid */
	.dnf-cust-dossier-body {
		padding: 30px;
		display: grid;
		grid-template-columns: 2fr 1fr;
		gap: 30px;
	}
	@media (max-width: 960px) {
		.dnf-cust-dossier-body {
			grid-template-columns: 1fr;
		}
	}

	/* Section Headings */
	.dnf-cust-section-title {
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
	.dnf-cust-section-title::after {
		content: '';
		flex-grow: 1;
		height: 1px;
		background: var(--dnf-border, #e2e8f0);
	}

	/* Spec Row List */
	.dnf-cust-spec-list {
		display: flex;
		flex-direction: column;
	}
	.dnf-cust-spec-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 10px 0;
		border-bottom: 1px dashed var(--dnf-border, #e2e8f0);
		font-size: 13.5px;
	}
	.dnf-cust-spec-row:last-child {
		border-bottom: none;
	}
	.dnf-cust-spec-label {
		color: var(--dnf-text-secondary, #64748b);
		font-weight: 500;
	}
	.dnf-cust-spec-val {
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		text-align: right;
	}

	/* Sidebar Summary Card */
	.dnf-cust-sidebar-box {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 22px;
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Notes Card */
	.dnf-cust-notes-surface {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-sm, 6px);
		padding: 14px;
		font-size: 13px;
		line-height: 1.5;
		color: var(--dnf-text-secondary, #475569);
	}

	/* History Mini Table */
	.dnf-history-table-wrap {
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		overflow: hidden;
		margin-top: 10px;
	}

	/* Print Overrides */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-cust-inspector-nav,
		.dev-nahian-sidebar {
			display: none !important;
		}
		.dev-nahian-content,
		.dnf-customers-scope,
		.dnf-cust-inspector-wrap {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
			border: none !important;
		}
		.dnf-cust-dossier-card {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
		}
	}
</style>

<div class="dnf-cust-inspector-wrap">

	<!-- Inspector Navigation Toolbar -->
	<div class="dnf-cust-inspector-nav">
		<div class="dnf-cust-inspector-nav-left">
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back to Customer Directory', 'dev-nahian-car' ); ?></span>
			</a>
			<span class="dnf-count-chip"><?php esc_html_e( 'Driver Dossier', 'dev-nahian-car' ); ?></span>
			<h2 style="margin:0; font-size:18px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
				<?php echo esc_html( (string) $cust['name'] ); ?>
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
		</div>
	</div>

	<!-- Main Dossier Card -->
	<div class="dnf-cust-dossier-card">
		<!-- Header Banner -->
		<div class="dnf-cust-dossier-header">
			<div>
				<div class="dnf-cust-dossier-code">
					<?php
					printf(
						/* translators: %s: customer code */
						esc_html__( 'KYC ACCOUNT ID: #%s', 'dev-nahian-car' ),
						esc_html( (string) ( $cust['customer_code'] ?? 'VEL-000000' ) )
					);
					?>
				</div>
				<h2 class="dnf-cust-dossier-heading">
					<?php echo esc_html( (string) $cust['name'] ); ?>
				</h2>
			</div>
			<div style="display:flex; align-items:center; gap:8px;">
				<span class="dnf-tier-badge <?php echo esc_attr( $cust_tier_slug ); ?>">
					<?php echo esc_html( ucfirst( $cust_tier_slug ) ); ?>
				</span>
				<span class="dnf-status-pill <?php echo esc_attr( $cust_status_slug ); ?>">
					&bull; <?php echo esc_html( ucfirst( $cust_status_slug ) ); ?>
				</span>
			</div>
		</div>

		<!-- Body Grid -->
		<div class="dnf-cust-dossier-body">
			<!-- Left Column: Identity, License & Notes -->
			<div>
				<!-- Section 1: Identity & Contact -->
				<div class="dnf-cust-section-title">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					<?php esc_html_e( '1. Client Profile & Verified Contact', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-cust-spec-list">
					<div class="dnf-cust-spec-row">
						<span class="dnf-cust-spec-label"><?php esc_html_e( 'Full Legal Name', 'dev-nahian-car' ); ?></span>
						<span class="dnf-cust-spec-val"><?php echo esc_html( (string) $cust['name'] ); ?></span>
					</div>
					<div class="dnf-cust-spec-row">
						<span class="dnf-cust-spec-label"><?php esc_html_e( 'Primary Email', 'dev-nahian-car' ); ?></span>
						<span class="dnf-cust-spec-val"><?php echo esc_html( (string) $cust['email'] ); ?></span>
					</div>
					<div class="dnf-cust-spec-row">
						<span class="dnf-cust-spec-label"><?php esc_html_e( 'Direct Phone', 'dev-nahian-car' ); ?></span>
						<span class="dnf-cust-spec-val"><?php echo esc_html( ! empty( $cust['phone'] ) ? (string) $cust['phone'] : '—' ); ?></span>
					</div>
					<div class="dnf-cust-spec-row">
						<span class="dnf-cust-spec-label"><?php esc_html_e( 'Date of Birth', 'dev-nahian-car' ); ?></span>
						<span class="dnf-cust-spec-val"><?php echo esc_html( ! empty( $cust['dob'] ) ? (string) $cust['dob'] : '—' ); ?></span>
					</div>
					<div class="dnf-cust-spec-row">
						<span class="dnf-cust-spec-label"><?php esc_html_e( 'Jurisdiction / Country', 'dev-nahian-car' ); ?></span>
						<span class="dnf-cust-spec-val"><?php echo esc_html( ! empty( $cust['country'] ) ? (string) $cust['country'] : 'Global Jurisdiction' ); ?></span>
					</div>
				</div>

				<!-- Section 2: Driver Credentials -->
				<div class="dnf-cust-section-title" style="margin-top:28px;">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><circle cx="9" cy="10" r="2"></circle><line x1="15" y1="8" x2="17" y2="8"></line><line x1="15" y1="12" x2="17" y2="12"></line><line x1="7" y1="16" x2="17" y2="16"></line></svg>
					<?php esc_html_e( '2. Driver License Verification', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-cust-spec-list">
					<div class="dnf-cust-spec-row">
						<span class="dnf-cust-spec-label"><?php esc_html_e( 'Driver License Number', 'dev-nahian-car' ); ?></span>
						<span class="dnf-cust-spec-val"><code class="dnf-code"><?php echo esc_html( ! empty( $cust['license_number'] ) ? (string) $cust['license_number'] : '—' ); ?></code></span>
					</div>
					<div class="dnf-cust-spec-row">
						<span class="dnf-cust-spec-label"><?php esc_html_e( 'License Expiration Date', 'dev-nahian-car' ); ?></span>
						<span class="dnf-cust-spec-val"><?php echo esc_html( ! empty( $cust['license_expiry'] ) ? (string) $cust['license_expiry'] : '—' ); ?></span>
					</div>
				</div>

				<!-- Section 3: Notes -->
				<div class="dnf-cust-section-title" style="margin-top:28px;">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					<?php esc_html_e( '3. Profile Notes & Handling Logistics', 'dev-nahian-car' ); ?>
				</div>
				<div class="dnf-cust-notes-surface">
					<?php
					if ( ! empty( $cust['notes'] ) ) {
						echo nl2br( esc_html( (string) $cust['notes'] ) );
					} else {
						echo '<em>' . esc_html__( 'No operational or handling instructions recorded for this client.', 'dev-nahian-car' ) . '</em>';
					}
					?>
				</div>

				<!-- Section 4: Recent Dispatches Ledger -->
				<div class="dnf-cust-section-title" style="margin-top:28px;">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
					<?php esc_html_e( '4. Recent Reservation Manifests', 'dev-nahian-car' ); ?>
				</div>
				<?php if ( ! empty( $recent_bookings ) ) : ?>
					<div class="dnf-history-table-wrap">
						<table class="wp-list-table widefat fixed striped" style="width:100%; border:none; margin:0;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Booking ID', 'dev-nahian-car' ); ?></th>
									<th><?php esc_html_e( 'Vehicle', 'dev-nahian-car' ); ?></th>
									<th style="width:190px;"><?php esc_html_e( 'Schedule Window', 'dev-nahian-car' ); ?></th>
									<th style="width:120px;"><?php esc_html_e( 'Total', 'dev-nahian-car' ); ?></th>
									<th style="width:120px;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
									<th style="text-align:right; width:80px;"><?php esc_html_e( 'Inspect', 'dev-nahian-car' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $recent_bookings as $rb ) :
									$bk_status = sanitize_key( (string) ( $rb['booking_status'] ?? 'pending' ) );
									$bk_url    = add_query_arg(
										array(
											'page'   => 'dev-nahian-fleet-manager',
											'tab'    => 'booking',
											'action' => 'view',
											'id'     => absint( $rb['id'] ),
										),
										admin_url( 'admin.php' )
									);
									?>
									<tr>
										<td><code class="dnf-code">#<?php echo esc_html( (string) ( $rb['booking_code'] ?? $rb['id'] ) ); ?></code></td>
										<td><strong><?php echo esc_html( ! empty( $rb['car_name'] ) ? (string) $rb['car_name'] : __( 'Fleet Asset', 'dev-nahian-car' ) ); ?></strong></td>
										<td style="font-size:12px;"><?php echo esc_html( (string) ( $rb['pickup_date'] ?? '' ) ); ?> &rarr; <?php echo esc_html( (string) ( $rb['return_date'] ?? '' ) ); ?></td>
										<td><strong style="font-feature-settings:'tnum';"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $rb['total_amount'] ?? 0 ), 2 ) ); ?></strong></td>
										<td><span class="dnf-status-pill <?php echo esc_attr( $bk_status ); ?>">&bull; <?php echo esc_html( ucfirst( $bk_status ) ); ?></span></td>
										<td style="text-align:right;">
											<a href="<?php echo esc_url( $bk_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'Inspect Manifest', 'dev-nahian-car' ); ?>">
												<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="dnf-cust-notes-surface" style="text-align:center; color:var(--dnf-text-tertiary, #94a3b8); padding:20px;">
						<?php esc_html_e( 'No reservation history recorded for this client yet.', 'dev-nahian-car' ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Right Column: Account & Lifetime Value -->
			<div>
				<div class="dnf-cust-sidebar-box">
					<div>
						<div class="dnf-cust-section-title">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
							<?php esc_html_e( 'Lifetime Analytics', 'dev-nahian-car' ); ?>
						</div>
						<div class="dnf-cust-spec-list">
							<div class="dnf-cust-spec-row">
								<span class="dnf-cust-spec-label"><?php esc_html_e( 'Membership Tier', 'dev-nahian-car' ); ?></span>
								<span class="dnf-cust-spec-val">
									<span class="dnf-tier-badge <?php echo esc_attr( $cust_tier_slug ); ?>">
										<?php echo esc_html( ucfirst( $cust_tier_slug ) ); ?>
									</span>
								</span>
							</div>
							<div class="dnf-cust-spec-row">
								<span class="dnf-cust-spec-label"><?php esc_html_e( 'Total Dispatches', 'dev-nahian-car' ); ?></span>
								<span class="dnf-cust-spec-val" style="font-feature-settings:'tnum';">
									<?php
									printf(
										/* translators: %s: number of bookings */
										esc_html__( '%s Bookings', 'dev-nahian-car' ),
										esc_html( (string) number_format_i18n( $booking_count ) )
									);
									?>
								</span>
							</div>
							<div class="dnf-cust-spec-row">
								<span class="dnf-cust-spec-label"><?php esc_html_e( 'Lifetime Revenue', 'dev-nahian-car' ); ?></span>
								<span class="dnf-cust-spec-val" style="color:var(--dnf-accent-teal, #0d9488); font-size:16px; font-feature-settings:'tnum';">
									<?php echo esc_html( $currency . ' ' . number_format_i18n( $total_spent, 2 ) ); ?>
								</span>
							</div>
						</div>
					</div>

					<div>
						<div class="dnf-cust-section-title">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
							<?php esc_html_e( 'Security & Metadata', 'dev-nahian-car' ); ?>
						</div>
						<div class="dnf-cust-spec-list">
							<div class="dnf-cust-spec-row">
								<span class="dnf-cust-spec-label"><?php esc_html_e( 'Client Account Tag', 'dev-nahian-car' ); ?></span>
								<span class="dnf-cust-spec-val"><code class="dnf-code"><?php echo esc_html( (string) ( $cust['customer_code'] ?? '—' ) ); ?></code></span>
							</div>
							<div class="dnf-cust-spec-row">
								<span class="dnf-cust-spec-label"><?php esc_html_e( 'Registered On', 'dev-nahian-car' ); ?></span>
								<span class="dnf-cust-spec-val" style="font-feature-settings:'tnum';">
									<?php
									$created_time = ! empty( $cust['created_at'] ) ? strtotime( (string) $cust['created_at'] ) : false;
									echo esc_html( $created_time ? wp_date( (string) get_option( 'date_format', 'F j, Y' ), $created_time ) : '—' );
									?>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
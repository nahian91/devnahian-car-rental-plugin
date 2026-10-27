<?php
/**
 * Customers Module: Create & Edit Workstation (`customer-add-edit.php`)
 * Handles customer profile mutations, KYC validation, membership tier assignment, and modern form controls.
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

// Ensure variables have robust fallbacks.
$id               = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_customers  = isset( $table_customers ) ? $table_customers : $wpdb->prefix . 'dev_nahian_customers';
$base             = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'customers',
	),
	admin_url( 'admin.php' )
);

$validation_error = '';

// -----------------------------------------------------------------------------
// 1. Process Save Action (Create POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_add_customer_submit'] ) ) {
	check_admin_referer( 'dev_nahian_add_customer_verify', 'dev_nahian_cust_nonce' );

	$raw_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( ! is_email( $raw_email ) ) {
		$validation_error = __( 'Please provide a valid email address for client registration.', 'dev-nahian-car' );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_customers} WHERE email = %s", $raw_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $email_exists ) {
			$validation_error = __( 'A customer profile is already registered under this email address. Please use a distinct address.', 'dev-nahian-car' );
		} else {
			$customer_code  = 'VEL-' . strtoupper( wp_generate_password( 6, false, false ) );
			$raw_dob        = ! empty( $_POST['dob'] ) ? sanitize_text_field( wp_unslash( $_POST['dob'] ) ) : '';
			$raw_lic_expiry = ! empty( $_POST['license_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['license_expiry'] ) ) : '';
			$kyc_status     = isset( $_POST['kyc_status'] ) ? sanitize_key( wp_unslash( $_POST['kyc_status'] ) ) : 'pending';

			$dob_val        = ! empty( $raw_dob ) ? $raw_dob : null;
			$license_expiry = ! empty( $raw_lic_expiry ) ? $raw_lic_expiry : null;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table_customers,
				array(
					'customer_code'  => $customer_code,
					'name'           => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
					'email'          => $raw_email,
					'phone'          => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
					'dob'            => $dob_val,
					'country'        => isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '',
					'license_number' => isset( $_POST['license_number'] ) ? sanitize_text_field( wp_unslash( $_POST['license_number'] ) ) : '',
					'license_expiry' => $license_expiry,
					'tier'           => isset( $_POST['tier'] ) ? sanitize_key( wp_unslash( $_POST['tier'] ) ) : 'standard',
					'kyc_status'     => $kyc_status,
					'status'         => $kyc_status, // Backward compatibility with legacy schema columns.
					'notes'          => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
					'created_at'     => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false !== $inserted ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'  => 'dev-nahian-fleet-manager',
							'tab'   => 'customers',
							'saved' => '1',
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}
		}
	}
}

// -----------------------------------------------------------------------------
// 2. Process Edit Action (Update POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_edit_customer_submit'] ) && $id > 0 ) {
	check_admin_referer( 'dev_nahian_edit_customer_' . $id, 'dev_nahian_cust_nonce' );

	$raw_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( ! is_email( $raw_email ) ) {
		$validation_error = __( 'Please provide a valid email address for client profile updates.', 'dev-nahian-car' );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_customers} WHERE email = %s AND id != %d", $raw_email, $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $email_exists ) {
			$validation_error = __( 'Another customer profile is already registered under this email address. Please assign a unique email.', 'dev-nahian-car' );
		} else {
			$raw_dob        = ! empty( $_POST['dob'] ) ? sanitize_text_field( wp_unslash( $_POST['dob'] ) ) : '';
			$raw_lic_expiry = ! empty( $_POST['license_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['license_expiry'] ) ) : '';
			$kyc_status     = isset( $_POST['kyc_status'] ) ? sanitize_key( wp_unslash( $_POST['kyc_status'] ) ) : 'pending';

			$dob_val        = ! empty( $raw_dob ) ? $raw_dob : null;
			$license_expiry = ! empty( $raw_lic_expiry ) ? $raw_lic_expiry : null;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$updated = $wpdb->update(
				$table_customers,
				array(
					'name'           => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
					'email'          => $raw_email,
					'phone'          => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
					'dob'            => $dob_val,
					'country'        => isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '',
					'license_number' => isset( $_POST['license_number'] ) ? sanitize_text_field( wp_unslash( $_POST['license_number'] ) ) : '',
					'license_expiry' => $license_expiry,
					'tier'           => isset( $_POST['tier'] ) ? sanitize_key( wp_unslash( $_POST['tier'] ) ) : 'standard',
					'kyc_status'     => $kyc_status,
					'status'         => $kyc_status,
					'notes'          => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
				),
				array( 'id' => $id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'    => 'dev-nahian-fleet-manager',
							'tab'     => 'customers',
							'updated' => '1',
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}
		}
	}
}

// -----------------------------------------------------------------------------
// 3. Load Customer Data for Form Rendering
// -----------------------------------------------------------------------------
$is_edit = ( 'edit' === $action && $id > 0 );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cust = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $id ), ARRAY_A ) : array();

$cust_name       = (string) ( $cust['name'] ?? '' );
$cust_email      = (string) ( $cust['email'] ?? '' );
$cust_phone      = (string) ( $cust['phone'] ?? '' );
$cust_dob        = (string) ( $cust['dob'] ?? '' );
$cust_country    = (string) ( $cust['country'] ?? '' );
$cust_status     = sanitize_key( (string) ( $cust['kyc_status'] ?? ( $cust['status'] ?? 'pending' ) ) );
$cust_license    = (string) ( $cust['license_number'] ?? '' );
$cust_lic_expiry = (string) ( $cust['license_expiry'] ?? '' );
$cust_tier       = sanitize_key( (string) ( $cust['tier'] ?? 'standard' ) );
$cust_notes      = (string) ( $cust['notes'] ?? '' );
?>

<style>
	/* Scoped Customer Form Surface */
	.dnf-cust-form-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 32px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
		max-width: 900px;
	}
	.dnf-cust-form-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px;
	}
	@media (max-width: 960px) {
		.dnf-cust-form-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 640px) {
		.dnf-cust-form-grid {
			grid-template-columns: 1fr;
		}
	}
	.col-span-full {
		grid-column: 1 / -1;
	}

	.dnf-cust-section-title {
		grid-column: 1 / -1;
		font-size: 11px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: var(--dnf-accent-teal, #0d9488);
		padding-top: 18px;
		margin-top: 4px;
		border-top: 1px solid var(--dnf-border, #e2e8f0);
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-cust-section-title:first-of-type {
		border-top: none;
		padding-top: 0;
		margin-top: 0;
	}
</style>

<!-- Toolbar / Subnav Header -->
<div class="dnf-cust-toolbar" style="margin-bottom: 20px;">
	<div class="dnf-cust-toolbar-left">
		<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
			<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Cancel & Back to Directory', 'dev-nahian-car' ); ?></span>
		</a>
		<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
			<?php
			echo $is_edit
				? sprintf(
					/* translators: %s: customer name */
					esc_html__( 'Edit Client Profile: %s', 'dev-nahian-car' ),
					esc_html( $cust_name )
				)
				: esc_html__( 'Register Client KYC Dossier', 'dev-nahian-car' );
			?>
		</h2>
	</div>
</div>

<?php if ( ! empty( $validation_error ) ) : ?>
	<div class="notice notice-error is-dismissible" style="margin-bottom: 20px !important;">
		<p><strong><?php esc_html_e( 'Error:', 'dev-nahian-car' ); ?></strong> <?php echo esc_html( $validation_error ); ?></p>
	</div>
<?php endif; ?>

<!-- Form Surface -->
<div class="dnf-cust-form-card">
	<form method="post" action="" id="dnfCustomerForm">
		<?php
		if ( $is_edit ) {
			wp_nonce_field( 'dev_nahian_edit_customer_' . $id, 'dev_nahian_cust_nonce' );
			echo '<input type="hidden" name="dev_nahian_edit_customer_submit" value="1">';
		} else {
			wp_nonce_field( 'dev_nahian_add_customer_verify', 'dev_nahian_cust_nonce' );
			echo '<input type="hidden" name="dev_nahian_add_customer_submit" value="1">';
		}
		?>

		<div class="dnf-cust-form-grid">
			<!-- Section 1: Personal Details -->
			<div class="dnf-cust-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
				<?php esc_html_e( '1. Client Identity & Personal Details', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_name">
					<?php esc_html_e( 'Full Legal Name', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="text" id="cust_name" name="name" class="dnf-input" value="<?php echo esc_attr( $cust_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. Jonathan Sterling', 'dev-nahian-car' ); ?>" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_email">
					<?php esc_html_e( 'Email Address', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="email" id="cust_email" name="email" class="dnf-input" value="<?php echo esc_attr( $cust_email ); ?>" placeholder="<?php esc_attr_e( 'client@enterprise.com', 'dev-nahian-car' ); ?>" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_phone">
					<?php esc_html_e( 'Contact Phone Number', 'dev-nahian-car' ); ?>
				</label>
				<input type="tel" id="cust_phone" name="phone" class="dnf-input" value="<?php echo esc_attr( $cust_phone ); ?>" placeholder="<?php esc_attr_e( '+1 (555) 019-2834', 'dev-nahian-car' ); ?>">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_dob">
					<?php esc_html_e( 'Date of Birth', 'dev-nahian-car' ); ?>
				</label>
				<input type="date" id="cust_dob" name="dob" class="dnf-input" value="<?php echo esc_attr( $cust_dob ); ?>">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_country">
					<?php esc_html_e( 'Country / Jurisdiction of Residence', 'dev-nahian-car' ); ?>
				</label>
				<input type="text" id="cust_country" name="country" class="dnf-input" value="<?php echo esc_attr( $cust_country ); ?>" placeholder="<?php esc_attr_e( 'e.g. United States / Switzerland', 'dev-nahian-car' ); ?>">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_status">
					<?php esc_html_e( 'KYC Verification Status', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="cust_status" name="kyc_status" class="dnf-select">
						<option value="verified" <?php selected( $cust_status, 'verified' ); ?>><?php esc_html_e( 'Verified Driver (KYC Approved)', 'dev-nahian-car' ); ?></option>
						<option value="pending" <?php selected( $cust_status, 'pending' ); ?>><?php esc_html_e( 'Pending Verification Review', 'dev-nahian-car' ); ?></option>
						<option value="suspended" <?php selected( $cust_status, 'suspended' ); ?>><?php esc_html_e( 'Suspended (Blacklisted / Hold)', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<!-- Section 2: Driver License Credentials -->
			<div class="dnf-cust-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><circle cx="9" cy="10" r="2"></circle><line x1="15" y1="8" x2="17" y2="8"></line><line x1="15" y1="12" x2="17" y2="12"></line><line x1="7" y1="16" x2="17" y2="16"></line></svg>
				<?php esc_html_e( '2. Driver Credentials & Membership Privilege', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_license">
					<?php esc_html_e( 'Driver License Number', 'dev-nahian-car' ); ?>
				</label>
				<input type="text" id="cust_license" name="license_number" class="dnf-input" value="<?php echo esc_attr( $cust_license ); ?>" placeholder="DL-98234-USA">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_lic_expiry">
					<?php esc_html_e( 'License Expiration Date', 'dev-nahian-car' ); ?>
				</label>
				<input type="date" id="cust_lic_expiry" name="license_expiry" class="dnf-input" value="<?php echo esc_attr( $cust_lic_expiry ); ?>">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cust_tier">
					<?php esc_html_e( 'Membership Tier Privilege', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="cust_tier" name="tier" class="dnf-select">
						<option value="standard" <?php selected( $cust_tier, 'standard' ); ?>><?php esc_html_e( 'Standard Member (Full Deposit Hold)', 'dev-nahian-car' ); ?></option>
						<option value="vip" <?php selected( $cust_tier, 'vip' ); ?>><?php esc_html_e( 'VIP Tier (Reduced Deposit & Priority)', 'dev-nahian-car' ); ?></option>
						<option value="sovereign" <?php selected( $cust_tier, 'sovereign' ); ?>><?php esc_html_e( 'Sovereign (Zero Deposit Required)', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<!-- Section 3: Notes & Logistics Profile -->
			<div class="dnf-cust-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
				<?php esc_html_e( '3. Profile Notes & Handling Instructions', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group col-span-full" style="margin-bottom:0;">
				<label class="dnf-label" for="cust_notes">
					<?php esc_html_e( 'Internal Operator Notes & VIP Preferences', 'dev-nahian-car' ); ?>
				</label>
				<textarea id="cust_notes" name="notes" class="dnf-textarea" placeholder="<?php esc_attr_e( 'Add corporate account numbers, chauffeur preference records, beverage requests, or verified insurance broker details...', 'dev-nahian-car' ); ?>"><?php echo esc_textarea( $cust_notes ); ?></textarea>
			</div>
		</div>

		<div style="margin-top: 32px; display: flex; gap: 12px; align-items: center;">
			<button type="submit" class="dnf-btn dnf-btn-primary">
				<?php echo $is_edit ? esc_html__( 'Save Profile Changes', 'dev-nahian-car' ) : esc_html__( 'Register Client Dossier', 'dev-nahian-car' ); ?>
			</button>
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?>
			</a>
		</div>
	</form>
</div>
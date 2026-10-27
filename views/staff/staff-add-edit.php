<?php
/**
 * Staff Module: Create & Edit Workstation (`staff-add-edit.php`)
 * Handles staff creation, profile updates, credential validation, and modern form UI.
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
$id          = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_staff = isset( $table_staff ) ? $table_staff : $wpdb->prefix . 'dev_nahian_staff';
$base        = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'staff',
	),
	admin_url( 'admin.php' )
);

$validation_error = '';

// -----------------------------------------------------------------------------
// 1. Process Save Action (Create POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_add_staff_submit'] ) ) {
	check_admin_referer( 'dev_nahian_add_staff_verify', 'dev_nahian_staff_nonce' );

	$raw_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( ! is_email( $raw_email ) ) {
		$validation_error = __( 'Please enter a valid email address for this staff profile.', 'dev-nahian-car' );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_staff} WHERE email = %s", $raw_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $email_exists ) {
			$validation_error = __( 'A staff member profile with this email address already exists. Please assign a distinct email.', 'dev-nahian-car' );
		} else {
			$staff_code = 'STF-' . strtoupper( wp_generate_password( 5, false, false ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table_staff,
				array(
					'staff_code' => $staff_code,
					'name'       => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
					'email'      => $raw_email,
					'phone'      => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
					'role_type'  => isset( $_POST['role_type'] ) ? sanitize_key( wp_unslash( $_POST['role_type'] ) ) : 'chauffeur',
					'license_no' => isset( $_POST['license_no'] ) ? sanitize_text_field( wp_unslash( $_POST['license_no'] ) ) : '',
					'status'     => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
					'notes'      => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
					'created_at' => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false !== $inserted ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'  => 'dev-nahian-fleet-manager',
							'tab'   => 'staff',
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
if ( isset( $_POST['dev_nahian_edit_staff_submit'] ) && $id > 0 ) {
	check_admin_referer( 'dev_nahian_edit_staff_' . $id, 'dev_nahian_staff_nonce' );

	$raw_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( ! is_email( $raw_email ) ) {
		$validation_error = __( 'Please enter a valid email address for this staff profile.', 'dev-nahian-car' );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_staff} WHERE email = %s AND id != %d", $raw_email, $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $email_exists ) {
			$validation_error = __( 'Another staff member profile is already registered under this email address. Please use a unique email.', 'dev-nahian-car' );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$updated = $wpdb->update(
				$table_staff,
				array(
					'name'       => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
					'email'      => $raw_email,
					'phone'      => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
					'role_type'  => isset( $_POST['role_type'] ) ? sanitize_key( wp_unslash( $_POST['role_type'] ) ) : 'chauffeur',
					'license_no' => isset( $_POST['license_no'] ) ? sanitize_text_field( wp_unslash( $_POST['license_no'] ) ) : '',
					'status'     => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
					'notes'      => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
				),
				array( 'id' => $id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'    => 'dev-nahian-fleet-manager',
							'tab'     => 'staff',
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
// 3. Load Staff Data for Form Rendering
// -----------------------------------------------------------------------------
$is_edit = ( 'edit' === $action && $id > 0 );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$staff = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $id ), ARRAY_A ) : array();

$staff_name    = (string) ( $staff['name'] ?? '' );
$staff_email   = (string) ( $staff['email'] ?? '' );
$staff_phone   = (string) ( $staff['phone'] ?? '' );
$staff_role    = sanitize_key( (string) ( $staff['role_type'] ?? 'chauffeur' ) );
$staff_license = (string) ( $staff['license_no'] ?? '' );
$staff_status  = sanitize_key( (string) ( $staff['status'] ?? 'active' ) );
$staff_notes   = (string) ( $staff['notes'] ?? '' );
?>

<style>
	/* Scoped Staff Form Card Surface */
	.dnf-staff-form-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 32px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
		max-width: 920px;
		width: 100%;
		box-sizing: border-box;
	}
	.dnf-staff-form-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px;
	}
	@media (max-width: 960px) {
		.dnf-staff-form-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 640px) {
		.dnf-staff-form-grid {
			grid-template-columns: 1fr;
		}
	}
	.col-span-full {
		grid-column: 1 / -1;
	}

	.dnf-staff-section-title {
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
	.dnf-staff-section-title:first-of-type {
		border-top: none;
		padding-top: 0;
		margin-top: 0;
	}
</style>

<!-- Toolbar / Subnav Header -->
<div class="dnf-staff-toolbar" style="margin-bottom: 20px;">
	<div class="dnf-staff-toolbar-left">
		<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
			<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Cancel & Back to Staff Roster', 'dev-nahian-car' ); ?></span>
		</a>
		<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
			<?php
			echo $is_edit
				? sprintf(
					/* translators: %s: staff member name */
					esc_html__( 'Edit Personnel Record: %s', 'dev-nahian-car' ),
					esc_html( $staff_name )
				)
				: esc_html__( 'Register New Staff Member', 'dev-nahian-car' );
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
<div class="dnf-staff-form-card">
	<form method="post" action="" id="dnfStaffForm">
		<?php
		if ( $is_edit ) {
			wp_nonce_field( 'dev_nahian_edit_staff_' . $id, 'dev_nahian_staff_nonce' );
			echo '<input type="hidden" name="dev_nahian_edit_staff_submit" value="1">';
		} else {
			wp_nonce_field( 'dev_nahian_add_staff_verify', 'dev_nahian_staff_nonce' );
			echo '<input type="hidden" name="dev_nahian_add_staff_submit" value="1">';
		}
		?>

		<div class="dnf-staff-form-grid">
			<!-- Section 1: Personnel Identity & Role -->
			<div class="dnf-staff-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
				<?php esc_html_e( '1. Personnel Identity & Role Designation', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="staff_name">
					<?php esc_html_e( 'Full Legal Name', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="text" id="staff_name" name="name" class="dnf-input" value="<?php echo esc_attr( $staff_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. Marcus Vance', 'dev-nahian-car' ); ?>" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="staff_email">
					<?php esc_html_e( 'Email Address', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="email" id="staff_email" name="email" class="dnf-input" value="<?php echo esc_attr( $staff_email ); ?>" placeholder="<?php esc_attr_e( 'staff@fleetops.com', 'dev-nahian-car' ); ?>" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="staff_phone">
					<?php esc_html_e( 'Contact Phone Number', 'dev-nahian-car' ); ?>
				</label>
				<input type="tel" id="staff_phone" name="phone" class="dnf-input" value="<?php echo esc_attr( $staff_phone ); ?>" placeholder="<?php esc_attr_e( '+1 (555) 019-2834', 'dev-nahian-car' ); ?>">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="role_type">
					<?php esc_html_e( 'Operational Role / Title', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="role_type" name="role_type" class="dnf-select">
						<option value="chauffeur" <?php selected( $staff_role, 'chauffeur' ); ?>><?php esc_html_e( 'Executive Chauffeur / Driver', 'dev-nahian-car' ); ?></option>
						<option value="dispatcher" <?php selected( $staff_role, 'dispatcher' ); ?>><?php esc_html_e( 'Desk Dispatcher / Terminal Lead', 'dev-nahian-car' ); ?></option>
						<option value="mechanic" <?php selected( $staff_role, 'mechanic' ); ?>><?php esc_html_e( 'Workshop Mechanic / Tech', 'dev-nahian-car' ); ?></option>
						<option value="concierge" <?php selected( $staff_role, 'concierge' ); ?>><?php esc_html_e( 'VIP Client Concierge', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="staff_status">
					<?php esc_html_e( 'Duty Roster Status', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="staff_status" name="status" class="dnf-select">
						<option value="active" <?php selected( $staff_status, 'active' ); ?>><?php esc_html_e( 'Active / Ready for Dispatch', 'dev-nahian-car' ); ?></option>
						<option value="on_duty" <?php selected( $staff_status, 'on_duty' ); ?>><?php esc_html_e( 'On Active Assignment', 'dev-nahian-car' ); ?></option>
						<option value="off_duty" <?php selected( $staff_status, 'off_duty' ); ?>><?php esc_html_e( 'Off Duty / Rest Period', 'dev-nahian-car' ); ?></option>
						<option value="suspended" <?php selected( $staff_status, 'suspended' ); ?>><?php esc_html_e( 'Suspended / Inactive', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="license_no">
					<?php esc_html_e( 'Professional Driver License No.', 'dev-nahian-car' ); ?>
				</label>
				<input type="text" id="license_no" name="license_no" class="dnf-input" value="<?php echo esc_attr( $staff_license ); ?>" placeholder="DL-78923-X">
			</div>

			<!-- Section 2: Credentials & Operational Logistics -->
			<div class="dnf-staff-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
				<?php esc_html_e( '2. Operational Credentials & Language Clearances', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group col-span-full" style="margin-bottom:0;">
				<label class="dnf-label" for="staff_notes">
					<?php esc_html_e( 'Notes, Security Clearances & Certifications', 'dev-nahian-car' ); ?>
				</label>
				<textarea id="staff_notes" name="notes" class="dnf-textarea" placeholder="<?php esc_attr_e( 'Security clearance rating, certified supercar transmission competence, language proficiencies, defensive driving credentials...', 'dev-nahian-car' ); ?>"><?php echo esc_textarea( $staff_notes ); ?></textarea>
			</div>
		</div>

		<div style="margin-top: 32px; display: flex; gap: 12px; align-items: center;">
			<button type="submit" class="dnf-btn dnf-btn-primary">
				<?php echo $is_edit ? esc_html__( 'Save Staff Changes', 'dev-nahian-car' ) : esc_html__( 'Register Staff Profile', 'dev-nahian-car' ); ?>
			</button>
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?>
			</a>
		</div>
	</form>
</div>
<?php
/**
 * Promos Module: Create & Edit Workstation (`promo-add-edit.php`)
 * Handles promo code configuration, percentage/fixed yield limits, and modern form UI.
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
$id           = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : ( isset( $_POST['promo_id'] ) ? absint( wp_unslash( $_POST['promo_id'] ) ) : 0 ) );
$table_promos = isset( $table_promos ) ? $table_promos : $wpdb->prefix . 'dev_nahian_promos';
$ledger_url   = isset( $ledger_url ) ? $ledger_url : add_query_arg(
	array(
		'page'   => 'dev-nahian-fleet-manager',
		'tab'    => 'promos',
		'subtab' => 'all',
	),
	admin_url( 'admin.php' )
);
$currency     = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

$action   = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$is_edit  = ( ( 'edit' === $action || isset( $_POST['dev_nahian_edit_promo_submit'] ) ) && $id > 0 );
$form_err = '';

// -----------------------------------------------------------------------------
// 1. Process CREATE (POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_add_promo_submit'] ) ) {
	check_admin_referer( 'dev_nahian_add_promo_verify', 'dev_nahian_promo_nonce' );

	$raw_code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
	$promo_code = strtoupper( (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $raw_code ) );

	if ( '' === $promo_code ) {
		$form_err = __( 'Coupon Code Identifier cannot be empty.', 'dev-nahian-car' );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$code_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_promos} WHERE code = %s", $promo_code ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $code_exists ) {
			$form_err = __( 'A promotional coupon with this code already exists.', 'dev-nahian-car' );
		} else {
			$discount_type = isset( $_POST['discount_type'] ) ? sanitize_key( wp_unslash( $_POST['discount_type'] ) ) : 'percentage';
			$discount_val  = isset( $_POST['discount_value'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['discount_value'] ) ) : 0.00;
			$expiry_raw    = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
			$expiry_date   = ! empty( $expiry_raw ) ? $expiry_raw : null;
			$usage_limit   = isset( $_POST['usage_limit'] ) ? max( 1, absint( wp_unslash( $_POST['usage_limit'] ) ) ) : 100;
			$status_val    = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';

			// Percentage yield cap check.
			if ( 'percentage' === $discount_type && $discount_val > 100 ) {
				$discount_val = 100.00;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table_promos,
				array(
					'code'           => $promo_code,
					'discount_type'  => $discount_type,
					'discount_value' => $discount_val,
					'expiry_date'    => $expiry_date,
					'usage_limit'    => $usage_limit,
					'times_used'     => 0,
					'status'         => $status_val,
					'created_at'     => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%f', '%s', '%d', '%d', '%s', '%s' )
			);

			if ( false !== $inserted ) {
				wp_safe_redirect( add_query_arg( array( 'saved' => '1' ), $ledger_url ) );
				exit;
			} else {
				$form_err = sprintf(
					/* translators: %s: database error message */
					__( 'Database error: %s', 'dev-nahian-car' ),
					$wpdb->last_error
				);
			}
		}
	}
}

// -----------------------------------------------------------------------------
// 2. Process UPDATE (POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_edit_promo_submit'] ) && $id > 0 ) {
	check_admin_referer( 'dev_nahian_edit_promo_' . $id, 'dev_nahian_promo_nonce' );

	$raw_code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
	$promo_code = strtoupper( (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $raw_code ) );

	if ( '' === $promo_code ) {
		$form_err = __( 'Coupon Code Identifier cannot be empty.', 'dev-nahian-car' );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$code_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_promos} WHERE code = %s AND id != %d", $promo_code, $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $code_exists ) {
			$form_err = __( 'Another promotional coupon is already using this code identifier.', 'dev-nahian-car' );
		} else {
			$discount_type = isset( $_POST['discount_type'] ) ? sanitize_key( wp_unslash( $_POST['discount_type'] ) ) : 'percentage';
			$discount_val  = isset( $_POST['discount_value'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['discount_value'] ) ) : 0.00;
			$expiry_raw    = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
			$expiry_date   = ! empty( $expiry_raw ) ? $expiry_raw : null;
			$usage_limit   = isset( $_POST['usage_limit'] ) ? max( 1, absint( wp_unslash( $_POST['usage_limit'] ) ) ) : 100;
			$status_val    = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';

			if ( 'percentage' === $discount_type && $discount_val > 100 ) {
				$discount_val = 100.00;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$updated = $wpdb->update(
				$table_promos,
				array(
					'code'           => $promo_code,
					'discount_type'  => $discount_type,
					'discount_value' => $discount_val,
					'expiry_date'    => $expiry_date,
					'usage_limit'    => $usage_limit,
					'status'         => $status_val,
				),
				array( 'id' => $id ),
				array( '%s', '%s', '%f', '%s', '%d', '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				wp_safe_redirect( add_query_arg( array( 'updated' => '1' ), $ledger_url ) );
				exit;
			} else {
				$form_err = sprintf(
					/* translators: %s: database error message */
					__( 'Database update failed: %s', 'dev-nahian-car' ),
					$wpdb->last_error
				);
			}
		}
	}
}

// -----------------------------------------------------------------------------
// 3. Load Active Record For Form Display
// -----------------------------------------------------------------------------
$promo = array();
if ( $is_edit ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$promo = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_promos} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( ! $promo && empty( $form_err ) ) {
		$form_err = __( 'Requested promotional record could not be found.', 'dev-nahian-car' );
	}
}

// Retain submitted inputs if an error prevented redirect.
if ( ! empty( $_POST ) && ! empty( $form_err ) ) {
	$promo['code']           = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
	$promo['discount_type']  = sanitize_key( wp_unslash( $_POST['discount_type'] ?? 'percentage' ) );
	$promo['discount_value'] = (float) sanitize_text_field( wp_unslash( $_POST['discount_value'] ?? 0.00 ) );
	$promo['expiry_date']    = sanitize_text_field( wp_unslash( $_POST['expiry_date'] ?? '' ) );
	$promo['usage_limit']    = absint( wp_unslash( $_POST['usage_limit'] ?? 100 ) );
	$promo['status']         = sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) );
}

$current_form_url = add_query_arg(
	array(
		'page'   => 'dev-nahian-fleet-manager',
		'tab'    => 'promos',
		'subtab' => 'add',
		'action' => $is_edit ? 'edit' : 'add',
		'id'     => $id,
	),
	admin_url( 'admin.php' )
);

$code_val          = (string) ( $promo['code'] ?? '' );
$discount_type_val = sanitize_key( (string) ( $promo['discount_type'] ?? 'percentage' ) );
$discount_val_val  = isset( $promo['discount_value'] ) ? (string) $promo['discount_value'] : '0.00';
$expiry_date_val   = (string) ( $promo['expiry_date'] ?? '' );
$usage_limit_val   = isset( $promo['usage_limit'] ) ? (string) $promo['usage_limit'] : '100';
$status_val_val    = sanitize_key( (string) ( $promo['status'] ?? 'active' ) );
?>

<style>
	/* Scoped Promo Form Card Surface */
	.dnf-promo-form-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 32px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
		max-width: 920px;
	}
	.dnf-promo-form-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px;
	}
	@media (max-width: 960px) {
		.dnf-promo-form-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 640px) {
		.dnf-promo-form-grid {
			grid-template-columns: 1fr;
		}
	}
	.col-span-full {
		grid-column: 1 / -1;
	}

	.dnf-promo-section-title {
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
	.dnf-promo-section-title:first-of-type {
		border-top: none;
		padding-top: 0;
		margin-top: 0;
	}
</style>

<!-- Toolbar / Subnav Header -->
<div class="dnf-promo-toolbar" style="margin-bottom: 20px;">
	<div class="dnf-promo-toolbar-left">
		<a href="<?php echo esc_url( $ledger_url ); ?>" class="dnf-btn dnf-btn-secondary">
			<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Cancel & Back to Promotions Ledger', 'dev-nahian-car' ); ?></span>
		</a>
		<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
			<?php
			echo $is_edit
				? sprintf(
					/* translators: %s: coupon code identifier */
					esc_html__( 'Edit Promo Code: #%s', 'dev-nahian-car' ),
					esc_html( $code_val )
				)
				: esc_html__( 'Create New Promo Code', 'dev-nahian-car' );
			?>
		</h2>
	</div>
</div>

<?php if ( ! empty( $form_err ) ) : ?>
	<div class="notice notice-error is-dismissible" style="margin-bottom: 20px !important;">
		<p><strong><?php esc_html_e( 'Error:', 'dev-nahian-car' ); ?></strong> <?php echo esc_html( $form_err ); ?></p>
	</div>
<?php endif; ?>

<!-- Form Surface -->
<div class="dnf-promo-form-card">
	<form method="post" action="<?php echo esc_url( $current_form_url ); ?>" id="dnfPromoForm">
		<?php
		if ( $is_edit ) {
			wp_nonce_field( 'dev_nahian_edit_promo_' . $id, 'dev_nahian_promo_nonce' );
			echo '<input type="hidden" name="promo_id" value="' . esc_attr( (string) $id . '">';
		} else {
			wp_nonce_field( 'dev_nahian_add_promo_verify', 'dev_nahian_promo_nonce' );
		}
		?>

		<div class="dnf-promo-form-grid">
			<!-- Section 1: Coupon Configuration -->
			<div class="dnf-promo-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
				<?php esc_html_e( '1. Coupon Identifier & Yield Mechanism', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="code">
					<?php esc_html_e( 'Coupon Code Identifier', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="text" id="code" name="code" class="dnf-input" value="<?php echo esc_attr( $code_val ); ?>" placeholder="<?php esc_attr_e( 'e.g. SUMMERSPECIAL20', 'dev-nahian-car' ); ?>" style="text-transform: uppercase;" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="discount_type">
					<?php esc_html_e( 'Discount Mechanism', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="discount_type" name="discount_type" class="dnf-select">
						<option value="percentage" <?php selected( $discount_type_val, 'percentage' ); ?>><?php esc_html_e( 'Percentage Discount (%)', 'dev-nahian-car' ); ?></option>
						<option value="fixed" <?php selected( $discount_type_val, 'fixed' ); ?>><?php printf( esc_html__( 'Fixed Amount (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></option>
					</select>
				</div>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="discount_value">
					<?php esc_html_e( 'Discount Numerical Value', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="number" step="0.01" min="0" id="discount_value" name="discount_value" class="dnf-input" value="<?php echo esc_attr( $discount_val_val ); ?>" placeholder="15.00" required>
			</div>

			<!-- Section 2: Validity & Limitations -->
			<div class="dnf-promo-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
				<?php esc_html_e( '2. Validity Window & Redemption Controls', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="expiry_date">
					<?php esc_html_e( 'Expiration Date (Optional)', 'dev-nahian-car' ); ?>
				</label>
				<input type="date" id="expiry_date" name="expiry_date" class="dnf-input" value="<?php echo esc_attr( $expiry_date_val ); ?>">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="usage_limit">
					<?php esc_html_e( 'Maximum Redemption Limit', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="number" id="usage_limit" name="usage_limit" class="dnf-input" value="<?php echo esc_attr( $usage_limit_val ); ?>" min="1" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="status">
					<?php esc_html_e( 'Voucher Lifecycle Status', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="status" name="status" class="dnf-select">
						<option value="active" <?php selected( $status_val_val, 'active' ); ?>><?php esc_html_e( 'Active • Redeemable', 'dev-nahian-car' ); ?></option>
						<option value="expired" <?php selected( $status_val_val, 'expired' ); ?>><?php esc_html_e( 'Expired • Window Closed', 'dev-nahian-car' ); ?></option>
						<option value="disabled" <?php selected( $status_val_val, 'disabled' ); ?>><?php esc_html_e( 'Disabled • Inactive', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>
		</div>

		<div style="margin-top: 32px; display: flex; gap: 12px; align-items: center;">
			<button type="submit" name="<?php echo $is_edit ? 'dev_nahian_edit_promo_submit' : 'dev_nahian_add_promo_submit'; ?>" class="dnf-btn dnf-btn-primary">
				<span><?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Save Promo Code', 'dev-nahian-car' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $ledger_url ); ?>" class="dnf-btn dnf-btn-secondary">
				<span><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></span>
			</a>
		</div>
	</form>
</div>
<?php
/**
 * Finances Module: Create & Edit Workstation (`finance-add-edit.php`)
 * Handles income and expense transaction records, validation, and modern form UI.
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
$id             = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : ( isset( $_POST['txn_id'] ) ? absint( wp_unslash( $_POST['txn_id'] ) ) : 0 ) );
$table_finances = isset( $table_finances ) ? $table_finances : $wpdb->prefix . 'dev_nahian_finances';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'finances',
	),
	admin_url( 'admin.php' )
);
$currency       = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

$allowed_types      = array( 'income', 'expense' );
$allowed_categories = array( 'rental', 'maintenance', 'insurance', 'fuel', 'marketing', 'other' );

// -----------------------------------------------------------------------------
// 1. Process Save Action (Create POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_add_txn_submit'] ) ) {
	check_admin_referer( 'dev_nahian_add_txn_verify', 'dev_nahian_txn_nonce' );

	$type_val = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'income';
	if ( ! in_array( $type_val, $allowed_types, true ) ) {
		$type_val = 'income';
	}

	$category_val = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'rental';
	if ( ! in_array( $category_val, $allowed_categories, true ) ) {
		$category_val = 'rental';
	}

	$amount   = isset( $_POST['amount'] ) ? max( 0.0, (float) sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) : 0.00;
	$txn_date = ! empty( $_POST['txn_date'] ) ? sanitize_text_field( wp_unslash( $_POST['txn_date'] ) ) : current_time( 'Y-m-d' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$table_finances,
		array(
			'title'          => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'type'           => $type_val,
			'category'       => $category_val,
			'amount'         => $amount,
			'txn_date'       => $txn_date,
			'reference_code' => isset( $_POST['reference_code'] ) ? sanitize_text_field( wp_unslash( $_POST['reference_code'] ) ) : '',
			'notes'          => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			'created_at'     => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
	);

	if ( false !== $inserted ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'dev-nahian-fleet-manager',
					'tab'   => 'finances',
					'saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

// -----------------------------------------------------------------------------
// 2. Process Edit Action (Update POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_edit_txn_submit'] ) && $id > 0 ) {
	check_admin_referer( 'dev_nahian_edit_txn_' . $id, 'dev_nahian_txn_nonce' );

	$type_val = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'income';
	if ( ! in_array( $type_val, $allowed_types, true ) ) {
		$type_val = 'income';
	}

	$category_val = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'rental';
	if ( ! in_array( $category_val, $allowed_categories, true ) ) {
		$category_val = 'rental';
	}

	$amount   = isset( $_POST['amount'] ) ? max( 0.0, (float) sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) : 0.00;
	$txn_date = ! empty( $_POST['txn_date'] ) ? sanitize_text_field( wp_unslash( $_POST['txn_date'] ) ) : current_time( 'Y-m-d' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$updated = $wpdb->update(
		$table_finances,
		array(
			'title'          => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'type'           => $type_val,
			'category'       => $category_val,
			'amount'         => $amount,
			'txn_date'       => $txn_date,
			'reference_code' => isset( $_POST['reference_code'] ) ? sanitize_text_field( wp_unslash( $_POST['reference_code'] ) ) : '',
			'notes'          => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
		),
		array( 'id' => $id ),
		array( '%s', '%s', '%s', '%f', '%s', '%s', '%s' ),
		array( '%d' )
	);

	if ( false !== $updated ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'dev-nahian-fleet-manager',
					'tab'     => 'finances',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

// -----------------------------------------------------------------------------
// 3. Load Active Record For Form Rendering
// -----------------------------------------------------------------------------
$is_edit = ( 'edit' === $action && $id > 0 );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$txn = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_finances} WHERE id = %d", $id ), ARRAY_A ) : array();

$title_val     = (string) ( $txn['title'] ?? '' );
$type_val      = sanitize_key( (string) ( $txn['type'] ?? 'income' ) );
$category_val  = sanitize_key( (string) ( $txn['category'] ?? 'rental' ) );
$amount_val    = (string) ( $txn['amount'] ?? '' );
$txn_date_val  = (string) ( $txn['txn_date'] ?? current_time( 'Y-m-d' ) );
$ref_code_val  = (string) ( $txn['reference_code'] ?? '' );
$notes_val     = (string) ( $txn['notes'] ?? '' );
?>

<style>
	/* Scoped Financial Form Surface */
	.dnf-fin-form-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 32px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
		max-width: 920px;
	}
	.dnf-fin-form-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px;
	}
	@media (max-width: 960px) {
		.dnf-fin-form-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 640px) {
		.dnf-fin-form-grid {
			grid-template-columns: 1fr;
		}
	}
	.col-span-full {
		grid-column: 1 / -1;
	}

	.dnf-fin-section-title {
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
	.dnf-fin-section-title:first-of-type {
		border-top: none;
		padding-top: 0;
		margin-top: 0;
	}
</style>

<!-- Toolbar / Subnav Header -->
<div class="dnf-fin-toolbar" style="margin-bottom: 20px;">
	<div class="dnf-fin-toolbar-left">
		<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
			<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Cancel & Back to Treasury Ledger', 'dev-nahian-car' ); ?>
		</a>
		<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
			<?php
			echo $is_edit
				? sprintf(
					/* translators: %d: transaction record ID */
					esc_html__( 'Edit Transaction #%d', 'dev-nahian-car' ),
					absint( $id )
				)
				: esc_html__( 'Record Financial Entry', 'dev-nahian-car' );
			?>
		</h2>
	</div>
</div>

<!-- Form Surface -->
<div class="dnf-fin-form-card">
	<form method="post" action="" id="dnfFinForm">
		<?php
		if ( $is_edit ) {
			wp_nonce_field( 'dev_nahian_edit_txn_' . $id, 'dev_nahian_txn_nonce' );
			echo '<input type="hidden" name="dev_nahian_edit_txn_submit" value="1">';
			echo '<input type="hidden" name="txn_id" value="' . esc_attr( (string) $id ) . '">';
		} else {
			wp_nonce_field( 'dev_nahian_add_txn_verify', 'dev_nahian_txn_nonce' );
			echo '<input type="hidden" name="dev_nahian_add_txn_submit" value="1">';
		}
		?>

		<div class="dnf-fin-form-grid">
			<!-- Section 1: Ledger Details -->
			<div class="dnf-fin-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
				<?php esc_html_e( '1. Ledger Classification & Accounting Scope', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group col-span-full">
				<label class="dnf-label" for="title">
					<?php esc_html_e( 'Transaction Title / Purpose', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="text" id="title" name="title" class="dnf-input" value="<?php echo esc_attr( $title_val ); ?>" placeholder="<?php esc_attr_e( 'e.g. Monthly Commercial Fleet Insurance Premium', 'dev-nahian-car' ); ?>" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="type">
					<?php esc_html_e( 'Accounting Type', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="type" name="type" class="dnf-select">
						<option value="income" <?php selected( $type_val, 'income' ); ?>><?php esc_html_e( 'Income (Credit / Inflow)', 'dev-nahian-car' ); ?></option>
						<option value="expense" <?php selected( $type_val, 'expense' ); ?>><?php esc_html_e( 'Expense (Debit / Outflow)', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="category">
					<?php esc_html_e( 'Operational Category', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="category" name="category" class="dnf-select">
						<option value="rental" <?php selected( $category_val, 'rental' ); ?>><?php esc_html_e( 'Rental Revenue', 'dev-nahian-car' ); ?></option>
						<option value="maintenance" <?php selected( $category_val, 'maintenance' ); ?>><?php esc_html_e( 'Vehicle Maintenance', 'dev-nahian-car' ); ?></option>
						<option value="insurance" <?php selected( $category_val, 'insurance' ); ?>><?php esc_html_e( 'Fleet Insurance', 'dev-nahian-car' ); ?></option>
						<option value="fuel" <?php selected( $category_val, 'fuel' ); ?>><?php esc_html_e( 'Fuel & Detailing', 'dev-nahian-car' ); ?></option>
						<option value="marketing" <?php selected( $category_val, 'marketing' ); ?>><?php esc_html_e( 'Marketing & Ads', 'dev-nahian-car' ); ?></option>
						<option value="other" <?php selected( $category_val, 'other' ); ?>><?php esc_html_e( 'Other Operations', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="amount">
					<?php
					printf(
						/* translators: %s: currency code */
						esc_html__( 'Amount (%s)', 'dev-nahian-car' ),
						esc_html( $currency )
					);
					?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="number" step="0.01" min="0" id="amount" name="amount" class="dnf-input" value="<?php echo esc_attr( $amount_val ); ?>" placeholder="1250.00" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="txn_date">
					<?php esc_html_e( 'Transaction Date', 'dev-nahian-car' ); ?>
				</label>
				<input type="date" id="txn_date" name="txn_date" class="dnf-input" value="<?php echo esc_attr( $txn_date_val ); ?>">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="reference_code">
					<?php esc_html_e( 'Reference / Invoice Code', 'dev-nahian-car' ); ?>
				</label>
				<input type="text" id="reference_code" name="reference_code" class="dnf-input" value="<?php echo esc_attr( $ref_code_val ); ?>" placeholder="<?php esc_attr_e( 'INV-2026-001', 'dev-nahian-car' ); ?>">
			</div>

			<!-- Section 2: Operational Notes & Documentation -->
			<div class="dnf-fin-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
				<?php esc_html_e( '2. Operational Documentation & Receipt Manifest', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group col-span-full" style="margin-bottom:0;">
				<label class="dnf-label" for="notes">
					<?php esc_html_e( 'Notes, Payment Terms & Receipt Verification', 'dev-nahian-car' ); ?>
				</label>
				<textarea id="notes" name="notes" class="dnf-textarea" placeholder="<?php esc_attr_e( 'Vendor invoice details, direct deposit reference, payment approval sign-off...', 'dev-nahian-car' ); ?>"><?php echo esc_textarea( $notes_val ); ?></textarea>
			</div>
		</div>

		<div style="margin-top: 32px; display: flex; gap: 12px; align-items: center;">
			<button type="submit" class="dnf-btn dnf-btn-primary">
				<?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Record Transaction', 'dev-nahian-car' ); ?>
			</button>
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?>
			</a>
		</div>
	</form>
</div>
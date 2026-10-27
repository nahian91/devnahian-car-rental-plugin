<?php
/**
 * Categories Module: Create & Edit Workstation (`category-add-edit.php`)
 * Handles category creation, update mutations, validation, and modern form controls.
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
$table_categories = isset( $table_categories ) ? $table_categories : $wpdb->prefix . 'dev_nahian_categories';
$base             = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'categories',
	),
	admin_url( 'admin.php' )
);
$currency         = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

$validation_error = '';

// -----------------------------------------------------------------------------
// 1. Process Save Action (Create / Insert)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_add_category_submit'] ) ) {
	check_admin_referer( 'dev_nahian_add_category_verify', 'dev_nahian_cat_nonce' );

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

	if ( empty( $slug ) ) {
		$slug = sanitize_title( $name );
	}

	$raw_price  = isset( $_POST['base_price'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['base_price'] ) ) : 0.00;
	$base_price = (float) number_format( max( 0.0, $raw_price ), 2, '.', '' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$slug_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_categories} WHERE slug = %s", $slug ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( $slug_exists ) {
		$validation_error = __( 'A fleet classification with this slug already exists. Please choose a unique slug identifier.', 'dev-nahian-car' );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table_categories,
			array(
				'name'        => $name,
				'slug'        => $slug,
				'base_price'  => $base_price,
				'status'      => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
				'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%f', '%s', '%s', '%s' )
		);

		if ( false !== $inserted ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'dev-nahian-fleet-manager',
						'tab'   => 'categories',
						'saved' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}
}

// -----------------------------------------------------------------------------
// 2. Process Edit Action (Update)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_edit_category_submit'] ) && $id > 0 ) {
	check_admin_referer( 'dev_nahian_edit_category_' . $id, 'dev_nahian_cat_nonce' );

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

	if ( empty( $slug ) ) {
		$slug = sanitize_title( $name );
	}

	$raw_price  = isset( $_POST['base_price'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['base_price'] ) ) : 0.00;
	$base_price = (float) number_format( max( 0.0, $raw_price ), 2, '.', '' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$slug_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_categories} WHERE slug = %s AND id != %d", $slug, $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( $slug_exists ) {
		$validation_error = __( 'Another category is already utilizing this slug. Please assign a distinct slug.', 'dev-nahian-car' );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->update(
			$table_categories,
			array(
				'name'        => $name,
				'slug'        => $slug,
				'base_price'  => $base_price,
				'status'      => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
				'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%f', '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $updated ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'dev-nahian-fleet-manager',
						'tab'     => 'categories',
						'updated' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}
}

// -----------------------------------------------------------------------------
// 3. Load Category Data for Editing
// -----------------------------------------------------------------------------
$is_edit = ( 'edit' === $action && $id > 0 );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cat = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_categories} WHERE id = %d", $id ), ARRAY_A ) : array();

$cat_name       = (string) ( $cat['name'] ?? '' );
$cat_slug       = (string) ( $cat['slug'] ?? '' );
$cat_base_price = isset( $cat['base_price'] ) ? (string) $cat['base_price'] : '';
$cat_status     = (string) ( $cat['status'] ?? 'active' );
$cat_desc       = (string) ( $cat['description'] ?? '' );
?>

<style>
	/* Scoped Category Form Card */
	.dnf-category-form-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 32px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
		max-width: 820px;
	}
	.dnf-cat-form-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 20px;
	}
	@media (max-width: 680px) {
		.dnf-cat-form-grid {
			grid-template-columns: 1fr;
		}
	}
	.col-span-full {
		grid-column: 1 / -1;
	}

	.dnf-cat-section-title {
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
	.dnf-cat-section-title:first-of-type {
		border-top: none;
		padding-top: 0;
		margin-top: 0;
	}

	.dnf-help-text {
		display: block;
		font-size: 11.5px;
		color: var(--dnf-text-secondary, #64748b);
		margin-top: 5px;
	}
</style>

<!-- Toolbar / Subnav Header -->
<div class="dnf-cat-toolbar" style="margin-bottom: 20px;">
	<div class="dnf-cat-toolbar-left">
		<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
			<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Cancel & Back to Taxonomy', 'dev-nahian-car' ); ?></span>
		</a>
		<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
			<?php
			echo $is_edit
				? sprintf(
					/* translators: %s: category name */
					esc_html__( 'Edit Classification: %s', 'dev-nahian-car' ),
					esc_html( $cat_name )
				)
				: esc_html__( 'Register New Category', 'dev-nahian-car' );
			?>
		</h2>
	</div>
</div>

<?php if ( ! empty( $validation_error ) ) : ?>
	<div class="notice notice-error is-dismissible" style="margin-bottom: 20px !important;">
		<p><strong><?php esc_html_e( 'Error:', 'dev-nahian-car' ); ?></strong> <?php echo esc_html( $validation_error ); ?></p>
	</div>
<?php endif; ?>

<!-- Category Form Surface -->
<div class="dnf-category-form-card">
	<form method="post" action="" id="dnfCategoryForm">
		<?php
		if ( $is_edit ) {
			wp_nonce_field( 'dev_nahian_edit_category_' . $id, 'dev_nahian_cat_nonce' );
			echo '<input type="hidden" name="dev_nahian_edit_category_submit" value="1">';
		} else {
			wp_nonce_field( 'dev_nahian_add_category_verify', 'dev_nahian_cat_nonce' );
			echo '<input type="hidden" name="dev_nahian_add_category_submit" value="1">';
		}
		?>

		<div class="dnf-cat-form-grid">
			<!-- Section 1: Taxonomy Definition -->
			<div class="dnf-cat-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
				<?php esc_html_e( '1. Taxonomy & Classification Identity', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="category_name">
					<?php esc_html_e( 'Category Title', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="text" id="category_name" name="name" class="dnf-input" value="<?php echo esc_attr( $cat_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. Ultra Luxury Saloon', 'dev-nahian-car' ); ?>" required>
				<span class="dnf-help-text"><?php esc_html_e( 'Public category name displayed to clients and operators.', 'dev-nahian-car' ); ?></span>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="category_slug">
					<?php esc_html_e( 'URL Slug Identifier', 'dev-nahian-car' ); ?>
				</label>
				<input type="text" id="category_slug" name="slug" class="dnf-input" value="<?php echo esc_attr( $cat_slug ); ?>" placeholder="<?php esc_attr_e( 'e.g. ultra-luxury-saloon', 'dev-nahian-car' ); ?>">
				<span class="dnf-help-text"><?php esc_html_e( 'Unique programmatic slug used in database filtering.', 'dev-nahian-car' ); ?></span>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="base_price">
					<?php
					printf(
						/* translators: %s: currency code */
						esc_html__( 'Base Daily Tariff (%s)', 'dev-nahian-car' ),
						esc_html( $currency )
					);
					?>
				</label>
				<input type="number" step="0.01" min="0" id="base_price" name="base_price" class="dnf-input" value="<?php echo esc_attr( $cat_base_price ); ?>" placeholder="249.00">
				<span class="dnf-help-text"><?php esc_html_e( 'Suggested baseline rate for vehicles assigned to this tier.', 'dev-nahian-car' ); ?></span>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="category_status">
					<?php esc_html_e( 'Publish Status', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="category_status" name="status" class="dnf-select">
						<option value="active" <?php selected( $cat_status, 'active' ); ?>><?php esc_html_e( 'Active • Visible in Fleet', 'dev-nahian-car' ); ?></option>
						<option value="inactive" <?php selected( $cat_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive • Hidden from Terminal', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<!-- Section 2: Editorial Description -->
			<div class="dnf-cat-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
				<?php esc_html_e( '2. Tier Specification & Customer Summary', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group col-span-full" style="margin-bottom:0;">
				<label class="dnf-label" for="description">
					<?php esc_html_e( 'Classification Summary & Features', 'dev-nahian-car' ); ?>
				</label>
				<textarea id="description" name="description" class="dnf-textarea" placeholder="<?php esc_attr_e( 'Executive transport chassis engineered with sound deadening, rear reclining comfort packages, and private chauffeur amenities...', 'dev-nahian-car' ); ?>"><?php echo esc_textarea( $cat_desc ); ?></textarea>
			</div>
		</div>

		<div style="margin-top: 32px; display: flex; gap: 12px; align-items: center;">
			<button type="submit" class="dnf-btn dnf-btn-primary">
				<?php echo $is_edit ? esc_html__( 'Save Category Changes', 'dev-nahian-car' ) : esc_html__( 'Register Category Tier', 'dev-nahian-car' ); ?>
			</button>
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?>
			</a>
		</div>
	</form>
</div>

<script>
(function() {
	var nameInput = document.getElementById('category_name');
	var slugInput = document.getElementById('category_slug');
	var userModifiedSlug = Boolean(slugInput && slugInput.value.trim() !== '');

	if (slugInput) {
		slugInput.addEventListener('input', function() {
			userModifiedSlug = slugInput.value.trim() !== '';
		});
	}

	if (nameInput && slugInput) {
		nameInput.addEventListener('input', function() {
			if (!userModifiedSlug) {
				slugInput.value = nameInput.value
					.toLowerCase()
					.trim()
					.replace(/[^a-z0-9\s-]/g, '')
					.replace(/[\s-]+/g, '-');
			}
		});
	}
})();
</script>
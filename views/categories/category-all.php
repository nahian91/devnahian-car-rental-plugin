<?php
/**
 * Categories Module: All Categories List Table (`category-all.php`)
 * Displays tabulated fleet classification tiers with car counts, filter awareness, and action triggers.
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
$table_categories = isset( $table_categories ) ? $table_categories : $wpdb->prefix . 'dev_nahian_categories';
$table_cars       = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$base             = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'categories',
	),
	admin_url( 'admin.php' )
);
$currency         = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );
$current_subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Categories (Respecting Subtab Status Filter)
// -----------------------------------------------------------------------------
if ( in_array( $current_subtab, array( 'active', 'inactive' ), true ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$categories = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_categories} WHERE status = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_subtab
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$categories = $wpdb->get_results( "SELECT * FROM {$table_categories} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$cat_count = is_array( $categories ) ? count( $categories ) : 0;

// -----------------------------------------------------------------------------
// 2. Aggregate Car Allocation Counts (Eliminates N+1 loop queries)
// -----------------------------------------------------------------------------
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$car_counts_raw = $wpdb->get_results( "SELECT category, COUNT(*) as total FROM {$table_cars} GROUP BY category", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$car_counts_map = array();

if ( ! empty( $car_counts_raw ) ) {
	foreach ( $car_counts_raw as $cnt_row ) {
		$car_counts_map[ (string) $cnt_row['category'] ] = absint( $cnt_row['total'] );
	}
}
?>

<style>
	/* Scoped Category Listing Styles */
	.dnf-categories-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-categories-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-categories-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-categories-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-categories-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-categories-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}

	/* Category Details Cells */
	.dnf-cat-name-link {
		font-size: 13.5px;
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		text-decoration: none;
		transition: color 0.15s ease;
	}
	.dnf-cat-name-link:hover {
		color: var(--dnf-accent-teal, #0d9488);
	}
	.dnf-cat-fleet-tag {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-size: 12px;
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		padding: 3px 8px;
		border-radius: var(--dnf-radius-sm, 6px);
		font-feature-settings: "tnum";
	}
	.dnf-cat-fleet-tag.empty {
		color: var(--dnf-text-tertiary, #94a3b8);
	}

	/* Action Icons */
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
	.dnf-btn-icon.disabled {
		opacity: 0.35;
		cursor: not-allowed;
		pointer-events: none;
	}
	.dnf-table-empty {
		text-align: center;
		padding: 36px 16px !important;
		color: var(--dnf-text-tertiary, #94a3b8);
		font-size: 13px;
	}
</style>

<div class="dnf-categories-table-card">
	<div class="dnf-categories-meta-bar">
		<div class="dnf-categories-counter">
			<?php
			printf(
				/* translators: %s: total category count */
				esc_html__( 'Classifications: %s tiers registered', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $cat_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( $current_subtab ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-categories-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 180px;"><?php esc_html_e( 'Category Title', 'dev-nahian-car' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Slug Identifier', 'dev-nahian-car' ); ?></th>
					<th style="width: 130px;"><?php esc_html_e( 'Base Daily Rate', 'dev-nahian-car' ); ?></th>
					<th style="width: 130px;"><?php esc_html_e( 'Active Assets', 'dev-nahian-car' ); ?></th>
					<th style="width: 110px;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $categories ) ) : ?>
					<?php
					foreach ( $categories as $c ) :
						$cat_id       = absint( $c['id'] );
						$cat_slug_key = sanitize_key( (string) ( $c['status'] ?? 'active' ) );
						$cars_in_cat  = $car_counts_map[ (string) $c['slug'] ] ?? 0;

						$view_url = add_query_arg(
							array(
								'action' => 'view',
								'id'     => $cat_id,
							),
							$base
						);
						$edit_url = add_query_arg(
							array(
								'action' => 'edit',
								'id'     => $cat_id,
								'subtab' => 'add',
							),
							$base
						);
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'delete',
									'id'     => $cat_id,
								),
								$base
							),
							'dev_nahian_delete_category_' . $cat_id
						);

						$has_vehicles = ( $cars_in_cat > 0 );
						?>
						<tr>
							<!-- Category Name -->
							<td>
								<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-cat-name-link">
									<?php echo esc_html( (string) $c['name'] ); ?>
								</a>
							</td>

							<!-- Slug Identifier -->
							<td>
								<code class="dnf-code"><?php echo esc_html( (string) $c['slug'] ); ?></code>
							</td>

							<!-- Base Daily Rate -->
							<td>
								<strong style="color: var(--dnf-text-primary, #0f172a); font-feature-settings: 'tnum';">
									<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $c['base_price'] ?? 0 ), 2 ) ); ?>
								</strong>
							</td>

							<!-- Registered Cars -->
							<td>
								<span class="dnf-cat-fleet-tag <?php echo 0 === $cars_in_cat ? 'empty' : ''; ?>">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 17h14v-5l-2-6H7L5 12v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
									<?php
									printf(
										/* translators: %s: number of units */
										esc_html__( '%s Units', 'dev-nahian-car' ),
										esc_html( (string) number_format_i18n( $cars_in_cat ) )
									);
									?>
								</span>
							</td>

							<!-- Status Pill -->
							<td>
								<span class="dnf-status-pill <?php echo esc_attr( $cat_slug_key ); ?>">
									&bull; <?php echo esc_html( ucfirst( $cat_slug_key ) ); ?>
								</span>
							</td>

							<!-- Actions -->
							<td style="text-align: right;">
								<div class="dnf-actions-group">
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'Inspect Category Dossier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Category Tier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<?php if ( $has_vehicles ) : ?>
										<span class="dnf-btn-icon delete disabled" title="<?php esc_attr_e( 'Cannot retire category with registered active vehicles. Reassign them first.', 'dev-nahian-car' ); ?>">
											<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
									<?php else : ?>
										<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Permanently Retire Category', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Permanently retire this classification category?', 'dev-nahian-car' ); ?>');">
											<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6" class="dnf-table-empty">
							<?php esc_html_e( 'No fleet classification categories registered matching this filter.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
/**
 * Categories Module: Category Details Inspector (`category-view.php`)
 * Detailed inspection dossier with assigned vehicle manifest and classification metrics.
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
$id               = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
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

if ( $id <= 0 ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid category identifier provided.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_categories} WHERE id = %d", $id ), ARRAY_A );

if ( ! $cat ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Fleet classification category not found.', 'dev-nahian-car' ) . '</p></div>';
	return;
}

$cat_status_slug = sanitize_key( (string) ( $cat['status'] ?? 'active' ) );
$cat_slug        = sanitize_title( (string) ( $cat['slug'] ?? '' ) );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$assigned_cars = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id, name, brand, plate, price, status, image FROM {$table_cars} WHERE category = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cat_slug
	),
	ARRAY_A
);
$car_count = is_array( $assigned_cars ) ? count( $assigned_cars ) : 0;

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
	/* Scoped Category Dossier Styles */
	.dnf-cat-inspector-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Navigation Toolbar */
	.dnf-cat-inspector-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-cat-inspector-nav-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Main Category Card */
	.dnf-cat-dossier-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		overflow: hidden;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
	}

	/* Executive Header */
	.dnf-cat-dossier-header {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
		color: #ffffff;
		padding: 24px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-cat-dossier-code {
		font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
		font-size: 11.5px;
		font-weight: 700;
		color: var(--dnf-accent-gold, #b88e1e);
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.dnf-cat-dossier-heading {
		margin: 4px 0 0;
		font-size: 22px;
		font-weight: 800;
		color: #ffffff;
		letter-spacing: -0.02em;
	}

	/* Body Content */
	.dnf-cat-dossier-body {
		padding: 28px 30px;
		display: flex;
		flex-direction: column;
		gap: 24px;
	}

	/* Attribute Spec List */
	.dnf-cat-spec-list {
		display: flex;
		flex-direction: column;
	}
	.dnf-cat-spec-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 12px 0;
		border-bottom: 1px dashed var(--dnf-border, #e2e8f0);
		font-size: 13.5px;
	}
	.dnf-cat-spec-row:last-child {
		border-bottom: none;
	}
	.dnf-cat-spec-label {
		color: var(--dnf-text-secondary, #64748b);
		font-weight: 500;
	}
	.dnf-cat-spec-val {
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
		text-align: right;
	}

	/* Description Surface */
	.dnf-cat-desc-surface {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 16px;
		font-size: 13.5px;
		line-height: 1.6;
		color: var(--dnf-text-primary, #0f172a);
	}

	/* Assigned Fleet Mini Table */
	.dnf-assigned-table-wrap {
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		overflow: hidden;
	}

	/* Print Styles */
	@media print {
		#adminmenumain,
		#wpadminbar,
		#wpfooter,
		.dnf-cat-inspector-nav,
		.dev-nahian-sidebar {
			display: none !important;
		}
		.dev-nahian-content,
		.dnf-categories-scope,
		.dnf-cat-inspector-wrap {
			margin: 0 !important;
			padding: 0 !important;
			box-shadow: none !important;
			border: none !important;
		}
		.dnf-cat-dossier-card {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
		}
	}
</style>

<div class="dnf-cat-inspector-wrap">

	<!-- Top Toolbar -->
	<div class="dnf-cat-inspector-nav">
		<div class="dnf-cat-inspector-nav-left">
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back to Classifications', 'dev-nahian-car' ); ?></span>
			</a>
			<span class="dnf-count-chip"><?php esc_html_e( 'Category Inspector', 'dev-nahian-car' ); ?></span>
			<h2 style="margin:0; font-size:18px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
				<?php echo esc_html( (string) $cat['name'] ); ?>
			</h2>
		</div>

		<div style="display:flex; gap:8px;">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn dnf-btn-primary">
				<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Category', 'dev-nahian-car' ); ?></span>
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
	<div class="dnf-cat-dossier-card">
		<!-- Executive Banner Header -->
		<div class="dnf-cat-dossier-header">
			<div>
				<div class="dnf-cat-dossier-code">
					<?php
					printf(
						/* translators: %s: slug identifier */
						esc_html__( 'TAXONOMY SLUG: %s', 'dev-nahian-car' ),
						esc_html( (string) $cat['slug'] )
					);
					?>
				</div>
				<h2 class="dnf-cat-dossier-heading">
					<?php echo esc_html( (string) $cat['name'] ); ?>
				</h2>
			</div>
			<div>
				<span class="dnf-status-pill <?php echo esc_attr( $cat_status_slug ); ?>">
					&bull; <?php echo esc_html( ucfirst( $cat_status_slug ) ); ?>
				</span>
			</div>
		</div>

		<!-- Attributes Body -->
		<div class="dnf-cat-dossier-body">
			<!-- Specifications -->
			<div class="dnf-cat-spec-list">
				<div class="dnf-cat-spec-row">
					<span class="dnf-cat-spec-label"><?php esc_html_e( 'Classification Name', 'dev-nahian-car' ); ?></span>
					<span class="dnf-cat-spec-val"><?php echo esc_html( (string) $cat['name'] ); ?></span>
				</div>
				<div class="dnf-cat-spec-row">
					<span class="dnf-cat-spec-label"><?php esc_html_e( 'System Slug Identifier', 'dev-nahian-car' ); ?></span>
					<span class="dnf-cat-spec-val"><code class="dnf-code"><?php echo esc_html( (string) $cat['slug'] ); ?></code></span>
				</div>
				<div class="dnf-cat-spec-row">
					<span class="dnf-cat-spec-label"><?php esc_html_e( 'Standard Daily Baseline Rate', 'dev-nahian-car' ); ?></span>
					<span class="dnf-cat-spec-val" style="color:var(--dnf-accent-teal, #0d9488); font-size:16px; font-feature-settings:'tnum';">
						<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $cat['base_price'] ?? 0.00 ), 2 ) . ' / ' . __( 'day', 'dev-nahian-car' ) ); ?>
					</span>
				</div>
				<div class="dnf-cat-spec-row">
					<span class="dnf-cat-spec-label"><?php esc_html_e( 'Assigned Fleet Inventory', 'dev-nahian-car' ); ?></span>
					<span class="dnf-cat-spec-val" style="font-feature-settings:'tnum';">
						<?php
						printf(
							/* translators: %s: number of registered units */
							esc_html__( '%s Units Registered', 'dev-nahian-car' ),
							esc_html( (string) number_format_i18n( $car_count ) )
						);
						?>
					</span>
				</div>
			</div>

			<!-- Editorial Overview Description -->
			<div>
				<h4 style="margin:0 0 10px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--dnf-text-secondary, #475569);">
					<?php esc_html_e( 'Tier Description & Features', 'dev-nahian-car' ); ?>
				</h4>
				<div class="dnf-cat-desc-surface">
					<?php
					if ( ! empty( $cat['description'] ) ) {
						echo nl2br( esc_html( (string) $cat['description'] ) );
					} else {
						echo '<em style="color:var(--dnf-text-tertiary, #94a3b8);">' . esc_html__( 'No description registered for this classification tier.', 'dev-nahian-car' ) . '</em>';
					}
					?>
				</div>
			</div>

			<!-- Allocated Vehicle Manifest -->
			<div>
				<h4 style="margin:0 0 12px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--dnf-text-secondary, #475569);">
					<?php esc_html_e( 'Allocated Vehicle Inventory in this Category', 'dev-nahian-car' ); ?>
				</h4>

				<?php if ( ! empty( $assigned_cars ) ) : ?>
					<div class="dnf-assigned-table-wrap">
						<table class="wp-list-table widefat fixed striped" style="width:100%; border:none; margin:0;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Model', 'dev-nahian-car' ); ?></th>
									<th style="width:140px;"><?php esc_html_e( 'License Plate', 'dev-nahian-car' ); ?></th>
									<th style="width:130px;"><?php esc_html_e( 'Daily Rate', 'dev-nahian-car' ); ?></th>
									<th style="width:130px;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
									<th style="text-align:right; width:80px;"><?php esc_html_e( 'Inspect', 'dev-nahian-car' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $assigned_cars as $ac ) :
									$car_dossier_url = add_query_arg(
										array(
											'page'   => 'dev-nahian-fleet-manager',
											'tab'    => 'cars',
											'action' => 'view',
											'id'     => absint( $ac['id'] ),
										),
										admin_url( 'admin.php' )
									);
									$car_status = sanitize_key( (string) ( $ac['status'] ?? 'ready' ) );
									?>
									<tr>
										<td>
											<strong><?php echo esc_html( (string) $ac['name'] ); ?></strong>
											<?php if ( ! empty( $ac['brand'] ) ) : ?>
												<span style="font-size:11.5px; color:var(--dnf-text-secondary, #64748b);"> (<?php echo esc_html( (string) $ac['brand'] ); ?>)</span>
											<?php endif; ?>
										</td>
										<td>
											<code class="dnf-code"><?php echo esc_html( ! empty( $ac['plate'] ) ? (string) $ac['plate'] : '—' ); ?></code>
										</td>
										<td>
											<strong style="color:var(--dnf-text-primary, #0f172a); font-feature-settings:'tnum';">
												<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $ac['price'] ?? 0 ), 2 ) ); ?>
											</strong>
										</td>
										<td>
											<span class="dnf-car-badge <?php echo esc_attr( $car_status ); ?>">
												&bull; <?php echo esc_html( ucfirst( $car_status ) ); ?>
											</span>
										</td>
										<td style="text-align:right;">
											<a href="<?php echo esc_url( $car_dossier_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'View Vehicle Dossier', 'dev-nahian-car' ); ?>">
												<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="dnf-cat-desc-surface" style="text-align:center; color:var(--dnf-text-tertiary, #94a3b8); padding:24px;">
						<?php esc_html_e( 'No active fleet vehicles currently assigned to this category.', 'dev-nahian-car' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

</div>
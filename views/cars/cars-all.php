<?php
/**
 * Cars Module: Fleet Registry List View (`cars-all.php`)
 * Displays tabulated fleet inventory with live filters, telematics, and action controls.
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
$table_cars       = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$table_categories = isset( $table_categories ) ? $table_categories : $wpdb->prefix . 'dev_nahian_categories';
$base             = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'cars',
	),
	admin_url( 'admin.php' )
);
$currency         = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );
$current_subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';

// -----------------------------------------------------------------------------
// 1. Fetch Fleet Cars (Respecting Subtab Filter via Single Prepared Query)
// -----------------------------------------------------------------------------
if ( 'ready' === $current_subtab ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$cars = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_cars} WHERE status = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'ready'
		),
		ARRAY_A
	);
} elseif ( 'rented' === $current_subtab ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$cars = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_cars} WHERE status IN (%s, %s) ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'rented',
			'dispatched'
		),
		ARRAY_A
	);
} elseif ( 'maintenance' === $current_subtab ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$cars = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_cars} WHERE status IN (%s, %s, %s) ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'maintenance',
			'in_workshop',
			'service'
		),
		ARRAY_A
	);
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$cars = $wpdb->get_results( "SELECT * FROM {$table_cars} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$cars_count = is_array( $cars ) ? count( $cars ) : 0;

// Fetch human-readable category name map for quick rendering.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$category_map = $wpdb->get_results( "SELECT slug, name FROM {$table_categories}", OBJECT_K );
?>

<style>
	/* Scoped Cars List Table Card */
	.dnf-cars-table-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		overflow: hidden;
	}
	.dnf-cars-meta-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px 20px;
		background: #ffffff;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-cars-counter {
		font-size: 13px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-cars-counter span {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-cars-table-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.dnf-cars-table-wrap table.dataTable.no-footer {
		border-bottom: none !important;
		margin: 0 !important;
	}

	/* Daily Rate Cell */
	.dnf-rate-text {
		font-size: 13.5px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		font-feature-settings: "tnum";
	}

	/* Action Buttons */
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
	.dnf-table-empty {
		text-align: center;
		padding: 36px 16px !important;
		color: var(--dnf-text-tertiary, #94a3b8);
		font-size: 13px;
	}
</style>

<div class="dnf-cars-table-card">
	<div class="dnf-cars-meta-bar">
		<div class="dnf-cars-counter">
			<?php
			printf(
				/* translators: %s: number of registered chassis */
				esc_html__( 'Active Stable: %s units', 'dev-nahian-car' ),
				'<span>' . esc_html( (string) number_format_i18n( $cars_count ) ) . '</span>'
			);
			?>
		</div>
		<div class="dnf-badge-row">
			<span class="dnf-count-chip">
				<?php echo esc_html( ucfirst( $current_subtab ) ); ?>
			</span>
		</div>
	</div>

	<div class="dnf-cars-table-wrap">
		<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 120px;"><?php esc_html_e( 'Plate', 'dev-nahian-car' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Classification', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Daily Tariff', 'dev-nahian-car' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Fleet Status', 'dev-nahian-car' ); ?></th>
					<th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $cars ) ) : ?>
					<?php
					foreach ( $cars as $c ) :
						$car_id     = absint( $c['id'] );
						$status_key = sanitize_key( (string) ( $c['status'] ?? 'ready' ) );

						$view_url = add_query_arg(
							array(
								'action' => 'view',
								'id'     => $car_id,
							),
							$base
						);
						$edit_url = add_query_arg(
							array(
								'action' => 'edit',
								'id'     => $car_id,
								'subtab' => 'add',
							),
							$base
						);
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'delete',
									'id'     => $car_id,
								),
								$base
							),
							'dev_nahian_delete_car_' . $car_id
						);

						$status_label = ucfirst( $status_key );
						if ( 'ready' === $status_key ) {
							$status_label = __( 'Ready', 'dev-nahian-car' );
						} elseif ( 'dispatched' === $status_key || 'rented' === $status_key ) {
							$status_label = __( 'Dispatched', 'dev-nahian-car' );
						} elseif ( 'service' === $status_key || 'maintenance' === $status_key || 'in_workshop' === $status_key ) {
							$status_label = __( 'In Service', 'dev-nahian-car' );
						}

						// Resolve human-readable category name.
						$cat_slug = sanitize_key( (string) ( $c['category'] ?? '' ) );
						$cat_name = isset( $category_map[ $cat_slug ] )
							? $category_map[ $cat_slug ]->name
							: ucwords( str_replace( array( '-', '_' ), ' ', $cat_slug ) );
						?>
						<tr>
							<!-- License Plate -->
							<td>
								<code class="dnf-code"><?php echo esc_html( ! empty( $c['plate'] ) ? (string) $c['plate'] : '—' ); ?></code>
							</td>

							<!-- Category Classification -->
							<td>
								<span style="font-size: 13px; font-weight: 600; color: var(--dnf-text-secondary, #475569);">
									<?php echo esc_html( $cat_name ); ?>
								</span>
							</td>

							<!-- Daily Tariff Rate -->
							<td>
								<span class="dnf-rate-text">
									<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) ( $c['price'] ?? 0 ), 2 ) ); ?>
								</span>
								<span style="font-size: 11px; color: var(--dnf-text-tertiary, #94a3b8); display: block;">/ <?php esc_html_e( 'day', 'dev-nahian-car' ); ?></span>
							</td>

							<!-- Fleet Status Badge -->
							<td>
								<span class="dnf-car-badge <?php echo esc_attr( $status_key ); ?>">
									&bull; <?php echo esc_html( $status_label ); ?>
								</span>
							</td>

							<!-- Actions -->
							<td style="text-align: right;">
								<div class="dnf-actions-group">
									<a href="<?php echo esc_url( $view_url ); ?>" class="dnf-btn-icon view" title="<?php esc_attr_e( 'View Vehicle Dossier', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'view' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="dnf-btn-icon edit" title="<?php esc_attr_e( 'Edit Chassis Specifications', 'dev-nahian-car' ); ?>">
										<?php echo dev_nahian_get_icon( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
									<a href="<?php echo esc_url( $delete_url ); ?>" class="dnf-btn-icon delete" title="<?php esc_attr_e( 'Retire Chassis Asset', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to permanently retire this chassis from active inventory?', 'dev-nahian-car' ); ?>');">
										<?php echo dev_nahian_get_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" class="dnf-table-empty">
							<?php esc_html_e( 'No vehicle assets registered in fleet inventory matching the filter.', 'dev-nahian-car' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
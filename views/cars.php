<?php
/**
 * Admin View Tab: Fleet Cars Registry & Workstation (Master Router)
 * Pure orchestrator file containing request routing, permission guards, and template loading.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dev-nahian-car' ), 403 );
}

global $wpdb;
$table_cars       = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$table_categories = isset( $table_categories ) ? $table_categories : $wpdb->prefix . 'dev_nahian_categories';

$base     = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'cars',
	),
	admin_url( 'admin.php' )
);
$action   = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$currency = (string) get_option( 'dev_nahian_currency', 'USD' );

$cars_dir = plugin_dir_path( __FILE__ ) . 'cars/';

// -----------------------------------------------------------------------------
// 1. Process Actions (Delete / POST Save) BEFORE Any HTML Output
// -----------------------------------------------------------------------------
if ( 'delete' === $action && $id > 0 ) {
	$delete_file = $cars_dir . 'cars-delete.php';
	if ( file_exists( $delete_file ) ) {
		require_once $delete_file;
	}
}

if ( isset( $_POST['dev_nahian_save_car_submit'] ) ) {
	$add_edit_file = $cars_dir . 'cars-add-edit.php';
	if ( file_exists( $add_edit_file ) ) {
		require_once $add_edit_file;
	}
}

// -----------------------------------------------------------------------------
// 2. Enqueue Media Scripts & Conditional Global Datasets (Editor Workstation)
// -----------------------------------------------------------------------------
$is_editor_view = ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) );

if ( $is_editor_view ) {
	wp_enqueue_media();

	$global_locations = get_option( 'dev_nahian_car_extras_locations', array() );
	$global_specs     = get_option( 'dev_nahian_car_extras_specs', array() );
	$global_amenities = get_option( 'dev_nahian_car_extras_amenities', array() );
	$global_insurance = get_option( 'dev_nahian_car_extras_insurance', array() );
	$global_borders   = get_option( 'dev_nahian_car_extras_border', array() );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$available_cats = $wpdb->get_results( "SELECT slug, name FROM {$table_categories} ORDER BY name ASC", ARRAY_A );
}
?>

<style>
	/* Scoped Cars Workstation Design System */
	.dnf-cars-scope {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Executive Header Panel */
	.dnf-cars-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--dnf-bg-card, #ffffff);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 20px 24px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		flex-wrap: wrap;
		gap: 16px;
	}
	.dnf-cars-meta {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-badge-row {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	.dnf-cars-title {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
	}

	/* Two-Column Editor Layout (Main Specs + Sidebar Telematics) */
	.dnf-editor-layout {
		display: grid;
		grid-template-columns: 1fr 360px;
		gap: 24px;
		align-items: start;
	}
	@media (max-width: 1080px) {
		.dnf-editor-layout {
			grid-template-columns: 1fr;
		}
	}

	/* Modular Form Cards */
	.dnf-card-module {
		background: var(--dnf-bg-card, #ffffff);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 24px;
		margin-bottom: 24px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
	}
	.dnf-card-module:last-child {
		margin-bottom: 0;
	}
	.dnf-module-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 20px;
		padding-bottom: 12px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-module-title {
		font-size: 13.5px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-primary, #0f172a);
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-module-title svg {
		color: var(--dnf-accent-teal, #0d9488);
	}

	/* Fleet Status Badges */
	.dnf-car-badge {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		font-size: 11px;
		font-weight: 700;
		padding: 3px 10px;
		border-radius: var(--dnf-radius-pill, 9999px);
		text-transform: uppercase;
		letter-spacing: 0.04em;
		line-height: 1.2;
	}
	.dnf-car-badge.ready {
		background: #f0fdfa;
		color: var(--dnf-accent-teal, #0d9488);
		border: 1px solid #ccfbf1;
	}
	.dnf-car-badge.rented,
	.dnf-car-badge.dispatched {
		background: #eff6ff;
		color: #2563eb;
		border: 1px solid #dbeafe;
	}
	.dnf-car-badge.maintenance,
	.dnf-car-badge.service {
		background: #fffbeb;
		color: #d97706;
		border: 1px solid #fef3c7;
	}
	.dnf-car-badge.retired {
		background: var(--dnf-danger-bg, #fef2f2);
		color: var(--dnf-danger, #dc2626);
		border: 1px solid var(--dnf-danger-border, #fecaca);
	}

	/* Notice Overrides */
	.dnf-cars-scope .notice {
		margin: 0 !important;
		border-radius: var(--dnf-radius-md, 8px);
		border-left-width: 4px !important;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
	}
</style>

<div class="dnf-cars-scope">

	<!-- Executive Header Panel -->
	<div class="dnf-cars-header">
		<div class="dnf-cars-meta">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'Fleet Telematics Active', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext"><?php esc_html_e( 'Vehicle Chassis Registry', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-cars-title"><?php esc_html_e( 'Fleet Cars & Asset Registry', 'dev-nahian-car' ); ?></h2>
		</div>

		<div class="dnf-hud-controls">
			<?php if ( ! $is_editor_view ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'subtab' => 'add' ), $base ) ); ?>" class="dnf-btn dnf-btn-primary">
					<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Register New Vehicle', 'dev-nahian-car' ); ?></span>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
					<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Return to Fleet Stable', 'dev-nahian-car' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php
	// Contextual Feedback Messages
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Vehicle asset registered successfully into the fleet inventory.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Vehicle telematics and data record updated successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Notice:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Vehicle asset retired from active service.', 'dev-nahian-car' ) . '</p></div>';
	}

	// 1. Subnav Filter Header Tabs
	$tabs_file = $cars_dir . 'cars-tabs.php';
	if ( file_exists( $tabs_file ) ) {
		include $tabs_file;
	}

	// 2. Active View Router
	if ( $is_editor_view ) {
		$add_edit_file = $cars_dir . 'cars-add-edit.php';
		if ( file_exists( $add_edit_file ) ) {
			include $add_edit_file;
		}
	} elseif ( 'view' === $action && $id > 0 ) {
		$view_file = $cars_dir . 'cars-view.php';
		if ( file_exists( $view_file ) ) {
			include $view_file;
		}
	} else {
		$all_file = $cars_dir . 'cars-all.php';
		if ( file_exists( $all_file ) ) {
			include $all_file;
		}
	}
	?>

</div>
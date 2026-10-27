<?php
/**
 * Admin View Tab: Vehicle Inspections & Condition Ledger (Master Router)
 * Pure orchestrator file containing request routing, permission guards, and template loading.
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
$table_insp     = isset( $table_insp ) ? $table_insp : $wpdb->prefix . 'dev_nahian_inspections';
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$table_cars     = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';

$base = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'inspections',
	),
	admin_url( 'admin.php' )
);

$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

$insp_dir = plugin_dir_path( __FILE__ ) . 'inspections/';

// -----------------------------------------------------------------------------
// 1. Process Actions (Delete / Form POST) BEFORE Any HTML Output
// -----------------------------------------------------------------------------
if ( 'delete' === $action && $id > 0 ) {
	$delete_file = $insp_dir . 'inspection-delete.php';
	if ( file_exists( $delete_file ) ) {
		require_once $delete_file;
	}
}

if ( isset( $_POST['dev_nahian_add_insp_submit'] ) || ( isset( $_POST['dev_nahian_edit_insp_submit'] ) && $id > 0 ) ) {
	$add_edit_file = $insp_dir . 'inspection-add-edit.php';
	if ( file_exists( $add_edit_file ) ) {
		require_once $add_edit_file;
	}
}

// Fetch active bookings for dropdown selection.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$active_bookings = $wpdb->get_results( "SELECT booking_code, car_name, customer_name FROM {$table_bookings} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
?>

<style>
	/* Scoped Inspection Module Styles & Scrollbar Prevention */
	.dnf-insp-scope,
	.dnf-insp-scope * {
		box-sizing: border-box !important;
	}

	.dnf-insp-scope {
		display: flex;
		flex-direction: column;
		gap: 20px;
		width: 100%;
		max-width: 100%;
		overflow-x: clip;
	}

	/* Top Executive Header */
	.dnf-insp-header {
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
		width: 100%;
	}
	.dnf-insp-title-area {
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
	.dnf-insp-heading {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
	}

	/* Subnavigation / Toolbar */
	.dnf-insp-toolbar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-insp-toolbar-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Inspection Status Pills */
	.dnf-status-pill {
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
	.dnf-status-pill.checkout {
		background: #eff6ff;
		color: #2563eb;
		border: 1px solid #dbeafe;
	}
	.dnf-status-pill.checkin {
		background: #f0fdfa;
		color: var(--dnf-accent-teal, #0d9488);
		border: 1px solid #ccfbf1;
	}
	.dnf-status-pill.spot_check {
		background: #fffbeb;
		color: #d97706;
		border: 1px solid #fef3c7;
	}

	/* Notice Overrides */
	.dnf-insp-scope .notice {
		margin: 0 !important;
		border-radius: var(--dnf-radius-md, 8px);
		border-left-width: 4px !important;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
	}
</style>

<div class="dnf-insp-scope">

	<!-- Executive Header Panel -->
	<div class="dnf-insp-header">
		<div class="dnf-insp-title-area">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'Condition Telematics Active', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext"><?php esc_html_e( 'Pre/Post-Trip Audits, Fuel/Odo Logs & Damage Tracking', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-insp-heading"><?php esc_html_e( 'Vehicle Inspections & Condition Ledger', 'dev-nahian-car' ); ?></h2>
		</div>

		<div class="dnf-hud-controls">
			<?php if ( 'add' !== $subtab ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'subtab' => 'add' ), $base ) ); ?>" class="dnf-btn dnf-btn-primary">
					<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Log Vehicle Inspection', 'dev-nahian-car' ); ?></span>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
					<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Back to Inspection Log', 'dev-nahian-car' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php
	// Contextual Feedback Messages
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Vehicle condition audit and inspection report saved successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Vehicle inspection report updated successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Notice:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Inspection record removed from condition ledger.', 'dev-nahian-car' ) . '</p></div>';
	}

	// 1. Subnav Header Bar
	$tabs_file = $insp_dir . 'inspection-tabs.php';
	if ( file_exists( $tabs_file ) ) {
		include $tabs_file;
	}

	// 2. Active View Routing
	if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
		$add_edit_file = $insp_dir . 'inspection-add-edit.php';
		if ( file_exists( $add_edit_file ) ) {
			include $add_edit_file;
		}
	} elseif ( 'view' === $action && $id > 0 ) {
		$view_file = $insp_dir . 'inspection-view.php';
		if ( file_exists( $view_file ) ) {
			include $view_file;
		}
	} else {
		$all_file = $insp_dir . 'inspection-all.php';
		if ( file_exists( $all_file ) ) {
			include $all_file;
		}
	}
	?>

</div>
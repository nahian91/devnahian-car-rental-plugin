<?php
/**
 * Admin View Tab: Promo Codes & Yield Rules (Master Router)
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
$table_promos = isset( $table_promos ) ? $table_promos : $wpdb->prefix . 'dev_nahian_promos';
$currency     = (string) get_option( 'dev_nahian_currency', 'USD' );

$base = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'promos',
	),
	admin_url( 'admin.php' )
);

$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

$promos_dir = plugin_dir_path( __FILE__ ) . 'promos/';

// -----------------------------------------------------------------------------
// 1. Process Actions (Delete / Form POST) BEFORE Any HTML Output
// -----------------------------------------------------------------------------
if ( 'delete' === $action && $id > 0 ) {
	$delete_file = $promos_dir . 'promo-delete.php';
	if ( file_exists( $delete_file ) ) {
		require_once $delete_file;
	}
}

if ( isset( $_POST['dev_nahian_add_promo_submit'] ) || ( isset( $_POST['dev_nahian_edit_promo_submit'] ) && $id > 0 ) ) {
	$add_edit_file = $promos_dir . 'promo-add-edit.php';
	if ( file_exists( $add_edit_file ) ) {
		require_once $add_edit_file;
	}
}
?>

<style>
	/* Scoped Promo Module Styles & Scrollbar Prevention */
	.dnf-promo-scope,
	.dnf-promo-scope * {
		box-sizing: border-box !important;
	}

	.dnf-promo-scope {
		display: flex;
		flex-direction: column;
		gap: 20px;
		width: 100%;
		max-width: 100%;
		overflow-x: clip;
	}

	/* Top Executive Header */
	.dnf-promo-header {
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
	.dnf-promo-title-area {
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
	.dnf-promo-heading {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
	}

	/* Subnavigation / Toolbar */
	.dnf-promo-toolbar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-promo-toolbar-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Status Pills */
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
	.dnf-status-pill.active {
		background: #f0fdfa;
		color: var(--dnf-accent-teal, #0d9488);
		border: 1px solid #ccfbf1;
	}
	.dnf-status-pill.expired {
		background: #fffbeb;
		color: #d97706;
		border: 1px solid #fef3c7;
	}
	.dnf-status-pill.disabled {
		background: var(--dnf-danger-bg, #fef2f2);
		color: var(--dnf-danger, #dc2626);
		border: 1px solid var(--dnf-danger-border, #fecaca);
	}

	/* Notice Overrides */
	.dnf-promo-scope .notice {
		margin: 0 !important;
		border-radius: var(--dnf-radius-md, 8px);
		border-left-width: 4px !important;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
	}
</style>

<div class="dnf-promo-scope">

	<!-- Executive Header Panel -->
	<div class="dnf-promo-header">
		<div class="dnf-promo-title-area">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'Yield & Coupon Engine Active', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext"><?php esc_html_e( 'Discount Vouchers, Seasonal Yields & Corporate Pricing', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-promo-heading"><?php esc_html_e( 'Promo Codes & Yield Rules', 'dev-nahian-car' ); ?></h2>
		</div>

		<div class="dnf-hud-controls">
			<?php if ( 'add' !== $subtab ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'subtab' => 'add' ), $base ) ); ?>" class="dnf-btn dnf-btn-primary">
					<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Create Promo Code', 'dev-nahian-car' ); ?></span>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
					<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Back to Promotions Ledger', 'dev-nahian-car' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php
	// Contextual Feedback Messages
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Promo code and yield discount rules configured successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Promo code terms and discount parameters updated successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Notice:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Promo code deleted from promotional registry.', 'dev-nahian-car' ) . '</p></div>';
	}

	// 1. Subnav Header Bar
	$tabs_file = $promos_dir . 'promo-tabs.php';
	if ( file_exists( $tabs_file ) ) {
		include $tabs_file;
	}

	// 2. Active View Routing
	if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
		$add_edit_file = $promos_dir . 'promo-add-edit.php';
		if ( file_exists( $add_edit_file ) ) {
			include $add_edit_file;
		}
	} else {
		$all_file = $promos_dir . 'promo-all.php';
		if ( file_exists( $all_file ) ) {
			include $all_file;
		}
	}
	?>

</div>
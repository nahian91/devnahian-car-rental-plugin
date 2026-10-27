<?php
/**
 * Admin View Tab: Customer Directory & KYC Dossier (Master Router)
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
$table_customers = isset( $table_customers ) ? $table_customers : $wpdb->prefix . 'dev_nahian_customers';
$table_bookings  = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';

$base = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'customers',
	),
	admin_url( 'admin.php' )
);

$action   = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$currency = (string) get_option( 'dev_nahian_currency', 'USD' );

$customers_dir = plugin_dir_path( __FILE__ ) . 'customers/';

// -----------------------------------------------------------------------------
// 1. Process Actions (Delete / Form POST) BEFORE Any HTML Output
// -----------------------------------------------------------------------------
if ( 'delete' === $action && $id > 0 ) {
	$delete_file = $customers_dir . 'customer-delete.php';
	if ( file_exists( $delete_file ) ) {
		require_once $delete_file;
	}
}

if ( isset( $_POST['dev_nahian_add_customer_submit'] ) || ( isset( $_POST['dev_nahian_edit_customer_submit'] ) && $id > 0 ) ) {
	$add_edit_file = $customers_dir . 'customer-add-edit.php';
	if ( file_exists( $add_edit_file ) ) {
		require_once $add_edit_file;
	}
}

// -----------------------------------------------------------------------------
// 2. Enqueue Media Scripts for KYC Document and License Uploaders
// -----------------------------------------------------------------------------
if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
	wp_enqueue_media();
}
?>

<style>
	/* Scoped Customers Module Styles & Scrollbar Prevention */
	.dnf-customers-scope,
	.dnf-customers-scope * {
		box-sizing: border-box !important;
	}

	.dnf-customers-scope {
		display: flex;
		flex-direction: column;
		gap: 20px;
		width: 100%;
		max-width: 100%;
		overflow-x: clip;
	}

	/* Top Executive Header */
	.dnf-cust-header {
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
	.dnf-cust-title-area {
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
	.dnf-cust-heading {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
	}

	/* Subnavigation / Toolbar */
	.dnf-cust-toolbar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-cust-toolbar-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Status Pills (KYC Verification Statuses) */
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
	.dnf-status-pill.verified {
		background: #f0fdfa;
		color: var(--dnf-accent-teal, #0d9488);
		border: 1px solid #ccfbf1;
	}
	.dnf-status-pill.pending {
		background: #fffbeb;
		color: #d97706;
		border: 1px solid #fef3c7;
	}
	.dnf-status-pill.suspended {
		background: var(--dnf-danger-bg, #fef2f2);
		color: var(--dnf-danger, #dc2626);
		border: 1px solid var(--dnf-danger-border, #fecaca);
	}

	/* Tier Badges */
	.dnf-tier-badge {
		display: inline-flex;
		align-items: center;
		font-size: 10px;
		font-weight: 800;
		text-transform: uppercase;
		padding: 2px 7px;
		border-radius: var(--dnf-radius-sm, 6px);
		letter-spacing: 0.05em;
		line-height: 1.2;
	}
	.dnf-tier-badge.sovereign {
		background: var(--dnf-accent-gold-bg, #fefce8);
		color: var(--dnf-accent-gold, #b88e1e);
		border: 1px solid #fef08a;
	}
	.dnf-tier-badge.vip {
		background: #eff6ff;
		color: #2563eb;
		border: 1px solid #dbeafe;
	}
	.dnf-tier-badge.standard {
		background: #f1f5f9;
		color: var(--dnf-text-secondary, #475569);
		border: 1px solid var(--dnf-border, #e2e8f0);
	}

	/* Notice Overrides */
	.dnf-customers-scope .notice {
		margin: 0 !important;
		border-radius: var(--dnf-radius-md, 8px);
		border-left-width: 4px !important;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
	}
</style>

<div class="dnf-customers-scope">

	<!-- Executive Header Panel -->
	<div class="dnf-cust-header">
		<div class="dnf-cust-title-area">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'KYC Verification Active', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext"><?php esc_html_e( 'Client Directory & License Credentials', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-cust-heading"><?php esc_html_e( 'Customer Directory & KYC Dossiers', 'dev-nahian-car' ); ?></h2>
		</div>

		<div class="dnf-hud-controls">
			<?php if ( 'add' !== $subtab ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'subtab' => 'add' ), $base ) ); ?>" class="dnf-btn dnf-btn-primary">
					<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Register Customer', 'dev-nahian-car' ); ?></span>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
					<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Back to Directory', 'dev-nahian-car' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php
	// Contextual Feedback Messages
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Customer profile and KYC credentials registered successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Customer dossier updated successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Notice:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Customer record permanently removed from directory.', 'dev-nahian-car' ) . '</p></div>';
	}

	// 1. Subnav Header Bar
	$tabs_file = $customers_dir . 'customer-tabs.php';
	if ( file_exists( $tabs_file ) ) {
		include $tabs_file;
	}

	// 2. Active View Routing
	if ( 'view' === $action && $id > 0 ) {
		$view_file = $customers_dir . 'customer-view.php';
		if ( file_exists( $view_file ) ) {
			include $view_file;
		}
	} elseif ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
		$add_edit_file = $customers_dir . 'customer-add-edit.php';
		if ( file_exists( $add_edit_file ) ) {
			include $add_edit_file;
		}
	} else {
		$all_file = $customers_dir . 'customer-all.php';
		if ( file_exists( $all_file ) ) {
			include $all_file;
		}
	}
	?>

</div>
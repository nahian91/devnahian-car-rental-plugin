<?php
/**
 * Admin View Tab: Income & Expense Financial Ledger (Master Router)
 * Pure orchestrator file containing request routing, permission guards, KPI analytics, and view includes.
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
$table_finances = isset( $table_finances ) ? $table_finances : $wpdb->prefix . 'dev_nahian_finances';
$currency       = (string) get_option( 'dev_nahian_currency', 'USD' );

$base = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'finances',
	),
	admin_url( 'admin.php' )
);

$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

$fin_dir = plugin_dir_path( __FILE__ ) . 'finances/';

// -----------------------------------------------------------------------------
// 1. Process Actions (Delete / Form POST) BEFORE Any HTML Output
// -----------------------------------------------------------------------------
if ( 'delete' === $action && $id > 0 ) {
	$delete_file = $fin_dir . 'finance-delete.php';
	if ( file_exists( $delete_file ) ) {
		require_once $delete_file;
	}
}

if ( isset( $_POST['dev_nahian_add_txn_submit'] ) || ( isset( $_POST['dev_nahian_edit_txn_submit'] ) && $id > 0 ) ) {
	$add_edit_file = $fin_dir . 'finance-add-edit.php';
	if ( file_exists( $add_edit_file ) ) {
		require_once $add_edit_file;
	}
}

// -----------------------------------------------------------------------------
// 2. High-Performance KPI Calculations (Single Query)
// -----------------------------------------------------------------------------
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$kpis = $wpdb->get_row(
	"SELECT 
		COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
		COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expenses,
		COUNT(id) AS total_txns
	FROM {$table_finances}",
	ARRAY_A
);

$total_income   = (float) ( $kpis['total_income'] ?? 0.0 );
$total_expenses = (float) ( $kpis['total_expenses'] ?? 0.0 );
$net_profit     = $total_income - $total_expenses;
$total_txns     = (int) ( $kpis['total_txns'] ?? 0 );
?>

<style>
	/* Scoped Financial Module Styles */
	.dnf-fin-scope {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Top Executive Header */
	.dnf-fin-header {
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
	.dnf-fin-title-area {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-fin-heading {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
	}

	/* KPI Analytics Summary Grid */
	.dnf-fin-metric-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 16px;
	}
	@media (max-width: 900px) {
		.dnf-fin-metric-grid {
			grid-template-columns: 1fr;
		}
	}
	.dnf-fin-metric-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 20px 24px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-fin-metric-label {
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: var(--dnf-text-secondary, #64748b);
	}
	.dnf-fin-metric-val {
		font-size: 24px;
		font-weight: 800;
		letter-spacing: -0.02em;
		line-height: 1.1;
		font-feature-settings: 'tnum';
	}
	.dnf-fin-metric-val.income {
		color: #16a34a;
	}
	.dnf-fin-metric-val.expense {
		color: var(--dnf-danger, #dc2626);
	}
	.dnf-fin-metric-val.profit-positive {
		color: var(--dnf-accent-teal, #0d9488);
	}
	.dnf-fin-metric-val.profit-negative {
		color: var(--dnf-danger, #dc2626);
	}

	/* Subnavigation / Toolbar */
	.dnf-fin-toolbar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-fin-toolbar-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Transaction Status Pills */
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
	.dnf-status-pill.income {
		background: #f0fdf4;
		color: #16a34a;
		border: 1px solid #bbf7d0;
	}
	.dnf-status-pill.expense {
		background: var(--dnf-danger-bg, #fef2f2);
		color: var(--dnf-danger, #dc2626);
		border: 1px solid var(--dnf-danger-border, #fecaca);
	}

	/* Notice Overrides */
	.dnf-fin-scope .notice {
		margin: 0 !important;
		border-radius: var(--dnf-radius-md, 8px);
		border-left-width: 4px !important;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
	}
</style>

<div class="dnf-fin-scope">

	<!-- Executive Header Panel -->
	<div class="dnf-fin-header">
		<div class="dnf-fin-title-area">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'Treasury & Cashflow Active', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext"><?php esc_html_e( 'Fleet Revenue, Operational Expenses & Ledger Auditing', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-fin-heading"><?php esc_html_e( 'Income & Expense Financial Ledger', 'dev-nahian-car' ); ?></h2>
		</div>

		<div class="dnf-hud-controls">
			<?php if ( 'add' !== $subtab ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'subtab' => 'add' ), $base ) ); ?>" class="dnf-btn dnf-btn-primary">
					<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Record Transaction', 'dev-nahian-car' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
					<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Back to Treasury Ledger', 'dev-nahian-car' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Top KPI Analytics Grid -->
	<div class="dnf-fin-metric-grid">
		<div class="dnf-fin-metric-card">
			<span class="dnf-fin-metric-label"><?php esc_html_e( 'Gross Revenue / Income', 'dev-nahian-car' ); ?></span>
			<span class="dnf-fin-metric-val income">
				+<?php echo esc_html( $currency . ' ' . number_format_i18n( $total_income, 2 ) ); ?>
			</span>
		</div>
		<div class="dnf-fin-metric-card">
			<span class="dnf-fin-metric-label"><?php esc_html_e( 'Fleet Operational Expenses', 'dev-nahian-car' ); ?></span>
			<span class="dnf-fin-metric-val expense">
				-<?php echo esc_html( $currency . ' ' . number_format_i18n( $total_expenses, 2 ) ); ?>
			</span>
		</div>
		<div class="dnf-fin-metric-card">
			<span class="dnf-fin-metric-label"><?php esc_html_e( 'Net Treasury Balance', 'dev-nahian-car' ); ?></span>
			<span class="dnf-fin-metric-val <?php echo $net_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
				<?php echo $net_profit >= 0 ? '+' : ''; ?><?php echo esc_html( $currency . ' ' . number_format_i18n( $net_profit, 2 ) ); ?>
			</span>
		</div>
	</div>

	<?php
	// Contextual Feedback Notices
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Financial transaction recorded into the ledger successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Financial transaction details updated successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Notice:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'Transaction record removed from the financial ledger.', 'dev-nahian-car' ) . '</p></div>';
	}

	// 1. Subnav Header Bar
	$tabs_file = $fin_dir . 'finance-tabs.php';
	if ( file_exists( $tabs_file ) ) {
		include $tabs_file;
	}

	// 2. Active View Routing
	if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
		$add_edit_file = $fin_dir . 'finance-add-edit.php';
		if ( file_exists( $add_edit_file ) ) {
			include $add_edit_file;
		}
	} elseif ( 'view' === $action && $id > 0 ) {
		$view_file = $fin_dir . 'finance-view.php';
		if ( file_exists( $view_file ) ) {
			include $view_file;
		}
	} else {
		$all_file = $fin_dir . 'finance-all.php';
		if ( file_exists( $all_file ) ) {
			include $all_file;
		}
	}
	?>

</div>
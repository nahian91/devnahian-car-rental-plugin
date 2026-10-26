<?php
/**
 * Admin View Tab: Income & Expense Financial Ledger (Master Router)
 * Pure orchestrator file containing only request routing and include statements.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_finances = $wpdb->prefix . 'dev_nahian_finances';
$currency       = get_option( 'dev_nahian_currency', 'USD' );

$base   = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'finances' ), admin_url( 'admin.php' ) );
$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$subtab = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$notice = '';

// =========================================================================
// ROUTE ACTIONS: DELETE / CREATE / UPDATE
// =========================================================================
if ( 'delete' === $action && $id > 0 ) {
    require_once plugin_dir_path( __FILE__ ) . 'finances/finance-delete.php';
}

if ( isset( $_POST['dev_nahian_add_txn_submit'] ) || ( isset( $_POST['dev_nahian_edit_txn_submit'] ) && $id > 0 ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'finances/finance-add-edit.php';
}

// Dynamic KPI Calculations
$total_income   = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_finances} WHERE type = 'income'" );
$total_expenses = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_finances} WHERE type = 'expense'" );
$net_profit     = $total_income - $total_expenses;
?>

<style>
    .dn-fin-scope {
        --dark: #0f172a;
        --text: #334155;
        --muted: #64748b;
        --bg: #f8fafc;
        --border: #e2e8f0;
        --teal: #0d9488;
        --teal-bg: #f0fdfa;
        --teal-border: #ccfbf1;
        --green: #16a34a;
        --green-bg: #f0fdf4;
        --green-border: #bbf7d0;
        --red: #dc2626;
        --red-bg: #fef2f2;
        --red-border: #fecaca;
    }
    .dn-fin-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dn-fin-bar-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dn-fin-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--dark);
    }
    .dn-fin-chip {
        font-size: 11px;
        font-weight: 700;
        color: var(--muted);
        background: var(--bg);
        border: 1px solid var(--border);
        padding: 3px 10px;
        border-radius: 999px;
    }
    .dn-fin-metric-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    @media (max-width: 900px) {
        .dn-fin-metric-grid { grid-template-columns: 1fr; }
    }
    .dn-fin-metric-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .dn-fin-metric-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        display: block;
        margin-bottom: 6px;
    }
    .dn-fin-metric-val {
        font-size: 24px;
        font-weight: 800;
        color: var(--dark);
        letter-spacing: -0.02em;
        display: block;
        line-height: 1.1;
    }
    .dev-nahian-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
    }
    .dev-nahian-status-pill.income { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
    .dev-nahian-status-pill.expense { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); }
</style>

<div class="dn-fin-scope">

    <?php
    if ( ! empty( $notice ) ) {
        echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Transaction recorded successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Transaction updated successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Transaction deleted.', 'dev-nahian-car' ) . '</p></div>';
    }

    // Include subnav header bar
    include plugin_dir_path( __FILE__ ) . 'finances/finance-tabs.php';

    // Route views
    if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
        include plugin_dir_path( __FILE__ ) . 'finances/finance-add-edit.php';
    } elseif ( 'view' === $action && $id > 0 ) {
        include plugin_dir_path( __FILE__ ) . 'finances/finance-view.php';
    } else {
        include plugin_dir_path( __FILE__ ) . 'finances/finance-all.php';
    }
    ?>

</div>
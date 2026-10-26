<?php
/**
 * Admin View Tab: Promo Codes & Yield Rules (Master Router)
 * Pure orchestrator file containing only request routing and include statements.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_promos = $wpdb->prefix . 'dev_nahian_promos';
$currency     = get_option( 'dev_nahian_currency', 'USD' );

$base   = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'promos' ), admin_url( 'admin.php' ) );
$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$subtab = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$notice = '';

// =========================================================================
// ROUTE ACTIONS: DELETE / CREATE / UPDATE
// =========================================================================
if ( 'delete' === $action && $id > 0 ) {
    require_once plugin_dir_path( __FILE__ ) . 'promos/promo-delete.php';
}

if ( isset( $_POST['dev_nahian_add_promo_submit'] ) || ( isset( $_POST['dev_nahian_edit_promo_submit'] ) && $id > 0 ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'promos/promo-add-edit.php';
}
?>

<style>
    .dn-promo-scope {
        --dark: #0f172a;
        --text: #334155;
        --muted: #64748b;
        --bg: #f8fafc;
        --border: #e2e8f0;
        --teal: #0d9488;
        --teal-bg: #f0fdfa;
        --teal-border: #ccfbf1;
        --blue: #2563eb;
        --blue-bg: #eff6ff;
        --gold: #b88e1e;
        --gold-bg: #fefce8;
    }
    .dn-promo-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dn-promo-bar-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dn-promo-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--dark);
    }
    .dn-promo-chip {
        font-size: 11px;
        font-weight: 700;
        color: var(--muted);
        background: var(--bg);
        border: 1px solid var(--border);
        padding: 3px 10px;
        border-radius: 999px;
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
    .dev-nahian-status-pill.active { background: var(--teal-bg); color: var(--teal); border: 1px solid var(--teal-border); }
    .dev-nahian-status-pill.expired { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
    .dev-nahian-status-pill.disabled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
</style>

<div class="dn-promo-scope">

    <?php
    if ( ! empty( $notice ) ) {
        echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Promo code created successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Promo code updated successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Promo code deleted.', 'dev-nahian-car' ) . '</p></div>';
    }

    // Include subnav header bar
    include plugin_dir_path( __FILE__ ) . 'promos/promo-tabs.php';

    // Route views
    if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
        include plugin_dir_path( __FILE__ ) . 'promos/promo-add-edit.php';
    } else {
        include plugin_dir_path( __FILE__ ) . 'promos/promo-all.php';
    }
    ?>

</div>
<?php
/**
 * Admin View Tab: Fleet Maintenance & Workshop Management (Master Router)
 * Pure orchestrator file containing only request routing and include statements.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_maint = $wpdb->prefix . 'dev_nahian_maintenance';
$table_cars  = $wpdb->prefix . 'dev_nahian_cars';
$currency    = get_option( 'dev_nahian_currency', 'USD' );

$base   = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'maintenance' ), admin_url( 'admin.php' ) );
$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$subtab = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$notice = '';

// =========================================================================
// ROUTE ACTIONS: DELETE / CREATE / UPDATE
// =========================================================================
if ( 'delete' === $action && $id > 0 ) {
    require_once plugin_dir_path( __FILE__ ) . 'maintenance/maint-delete.php';
}

if ( isset( $_POST['dev_nahian_add_maint_submit'] ) || ( isset( $_POST['dev_nahian_edit_maint_submit'] ) && $id > 0 ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'maintenance/maint-add-edit.php';
}

// Fetch available cars for dropdowns
$fleet_cars = $wpdb->get_results( "SELECT id, name, vin FROM {$table_cars} ORDER BY name ASC", ARRAY_A );
?>

<style>
    .dn-maint-scope {
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
    .dn-maint-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dn-maint-bar-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dn-maint-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--dark);
    }
    .dn-maint-chip {
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
    .dev-nahian-status-pill.completed { background: var(--teal-bg); color: var(--teal); border: 1px solid var(--teal-border); }
    .dev-nahian-status-pill.in_workshop { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
    .dev-nahian-status-pill.scheduled { background: var(--blue-bg); color: var(--blue); border: 1px solid #dbeafe; }
    .dev-nahian-status-pill.cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
</style>

<div class="dn-maint-scope">

    <?php
    if ( ! empty( $notice ) ) {
        echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Maintenance service ticket scheduled successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Maintenance record updated successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Maintenance record deleted.', 'dev-nahian-car' ) . '</p></div>';
    }

    // Include subnav header bar
    include plugin_dir_path( __FILE__ ) . 'maintenance/maint-tabs.php';

    // Route views
    if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
        include plugin_dir_path( __FILE__ ) . 'maintenance/maint-add-edit.php';
    } elseif ( 'view' === $action && $id > 0 ) {
        include plugin_dir_path( __FILE__ ) . 'maintenance/maint-view.php';
    } else {
        include plugin_dir_path( __FILE__ ) . 'maintenance/maint-all.php';
    }
    ?>

</div>
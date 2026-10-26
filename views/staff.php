<?php
/**
 * Admin View Tab: Staff & Chauffeurs Directory (Master Router)
 * Pure orchestrator file containing only request routing and include statements.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_staff = $wpdb->prefix . 'dev_nahian_staff';

$base   = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'staff' ), admin_url( 'admin.php' ) );
$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$subtab = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$notice = '';

// =========================================================================
// ROUTE ACTIONS: DELETE / CREATE / UPDATE
// =========================================================================
if ( 'delete' === $action && $id > 0 ) {
    require_once plugin_dir_path( __FILE__ ) . 'staff/staff-delete.php';
}

if ( isset( $_POST['dev_nahian_add_staff_submit'] ) || ( isset( $_POST['dev_nahian_edit_staff_submit'] ) && $id > 0 ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'staff/staff-add-edit.php';
}
?>

<style>
    .dn-staff-scope {
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
    .dn-staff-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dn-staff-bar-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dn-staff-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--dark);
    }
    .dn-staff-chip {
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
    .dev-nahian-status-pill.on_duty { background: var(--blue-bg); color: var(--blue); border: 1px solid #dbeafe; }
    .dev-nahian-status-pill.off_duty { background: #f1f5f9; color: var(--muted); border: 1px solid var(--border); }
    .dev-nahian-status-pill.suspended { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .dev-nahian-role-badge {
        display: inline-flex;
        align-items: center;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 2px 7px;
        border-radius: 4px;
        letter-spacing: 0.05em;
        background: var(--gold-bg);
        color: var(--gold);
        border: 1px solid #fef08a;
    }
</style>

<div class="dn-staff-scope">

    <?php
    if ( ! empty( $notice ) ) {
        echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Staff member registered successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Staff profile updated successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Staff member record deleted.', 'dev-nahian-car' ) . '</p></div>';
    }

    // Include subnav header bar
    include plugin_dir_path( __FILE__ ) . 'staff/staff-tabs.php';

    // Route views
    if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
        include plugin_dir_path( __FILE__ ) . 'staff/staff-add-edit.php';
    } elseif ( 'view' === $action && $id > 0 ) {
        include plugin_dir_path( __FILE__ ) . 'staff/staff-view.php';
    } else {
        include plugin_dir_path( __FILE__ ) . 'staff/staff-all.php';
    }
    ?>

</div>
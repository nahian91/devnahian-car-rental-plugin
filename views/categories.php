<?php
/**
 * Admin View Tab: Categories Management (Master Router)
 * Pure orchestrator file containing only request routing and include statements.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_categories = $wpdb->prefix . 'dev_nahian_categories';
$table_cars       = $wpdb->prefix . 'dev_nahian_cars';

$base     = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'categories' ), admin_url( 'admin.php' ) );
$action   = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$id       = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency = get_option( 'dev_nahian_currency', 'USD' );

// 1. Route Deletion Request
if ( 'delete' === $action && $id > 0 ) {
    require_once plugin_dir_path( __FILE__ ) . 'categories/category-delete.php';
}

// 2. Route Create / Update POST Request
if ( isset( $_POST['dev_nahian_add_category_submit'] ) || ( isset( $_POST['dev_nahian_edit_category_submit'] ) && $id > 0 ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'categories/category-add-edit.php';
}
?>

<style>
    .dev-nahian-cat-scope {
        --dark: #0f172a;
        --text: #334155;
        --muted: #64748b;
        --bg: #f8fafc;
        --border: #e2e8f0;
        --border-subtle: #f1f5f9;
        --teal: #0d9488;
        --teal-bg: #f0fdfa;
        --teal-border: #ccfbf1;
        --blue: #2563eb;
        --blue-bg: #eff6ff;
        --gold: #b88e1e;
        --gold-bg: #fefce8;
    }
    .dev-nahian-cat-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dev-nahian-cat-bar-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dev-nahian-cat-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--dark);
    }
    .dev-nahian-cat-chip {
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
        font-size: 11px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
    }
    .dev-nahian-status-pill.active { background: var(--teal-bg); color: var(--teal); border: 1px solid var(--teal-border); }
    .dev-nahian-status-pill.inactive { background: #f1f5f9; color: var(--muted); border: 1px solid var(--border); }
</style>

<div class="dev-nahian-cat-scope">

    <?php
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Category added successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Category updated successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Category deleted successfully.', 'dev-nahian-car' ) . '</p></div>';
    }

    // 1. Include Subnav Header Bar
    include plugin_dir_path( __FILE__ ) . 'categories/category-tabs.php';

    // 2. Route Active View
    if ( 'view' === $action && $id > 0 ) {
        include plugin_dir_path( __FILE__ ) . 'categories/category-view.php';
    } elseif ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
        include plugin_dir_path( __FILE__ ) . 'categories/category-add-edit.php';
    } else {
        include plugin_dir_path( __FILE__ ) . 'categories/category-all.php';
    }
    ?>

</div>
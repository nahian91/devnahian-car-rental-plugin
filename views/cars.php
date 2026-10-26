<?php
/**
 * Admin View Tab: Fleet Cars Registry & Workstation (Master Router)
 * Pure orchestrator file containing only include statements and request routing.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_cars       = $wpdb->prefix . 'dev_nahian_cars';
$table_categories = $wpdb->prefix . 'dev_nahian_categories';

$base     = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'cars' ), admin_url( 'admin.php' ) );
$action   = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$id       = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency = get_option( 'dev_nahian_currency', 'USD' );

// 1. Route Deletion Request
if ( 'delete' === $action && $id > 0 ) {
    require_once plugin_dir_path( __FILE__ ) . 'cars/cars-delete.php';
}

// 2. Route Save / Update POST Request
if ( isset( $_POST['dev_nahian_save_car_submit'] ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'cars/cars-add-edit.php';
}

// 3. Enqueue media scripts if editing or adding
if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
    wp_enqueue_media();
}

// Global Lookups
$global_locations  = get_option( 'dev_nahian_car_extras_locations', array() );
$global_specs      = get_option( 'dev_nahian_car_extras_specs', array() );
$global_amenities  = get_option( 'dev_nahian_car_extras_amenities', array() );
$global_insurance  = get_option( 'dev_nahian_car_extras_insurance', array() );
$global_borders    = get_option( 'dev_nahian_car_extras_border', array() );
$available_cats    = $wpdb->get_results( "SELECT slug, name FROM {$table_categories} ORDER BY name ASC", ARRAY_A );
?>

<style>
    .dev-nahian-cars-scope {
        --dn-admin-primary: #0f172a;
        --dn-admin-primary-hover: #1e293b;
        --dn-admin-accent: #b88e1e;
        --dn-admin-border: #cbd5e1;
        --dn-admin-border-subtle: #e2e8f0;
        --dn-admin-bg-surface: #ffffff;
        --dn-admin-text-main: #0f172a;
        --dn-admin-text-muted: #64748b;
        --dn-admin-blue: #2563eb;
        --dn-admin-teal: #0d9488;
        --dn-admin-red: #ef4444;
        --dn-admin-radius: 10px;
        --dn-admin-radius-sm: 6px;
        --dn-admin-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 4px 12px -2px rgba(15, 23, 42, 0.02);
        --dn-admin-shadow-lg: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--dn-admin-text-main);
        max-width: 1100px;
        margin: 20px 20px 40px 0;
        box-sizing: border-box;
    }
    .dev-nahian-subnav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--dn-admin-border-subtle);
        flex-wrap: wrap;
        gap: 14px;
    }
    .dev-nahian-subnav-title {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: var(--dn-admin-primary);
        letter-spacing: -0.02em;
    }
    .dn-editor-layout {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 1080px) {
        .dn-editor-layout { grid-template-columns: 1fr; }
    }
    .dn-card-module {
        background: var(--dn-admin-bg-surface);
        border: 1px solid var(--dn-admin-border-subtle);
        border-radius: var(--dn-admin-radius);
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--dn-admin-shadow);
    }
    .dn-module-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--dn-admin-border-subtle);
    }
    .dn-module-title {
        font-size: 15px;
        font-weight: 800;
        color: var(--dn-admin-primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dn-field-group {
        margin-bottom: 18px;
    }
    .dn-ctrl-input {
        width: 100%;
        height: 40px;
        border: 1px solid var(--dn-admin-border);
        border-radius: var(--dn-admin-radius-sm);
        padding: 0 14px;
        font-size: 13.5px;
        color: var(--dn-admin-text-main);
        background: #ffffff;
        outline: none;
        box-sizing: border-box;
    }
    .dn-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11.5px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 999px;
    }
    .dn-badge.ready { background: #f0fdfa; color: var(--dn-admin-teal); border: 1px solid #ccfbf1; }
    .dn-badge.dispatched { background: #eff6ff; color: var(--dn-admin-blue); border: 1px solid #dbeafe; }
    .dn-badge.service { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
</style>

<div class="dev-nahian-cars-scope">

    <?php
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Vehicle asset registered successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Vehicle data updated successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Vehicle asset retired.', 'dev-nahian-car' ) . '</p></div>';
    }

    // 1. Include Subnav Header Bar
    include plugin_dir_path( __FILE__ ) . 'cars/cars-tabs.php';

    // 2. Route Active View
    if ( 'add' === $subtab || ( 'edit' === $action && $id > 0 ) ) {
        include plugin_dir_path( __FILE__ ) . 'cars/cars-add-edit.php';
    } elseif ( 'view' === $action && $id > 0 ) {
        include plugin_dir_path( __FILE__ ) . 'cars/cars-view.php';
    } else {
        include plugin_dir_path( __FILE__ ) . 'cars/cars-all.php';
    }
    ?>

</div>
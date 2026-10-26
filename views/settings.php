<?php
/**
 * Admin View Tab: System, Payments & Email Branding Settings (Master Router)
 * Pure orchestrator file containing request handling and include routes.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
}

$notice         = '';
$base           = add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'settings' ), admin_url( 'admin.php' ) );
$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'general';

// Ensure WordPress media library scripts are active for image uploaders if on emails subtab
if ( 'emails' === $current_subtab ) {
    wp_enqueue_media();
}

// =========================================================================
// ROUTE & PROCESS SAVE HANDLER (Delegated to individual sub-tab files)
// =========================================================================
if ( isset( $_POST['dev_nahian_save_settings_subtab'] ) ) {
    check_admin_referer( 'dev_nahian_settings_subtab_verify', 'dev_nahian_settings_subtab_nonce' );
    $saved_subtab = sanitize_key( $_POST['current_subtab'] ?? 'general' );

    $handler_file = plugin_dir_path( __FILE__ ) . 'settings/setting-' . $saved_subtab . '.php';
    if ( file_exists( $handler_file ) ) {
        require_once $handler_file;
    }
}

// =========================================================================
// RETRIEVE ALL GLOBAL OPTIONS WITH PERSISTED VALUES
// =========================================================================
$currency           = get_option( 'dev_nahian_currency', 'USD' );
$currency_pos       = get_option( 'dev_nahian_currency_pos', 'left' );
$pricing_unit       = get_option( 'dev_nahian_pricing_unit', 'per_day' );
$min_days           = get_option( 'dev_nahian_min_days', 1 );
$min_driver_age     = get_option( 'dev_nahian_min_driver_age', 21 );
$op_hours_start     = get_option( 'dev_nahian_op_hours_start', '08:00' );
$op_hours_end       = get_option( 'dev_nahian_op_hours_end', '20:00' );
$prep_buffer_hours  = get_option( 'dev_nahian_prep_buffer_hours', 2 );
$weekend_ops        = get_option( 'dev_nahian_weekend_ops', 1 );

$gw_pay_later       = get_option( 'dev_nahian_gw_pay_later', 1 );
$gw_bank_transfer   = get_option( 'dev_nahian_gw_bank_transfer', 1 );
$gw_bank_details    = get_option( 'dev_nahian_gw_bank_details', "Bank: JPMorgan Chase & Co.\nAccount Name: Dev Nahian Fleet LLC\nIBAN / Routing: CHASEUS33XXX\nAccount No: 8849-2019-3301" );
$gw_cash_pickup     = get_option( 'dev_nahian_gw_cash_pickup', 1 );
$deposit_amount     = get_option( 'dev_nahian_deposit_amount', '1500.00' );
$no_deposit         = get_option( 'dev_nahian_no_deposit', 1 );

$admin_email        = get_option( 'dev_nahian_email_admin', get_option( 'admin_email' ) );
$brand_logo_url     = get_option( 'dev_nahian_email_logo_url', '' );
$email_accent_color = get_option( 'dev_nahian_email_accent_color', '#0d9488' );
$email_signature    = get_option( 'dev_nahian_email_signature', 'Thank you for choosing our premier car rental service.' );
?>

<style>
    .dev-nahian-settings-scope {
        --dark: #0f172a;
        --slate: #334155;
        --muted: #64748b;
        --light: #f8fafc;
        --border: #e2e8f0;
        --border-subtle: #f1f5f9;
        --teal: #0d9488;
        --teal-bg: #f0fdfa;
        --teal-border: #ccfbf1;
        --radius: 10px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .dev-nahian-settings-nav {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 14px;
    }
    .dev-nahian-subtab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 20px;
        font-size: 13.5px;
        font-weight: 700;
        text-decoration: none;
        color: var(--muted);
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 8px;
        transition: all 0.15s ease;
    }
    .dev-nahian-subtab-btn:hover {
        background: var(--light);
        color: var(--dark);
        border-color: #cbd5e1;
    }
    .dev-nahian-subtab-btn.is-active {
        background: var(--dark);
        color: #ffffff;
        border-color: var(--dark);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
    }
    .dev-nahian-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 24px;
        margin-bottom: 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .dev-nahian-card-header {
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-subtle);
    }
    .dev-nahian-card-title {
        margin: 0 0 4px;
        font-size: 16px;
        font-weight: 800;
        color: var(--dark);
    }
    .dev-nahian-card-desc {
        margin: 0;
        font-size: 12.5px;
        color: var(--muted);
    }
    .dev-nahian-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }
    @media (max-width: 800px) {
        .dev-nahian-grid-2 { grid-template-columns: 1fr; }
    }
    .dev-nahian-field-box {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .dev-nahian-field-box label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--slate);
    }
    .dev-nahian-input,
    .dev-nahian-select,
    .dev-nahian-textarea {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: #ffffff;
        font-size: 13.5px;
        color: var(--dark);
        outline: none;
        box-sizing: border-box;
    }
    .dev-nahian-textarea {
        height: 86px;
        padding: 10px 12px;
        resize: vertical;
    }
    .dev-nahian-input:focus,
    .dev-nahian-select:focus,
    .dev-nahian-textarea:focus {
        border-color: var(--dark);
    }
    .dev-nahian-hint {
        font-size: 11.5px;
        color: var(--muted);
        margin: 2px 0 0;
    }
    .dev-nahian-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }
    .dev-nahian-switch input { opacity: 0; width: 0; height: 0; }
    .dev-nahian-slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--border); transition: .2s; border-radius: 24px;
    }
    .dev-nahian-slider:before {
        position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
        background-color: white; transition: .2s; border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    .dev-nahian-switch input:checked + .dev-nahian-slider { background-color: var(--teal); }
    .dev-nahian-switch input:checked + .dev-nahian-slider:before { transform: translateX(20px); }
</style>

<div class="dev-nahian-settings-scope">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid #e2e8f0; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="margin:0; font-size:18px; font-weight:800; color:#0f172a;"><?php esc_html_e( 'System & Terminal Settings', 'dev-nahian-car' ); ?></h2>
            <p style="margin:4px 0 0; font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Configure local currencies, payment settlement rails, and automated email branding.', 'dev-nahian-car' ); ?></p>
        </div>
        <span style="font-size:11px; font-weight:700; color:#0d9488; background:#f0fdfa; border:1px solid #ccfbf1; padding:4px 12px; border-radius:999px;">
            &bull; <?php esc_html_e( 'Configuration Active', 'dev-nahian-car' ); ?>
        </span>
    </div>

    <?php if ( ! empty( $notice ) ) : ?>
        <?php echo wp_kses_post( $notice ); ?>
    <?php endif; ?>

    <!-- Sub-Navigation Tabs Bar -->
    <nav class="dev-nahian-settings-nav">
        <a href="<?php echo esc_url( add_query_arg( 'subtab', 'general', $base ) ); ?>" class="dev-nahian-subtab-btn <?php echo ( 'general' === $current_subtab ) ? 'is-active' : ''; ?>">
            <span class="dashicons dashicons-admin-settings" style="font-size:16px; line-height:20px;"></span>
            <?php esc_html_e( 'General', 'dev-nahian-car' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'subtab', 'payments', $base ) ); ?>" class="dev-nahian-subtab-btn <?php echo ( 'payments' === $current_subtab ) ? 'is-active' : ''; ?>">
            <span class="dashicons dashicons-money-alt" style="font-size:16px; line-height:20px;"></span>
            <?php esc_html_e( 'Payments', 'dev-nahian-car' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'subtab', 'emails', $base ) ); ?>" class="dev-nahian-subtab-btn <?php echo ( 'emails' === $current_subtab ) ? 'is-active' : ''; ?>">
            <span class="dashicons dashicons-email-alt" style="font-size:16px; line-height:20px;"></span>
            <?php esc_html_e( 'Emails', 'dev-nahian-car' ); ?>
        </a>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field( 'dev_nahian_settings_subtab_verify', 'dev_nahian_settings_subtab_nonce' ); ?>
        <input type="hidden" name="dev_nahian_save_settings_subtab" value="1">
        <input type="hidden" name="current_subtab" value="<?php echo esc_attr( $current_subtab ); ?>">

        <?php
        // Include the active sub-tab view file
        $view_file = plugin_dir_path( __FILE__ ) . 'settings/setting-' . $current_subtab . '.php';
        if ( file_exists( $view_file ) ) {
            include $view_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Sub-tab view not found.', 'dev-nahian-car' ) . '</p></div>';
        }
        ?>

        <!-- Unified Save Button -->
        <p style="margin: 22px 0 0 0;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; color:#ffffff; padding:10px 24px; font-weight:700; border-radius:6px; font-size:13.5px; cursor:pointer;">
                <?php 
                if ( 'emails' === $current_subtab ) {
                    esc_html_e( 'Save Email Branding', 'dev-nahian-car' );
                } elseif ( 'payments' === $current_subtab ) {
                    esc_html_e( 'Save Payment Gateways', 'dev-nahian-car' );
                } else {
                    esc_html_e( 'Save General Settings', 'dev-nahian-car' );
                }
                ?>
            </button>
        </p>
    </form>
</div>
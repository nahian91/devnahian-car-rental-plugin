<?php
/**
 * Cars Module: Sub-Navigation Header Bar (`cars-tabs.php`)
 * Upgraded to match the executive SaaS UI/UX design standard.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<style>
    .dn-subnav-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
        flex-wrap: wrap;
        gap: 14px;
    }
    .dn-subnav-heading {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dn-subnav-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #0f172a !important;
        border-color: #0f172a !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 13px !important;
        height: 38px !important;
        padding: 0 16px !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(15, 23, 42, 0.1) !important;
        transition: background 0.15s ease, transform 0.1s ease !important;
        text-decoration: none !important;
    }
    .dn-subnav-action-btn:hover {
        background: #1e293b !important;
        border-color: #1e293b !important;
    }
    .dn-subnav-action-btn .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
        line-height: 38px;
    }
</style>

<div class="dn-subnav-wrapper">
    <div>
        <h2 class="dn-subnav-heading">
            <span class="dashicons dashicons-car" style="color: #0d9488;"></span>
            <?php esc_html_e( 'Fleet Chassis Registry', 'dev-nahian-car' ); ?>
        </h2>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( 'subtab', 'add', $base ) ); ?>" class="button button-primary dn-subnav-action-btn">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'Register Vehicle', 'dev-nahian-car' ); ?>
        </a>
    </div>
</div>
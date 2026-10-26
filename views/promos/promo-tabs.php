<?php
/**
 * Promos Module: Sub-Navigation Header Bar (`promo-tabs.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="dn-promo-bar">
    <div class="dn-promo-bar-left">
        <h2 class="dn-promo-title"><?php esc_html_e( 'Promo Codes & Yield Rules', 'dev-nahian-car' ); ?></h2>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( 'subtab', 'add', $base ) ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700; height:36px; line-height:34px;">
            <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle; line-height:34px;"></span> <?php esc_html_e( 'Add Promo Code', 'dev-nahian-car' ); ?>
        </a>
    </div>
</div>
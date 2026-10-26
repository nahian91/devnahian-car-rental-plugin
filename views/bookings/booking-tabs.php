<?php
/**
 * Bookings Module: Sub-Navigation Header Bar (`booking-tabs.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="dev-nahian-subnav-bar">
    <div class="dev-nahian-subnav-left">
        <h2 class="dev-nahian-subnav-title"><?php esc_html_e( 'Bookings Management', 'dev-nahian-car' ); ?></h2>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( 'subtab', 'add', $base ) ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
            <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle; line-height:26px;"></span> <?php esc_html_e( 'Add Booking', 'dev-nahian-car' ); ?>
        </a>
    </div>
</div>
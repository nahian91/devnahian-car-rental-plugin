<?php
/**
 * Categories Module: Sub-Navigation Header Bar (`category-tabs.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="dev-nahian-cat-bar">
    <div class="dev-nahian-cat-bar-left">
        <h2 class="dev-nahian-cat-title"><?php esc_html_e( 'Categories Management', 'dev-nahian-car' ); ?></h2>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( 'subtab', 'add', $base ) ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
            <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle; line-height:26px;"></span> <?php esc_html_e( 'Add Category', 'dev-nahian-car' ); ?>
        </a>
    </div>
</div>
<?php
/**
 * Finances Module: Sub-Navigation Header Bar (`finance-tabs.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_add = ( 'add' === $subtab );
$is_all = ( ! $is_add && empty( $action ) );
?>
<div class="dn-fin-bar">
    <div class="dn-fin-bar-left">
        <h2 class="dn-fin-title"><?php esc_html_e( 'Income & Expense Ledger', 'dev-nahian-car' ); ?></h2>
        <div style="display: flex; gap: 6px; margin-left: 12px;">
            <a href="<?php echo esc_url( remove_query_arg( array( 'subtab', 'action', 'id' ), $base ) ); ?>" class="button <?php echo esc_attr( $is_all ? 'button-primary' : 'button-secondary' ); ?>" style="<?php echo esc_attr( $is_all ? 'background:#0f172a; border-color:#0f172a;' : '' ); ?>">
                <?php esc_html_e( 'All Entries', 'dev-nahian-car' ); ?>
            </a>
        </div>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( 'subtab', 'add', remove_query_arg( array( 'action', 'id' ), $base ) ) ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700; height:36px; line-height:34px;">
            <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle; line-height:34px;"></span> <?php esc_html_e( 'Add Transaction', 'dev-nahian-car' ); ?>
        </a>
    </div>
</div>
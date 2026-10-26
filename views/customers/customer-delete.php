<?php
/**
 * Customers Module: Delete & Retirement Handler (`customer-delete.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( 'delete' === $action && $id > 0 ) {
    check_admin_referer( 'dev_nahian_delete_customer_' . $id );
    if ( current_user_can( 'manage_options' ) ) {
        $wpdb->delete( $table_customers, array( 'id' => $id ), array( '%d' ) );
        wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'customers', 'deleted' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }
}
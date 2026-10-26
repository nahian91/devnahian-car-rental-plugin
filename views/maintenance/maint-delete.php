<?php
/**
 * Maintenance Module: Delete Handler (`maint-delete.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( 'delete' === $action && $id > 0 ) {
    check_admin_referer( 'dev_nahian_delete_maint_' . $id );
    if ( current_user_can( 'manage_options' ) ) {
        $wpdb->delete( $table_maint, array( 'id' => $id ), array( '%d' ) );
        wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'maintenance', 'deleted' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }
}
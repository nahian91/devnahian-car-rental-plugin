<?php
/**
 * Finances Module: Delete Handler (`finance-delete.php`)
 * Validates administrative permissions, enforces nonce security, and purges financial transactions.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Strict Capability Guard.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
}

global $wpdb;

// 2. Safe Scope Fallbacks.
$delete_id      = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$delete_action  = isset( $action ) ? sanitize_key( $action ) : ( isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '' );
$table_finances = isset( $table_finances ) ? $table_finances : $wpdb->prefix . 'dev_nahian_finances';

// 3. Execution.
if ( 'delete' === $delete_action && $delete_id > 0 ) {
	check_admin_referer( 'dev_nahian_delete_txn_' . $delete_id );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_finances,
		array( 'id' => $delete_id ),
		array( '%d' )
	);

	$redirect_args = array(
		'page'   => 'dev-nahian-fleet-manager',
		'tab'    => 'finances',
		'subtab' => 'all',
	);

	if ( false !== $deleted && $deleted > 0 ) {
		$redirect_args['deleted'] = '1';
	} else {
		$redirect_args['error'] = 'delete_failed';
	}

	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
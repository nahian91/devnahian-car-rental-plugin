<?php
/**
 * Bookings Module: Delete & Retirement Handler (`booking-delete.php`)
 * Validates permissions, nonces, and securely removes booking records.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Strict Privilege Guard
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
}

global $wpdb;

// 2. Safe Scope Fallbacks
$delete_id      = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$delete_action  = isset( $action ) ? sanitize_key( $action ) : ( isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '' );
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';

// 3. Deletion Execution
if ( 'delete' === $delete_action && $delete_id > 0 ) {
	check_admin_referer( 'dev_nahian_delete_booking_' . $delete_id );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_bookings,
		array( 'id' => $delete_id ),
		array( '%d' )
	);

	$redirect_args = array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'booking',
	);

	if ( false !== $deleted && $deleted > 0 ) {
		$redirect_args['deleted'] = '1';
	} else {
		$redirect_args['error'] = 'delete_failed';
	}

	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
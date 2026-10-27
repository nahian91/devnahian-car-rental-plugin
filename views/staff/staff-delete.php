<?php
/**
 * Staff Module: Delete Handler (`staff-delete.php`)
 * Validates permissions, checks trip assignment integrity, and retires staff profiles.
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
$table_staff    = isset( $table_staff ) ? $table_staff : $wpdb->prefix . 'dev_nahian_staff';
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';

// 3. Execution.
if ( 'delete' === $delete_action && $delete_id > 0 ) {
	check_admin_referer( 'dev_nahian_delete_staff_' . $delete_id );

	// Relational Check: Verify if chauffeur or personnel has assigned bookings.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$column_exists = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table_bookings} LIKE %s", 'driver_id' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! empty( $column_exists ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$assigned_trips = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_bookings} WHERE driver_id = %d", $delete_id ) );

		if ( $assigned_trips > 0 ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'dev-nahian-fleet-manager',
						'tab'   => 'staff',
						'error' => 'staff_has_trips',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_staff,
		array( 'id' => $delete_id ),
		array( '%d' )
	);

	$redirect_args = array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'staff',
	);

	if ( false !== $deleted && $deleted > 0 ) {
		$redirect_args['deleted'] = '1';
	} else {
		$redirect_args['error'] = 'delete_failed';
	}

	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
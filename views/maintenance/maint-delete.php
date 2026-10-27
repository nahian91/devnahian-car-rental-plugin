<?php
/**
 * Maintenance Module: Delete Handler (`maint-delete.php`)
 * Validates permissions, safely deletes service tickets, and restores vehicle fleet status if grounded.
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
$delete_id     = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$delete_action = isset( $action ) ? sanitize_key( $action ) : ( isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '' );
$table_maint   = isset( $table_maint ) ? $table_maint : $wpdb->prefix . 'dev_nahian_maintenance';
$table_cars    = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';

// 3. Execution.
if ( 'delete' === $delete_action && $delete_id > 0 ) {
	check_admin_referer( 'dev_nahian_delete_maint_' . $delete_id );

	// Fetch maintenance ticket details to check car grounding status.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT car_id, status FROM {$table_maint} WHERE id = %d", $delete_id ), ARRAY_A );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_maint,
		array( 'id' => $delete_id ),
		array( '%d' )
	);

	$redirect_args = array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'maintenance',
	);

	if ( false !== $deleted && $deleted > 0 ) {
		// If vehicle was marked in workshop, verify if any other active tickets remain; otherwise restore vehicle to 'ready'.
		if ( ! empty( $ticket['car_id'] ) && 'in_workshop' === $ticket['status'] ) {
			$car_id = absint( $ticket['car_id'] );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$remaining_active_tickets = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(id) FROM {$table_maint} WHERE car_id = %d AND status = 'in_workshop'",
					$car_id
				)
			);

			if ( 0 === $remaining_active_tickets ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update(
					$table_cars,
					array( 'status' => 'ready' ),
					array(
						'id'     => $car_id,
						'status' => 'maintenance',
					),
					array( '%s' ),
					array( '%d', '%s' )
				);
			}
		}

		$redirect_args['deleted'] = '1';
	} else {
		$redirect_args['error'] = 'delete_failed';
	}

	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
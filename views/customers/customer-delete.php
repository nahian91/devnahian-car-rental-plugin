<?php
/**
 * Customers Module: Delete & Retirement Handler (`customer-delete.php`)
 * Validates permissions, prevents orphaned booking records, and safely removes client profiles.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
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
$delete_id       = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$delete_action   = isset( $action ) ? sanitize_key( $action ) : ( isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '' );
$table_customers = isset( $table_customers ) ? $table_customers : $wpdb->prefix . 'dev_nahian_customers';
$table_bookings  = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';

// 3. Execution.
if ( 'delete' === $delete_action && $delete_id > 0 ) {
	check_admin_referer( 'dev_nahian_delete_customer_' . $delete_id );

	// Fetch customer email to ensure no booking manifests exist for this profile.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$customer_email = (string) $wpdb->get_var( $wpdb->prepare( "SELECT email FROM {$table_customers} WHERE id = %d", $delete_id ) );

	if ( '' !== $customer_email ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$has_bookings = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_bookings} WHERE customer_email = %s", $customer_email ) );

		if ( $has_bookings > 0 ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'dev-nahian-fleet-manager',
						'tab'   => 'customers',
						'error' => 'customer_has_bookings',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_customers,
		array( 'id' => $delete_id ),
		array( '%d' )
	);

	$redirect_args = array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'customers',
	);

	if ( false !== $deleted && $deleted > 0 ) {
		$redirect_args['deleted'] = '1';
	} else {
		$redirect_args['error'] = 'delete_failed';
	}

	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
<?php
/**
 * Categories Module: Delete & Retirement Handler (`category-delete.php`)
 * Validates permissions, prevents orphaned vehicle assignments, and deletes category rows.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Strict Capability Guard
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
}

global $wpdb;

// 2. Safe Scope Fallbacks
$delete_id        = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$delete_action    = isset( $action ) ? sanitize_key( $action ) : ( isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '' );
$table_categories = isset( $table_categories ) ? $table_categories : $wpdb->prefix . 'dev_nahian_categories';
$table_cars       = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';

// 3. Execution
if ( 'delete' === $delete_action && $delete_id > 0 ) {
	check_admin_referer( 'dev_nahian_delete_category_' . $delete_id );

	// Fetch category slug to ensure no fleet vehicles are assigned to it.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$cat_slug = (string) $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$table_categories} WHERE id = %d", $delete_id ) );

	if ( ! empty( $cat_slug ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$assigned_cars = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_cars} WHERE category = %s", $cat_slug ) );

		if ( $assigned_cars > 0 ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'dev-nahian-fleet-manager',
						'tab'   => 'categories',
						'error' => 'category_in_use',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_categories,
		array( 'id' => $delete_id ),
		array( '%d' )
	);

	$redirect_args = array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'categories',
	);

	if ( false !== $deleted && $deleted > 0 ) {
		$redirect_args['deleted'] = '1';
	} else {
		$redirect_args['error'] = 'delete_failed';
	}

	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
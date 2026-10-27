<?php
/**
 * Bookings Module: Sub-Navigation Header Bar (`booking-tabs.php`)
 * Provides tab-level status filtering and live record counters across reservation phases.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';

// Safe base URL fallback in case $base is not pre-defined in parent scope.
$booking_base = ! empty( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'booking',
	),
	admin_url( 'admin.php' )
);

$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$current_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$is_form_view   = ( 'add' === $current_subtab || 'edit' === $current_action );

// -----------------------------------------------------------------------------
// Live Counts for Sub-Nav Status Filters (Single Query)
// -----------------------------------------------------------------------------
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$counts = $wpdb->get_row(
	"SELECT
		COUNT(id) AS all_count,
		SUM(CASE WHEN booking_status = 'active' THEN 1 ELSE 0 END) AS active_count,
		SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
		SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
		SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
	FROM {$table_bookings}",
	ARRAY_A
);

$filter_tabs = array(
	'all'       => array(
		'label' => __( 'All Bookings', 'dev-nahian-car' ),
		'count' => (int) ( $counts['all_count'] ?? 0 ),
	),
	'active'    => array(
		'label' => __( 'Active', 'dev-nahian-car' ),
		'count' => (int) ( $counts['active_count'] ?? 0 ),
	),
	'pending'   => array(
		'label' => __( 'Pending', 'dev-nahian-car' ),
		'count' => (int) ( $counts['pending_count'] ?? 0 ),
	),
	'completed' => array(
		'label' => __( 'Completed', 'dev-nahian-car' ),
		'count' => (int) ( $counts['completed_count'] ?? 0 ),
	),
	'cancelled' => array(
		'label' => __( 'Cancelled', 'dev-nahian-car' ),
		'count' => (int) ( $counts['cancelled_count'] ?? 0 ),
	),
);
?>

<div class="dnf-subnav-bar">
	<div class="dnf-subnav-left">
		<?php
		foreach ( $filter_tabs as $tab_key => $tab_data ) :
			$tab_url   = add_query_arg( 'subtab', $tab_key, remove_query_arg( array( 'action', 'id' ), $booking_base ) );
			$is_active = ( ! $is_form_view && $current_subtab === $tab_key );
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" class="dnf-subnav-link <?php echo $is_active ? 'active' : ''; ?>">
				<span><?php echo esc_html( $tab_data['label'] ); ?></span>
				<span class="dnf-count-chip"><?php echo esc_html( (string) number_format_i18n( $tab_data['count'] ) ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</div>
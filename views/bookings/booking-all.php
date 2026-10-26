<?php
/**
 * Bookings Module: All Bookings List Table (`booking-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$bookings      = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC", ARRAY_A );
$booking_count = is_array( $bookings ) ? count( $bookings ) : 0;
?>
<div style="margin-bottom: 12px;">
    <span class="dev-nahian-count-chip">
        <?php printf( esc_html__( '%s Total Records', 'dev-nahian-car' ), esc_html( number_format_i18n( $booking_count ) ) ); ?>
    </span>
</div>

<table class="wp-list-table widefat fixed striped" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th style="width: 100px;"><?php esc_html_e( 'Booking ID', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Customer', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Phone', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Vehicle', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Pickup', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Return', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Total', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 140px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $bookings ) ) : ?>
            <?php foreach ( $bookings as $b ) : 
                $status_key = sanitize_key( $b['booking_status'] );
                $view_url   = add_query_arg( array( 'action' => 'view', 'id' => $b['id'] ), $base );
                $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $b['id'] ), $base );
                $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $b['id'] ), $base ), 'dev_nahian_delete_booking_' . $b['id'] );
            ?>
                <tr>
                    <td><strong><?php echo esc_html( $b['booking_code'] ); ?></strong></td>
                    <td>
                        <strong><?php echo esc_html( $b['customer_name'] ); ?></strong><br>
                        <small style="color: var(--b-muted);"><?php echo esc_html( $b['customer_email'] ); ?></small>
                    </td>
                    <td><?php echo esc_html( $b['customer_phone'] ); ?></td>
                    <td><?php echo esc_html( ! empty( $b['car_name'] ) ? $b['car_name'] : '-' ); ?></td>
                    <td><?php echo esc_html( $b['pickup_date'] . ' (' . $b['pickup_time'] . ')' ); ?></td>
                    <td><?php echo esc_html( $b['return_date'] . ' (' . $b['return_time'] . ')' ); ?></td>
                    <td><strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $b['total_amount'], 2 ) ); ?></strong></td>
                    <td><span class="dev-nahian-status-pill <?php echo esc_attr( $status_key ); ?>">&bull; <?php echo esc_html( ucfirst( $status_key ) ); ?></span></td>
                    <td style="text-align: right;">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="button button-small"><?php esc_html_e( 'View', 'dev-nahian-car' ); ?></a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?></a>
                        <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this booking record permanently?', 'dev-nahian-car' ); ?>');">&times;</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="9" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No bookings recorded in the system yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
/**
 * Maintenance Module: All Service Tickets List Table (`maint-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$maint_records = $wpdb->get_results( "SELECT * FROM {$table_maint} ORDER BY id DESC", ARRAY_A );
$maint_count   = is_array( $maint_records ) ? count( $maint_records ) : 0;
?>
<div style="margin-bottom: 12px;">
    <span class="dn-maint-chip">
        <?php printf( esc_html__( '%s Service Tickets', 'dev-nahian-car' ), esc_html( number_format_i18n( $maint_count ) ) ); ?>
    </span>
</div>

<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th style="width: 80px;"><?php esc_html_e( 'Ticket #', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Target Chassis', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Service Procedure', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Service Date', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Workshop Cost', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 170px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $maint_records ) ) : ?>
            <?php foreach ( $maint_records as $m ) : 
                $status_key = sanitize_key( $m['status'] );
                $view_url   = add_query_arg( array( 'action' => 'view', 'id' => $m['id'] ), $base );
                $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $m['id'] ), $base );
                $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $m['id'] ), $base ), 'dev_nahian_delete_maint_' . $m['id'] );
            ?>
                <tr>
                    <td><code>#<?php echo esc_html( $m['id'] ); ?></code></td>
                    <td><strong><?php echo esc_html( ! empty( $m['car_name'] ) ? $m['car_name'] : 'Chassis #' . $m['car_id'] ); ?></strong></td>
                    <td><?php echo esc_html( $m['service_type'] ); ?></td>
                    <td><?php echo esc_html( $m['service_date'] ); ?></td>
                    <td><strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $m['cost'], 2 ) ); ?></strong></td>
                    <td><span class="dev-nahian-status-pill <?php echo esc_attr( $status_key ); ?>">&bull; <?php echo esc_html( ucfirst( str_replace( '_', ' ', $status_key ) ) ); ?></span></td>
                    <td style="text-align: right;">
                        <div class="dev-nahian-btn-group">
                            <a href="<?php echo esc_url( $view_url ); ?>" class="dev-nahian-btn dev-nahian-btn-view" title="<?php esc_attr_e( 'View Ticket', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'view' ); ?> <?php esc_html_e( 'View', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="dev-nahian-btn dev-nahian-btn-edit" title="<?php esc_attr_e( 'Edit Ticket', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'edit' ); ?> <?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="dev-nahian-btn dev-nahian-btn-delete" title="<?php esc_attr_e( 'Delete Ticket', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Remove this maintenance ticket permanently?', 'dev-nahian-car' ); ?>');">
                                <?php echo dev_nahian_get_icon( 'delete' ); ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No maintenance service tickets recorded in the workshop log yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
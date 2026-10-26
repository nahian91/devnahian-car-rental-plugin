<?php
/**
 * Inspections Module: All Reports List Table (`inspection-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$inspections = $wpdb->get_results( "SELECT * FROM {$table_insp} ORDER BY id DESC", ARRAY_A );
$insp_count  = is_array( $inspections ) ? count( $inspections ) : 0;
?>
<div style="margin-bottom: 12px;">
    <span class="dn-insp-chip">
        <?php printf( esc_html__( '%s Reports Filed', 'dev-nahian-car' ), esc_html( number_format_i18n( $insp_count ) ) ); ?>
    </span>
</div>

<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th style="width: 100px;"><?php esc_html_e( 'Manifest ID', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Vehicle Asset', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Phase', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Fuel / Odometer', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Inspector', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Timestamp', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 170px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $inspections ) ) : ?>
            <?php foreach ( $inspections as $ins ) : 
                $type_key   = sanitize_key( $ins['inspection_type'] );
                $view_url   = add_query_arg( array( 'action' => 'view', 'id' => $ins['id'] ), $base );
                $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $ins['id'] ), $base );
                $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $ins['id'] ), $base ), 'dev_nahian_delete_insp_' . $ins['id'] );
            ?>
                <tr>
                    <td><strong>#<?php echo esc_html( $ins['booking_code'] ); ?></strong></td>
                    <td><?php echo esc_html( $ins['car_name'] ); ?></td>
                    <td><span class="dev-nahian-status-pill <?php echo esc_attr( $type_key ); ?>">&bull; <?php echo esc_html( ucfirst( $type_key ) ); ?></span></td>
                    <td>
                        <code><?php echo esc_html( $ins['fuel_level'] . '% Fuel' ); ?></code>
                        <span style="font-size: 11px; color: var(--muted); display: block; font-feature-settings: 'tnum';"><?php echo esc_html( number_format_i18n( (int) $ins['odometer'] ) . ' mi' ); ?></span>
                    </td>
                    <td><?php echo esc_html( $ins['inspector_name'] ); ?></td>
                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ins['inspected_at'] ) ) ); ?></td>
                    <td style="text-align: right;">
                        <div class="dev-nahian-btn-group">
                            <a href="<?php echo esc_url( $view_url ); ?>" class="dev-nahian-btn dev-nahian-btn-view" title="<?php esc_attr_e( 'View Report', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'view' ); ?> <?php esc_html_e( 'View', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="dev-nahian-btn dev-nahian-btn-edit" title="<?php esc_attr_e( 'Edit Report', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'edit' ); ?> <?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="dev-nahian-btn dev-nahian-btn-delete" title="<?php esc_attr_e( 'Delete Report', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Remove this inspection report permanently?', 'dev-nahian-car' ); ?>');">
                                <?php echo dev_nahian_get_icon( 'delete' ); ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No vehicle inspection reports recorded yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
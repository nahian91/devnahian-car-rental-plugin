<?php
/**
 * Promos Module: All Coupons List Table (`promo-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$promos      = $wpdb->get_results( "SELECT * FROM {$table_promos} ORDER BY id DESC", ARRAY_A );
$promo_count = is_array( $promos ) ? count( $promos ) : 0;
?>
<div style="margin-bottom: 12px;">
    <span class="dn-promo-chip">
        <?php printf( esc_html__( '%s Coupons Registered', 'dev-nahian-car' ), esc_html( number_format_i18n( $promo_count ) ) ); ?>
    </span>
</div>

<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th style="width: 140px;"><?php esc_html_e( 'Coupon Code', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Discount Value', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Redemptions', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Expiration Date', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 170px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $promos ) ) : ?>
            <?php foreach ( $promos as $p ) : 
                $status_key = sanitize_key( $p['status'] );
                $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $p['id'] ), $base );
                $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $p['id'] ), $base ), 'dev_nahian_delete_promo_' . $p['id'] );
                
                $discount_display = ( 'percentage' === $p['discount_type'] ) 
                    ? number_format_i18n( (float) $p['discount_value'], 0 ) . '%' 
                    : $currency . ' ' . number_format_i18n( (float) $p['discount_value'], 2 );
            ?>
                <tr>
                    <td><strong style="font-family: monospace; font-size: 14px; letter-spacing: 0.05em;"><?php echo esc_html( $p['code'] ); ?></strong></td>
                    <td><strong><?php echo esc_html( $discount_display ); ?></strong></td>
                    <td><?php printf( esc_html__( '%s / %s used', 'dev-nahian-car' ), esc_html( number_format_i18n( $p['times_used'] ) ), esc_html( number_format_i18n( $p['usage_limit'] ) ) ); ?></td>
                    <td><?php echo esc_html( ! empty( $p['expiry_date'] ) ? $p['expiry_date'] : 'No Expiry' ); ?></td>
                    <td><span class="dev-nahian-status-pill <?php echo esc_attr( $status_key ); ?>">&bull; <?php echo esc_html( ucfirst( $status_key ) ); ?></span></td>
                    <td style="text-align: right;">
                        <div class="dev-nahian-btn-group">
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="dev-nahian-btn dev-nahian-btn-edit" title="<?php esc_attr_e( 'Edit Coupon', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'edit' ); ?> <?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="dev-nahian-btn dev-nahian-btn-delete" title="<?php esc_attr_e( 'Delete Coupon', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Remove this promo code permanently?', 'dev-nahian-car' ); ?>');">
                                <?php echo dev_nahian_get_icon( 'delete' ); ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No promotional coupons registered in the system yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
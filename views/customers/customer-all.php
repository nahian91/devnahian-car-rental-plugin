<?php
/**
 * Customers Module: All Customers List Table (`customer-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$customers  = $wpdb->get_results( "SELECT * FROM {$table_customers} ORDER BY id DESC", ARRAY_A );
$cust_count = is_array( $customers ) ? count( $customers ) : 0;
?>
<div style="margin-bottom: 12px;">
    <span class="dev-nahian-cust-chip">
        <?php printf( esc_html__( '%s Registered Profiles', 'dev-nahian-car' ), esc_html( number_format_i18n( $cust_count ) ) ); ?>
    </span>
</div>

<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Customer Name', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Email', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Phone', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'License / Region', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Tier', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'KYC Status', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 170px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $customers ) ) : ?>
            <?php foreach ( $customers as $c ) : 
                $c_status   = sanitize_key( $c['status'] );
                $c_tier     = sanitize_key( $c['tier'] );
                $view_url   = add_query_arg( array( 'action' => 'view', 'id' => $c['id'] ), $base );
                $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $c['id'] ), $base );
                $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $c['id'] ), $base ), 'dev_nahian_delete_customer_' . $c['id'] );
            ?>
                <tr>
                    <td><strong><?php echo esc_html( $c['name'] ); ?></strong></td>
                    <td><?php echo esc_html( $c['email'] ); ?></td>
                    <td><?php echo esc_html( ! empty( $c['phone'] ) ? $c['phone'] : '-' ); ?></td>
                    <td>
                        <code><?php echo esc_html( ! empty( $c['license_number'] ) ? $c['license_number'] : '-' ); ?></code>
                        <span style="font-size: 11px; color: var(--muted); display: block;"><?php echo esc_html( $c['country'] ); ?></span>
                    </td>
                    <td><span class="dev-nahian-tier-badge <?php echo esc_attr( $c_tier ); ?>"><?php echo esc_html( ucfirst( $c_tier ) ); ?></span></td>
                    <td><span class="dev-nahian-status-pill <?php echo esc_attr( $c_status ); ?>">&bull; <?php echo esc_html( ucfirst( $c_status ) ); ?></span></td>
                    <td style="text-align: right;">
                        <div class="dev-nahian-btn-group">
                            <a href="<?php echo esc_url( $view_url ); ?>" class="dev-nahian-btn dev-nahian-btn-view" title="<?php esc_attr_e( 'View Customer Dossier', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'view' ); ?> <?php esc_html_e( 'View', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="dev-nahian-btn dev-nahian-btn-edit" title="<?php esc_attr_e( 'Edit Profile', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'edit' ); ?> <?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="dev-nahian-btn dev-nahian-btn-delete" title="<?php esc_attr_e( 'Delete Customer Record', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Remove this customer record permanently?', 'dev-nahian-car' ); ?>');">
                                <?php echo dev_nahian_get_icon( 'delete' ); ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No customer profiles registered in the system yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
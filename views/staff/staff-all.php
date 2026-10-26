<?php
/**
 * Staff Module: All Personnel List Table (`staff-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$staff_members = $wpdb->get_results( "SELECT * FROM {$table_staff} ORDER BY id DESC", ARRAY_A );
$staff_count   = is_array( $staff_members ) ? count( $staff_members ) : 0;
?>
<div style="margin-bottom: 12px;">
    <span class="dn-staff-chip">
        <?php printf( esc_html__( '%s Personnel Registered', 'dev-nahian-car' ), esc_html( number_format_i18n( $staff_count ) ) ); ?>
    </span>
</div>

<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th style="width: 110px;"><?php esc_html_e( 'Staff ID', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Full Name', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Role / Designation', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Contact Email', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Phone / License', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Duty Status', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 170px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $staff_members ) ) : ?>
            <?php foreach ( $staff_members as $m ) : 
                $status_key = sanitize_key( $m['status'] );
                $role_key   = sanitize_key( $m['role_type'] );
                $view_url   = add_query_arg( array( 'action' => 'view', 'id' => $m['id'] ), $base );
                $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $m['id'] ), $base );
                $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $m['id'] ), $base ), 'dev_nahian_delete_staff_' . $m['id'] );
            ?>
                <tr>
                    <td><code><?php echo esc_html( $m['staff_code'] ); ?></code></td>
                    <td><strong><?php echo esc_html( $m['name'] ); ?></strong></td>
                    <td><span class="dev-nahian-role-badge"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $role_key ) ) ); ?></span></td>
                    <td><?php echo esc_html( $m['email'] ); ?></td>
                    <td>
                        <?php echo esc_html( ! empty( $m['phone'] ) ? $m['phone'] : '-' ); ?>
                        <span style="font-size: 11px; color: var(--muted); display: block; font-family: monospace;"><?php echo esc_html( ! empty( $m['license_no'] ) ? $m['license_no'] : 'No DL' ); ?></span>
                    </td>
                    <td><span class="dev-nahian-status-pill <?php echo esc_attr( $status_key ); ?>">&bull; <?php echo esc_html( ucfirst( str_replace( '_', ' ', $status_key ) ) ); ?></span></td>
                    <td style="text-align: right;">
                        <div class="dev-nahian-btn-group">
                            <a href="<?php echo esc_url( $view_url ); ?>" class="dev-nahian-btn dev-nahian-btn-view" title="<?php esc_attr_e( 'View Dossier', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'view' ); ?> <?php esc_html_e( 'View', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="dev-nahian-btn dev-nahian-btn-edit" title="<?php esc_attr_e( 'Edit Profile', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'edit' ); ?> <?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="dev-nahian-btn dev-nahian-btn-delete" title="<?php esc_attr_e( 'Delete Profile', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Remove this staff member permanently?', 'dev-nahian-car' ); ?>');">
                                <?php echo dev_nahian_get_icon( 'delete' ); ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No staff personnel registered in the system yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
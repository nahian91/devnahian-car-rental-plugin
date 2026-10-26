<?php
/**
 * Cars Module: Fleet Registry List View (`cars-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cars       = $wpdb->get_results( "SELECT * FROM {$table_cars} ORDER BY id DESC", ARRAY_A );
$cars_count = is_array( $cars ) ? count( $cars ) : 0;
?>
<style>
    .dn-action-btns {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        justify-content: flex-end;
    }
    .dn-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        text-decoration: none;
        transition: all 0.15s ease;
        cursor: pointer;
        padding: 0;
    }
    .dn-icon-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }
    .dn-icon-btn.delete:hover {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #fca5a5;
    }
    .dn-icon-btn svg {
        width: 15px;
        height: 15px;
        fill: currentColor;
    }
</style>

<div style="margin-bottom: 12px;">
    <span style="font-size:11px; font-weight:700; color:#475569; background:#f1f5f9; padding:4px 10px; border-radius:999px;">
        <?php printf( esc_html__( '%s Registered Chassis', 'dev-nahian-car' ), esc_html( number_format_i18n( $cars_count ) ) ); ?>
    </span>
</div>

<table class="wp-list-table widefat fixed striped" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Model & VIN', 'dev-nahian-car' ); ?></th>
            <th style="width: 110px;"><?php esc_html_e( 'Plate', 'dev-nahian-car' ); ?></th>
            <th style="width: 140px;"><?php esc_html_e( 'Category', 'dev-nahian-car' ); ?></th>
            <th style="width: 130px;"><?php esc_html_e( 'Daily Rate', 'dev-nahian-car' ); ?></th>
            <th style="width: 130px;"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 130px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $cars ) ) : ?>
            <?php foreach ( $cars as $c ) : 
                $status_key = sanitize_key( $c['status'] );
                $view_url   = add_query_arg( array( 'action' => 'view', 'id' => $c['id'] ), $base );
                $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $c['id'] ), $base );
                $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $c['id'] ), $base ), 'dev_nahian_delete_car_' . $c['id'] );
            ?>
                <tr>
                    <td>
                        <strong><a href="<?php echo esc_url( $view_url ); ?>" style="color:#0f172a; text-decoration:none;"><?php echo esc_html( $c['name'] ); ?></a></strong><br>
                        <code style="font-size: 10px; color: #64748b;"><?php echo esc_html( $c['vin'] ); ?></code>
                    </td>
                    <td><code><?php echo esc_html( ! empty( $c['plate'] ) ? $c['plate'] : '-' ); ?></code></td>
                    <td><?php echo esc_html( $c['category'] ); ?></td>
                    <td><strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $c['price'], 2 ) ); ?></strong></td>
                    <td><span class="dn-badge <?php echo esc_attr( $status_key ); ?>">&bull; <?php echo esc_html( ucfirst( $status_key ) ); ?></span></td>
                    <td style="text-align: right;">
                        <div class="dn-action-btns">
                            <!-- View Icon Button -->
                            <a href="<?php echo esc_url( $view_url ); ?>" class="dn-icon-btn" title="<?php esc_attr_e( 'View Dossier', 'dev-nahian-car' ); ?>">
                                <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                            </a>
                            <!-- Edit Icon Button -->
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="dn-icon-btn" title="<?php esc_attr_e( 'Edit Chassis', 'dev-nahian-car' ); ?>">
                                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </a>
                            <!-- Delete Icon Button -->
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="dn-icon-btn delete" title="<?php esc_attr_e( 'Retire Chassis', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Retire this chassis permanently?', 'dev-nahian-car' ); ?>');">
                                <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No vehicle assets registered in the fleet inventory yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
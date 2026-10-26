<?php
/**
 * Categories Module: All Categories List Table (`category-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = $wpdb->get_results( "SELECT * FROM {$table_categories} ORDER BY id DESC", ARRAY_A );
$cat_count  = is_array( $categories ) ? count( $categories ) : 0;

// Single aggregated query for car allocation counts (Eliminates N+1 loop queries)
$car_counts_raw = $wpdb->get_results( "SELECT category, COUNT(*) as total FROM {$table_cars} GROUP BY category", ARRAY_A );
$car_counts_map = array();
if ( ! empty( $car_counts_raw ) ) {
    foreach ( $car_counts_raw as $cnt_row ) {
        $car_counts_map[ $cnt_row['category'] ] = absint( $cnt_row['total'] );
    }
}
?>
<div style="margin-bottom: 12px;">
    <span class="dev-nahian-cat-chip">
        <?php printf( esc_html__( '%s Total Categories', 'dev-nahian-car' ), esc_html( number_format_i18n( $cat_count ) ) ); ?>
    </span>
</div>

<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Category Name', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Slug', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Base Daily Rate', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Description', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Registered Cars', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 170px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $categories ) ) : ?>
            <?php foreach ( $categories as $c ) : 
                $cat_slug_key = sanitize_key( $c['status'] );
                $cars_in_cat  = $car_counts_map[ $c['slug'] ] ?? 0;
                $view_url     = add_query_arg( array( 'action' => 'view', 'id' => $c['id'] ), $base );
                $edit_url     = add_query_arg( array( 'action' => 'edit', 'id' => $c['id'] ), $base );
                $delete_url   = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $c['id'] ), $base ), 'dev_nahian_delete_category_' . $c['id'] );
            ?>
                <tr>
                    <td><strong><?php echo esc_html( $c['name'] ); ?></strong></td>
                    <td><code><?php echo esc_html( $c['slug'] ); ?></code></td>
                    <td><strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $c['base_price'], 2 ) ); ?></strong></td>
                    <td><?php echo esc_html( wp_trim_words( $c['description'], 10, '...' ) ); ?></td>
                    <td><?php printf( esc_html__( '%s Cars', 'dev-nahian-car' ), esc_html( number_format_i18n( $cars_in_cat ) ) ); ?></td>
                    <td><span class="dev-nahian-status-pill <?php echo esc_attr( $cat_slug_key ); ?>">&bull; <?php echo esc_html( ucfirst( $cat_slug_key ) ); ?></span></td>
                    <td style="text-align: right;">
                        <div class="dev-nahian-btn-group">
                            <a href="<?php echo esc_url( $view_url ); ?>" class="dev-nahian-btn dev-nahian-btn-view" title="<?php esc_attr_e( 'View Category Dossier', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'view' ); ?> <?php esc_html_e( 'View', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="dev-nahian-btn dev-nahian-btn-edit" title="<?php esc_attr_e( 'Edit Category', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'edit' ); ?> <?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="dev-nahian-btn dev-nahian-btn-delete" title="<?php esc_attr_e( 'Delete Category Record', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this category permanently?', 'dev-nahian-car' ); ?>');">
                                <?php echo dev_nahian_get_icon( 'delete' ); ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No categories recorded in the system yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
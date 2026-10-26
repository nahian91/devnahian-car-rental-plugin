<?php
/**
 * Categories Module: Category Details Inspector (`category-view.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_categories} WHERE id = %d", $id ), ARRAY_A );

if ( ! $cat ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Category not found.', 'dev-nahian-car' ) . '</p></div>';
    return;
}

$cat_status_slug = sanitize_key( $cat['status'] );
$car_count       = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_cars} WHERE category = %s", $cat['slug'] ) );
?>
<style>
    .dev-nahian-view-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        max-width: 800px;
    }
    .dev-nahian-view-header {
        background: var(--dark);
        color: #ffffff;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dev-nahian-view-body {
        padding: 24px;
    }
    .dev-nahian-row-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dashed var(--border);
        font-size: 13px;
    }
    .dev-nahian-row-item .label { color: var(--muted); }
    .dev-nahian-row-item .value { font-weight: 700; color: var(--dark); text-align: right; }
</style>

<div class="dev-nahian-cat-bar">
    <div class="dev-nahian-cat-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to Categories', 'dev-nahian-car' ); ?></a>
        <span class="dev-nahian-cat-chip"><?php esc_html_e( 'Category Details', 'dev-nahian-car' ); ?></span>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $id ), $base ) ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
            <?php esc_html_e( 'Edit Category', 'dev-nahian-car' ); ?>
        </a>
    </div>
</div>

<div class="dev-nahian-view-card">
    <div class="dev-nahian-view-header">
        <div>
            <span style="font-family: monospace; font-size: 12px; color: var(--gold); font-weight: 700;">
                <?php printf( esc_html__( 'SLUG: %s', 'dev-nahian-car' ), esc_html( $cat['slug'] ) ); ?>
            </span>
            <h2 style="margin: 4px 0 0; font-size: 20px; font-weight: 800; color: #fff;">
                <?php echo esc_html( $cat['name'] ); ?>
            </h2>
        </div>
        <div>
            <span class="dev-nahian-status-pill <?php echo esc_attr( $cat_status_slug ); ?>">
                &bull; <?php echo esc_html( ucfirst( $cat_status_slug ) ); ?>
            </span>
        </div>
    </div>

    <div class="dev-nahian-view-body">
        <div class="dev-nahian-row-item">
            <span class="label"><?php esc_html_e( 'Category Name', 'dev-nahian-car' ); ?></span>
            <span class="value"><?php echo esc_html( $cat['name'] ); ?></span>
        </div>
        <div class="dev-nahian-row-item">
            <span class="label"><?php esc_html_e( 'Slug', 'dev-nahian-car' ); ?></span>
            <span class="value"><code><?php echo esc_html( $cat['slug'] ); ?></code></span>
        </div>
        <div class="dev-nahian-row-item">
            <span class="label"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></span>
            <span class="value"><?php echo esc_html( ucfirst( $cat_status_slug ) ); ?></span>
        </div>
        <div class="dev-nahian-row-item">
            <span class="label"><?php esc_html_e( 'Registered Vehicles', 'dev-nahian-car' ); ?></span>
            <span class="value"><?php printf( esc_html__( '%s Chassis Allocated', 'dev-nahian-car' ), esc_html( number_format_i18n( $car_count ) ) ); ?></span>
        </div>
        <div class="dev-nahian-row-item">
            <span class="label"><?php esc_html_e( 'Standard Daily Base Rate', 'dev-nahian-car' ); ?></span>
            <span class="value"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $cat['base_price'], 2 ) . ' / ' . __( 'day', 'dev-nahian-car' ) ); ?></span>
        </div>
        <div class="dev-nahian-row-item">
            <span class="label"><?php esc_html_e( 'Description', 'dev-nahian-car' ); ?></span>
            <span class="value" style="max-width: 480px; text-align: right; line-height: 1.5;">
                <?php echo ! empty( $cat['description'] ) ? esc_html( $cat['description'] ) : esc_html__( 'No description provided.', 'dev-nahian-car' ); ?>
            </span>
        </div>
    </div>
</div>
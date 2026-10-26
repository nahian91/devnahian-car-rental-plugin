<?php
/**
 * Categories Module: Create & Edit Workstation (`category-add-edit.php`)
 * Handles both POST actions and form markup.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action (Create or Update)
if ( isset( $_POST['dev_nahian_add_category_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_category_verify', 'dev_nahian_cat_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $slug = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );

        if ( empty( $slug ) ) {
            $slug = sanitize_title( $name );
        }

        $slug_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_categories} WHERE slug = %s", $slug ) );

        if ( $slug_exists ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'A category with this slug already exists. Please choose another.', 'dev-nahian-car' ) . '</p></div>';
        } else {
            $inserted = $wpdb->insert(
                $table_categories,
                array(
                    'name'        => $name,
                    'slug'        => $slug,
                    'base_price'  => floatval( $_POST['base_price'] ?? 0.00 ),
                    'status'      => sanitize_key( $_POST['status'] ?? 'active' ),
                    'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
                    'created_at'  => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%f', '%s', '%s', '%s' )
            );

            if ( false !== $inserted ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'categories', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

if ( isset( $_POST['dev_nahian_edit_category_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_category_' . $id, 'dev_nahian_cat_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $slug = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );

        if ( empty( $slug ) ) {
            $slug = sanitize_title( $name );
        }

        $slug_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_categories} WHERE slug = %s AND id != %d", $slug, $id ) );

        if ( $slug_exists ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Another category is already using this slug.', 'dev-nahian-car' ) . '</p></div>';
        } else {
            $updated = $wpdb->update(
                $table_categories,
                array(
                    'name'        => $name,
                    'slug'        => $slug,
                    'base_price'  => floatval( $_POST['base_price'] ?? 0.00 ),
                    'status'      => sanitize_key( $_POST['status'] ?? 'active' ),
                    'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
                ),
                array( 'id' => $id ),
                array( '%s', '%s', '%f', '%s', '%s' ),
                array( '%d' )
            );

            if ( false !== $updated ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'categories', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

// 2. Render Form View
$is_edit = ( 'edit' === $action && $id > 0 );
$cat     = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_categories} WHERE id = %d", $id ), ARRAY_A ) : array();
?>
<style>
    .dev-nahian-cat-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        max-width: 760px;
    }
    .dev-nahian-cat-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    @media (max-width: 640px) {
        .dev-nahian-cat-grid { grid-template-columns: 1fr; }
    }
    .col-full { grid-column: 1 / -1; }
    .dev-nahian-form-heading {
        grid-column: 1 / -1;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        padding-top: 14px;
        margin-top: 4px;
        border-top: 1px solid var(--border);
    }
    .dev-nahian-form-heading:first-of-type {
        border-top: none;
        padding-top: 0;
        margin-top: 0;
    }
    .dev-nahian-form-row {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .dev-nahian-form-row label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text);
    }
    .dev-nahian-form-input, 
    .dev-nahian-form-select, 
    .dev-nahian-form-textarea {
        height: 38px;
        padding: 0 12px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: #ffffff;
        font-size: 13.5px;
        color: var(--dark);
        outline: none;
        box-sizing: border-box;
        width: 100%;
    }
    .dev-nahian-form-textarea {
        height: 80px;
        padding: 8px 12px;
        resize: vertical;
    }
</style>

<div class="dev-nahian-cat-bar">
    <div class="dev-nahian-cat-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        <h2 class="dev-nahian-cat-title">
            <?php echo $is_edit ? sprintf( esc_html__( 'Edit Category: %s', 'dev-nahian-car' ), esc_html( $cat['name'] ?? '' ) ) : esc_html__( 'Add Category', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<div class="dev-nahian-cat-card">
    <form method="post" action="">
        <?php 
        if ( $is_edit ) {
            wp_nonce_field( 'dev_nahian_edit_category_' . $id, 'dev_nahian_cat_nonce' );
            echo '<input type="hidden" name="dev_nahian_edit_category_submit" value="1">';
        } else {
            wp_nonce_field( 'dev_nahian_add_category_verify', 'dev_nahian_cat_nonce' );
            echo '<input type="hidden" name="dev_nahian_add_category_submit" value="1">';
        }
        ?>

        <div class="dev-nahian-cat-grid">
            <div class="dev-nahian-form-heading"><?php esc_html_e( '1. Details', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-form-row">
                <label for="name"><?php esc_html_e( 'Category Name', 'dev-nahian-car' ); ?></label>
                <input type="text" id="name" name="name" class="dev-nahian-form-input" value="<?php echo esc_attr( $cat['name'] ?? '' ); ?>" placeholder="e.g. Track Ready" required>
            </div>

            <div class="dev-nahian-form-row">
                <label for="slug"><?php esc_html_e( 'Slug', 'dev-nahian-car' ); ?></label>
                <input type="text" id="slug" name="slug" class="dev-nahian-form-input" value="<?php echo esc_attr( $cat['slug'] ?? '' ); ?>" placeholder="e.g. track-ready">
            </div>

            <div class="dev-nahian-form-row">
                <label for="base_price"><?php printf( esc_html__( 'Base Daily Price (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></label>
                <input type="number" step="0.01" id="base_price" name="base_price" class="dev-nahian-form-input" value="<?php echo esc_attr( (string) ( $cat['base_price'] ?? '' ) ); ?>" placeholder="199.00">
            </div>

            <div class="dev-nahian-form-row">
                <label for="status"><?php esc_html_e( 'Status', 'dev-nahian-car' ); ?></label>
                <select id="status" name="status" class="dev-nahian-form-select">
                    <option value="active" <?php selected( $cat['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'dev-nahian-car' ); ?></option>
                    <option value="inactive" <?php selected( $cat['status'] ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dev-nahian-form-heading"><?php esc_html_e( '2. Information', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-form-row col-full">
                <label for="description"><?php esc_html_e( 'Description', 'dev-nahian-car' ); ?></label>
                <textarea id="description" name="description" class="dev-nahian-form-textarea" placeholder="Roll-cage and telemetry instrumented track editions."><?php echo esc_textarea( $cat['description'] ?? '' ); ?></textarea>
            </div>
        </div>

        <p style="margin: 20px 0 0 0; display: flex; gap: 10px;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
                <?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Save Category', 'dev-nahian-car' ); ?>
            </button>
            <a href="<?php echo esc_url( $base ); ?>" class="button"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        </p>
    </form>
</div>
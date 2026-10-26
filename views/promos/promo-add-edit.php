<?php
/**
 * Promos Module: Create & Edit Workstation (`promo-add-edit.php`)
 * Handles both POST actions and form markup.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action (Create or Update)
if ( isset( $_POST['dev_nahian_add_promo_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_promo_verify', 'dev_nahian_promo_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $code = strtoupper( sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ) );
        $code_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_promos} WHERE code = %s", $code ) );

        if ( $code_exists ) {
            echo '<div class="notice notice-error is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'A promotional coupon with this code already exists.', 'dev-nahian-car' ) . '</p></div>';
        } else {
            $inserted = $wpdb->insert(
                $table_promos,
                array(
                    'code'           => $code,
                    'discount_type'  => sanitize_key( $_POST['discount_type'] ?? 'percentage' ),
                    'discount_value' => floatval( $_POST['discount_value'] ?? 0.00 ),
                    'expiry_date'    => ! empty( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : null,
                    'usage_limit'    => absint( $_POST['usage_limit'] ?? 100 ),
                    'status'         => sanitize_key( $_POST['status'] ?? 'active' ),
                ),
                array( '%s', '%s', '%f', '%s', '%d', '%s' )
            );

            if ( false !== $inserted ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'promos', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

if ( isset( $_POST['dev_nahian_edit_promo_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_promo_' . $id, 'dev_nahian_promo_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $code = strtoupper( sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ) );
        $code_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_promos} WHERE code = %s AND id != %d", $code, $id ) );

        if ( $code_exists ) {
            echo '<div class="notice notice-error is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Another promo code is already registered under this identifier.', 'dev-nahian-car' ) . '</p></div>';
        } else {
            $updated = $wpdb->update(
                $table_promos,
                array(
                    'code'           => $code,
                    'discount_type'  => sanitize_key( $_POST['discount_type'] ?? 'percentage' ),
                    'discount_value' => floatval( $_POST['discount_value'] ?? 0.00 ),
                    'expiry_date'    => ! empty( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : null,
                    'usage_limit'    => absint( $_POST['usage_limit'] ?? 100 ),
                    'status'         => sanitize_key( $_POST['status'] ?? 'active' ),
                ),
                array( 'id' => $id ),
                array( '%s', '%s', '%f', '%s', '%d', '%s' ),
                array( '%d' )
            );

            if ( false !== $updated ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'promos', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

// 2. Render Form View
$is_edit = ( 'edit' === $action && $id > 0 );
$promo   = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_promos} WHERE id = %d", $id ), ARRAY_A ) : array();
?>
<style>
    .dn-promo-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }
    .dn-promo-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    @media (max-width: 960px) {
        .dn-promo-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .dn-promo-grid { grid-template-columns: 1fr; }
    }
    .col-full { grid-column: 1 / -1; }
    .dn-form-heading {
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
    .dn-form-heading:first-of-type {
        border-top: none;
        padding-top: 0;
        margin-top: 0;
    }
    .dn-field-box {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .dn-field-box label {
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #334155 !important;
    }
    .dev-nahian-input, .dev-nahian-select {
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0 14px !important;
        font-size: 13.5px !important;
        height: 42px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
</style>

<div class="dn-promo-bar">
    <div class="dn-promo-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        <h2 class="dn-promo-title">
            <?php echo $is_edit ? sprintf( esc_html__( 'Edit Promo Code: %s', 'dev-nahian-car' ), esc_html( $promo['code'] ?? '' ) ) : esc_html__( 'Create Promo Code', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<div class="dn-promo-card">
    <form method="post" action="">
        <?php 
        if ( $is_edit ) {
            wp_nonce_field( 'dev_nahian_edit_promo_' . $id, 'dev_nahian_promo_nonce' );
            echo '<input type="hidden" name="dev_nahian_edit_promo_submit" value="1">';
        } else {
            wp_nonce_field( 'dev_nahian_add_promo_verify', 'dev_nahian_promo_nonce' );
            echo '<input type="hidden" name="dev_nahian_add_promo_submit" value="1">';
        }
        ?>

        <div class="dn-promo-grid">
            <div class="dn-form-heading"><?php esc_html_e( '1. Coupon Configuration', 'dev-nahian-car' ); ?></div>

            <div class="dn-field-box">
                <label for="code"><?php esc_html_e( 'Coupon Code Identifier', 'dev-nahian-car' ); ?></label>
                <input type="text" id="code" name="code" class="dev-nahian-input" value="<?php echo esc_attr( $promo['code'] ?? '' ); ?>" placeholder="e.g. SUMMERSPECIAL20" style="text-transform: uppercase;" required>
            </div>

            <div class="dn-field-box">
                <label for="discount_type"><?php esc_html_e( 'Discount Mechanism', 'dev-nahian-car' ); ?></label>
                <select id="discount_type" name="discount_type" class="dev-nahian-select">
                    <option value="percentage" <?php selected( $promo['discount_type'] ?? 'percentage', 'percentage' ); ?>><?php esc_html_e( 'Percentage Discount (%)', 'dev-nahian-car' ); ?></option>
                    <option value="fixed" <?php selected( $promo['discount_type'] ?? '', 'fixed' ); ?>><?php printf( esc_html__( 'Fixed Amount (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></option>
                </select>
            </div>

            <div class="dn-field-box">
                <label for="discount_value"><?php esc_html_e( 'Discount Value', 'dev-nahian-car' ); ?></label>
                <input type="number" step="0.01" id="discount_value" name="discount_value" class="dev-nahian-input" value="<?php echo esc_attr( $promo['discount_value'] ?? '' ); ?>" placeholder="15.00" required>
            </div>

            <div class="dn-form-heading"><?php esc_html_e( '2. Validity & Limitations', 'dev-nahian-car' ); ?></div>

            <div class="dn-field-box">
                <label for="expiry_date"><?php esc_html_e( 'Expiration Date', 'dev-nahian-car' ); ?></label>
                <input type="date" id="expiry_date" name="expiry_date" class="dev-nahian-input" value="<?php echo esc_attr( (string) ( $promo['expiry_date'] ?? '' ) ); ?>">
            </div>

            <div class="dn-field-box">
                <label for="usage_limit"><?php esc_html_e( 'Redemption Limit', 'dev-nahian-car' ); ?></label>
                <input type="number" id="usage_limit" name="usage_limit" class="dev-nahian-input" value="<?php echo esc_attr( $promo['usage_limit'] ?? 100 ); ?>" min="1">
            </div>

            <div class="dn-field-box">
                <label for="status"><?php esc_html_e( 'Coupon Status', 'dev-nahian-car' ); ?></label>
                <select id="status" name="status" class="dev-nahian-select">
                    <option value="active" <?php selected( $promo['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'dev-nahian-car' ); ?></option>
                    <option value="expired" <?php selected( $promo['status'] ?? '', 'expired' ); ?>><?php esc_html_e( 'Expired', 'dev-nahian-car' ); ?></option>
                    <option value="disabled" <?php selected( $promo['status'] ?? '', 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'dev-nahian-car' ); ?></option>
                </select>
            </div>
        </div>

        <p style="margin: 24px 0 0 0; display: flex; gap: 10px;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; height:38px; padding:0 20px; font-weight:700;">
                <?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Save Promo Code', 'dev-nahian-car' ); ?>
            </button>
            <a href="<?php echo esc_url( $base ); ?>" class="button" style="height:38px; line-height:36px; padding:0 14px;"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        </p>
    </form>
</div>
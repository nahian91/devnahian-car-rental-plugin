<?php
/**
 * Admin View Tab: Global Extras, Locations & Specifications
 * Synchronized with Fleet Cars Registry workstation fields.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_extras = $wpdb->prefix . 'dev_nahian_extras';
$currency     = get_option( 'dev_nahian_currency', 'USD' );
$notice       = '';

// Permitted dashicon classes whitelist
$allowed_icons = array(
    'dashicons-location', 'dashicons-location-alt', 'dashicons-admin-site', 'dashicons-admin-site-alt',
    'dashicons-airplane', 'dashicons-car', 'dashicons-admin-tools', 'dashicons-performance',
    'dashicons-dashboard', 'dashicons-gauge', 'dashicons-star-filled', 'dashicons-star-half',
    'dashicons-star-empty', 'dashicons-awards', 'dashicons-shield', 'dashicons-shield-alt',
    'dashicons-unlock', 'dashicons-lock', 'dashicons-key', 'dashicons-cart',
    'dashicons-money-alt', 'dashicons-tickets-alt', 'dashicons-clock', 'dashicons-calendar-alt',
    'dashicons-smartphone', 'dashicons-phone', 'dashicons-email-alt', 'dashicons-rest-api',
    'dashicons-cloud', 'dashicons-thumbs-up', 'dashicons-heart', 'dashicons-yes-alt',
    'dashicons-controls-play', 'dashicons-controls-volumeon', 'dashicons-lightbulb', 'dashicons-visibility',
    'dashicons-flag', 'dashicons-randomize', 'dashicons-image-filter', 'dashicons-marker',
);

// =========================================================================
// 1. SAVE HANDLER
// =========================================================================
if ( isset( $_POST['dev_nahian_save_extras_ui'] ) ) {
    check_admin_referer( 'dev_nahian_extras_ui_verify', 'dev_nahian_extras_ui_nonce' );

    if ( current_user_can( 'manage_options' ) ) {

        // 1. Locations with Delivery Radius Fees
        $locations = array();
        if ( ! empty( $_POST['locations'] ) && is_array( $_POST['locations'] ) ) {
            foreach ( $_POST['locations'] as $loc ) {
                $address = sanitize_text_field( wp_unslash( $loc['address'] ?? '' ) );
                $fee     = floatval( $loc['fee'] ?? 0.00 );
                $icon    = sanitize_text_field( wp_unslash( $loc['icon'] ?? 'dashicons-location' ) );

                if ( ! in_array( $icon, $allowed_icons, true ) ) {
                    $icon = 'dashicons-location';
                }

                if ( ! empty( $address ) ) {
                    $locations[] = array(
                        'address' => $address,
                        'fee'     => $fee,
                        'icon'    => $icon,
                    );
                }
            }
        }
        update_option( 'dev_nahian_car_extras_locations', $locations, false );

        // 2. Technical Specifications
        $specs = array();
        if ( ! empty( $_POST['specs'] ) && is_array( $_POST['specs'] ) ) {
            foreach ( $_POST['specs'] as $spec ) {
                $label = sanitize_text_field( wp_unslash( $spec['label'] ?? '' ) );
                $value = sanitize_text_field( wp_unslash( $spec['value'] ?? '' ) );
                $icon  = sanitize_text_field( wp_unslash( $spec['icon'] ?? 'dashicons-admin-tools' ) );

                if ( ! in_array( $icon, $allowed_icons, true ) ) {
                    $icon = 'dashicons-admin-tools';
                }

                if ( ! empty( $label ) || ! empty( $value ) ) {
                    $specs[] = array(
                        'label' => $label,
                        'value' => $value,
                        'icon'  => $icon,
                    );
                }
            }
        }
        update_option( 'dev_nahian_car_extras_specs', $specs, false );

        // 3. Comfort Amenities
        $amenities = array();
        if ( ! empty( $_POST['amenities'] ) && is_array( $_POST['amenities'] ) ) {
            foreach ( $_POST['amenities'] as $amenity ) {
                $title = sanitize_text_field( wp_unslash( $amenity['title'] ?? '' ) );
                $icon  = sanitize_text_field( wp_unslash( $amenity['icon'] ?? 'dashicons-star-filled' ) );

                if ( ! in_array( $icon, $allowed_icons, true ) ) {
                    $icon = 'dashicons-star-filled';
                }

                if ( ! empty( $title ) ) {
                    $amenities[] = array(
                        'title' => $title,
                        'icon'  => $icon,
                    );
                }
            }
        }
        update_option( 'dev_nahian_car_extras_amenities', $amenities, false );

        // 4. Insurance Plans
        $insurance = array();
        if ( ! empty( $_POST['insurance'] ) && is_array( $_POST['insurance'] ) ) {
            foreach ( $_POST['insurance'] as $ins ) {
                $title   = sanitize_text_field( wp_unslash( $ins['title'] ?? '' ) );
                $desc    = sanitize_text_field( wp_unslash( $ins['desc'] ?? '' ) );
                $price   = floatval( $ins['price'] ?? 0 );
                $enabled = isset( $ins['enabled'] ) ? 1 : 0;

                if ( ! empty( $title ) ) {
                    $insurance[] = array(
                        'title'   => $title,
                        'desc'    => $desc,
                        'price'   => $price,
                        'enabled' => $enabled,
                    );
                }
            }
        }
        update_option( 'dev_nahian_car_extras_insurance', $insurance, false );

        // 5. Cross-Border Travel Clearance
        $border = array();
        if ( ! empty( $_POST['border'] ) && is_array( $_POST['border'] ) ) {
            foreach ( $_POST['border'] as $b ) {
                $dest    = sanitize_text_field( wp_unslash( $b['destination'] ?? '' ) );
                $price   = floatval( $b['price'] ?? 0 );
                $enabled = isset( $b['enabled'] ) ? 1 : 0;

                if ( ! empty( $dest ) ) {
                    $border[] = array(
                        'destination' => $dest,
                        'price'       => $price,
                        'enabled'     => $enabled,
                    );
                }
            }
        }
        update_option( 'dev_nahian_car_extras_border', $border, false );

        // 6. Paid Rental Add-ons with Quantity & Mandatory Controls
        $paid_extras = array();
        if ( ! empty( $_POST['paid_extras'] ) && is_array( $_POST['paid_extras'] ) ) {
            foreach ( $_POST['paid_extras'] as $item ) {
                $name      = sanitize_text_field( wp_unslash( $item['name'] ?? '' ) );
                $price     = floatval( $item['price'] ?? 0.00 );
                $type      = sanitize_key( $item['type'] ?? 'daily' );
                $mandatory = isset( $item['mandatory'] ) ? 1 : 0;
                $max_qty   = max( 1, absint( $item['max_qty'] ?? 1 ) );
                $icon      = sanitize_text_field( wp_unslash( $item['icon'] ?? 'dashicons-cart' ) );

                if ( ! in_array( $icon, $allowed_icons, true ) ) {
                    $icon = 'dashicons-cart';
                }

                if ( ! empty( $name ) ) {
                    $paid_extras[] = array(
                        'name'      => $name,
                        'price'     => $price,
                        'type'      => $type,
                        'mandatory' => $mandatory,
                        'max_qty'   => $max_qty,
                        'icon'      => $icon,
                    );
                }
            }
        }
        update_option( 'dev_nahian_car_paid_extras', $paid_extras, false );

        // 7. Age Restrictions & License Verification Flags
        $policy_rules = array(
            'min_driver_age'  => absint( $_POST['policy_min_age'] ?? 25 ),
            'require_license' => sanitize_text_field( wp_unslash( $_POST['policy_license'] ?? 'Standard Class C & Clean Abstract' ) ),
            'supercar_tier'   => absint( $_POST['policy_supercar_age'] ?? 30 ),
        );
        update_option( 'dev_nahian_car_policy_rules', $policy_rules, false );

        $notice = '<div class="notice notice-success is-dismissible" style="margin: 15px 0 25px;"><p><strong>' . esc_html__( 'Settings Saved:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'All global vehicle options, staging hubs, delivery fees, add-on rules, and license policies were synchronized successfully.', 'dev-nahian-car' ) . '</p></div>';
    }
}

// =========================================================================
// 2. FETCH STORED VALUES (ZERO DEFAULTS)
// =========================================================================
$locations   = get_option( 'dev_nahian_car_extras_locations', array() );
$specs       = get_option( 'dev_nahian_car_extras_specs', array() );
$amenities   = get_option( 'dev_nahian_car_extras_amenities', array() );
$insurance   = get_option( 'dev_nahian_car_extras_insurance', array() );
$border      = get_option( 'dev_nahian_car_extras_border', array() );
$paid_extras = get_option( 'dev_nahian_car_paid_extras', array() );
$policy_rules= get_option( 'dev_nahian_car_policy_rules', array( 'min_driver_age' => 25, 'require_license' => 'Standard Class C', 'supercar_tier' => 30 ) );
?>

<style>
    .dn-meta-wrapper {
        max-width: 980px;
        margin: 20px 20px 40px 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #0f172a;
    }

    .dn-card-section {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    .dn-card-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 18px 0;
    }

    .dn-card-title-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .dn-card-title .dashicons {
        color: #2563eb;
        font-size: 20px;
        width: 20px;
        height: 20px;
    }

    .dn-row-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 16px;
    }

    .dn-item-row {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        transition: border-color 0.15s ease;
        flex-wrap: wrap;
    }

    @media(min-width: 768px) {
        .dn-item-row { flex-wrap: nowrap; }
    }

    .dn-drag-handle {
        color: #cbd5e1;
        cursor: grab;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .dn-drag-handle .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    .dn-icon-picker-btn {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        color: #1e293b;
        cursor: pointer;
        padding: 0;
        transition: all 0.15s ease;
    }
    .dn-icon-picker-btn:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
        color: #2563eb;
    }
    .dn-icon-picker-btn .dashicons {
        font-size: 20px;
        width: 20px;
        height: 20px;
    }

    .dn-input {
        height: 38px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        font-size: 13.5px;
        color: #1e293b;
        outline: none;
        box-sizing: border-box;
        width: 100%;
        transition: border-color 0.15s ease;
    }
    .dn-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
    }
    .dn-input-label {
        width: 200px;
        min-width: 160px;
        flex-shrink: 0;
    }
    .dn-input-price {
        width: 110px;
        min-width: 90px;
        flex-shrink: 0;
    }
    .dn-input-qty {
        width: 80px;
        min-width: 70px;
        flex-shrink: 0;
    }
    .dn-select-type {
        width: 110px;
        min-width: 90px;
        flex-shrink: 0;
        height: 38px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
    }

    .dn-btn-remove {
        background: none;
        border: none;
        color: #f87171;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.15s ease;
    }
    .dn-btn-remove .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    .dn-btn-remove:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    .dn-btn-add-trigger {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #2563eb;
        background: none;
        border: none;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        padding: 6px 0;
        transition: color 0.15s ease;
    }
    .dn-btn-add-trigger .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    .dn-btn-add-trigger:hover {
        color: #1d4ed8;
    }

    /* Modal Backdrop & Picker */
    .dn-modal-backdrop {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.5);
        z-index: 100000;
        align-items: center;
        justify-content: center;
    }
    .dn-modal-backdrop.is-open {
        display: flex;
    }
    .dn-modal-box {
        background: #ffffff;
        border-radius: 10px;
        width: 580px;
        max-width: 90%;
        max-height: 80vh;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .dn-modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dn-modal-header h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
    }
    .dn-modal-body {
        padding: 20px;
        overflow-y: auto;
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 10px;
    }
    .dn-icon-option {
        width: 44px;
        height: 44px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: #ffffff;
        transition: all 0.15s ease;
    }
    .dn-icon-option:hover {
        border-color: #2563eb;
        background: #eff6ff;
        color: #2563eb;
        transform: scale(1.08);
    }
    .dn-icon-option .dashicons {
        font-size: 22px;
        width: 22px;
        height: 22px;
    }
</style>

<div class="dn-meta-wrapper">

    <?php if ( ! empty( $notice ) ) : ?>
        <?php echo wp_kses_post( $notice ); ?>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'dev_nahian_extras_ui_verify', 'dev_nahian_extras_ui_nonce' ); ?>
        <input type="hidden" name="dev_nahian_save_extras_ui" value="1">

        <!-- 1. PICKUP & DROP-OFF LOCATIONS WITH RADIUS FEES -->
        <div class="dn-card-section">
            <h3 class="dn-card-title">
                <span class="dn-card-title-left">
                    <span class="dashicons dashicons-location"></span>
                    <?php esc_html_e( 'Pickup & Drop-off Locations & Delivery Surcharges', 'dev-nahian-car' ); ?>
                </span>
            </h3>

            <div class="dn-row-list" id="dn-locations-list">
                <?php if ( ! empty( $locations ) ) : ?>
                    <?php foreach ( $locations as $index => $loc ) : 
                        $address_val = is_array( $loc ) ? ( $loc['address'] ?? '' ) : $loc;
                        $fee_val     = is_array( $loc ) ? ( $loc['fee'] ?? 0.00 ) : 0.00;
                        $icon_val    = is_array( $loc ) ? ( $loc['icon'] ?? 'dashicons-location' ) : 'dashicons-location';
                    ?>
                        <div class="dn-item-row">
                            <div class="dn-drag-handle" title="<?php esc_attr_e( 'Move', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-move"></span>
                            </div>
                            <button type="button" class="dn-icon-picker-btn" onclick="dnOpenIconPicker(this);" title="<?php esc_attr_e( 'Change Icon', 'dev-nahian-car' ); ?>">
                                <span class="dashicons <?php echo esc_attr( $icon_val ); ?>"></span>
                            </button>
                            <input type="hidden" name="locations[<?php echo esc_attr( (string) $index ); ?>][icon]" class="dn-row-icon-input" value="<?php echo esc_attr( $icon_val ); ?>">
                            <input type="text" name="locations[<?php echo esc_attr( (string) $index ); ?>][address]" class="dn-input" value="<?php echo esc_attr( $address_val ); ?>" placeholder="<?php esc_attr_e( 'Hub address or zone...', 'dev-nahian-car' ); ?>" style="flex-grow:1;">
                            <input type="number" step="0.01" name="locations[<?php echo esc_attr( (string) $index ); ?>][fee]" class="dn-input dn-input-price" value="<?php echo esc_attr( (string) $fee_val ); ?>" placeholder="<?php printf( esc_attr__( 'Fee (%s)', 'dev-nahian-car' ), esc_attr( $currency ) ); ?>">
                            <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php esc_attr_e( 'Delete Location', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="dn-btn-add-trigger" onclick="dnAddLocationRow();">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Location Hub', 'dev-nahian-car' ); ?>
            </button>
        </div>

        <!-- 2. TECHNICAL SPECIFICATIONS -->
        <div class="dn-card-section">
            <h3 class="dn-card-title">
                <span class="dn-card-title-left">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e( 'Technical Specifications', 'dev-nahian-car' ); ?>
                </span>
            </h3>

            <div class="dn-row-list" id="dn-specs-list">
                <?php if ( ! empty( $specs ) ) : ?>
                    <?php foreach ( $specs as $i => $s ) : 
                        $spec_icon = ! empty( $s['icon'] ) ? $s['icon'] : 'dashicons-admin-tools';
                    ?>
                        <div class="dn-item-row">
                            <div class="dn-drag-handle" title="<?php esc_attr_e( 'Move', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-move"></span>
                            </div>
                            <button type="button" class="dn-icon-picker-btn" onclick="dnOpenIconPicker(this);" title="<?php esc_attr_e( 'Change Icon', 'dev-nahian-car' ); ?>">
                                <span class="dashicons <?php echo esc_attr( $spec_icon ); ?>"></span>
                            </button>
                            <input type="hidden" name="specs[<?php echo esc_attr( (string) $i ); ?>][icon]" class="dn-row-icon-input" value="<?php echo esc_attr( $spec_icon ); ?>">
                            <input type="text" name="specs[<?php echo esc_attr( (string) $i ); ?>][label]" class="dn-input dn-input-label" value="<?php echo esc_attr( $s['label'] ); ?>" placeholder="<?php esc_attr_e( 'Specification label', 'dev-nahian-car' ); ?>">
                            <input type="text" name="specs[<?php echo esc_attr( (string) $i ); ?>][value]" class="dn-input" value="<?php echo esc_attr( $s['value'] ); ?>" placeholder="<?php esc_attr_e( 'Default Value', 'dev-nahian-car' ); ?>" style="flex-grow:1;">
                            <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php esc_attr_e( 'Delete Specification', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="dn-btn-add-trigger" onclick="dnAddSpecRow();">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Specification Field', 'dev-nahian-car' ); ?>
            </button>
        </div>

        <!-- 3. COMFORT AMENITIES -->
        <div class="dn-card-section">
            <h3 class="dn-card-title">
                <span class="dn-card-title-left">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e( 'Comfort Amenities', 'dev-nahian-car' ); ?>
                </span>
            </h3>

            <div class="dn-row-list" id="dn-amenities-list">
                <?php if ( ! empty( $amenities ) ) : ?>
                    <?php foreach ( $amenities as $j => $a ) : 
                        $amenity_icon = ! empty( $a['icon'] ) ? $a['icon'] : 'dashicons-star-filled';
                    ?>
                        <div class="dn-item-row">
                            <div class="dn-drag-handle" title="<?php esc_attr_e( 'Move', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-move"></span>
                            </div>
                            <button type="button" class="dn-icon-picker-btn" onclick="dnOpenIconPicker(this);" title="<?php esc_attr_e( 'Change Icon', 'dev-nahian-car' ); ?>">
                                <span class="dashicons <?php echo esc_attr( $amenity_icon ); ?>"></span>
                            </button>
                            <input type="hidden" name="amenities[<?php echo esc_attr( (string) $j ); ?>][icon]" class="dn-row-icon-input" value="<?php echo esc_attr( $amenity_icon ); ?>">
                            <input type="text" name="amenities[<?php echo esc_attr( (string) $j ); ?>][title]" class="dn-input" value="<?php echo esc_attr( $a['title'] ); ?>" placeholder="<?php esc_attr_e( 'Amenity title', 'dev-nahian-car' ); ?>" style="flex-grow:1;">
                            <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php esc_attr_e( 'Delete Amenity', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="dn-btn-add-trigger" onclick="dnAddAmenityRow();">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Amenity Option', 'dev-nahian-car' ); ?>
            </button>
        </div>

        <!-- 4. INSURANCE & PROTECTION PLANS -->
        <div class="dn-card-section">
            <h3 class="dn-card-title">
                <span class="dn-card-title-left">
                    <span class="dashicons dashicons-shield"></span>
                    <?php esc_html_e( 'Insurance & Protection Plans', 'dev-nahian-car' ); ?>
                </span>
            </h3>

            <div class="dn-row-list" id="dn-insurance-list">
                <?php if ( ! empty( $insurance ) ) : ?>
                    <?php foreach ( $insurance as $ins_idx => $ins_item ) : 
                        $is_checked = ! empty( $ins_item['enabled'] );
                    ?>
                        <div class="dn-item-row" style="align-items: center;">
                            <div class="dn-drag-handle" title="<?php esc_attr_e( 'Move', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-move"></span>
                            </div>
                            <label style="display:flex; align-items:center; gap:4px; font-size:12px; font-weight:600;">
                                <input type="checkbox" name="insurance[<?php echo esc_attr( (string) $ins_idx ); ?>][enabled]" value="1" <?php checked( $is_checked, 1 ); ?> style="width:16px; height:16px; cursor:pointer;"> Active
                            </label>
                            <div style="display: flex; flex-direction: column; gap: 4px; flex-grow: 1;">
                                <input type="text" name="insurance[<?php echo esc_attr( (string) $ins_idx ); ?>][title]" class="dn-input" value="<?php echo esc_attr( $ins_item['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Plan Title', 'dev-nahian-car' ); ?>" style="font-weight:700;">
                                <input type="text" name="insurance[<?php echo esc_attr( (string) $ins_idx ); ?>][desc]" class="dn-input" value="<?php echo esc_attr( $ins_item['desc'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Plan description...', 'dev-nahian-car' ); ?>" style="font-size:12px; height:32px;">
                            </div>
                            <input type="number" step="0.01" name="insurance[<?php echo esc_attr( (string) $ins_idx ); ?>][price]" class="dn-input dn-input-price" value="<?php echo esc_attr( (string) ( $ins_item['price'] ?? 0 ) ); ?>" placeholder="0.00">
                            <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php esc_attr_e( 'Delete Plan', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="dn-btn-add-trigger" onclick="dnAddInsuranceRow();">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Insurance Plan', 'dev-nahian-car' ); ?>
            </button>
        </div>

        <!-- 5. CROSS-BORDER TRAVEL CLEARANCE -->
        <div class="dn-card-section">
            <h3 class="dn-card-title">
                <span class="dn-card-title-left">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <?php esc_html_e( 'Cross-Border Travel Clearance', 'dev-nahian-car' ); ?>
                </span>
            </h3>

            <div class="dn-row-list" id="dn-border-list">
                <?php if ( ! empty( $border ) ) : ?>
                    <?php foreach ( $border as $b_idx => $b_item ) : 
                        $b_checked = ! empty( $b_item['enabled'] );
                    ?>
                        <div class="dn-item-row" style="align-items: center;">
                            <div class="dn-drag-handle" title="<?php esc_attr_e( 'Move', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-move"></span>
                            </div>
                            <label style="display:flex; align-items:center; gap:4px; font-size:12px; font-weight:600;">
                                <input type="checkbox" name="border[<?php echo esc_attr( (string) $b_idx ); ?>][enabled]" value="1" <?php checked( $b_checked, 1 ); ?> style="width:16px; height:16px; cursor:pointer;"> Active
                            </label>
                            <div style="flex-grow: 1;">
                                <input type="text" name="border[<?php echo esc_attr( (string) $b_idx ); ?>][destination]" class="dn-input" value="<?php echo esc_attr( $b_item['destination'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Crossing Destination (e.g. Canada / Europe)', 'dev-nahian-car' ); ?>">
                            </div>
                            <input type="number" step="0.01" name="border[<?php echo esc_attr( (string) $b_idx ); ?>][price]" class="dn-input dn-input-price" value="<?php echo esc_attr( (string) ( $b_item['price'] ?? 0 ) ); ?>" placeholder="0.00">
                            <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php esc_attr_e( 'Delete Destination', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="dn-btn-add-trigger" onclick="dnAddBorderRow();">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Destination', 'dev-nahian-car' ); ?>
            </button>
        </div>

        <!-- 6. PAID RENTAL ADD-ONS (WITH QUANTITY & MANDATORY CONTROLS) -->
        <div class="dn-card-section">
            <h3 class="dn-card-title">
                <span class="dn-card-title-left">
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php esc_html_e( 'Paid Rental Add-Ons & Extras (Quantity & Mandatory Rules)', 'dev-nahian-car' ); ?>
                </span>
            </h3>

            <div class="dn-row-list" id="dn-paid-extras-list">
                <?php if ( ! empty( $paid_extras ) ) : ?>
                    <?php foreach ( $paid_extras as $k => $pe ) : 
                        $paid_icon   = ! empty( $pe['icon'] ) ? $pe['icon'] : 'dashicons-cart';
                        $is_mandatory= ! empty( $pe['mandatory'] );
                        $max_qty     = max( 1, absint( $pe['max_qty'] ?? 1 ) );
                    ?>
                        <div class="dn-item-row" style="align-items: center;">
                            <div class="dn-drag-handle" title="<?php esc_attr_e( 'Move', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-move"></span>
                            </div>
                            <button type="button" class="dn-icon-picker-btn" onclick="dnOpenIconPicker(this);" title="<?php esc_attr_e( 'Change Icon', 'dev-nahian-car' ); ?>">
                                <span class="dashicons <?php echo esc_attr( $paid_icon ); ?>"></span>
                            </button>
                            <input type="hidden" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][icon]" class="dn-row-icon-input" value="<?php echo esc_attr( $paid_icon ); ?>">
                            
                            <input type="text" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][name]" class="dn-input" value="<?php echo esc_attr( $pe['name'] ); ?>" placeholder="<?php esc_attr_e( 'Service name (e.g. GPS, Child Seat)', 'dev-nahian-car' ); ?>" style="flex-grow:1;">
                            
                            <input type="number" step="0.01" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][price]" class="dn-input dn-input-price" value="<?php echo esc_attr( (string) $pe['price'] ); ?>" placeholder="<?php printf( esc_attr__( 'Price (%s)', 'dev-nahian-car' ), esc_attr( $currency ) ); ?>">
                            
                            <select name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][type]" class="dn-select-type">
                                <option value="daily" <?php selected( $pe['type'], 'daily' ); ?>><?php esc_html_e( 'Per Day', 'dev-nahian-car' ); ?></option>
                                <option value="fixed" <?php selected( $pe['type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed Fee', 'dev-nahian-car' ); ?></option>
                            </select>

                            <div style="display:flex; align-items:center; gap:6px;" title="<?php esc_attr_e( 'Max selectable quantity', 'dev-nahian-car' ); ?>">
                                <span style="font-size:11px; font-weight:700; color:#64748b;">Max Qty:</span>
                                <input type="number" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][max_qty]" class="dn-input dn-input-qty" value="<?php echo esc_attr( (string) $max_qty ); ?>" min="1" max="10">
                            </div>

                            <label style="display:flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#b91c1c;" title="<?php esc_attr_e( 'Check if mandatory for all bookings', 'dev-nahian-car' ); ?>">
                                <input type="checkbox" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][mandatory]" value="1" <?php checked( $is_mandatory, 1 ); ?> style="width:16px; height:16px; cursor:pointer;"> Mandatory
                            </label>

                            <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php esc_attr_e( 'Delete Add-on', 'dev-nahian-car' ); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="dn-btn-add-trigger" onclick="dnAddPaidExtraRow();">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Paid Extra', 'dev-nahian-car' ); ?>
            </button>
        </div>

        <!-- 7. AGE RESTRICTIONS & LICENSE POLICIES -->
        <div class="dn-card-section">
            <h3 class="dn-card-title">
                <span class="dn-card-title-left">
                    <span class="dashicons dashicons-id"></span>
                    <?php esc_html_e( 'Driver Age Restrictions & License Verification Policies', 'dev-nahian-car' ); ?>
                </span>
            </h3>

            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:11px; font-weight:700; text-transform:uppercase; color:#334155;"><?php esc_html_e( 'Minimum Driver Age', 'dev-nahian-car' ); ?></label>
                    <input type="number" name="policy_min_age" class="dn-input" value="<?php echo esc_attr( (string) ( $policy_rules['min_driver_age'] ?? 25 ) ); ?>" min="18" max="99">
                </div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:11px; font-weight:700; text-transform:uppercase; color:#334155;"><?php esc_html_e( 'Supercar Tier Age Requirement', 'dev-nahian-car' ); ?></label>
                    <input type="number" name="policy_supercar_age" class="dn-input" value="<?php echo esc_attr( (string) ( $policy_rules['supercar_tier'] ?? 30 ) ); ?>" min="18" max="99">
                </div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:11px; font-weight:700; text-transform:uppercase; color:#334155;"><?php esc_html_e( 'Required License Endorsement', 'dev-nahian-car' ); ?></label>
                    <input type="text" name="policy_license" class="dn-input" value="<?php echo esc_attr( $policy_rules['require_license'] ?? 'Standard Class C' ); ?>" placeholder="e.g. Class C & Clean Record">
                </div>
            </div>
        </div>

        <div style="margin: 28px 0 10px; padding-top: 18px; border-top: 1px solid #e2e8f0;">
            <button type="submit" class="button button-primary button-large" style="background:#0f172a; border-color:#0f172a; padding:6px 28px; font-weight:700; font-size:14px; border-radius:6px; cursor:pointer;">
                <?php esc_html_e( 'Save All Extras & Specifications', 'dev-nahian-car' ); ?>
            </button>
        </div>

    </form>
</div>

<!-- 8. DASHICONS POPUP MODAL PICKER -->
<div class="dn-modal-backdrop" id="dnIconModal" onclick="dnCloseIconPicker(event);">
    <div class="dn-modal-box" onclick="event.stopPropagation();">
        <div class="dn-modal-header">
            <h4><?php esc_html_e( 'Choose Dashicon', 'dev-nahian-car' ); ?></h4>
            <button type="button" class="button-link" onclick="dnCloseIconModal();" style="font-size:20px; line-height:1; text-decoration:none;">&times;</button>
        </div>
        <div class="dn-modal-body">
            <?php foreach ( $allowed_icons as $icon_name ) : ?>
                <div class="dn-icon-option" onclick="dnSelectIcon('<?php echo esc_js( $icon_name ); ?>');" title="<?php echo esc_attr( $icon_name ); ?>">
                    <span class="dashicons <?php echo esc_attr( $icon_name ); ?>"></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    var currentIconTriggerBtn = null;

    function dnOpenIconPicker(btn) {
        currentIconTriggerBtn = btn;
        document.getElementById('dnIconModal').classList.add('is-open');
    }

    function dnCloseIconModal() {
        document.getElementById('dnIconModal').classList.remove('is-open');
        currentIconTriggerBtn = null;
    }

    function dnCloseIconPicker(e) {
        if (e.target.id === 'dnIconModal') {
            dnCloseIconModal();
        }
    }

    function dnSelectIcon(iconClass) {
        if (currentIconTriggerBtn) {
            var iconElem = currentIconTriggerBtn.querySelector('.dashicons');
            if (iconElem) {
                iconElem.className = 'dashicons ' + iconClass;
            }
            var row = currentIconTriggerBtn.closest('.dn-item-row');
            if (row) {
                var hiddenInput = row.querySelector('.dn-row-icon-input');
                if (hiddenInput) {
                    hiddenInput.value = iconClass;
                }
            }
        }
        dnCloseIconModal();
    }

    function dnRemoveRow(button) {
        var row = button.closest('.dn-item-row');
        if (row) {
            row.remove();
        }
    }

    function dnAddLocationRow() {
        var container = document.getElementById('dn-locations-list');
        var idx = Date.now();
        var html = `
            <div class="dn-item-row">
                <div class="dn-drag-handle"><span class="dashicons dashicons-move"></span></div>
                <button type="button" class="dn-icon-picker-btn" onclick="dnOpenIconPicker(this);" title="<?php echo esc_js( __( 'Change Icon', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-location"></span>
                </button>
                <input type="hidden" name="locations[${idx}][icon]" class="dn-row-icon-input" value="dashicons-location">
                <input type="text" name="locations[${idx}][address]" class="dn-input" placeholder="<?php echo esc_js( __( 'Hub address or zone...', 'dev-nahian-car' ) ); ?>" style="flex-grow:1;">
                <input type="number" step="0.01" name="locations[${idx}][fee]" class="dn-input dn-input-price" placeholder="<?php printf( esc_js( __( 'Fee (%s)', 'dev-nahian-car' ) ), esc_js( $currency ) ); ?>">
                <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php echo esc_js( __( 'Delete Location', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        container.lastElementChild.querySelector('.dn-input').focus();
    }

    function dnAddSpecRow() {
        var container = document.getElementById('dn-specs-list');
        var idx = Date.now();
        var html = `
            <div class="dn-item-row">
                <div class="dn-drag-handle"><span class="dashicons dashicons-move"></span></div>
                <button type="button" class="dn-icon-picker-btn" onclick="dnOpenIconPicker(this);" title="<?php echo esc_js( __( 'Change Icon', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-admin-tools"></span>
                </button>
                <input type="hidden" name="specs[${idx}][icon]" class="dn-row-icon-input" value="dashicons-admin-tools">
                <input type="text" name="specs[${idx}][label]" class="dn-input dn-input-label" placeholder="<?php echo esc_js( __( 'Specification label', 'dev-nahian-car' ) ); ?>">
                <input type="text" name="specs[${idx}][value]" class="dn-input" placeholder="<?php echo esc_js( __( 'Default Value', 'dev-nahian-car' ) ); ?>" style="flex-grow:1;">
                <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php echo esc_js( __( 'Delete Specification', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        container.lastElementChild.querySelector('.dn-input-label').focus();
    }

    function dnAddAmenityRow() {
        var container = document.getElementById('dn-amenities-list');
        var idx = Date.now();
        var html = `
            <div class="dn-item-row">
                <div class="dn-drag-handle"><span class="dashicons dashicons-move"></span></div>
                <button type="button" class="dn-icon-picker-btn" onclick="dnOpenIconPicker(this);" title="<?php echo esc_js( __( 'Change Icon', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-star-filled"></span>
                </button>
                <input type="hidden" name="amenities[${idx}][icon]" class="dn-row-icon-input" value="dashicons-star-filled">
                <input type="text" name="amenities[${idx}][title]" class="dn-input" placeholder="<?php echo esc_js( __( 'Amenity title', 'dev-nahian-car' ) ); ?>" style="flex-grow:1;">
                <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php echo esc_js( __( 'Delete Amenity', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        container.lastElementChild.querySelector('.dn-input').focus();
    }

    function dnAddInsuranceRow() {
        var container = document.getElementById('dn-insurance-list');
        var idx = Date.now();
        var html = `
            <div class="dn-item-row" style="align-items: center;">
                <div class="dn-drag-handle"><span class="dashicons dashicons-move"></span></div>
                <label style="display:flex; align-items:center; gap:4px; font-size:12px; font-weight:600;">
                    <input type="checkbox" name="insurance[${idx}][enabled]" value="1" checked style="width:16px; height:16px; cursor:pointer;"> Active
                </label>
                <div style="display: flex; flex-direction: column; gap: 4px; flex-grow: 1;">
                    <input type="text" name="insurance[${idx}][title]" class="dn-input" placeholder="<?php echo esc_js( __( 'Plan Title', 'dev-nahian-car' ) ); ?>" style="font-weight:700;">
                    <input type="text" name="insurance[${idx}][desc]" class="dn-input" placeholder="<?php echo esc_js( __( 'Plan description...', 'dev-nahian-car' ) ); ?>" style="font-size:12px; height:32px;">
                </div>
                <input type="number" step="0.01" name="insurance[${idx}][price]" class="dn-input dn-input-price" placeholder="0.00">
                <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php echo esc_js( __( 'Delete Plan', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        container.lastElementChild.querySelector('.dn-input').focus();
    }

    function dnAddBorderRow() {
        var container = document.getElementById('dn-border-list');
        var idx = Date.now();
        var html = `
            <div class="dn-item-row" style="align-items: center;">
                <div class="dn-drag-handle"><span class="dashicons dashicons-move"></span></div>
                <label style="display:flex; align-items:center; gap:4px; font-size:12px; font-weight:600;">
                    <input type="checkbox" name="border[${idx}][enabled]" value="1" checked style="width:16px; height:16px; cursor:pointer;"> Active
                </label>
                <div style="flex-grow: 1;">
                    <input type="text" name="border[${idx}][destination]" class="dn-input" placeholder="<?php echo esc_js( __( 'Crossing Destination', 'dev-nahian-car' ) ); ?>">
                </div>
                <input type="number" step="0.01" name="border[${idx}][price]" class="dn-input dn-input-price" placeholder="0.00">
                <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php echo esc_js( __( 'Delete Destination', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        container.lastElementChild.querySelector('.dn-input').focus();
    }

    function dnAddPaidExtraRow() {
        var container = document.getElementById('dn-paid-extras-list');
        var idx = Date.now();
        var html = `
            <div class="dn-item-row" style="align-items: center;">
                <div class="dn-drag-handle"><span class="dashicons dashicons-move"></span></div>
                <button type="button" class="dn-icon-picker-btn" onclick="dnOpenIconPicker(this);" title="<?php echo esc_js( __( 'Change Icon', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-cart"></span>
                </button>
                <input type="hidden" name="paid_extras[${idx}][icon]" class="dn-row-icon-input" value="dashicons-cart">
                <input type="text" name="paid_extras[${idx}][name]" class="dn-input" placeholder="<?php echo esc_js( __( 'Service name', 'dev-nahian-car' ) ); ?>" style="flex-grow:1;">
                <input type="number" step="0.01" name="paid_extras[${idx}][price]" class="dn-input dn-input-price" placeholder="<?php printf( esc_js( __( 'Price (%s)', 'dev-nahian-car' ) ), esc_js( $currency ) ); ?>">
                <select name="paid_extras[${idx}][type]" class="dn-select-type">
                    <option value="daily"><?php echo esc_js( __( 'Per Day', 'dev-nahian-car' ) ); ?></option>
                    <option value="fixed"><?php echo esc_js( __( 'Fixed Fee', 'dev-nahian-car' ) ); ?></option>
                </select>
                <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-size:11px; font-weight:700; color:#64748b;">Max Qty:</span>
                    <input type="number" name="paid_extras[${idx}][max_qty]" class="dn-input dn-input-qty" value="1" min="1" max="10">
                </div>
                <label style="display:flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#b91c1c;">
                    <input type="checkbox" name="paid_extras[${idx}][mandatory]" value="1" style="width:16px; height:16px; cursor:pointer;"> Mandatory
                </label>
                <button type="button" class="dn-btn-remove" onclick="dnRemoveRow(this);" title="<?php echo esc_js( __( 'Delete Add-on', 'dev-nahian-car' ) ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        container.lastElementChild.querySelector('.dn-input').focus();
    }
</script>
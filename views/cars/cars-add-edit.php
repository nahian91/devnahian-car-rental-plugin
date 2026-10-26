<?php
/**
 * Cars Module: Registration & Editor Workstation (`cars-add-edit.php`)
 * Fully integrated with all missing extras (Insurance, Borders, Seasonal Rates) 
 * and elevated to an elite SaaS UI/UX standard.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action if POST triggered
if ( isset( $_POST['dev_nahian_save_car_submit'] ) ) {
    check_admin_referer( 'dev_nahian_car_save_verify', 'dev_nahian_car_nonce' );
    if ( current_user_can( 'manage_options' ) ) {

        $car_id = isset( $_POST['car_id'] ) ? absint( $_POST['car_id'] ) : 0;

        $core_data = array(
            'name'         => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
            'brand'        => sanitize_text_field( wp_unslash( $_POST['brand'] ?? '' ) ),
            'year'         => absint( $_POST['year'] ?? 2026 ),
            'vin'          => sanitize_text_field( wp_unslash( $_POST['vin'] ?? '' ) ),
            'plate'        => sanitize_text_field( wp_unslash( $_POST['plate'] ?? '' ) ),
            'category'     => sanitize_key( $_POST['category'] ?? 'sports-gt' ),
            'color'        => sanitize_text_field( wp_unslash( $_POST['color'] ?? '' ) ),
            'status'       => sanitize_key( $_POST['status'] ?? 'ready' ),
            'location'     => sanitize_text_field( wp_unslash( $_POST['default_pickup'] ?? '' ) ),
            'horsepower'   => absint( $_POST['horsepower'] ?? 0 ),
            'acceleration' => sanitize_text_field( wp_unslash( $_POST['acceleration'] ?? '' ) ),
            'speed'        => sanitize_text_field( wp_unslash( $_POST['speed'] ?? '' ) ),
            'drivetrain'   => sanitize_text_field( wp_unslash( $_POST['drivetrain'] ?? 'AWD' ) ),
            'transmission' => sanitize_key( $_POST['transmission'] ?? 'automatic' ),
            'fuel'         => sanitize_key( $_POST['fuel'] ?? 'gasoline' ),
            'seats'        => absint( $_POST['seats'] ?? 2 ),
            'doors'        => absint( $_POST['doors'] ?? 4 ),
            'bags'         => absint( $_POST['bags'] ?? 2 ),
            'price'        => floatval( $_POST['price'] ?? 0.00 ),
            'deposit'      => floatval( $_POST['deposit'] ?? 0.00 ),
            'odometer'     => absint( $_POST['odometer'] ?? 0 ),
            'fuel_level'   => absint( $_POST['fuel_level'] ?? 100 ),
            'image'        => esc_url_raw( wp_unslash( $_POST['featured_image'] ?? '' ) ),
            'notes'        => wp_kses_post( wp_unslash( $_POST['vehicle_description'] ?? '' ) ),
        );

        $meta_data = array(
            'default_pickup'   => sanitize_text_field( wp_unslash( $_POST['default_pickup'] ?? '' ) ),
            'default_dropoff'  => sanitize_text_field( wp_unslash( $_POST['default_dropoff'] ?? '' ) ),
            'technical_specs'  => array(),
            'amenities'        => array(),
            'insurance'        => array(),
            'border_crossing'  => array(),
            'min_booking_days' => max( 1, absint( $_POST['min_booking_days'] ?? 1 ) ),
            'video_url'        => esc_url_raw( wp_unslash( $_POST['video_url'] ?? '' ) ),
            'gallery_images'   => array_map( 'esc_url_raw', (array) ( $_POST['gallery_images'] ?? array() ) ),
            'seasonal_pricing' => array(),
        );

        if ( ! empty( $_POST['specs'] ) && is_array( $_POST['specs'] ) ) {
            foreach ( $_POST['specs'] as $k => $v ) {
                $meta_data['technical_specs'][ sanitize_text_field( wp_unslash( $k ) ) ] = sanitize_text_field( wp_unslash( $v ) );
            }
        }

        if ( ! empty( $_POST['amenities'] ) && is_array( $_POST['amenities'] ) ) {
            foreach ( $_POST['amenities'] as $amenity_item ) {
                $meta_data['amenities'][] = sanitize_text_field( wp_unslash( $amenity_item ) );
            }
        }

        if ( ! empty( $_POST['insurance'] ) && is_array( $_POST['insurance'] ) ) {
            foreach ( $_POST['insurance'] as $ins_key => $ins_val ) {
                $meta_data['insurance'][ sanitize_key( $ins_key ) ] = array(
                    'enabled' => isset( $ins_val['enabled'] ) ? 1 : 0,
                    'price'   => floatval( $ins_val['price'] ?? 0 ),
                );
            }
        }

        if ( ! empty( $_POST['border'] ) && is_array( $_POST['border'] ) ) {
            foreach ( $_POST['border'] as $b_key => $b_val ) {
                $meta_data['border_crossing'][ sanitize_key( $b_key ) ] = array(
                    'enabled' => isset( $b_val['enabled'] ) ? 1 : 0,
                    'price'   => floatval( $b_val['price'] ?? 0 ),
                );
            }
        }

        if ( ! empty( $_POST['seasonal'] ) && is_array( $_POST['seasonal'] ) ) {
            foreach ( $_POST['seasonal'] as $s_item ) {
                if ( ! empty( $s_item['name'] ) && isset( $s_item['rate'] ) ) {
                    $meta_data['seasonal_pricing'][] = array(
                        'name'       => sanitize_text_field( wp_unslash( $s_item['name'] ) ),
                        'start_date' => sanitize_text_field( wp_unslash( $s_item['start_date'] ?? '' ) ),
                        'end_date'   => sanitize_text_field( wp_unslash( $s_item['end_date'] ?? '' ) ),
                        'rate'       => floatval( $s_item['rate'] ),
                    );
                }
            }
        }

        if ( $car_id > 0 ) {
            $wpdb->update( $table_cars, $core_data, array( 'id' => $car_id ) );
            update_option( "dev_nahian_car_meta_{$car_id}", $meta_data, false );
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'cars', 'action' => 'edit', 'id' => $car_id, 'updated' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        } else {
            $core_data['created_at'] = current_time( 'mysql' );
            $inserted = $wpdb->insert( $table_cars, $core_data );
            $new_id   = $wpdb->insert_id;

            if ( false !== $inserted && $new_id ) {
                update_option( "dev_nahian_car_meta_{$new_id}", $meta_data, false );
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'cars', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

// 2. Render Form View
$car  = array();
$meta = array();
if ( $id > 0 ) {
    $car  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cars} WHERE id = %d", $id ), ARRAY_A );
    $meta = get_option( "dev_nahian_car_meta_{$id}", array() );
}

$car_name       = $car['name'] ?? '';
$car_brand      = $car['brand'] ?? '';
$car_year       = $car['year'] ?? 2026;
$car_vin        = $car['vin'] ?? '';
$car_plate      = $car['plate'] ?? '';
$car_cat        = $car['category'] ?? 'sports-gt';
$car_price      = $car['price'] ?? '0.00';
$car_deposit    = $car['deposit'] ?? '0.00';
$car_img        = $car['image'] ?? '';
$car_notes      = $car['notes'] ?? '';
$car_video      = $meta['video_url'] ?? '';
$car_min_days   = $meta['min_booking_days'] ?? 1;
$car_specs      = $meta['technical_specs'] ?? array();
$car_amenities  = $meta['amenities'] ?? array();
$car_insurances = $meta['insurance'] ?? array();
$car_borders    = $meta['border_crossing'] ?? array();
$car_seasons    = $meta['seasonal_pricing'] ?? array();
$car_pickup     = $meta['default_pickup'] ?? ( $car['location'] ?? '' );
$car_dropoff    = $meta['default_dropoff'] ?? '';
?>

<style>
    .dn-editor-layout {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 1080px) {
        .dn-editor-layout { grid-template-columns: 1fr; }
    }
    .dn-card-module {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .dn-card-module:hover {
        border-color: #cbd5e1;
        box-shadow: 0 6px 20px -2px rgba(15, 23, 42, 0.05);
    }
    .dn-module-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dn-module-title {
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.01em;
    }
    .dn-module-title .dashicons {
        color: #0d9488;
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    .dn-field-group {
        margin-bottom: 20px;
    }
    .dn-field-group:last-child {
        margin-bottom: 0;
    }
    .dn-field-group label, .dn-ctrl-label {
        display: block;
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #334155 !important;
        margin-bottom: 6px !important;
    }
    .dn-ctrl-input {
        width: 100% !important;
        height: 42px !important;
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0 14px !important;
        font-size: 13.5px !important;
        color: #0f172a !important;
        outline: none !important;
        box-sizing: border-box !important;
        transition: all 0.2s ease !important;
    }
    .dn-ctrl-input:focus {
        background: #ffffff !important;
        border-color: #0f172a !important;
        box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
    }
    .dn-option-tile {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: border-color 0.15s ease;
    }
    .dn-option-tile:hover {
        border-color: #94a3b8;
    }
    .dn-option-tile input[type="checkbox"] {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        cursor: pointer;
    }
</style>

<div class="dev-nahian-subnav-bar">
    <div>
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary" style="margin-right:8px;">
            <span class="dashicons dashicons-arrow-left-alt" style="vertical-align:middle; line-height:26px;"></span> <?php esc_html_e( 'Back to Fleet', 'dev-nahian-car' ); ?>
        </a>
        <h2 style="display:inline-block; vertical-align:middle; margin:0;" class="dev-nahian-subnav-title">
            <?php echo $id > 0 ? sprintf( esc_html__( 'Edit Vehicle: %s', 'dev-nahian-car' ), esc_html( $car_name ) ) : esc_html__( 'Register New Vehicle Asset', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<form method="post" action="">
    <?php wp_nonce_field( 'dev_nahian_car_save_verify', 'dev_nahian_car_nonce' ); ?>
    <input type="hidden" name="dev_nahian_save_car_submit" value="1">
    <input type="hidden" name="car_id" value="<?php echo esc_attr( (string) $id ); ?>">

    <div class="dn-editor-layout">
        <div class="dn-main-col">
            
            <!-- 1. Identity & Build Data -->
            <div class="dn-card-module">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-car"></span> <?php esc_html_e( 'Vehicle Identity & Build Data', 'dev-nahian-car' ); ?></h3>
                </div>
                <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:16px;" class="dn-field-group">
                    <div>
                        <label><?php esc_html_e( 'Model Designation', 'dev-nahian-car' ); ?></label>
                        <input type="text" name="name" class="dn-ctrl-input" value="<?php echo esc_attr( $car_name ); ?>" placeholder="e.g. Veloce GT3 Coupé" required>
                    </div>
                    <div>
                        <label><?php esc_html_e( 'Brand / Make', 'dev-nahian-car' ); ?></label>
                        <input type="text" name="brand" class="dn-ctrl-input" value="<?php echo esc_attr( $car_brand ); ?>" placeholder="e.g. Porsche">
                    </div>
                    <div>
                        <label><?php esc_html_e( 'Model Year', 'dev-nahian-car' ); ?></label>
                        <input type="number" name="year" class="dn-ctrl-input" value="<?php echo esc_attr( (string) $car_year ); ?>">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
                    <div class="dn-field-group">
                        <label><?php esc_html_e( 'VIN Number', 'dev-nahian-car' ); ?></label>
                        <input type="text" name="vin" class="dn-ctrl-input" value="<?php echo esc_attr( $car_vin ); ?>" placeholder="VIN..." required>
                    </div>
                    <div class="dn-field-group">
                        <label><?php esc_html_e( 'License Plate', 'dev-nahian-car' ); ?></label>
                        <input type="text" name="plate" class="dn-ctrl-input" value="<?php echo esc_attr( $car_plate ); ?>" placeholder="Plate #">
                    </div>
                    <div class="dn-field-group">
                        <label><?php esc_html_e( 'Color Finish', 'dev-nahian-car' ); ?></label>
                        <input type="text" name="color" class="dn-ctrl-input" value="<?php echo esc_attr( $car['color'] ?? '' ); ?>" placeholder="e.g. Grey">
                    </div>
                </div>
            </div>

            <!-- 2. Location Availability -->
            <div class="dn-card-module">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Location Availability', 'dev-nahian-car' ); ?></h3>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="dn-field-group" style="margin-bottom:0;">
                        <label><?php esc_html_e( 'Default Pick Up', 'dev-nahian-car' ); ?></label>
                        <select name="default_pickup" class="dn-ctrl-input">
                            <option value=""><?php esc_html_e( '-- Choose Staging Hub --', 'dev-nahian-car' ); ?></option>
                            <?php foreach ( $global_locations as $loc ) : 
                                $loc_name = is_array( $loc ) ? ( $loc['address'] ?? '' ) : $loc;
                                if ( empty( $loc_name ) ) continue;
                            ?>
                                <option value="<?php echo esc_attr( $loc_name ); ?>" <?php selected( $car_pickup, $loc_name ); ?>><?php echo esc_html( $loc_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="dn-field-group" style="margin-bottom:0;">
                        <label><?php esc_html_e( 'Default Drop Off', 'dev-nahian-car' ); ?></label>
                        <select name="default_dropoff" class="dn-ctrl-input">
                            <option value=""><?php esc_html_e( '-- Same as Pickup --', 'dev-nahian-car' ); ?></option>
                            <?php foreach ( $global_locations as $loc ) : 
                                $loc_name = is_array( $loc ) ? ( $loc['address'] ?? '' ) : $loc;
                                if ( empty( $loc_name ) ) continue;
                            ?>
                                <option value="<?php echo esc_attr( $loc_name ); ?>" <?php selected( $car_dropoff, $loc_name ); ?>><?php echo esc_html( $loc_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. Technical Specifications -->
            <div class="dn-card-module">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Technical Specifications', 'dev-nahian-car' ); ?></h3>
                </div>
                <?php if ( ! empty( $global_specs ) ) : ?>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <?php foreach ( $global_specs as $g_spec ) : 
                            $lbl = $g_spec['label'] ?? '';
                            if ( empty( $lbl ) ) continue;
                            $saved_val = $car_specs[ $lbl ] ?? ( $g_spec['value'] ?? '' );
                        ?>
                            <div class="dn-field-group" style="margin-bottom:0;">
                                <label><?php echo esc_html( $lbl ); ?></label>
                                <input type="text" name="specs[<?php echo esc_attr( $lbl ); ?>]" class="dn-ctrl-input" value="<?php echo esc_attr( $saved_val ); ?>" placeholder="<?php echo esc_attr( $g_spec['value'] ?? '' ); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p style="font-size:12px; color:#64748b; margin:0;"><?php esc_html_e( 'No global technical specifications configured yet.', 'dev-nahian-car' ); ?></p>
                <?php endif; ?>
            </div>

            <!-- 4. Included Amenities -->
            <div class="dn-card-module">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Included Amenities', 'dev-nahian-car' ); ?></h3>
                </div>
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <?php if ( ! empty( $global_amenities ) ) : ?>
                        <?php foreach ( $global_amenities as $g_amenity ) : 
                            $amenity_title = $g_amenity['title'] ?? '';
                            if ( empty( $amenity_title ) ) continue;
                            $is_checked = in_array( $amenity_title, $car_amenities, true );
                        ?>
                            <label style="display:flex; align-items:center; gap:10px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:12px 14px; font-size:13px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="amenities[]" value="<?php echo esc_attr( $amenity_title ); ?>" <?php checked( $is_checked ); ?> style="width:16px; height:16px; border-radius:4px;">
                                <span style="color:#0f172a;"><?php echo esc_html( $amenity_title ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5. Insurance Options -->
            <div class="dn-card-module">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Insurance & Protection Plans', 'dev-nahian-car' ); ?></h3>
                </div>
                <?php if ( ! empty( $global_insurance ) ) : ?>
                    <?php foreach ( $global_insurance as $ins_key => $ins_item ) : 
                        $ins_title = $ins_item['title'] ?? '';
                        if ( empty( $ins_title ) ) continue;
                        $slug_key   = sanitize_key( $ins_key );
                        $is_enabled = $car_insurances[ $slug_key ]['enabled'] ?? ( $ins_item['enabled'] ?? 1 );
                        $ins_price  = $car_insurances[ $slug_key ]['price'] ?? ( $ins_item['price'] ?? 0 );
                    ?>
                        <div class="dn-option-tile">
                            <input type="checkbox" name="insurance[<?php echo esc_attr( $slug_key ); ?>][enabled]" value="1" <?php checked( $is_enabled, 1 ); ?>>
                            <div style="flex-grow:1;">
                                <strong style="display:block; font-size:13.5px; font-weight:800; color:#0f172a; margin-bottom:2px;"><?php echo esc_html( $ins_title ); ?></strong>
                                <p style="margin:0; font-size:12px; color:#64748b;"><?php echo esc_html( $ins_item['desc'] ?? '' ); ?></p>
                            </div>
                            <div style="width:120px; flex-shrink:0;">
                                <input type="number" step="0.01" name="insurance[<?php echo esc_attr( $slug_key ); ?>][price]" class="dn-ctrl-input" value="<?php echo esc_attr( (string) $ins_price ); ?>" placeholder="0.00">
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p style="font-size:12px; color:#64748b; margin:0;"><?php esc_html_e( 'No insurance plans configured globally.', 'dev-nahian-car' ); ?></p>
                <?php endif; ?>
            </div>

            <!-- 6. Cross-Border Clearance -->
            <div class="dn-card-module">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( 'Cross-Border Travel Clearance', 'dev-nahian-car' ); ?></h3>
                </div>
                <?php if ( ! empty( $global_borders ) ) : ?>
                    <?php foreach ( $global_borders as $b_key => $b_item ) : 
                        $dest = $b_item['destination'] ?? '';
                        if ( empty( $dest ) ) continue;
                        $b_slug   = sanitize_key( $b_key );
                        $b_active = $car_borders[ $b_slug ]['enabled'] ?? ( $b_item['enabled'] ?? 0 );
                        $b_fee    = $car_borders[ $b_slug ]['price'] ?? ( $b_item['price'] ?? 0 );
                    ?>
                        <div class="dn-option-tile">
                            <input type="checkbox" name="border[<?php echo esc_attr( $b_slug ); ?>][enabled]" value="1" <?php checked( $b_active, 1 ); ?>>
                            <div style="flex-grow:1;">
                                <strong style="display:block; font-size:13.5px; font-weight:800; color:#0f172a;"><?php echo esc_html( $dest ); ?></strong>
                            </div>
                            <div style="width:120px; flex-shrink:0;">
                                <input type="number" step="0.01" name="border[<?php echo esc_attr( $b_slug ); ?>][price]" class="dn-ctrl-input" value="<?php echo esc_attr( (string) $b_fee ); ?>" placeholder="0.00">
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p style="font-size:12px; color:#64748b; margin:0;"><?php esc_html_e( 'No cross-border destinations configured globally.', 'dev-nahian-car' ); ?></p>
                <?php endif; ?>
            </div>

            <!-- 7. Seasonal & Advanced Pricing Rates -->
            <div class="dn-card-module">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-chart-line"></span> <?php esc_html_e( 'Seasonal & Advanced Pricing Rates', 'dev-nahian-car' ); ?></h3>
                    <button type="button" class="button button-small" onclick="dnAddSeasonRow();">+ <?php esc_html_e( 'Add Season', 'dev-nahian-car' ); ?></button>
                </div>
                <div id="dn-seasonal-list" style="display:flex; flex-direction:column; gap:10px;">
                    <?php if ( ! empty( $car_seasons ) ) : ?>
                        <?php foreach ( $car_seasons as $s_idx => $season ) : ?>
                            <div style="display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr 34px; gap:10px; align-items:center;">
                                <input type="text" name="seasonal[<?php echo esc_attr( (string) $s_idx ); ?>][name]" class="dn-ctrl-input" value="<?php echo esc_attr( $season['name'] ); ?>" placeholder="Season Title">
                                <input type="date" name="seasonal[<?php echo esc_attr( (string) $s_idx ); ?>][start_date]" class="dn-ctrl-input" value="<?php echo esc_attr( $season['start_date'] ); ?>">
                                <input type="date" name="seasonal[<?php echo esc_attr( (string) $s_idx ); ?>][end_date]" class="dn-ctrl-input" value="<?php echo esc_attr( $season['end_date'] ); ?>">
                                <input type="number" step="0.01" name="seasonal[<?php echo esc_attr( (string) $s_idx ); ?>][rate]" class="dn-ctrl-input" value="<?php echo esc_attr( (string) $season['rate'] ); ?>" placeholder="Rate">
                                <button type="button" class="button-link-delete" onclick="this.parentElement.remove();" style="color:#ef4444; font-weight:700;">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 8. Description & Overview -->
            <div class="dn-card-module">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Overview & Description', 'dev-nahian-car' ); ?></h3>
                </div>
                <div class="dn-field-group">
                    <label><?php esc_html_e( 'Minimum Reservation Period (Days)', 'dev-nahian-car' ); ?></label>
                    <input type="number" name="min_booking_days" class="dn-ctrl-input" value="<?php echo esc_attr( (string) $car_min_days ); ?>" min="1" max="30" style="max-width:200px;">
                </div>
                <div class="dn-field-group" style="margin-top:20px; margin-bottom:0;">
                    <label><?php esc_html_e( 'Editorial Description', 'dev-nahian-car' ); ?></label>
                    <?php wp_editor( $car_notes, 'vehicle_description', array( 'media_buttons' => true, 'textarea_rows' => 8, 'teeny' => true ) ); ?>
                </div>
            </div>
        </div>

        <!-- SIDEBAR DOCK -->
        <div class="dn-side-col">
            <div class="dn-card-module" style="border-top: 3px solid var(--dn-admin-primary);">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Tariffs & Status', 'dev-nahian-car' ); ?></h3>
                </div>
                <div class="dn-field-group">
                    <label><?php printf( esc_html__( 'Base Daily Rate (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></label>
                    <input type="number" step="0.01" name="price" class="dn-ctrl-input" value="<?php echo esc_attr( (string) $car_price ); ?>" required style="font-size:16px; font-weight:800; color:#0f172a;">
                </div>
                <div class="dn-field-group">
                    <label><?php printf( esc_html__( 'Security Deposit Hold (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></label>
                    <input type="number" step="0.01" name="deposit" class="dn-ctrl-input" value="<?php echo esc_attr( (string) $car_deposit ); ?>">
                </div>
                <div class="dn-field-group">
                    <label><?php esc_html_e( 'Fleet Classification', 'dev-nahian-car' ); ?></label>
                    <select name="category" class="dn-ctrl-input">
                        <?php foreach ( $available_cats as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat['slug'] ); ?>" <?php selected( $car_cat, $cat['slug'] ); ?>><?php echo esc_html( $cat['name'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="dn-field-group" style="margin-bottom:0;">
                    <label><?php esc_html_e( 'Operational Status', 'dev-nahian-car' ); ?></label>
                    <select name="status" class="dn-ctrl-input">
                        <option value="ready" <?php selected( $car['status'] ?? 'ready', 'ready' ); ?>><?php esc_html_e( 'Ready • Available', 'dev-nahian-car' ); ?></option>
                        <option value="dispatched" <?php selected( $car['status'] ?? '', 'dispatched' ); ?>><?php esc_html_e( 'Active Dispatched', 'dev-nahian-car' ); ?></option>
                        <option value="service" <?php selected( $car['status'] ?? '', 'service' ); ?>><?php esc_html_e( 'Maintenance', 'dev-nahian-car' ); ?></option>
                    </select>
                </div>
                <p style="margin:24px 0 0 0;">
                    <button type="submit" class="button button-primary button-large" style="width:100%; height:44px; background:#0f172a; border-color:#0f172a; font-weight:800; font-size:14px; border-radius:6px; cursor:pointer;">
                        <?php echo $id > 0 ? esc_html__( 'Update Vehicle Data', 'dev-nahian-car' ) : esc_html__( 'Register Vehicle Asset', 'dev-nahian-car' ); ?>
                    </button>
                </p>
            </div>

            <!-- Cover Image Box -->
            <div class="dn-card-module" style="margin-bottom:0;">
                <div class="dn-module-header">
                    <h3 class="dn-module-title"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Cover Image', 'dev-nahian-car' ); ?></h3>
                </div>
                <div style="width:100%; height:190px; background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:8px; display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:12px;" id="dn-featured-preview">
                    <?php if ( ! empty( $car_img ) ) : ?>
                        <img src="<?php echo esc_url( $car_img ); ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                    <?php else : ?>
                        <span style="color:#94a3b8; font-size:12px;"><?php esc_html_e( 'No photo attached', 'dev-nahian-car' ); ?></span>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="featured_image" id="dn-featured-input" value="<?php echo esc_url( $car_img ); ?>">
                <button type="button" class="button button-secondary" style="width:100%; height:38px;" onclick="dnUploadImage();">
                    <span class="dashicons dashicons-upload" style="vertical-align:middle;"></span> <?php esc_html_e( 'Select Cover Image', 'dev-nahian-car' ); ?>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    function dnUploadImage() {
        var frame = wp.media({
            title: '<?php echo esc_js( __( 'Select Vehicle Cover Image', 'dev-nahian-car' ) ); ?>',
            button: { text: '<?php echo esc_js( __( 'Set As Cover', 'dev-nahian-car' ) ); ?>' },
            multiple: false
        });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            document.getElementById('dn-featured-input').value = attachment.url;
            document.getElementById('dn-featured-preview').innerHTML = '<img src="' + attachment.url + '" alt="" style="width:100%; height:100%; object-fit:cover;">';
        });
        frame.open();
    }

    function dnAddSeasonRow() {
        var container = document.getElementById('dn-seasonal-list');
        var idx = Date.now();
        var html = '\
            <div style="display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr 34px; gap:10px; align-items:center;">\
                <input type="text" name="seasonal[' + idx + '][name]" class="dn-ctrl-input" placeholder="Season Title">\
                <input type="date" name="seasonal[' + idx + '][start_date]" class="dn-ctrl-input">\
                <input type="date" name="seasonal[' + idx + '][end_date]" class="dn-ctrl-input">\
                <input type="number" step="0.01" name="seasonal[' + idx + '][rate]" class="dn-ctrl-input" placeholder="Rate">\
                <button type="button" class="button-link-delete" onclick="this.parentElement.remove();" style="color:#ef4444; font-weight:700;">&times;</button>\
            </div>\
        ';
        container.insertAdjacentHTML('beforeend', html);
    }
</script>
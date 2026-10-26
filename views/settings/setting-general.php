<?php
/**
 * Settings Sub-Tab View: General (`setting-general.php`)
 * Upgraded to elite SaaS UI/UX standards with polished input boxes and card grouping.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handler logic if POST submission targets general
if ( isset( $_POST['dev_nahian_save_settings_subtab'] ) && 'general' === sanitize_key( $_POST['current_subtab'] ?? '' ) ) {
    $allowed_currencies = array( 'USD', 'BDT', 'EUR', 'GBP', 'AED', 'CAD', 'AUD', 'CHF', 'JPY' );
    $currency_input     = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) );
    $currency           = in_array( $currency_input, $allowed_currencies, true ) ? $currency_input : 'USD';
    update_option( 'dev_nahian_currency', $currency );

    $currency_pos = sanitize_key( $_POST['currency_pos'] ?? 'left' );
    update_option( 'dev_nahian_currency_pos', $currency_pos, false );

    $pricing_unit = sanitize_key( $_POST['pricing_unit'] ?? 'per_day' );
    update_option( 'dev_nahian_pricing_unit', $pricing_unit, false );

    $min_days = max( 1, absint( $_POST['min_days'] ?? 1 ) );
    update_option( 'dev_nahian_min_days', $min_days, false );

    $min_driver_age = max( 18, absint( $_POST['min_driver_age'] ?? 21 ) );
    update_option( 'dev_nahian_min_driver_age', $min_driver_age, false );

    $op_hours_start = sanitize_text_field( wp_unslash( $_POST['op_hours_start'] ?? '08:00' ) );
    update_option( 'dev_nahian_op_hours_start', $op_hours_start, false );

    $op_hours_end = sanitize_text_field( wp_unslash( $_POST['op_hours_end'] ?? '20:00' ) );
    update_option( 'dev_nahian_op_hours_end', $op_hours_end, false );

    $prep_buffer_hours = absint( $_POST['prep_buffer_hours'] ?? 2 );
    update_option( 'dev_nahian_prep_buffer_hours', $prep_buffer_hours, false );

    $weekend_ops = isset( $_POST['weekend_ops'] ) ? 1 : 0;
    update_option( 'dev_nahian_weekend_ops', $weekend_ops, false );

    $notice = '<div class="notice notice-success is-dismissible" style="margin: 15px 0 20px;"><p>' . esc_html__( 'General settings updated successfully.', 'dev-nahian-car' ) . '</p></div>';
}
?>

<style>
    .dn-settings-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .dn-settings-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 6px 20px -2px rgba(15, 23, 42, 0.05);
    }
    .dn-settings-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dn-settings-title {
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.01em;
    }
    .dn-settings-title .dashicons {
        color: #0d9488;
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    .dev-nahian-field-box label {
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #334155 !important;
        margin-bottom: 6px !important;
    }
    .dev-nahian-input, .dev-nahian-select, .dev-nahian-textarea {
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0 14px !important;
        font-size: 13.5px !important;
        height: 42px !important;
        transition: all 0.2s ease !important;
    }
    .dev-nahian-select {
        height: 42px !important;
        padding-right: 32px !important;
    }
    .dev-nahian-input:focus, .dev-nahian-select:focus, .dev-nahian-textarea:focus {
        background: #ffffff !important;
        border-color: #0f172a !important;
        box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
    }
</style>

<!-- Card: Localization & Policy -->
<div class="dn-settings-card">
    <div class="dn-settings-header">
        <h3 class="dn-settings-title">
            <span class="dashicons dashicons-admin-site-alt3"></span>
            <?php esc_html_e( 'Localization & Currency Policies', 'dev-nahian-car' ); ?>
        </h3>
    </div>
    <div style="margin-bottom:8px; font-size:12.5px; color:#64748b;">
        <?php esc_html_e( 'Currency and core pricing logic.', 'dev-nahian-car' ); ?>
    </div>

    <div class="dev-nahian-grid-2" style="margin-top:16px;">
        <div class="dev-nahian-field-box">
            <label for="currency"><?php esc_html_e( 'Currency', 'dev-nahian-car' ); ?></label>
            <select id="currency" name="currency" class="dev-nahian-select">
                <option value="USD" <?php selected( $currency, 'USD' ); ?>>USD ($) &ndash; United States Dollar</option>
                <option value="BDT" <?php selected( $currency, 'BDT' ); ?>>BDT (৳) &ndash; Bangladeshi Taka</option>
                <option value="EUR" <?php selected( $currency, 'EUR' ); ?>>EUR (€) &ndash; Euro</option>
                <option value="GBP" <?php selected( $currency, 'GBP' ); ?>>GBP (£) &ndash; British Pound</option>
                <option value="AED" <?php selected( $currency, 'AED' ); ?>>AED (د.إ) &ndash; UAE Dirham</option>
                <option value="CAD" <?php selected( $currency, 'CAD' ); ?>>CAD ($) &ndash; Canadian Dollar</option>
                <option value="AUD" <?php selected( $currency, 'AUD' ); ?>>AUD ($) &ndash; Australian Dollar</option>
                <option value="CHF" <?php selected( $currency, 'CHF' ); ?>>CHF &ndash; Swiss Franc</option>
                <option value="JPY" <?php selected( $currency, 'JPY' ); ?>>JPY (¥) &ndash; Japanese Yen</option>
            </select>
        </div>

        <div class="dev-nahian-field-box">
            <label for="currency_pos"><?php esc_html_e( 'Symbol Position', 'dev-nahian-car' ); ?></label>
            <select id="currency_pos" name="currency_pos" class="dev-nahian-select">
                <option value="left" <?php selected( $currency_pos, 'left' ); ?>><?php esc_html_e( 'Left ($100 / ৳100)', 'dev-nahian-car' ); ?></option>
                <option value="right" <?php selected( $currency_pos, 'right' ); ?>><?php esc_html_e( 'Right (100$ / 100৳)', 'dev-nahian-car' ); ?></option>
                <option value="left_space" <?php selected( $currency_pos, 'left_space' ); ?>><?php esc_html_e( 'Left with space ($ 100 / ৳ 100)', 'dev-nahian-car' ); ?></option>
                <option value="right_space" <?php selected( $currency_pos, 'right_space' ); ?>><?php esc_html_e( 'Right with space (100 $ / 100 ৳)', 'dev-nahian-car' ); ?></option>
            </select>
        </div>

        <div class="dev-nahian-field-box">
            <label for="pricing_unit"><?php esc_html_e( 'Base Pricing Unit', 'dev-nahian-car' ); ?></label>
            <select id="pricing_unit" name="pricing_unit" class="dev-nahian-select">
                <option value="per_day" <?php selected( $pricing_unit, 'per_day' ); ?>><?php esc_html_e( 'Per Day (24-Hour Cycle)', 'dev-nahian-car' ); ?></option>
                <option value="per_hour" <?php selected( $pricing_unit, 'per_hour' ); ?>><?php esc_html_e( 'Per Hour (Chauffeur / Flex)', 'dev-nahian-car' ); ?></option>
            </select>
        </div>

        <div class="dev-nahian-field-box">
            <label for="min_days"><?php esc_html_e( 'Min. Days', 'dev-nahian-car' ); ?></label>
            <input type="number" id="min_days" name="min_days" class="dev-nahian-input" value="<?php echo esc_attr( (string) $min_days ); ?>" min="1" max="30">
            <p class="dev-nahian-hint"><?php esc_html_e( 'Minimum rental duration per booking.', 'dev-nahian-car' ); ?></p>
        </div>

        <div class="dev-nahian-field-box" style="grid-column: 1 / -1;">
            <label for="min_driver_age"><?php esc_html_e( 'Min. Driver Age', 'dev-nahian-car' ); ?></label>
            <input type="number" id="min_driver_age" name="min_driver_age" class="dev-nahian-input" value="<?php echo esc_attr( (string) $min_driver_age ); ?>" min="18" max="35" style="max-width: 320px;">
            <p class="dev-nahian-hint"><?php esc_html_e( 'Drivers below this age threshold are prohibited from reservation.', 'dev-nahian-car' ); ?></p>
        </div>
    </div>
</div>

<!-- Card: Service Window -->
<div class="dn-settings-card">
    <div class="dn-settings-header">
        <h3 class="dn-settings-title">
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e( 'Service Window & Operations', 'dev-nahian-car' ); ?>
        </h3>
    </div>
    <div style="margin-bottom:8px; font-size:12.5px; color:#64748b;">
        <?php esc_html_e( 'Pickup times and preparation rules.', 'dev-nahian-car' ); ?>
    </div>

    <div class="dev-nahian-grid-2" style="margin-top:16px;">
        <div class="dev-nahian-field-box">
            <label><?php esc_html_e( 'Daily Operation Hours', 'dev-nahian-car' ); ?></label>
            <div style="display:flex; align-items:center; gap:10px;">
                <input type="time" name="op_hours_start" class="dev-nahian-input" value="<?php echo esc_attr( $op_hours_start ); ?>">
                <span style="font-size:12px; color:#64748b; font-weight:700;"><?php esc_html_e( 'to', 'dev-nahian-car' ); ?></span>
                <input type="time" name="op_hours_end" class="dev-nahian-input" value="<?php echo esc_attr( $op_hours_end ); ?>">
            </div>
        </div>

        <div class="dev-nahian-field-box">
            <label for="prep_buffer_hours"><?php esc_html_e( 'Preparation Buffer (Hours)', 'dev-nahian-car' ); ?></label>
            <input type="number" id="prep_buffer_hours" name="prep_buffer_hours" class="dev-nahian-input" value="<?php echo esc_attr( (string) $prep_buffer_hours ); ?>" min="0" max="24">
            <p class="dev-nahian-hint"><?php esc_html_e( 'Rest and detailing period required between bookings.', 'dev-nahian-car' ); ?></p>
        </div>

        <div style="grid-column: 1 / -1; display:flex; align-items:center; justify-content:space-between; padding-top:16px; border-top:1px dashed #e2e8f0; margin-top:4px;">
            <div>
                <strong style="display:block; font-size:13.5px; color:#0f172a; font-weight:700;"><?php esc_html_e( 'Enable Weekend Operations', 'dev-nahian-car' ); ?></strong>
                <span style="font-size:12px; color:#64748b;"><?php esc_html_e( 'Allow pickups on Saturday and Sunday.', 'dev-nahian-car' ); ?></span>
            </div>
            <label class="dev-nahian-switch">
                <input type="checkbox" name="weekend_ops" value="1" <?php checked( $weekend_ops, 1 ); ?>>
                <span class="dev-nahian-slider"></span>
            </label>
        </div>
    </div>
</div>
<?php
/**
 * Settings Sub-Tab View: Payments (`setting-payments.php`)
 * Upgraded to elite SaaS UI/UX standards with polished input boxes and card grouping.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handler logic if POST submission targets payments
if ( isset( $_POST['dev_nahian_save_settings_subtab'] ) && 'payments' === sanitize_key( $_POST['current_subtab'] ?? '' ) ) {
    // Gateways Toggle
    $gw_pay_later       = isset( $_POST['gw_pay_later'] ) ? 1 : 0;
    update_option( 'dev_nahian_gw_pay_later', $gw_pay_later, false );

    $gw_bank_transfer   = isset( $_POST['gw_bank_transfer'] ) ? 1 : 0;
    update_option( 'dev_nahian_gw_bank_transfer', $gw_bank_transfer, false );

    $gw_bank_details    = sanitize_textarea_field( wp_unslash( $_POST['gw_bank_details'] ?? '' ) );
    update_option( 'dev_nahian_gw_bank_details', $gw_bank_details, false );

    $gw_paypal          = isset( $_POST['gw_paypal'] ) ? 1 : 0;
    update_option( 'dev_nahian_gw_paypal', $gw_paypal, false );

    $paypal_client_id   = sanitize_text_field( wp_unslash( $_POST['paypal_client_id'] ?? '' ) );
    update_option( 'dev_nahian_paypal_client_id', $paypal_client_id, false );

    $paypal_secret      = sanitize_text_field( wp_unslash( $_POST['paypal_secret'] ?? '' ) );
    update_option( 'dev_nahian_paypal_secret', $paypal_secret, false );

    $paypal_mode        = sanitize_key( $_POST['paypal_mode'] ?? 'sandbox' );
    update_option( 'dev_nahian_paypal_mode', $paypal_mode, false );

    $gw_stripe          = isset( $_POST['gw_stripe'] ) ? 1 : 0;
    update_option( 'dev_nahian_gw_stripe', $gw_stripe, false );

    $stripe_publishable = sanitize_text_field( wp_unslash( $_POST['stripe_publishable'] ?? '' ) );
    update_option( 'dev_nahian_stripe_publishable', $stripe_publishable, false );

    $stripe_secret      = sanitize_text_field( wp_unslash( $_POST['stripe_secret'] ?? '' ) );
    update_option( 'dev_nahian_stripe_secret', $stripe_secret, false );

    // Deposit Rules
    $deposit_val        = floatval( $_POST['deposit_amount'] ?? 0.00 );
    update_option( 'dev_nahian_deposit_amount', number_format( $deposit_val, 2, '.', '' ) );

    $no_deposit         = isset( $_POST['no_deposit'] ) ? 1 : 0;
    update_option( 'dev_nahian_no_deposit', $no_deposit );

    $notice = '<div class="notice notice-success is-dismissible" style="margin: 15px 0 20px;"><p>' . esc_html__( 'Payment gateways and deposit rules updated successfully.', 'dev-nahian-car' ) . '</p></div>';
}

// Retrieve saved gateway values
$gw_pay_later       = get_option( 'dev_nahian_gw_pay_later', 1 );
$gw_bank_transfer   = get_option( 'dev_nahian_gw_bank_transfer', 1 );
$gw_bank_details    = get_option( 'dev_nahian_gw_bank_details', "Bank: JPMorgan Chase & Co.\nAccount Name: Dev Nahian Fleet LLC\nIBAN / Routing: CHASEUS33XXX\nAccount No: 8849-2019-3301" );

$gw_paypal          = get_option( 'dev_nahian_gw_paypal', 0 );
$paypal_client_id   = get_option( 'dev_nahian_paypal_client_id', '' );
$paypal_secret      = get_option( 'dev_nahian_paypal_secret', '' );
$paypal_mode        = get_option( 'dev_nahian_paypal_mode', 'sandbox' );

$gw_stripe          = get_option( 'dev_nahian_gw_stripe', 1 );
$stripe_publishable = get_option( 'dev_nahian_stripe_publishable', '' );
$stripe_secret      = get_option( 'dev_nahian_stripe_secret', '' );

$deposit_amount     = get_option( 'dev_nahian_deposit_amount', '1500.00' );
$no_deposit         = get_option( 'dev_nahian_no_deposit', 1 );
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
    .dev-nahian-textarea {
        height: 86px !important;
        padding: 10px 14px !important;
    }
    .dev-nahian-input:focus, .dev-nahian-select:focus, .dev-nahian-textarea:focus {
        background: #ffffff !important;
        border-color: #0f172a !important;
        box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
    }
</style>

<!-- ================= 1. STRIPE GATEWAY MODULE ================= -->
<div class="dn-settings-card">
    <div class="dn-settings-header">
        <h3 class="dn-settings-title">
            <span class="dashicons dashicons-money-alt"></span>
            <?php esc_html_e( 'Stripe Checkout', 'dev-nahian-car' ); ?>
        </h3>
        <label class="dev-nahian-switch" title="Toggle Gateway">
            <input type="checkbox" name="gw_stripe" value="1" <?php checked( $gw_stripe, 1 ); ?>>
            <span class="dev-nahian-slider"></span>
        </label>
    </div>
    <div style="margin-bottom:8px; font-size:12.5px; color:#64748b;">
        <?php esc_html_e( 'Accept secure credit and debit card payments via Stripe API.', 'dev-nahian-car' ); ?>
    </div>
    <div class="dev-nahian-grid-2" style="margin-top:16px;">
        <div class="dev-nahian-field-box">
            <label for="stripe_publishable"><?php esc_html_e( 'Stripe Publishable Key', 'dev-nahian-car' ); ?></label>
            <input type="text" id="stripe_publishable" name="stripe_publishable" class="dev-nahian-input" value="<?php echo esc_attr( $stripe_publishable ); ?>" placeholder="pk_live_...">
        </div>
        <div class="dev-nahian-field-box">
            <label for="stripe_secret"><?php esc_html_e( 'Stripe Secret Key', 'dev-nahian-car' ); ?></label>
            <input type="password" id="stripe_secret" name="stripe_secret" class="dev-nahian-input" value="<?php echo esc_attr( $stripe_secret ); ?>" placeholder="sk_live_...">
        </div>
    </div>
</div>

<!-- ================= 2. PAYPAL GATEWAY MODULE ================= -->
<div class="dn-settings-card">
    <div class="dn-settings-header">
        <h3 class="dn-settings-title">
            <span class="dashicons dashicons-shield"></span>
            <?php esc_html_e( 'PayPal Standard / REST', 'dev-nahian-car' ); ?>
        </h3>
        <label class="dev-nahian-switch" title="Toggle Gateway">
            <input type="checkbox" name="gw_paypal" value="1" <?php checked( $gw_paypal, 1 ); ?>>
            <span class="dev-nahian-slider"></span>
        </label>
    </div>
    <div style="margin-bottom:8px; font-size:12.5px; color:#64748b;">
        <?php esc_html_e( 'Allow instant checkout using PayPal balances or linked bank accounts.', 'dev-nahian-car' ); ?>
    </div>
    <div class="dev-nahian-grid-2" style="margin-top:16px; margin-bottom: 18px;">
        <div class="dev-nahian-field-box">
            <label for="paypal_client_id"><?php esc_html_e( 'PayPal Client ID', 'dev-nahian-car' ); ?></label>
            <input type="text" id="paypal_client_id" name="paypal_client_id" class="dev-nahian-input" value="<?php echo esc_attr( $paypal_client_id ); ?>" placeholder="AbCdeF...">
        </div>
        <div class="dev-nahian-field-box">
            <label for="paypal_secret"><?php esc_html_e( 'PayPal Secret Key', 'dev-nahian-car' ); ?></label>
            <input type="password" id="paypal_secret" name="paypal_secret" class="dev-nahian-input" value="<?php echo esc_attr( $paypal_secret ); ?>" placeholder="SECRET_key...">
        </div>
    </div>
    <div class="dev-nahian-field-box" style="max-width: 320px;">
        <label for="paypal_mode"><?php esc_html_e( 'Environment Mode', 'dev-nahian-car' ); ?></label>
        <select id="paypal_mode" name="paypal_mode" class="dev-nahian-select">
            <option value="sandbox" <?php selected( $paypal_mode, 'sandbox' ); ?>><?php esc_html_e( 'Sandbox (Testing)', 'dev-nahian-car' ); ?></option>
            <option value="live" <?php selected( $paypal_mode, 'live' ); ?>><?php esc_html_e( 'Live (Production)', 'dev-nahian-car' ); ?></option>
        </select>
    </div>
</div>

<!-- ================= 3. DIRECT BANK TRANSFER ================= -->
<div class="dn-settings-card">
    <div class="dn-settings-header">
        <h3 class="dn-settings-title">
            <span class="dashicons dashicons-building"></span>
            <?php esc_html_e( 'Direct Bank Transfer', 'dev-nahian-car' ); ?>
        </h3>
        <label class="dev-nahian-switch" title="Toggle Gateway">
            <input type="checkbox" name="gw_bank_transfer" value="1" <?php checked( $gw_bank_transfer, 1 ); ?>>
            <span class="dev-nahian-slider"></span>
        </label>
    </div>
    <div style="margin-bottom:12px; font-size:12.5px; color:#64748b;">
        <?php esc_html_e( 'Display wire transfer details for manual settlement.', 'dev-nahian-car' ); ?>
    </div>
    <div class="dev-nahian-field-box" style="margin-top:12px;">
        <label for="gw_bank_details"><?php esc_html_e( 'Bank Account Instructions', 'dev-nahian-car' ); ?></label>
        <textarea id="gw_bank_details" name="gw_bank_details" class="dev-nahian-textarea" style="font-family: monospace; font-size: 12.5px;"><?php echo esc_textarea( $gw_bank_details ); ?></textarea>
    </div>
</div>

<!-- ================= 4. BOOK NOW, PAY LATER ================= -->
<div class="dn-settings-card">
    <div class="dn-settings-header" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
        <h3 class="dn-settings-title">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e( 'Book Now, Pay Later', 'dev-nahian-car' ); ?>
        </h3>
        <label class="dev-nahian-switch" title="Toggle Gateway">
            <input type="checkbox" name="gw_pay_later" value="1" <?php checked( $gw_pay_later, 1 ); ?>>
            <span class="dev-nahian-slider"></span>
        </label>
    </div>
    <div style="margin-top:6px; font-size:12.5px; color:#64748b;">
        <?php esc_html_e( 'Reserve chassis immediately; settle payment upon arrival at handover.', 'dev-nahian-car' ); ?>
    </div>
</div>

<!-- ================= 5. SECURITY DEPOSIT RULES ================= -->
<div class="dn-settings-card">
    <div class="dn-settings-header">
        <h3 class="dn-settings-title">
            <span class="dashicons dashicons-lock"></span>
            <?php esc_html_e( 'Security Deposit & Hold Policies', 'dev-nahian-car' ); ?>
        </h3>
    </div>
    <div style="margin-bottom:8px; font-size:12.5px; color:#64748b;">
        <?php esc_html_e( 'Configure collateral freeze amounts applied upon vehicle dispatch.', 'dev-nahian-car' ); ?>
    </div>

    <div class="dev-nahian-grid-2" style="margin-top:16px;">
        <div class="dev-nahian-field-box">
            <label for="deposit_amount"><?php printf( esc_html__( 'Security Deposit Hold (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></label>
            <input type="number" step="0.01" id="deposit_amount" name="deposit_amount" class="dev-nahian-input" value="<?php echo esc_attr( (string) $deposit_amount ); ?>">
            <p class="dev-nahian-hint"><?php esc_html_e( 'Default authorization freeze placed upon handover.', 'dev-nahian-car' ); ?></p>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:16px;">
            <div>
                <strong style="display:block; font-size:13.5px; color:#0f172a; font-weight:700;"><?php esc_html_e( 'Sovereign Tier Exemption', 'dev-nahian-car' ); ?></strong>
                <span style="font-size:12px; color:#64748b;"><?php esc_html_e( 'Waive deposit holds for pre-verified drivers.', 'dev-nahian-car' ); ?></span>
            </div>
            <label class="dev-nahian-switch">
                <input type="checkbox" name="no_deposit" value="1" <?php checked( $no_deposit, 1 ); ?>>
                <span class="dev-nahian-slider"></span>
            </label>
        </div>
    </div>
</div>
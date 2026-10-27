<?php
/**
 * Settings Sub-Tab View: Payment Gateways & Deposit Rules (`setting-payments.php`)
 * Handles gateway credentials (Stripe, PayPal, Bank Wire, Pay Later) and security deposit hold governance.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'dev-nahian-car' ), 403 );
}

// =========================================================================
// 1. Process Form Mutations
// =========================================================================
if ( isset( $_POST['dev_nahian_save_settings_subtab'] ) && 'payments' === sanitize_key( wp_unslash( $_POST['current_subtab'] ?? '' ) ) ) {
	check_admin_referer( 'dev_nahian_settings_subtab_verify', 'dev_nahian_settings_subtab_nonce' );

	// Gateways Toggles.
	$gw_pay_later = isset( $_POST['gw_pay_later'] ) ? 1 : 0;
	update_option( 'dev_nahian_gw_pay_later', $gw_pay_later, false );

	$gw_bank_transfer = isset( $_POST['gw_bank_transfer'] ) ? 1 : 0;
	update_option( 'dev_nahian_gw_bank_transfer', $gw_bank_transfer, false );

	$gw_bank_details = sanitize_textarea_field( wp_unslash( $_POST['gw_bank_details'] ?? '' ) );
	update_option( 'dev_nahian_gw_bank_details', $gw_bank_details, false );

	$gw_paypal = isset( $_POST['gw_paypal'] ) ? 1 : 0;
	update_option( 'dev_nahian_gw_paypal', $gw_paypal, false );

	$paypal_client_id = sanitize_text_field( wp_unslash( $_POST['paypal_client_id'] ?? '' ) );
	update_option( 'dev_nahian_paypal_client_id', $paypal_client_id, false );

	$paypal_secret = sanitize_text_field( wp_unslash( $_POST['paypal_secret'] ?? '' ) );
	update_option( 'dev_nahian_paypal_secret', $paypal_secret, false );

	$allowed_pp_modes = array( 'sandbox', 'live' );
	$paypal_mode_in   = sanitize_key( wp_unslash( $_POST['paypal_mode'] ?? 'sandbox' ) );
	$paypal_mode      = in_array( $paypal_mode_in, $allowed_pp_modes, true ) ? $paypal_mode_in : 'sandbox';
	update_option( 'dev_nahian_paypal_mode', $paypal_mode, false );

	$gw_stripe = isset( $_POST['gw_stripe'] ) ? 1 : 0;
	update_option( 'dev_nahian_gw_stripe', $gw_stripe, false );

	$stripe_publishable = sanitize_text_field( wp_unslash( $_POST['stripe_publishable'] ?? '' ) );
	update_option( 'dev_nahian_stripe_publishable', $stripe_publishable, false );

	$stripe_secret = sanitize_text_field( wp_unslash( $_POST['stripe_secret'] ?? '' ) );
	update_option( 'dev_nahian_stripe_secret', $stripe_secret, false );

	// Deposit Rules.
	$raw_deposit    = isset( $_POST['deposit_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['deposit_amount'] ) ) : 0.00;
	$deposit_amount = number_format( max( 0.0, $raw_deposit ), 2, '.', '' );
	update_option( 'dev_nahian_deposit_amount', $deposit_amount, false );

	$no_deposit = isset( $_POST['no_deposit'] ) ? 1 : 0;
	update_option( 'dev_nahian_no_deposit', $no_deposit, false );

	$redirect_url = isset( $base ) ? $base : add_query_arg(
		array(
			'page'   => 'dev-nahian-fleet-manager',
			'tab'    => 'settings',
			'subtab' => 'payments',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( add_query_arg( array( 'saved' => '1' ), $redirect_url ) );
	exit;
}

// =========================================================================
// 2. Safe Scope Fallbacks
// =========================================================================
$currency           = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );
$gw_pay_later       = isset( $gw_pay_later ) ? $gw_pay_later : absint( get_option( 'dev_nahian_gw_pay_later', 1 ) );
$gw_bank_transfer   = isset( $gw_bank_transfer ) ? $gw_bank_transfer : absint( get_option( 'dev_nahian_gw_bank_transfer', 1 ) );
$gw_bank_details    = isset( $gw_bank_details ) ? $gw_bank_details : (string) get_option(
	'dev_nahian_gw_bank_details',
	"Bank: JPMorgan Chase & Co.\nAccount Name: Dev Nahian Fleet LLC\nIBAN / Routing: CHASEUS33XXX\nAccount No: 8849-2019-3301"
);
$gw_paypal          = absint( get_option( 'dev_nahian_gw_paypal', 0 ) );
$paypal_client_id   = (string) get_option( 'dev_nahian_paypal_client_id', '' );
$paypal_secret      = (string) get_option( 'dev_nahian_paypal_secret', '' );
$paypal_mode        = (string) get_option( 'dev_nahian_paypal_mode', 'sandbox' );

$gw_stripe          = absint( get_option( 'dev_nahian_gw_stripe', 1 ) );
$stripe_publishable = (string) get_option( 'dev_nahian_stripe_publishable', '' );
$stripe_secret      = (string) get_option( 'dev_nahian_stripe_secret', '' );

$deposit_amount     = isset( $deposit_amount ) ? $deposit_amount : (string) get_option( 'dev_nahian_deposit_amount', '1500.00' );
$no_deposit         = isset( $no_deposit ) ? $no_deposit : absint( get_option( 'dev_nahian_no_deposit', 1 ) );
?>

<!-- 1. Stripe Checkout Integration Module -->
<div class="dnf-card">
	<div class="dnf-card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
		<div>
			<h3 class="dnf-card-title"><?php esc_html_e( 'Stripe Checkout API Integration', 'dev-nahian-car' ); ?></h3>
			<p class="dnf-card-desc"><?php esc_html_e( 'Accept debit cards, credit cards, Apple Pay, and Google Pay through direct Stripe tokenization.', 'dev-nahian-car' ); ?></p>
		</div>
		<label class="dnf-switch" title="<?php esc_attr_e( 'Toggle Stripe Gateway', 'dev-nahian-car' ); ?>">
			<input type="checkbox" name="gw_stripe" value="1" <?php checked( $gw_stripe, 1 ); ?>>
			<span class="dnf-slider"></span>
		</label>
	</div>

	<div class="dnf-grid-2">
		<div class="dnf-field-box">
			<label for="stripe_publishable"><?php esc_html_e( 'Stripe Publishable Key', 'dev-nahian-car' ); ?></label>
			<input type="text" id="stripe_publishable" name="stripe_publishable" class="dnf-input" value="<?php echo esc_attr( $stripe_publishable ); ?>" placeholder="pk_live_...">
			<p class="dnf-hint"><?php esc_html_e( 'Public API token used by Stripe.js in the browser client.', 'dev-nahian-car' ); ?></p>
		</div>

		<div class="dnf-field-box">
			<label for="stripe_secret"><?php esc_html_e( 'Stripe Secret Key', 'dev-nahian-car' ); ?></label>
			<input type="password" id="stripe_secret" name="stripe_secret" class="dnf-input" value="<?php echo esc_attr( $stripe_secret ); ?>" placeholder="sk_live_...">
			<p class="dnf-hint"><?php esc_html_e( 'Server-side secret token used to settle payment intents and authorize escrow holds.', 'dev-nahian-car' ); ?></p>
		</div>
	</div>
</div>

<!-- 2. PayPal REST API Module -->
<div class="dnf-card">
	<div class="dnf-card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
		<div>
			<h3 class="dnf-card-title"><?php esc_html_e( 'PayPal REST Gateway', 'dev-nahian-car' ); ?></h3>
			<p class="dnf-card-desc"><?php esc_html_e( 'Allow direct checkout using digital PayPal balances, Pay in 4, and linked accounts.', 'dev-nahian-car' ); ?></p>
		</div>
		<label class="dnf-switch" title="<?php esc_attr_e( 'Toggle PayPal Gateway', 'dev-nahian-car' ); ?>">
			<input type="checkbox" name="gw_paypal" value="1" <?php checked( $gw_paypal, 1 ); ?>>
			<span class="dnf-slider"></span>
		</label>
	</div>

	<div class="dnf-grid-2">
		<div class="dnf-field-box">
			<label for="paypal_client_id"><?php esc_html_e( 'PayPal Client ID', 'dev-nahian-car' ); ?></label>
			<input type="text" id="paypal_client_id" name="paypal_client_id" class="dnf-input" value="<?php echo esc_attr( $paypal_client_id ); ?>" placeholder="AbCdeF...">
		</div>

		<div class="dnf-field-box">
			<label for="paypal_secret"><?php esc_html_e( 'PayPal Secret Key', 'dev-nahian-car' ); ?></label>
			<input type="password" id="paypal_secret" name="paypal_secret" class="dnf-input" value="<?php echo esc_attr( $paypal_secret ); ?>" placeholder="SECRET_key...">
		</div>

		<div class="dnf-field-box col-span-full" style="max-width: 320px;">
			<label for="paypal_mode"><?php esc_html_e( 'Environment Mode', 'dev-nahian-car' ); ?></label>
			<div class="dnf-select-wrapper">
				<select id="paypal_mode" name="paypal_mode" class="dnf-select">
					<option value="sandbox" <?php selected( $paypal_mode, 'sandbox' ); ?>><?php esc_html_e( 'Sandbox (Testing Environment)', 'dev-nahian-car' ); ?></option>
					<option value="live" <?php selected( $paypal_mode, 'live' ); ?>><?php esc_html_e( 'Live (Production Environment)', 'dev-nahian-car' ); ?></option>
				</select>
			</div>
			<p class="dnf-hint"><?php esc_html_e( 'Switch to Sandbox mode to simulate test payments without processing real funds.', 'dev-nahian-car' ); ?></p>
		</div>
	</div>
</div>

<!-- 3. Direct Bank Wire & BACS Transfer -->
<div class="dnf-card">
	<div class="dnf-card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
		<div>
			<h3 class="dnf-card-title"><?php esc_html_e( 'Direct Bank Wire (BACS / ACH)', 'dev-nahian-car' ); ?></h3>
			<p class="dnf-card-desc"><?php esc_html_e( 'Display corporate account instructions for clients settling large balances via wire transfer.', 'dev-nahian-car' ); ?></p>
		</div>
		<label class="dnf-switch" title="<?php esc_attr_e( 'Toggle Bank Transfer', 'dev-nahian-car' ); ?>">
			<input type="checkbox" name="gw_bank_transfer" value="1" <?php checked( $gw_bank_transfer, 1 ); ?>>
			<span class="dnf-slider"></span>
		</label>
	</div>

	<div class="dnf-field-box">
		<label for="gw_bank_details"><?php esc_html_e( 'Wire Transfer & Deposit Instructions', 'dev-nahian-car' ); ?></label>
		<textarea id="gw_bank_details" name="gw_bank_details" class="dnf-textarea" style="font-family: SFMono-Regular, Consolas, 'Liberation Mono', Menlo, monospace; font-size:12.5px; height:96px;"><?php echo esc_textarea( $gw_bank_details ); ?></textarea>
		<p class="dnf-hint"><?php esc_html_e( 'Rendered on the booking confirmation screen and sent within the manifest receipt email.', 'dev-nahian-car' ); ?></p>
	</div>
</div>

<!-- 4. Book Now, Pay Later Handover Settlement -->
<div class="dnf-card">
	<div class="dnf-card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; border-bottom:none; margin-bottom:0; padding-bottom:0;">
		<div>
			<h3 class="dnf-card-title"><?php esc_html_e( 'Pay on Arrival / Terminal Handover', 'dev-nahian-car' ); ?></h3>
			<p class="dnf-card-desc"><?php esc_html_e( 'Permit customers to reserve a chassis online without upfront payment, settling in full during vehicle pickup.', 'dev-nahian-car' ); ?></p>
		</div>
		<label class="dnf-switch" title="<?php esc_attr_e( 'Toggle Pay Later', 'dev-nahian-car' ); ?>">
			<input type="checkbox" name="gw_pay_later" value="1" <?php checked( $gw_pay_later, 1 ); ?>>
			<span class="dnf-slider"></span>
		</label>
	</div>
</div>

<!-- 5. Security Deposit & Collateral Freeze Policies -->
<div class="dnf-card">
	<div class="dnf-card-header">
		<h3 class="dnf-card-title"><?php esc_html_e( 'Security Deposit & Escrow Hold Policies', 'dev-nahian-car' ); ?></h3>
		<p class="dnf-card-desc"><?php esc_html_e( 'Configure collateral freeze parameters applied during vehicle dispatch to protect against fleet damage or toll violations.', 'dev-nahian-car' ); ?></p>
	</div>

	<div class="dnf-grid-2">
		<div class="dnf-field-box">
			<label for="deposit_amount">
				<?php
				printf(
					/* translators: %s: currency symbol */
					esc_html__( 'Default Security Deposit Hold (%s)', 'dev-nahian-car' ),
					esc_html( $currency )
				);
				?>
			</label>
			<input type="number" step="0.01" min="0" id="deposit_amount" name="deposit_amount" class="dnf-input" value="<?php echo esc_attr( (string) $deposit_amount ); ?>">
			<p class="dnf-hint"><?php esc_html_e( 'Baseline pre-authorization freeze captured or held upon vehicle dispatch.', 'dev-nahian-car' ); ?></p>
		</div>

		<div class="dnf-switch-label-wrap">
			<div class="dnf-switch-info">
				<span class="dnf-switch-title"><?php esc_html_e( 'Sovereign Tier Exemption Policy', 'dev-nahian-car' ); ?></span>
				<span class="dnf-hint" style="margin:0;"><?php esc_html_e( 'Waive security deposit freezes for pre-verified enterprise and VIP chauffeur clients.', 'dev-nahian-car' ); ?></span>
			</div>
			<label class="dnf-switch">
				<input type="checkbox" name="no_deposit" value="1" <?php checked( $no_deposit, 1 ); ?>>
				<span class="dnf-slider"></span>
			</label>
		</div>
	</div>
</div>
<?php
/**
 * Settings Sub-Tab View: General Operations (`setting-general.php`)
 * Handles localization, currency policies, pricing logic, service windows, and fleet buffers.
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
if ( isset( $_POST['dev_nahian_save_settings_subtab'] ) && 'general' === sanitize_key( wp_unslash( $_POST['current_subtab'] ?? '' ) ) ) {
	check_admin_referer( 'dev_nahian_settings_subtab_verify', 'dev_nahian_settings_subtab_nonce' );

	$allowed_currencies = array( 'USD', 'BDT', 'EUR', 'GBP', 'AED', 'CAD', 'AUD', 'CHF', 'JPY' );
	$currency_input     = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) );
	$currency           = in_array( $currency_input, $allowed_currencies, true ) ? $currency_input : 'USD';
	update_option( 'dev_nahian_currency', $currency );

	$allowed_positions = array( 'left', 'right', 'left_space', 'right_space' );
	$currency_pos_in   = sanitize_key( wp_unslash( $_POST['currency_pos'] ?? 'left' ) );
	$currency_pos      = in_array( $currency_pos_in, $allowed_positions, true ) ? $currency_pos_in : 'left';
	update_option( 'dev_nahian_currency_pos', $currency_pos, false );

	$allowed_units = array( 'per_day', 'per_hour' );
	$pricing_unit_in = sanitize_key( wp_unslash( $_POST['pricing_unit'] ?? 'per_day' ) );
	$pricing_unit    = in_array( $pricing_unit_in, $allowed_units, true ) ? $pricing_unit_in : 'per_day';
	update_option( 'dev_nahian_pricing_unit', $pricing_unit, false );

	$min_days = max( 1, min( 365, absint( wp_unslash( $_POST['min_days'] ?? 1 ) ) ) );
	update_option( 'dev_nahian_min_days', $min_days, false );

	$min_driver_age = max( 18, min( 75, absint( wp_unslash( $_POST['min_driver_age'] ?? 21 ) ) ) );
	update_option( 'dev_nahian_min_driver_age', $min_driver_age, false );

	// Validate 24-hour time format (HH:MM).
	$time_regex     = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';
	$op_start_input = sanitize_text_field( wp_unslash( $_POST['op_hours_start'] ?? '08:00' ) );
	$op_hours_start = preg_match( $time_regex, $op_start_input ) ? $op_start_input : '08:00';
	update_option( 'dev_nahian_op_hours_start', $op_hours_start, false );

	$op_end_input = sanitize_text_field( wp_unslash( $_POST['op_hours_end'] ?? '20:00' ) );
	$op_hours_end = preg_match( $time_regex, $op_end_input ) ? $op_end_input : '20:00';
	update_option( 'dev_nahian_op_hours_end', $op_hours_end, false );

	$prep_buffer_hours = max( 0, min( 72, absint( wp_unslash( $_POST['prep_buffer_hours'] ?? 2 ) ) ) );
	update_option( 'dev_nahian_prep_buffer_hours', $prep_buffer_hours, false );

	$weekend_ops = isset( $_POST['weekend_ops'] ) ? 1 : 0;
	update_option( 'dev_nahian_weekend_ops', $weekend_ops, false );

	$redirect_url = isset( $base ) ? $base : add_query_arg(
		array(
			'page'   => 'dev-nahian-fleet-manager',
			'tab'    => 'settings',
			'subtab' => 'general',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( add_query_arg( array( 'saved' => '1' ), $redirect_url ) );
	exit;
}

// =========================================================================
// 2. Safe Scope Fallbacks
// =========================================================================
$currency          = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );
$currency_pos      = isset( $currency_pos ) ? $currency_pos : (string) get_option( 'dev_nahian_currency_pos', 'left' );
$pricing_unit      = isset( $pricing_unit ) ? $pricing_unit : (string) get_option( 'dev_nahian_pricing_unit', 'per_day' );
$min_days          = isset( $min_days ) ? $min_days : absint( get_option( 'dev_nahian_min_days', 1 ) );
$min_driver_age    = isset( $min_driver_age ) ? $min_driver_age : absint( get_option( 'dev_nahian_min_driver_age', 21 ) );
$op_hours_start    = isset( $op_hours_start ) ? $op_hours_start : (string) get_option( 'dev_nahian_op_hours_start', '08:00' );
$op_hours_end      = isset( $op_hours_end ) ? $op_hours_end : (string) get_option( 'dev_nahian_op_hours_end', '20:00' );
$prep_buffer_hours = isset( $prep_buffer_hours ) ? $prep_buffer_hours : absint( get_option( 'dev_nahian_prep_buffer_hours', 2 ) );
$weekend_ops       = isset( $weekend_ops ) ? $weekend_ops : absint( get_option( 'dev_nahian_weekend_ops', 1 ) );
?>

<!-- Card 1: Localization & Monetary Governance -->
<div class="dnf-card">
	<div class="dnf-card-header">
		<h3 class="dnf-card-title"><?php esc_html_e( 'Localization & Currency Policies', 'dev-nahian-car' ); ?></h3>
		<p class="dnf-card-desc"><?php esc_html_e( 'Define baseline currency denomination, symbol presentation, and unit pricing mechanisms.', 'dev-nahian-car' ); ?></p>
	</div>

	<div class="dnf-grid-2">
		<!-- Base Currency -->
		<div class="dnf-field-box">
			<label for="currency"><?php esc_html_e( 'Base Operating Currency', 'dev-nahian-car' ); ?></label>
			<div class="dnf-select-wrapper">
				<select id="currency" name="currency" class="dnf-select">
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
			<p class="dnf-hint"><?php esc_html_e( 'Applied universally across rate cards, extras, deposits, and financial accounting ledgers.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Currency Symbol Position -->
		<div class="dnf-field-box">
			<label for="currency_pos"><?php esc_html_e( 'Symbol Formatting & Alignment', 'dev-nahian-car' ); ?></label>
			<div class="dnf-select-wrapper">
				<select id="currency_pos" name="currency_pos" class="dnf-select">
					<option value="left" <?php selected( $currency_pos, 'left' ); ?>><?php esc_html_e( 'Left ($100 / ৳100)', 'dev-nahian-car' ); ?></option>
					<option value="right" <?php selected( $currency_pos, 'right' ); ?>><?php esc_html_e( 'Right (100$ / 100৳)', 'dev-nahian-car' ); ?></option>
					<option value="left_space" <?php selected( $currency_pos, 'left_space' ); ?>><?php esc_html_e( 'Left with space ($ 100 / ৳ 100)', 'dev-nahian-car' ); ?></option>
					<option value="right_space" <?php selected( $currency_pos, 'right_space' ); ?>><?php esc_html_e( 'Right with space (100 $ / 100 ৳)', 'dev-nahian-car' ); ?></option>
				</select>
			</div>
			<p class="dnf-hint"><?php esc_html_e( 'Determines front-end display formatting for booking quotations and invoices.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Base Pricing Unit -->
		<div class="dnf-field-box">
			<label for="pricing_unit"><?php esc_html_e( 'Core Tariff Billing Cycle', 'dev-nahian-car' ); ?></label>
			<div class="dnf-select-wrapper">
				<select id="pricing_unit" name="pricing_unit" class="dnf-select">
					<option value="per_day" <?php selected( $pricing_unit, 'per_day' ); ?>><?php esc_html_e( 'Per Day (Standard 24-Hour Cycle)', 'dev-nahian-car' ); ?></option>
					<option value="per_hour" <?php selected( $pricing_unit, 'per_hour' ); ?>><?php esc_html_e( 'Per Hour (Chauffeur & Flex-Rental)', 'dev-nahian-car' ); ?></option>
				</select>
			</div>
			<p class="dnf-hint"><?php esc_html_e( 'Default tariff increment used by the front-end calculation engine.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Minimum Rental Days -->
		<div class="dnf-field-box">
			<label for="min_days"><?php esc_html_e( 'Minimum Reservation Period (Days)', 'dev-nahian-car' ); ?></label>
			<input type="number" id="min_days" name="min_days" class="dnf-input" value="<?php echo esc_attr( (string) $min_days ); ?>" min="1" max="365">
			<p class="dnf-hint"><?php esc_html_e( 'Requests shorter than this duration are automatically rejected during manifest calculation.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Minimum Driver Age Requirement -->
		<div class="dnf-field-box col-span-full">
			<label for="min_driver_age"><?php esc_html_e( 'Minimum Permissible Driver Age', 'dev-nahian-car' ); ?></label>
			<input type="number" id="min_driver_age" name="min_driver_age" class="dnf-input" value="<?php echo esc_attr( (string) $min_driver_age ); ?>" min="18" max="75" style="max-width: 320px;">
			<p class="dnf-hint"><?php esc_html_e( 'Enforces compliance during KYC identity validation and driver roster verification.', 'dev-nahian-car' ); ?></p>
		</div>
	</div>
</div>

<!-- Card 2: Operating Window & Fleet Telematics Buffers -->
<div class="dnf-card">
	<div class="dnf-card-header">
		<h3 class="dnf-card-title"><?php esc_html_e( 'Operational Windows & Preparation Buffers', 'dev-nahian-car' ); ?></h3>
		<p class="dnf-card-desc"><?php esc_html_e( 'Set facility handover working hours, vehicle inspection rest periods, and weekend dispatch availability.', 'dev-nahian-car' ); ?></p>
	</div>

	<div class="dnf-grid-2">
		<!-- Daily Operating Hours -->
		<div class="dnf-field-box">
			<label><?php esc_html_e( 'Daily Handover Terminal Hours', 'dev-nahian-car' ); ?></label>
			<div style="display:flex; align-items:center; gap:10px;">
				<input type="time" name="op_hours_start" class="dnf-input" value="<?php echo esc_attr( $op_hours_start ); ?>">
				<span style="font-size:12px; color:var(--dnf-text-secondary, #64748b); font-weight:700;"><?php esc_html_e( 'to', 'dev-nahian-car' ); ?></span>
				<input type="time" name="op_hours_end" class="dnf-input" value="<?php echo esc_attr( $op_hours_end ); ?>">
			</div>
			<p class="dnf-hint"><?php esc_html_e( 'Restricts pickup and return dropdown selection slots on the customer booking portal.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Preparation Buffer Hours -->
		<div class="dnf-field-box">
			<label for="prep_buffer_hours"><?php esc_html_e( 'Turnaround & Detailing Buffer (Hours)', 'dev-nahian-car' ); ?></label>
			<input type="number" id="prep_buffer_hours" name="prep_buffer_hours" class="dnf-input" value="<?php echo esc_attr( (string) $prep_buffer_hours ); ?>" min="0" max="72">
			<p class="dnf-hint"><?php esc_html_e( 'Mandatory downtime blocked after every return for walkaround inspection, detailing, and refueling.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Weekend Operations Toggle -->
		<div class="col-span-full">
			<div class="dnf-switch-label-wrap">
				<div class="dnf-switch-info">
					<span class="dnf-switch-title"><?php esc_html_e( 'Enable Weekend Handover & Return Operations', 'dev-nahian-car' ); ?></span>
					<span class="dnf-hint" style="margin:0;"><?php esc_html_e( 'Permits customers to select Saturday and Sunday checkout/return slots.', 'dev-nahian-car' ); ?></span>
				</div>
				<label class="dnf-switch">
					<input type="checkbox" name="weekend_ops" value="1" <?php checked( $weekend_ops, 1 ); ?>>
					<span class="dnf-slider"></span>
				</label>
			</div>
		</div>
	</div>
</div>
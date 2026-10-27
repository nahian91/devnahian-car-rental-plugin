<?php
/**
 * Admin View Tab: System, Payments & Email Branding Settings (Master Router)
 * Pure orchestrator file containing request handling, sub-tab routing, and option normalization.
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

$base            = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'settings',
	),
	admin_url( 'admin.php' )
);
$allowed_subtabs = array( 'general', 'payments', 'emails' );
$current_subtab  = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'general';

if ( ! in_array( $current_subtab, $allowed_subtabs, true ) ) {
	$current_subtab = 'general';
}

$settings_dir = plugin_dir_path( __FILE__ ) . 'settings/';

// =========================================================================
// 1. ROUTE & PROCESS SAVE HANDLER (Before Any HTML Output)
// =========================================================================
if ( isset( $_POST['dev_nahian_save_settings_subtab'] ) ) {
	check_admin_referer( 'dev_nahian_settings_subtab_verify', 'dev_nahian_settings_subtab_nonce' );

	$saved_subtab = isset( $_POST['current_subtab'] ) ? sanitize_key( wp_unslash( $_POST['current_subtab'] ) ) : 'general';

	if ( in_array( $saved_subtab, $allowed_subtabs, true ) ) {
		$handler_file = $settings_dir . 'setting-' . $saved_subtab . '.php';
		if ( file_exists( $handler_file ) ) {
			require_once $handler_file;
		}
	}
}

// Ensure WordPress media library scripts are active for branding image uploads.
if ( 'emails' === $current_subtab ) {
	wp_enqueue_media();
}

// =========================================================================
// 2. RETRIEVE ALL GLOBAL OPTIONS WITH PERSISTED VALUES
// =========================================================================
$currency           = (string) get_option( 'dev_nahian_currency', 'USD' );
$currency_pos       = (string) get_option( 'dev_nahian_currency_pos', 'left' );
$pricing_unit       = (string) get_option( 'dev_nahian_pricing_unit', 'per_day' );
$min_days           = absint( get_option( 'dev_nahian_min_days', 1 ) );
$min_driver_age     = absint( get_option( 'dev_nahian_min_driver_age', 21 ) );
$op_hours_start     = (string) get_option( 'dev_nahian_op_hours_start', '08:00' );
$op_hours_end       = (string) get_option( 'dev_nahian_op_hours_end', '20:00' );
$prep_buffer_hours  = absint( get_option( 'dev_nahian_prep_buffer_hours', 2 ) );
$weekend_ops        = absint( get_option( 'dev_nahian_weekend_ops', 1 ) );

$gw_pay_later       = absint( get_option( 'dev_nahian_gw_pay_later', 1 ) );
$gw_bank_transfer   = absint( get_option( 'dev_nahian_gw_bank_transfer', 1 ) );
$gw_bank_details    = (string) get_option(
	'dev_nahian_gw_bank_details',
	"Bank: JPMorgan Chase & Co.\nAccount Name: Dev Nahian Fleet LLC\nIBAN / Routing: CHASEUS33XXX\nAccount No: 8849-2019-3301"
);
$gw_cash_pickup     = absint( get_option( 'dev_nahian_gw_cash_pickup', 1 ) );
$deposit_amount     = (string) get_option( 'dev_nahian_deposit_amount', '1500.00' );
$no_deposit         = absint( get_option( 'dev_nahian_no_deposit', 1 ) );

$admin_email        = (string) get_option( 'dev_nahian_email_admin', (string) get_option( 'admin_email' ) );
$brand_logo_url     = (string) get_option( 'dev_nahian_email_logo_url', '' );
$email_accent_color = (string) get_option( 'dev_nahian_email_accent_color', '#0d9488' );
$email_signature    = (string) get_option(
	'dev_nahian_email_signature',
	'Thank you for choosing our premier car rental service.'
);
?>

<style>
	/* Scoped Settings Styles & Scrollbar Prevention */
	.dnf-settings-scope,
	.dnf-settings-scope * {
		box-sizing: border-box !important;
	}

	.dnf-settings-scope {
		display: flex;
		flex-direction: column;
		gap: 20px;
		width: 100%;
		max-width: 100%;
		overflow-x: clip;
	}

	/* Top Executive Header */
	.dnf-settings-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--dnf-bg-card, #ffffff);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 20px 24px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		flex-wrap: wrap;
		gap: 16px;
		width: 100%;
	}
	.dnf-settings-title-area {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-settings-heading {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
	}

	/* Subnavigation Tabs Toolbar */
	.dnf-settings-nav {
		display: flex;
		align-items: center;
		gap: 8px;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		flex-wrap: wrap;
	}
	.dnf-settings-nav .dnf-subnav-link {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 8px 14px;
		border-radius: var(--dnf-radius-md, 10px);
		font-size: 13px;
		font-weight: 600;
		text-decoration: none;
		color: var(--dnf-text-secondary, #475569);
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		transition: all 0.15s ease-in-out;
		line-height: 1.2;
		box-sizing: border-box;
	}
	.dnf-settings-nav .dnf-subnav-link:hover {
		background: var(--dnf-bg-subtle, #f8fafc);
		color: var(--dnf-text-primary, #0f172a);
		border-color: var(--dnf-border-hover, #cbd5e1);
		transform: translateY(-1px);
	}
	.dnf-settings-nav .dnf-subnav-link.active {
		background: var(--dnf-primary, #0f172a);
		color: #ffffff !important;
		border-color: var(--dnf-primary, #0f172a);
		box-shadow: 0 2px 4px rgba(15, 23, 42, 0.08);
	}

	/* Setting Panels / Cards */
	.dnf-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 28px;
		margin-bottom: 22px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
		width: 100%;
	}
	.dnf-card-header {
		margin-bottom: 22px;
		padding-bottom: 14px;
		border-bottom: 1px solid var(--dnf-border-subtle, #f1f5f9);
	}
	.dnf-card-title {
		margin: 0 0 4px;
		font-size: 16px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.01em;
	}
	.dnf-card-desc {
		margin: 0;
		font-size: 12.5px;
		color: var(--dnf-text-secondary, #64748b);
	}

	/* Settings Field Grids */
	.dnf-grid-2 {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 20px;
	}
	@media (max-width: 800px) {
		.dnf-grid-2 {
			grid-template-columns: 1fr;
		}
	}
	.col-span-full {
		grid-column: 1 / -1;
	}

	/* Modern Form Inputs & Selects */
	.dnf-field-box {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-field-box label {
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-secondary, #475569);
	}
	.dnf-hint {
		font-size: 11.5px;
		color: var(--dnf-text-tertiary, #94a3b8);
		margin: 2px 0 0;
		line-height: 1.4;
	}

	/* iOS-style Modern Toggle Switches */
	.dnf-switch-label-wrap {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 12px 16px;
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		gap: 16px;
	}
	.dnf-switch-info {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}
	.dnf-switch-title {
		font-size: 13.5px;
		font-weight: 700;
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-switch {
		position: relative;
		display: inline-block;
		width: 44px;
		height: 24px;
		flex-shrink: 0;
	}
	.dnf-switch input {
		opacity: 0;
		width: 0;
		height: 0;
	}
	.dnf-slider {
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: var(--dnf-border, #cbd5e1);
		transition: 0.2s ease;
		border-radius: 24px;
	}
	.dnf-slider:before {
		position: absolute;
		content: "";
		height: 18px;
		width: 18px;
		left: 3px;
		bottom: 3px;
		background-color: #ffffff;
		transition: 0.2s ease;
		border-radius: 50%;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.15);
	}
	.dnf-switch input:checked + .dnf-slider {
		background-color: var(--dnf-accent-teal, #0d9488);
	}
	.dnf-switch input:checked + .dnf-slider:before {
		transform: translateX(20px);
	}

	/* Notice Overrides */
	.dnf-settings-scope .notice {
		margin: 0 !important;
		border-radius: var(--dnf-radius-md, 8px);
		border-left-width: 4px !important;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
	}
</style>

<div class="dnf-settings-scope">

	<!-- Executive Header Panel -->
	<div class="dnf-settings-header">
		<div class="dnf-settings-title-area">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'Configuration Active', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext"><?php esc_html_e( 'Global Currencies, Payment Gateways & Email Brand Templates', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-settings-heading"><?php esc_html_e( 'System & Terminal Settings', 'dev-nahian-car' ); ?></h2>
		</div>
	</div>

	<?php
	// Contextual Feedback Messages
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'dev-nahian-car' ) . '</strong> ' . esc_html__( 'System configuration parameters saved successfully.', 'dev-nahian-car' ) . '</p></div>';
	}
	?>

	<!-- Sub-Navigation Tabs Bar -->
	<nav class="dnf-settings-nav">
		<a href="<?php echo esc_url( add_query_arg( 'subtab', 'general', $base ) ); ?>" class="dnf-subnav-link <?php echo ( 'general' === $current_subtab ) ? 'active' : ''; ?>">
			<?php echo dev_nahian_get_icon( 'settings' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'General Operations', 'dev-nahian-car' ); ?></span>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'subtab', 'payments', $base ) ); ?>" class="dnf-subnav-link <?php echo ( 'payments' === $current_subtab ) ? 'active' : ''; ?>">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
			<span><?php esc_html_e( 'Payment Gateways & Escrow', 'dev-nahian-car' ); ?></span>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'subtab', 'emails', $base ) ); ?>" class="dnf-subnav-link <?php echo ( 'emails' === $current_subtab ) ? 'active' : ''; ?>">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
			<span><?php esc_html_e( 'Email Manifest Branding', 'dev-nahian-car' ); ?></span>
		</a>
	</nav>

	<form method="post" action="" id="dnfSettingsForm">
		<?php wp_nonce_field( 'dev_nahian_settings_subtab_verify', 'dev_nahian_settings_subtab_nonce' ); ?>
		<input type="hidden" name="dev_nahian_save_settings_subtab" value="1">
		<input type="hidden" name="current_subtab" value="<?php echo esc_attr( $current_subtab ); ?>">

		<?php
		// Include the active sub-tab view file safely.
		$view_file = $settings_dir . 'setting-' . $current_subtab . '.php';
		if ( file_exists( $view_file ) ) {
			include $view_file;
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Settings sub-tab view template not found.', 'dev-nahian-car' ) . '</p></div>';
		}
		?>

		<!-- Unified Save Controls -->
		<div style="margin-top: 24px; display: flex; gap: 12px; align-items: center;">
			<button type="submit" class="dnf-btn dnf-btn-primary">
				<?php echo dev_nahian_get_icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span>
					<?php
					if ( 'emails' === $current_subtab ) {
						esc_html_e( 'Save Email Branding Template', 'dev-nahian-car' );
					} elseif ( 'payments' === $current_subtab ) {
						esc_html_e( 'Save Gateway Rails & Deposit Terms', 'dev-nahian-car' );
					} else {
						esc_html_e( 'Save General Operational Settings', 'dev-nahian-car' );
					}
					?>
				</span>
			</button>
		</div>
	</form>

</div>
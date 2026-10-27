<?php
/**
 * Settings Sub-Tab View: Emails & Multi-Template Branding (`setting-emails.php`)
 * Configures notification dispatchers, brand assets, and multi-state booking lifecycle email templates.
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
if ( isset( $_POST['dev_nahian_save_settings_subtab'] ) && 'emails' === sanitize_key( wp_unslash( $_POST['current_subtab'] ?? '' ) ) ) {
	check_admin_referer( 'dev_nahian_settings_subtab_verify', 'dev_nahian_settings_subtab_nonce' );

	$admin_email_input = sanitize_email( wp_unslash( $_POST['admin_email'] ?? '' ) );
	if ( is_email( $admin_email_input ) ) {
		update_option( 'dev_nahian_email_admin', $admin_email_input, false );
	}

	$brand_logo_url = esc_url_raw( wp_unslash( $_POST['brand_logo_url'] ?? '' ) );
	update_option( 'dev_nahian_email_logo_url', $brand_logo_url, false );

	$raw_color          = sanitize_hex_color( wp_unslash( $_POST['email_accent_color'] ?? '#0d9488' ) );
	$email_accent_color = ! empty( $raw_color ) ? $raw_color : '#0d9488';
	update_option( 'dev_nahian_email_accent_color', $email_accent_color, false );

	$email_signature = sanitize_textarea_field( wp_unslash( $_POST['email_signature'] ?? '' ) );
	update_option( 'dev_nahian_email_signature', $email_signature, false );

	// Email Subjects & Custom Notification Toggles.
	$subject_received  = sanitize_text_field( wp_unslash( $_POST['email_subject_received'] ?? __( 'Reservation Received - Manifest #{booking_code}', 'dev-nahian-car' ) ) );
	$subject_confirmed = sanitize_text_field( wp_unslash( $_POST['email_subject_confirmed'] ?? __( 'Reservation Confirmed - Ready for Handover #{booking_code}', 'dev-nahian-car' ) ) );
	$subject_cancelled = sanitize_text_field( wp_unslash( $_POST['email_subject_cancelled'] ?? __( 'Reservation Cancelled - #{booking_code}', 'dev-nahian-car' ) ) );
	$subject_completed = sanitize_text_field( wp_unslash( $_POST['email_subject_completed'] ?? __( 'Rental Settled & Return Completed #{booking_code}', 'dev-nahian-car' ) ) );

	update_option( 'dev_nahian_email_sub_received', $subject_received, false );
	update_option( 'dev_nahian_email_sub_confirmed', $subject_confirmed, false );
	update_option( 'dev_nahian_email_sub_cancelled', $subject_cancelled, false );
	update_option( 'dev_nahian_email_sub_completed', $subject_completed, false );

	wp_safe_redirect( add_query_arg( array( 'saved' => '1' ), $base ) );
	exit;
}

// =========================================================================
// 2. Load Persisted Configuration
// =========================================================================
$admin_email        = (string) get_option( 'dev_nahian_email_admin', (string) get_option( 'admin_email' ) );
$brand_logo_url     = (string) get_option( 'dev_nahian_email_logo_url', '' );
$email_accent_color = (string) get_option( 'dev_nahian_email_accent_color', '#0d9488' );
$email_signature    = (string) get_option( 'dev_nahian_email_signature', 'Thank you for choosing our premier car rental service.' );

$subject_received  = (string) get_option( 'dev_nahian_email_sub_received', __( 'Reservation Received - Manifest #{booking_code}', 'dev-nahian-car' ) );
$subject_confirmed = (string) get_option( 'dev_nahian_email_sub_confirmed', __( 'Reservation Confirmed - Ready for Handover #{booking_code}', 'dev-nahian-car' ) );
$subject_cancelled = (string) get_option( 'dev_nahian_email_sub_cancelled', __( 'Reservation Cancelled - #{booking_code}', 'dev-nahian-car' ) );
$subject_completed = (string) get_option( 'dev_nahian_email_sub_completed', __( 'Rental Settled & Return Completed #{booking_code}', 'dev-nahian-car' ) );
?>

<style>
	/* Scoped Email Settings Layout */
	.dnf-email-workspace {
		display: grid;
		grid-template-columns: 1.15fr 1fr;
		gap: 24px;
		align-items: start;
	}
	@media (max-width: 1024px) {
		.dnf-email-workspace {
			grid-template-columns: 1fr;
		}
	}

	.dnf-card-section-divider {
		margin: 20px 0 16px;
		padding-top: 16px;
		border-top: 1px solid var(--dnf-border, #e2e8f0);
		font-size: 11px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: var(--dnf-accent-teal, #0d9488);
		display: flex;
		align-items: center;
		gap: 8px;
	}

	/* Interactive Template Selector Tabs */
	.dnf-template-switch-bar {
		display: flex;
		gap: 6px;
		background: #f1f5f9;
		padding: 4px;
		border-radius: var(--dnf-radius-md, 8px);
		margin-bottom: 14px;
		flex-wrap: wrap;
	}
	.dnf-tmpl-tab {
		flex: 1;
		min-width: 80px;
		text-align: center;
		font-size: 11.5px;
		font-weight: 700;
		padding: 6px 10px;
		border-radius: 6px;
		color: var(--dnf-text-secondary, #64748b);
		background: transparent;
		border: none;
		cursor: pointer;
		transition: all 0.15s ease;
	}
	.dnf-tmpl-tab:hover {
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-tmpl-tab.active {
		background: #ffffff;
		color: var(--dnf-text-primary, #0f172a);
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
	}

	/* Live Email Client Simulator Frame */
	.dnf-email-preview-wrap {
		background: #f8fafc;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 24px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
	}
	.dnf-email-card {
		background: #ffffff;
		border-radius: var(--dnf-radius-md, 8px);
		box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
		overflow: hidden;
		border: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-email-header {
		padding: 20px 24px;
		border-bottom: 1px solid #f1f5f9;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
	}
	.dnf-email-body {
		padding: 28px 24px;
		font-size: 13.5px;
		line-height: 1.6;
		color: var(--dnf-text-primary, #334155);
	}
	.dnf-email-btn {
		display: inline-block;
		color: #ffffff !important;
		text-decoration: none;
		padding: 10px 24px;
		border-radius: var(--dnf-radius-md, 8px);
		font-weight: 700;
		font-size: 13px;
		margin-top: 16px;
		transition: opacity 0.2s ease;
	}
	.dnf-email-btn:hover {
		opacity: 0.9;
	}
	.dnf-email-footer {
		padding: 16px 24px;
		background: #f8fafc;
		border-top: 1px solid #f1f5f9;
		font-size: 11.5px;
		color: var(--dnf-text-secondary, #64748b);
		text-align: center;
		white-space: pre-wrap;
	}

	/* Status Pills */
	.dnf-status-pill.received {
		background: #eff6ff;
		color: #2563eb;
		border: 1px solid #dbeafe;
	}
	.dnf-status-pill.confirmed {
		background: #f0fdfa;
		color: #0d9488;
		border: 1px solid #ccfbf1;
	}
	.dnf-status-pill.cancelled {
		background: #fef2f2;
		color: #dc2626;
		border: 1px solid #fecaca;
	}
	.dnf-status-pill.completed {
		background: #f1f5f9;
		color: #475569;
		border: 1px solid #e2e8f0;
	}
</style>

<div class="dnf-email-workspace">

	<!-- Left: Configuration Form Surface -->
	<div class="dnf-card" style="margin-bottom:0;">
		<div class="dnf-card-header">
			<h3 class="dnf-card-title"><?php esc_html_e( 'Dispatch Routing & Visual Identity', 'dev-nahian-car' ); ?></h3>
			<p class="dnf-card-desc"><?php esc_html_e( 'Define administrative recipient channels, brand logos, and styling for notifications.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Dispatch Routing -->
		<div class="dnf-field-box" style="margin-bottom:18px;">
			<label for="admin_email">
				<?php esc_html_e( 'Administrative Recipient (Inbox)', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
			</label>
			<input type="email" id="admin_email" name="admin_email" class="dnf-input" value="<?php echo esc_attr( $admin_email ); ?>" required>
			<p class="dnf-hint"><?php esc_html_e( 'Dispatch alerts for new checkout requests and payment approvals will route here.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Brand Logo Asset -->
		<div class="dnf-field-box" style="margin-bottom:18px;">
			<label for="brand_logo_url"><?php esc_html_e( 'Email Header Brand Logo', 'dev-nahian-car' ); ?></label>
			<div style="display:flex; gap:10px; align-items:center;">
				<input type="url" id="brand_logo_url" name="brand_logo_url" class="dnf-input" value="<?php echo esc_url( $brand_logo_url ); ?>" placeholder="https://domain.com/brand-logo.png">
				<button type="button" class="dnf-btn dnf-btn-secondary" id="dnfSelectLogoBtn" style="white-space:nowrap;">
					<?php echo dev_nahian_get_icon( 'upload' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Media Library', 'dev-nahian-car' ); ?></span>
				</button>
			</div>
			<p class="dnf-hint"><?php esc_html_e( 'Transparent PNG or SVG with maximum recommended width of 240px.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Accent Palette -->
		<div class="dnf-field-box" style="margin-bottom:18px;">
			<label for="email_accent_color"><?php esc_html_e( 'Brand Accent Color', 'dev-nahian-car' ); ?></label>
			<div style="display:flex; align-items:center; gap:12px;">
				<input type="color" id="email_accent_color" name="email_accent_color" value="<?php echo esc_attr( $email_accent_color ); ?>" style="width:48px; height:38px; border:1px solid var(--dnf-border, #e2e8f0); border-radius:6px; cursor:pointer; padding:2px; background:#fff;">
				<input type="text" id="email_accent_hex" class="dnf-input" value="<?php echo esc_attr( $email_accent_color ); ?>" style="max-width:140px; font-family:monospace;" readonly>
			</div>
			<p class="dnf-hint"><?php esc_html_e( 'Styles the action CTA buttons, voucher codes, and accent borders.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Footer Signature -->
		<div class="dnf-field-box" style="margin-bottom:24px;">
			<label for="email_signature"><?php esc_html_e( 'Legal Disclaimer & Footer Signature', 'dev-nahian-car' ); ?></label>
			<textarea id="email_signature" name="email_signature" class="dnf-textarea" rows="3"><?php echo esc_textarea( $email_signature ); ?></textarea>
			<p class="dnf-hint"><?php esc_html_e( 'Appended to the bottom of all customer-facing transactional emails.', 'dev-nahian-car' ); ?></p>
		</div>

		<!-- Lifecycle Subject Customizers -->
		<div class="dnf-card-section-divider">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>
			<?php esc_html_e( 'Lifecycle Subject Line Templates', 'dev-nahian-car' ); ?>
		</div>

		<div class="dnf-field-box" style="margin-bottom:12px;">
			<label for="email_subject_received"><?php esc_html_e( '1. Order Received (Pending)', 'dev-nahian-car' ); ?></label>
			<input type="text" id="email_subject_received" name="email_subject_received" class="dnf-input" value="<?php echo esc_attr( $subject_received ); ?>">
		</div>

		<div class="dnf-field-box" style="margin-bottom:12px;">
			<label for="email_subject_confirmed"><?php esc_html_e( '2. Order Confirmed (Handover)', 'dev-nahian-car' ); ?></label>
			<input type="text" id="email_subject_confirmed" name="email_subject_confirmed" class="dnf-input" value="<?php echo esc_attr( $subject_confirmed ); ?>">
		</div>

		<div class="dnf-field-box" style="margin-bottom:12px;">
			<label for="email_subject_cancelled"><?php esc_html_e( '3. Order Cancelled (Terminated)', 'dev-nahian-car' ); ?></label>
			<input type="text" id="email_subject_cancelled" name="email_subject_cancelled" class="dnf-input" value="<?php echo esc_attr( $subject_cancelled ); ?>">
		</div>

		<div class="dnf-field-box" style="margin-bottom:0;">
			<label for="email_subject_completed"><?php esc_html_e( '4. Trip Completed (Settled)', 'dev-nahian-car' ); ?></label>
			<input type="text" id="email_subject_completed" name="email_subject_completed" class="dnf-input" value="<?php echo esc_attr( $subject_completed ); ?>">
		</div>
	</div>

	<!-- Right: Interactive Live Simulator Surface -->
	<div>
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
			<h4 style="margin:0; font-size:12px; font-weight:800; text-transform:uppercase; color:var(--dnf-text-secondary, #64748b); letter-spacing:0.06em;">
				&bull; <?php esc_html_e( 'Live Template Preview Simulator', 'dev-nahian-car' ); ?>
			</h4>
			<span style="font-size:11px; color:var(--dnf-text-tertiary, #94a3b8);"><?php esc_html_e( 'Real-time rendering', 'dev-nahian-car' ); ?></span>
		</div>

		<!-- Interactive Template State Toggles -->
		<div class="dnf-template-switch-bar">
			<button type="button" class="dnf-tmpl-tab active" data-tmpl="received"><?php esc_html_e( 'Received', 'dev-nahian-car' ); ?></button>
			<button type="button" class="dnf-tmpl-tab" data-tmpl="confirmed"><?php esc_html_e( 'Confirmed', 'dev-nahian-car' ); ?></button>
			<button type="button" class="dnf-tmpl-tab" data-tmpl="cancelled"><?php esc_html_e( 'Cancelled', 'dev-nahian-car' ); ?></button>
			<button type="button" class="dnf-tmpl-tab" data-tmpl="completed"><?php esc_html_e( 'Completed', 'dev-nahian-car' ); ?></button>
		</div>

		<!-- Simulated Client Frame -->
		<div class="dnf-email-preview-wrap">
			<div class="dnf-email-card">
				<!-- Header -->
				<div class="dnf-email-header">
					<div id="emailPreviewLogoArea">
						<?php if ( ! empty( $brand_logo_url ) ) : ?>
							<img src="<?php echo esc_url( $brand_logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" style="max-height:36px; max-width:180px; object-fit:contain;" id="emailPreviewLogoImg">
						<?php else : ?>
							<strong style="font-size:15px; font-weight:800; color:var(--dnf-text-primary, #0f172a);" id="emailPreviewLogoText"><?php bloginfo( 'name' ); ?></strong>
						<?php endif; ?>
					</div>
					<div>
						<span id="emailPreviewStatusPill" class="dnf-status-pill received">&bull; Received</span>
					</div>
				</div>

				<!-- Dynamic Content Body -->
				<div class="dnf-email-body">
					<p style="margin:0 0 12px; font-weight:700; color:#0f172a;" id="emailPreviewGreeting"><?php esc_html_e( 'Hello Jonathan Sterling,', 'dev-nahian-car' ); ?></p>
					
					<p style="margin:0 0 16px;" id="emailPreviewMessage">
						<?php esc_html_e( 'Your reservation request has been received and is currently under review by our operations dispatch team.', 'dev-nahian-car' ); ?>
					</p>

					<!-- Telematics & Manifest Card -->
					<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 16px; font-size:12.5px; margin-bottom:16px;">
						<div style="margin-bottom:4px;"><strong><?php esc_html_e( 'Manifest Code:', 'dev-nahian-car' ); ?></strong> <code class="dnf-code">#BK-9204</code></div>
						<div style="margin-bottom:4px;"><strong><?php esc_html_e( 'Vehicle Chassis:', 'dev-nahian-car' ); ?></strong> 2024 Mercedes-Benz S-Class</div>
						<div style="margin-bottom:4px;" id="emailPreviewSchedule"><strong><?php esc_html_e( 'Handover Window:', 'dev-nahian-car' ); ?></strong> Oct 28 &rarr; Nov 02</div>
						<div id="emailPreviewAmount"><strong><?php esc_html_e( 'Total Realization:', 'dev-nahian-car' ); ?></strong> USD 1,450.00</div>
					</div>

					<a href="#" id="emailPreviewCta" class="dnf-email-btn" style="background-color:<?php echo esc_attr( $email_accent_color ); ?>;" onclick="return false;">
						<?php esc_html_e( 'Review Reservation Manifest', 'dev-nahian-car' ); ?>
					</a>
				</div>

				<!-- Footer -->
				<div class="dnf-email-footer" id="emailPreviewFooter">
					<?php echo esc_html( $email_signature ); ?>
				</div>
			</div>
		</div>
	</div>

</div>

<!-- Real-Time Simulator & Media Uploader Controller -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	// 1. WordPress Media Frame Uploader
	var logoBtn = document.getElementById('dnfSelectLogoBtn');
	var logoInput = document.getElementById('brand_logo_url');
	var logoArea = document.getElementById('emailPreviewLogoArea');

	if (logoBtn && logoInput) {
		logoBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var frame = wp.media({
				title: <?php echo wp_json_encode( __( 'Select Brand Logo', 'dev-nahian-car' ) ); ?>,
				button: { text: <?php echo wp_json_encode( __( 'Apply as Logo', 'dev-nahian-car' ) ); ?> },
				multiple: false
			});

			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				logoInput.value = attachment.url;
				renderPreviewLogo(attachment.url);
			});

			frame.open();
		});

		logoInput.addEventListener('input', function() {
			renderPreviewLogo(this.value.trim());
		});
	}

	function renderPreviewLogo(url) {
		if (!logoArea) return;
		if (url) {
			logoArea.innerHTML = '<img src="' + url + '" alt="Brand Logo" style="max-height:36px; max-width:180px; object-fit:contain;">';
		} else {
			logoArea.innerHTML = '<strong style="font-size:15px; font-weight:800; color:#0f172a;"><?php echo esc_js( get_bloginfo( 'name' ) ); ?></strong>';
		}
	}

	// 2. Real-Time Accent Color & Signature Binding
	var colorPicker = document.getElementById('email_accent_color');
	var colorHex    = document.getElementById('email_accent_hex');
	var ctaBtn      = document.getElementById('emailPreviewCta');
	var sigInput    = document.getElementById('email_signature');
	var sigPreview  = document.getElementById('emailPreviewFooter');

	if (colorPicker && ctaBtn) {
		colorPicker.addEventListener('input', function() {
			ctaBtn.style.backgroundColor = this.value;
			if (colorHex) {
				colorHex.value = this.value;
			}
		});
	}

	if (sigInput && sigPreview) {
		sigInput.addEventListener('input', function() {
			sigPreview.textContent = this.value;
		});
	}

	// 3. Multi-Template Simulator State Controller
	var templateTabs   = document.querySelectorAll('.dnf-tmpl-tab');
	var statusPill     = document.getElementById('emailPreviewStatusPill');
	var greeting       = document.getElementById('emailPreviewGreeting');
	var message        = document.getElementById('emailPreviewMessage');
	var schedule       = document.getElementById('emailPreviewSchedule');
	var amount         = document.getElementById('emailPreviewAmount');

	var templates = {
		received: {
			pillText: '• Order Received',
			pillClass: 'dnf-status-pill received',
			greeting: 'Hello Jonathan Sterling,',
			message: 'Your rental reservation has been successfully registered and placed in queue. Our dispatch desk is preparing vehicle validation.',
			schedule: '<strong>Requested Window:</strong> Oct 28, 2026 → Nov 02, 2026',
			amount: '<strong>Estimated Total:</strong> USD 1,450.00 (Pending Payment Approval)',
			cta: 'Review Booking Details'
		},
		confirmed: {
			pillText: '• Handover Ready',
			pillClass: 'dnf-status-pill confirmed',
			greeting: 'Your Trip is Confirmed, Jonathan!',
			message: 'Vehicle asset allocated and ready for physical handover. Please present your driving license and confirmation barcode at the desk.',
			schedule: '<strong>Handover Slot:</strong> Oct 28, 2026 at 09:30 AM (Central Bay 04)',
			amount: '<strong>Settled Escrow:</strong> USD 1,450.00 Paid in Full',
			cta: 'Download Handover Barcode'
		},
		cancelled: {
			pillText: '• Cancelled',
			pillClass: 'dnf-status-pill cancelled',
			greeting: 'Reservation Cancelled',
			message: 'Your booking has been cancelled in accordance with our terms. Any applicable escrow release or deposit reversals are processing.',
			schedule: '<strong>Cancelled Window:</strong> Oct 28, 2026 → Nov 02, 2026',
			amount: '<strong>Settlement Adjustment:</strong> Refund USD 1,450.00 Initiated',
			cta: 'View Cancellation Statement'
		},
		completed: {
			pillText: '• Settled & Closed',
			pillClass: 'dnf-status-pill completed',
			greeting: 'Thank You for Choosing Us!',
			message: 'Your return inspection audit is finalized. Fuel telemetry, odometer limits, and condition logs were successfully verified.',
			schedule: '<strong>Returned At:</strong> Nov 02, 2026 at 05:15 PM (Terminal Bay A)',
			amount: '<strong>Security Deposit:</strong> USD 500.00 Released to Card',
			cta: 'Download Final Invoice Receipt'
		}
	};

	templateTabs.forEach(function(tab) {
		tab.addEventListener('click', function() {
			templateTabs.forEach(function(t) { t.classList.remove('active'); });
			this.classList.add('active');

			var tmplKey = this.getAttribute('data-tmpl');
			var data    = templates[tmplKey];

			if (data) {
				if (statusPill) {
					statusPill.className = data.pillClass;
					statusPill.textContent = data.pillText;
				}
				if (greeting) greeting.textContent = data.greeting;
				if (message)  message.textContent  = data.message;
				if (schedule) schedule.innerHTML   = data.schedule;
				if (amount)   amount.innerHTML     = data.amount;
				if (ctaBtn)   ctaBtn.textContent   = data.cta;
			}
		});
	});
});
</script>
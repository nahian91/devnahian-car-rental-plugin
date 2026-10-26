<?php
/**
 * Settings Sub-Tab View: Emails & Branding (`setting-emails.php`)
 * Upgraded to elite SaaS UI/UX standards with polished input boxes and card grouping.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handler logic if POST submission targets emails
if ( isset( $_POST['dev_nahian_save_settings_subtab'] ) && 'emails' === sanitize_key( $_POST['current_subtab'] ?? '' ) ) {
    $admin_email_input = sanitize_email( wp_unslash( $_POST['admin_email'] ?? '' ) );
    if ( is_email( $admin_email_input ) ) {
        update_option( 'dev_nahian_email_admin', $admin_email_input, false );
    }

    $brand_logo_url     = esc_url_raw( wp_unslash( $_POST['brand_logo_url'] ?? '' ) );
    update_option( 'dev_nahian_email_logo_url', $brand_logo_url, false );

    $email_accent_color = sanitize_hex_color( wp_unslash( $_POST['email_accent_color'] ?? '#0d9488' ) );
    update_option( 'dev_nahian_email_accent_color', $email_accent_color ?: '#0d9488', false );

    $email_signature    = sanitize_textarea_field( wp_unslash( $_POST['email_signature'] ?? '' ) );
    update_option( 'dev_nahian_email_signature', $email_signature, false );

    $notice = '<div class="notice notice-success is-dismissible" style="margin: 15px 0 20px;"><p>' . esc_html__( 'Email branding and notification settings saved.', 'dev-nahian-car' ) . '</p></div>';
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
    .dev-nahian-textarea {
        height: 86px !important;
        padding: 10px 14px !important;
    }
    .dev-nahian-input:focus, .dev-nahian-select:focus, .dev-nahian-textarea:focus {
        background: #ffffff !important;
        border-color: #0f172a !important;
        box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
    }
    .dev-nahian-email-preview-wrap {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        max-width: 540px;
        margin-top: 10px;
    }
    .dev-nahian-email-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(15,23,42,0.06);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .dev-nahian-email-header {
        padding: 20px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dev-nahian-email-body {
        padding: 24px;
        font-size: 13.5px;
        line-height: 1.6;
        color: #334155;
    }
    .dev-nahian-email-btn {
        display: inline-block;
        color: #ffffff !important;
        text-decoration: none;
        padding: 10px 22px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        margin-top: 14px;
    }
    .dev-nahian-email-footer {
        padding: 16px 24px;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        font-size: 11.5px;
        color: #64748b;
        text-align: center;
    }
</style>

<div style="display:grid; grid-template-columns:1.2fr 1fr; gap:24px; align-items:start;">
    
    <!-- Left: Form Controls -->
    <div class="dn-settings-card" style="margin-bottom:0;">
        <div class="dn-settings-header">
            <h3 class="dn-settings-title">
                <span class="dashicons dashicons-email-alt"></span>
                <?php esc_html_e( 'Sender & Email Branding', 'dev-nahian-car' ); ?>
            </h3>
        </div>
        <p style="margin:0 0 20px; font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Configure email notifications and custom logo presentation.', 'dev-nahian-car' ); ?></p>

        <div class="dev-nahian-field-box" style="margin-bottom:18px;">
            <label for="admin_email"><?php esc_html_e( 'Notification Recipient (Admin)', 'dev-nahian-car' ); ?></label>
            <input type="email" id="admin_email" name="admin_email" class="dev-nahian-input" value="<?php echo esc_attr( $admin_email ); ?>" required>
            <p class="dev-nahian-hint"><?php esc_html_e( 'New booking alerts will be dispatched to this address.', 'dev-nahian-car' ); ?></p>
        </div>

        <div class="dev-nahian-field-box" style="margin-bottom:18px;">
            <label for="brand_logo_url"><?php esc_html_e( 'Brand Logo URL', 'dev-nahian-car' ); ?></label>
            <div style="display:flex; gap:10px;">
                <input type="url" id="brand_logo_url" name="brand_logo_url" class="dev-nahian-input" value="<?php echo esc_url( $brand_logo_url ); ?>" placeholder="https://example.com/logo.png">
                <button type="button" class="button button-secondary" id="dnSelectLogoBtn" style="height:42px; padding:0 16px; border-radius:8px; font-weight:700;"><?php esc_html_e( 'Select Logo', 'dev-nahian-car' ); ?></button>
            </div>
        </div>

        <div class="dev-nahian-field-box" style="margin-bottom:18px;">
            <label for="email_accent_color"><?php esc_html_e( 'Email Accent Color', 'dev-nahian-car' ); ?></label>
            <input type="color" id="email_accent_color" name="email_accent_color" value="<?php echo esc_attr( $email_accent_color ); ?>" style="height:42px; width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:4px; cursor:pointer;" onchange="document.getElementById('emailPreviewCta').style.backgroundColor = this.value;">
        </div>

        <div class="dev-nahian-field-box" style="margin-bottom:0;">
            <label for="email_signature"><?php esc_html_e( 'Footer Signature Text', 'dev-nahian-car' ); ?></label>
            <textarea id="email_signature" name="email_signature" class="dev-nahian-textarea" oninput="document.getElementById('emailPreviewFooter').textContent = this.value;"><?php echo esc_textarea( $email_signature ); ?></textarea>
        </div>
    </div>

    <!-- Right: Interactive Live Email Preview -->
    <div>
        <h4 style="margin:0 0 10px; font-size:13px; font-weight:800; text-transform:uppercase; color:#64748b; letter-spacing:0.05em;">
            &bull; <?php esc_html_e( 'Live Preview', 'dev-nahian-car' ); ?>
        </h4>

        <div class="dev-nahian-email-preview-wrap">
            <div class="dev-nahian-email-card">
                <div class="dev-nahian-email-header">
                    <strong style="font-size:14px; color:#0f172a;" id="emailPreviewLogoName"><?php bloginfo( 'name' ); ?></strong>
                    <span style="font-size:11px; font-weight:700; color:#0d9488;">#BK-8942</span>
                </div>

                <div class="dev-nahian-email-body">
                    <p style="margin:0 0 12px; font-weight:700; color:#0f172a;"><?php esc_html_e( 'Hello Customer,', 'dev-nahian-car' ); ?></p>
                    <p style="margin:0 0 14px;"><?php esc_html_e( 'Your rental reservation for the vehicle is confirmed and scheduled for handover.', 'dev-nahian-car' ); ?></p>

                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; font-size:12.5px; margin-bottom:14px;">
                        <div><strong><?php esc_html_e( 'Chassis:', 'dev-nahian-car' ); ?></strong> Executive Fleet Car</div>
                        <div><strong><?php esc_html_e( 'Pickup:', 'dev-nahian-car' ); ?></strong> Central Bay 04</div>
                        <div><strong><?php esc_html_e( 'Total Settled:', 'dev-nahian-car' ); ?></strong> USD 560.00</div>
                    </div>

                    <a href="#" id="emailPreviewCta" class="dev-nahian-email-btn" style="background-color:<?php echo esc_attr( $email_accent_color ); ?>;" onclick="return false;">
                        <?php esc_html_e( 'View Booking Details', 'dev-nahian-car' ); ?>
                    </a>
                </div>

                <div class="dev-nahian-email-footer" id="emailPreviewFooter">
                    <?php echo esc_html( $email_signature ); ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var logoBtn = document.getElementById('dnSelectLogoBtn');
    var logoInput = document.getElementById('brand_logo_url');

    if (logoBtn && logoInput) {
        logoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var frame = wp.media({
                title: '<?php echo esc_js( __( 'Select Brand Logo', 'dev-nahian-car' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Use as Logo', 'dev-nahian-car' ) ); ?>' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                logoInput.value = attachment.url;
            });

            frame.open();
        });
    }
});
</script>
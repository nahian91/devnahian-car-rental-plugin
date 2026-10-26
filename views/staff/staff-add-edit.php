<?php
/**
 * Staff Module: Create & Edit Workstation (`staff-add-edit.php`)
 * Handles both POST actions and form markup.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action (Create or Update)
if ( isset( $_POST['dev_nahian_add_staff_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_staff_verify', 'dev_nahian_staff_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_staff} WHERE email = %s", $email ) );

        if ( $email_exists ) {
            echo '<div class="notice notice-error is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'A staff member with this email address already exists.', 'dev-nahian-car' ) . '</p></div>';
        } else {
            $staff_code = 'STF-' . strtoupper( wp_generate_password( 5, false, false ) );
            $inserted   = $wpdb->insert(
                $table_staff,
                array(
                    'staff_code' => $staff_code,
                    'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
                    'email'      => $email,
                    'phone'      => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
                    'role_type'  => sanitize_key( $_POST['role_type'] ?? 'chauffeur' ),
                    'license_no' => sanitize_text_field( wp_unslash( $_POST['license_no'] ?? '' ) ),
                    'status'     => sanitize_key( $_POST['status'] ?? 'active' ),
                    'notes'      => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
            );

            if ( false !== $inserted ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'staff', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

if ( isset( $_POST['dev_nahian_edit_staff_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_staff_' . $id, 'dev_nahian_staff_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_staff} WHERE email = %s AND id != %d", $email, $id ) );

        if ( $email_exists ) {
            echo '<div class="notice notice-error is-dismissible" style="margin:15px 0 20px;"><p>' . esc_html__( 'Another staff profile is already registered under this email address.', 'dev-nahian-car' ) . '</p></div>';
        } else {
            $updated = $wpdb->update(
                $table_staff,
                array(
                    'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
                    'email'      => $email,
                    'phone'      => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
                    'role_type'  => sanitize_key( $_POST['role_type'] ?? 'chauffeur' ),
                    'license_no' => sanitize_text_field( wp_unslash( $_POST['license_no'] ?? '' ) ),
                    'status'     => sanitize_key( $_POST['status'] ?? 'active' ),
                    'notes'      => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                ),
                array( 'id' => $id ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );

            if ( false !== $updated ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'staff', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

// 2. Render Form View
$is_edit = ( 'edit' === $action && $id > 0 );
$staff   = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $id ), ARRAY_A ) : array();
?>
<style>
    .dn-staff-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }
    .dn-staff-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    @media (max-width: 960px) {
        .dn-staff-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .dn-staff-grid { grid-template-columns: 1fr; }
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
    .dev-nahian-input, .dev-nahian-select, .dev-nahian-textarea {
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0 14px !important;
        font-size: 13.5px !important;
        height: 42px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .dev-nahian-select {
        height: 42px !important;
        padding-right: 32px !important;
    }
    .dev-nahian-textarea {
        height: 86px !important;
        padding: 10px 14px !important;
        resize: vertical;
    }
</style>

<div class="dn-staff-bar">
    <div class="dn-staff-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        <h2 class="dn-staff-title">
            <?php echo $is_edit ? sprintf( esc_html__( 'Edit Staff: %s', 'dev-nahian-car' ), esc_html( $staff['name'] ?? '' ) ) : esc_html__( 'Register New Staff Member', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<div class="dn-staff-card">
    <form method="post" action="">
        <?php 
        if ( $is_edit ) {
            wp_nonce_field( 'dev_nahian_edit_staff_' . $id, 'dev_nahian_staff_nonce' );
            echo '<input type="hidden" name="dev_nahian_edit_staff_submit" value="1">';
        } else {
            wp_nonce_field( 'dev_nahian_add_staff_verify', 'dev_nahian_staff_nonce' );
            echo '<input type="hidden" name="dev_nahian_add_staff_submit" value="1">';
        }
        ?>

        <div class="dn-staff-grid">
            <div class="dn-form-heading"><?php esc_html_e( '1. Personnel Details', 'dev-nahian-car' ); ?></div>

            <div class="dn-field-box">
                <label for="name"><?php esc_html_e( 'Full Name', 'dev-nahian-car' ); ?></label>
                <input type="text" id="name" name="name" class="dev-nahian-input" value="<?php echo esc_attr( $staff['name'] ?? '' ); ?>" placeholder="e.g. Marcus Vance" required>
            </div>

            <div class="dn-field-box">
                <label for="email"><?php esc_html_e( 'Email Address', 'dev-nahian-car' ); ?></label>
                <input type="email" id="email" name="email" class="dev-nahian-input" value="<?php echo esc_attr( $staff['email'] ?? '' ); ?>" placeholder="staff@example.com" required>
            </div>

            <div class="dn-field-box">
                <label for="phone"><?php esc_html_e( 'Phone Number', 'dev-nahian-car' ); ?></label>
                <input type="text" id="phone" name="phone" class="dev-nahian-input" value="<?php echo esc_attr( $staff['phone'] ?? '' ); ?>" placeholder="+1 (555) 019-2834">
            </div>

            <div class="dn-field-box">
                <label for="role_type"><?php esc_html_e( 'Role / Designation', 'dev-nahian-car' ); ?></label>
                <select id="role_type" name="role_type" class="dev-nahian-select">
                    <option value="chauffeur" <?php selected( $staff['role_type'] ?? 'chauffeur', 'chauffeur' ); ?>><?php esc_html_e( 'Chauffeur / Driver', 'dev-nahian-car' ); ?></option>
                    <option value="dispatcher" <?php selected( $staff['role_type'] ?? '', 'dispatcher' ); ?>><?php esc_html_e( 'Desk Dispatcher', 'dev-nahian-car' ); ?></option>
                    <option value="mechanic" <?php selected( $staff['role_type'] ?? '', 'mechanic' ); ?>><?php esc_html_e( 'Workshop Mechanic', 'dev-nahian-car' ); ?></option>
                    <option value="concierge" <?php selected( $staff['role_type'] ?? '', 'concierge' ); ?>><?php esc_html_e( 'VIP Concierge', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dn-field-box">
                <label for="status"><?php esc_html_e( 'Duty Status', 'dev-nahian-car' ); ?></label>
                <select id="status" name="status" class="dev-nahian-select">
                    <option value="active" <?php selected( $staff['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active / Ready', 'dev-nahian-car' ); ?></option>
                    <option value="on_duty" <?php selected( $staff['status'] ?? '', 'on_duty' ); ?>><?php esc_html_e( 'On Assignment', 'dev-nahian-car' ); ?></option>
                    <option value="off_duty" <?php selected( $staff['status'] ?? '', 'off_duty' ); ?>><?php esc_html_e( 'Off Duty', 'dev-nahian-car' ); ?></option>
                    <option value="suspended" <?php selected( $staff['status'] ?? '', 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dn-field-box">
                <label for="license_no"><?php esc_html_e( 'Chauffeur / Driver License No.', 'dev-nahian-car' ); ?></label>
                <input type="text" id="license_no" name="license_no" class="dev-nahian-input" value="<?php echo esc_attr( $staff['license_no'] ?? '' ); ?>" placeholder="DL-78923-X">
            </div>

            <div class="dn-form-heading"><?php esc_html_e( '2. Operational Notes', 'dev-nahian-car' ); ?></div>

            <div class="dn-field-box col-full">
                <label for="notes"><?php esc_html_e( 'Notes & Credentials', 'dev-nahian-car' ); ?></label>
                <textarea id="notes" name="notes" class="dev-nahian-textarea" placeholder="Security clearance, certified vehicle classes, language proficiencies..."><?php echo esc_textarea( $staff['notes'] ?? '' ); ?></textarea>
            </div>
        </div>

        <p style="margin: 24px 0 0 0; display: flex; gap: 10px;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; height:38px; padding:0 20px; font-weight:700;">
                <?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Save Staff Profile', 'dev-nahian-car' ); ?>
            </button>
            <a href="<?php echo esc_url( $base ); ?>" class="button" style="height:38px; line-height:36px; padding:0 14px;"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        </p>
    </form>
</div>
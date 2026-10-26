<?php
/**
 * Customers Module: Create & Edit Workstation (`customer-add-edit.php`)
 * Handles both POST actions and form markup.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action (Create or Update)
if ( isset( $_POST['dev_nahian_add_customer_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_customer_verify', 'dev_nahian_cust_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_customers} WHERE email = %s", $email ) );

        if ( $email_exists ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'A customer with this email address already exists.', 'dev-nahian-car' ) . '</p></div>';
        } else {
            $customer_code = 'VEL-' . strtoupper( wp_generate_password( 6, false, false ) );

            $inserted = $wpdb->insert(
                $table_customers,
                array(
                    'customer_code'  => $customer_code,
                    'name'           => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
                    'email'          => $email,
                    'phone'          => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
                    'dob'            => ! empty( $_POST['dob'] ) ? sanitize_text_field( wp_unslash( $_POST['dob'] ) ) : null,
                    'country'        => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ),
                    'license_number' => sanitize_text_field( wp_unslash( $_POST['license_number'] ?? '' ) ),
                    'license_expiry' => ! empty( $_POST['license_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['license_expiry'] ) ) : null,
                    'tier'           => sanitize_key( $_POST['tier'] ?? 'standard' ),
                    'status'         => sanitize_key( $_POST['status'] ?? 'pending' ),
                    'notes'          => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                    'created_at'     => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
            );

            if ( false !== $inserted ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'customers', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

if ( isset( $_POST['dev_nahian_edit_customer_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_customer_' . $id, 'dev_nahian_cust_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_customers} WHERE email = %s AND id != %d", $email, $id ) );

        if ( $email_exists ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Another customer profile is already registered under this email address.', 'dev-nahian-car' ) . '</p></div>';
        } else {
            $updated = $wpdb->update(
                $table_customers,
                array(
                    'name'           => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
                    'email'          => $email,
                    'phone'          => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
                    'dob'            => ! empty( $_POST['dob'] ) ? sanitize_text_field( wp_unslash( $_POST['dob'] ) ) : null,
                    'country'        => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ),
                    'license_number' => sanitize_text_field( wp_unslash( $_POST['license_number'] ?? '' ) ),
                    'license_expiry' => ! empty( $_POST['license_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['license_expiry'] ) ) : null,
                    'tier'           => sanitize_key( $_POST['tier'] ?? 'standard' ),
                    'status'         => sanitize_key( $_POST['status'] ?? 'pending' ),
                    'notes'          => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                ),
                array( 'id' => $id ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );

            if ( false !== $updated ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'customers', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }
    }
}

// 2. Render Form View
$is_edit = ( 'edit' === $action && $id > 0 );
$cust    = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $id ), ARRAY_A ) : array();
?>
<style>
    .dev-nahian-cust-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        max-width: 860px;
    }
    .dev-nahian-cust-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    @media (max-width: 960px) {
        .dev-nahian-cust-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .dev-nahian-cust-grid { grid-template-columns: 1fr; }
    }
    .col-full { grid-column: 1 / -1; }
    .dev-nahian-form-heading {
        grid-column: 1 / -1;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        padding-top: 16px;
        margin-top: 6px;
        border-top: 1px solid var(--border);
    }
    .dev-nahian-form-heading:first-of-type {
        border-top: none;
        padding-top: 0;
        margin-top: 0;
    }
    .dev-nahian-form-row {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .dev-nahian-form-row label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text);
    }
    .dev-nahian-form-input, 
    .dev-nahian-form-select, 
    .dev-nahian-form-textarea {
        height: 38px;
        padding: 0 12px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: #ffffff;
        font-size: 13.5px;
        color: var(--dark);
        outline: none;
        box-sizing: border-box;
        width: 100%;
    }
    .dev-nahian-form-textarea {
        height: 70px;
        padding: 8px 12px;
        resize: vertical;
    }
</style>

<div class="dev-nahian-cust-bar">
    <div class="dev-nahian-cust-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        <h2 class="dev-nahian-cust-title">
            <?php echo $is_edit ? sprintf( esc_html__( 'Edit Customer: %s', 'dev-nahian-car' ), esc_html( $cust['name'] ?? '' ) ) : esc_html__( 'Add Customer (KYC)', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<div class="dev-nahian-cust-card">
    <form method="post" action="">
        <?php 
        if ( $is_edit ) {
            wp_nonce_field( 'dev_nahian_edit_customer_' . $id, 'dev_nahian_cust_nonce' );
            echo '<input type="hidden" name="dev_nahian_edit_customer_submit" value="1">';
        } else {
            wp_nonce_field( 'dev_nahian_add_customer_verify', 'dev_nahian_cust_nonce' );
            echo '<input type="hidden" name="dev_nahian_add_customer_submit" value="1">';
        }
        ?>

        <div class="dev-nahian-cust-grid">
            <div class="dev-nahian-form-heading"><?php esc_html_e( '1. Personal Details', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-form-row">
                <label for="name"><?php esc_html_e( 'Full Name', 'dev-nahian-car' ); ?></label>
                <input type="text" id="name" name="name" class="dev-nahian-form-input" value="<?php echo esc_attr( $cust['name'] ?? '' ); ?>" placeholder="e.g. Jonathan Sterling" required>
            </div>

            <div class="dev-nahian-form-row">
                <label for="email"><?php esc_html_e( 'Email Address', 'dev-nahian-car' ); ?></label>
                <input type="email" id="email" name="email" class="dev-nahian-form-input" value="<?php echo esc_attr( $cust['email'] ?? '' ); ?>" placeholder="client@example.com" required>
            </div>

            <div class="dev-nahian-form-row">
                <label for="phone"><?php esc_html_e( 'Phone Number', 'dev-nahian-car' ); ?></label>
                <input type="text" id="phone" name="phone" class="dev-nahian-form-input" value="<?php echo esc_attr( $cust['phone'] ?? '' ); ?>" placeholder="+1 (555) 000-0000">
            </div>

            <div class="dev-nahian-form-row">
                <label for="dob"><?php esc_html_e( 'Date of Birth', 'dev-nahian-car' ); ?></label>
                <input type="date" id="dob" name="dob" class="dev-nahian-form-input" value="<?php echo esc_attr( (string) ( $cust['dob'] ?? '' ) ); ?>">
            </div>

            <div class="dev-nahian-form-row">
                <label for="country"><?php esc_html_e( 'Country / Region', 'dev-nahian-car' ); ?></label>
                <input type="text" id="country" name="country" class="dev-nahian-form-input" value="<?php echo esc_attr( $cust['country'] ?? '' ); ?>" placeholder="e.g. United States">
            </div>

            <div class="dev-nahian-form-row">
                <label for="status"><?php esc_html_e( 'Verification Status', 'dev-nahian-car' ); ?></label>
                <select id="status" name="status" class="dev-nahian-form-select">
                    <option value="verified" <?php selected( $cust['status'] ?? '', 'verified' ); ?>><?php esc_html_e( 'Verified Driver', 'dev-nahian-car' ); ?></option>
                    <option value="pending" <?php selected( $cust['status'] ?? 'pending', 'pending' ); ?>><?php esc_html_e( 'Pending Review', 'dev-nahian-car' ); ?></option>
                    <option value="suspended" <?php selected( $cust['status'] ?? '', 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dev-nahian-form-heading"><?php esc_html_e( '2. Driver License Information', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-form-row">
                <label for="license_number"><?php esc_html_e( 'License Number', 'dev-nahian-car' ); ?></label>
                <input type="text" id="license_number" name="license_number" class="dev-nahian-form-input" value="<?php echo esc_attr( $cust['license_number'] ?? '' ); ?>" placeholder="DL-XXXX-XXXX">
            </div>

            <div class="dev-nahian-form-row">
                <label for="license_expiry"><?php esc_html_e( 'License Expiration Date', 'dev-nahian-car' ); ?></label>
                <input type="date" id="license_expiry" name="license_expiry" class="dev-nahian-form-input" value="<?php echo esc_attr( (string) ( $cust['license_expiry'] ?? '' ) ); ?>">
            </div>

            <div class="dev-nahian-form-row">
                <label for="tier"><?php esc_html_e( 'Membership Tier', 'dev-nahian-car' ); ?></label>
                <select id="tier" name="tier" class="dev-nahian-form-select">
                    <option value="standard" <?php selected( $cust['tier'] ?? 'standard', 'standard' ); ?>><?php esc_html_e( 'Standard Member', 'dev-nahian-car' ); ?></option>
                    <option value="vip" <?php selected( $cust['tier'] ?? '', 'vip' ); ?>><?php esc_html_e( 'VIP Tier', 'dev-nahian-car' ); ?></option>
                    <option value="sovereign" <?php selected( $cust['tier'] ?? '', 'sovereign' ); ?>><?php esc_html_e( 'Sovereign (Zero Deposit)', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dev-nahian-form-heading"><?php esc_html_e( '3. Notes & Preferences', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-form-row col-full">
                <label for="notes"><?php esc_html_e( 'Profile Notes', 'dev-nahian-car' ); ?></label>
                <textarea id="notes" name="notes" class="dev-nahian-form-textarea" placeholder="Special requirements, corporate account references, etc."><?php echo esc_textarea( $cust['notes'] ?? '' ); ?></textarea>
            </div>
        </div>

        <p style="margin: 20px 0 0 0; display: flex; gap: 10px;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
                <?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Save Customer Profile', 'dev-nahian-car' ); ?>
            </button>
            <a href="<?php echo esc_url( $base ); ?>" class="button"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        </p>
    </form>
</div>
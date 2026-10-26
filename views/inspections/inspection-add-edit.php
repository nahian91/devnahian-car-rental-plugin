<?php
/**
 * Inspections Module: Create & Edit Workstation (`inspection-add-edit.php`)
 * Handles both POST actions and form markup.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action (Create or Update)
if ( isset( $_POST['dev_nahian_add_insp_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_insp_verify', 'dev_nahian_insp_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $booking_code = sanitize_text_field( wp_unslash( $_POST['booking_code'] ?? '' ) );
        $car_name     = '';
        if ( ! empty( $booking_code ) ) {
            $car_name = $wpdb->get_var( $wpdb->prepare( "SELECT car_name FROM {$table_bookings} WHERE booking_code = %s", $booking_code ) );
        }

        $inserted = $wpdb->insert(
            $table_insp,
            array(
                'booking_code'    => $booking_code,
                'car_name'        => sanitize_text_field( $car_name ?: ( $_POST['manual_car_name'] ?? 'General Fleet Asset' ) ),
                'inspector_name'  => sanitize_text_field( wp_unslash( $_POST['inspector_name'] ?? '' ) ),
                'inspection_type' => sanitize_key( $_POST['inspection_type'] ?? 'checkout' ),
                'fuel_level'      => absint( $_POST['fuel_level'] ?? 100 ),
                'odometer'        => absint( $_POST['odometer'] ?? 0 ),
                'condition_notes' => sanitize_textarea_field( wp_unslash( $_POST['condition_notes'] ?? '' ) ),
                'inspected_at'    => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
        );

        if ( false !== $inserted ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'inspections', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

if ( isset( $_POST['dev_nahian_edit_insp_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_insp_' . $id, 'dev_nahian_insp_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $booking_code = sanitize_text_field( wp_unslash( $_POST['booking_code'] ?? '' ) );
        $car_name     = '';
        if ( ! empty( $booking_code ) ) {
            $car_name = $wpdb->get_var( $wpdb->prepare( "SELECT car_name FROM {$table_bookings} WHERE booking_code = %s", $booking_code ) );
        }

        $updated = $wpdb->update(
            $table_insp,
            array(
                'booking_code'    => $booking_code,
                'car_name'        => sanitize_text_field( $car_name ?: ( $_POST['manual_car_name'] ?? 'General Fleet Asset' ) ),
                'inspector_name'  => sanitize_text_field( wp_unslash( $_POST['inspector_name'] ?? '' ) ),
                'inspection_type' => sanitize_key( $_POST['inspection_type'] ?? 'checkout' ),
                'fuel_level'      => absint( $_POST['fuel_level'] ?? 100 ),
                'odometer'        => absint( $_POST['odometer'] ?? 0 ),
                'condition_notes' => sanitize_textarea_field( wp_unslash( $_POST['condition_notes'] ?? '' ) ),
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' ),
            array( '%d' )
        );

        if ( false !== $updated ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'inspections', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

// 2. Render Form View
$is_edit = ( 'edit' === $action && $id > 0 );
$insp    = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_insp} WHERE id = %d", $id ), ARRAY_A ) : array();
?>
<style>
    .dn-insp-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }
    .dn-insp-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    @media (max-width: 960px) {
        .dn-insp-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .dn-insp-grid { grid-template-columns: 1fr; }
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

<div class="dn-insp-bar">
    <div class="dn-insp-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        <h2 class="dn-insp-title">
            <?php echo $is_edit ? sprintf( esc_html__( 'Edit Inspection Report #%d', 'dev-nahian-car' ), esc_html( $id ) ) : esc_html__( 'Record Walkaround Inspection', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<div class="dn-insp-card">
    <form method="post" action="">
        <?php 
        if ( $is_edit ) {
            wp_nonce_field( 'dev_nahian_edit_insp_' . $id, 'dev_nahian_insp_nonce' );
            echo '<input type="hidden" name="dev_nahian_edit_insp_submit" value="1">';
        } else {
            wp_nonce_field( 'dev_nahian_add_insp_verify', 'dev_nahian_insp_nonce' );
            echo '<input type="hidden" name="dev_nahian_add_insp_submit" value="1">';
        }
        ?>

        <div class="dn-insp-grid">
            <div class="dn-form-heading"><?php esc_html_e( '1. Booking & Asset Assignment', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-box">
                <label for="booking_code"><?php esc_html_e( 'Target Reservation Code', 'dev-nahian-car' ); ?></label>
                <select id="booking_code" name="booking_code" class="dev-nahian-select" required>
                    <option value=""><?php esc_html_e( '-- Select Booking / Manifest --', 'dev-nahian-car' ); ?></option>
                    <?php foreach ( $active_bookings as $b ) : ?>
                        <option value="<?php echo esc_attr( $b['booking_code'] ); ?>" <?php selected( $insp['booking_code'] ?? '', $b['booking_code'] ); ?>>
                            <?php echo esc_html( '#' . $b['booking_code'] . ' &ndash; ' . $b['customer_name'] . ' (' . $b['car_name'] . ')' ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dev-nahian-field-box">
                <label for="inspection_type"><?php esc_html_e( 'Inspection Phase', 'dev-nahian-car' ); ?></label>
                <select id="inspection_type" name="inspection_type" class="dev-nahian-select">
                    <option value="checkout" <?php selected( $insp['inspection_type'] ?? 'checkout', 'checkout' ); ?>><?php esc_html_e( 'Pre-Rental Checkout (Handover)', 'dev-nahian-car' ); ?></option>
                    <option value="checkin" <?php selected( $insp['inspection_type'] ?? '', 'checkin' ); ?>><?php esc_html_e( 'Post-Rental Check-in (Return)', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dev-nahian-field-box">
                <label for="inspector_name"><?php esc_html_e( 'Inspector / Agent Name', 'dev-nahian-car' ); ?></label>
                <input type="text" id="inspector_name" name="inspector_name" class="dev-nahian-input" value="<?php echo esc_attr( $insp['inspector_name'] ?? '' ); ?>" placeholder="e.g. Jonathan Sterling" required>
            </div>

            <div class="dn-form-heading"><?php esc_html_e( '2. Telemetry & Condition Metrics', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-box">
                <label for="fuel_level"><?php esc_html_e( 'Fuel / Battery Level (%)', 'dev-nahian-car' ); ?></label>
                <input type="number" id="fuel_level" name="fuel_level" class="dev-nahian-input" value="<?php echo esc_attr( $insp['fuel_level'] ?? 100 ); ?>" min="0" max="100">
            </div>

            <div class="dev-nahian-field-box">
                <label for="odometer"><?php esc_html_e( 'Odometer Reading (mi/km)', 'dev-nahian-car' ); ?></label>
                <input type="number" id="odometer" name="odometer" class="dev-nahian-input" value="<?php echo esc_attr( $insp['odometer'] ?? 0 ); ?>" placeholder="12500">
            </div>

            <div class="dn-form-heading"><?php esc_html_e( '3. Walkaround Notes & Damage Log', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-box col-full">
                <label for="condition_notes"><?php esc_html_e( 'Condition & Scratch Log', 'dev-nahian-car' ); ?></label>
                <textarea id="condition_notes" name="condition_notes" class="dev-nahian-textarea" placeholder="Alloy wheels pristine, front bumper minor stone chips noted..."><?php echo esc_textarea( $insp['condition_notes'] ?? '' ); ?></textarea>
            </div>
        </div>

        <p style="margin: 24px 0 0 0; display: flex; gap: 10px;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; height:38px; padding:0 20px; font-weight:700;">
                <?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Save Inspection Report', 'dev-nahian-car' ); ?>
            </button>
            <a href="<?php echo esc_url( $base ); ?>" class="button" style="height:38px; line-height:36px; padding:0 14px;"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        </p>
    </form>
</div>
<?php
/**
 * Bookings Module: Create & Edit Workstation (`booking-add-edit.php`)
 * Handles both the save POST actions and form markup generation.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action (Create or Update)
if ( isset( $_POST['dev_nahian_add_booking_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_booking_verify', 'dev_nahian_booking_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $car_id       = absint( $_POST['car_id'] ?? 0 );
        $car_name_val = sanitize_text_field( wp_unslash( $_POST['car_name'] ?? '' ) );

        if ( empty( $car_name_val ) && $car_id > 0 ) {
            $car_name_val = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
        }

        $booking_code = 'BK-' . strtoupper( wp_generate_password( 6, false, false ) );

        $inserted = $wpdb->insert(
            $table_bookings,
            array(
                'booking_code'      => $booking_code,
                'customer_name'     => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) ),
                'customer_email'    => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
                'customer_phone'    => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
                'license_number'    => sanitize_text_field( wp_unslash( $_POST['license_number'] ?? '' ) ),
                'emergency_contact' => sanitize_text_field( wp_unslash( $_POST['emergency_contact'] ?? '' ) ),
                'passenger_count'   => max( 1, absint( $_POST['passenger_count'] ?? 1 ) ),
                'car_id'            => $car_id,
                'car_name'          => $car_name_val,
                'booking_status'    => sanitize_key( $_POST['booking_status'] ?? 'pending' ),
                'pickup_date'       => sanitize_text_field( wp_unslash( $_POST['pickup_date'] ?? '' ) ),
                'pickup_time'       => sanitize_text_field( wp_unslash( $_POST['pickup_time'] ?? '10:00:00' ) ),
                'pickup_location'   => sanitize_text_field( wp_unslash( $_POST['pickup_location'] ?? '' ) ),
                'return_date'       => sanitize_text_field( wp_unslash( $_POST['return_date'] ?? '' ) ),
                'return_time'       => sanitize_text_field( wp_unslash( $_POST['return_time'] ?? '10:00:00' ) ),
                'return_location'   => sanitize_text_field( wp_unslash( $_POST['return_location'] ?? '' ) ),
                'flight_number'     => sanitize_text_field( wp_unslash( $_POST['flight_number'] ?? '' ) ),
                'payment_method'    => sanitize_key( $_POST['payment_method'] ?? 'card' ),
                'total_amount'      => floatval( $_POST['total_amount'] ?? 0.00 ),
                'customer_notes'    => sanitize_textarea_field( wp_unslash( $_POST['customer_notes'] ?? '' ) ),
                'created_at'        => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
        );

        if ( false !== $inserted ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

if ( isset( $_POST['dev_nahian_edit_booking_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_booking_' . $id, 'dev_nahian_booking_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $car_id       = absint( $_POST['car_id'] ?? 0 );
        $car_name_val = sanitize_text_field( wp_unslash( $_POST['car_name'] ?? '' ) );

        if ( empty( $car_name_val ) && $car_id > 0 ) {
            $car_name_val = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
        }

        $updated = $wpdb->update(
            $table_bookings,
            array(
                'customer_name'     => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) ),
                'customer_email'    => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
                'customer_phone'    => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
                'license_number'    => sanitize_text_field( wp_unslash( $_POST['license_number'] ?? '' ) ),
                'emergency_contact' => sanitize_text_field( wp_unslash( $_POST['emergency_contact'] ?? '' ) ),
                'passenger_count'   => max( 1, absint( $_POST['passenger_count'] ?? 1 ) ),
                'car_id'            => $car_id,
                'car_name'          => $car_name_val,
                'booking_status'    => sanitize_key( $_POST['booking_status'] ?? 'pending' ),
                'pickup_date'       => sanitize_text_field( wp_unslash( $_POST['pickup_date'] ?? '' ) ),
                'pickup_time'       => sanitize_text_field( wp_unslash( $_POST['pickup_time'] ?? '10:00:00' ) ),
                'pickup_location'   => sanitize_text_field( wp_unslash( $_POST['pickup_location'] ?? '' ) ),
                'return_date'       => sanitize_text_field( wp_unslash( $_POST['return_date'] ?? '' ) ),
                'return_time'       => sanitize_text_field( wp_unslash( $_POST['return_time'] ?? '10:00:00' ) ),
                'return_location'   => sanitize_text_field( wp_unslash( $_POST['return_location'] ?? '' ) ),
                'flight_number'     => sanitize_text_field( wp_unslash( $_POST['flight_number'] ?? '' ) ),
                'payment_method'    => sanitize_key( $_POST['payment_method'] ?? 'card' ),
                'total_amount'      => floatval( $_POST['total_amount'] ?? 0.00 ),
                'customer_notes'    => sanitize_textarea_field( wp_unslash( $_POST['customer_notes'] ?? '' ) ),
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s' ),
            array( '%d' )
        );

        if ( false !== $updated ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'booking', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

// 2. Render Form View
$is_edit = ( 'edit' === $action && $id > 0 );
$booking = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $id ), ARRAY_A ) : array();
?>

<style>
    .dev-nahian-form-surface {
        background: #ffffff;
        border: 1px solid var(--b-border);
        border-radius: var(--b-radius);
        padding: 28px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .dev-nahian-form-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    @media (max-width: 1024px) {
        .dev-nahian-form-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .dev-nahian-form-grid { grid-template-columns: 1fr; }
    }
    .col-full { grid-column: 1 / -1; }
    .col-2 { grid-column: span 2; }
    @media (max-width: 640px) {
        .col-2 { grid-column: 1 / -1; }
    }
    .dev-nahian-form-section-heading {
        grid-column: 1 / -1;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--b-muted);
        padding-top: 14px;
        margin-top: 8px;
        border-top: 1px solid var(--b-border);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dev-nahian-form-section-heading:first-of-type {
        border-top: none;
        padding-top: 0;
        margin-top: 0;
    }
    .dev-nahian-field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .dev-nahian-field-group label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--b-slate);
    }
    .dev-nahian-field-input, 
    .dev-nahian-field-select,
    .dev-nahian-field-textarea {
        height: 38px;
        padding: 0 12px;
        border: 1px solid var(--b-border);
        border-radius: 6px;
        background: #ffffff;
        font-size: 13.5px;
        color: var(--b-dark);
        outline: none;
        box-sizing: border-box;
        width: 100%;
    }
    .dev-nahian-field-textarea {
        height: 74px;
        padding: 10px 12px;
        resize: vertical;
    }
</style>

<div class="dev-nahian-subnav-bar">
    <div class="dev-nahian-subnav-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">
            <?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?>
        </a>
        <h2 class="dev-nahian-subnav-title">
            <?php echo $is_edit ? sprintf( esc_html__( 'Edit Booking #%s', 'dev-nahian-car' ), esc_html( $booking['booking_code'] ?? '' ) ) : esc_html__( 'Create New Booking', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<div class="dev-nahian-form-surface">
    <form method="post" action="">
        <?php 
        if ( $is_edit ) {
            wp_nonce_field( 'dev_nahian_edit_booking_' . $id, 'dev_nahian_booking_nonce' );
            echo '<input type="hidden" name="dev_nahian_edit_booking_submit" value="1">';
        } else {
            wp_nonce_field( 'dev_nahian_add_booking_verify', 'dev_nahian_booking_nonce' );
            echo '<input type="hidden" name="dev_nahian_add_booking_submit" value="1">';
        }
        ?>
        <input type="hidden" id="car_name" name="car_name" value="<?php echo esc_attr( $booking['car_name'] ?? '' ); ?>">

        <div class="dev-nahian-form-grid">
            <div class="dev-nahian-form-section-heading"><?php esc_html_e( '1. Customer Information', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-group">
                <label for="customer_name"><?php esc_html_e( 'Customer Name', 'dev-nahian-car' ); ?></label>
                <input type="text" id="customer_name" name="customer_name" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['customer_name'] ?? '' ); ?>" required>
            </div>

            <div class="dev-nahian-field-group">
                <label for="customer_email"><?php esc_html_e( 'Customer Email', 'dev-nahian-car' ); ?></label>
                <input type="email" id="customer_email" name="customer_email" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['customer_email'] ?? '' ); ?>" required>
            </div>

            <div class="dev-nahian-field-group">
                <label for="customer_phone"><?php esc_html_e( 'Phone Number', 'dev-nahian-car' ); ?></label>
                <input type="text" id="customer_phone" name="customer_phone" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['customer_phone'] ?? '' ); ?>" required>
            </div>

            <div class="dev-nahian-field-group">
                <label for="license_number"><?php esc_html_e( 'Driver License Number', 'dev-nahian-car' ); ?></label>
                <input type="text" id="license_number" name="license_number" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['license_number'] ?? '' ); ?>">
            </div>

            <div class="dev-nahian-field-group">
                <label for="emergency_contact"><?php esc_html_e( 'Emergency Contact', 'dev-nahian-car' ); ?></label>
                <input type="text" id="emergency_contact" name="emergency_contact" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['emergency_contact'] ?? '' ); ?>">
            </div>

            <div class="dev-nahian-field-group">
                <label for="passenger_count"><?php esc_html_e( 'Passengers', 'dev-nahian-car' ); ?></label>
                <input type="number" id="passenger_count" name="passenger_count" class="dev-nahian-field-input" value="<?php echo esc_attr( (string) ( $booking['passenger_count'] ?? 1 ) ); ?>" min="1" max="12">
            </div>

            <div class="dev-nahian-form-section-heading"><?php esc_html_e( '2. Vehicle & Schedule', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-group col-2">
                <label for="car_id"><?php esc_html_e( 'Select Vehicle', 'dev-nahian-car' ); ?></label>
                <select id="car_id" name="car_id" class="dev-nahian-field-select" required onchange="document.getElementById('car_name').value = this.options[this.selectedIndex].text.split(' (')[0];">
                    <option value="0">-- <?php esc_html_e( 'Choose Vehicle', 'dev-nahian-car' ); ?> --</option>
                    <?php if ( ! empty( $available_cars ) ) : ?>
                        <?php foreach ( $available_cars as $c ) : ?>
                            <option value="<?php echo esc_attr( (string) $c['id'] ); ?>" <?php selected( (int) ( $booking['car_id'] ?? 0 ), (int) $c['id'] ); ?>>
                                <?php echo esc_html( $c['name'] . ' (' . $currency . ' ' . number_format_i18n( (float) $c['price'], 2 ) . '/day)' ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="dev-nahian-field-group">
                <label for="booking_status"><?php esc_html_e( 'Booking Status', 'dev-nahian-car' ); ?></label>
                <select id="booking_status" name="booking_status" class="dev-nahian-field-select">
                    <option value="active" <?php selected( $booking['booking_status'] ?? '', 'active' ); ?>><?php esc_html_e( 'Active • Dispatched', 'dev-nahian-car' ); ?></option>
                    <option value="pending" <?php selected( $booking['booking_status'] ?? 'pending', 'pending' ); ?>><?php esc_html_e( 'Pending Authorization', 'dev-nahian-car' ); ?></option>
                    <option value="completed" <?php selected( $booking['booking_status'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed • Returned', 'dev-nahian-car' ); ?></option>
                    <option value="cancelled" <?php selected( $booking['booking_status'] ?? '', 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dev-nahian-field-group">
                <label for="pickup_date"><?php esc_html_e( 'Pickup Date', 'dev-nahian-car' ); ?></label>
                <input type="date" id="pickup_date" name="pickup_date" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['pickup_date'] ?? current_time( 'Y-m-d' ) ); ?>" required>
            </div>

            <div class="dev-nahian-field-group">
                <label for="pickup_time"><?php esc_html_e( 'Pickup Time', 'dev-nahian-car' ); ?></label>
                <input type="time" id="pickup_time" name="pickup_time" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['pickup_time'] ?? '10:00' ); ?>" required>
            </div>

            <div class="dev-nahian-field-group">
                <label for="pickup_location"><?php esc_html_e( 'Pickup Location', 'dev-nahian-car' ); ?></label>
                <input type="text" id="pickup_location" name="pickup_location" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['pickup_location'] ?? '' ); ?>" required>
            </div>

            <div class="dev-nahian-field-group">
                <label for="return_date"><?php esc_html_e( 'Return Date', 'dev-nahian-car' ); ?></label>
                <input type="date" id="return_date" name="return_date" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['return_date'] ?? '' ); ?>" required>
            </div>

            <div class="dev-nahian-field-group">
                <label for="return_time"><?php esc_html_e( 'Return Time', 'dev-nahian-car' ); ?></label>
                <input type="time" id="return_time" name="return_time" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['return_time'] ?? '10:00' ); ?>" required>
            </div>

            <div class="dev-nahian-field-group">
                <label for="return_location"><?php esc_html_e( 'Return Location', 'dev-nahian-car' ); ?></label>
                <input type="text" id="return_location" name="return_location" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['return_location'] ?? '' ); ?>" required>
            </div>

            <div class="dev-nahian-form-section-heading"><?php esc_html_e( '3. Flight, Payment & Logistics', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-group">
                <label for="flight_number"><?php esc_html_e( 'Flight Tail / Number', 'dev-nahian-car' ); ?></label>
                <input type="text" id="flight_number" name="flight_number" class="dev-nahian-field-input" value="<?php echo esc_attr( $booking['flight_number'] ?? '' ); ?>">
            </div>

            <div class="dev-nahian-field-group">
                <label for="payment_method"><?php esc_html_e( 'Payment Method', 'dev-nahian-car' ); ?></label>
                <select id="payment_method" name="payment_method" class="dev-nahian-field-select">
                    <option value="card" <?php selected( $booking['payment_method'] ?? '', 'card' ); ?>><?php esc_html_e( 'Credit Card (Stripe)', 'dev-nahian-car' ); ?></option>
                    <option value="wallet" <?php selected( $booking['payment_method'] ?? '', 'wallet' ); ?>><?php esc_html_e( 'Apple / Google Pay', 'dev-nahian-car' ); ?></option>
                    <option value="corporate" <?php selected( $booking['payment_method'] ?? '', 'corporate' ); ?>><?php esc_html_e( 'Corporate Account', 'dev-nahian-car' ); ?></option>
                    <option value="wire" <?php selected( $booking['payment_method'] ?? '', 'wire' ); ?>><?php esc_html_e( 'Bank Wire Transfer', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dev-nahian-field-group">
                <label for="total_amount"><?php printf( esc_html__( 'Total Invoiced (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></label>
                <input type="number" step="0.01" id="total_amount" name="total_amount" class="dev-nahian-field-input" value="<?php echo esc_attr( (string) ( $booking['total_amount'] ?? 0.00 ) ); ?>" required>
            </div>

            <div class="dev-nahian-field-group col-full">
                <label for="customer_notes"><?php esc_html_e( 'Driver Notes & Special Logistics', 'dev-nahian-car' ); ?></label>
                <textarea id="customer_notes" name="customer_notes" class="dev-nahian-field-textarea"><?php echo esc_textarea( $booking['customer_notes'] ?? '' ); ?></textarea>
            </div>
        </div>

        <p style="margin: 24px 0 0 0; display: flex; gap: 10px;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
                <?php echo $is_edit ? esc_html__( 'Save Booking Changes', 'dev-nahian-car' ) : esc_html__( 'Save & Generate Booking', 'dev-nahian-car' ); ?>
            </button>
            <a href="<?php echo esc_url( $base ); ?>" class="button"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        </p>
    </form>
</div>
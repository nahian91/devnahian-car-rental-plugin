<?php
/**
 * Maintenance Module: Create & Edit Workstation (`maint-add-edit.php`)
 * Handles both POST actions and form markup.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action (Create or Update)
if ( isset( $_POST['dev_nahian_add_maint_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_maint_verify', 'dev_nahian_maint_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $car_id   = absint( $_POST['car_id'] ?? 0 );
        $car_name = '';
        if ( $car_id > 0 ) {
            $car_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
        }

        $inserted = $wpdb->insert(
            $table_maint,
            array(
                'car_id'       => $car_id,
                'car_name'     => sanitize_text_field( $car_name ),
                'service_type' => sanitize_text_field( wp_unslash( $_POST['service_type'] ?? '' ) ),
                'cost'         => floatval( $_POST['cost'] ?? 0.00 ),
                'service_date' => ! empty( $_POST['service_date'] ) ? sanitize_text_field( wp_unslash( $_POST['service_date'] ) ) : current_time( 'Y-m-d' ),
                'status'       => sanitize_key( $_POST['status'] ?? 'scheduled' ),
                'notes'        => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
            ),
            array( '%d', '%s', '%s', '%f', '%s', '%s', '%s' )
        );

        if ( false !== $inserted ) {
            if ( 'in_workshop' === sanitize_key( $_POST['status'] ?? '' ) && $car_id > 0 ) {
                $wpdb->update( $table_cars, array( 'status' => 'maintenance' ), array( 'id' => $car_id ), array( '%s' ), array( '%d' ) );
            }

            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'maintenance', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

if ( isset( $_POST['dev_nahian_edit_maint_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_maint_' . $id, 'dev_nahian_maint_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $car_id   = absint( $_POST['car_id'] ?? 0 );
        $car_name = '';
        if ( $car_id > 0 ) {
            $car_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
        }

        $updated = $wpdb->update(
            $table_maint,
            array(
                'car_id'       => $car_id,
                'car_name'     => sanitize_text_field( $car_name ),
                'service_type' => sanitize_text_field( wp_unslash( $_POST['service_type'] ?? '' ) ),
                'cost'         => floatval( $_POST['cost'] ?? 0.00 ),
                'service_date' => ! empty( $_POST['service_date'] ) ? sanitize_text_field( wp_unslash( $_POST['service_date'] ) ) : current_time( 'Y-m-d' ),
                'status'       => sanitize_key( $_POST['status'] ?? 'scheduled' ),
                'notes'        => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
            ),
            array( 'id' => $id ),
            array( '%d', '%s', '%s', '%f', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false !== $updated ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'maintenance', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

// 2. Render Form View
$is_edit = ( 'edit' === $action && $id > 0 );
$maint   = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_maint} WHERE id = %d", $id ), ARRAY_A ) : array();
?>
<style>
    .dn-maint-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }
    .dn-maint-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    @media (max-width: 960px) {
        .dn-maint-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .dn-maint-grid { grid-template-columns: 1fr; }
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

<div class="dn-maint-bar">
    <div class="dn-maint-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        <h2 class="dn-maint-title">
            <?php echo $is_edit ? sprintf( esc_html__( 'Edit Maintenance Record #%d', 'dev-nahian-car' ), esc_html( $id ) ) : esc_html__( 'Schedule Maintenance Ticket', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<div class="dn-maint-card">
    <form method="post" action="">
        <?php 
        if ( $is_edit ) {
            wp_nonce_field( 'dev_nahian_edit_maint_' . $id, 'dev_nahian_maint_nonce' );
            echo '<input type="hidden" name="dev_nahian_edit_maint_submit" value="1">';
        } else {
            wp_nonce_field( 'dev_nahian_add_maint_verify', 'dev_nahian_maint_nonce' );
            echo '<input type="hidden" name="dev_nahian_add_maint_submit" value="1">';
        }
        ?>

        <div class="dn-maint-grid">
            <div class="dn-form-heading"><?php esc_html_e( '1. Service & Asset Allocation', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-box">
                <label for="car_id"><?php esc_html_e( 'Target Vehicle Chassis', 'dev-nahian-car' ); ?></label>
                <select id="car_id" name="car_id" class="dev-nahian-select" required>
                    <option value=""><?php esc_html_e( '-- Select Chassis --', 'dev-nahian-car' ); ?></option>
                    <?php foreach ( $fleet_cars as $car ) : ?>
                        <option value="<?php echo esc_attr( $car['id'] ); ?>" <?php selected( $maint['car_id'] ?? 0, $car['id'] ); ?>>
                            <?php echo esc_html( $car['name'] . ' (VIN: ' . substr( $car['vin'], -6 ) . ')' ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dev-nahian-field-box">
                <label for="service_type"><?php esc_html_e( 'Service Type / Procedure', 'dev-nahian-car' ); ?></label>
                <input type="text" id="service_type" name="service_type" class="dev-nahian-input" value="<?php echo esc_attr( $maint['service_type'] ?? '' ); ?>" placeholder="e.g. Ceramic Coating & Oil Change" required>
            </div>

            <div class="dev-nahian-field-box">
                <label for="cost"><?php printf( esc_html__( 'Workshop Cost (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></label>
                <input type="number" step="0.01" id="cost" name="cost" class="dev-nahian-input" value="<?php echo esc_attr( $maint['cost'] ?? '' ); ?>" placeholder="0.00">
            </div>

            <div class="dev-nahian-field-box">
                <label for="service_date"><?php esc_html_e( 'Service Date', 'dev-nahian-car' ); ?></label>
                <input type="date" id="service_date" name="service_date" class="dev-nahian-input" value="<?php echo esc_attr( $maint['service_date'] ?? current_time( 'Y-m-d' ) ); ?>">
            </div>

            <div class="dev-nahian-field-box">
                <label for="status"><?php esc_html_e( 'Service Status', 'dev-nahian-car' ); ?></label>
                <select id="status" name="status" class="dev-nahian-select">
                    <option value="scheduled" <?php selected( $maint['status'] ?? 'scheduled', 'scheduled' ); ?>><?php esc_html_e( 'Scheduled', 'dev-nahian-car' ); ?></option>
                    <option value="in_workshop" <?php selected( $maint['status'] ?? '', 'in_workshop' ); ?>><?php esc_html_e( 'In Workshop', 'dev-nahian-car' ); ?></option>
                    <option value="completed" <?php selected( $maint['status'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed & Verified', 'dev-nahian-car' ); ?></option>
                    <option value="cancelled" <?php selected( $maint['status'] ?? '', 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dn-form-heading"><?php esc_html_e( '2. Mechanic Work Notes', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-box col-full">
                <label for="notes"><?php esc_html_e( 'Detailed Diagnosis & Parts Replaced', 'dev-nahian-car' ); ?></label>
                <textarea id="notes" name="notes" class="dev-nahian-textarea" placeholder="OEM brake rotors replaced, fluid flush completed..."><?php echo esc_textarea( $maint['notes'] ?? '' ); ?></textarea>
            </div>
        </div>

        <p style="margin: 24px 0 0 0; display: flex; gap: 10px;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; height:38px; padding:0 20px; font-weight:700;">
                <?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Schedule Maintenance Ticket', 'dev-nahian-car' ); ?>
            </button>
            <a href="<?php echo esc_url( $base ); ?>" class="button" style="height:38px; line-height:36px; padding:0 14px;"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        </p>
    </form>
</div>
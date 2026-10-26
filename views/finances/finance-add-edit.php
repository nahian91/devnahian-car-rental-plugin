<?php
/**
 * Finances Module: Create & Edit Workstation (`finance-add-edit.php`)
 * Handles both POST actions and form markup.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Save Action (Create or Update)
if ( isset( $_POST['dev_nahian_add_txn_submit'] ) ) {
    check_admin_referer( 'dev_nahian_add_txn_verify', 'dev_nahian_txn_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $inserted = $wpdb->insert(
            $table_finances,
            array(
                'title'          => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'type'           => sanitize_key( $_POST['type'] ?? 'income' ),
                'category'       => sanitize_key( $_POST['category'] ?? 'rental' ),
                'amount'         => floatval( $_POST['amount'] ?? 0.00 ),
                'txn_date'       => ! empty( $_POST['txn_date'] ) ? sanitize_text_field( wp_unslash( $_POST['txn_date'] ) ) : current_time( 'Y-m-d' ),
                'reference_code' => sanitize_text_field( wp_unslash( $_POST['reference_code'] ?? '' ) ),
                'notes'          => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
            ),
            array( '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
        );

        if ( false !== $inserted ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'finances', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

if ( isset( $_POST['dev_nahian_edit_txn_submit'] ) && $id > 0 ) {
    check_admin_referer( 'dev_nahian_edit_txn_' . $id, 'dev_nahian_txn_nonce' );
    if ( current_user_can( 'manage_options' ) ) {
        $updated = $wpdb->update(
            $table_finances,
            array(
                'title'          => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'type'           => sanitize_key( $_POST['type'] ?? 'income' ),
                'category'       => sanitize_key( $_POST['category'] ?? 'rental' ),
                'amount'         => floatval( $_POST['amount'] ?? 0.00 ),
                'txn_date'       => ! empty( $_POST['txn_date'] ) ? sanitize_text_field( wp_unslash( $_POST['txn_date'] ) ) : current_time( 'Y-m-d' ),
                'reference_code' => sanitize_text_field( wp_unslash( $_POST['reference_code'] ?? '' ) ),
                'notes'          => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s', '%f', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false !== $updated ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'dev-nahian-fleet-manager', 'tab' => 'finances', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}

// 2. Render Form View
$is_edit = ( 'edit' === $action && $id > 0 );
$txn     = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_finances} WHERE id = %d", $id ), ARRAY_A ) : array();
?>
<style>
    .dn-fin-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }
    .dn-fin-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    @media (max-width: 960px) {
        .dn-fin-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .dn-fin-grid { grid-template-columns: 1fr; }
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

<div class="dn-fin-bar">
    <div class="dn-fin-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        <h2 class="dn-fin-title">
            <?php echo $is_edit ? sprintf( esc_html__( 'Edit Transaction #%d', 'dev-nahian-car' ), esc_html( $id ) ) : esc_html__( 'Record Financial Entry', 'dev-nahian-car' ); ?>
        </h2>
    </div>
</div>

<div class="dn-fin-card">
    <form method="post" action="">
        <?php 
        if ( $is_edit ) {
            wp_nonce_field( 'dev_nahian_edit_txn_' . $id, 'dev_nahian_txn_nonce' );
            echo '<input type="hidden" name="dev_nahian_edit_txn_submit" value="1">';
        } else {
            wp_nonce_field( 'dev_nahian_add_txn_verify', 'dev_nahian_txn_nonce' );
            echo '<input type="hidden" name="dev_nahian_add_txn_submit" value="1">';
        }
        ?>

        <div class="dn-fin-grid">
            <div class="dn-form-heading"><?php esc_html_e( '1. Ledger Details', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-box">
                <label for="title"><?php esc_html_e( 'Transaction Title / Description', 'dev-nahian-car' ); ?></label>
                <input type="text" id="title" name="title" class="dev-nahian-input" value="<?php echo esc_attr( $txn['title'] ?? '' ); ?>" placeholder="e.g. Monthly Fleet Insurance Premium" required>
            </div>

            <div class="dev-nahian-field-box">
                <label for="type"><?php esc_html_e( 'Entry Type', 'dev-nahian-car' ); ?></label>
                <select id="type" name="type" class="dev-nahian-select">
                    <option value="income" <?php selected( $txn['type'] ?? '', 'income' ); ?>><?php esc_html_e( 'Income (Credit)', 'dev-nahian-car' ); ?></option>
                    <option value="expense" <?php selected( $txn['type'] ?? 'expense', 'expense' ); ?>><?php esc_html_e( 'Expense (Debit)', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dev-nahian-field-box">
                <label for="category"><?php esc_html_e( 'Category', 'dev-nahian-car' ); ?></label>
                <select id="category" name="category" class="dev-nahian-select">
                    <option value="rental" <?php selected( $txn['category'] ?? '', 'rental' ); ?>><?php esc_html_e( 'Rental Revenue', 'dev-nahian-car' ); ?></option>
                    <option value="maintenance" <?php selected( $txn['category'] ?? '', 'maintenance' ); ?>><?php esc_html_e( 'Vehicle Maintenance', 'dev-nahian-car' ); ?></option>
                    <option value="insurance" <?php selected( $txn['category'] ?? 'insurance', 'insurance' ); ?>><?php esc_html_e( 'Fleet Insurance', 'dev-nahian-car' ); ?></option>
                    <option value="fuel" <?php selected( $txn['category'] ?? '', 'fuel' ); ?>><?php esc_html_e( 'Fuel & Detailing', 'dev-nahian-car' ); ?></option>
                    <option value="marketing" <?php selected( $txn['category'] ?? '', 'marketing' ); ?>><?php esc_html_e( 'Marketing & Ads', 'dev-nahian-car' ); ?></option>
                    <option value="other" <?php selected( $txn['category'] ?? '', 'other' ); ?>><?php esc_html_e( 'Other Operations', 'dev-nahian-car' ); ?></option>
                </select>
            </div>

            <div class="dev-nahian-field-box">
                <label for="amount"><?php printf( esc_html__( 'Amount (%s)', 'dev-nahian-car' ), esc_html( $currency ) ); ?></label>
                <input type="number" step="0.01" id="amount" name="amount" class="dev-nahian-input" value="<?php echo esc_attr( $txn['amount'] ?? '' ); ?>" placeholder="1250.00" required>
            </div>

            <div class="dev-nahian-field-box">
                <label for="txn_date"><?php esc_html_e( 'Transaction Date', 'dev-nahian-car' ); ?></label>
                <input type="date" id="txn_date" name="txn_date" class="dev-nahian-input" value="<?php echo esc_attr( $txn['txn_date'] ?? current_time( 'Y-m-d' ) ); ?>">
            </div>

            <div class="dev-nahian-field-box">
                <label for="reference_code"><?php esc_html_e( 'Reference / Invoice Code', 'dev-nahian-car' ); ?></label>
                <input type="text" id="reference_code" name="reference_code" class="dev-nahian-input" value="<?php echo esc_attr( $txn['reference_code'] ?? '' ); ?>" placeholder="INV-2026-001">
            </div>

            <div class="dn-form-heading"><?php esc_html_e( '2. Operational Notes', 'dev-nahian-car' ); ?></div>

            <div class="dev-nahian-field-box col-full">
                <label for="notes"><?php esc_html_e( 'Notes & Receipts', 'dev-nahian-car' ); ?></label>
                <textarea id="notes" name="notes" class="dev-nahian-textarea" placeholder="Vendor invoice details, payment approval references..."><?php echo esc_textarea( $txn['notes'] ?? '' ); ?></textarea>
            </div>
        </div>

        <p style="margin: 24px 0 0 0; display: flex; gap: 10px;">
            <button type="submit" class="button button-primary" style="background:#0f172a; border-color:#0f172a; height:38px; padding:0 20px; font-weight:700;">
                <?php echo $is_edit ? esc_html__( 'Save Changes', 'dev-nahian-car' ) : esc_html__( 'Save Transaction', 'dev-nahian-car' ); ?>
            </button>
            <a href="<?php echo esc_url( $base ); ?>" class="button" style="height:38px; line-height:36px; padding:0 14px;"><?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?></a>
        </p>
    </form>
</div>
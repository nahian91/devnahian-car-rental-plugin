<?php
/**
 * Inspections Module: Single Report View (`inspection-view.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$insp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_insp} WHERE id = %d", $id ), ARRAY_A );

if ( ! $insp ) :
    ?>
    <div class="notice notice-error" style="margin: 0 0 20px 0;"><p><?php esc_html_e( 'Inspection report not found or already deleted.', 'dev-nahian-car' ); ?></p></div>
    <p><a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">&larr; <?php esc_html_e( 'Back to Inspections', 'dev-nahian-car' ); ?></a></p>
    <?php
    return;
endif;

$type_key   = sanitize_key( $insp['inspection_type'] );
$edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $insp['id'] ), $base );
$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $insp['id'] ), $base ), 'dev_nahian_delete_insp_' . $insp['id'] );
?>
<style>
    .dn-view-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 28px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }
    .dn-view-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 1px solid var(--border);
        padding-bottom: 20px;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .dn-view-title-wrap h3 {
        margin: 0 0 6px 0;
        font-size: 20px;
        font-weight: 800;
        color: var(--dark);
    }
    .dn-view-subtitle {
        font-size: 13px;
        color: var(--muted);
        font-family: monospace;
    }
    .dn-view-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    @media(max-width: 768px) {
        .dn-view-grid { grid-template-columns: 1fr; }
    }
    .dn-view-field-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 14px 16px;
    }
    .dn-view-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
    }
    .dn-view-value {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--dark);
    }
    .dn-view-notes-box {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 16px;
        font-size: 13.5px;
        color: var(--text);
        line-height: 1.6;
        white-space: pre-wrap;
    }
</style>

<div class="dn-insp-bar">
    <div class="dn-insp-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">&larr; <?php esc_html_e( 'Back to Inspections', 'dev-nahian-car' ); ?></a>
        <h2 class="dn-insp-title"><?php printf( esc_html__( 'Inspection Dossier #%d', 'dev-nahian-car' ), esc_html( $insp['id'] ) ); ?></h2>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a;"><?php esc_html_e( 'Edit Report', 'dev-nahian-car' ); ?></a>
        <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Remove this inspection report permanently?', 'dev-nahian-car' ); ?>');" style="line-height:28px;"><?php esc_html_e( 'Delete', 'dev-nahian-car' ); ?></a>
    </div>
</div>

<div class="dn-view-card">
    <div class="dn-view-header">
        <div class="dn-view-title-wrap">
            <h3><?php echo esc_html( $insp['car_name'] ); ?></h3>
            <span class="dn-view-subtitle"><?php echo esc_html( 'RESERVATION CODE: #' . $insp['booking_code'] ); ?></span>
        </div>
        <div>
            <span class="dev-nahian-status-pill <?php echo esc_attr( $type_key ); ?>" style="font-size: 13px; padding: 5px 12px;">
                &bull; <?php echo esc_html( ucfirst( $type_key ) ); ?>
            </span>
        </div>
    </div>

    <div class="dn-view-grid">
        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Fuel / Battery Level', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value" style="color: var(--teal);"><?php echo esc_html( $insp['fuel_level'] . '%' ); ?></span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Odometer Mileage', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><?php echo esc_html( number_format_i18n( (int) $insp['odometer'] ) . ' mi' ); ?></span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Inspecting Agent', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><?php echo esc_html( $insp['inspector_name'] ); ?></span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Timestamp', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $insp['inspected_at'] ) ) ); ?></span>
        </div>
    </div>

    <div style="margin-top: 20px;">
        <span class="dn-view-label" style="display: block; margin-bottom: 6px;"><?php esc_html_e( 'Walkaround Condition & Scratch Log', 'dev-nahian-car' ); ?></span>
        <div class="dn-view-notes-box">
            <?php echo esc_html( ! empty( $insp['condition_notes'] ) ? $insp['condition_notes'] : __( 'No walkaround notes or scratch observations recorded.', 'dev-nahian-car' ) ); ?>
        </div>
    </div>
</div>
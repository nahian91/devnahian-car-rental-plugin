<?php
/**
 * Finances Module: Single Transaction Detail View (`finance-view.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$txn = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_finances} WHERE id = %d", $id ), ARRAY_A );

if ( ! $txn ) :
    ?>
    <div class="notice notice-error" style="margin: 0 0 20px 0;"><p><?php esc_html_e( 'Transaction record not found or already deleted.', 'dev-nahian-car' ); ?></p></div>
    <p><a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">&larr; <?php esc_html_e( 'Back to Ledger', 'dev-nahian-car' ); ?></a></p>
    <?php
    return;
endif;

$type_key   = sanitize_key( $txn['type'] );
$edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $txn['id'] ), $base );
$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $txn['id'] ), $base ), 'dev_nahian_delete_txn_' . $txn['id'] );
$sign       = ( 'expense' === $type_key ) ? '-' : '+';
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

<div class="dn-fin-bar">
    <div class="dn-fin-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">&larr; <?php esc_html_e( 'Back to Ledger', 'dev-nahian-car' ); ?></a>
        <h2 class="dn-fin-title"><?php printf( esc_html__( 'Transaction Audit Dossier #%d', 'dev-nahian-car' ), esc_html( $txn['id'] ) ); ?></h2>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a;"><?php esc_html_e( 'Edit Entry', 'dev-nahian-car' ); ?></a>
        <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Remove this ledger entry permanently?', 'dev-nahian-car' ); ?>');" style="line-height:28px;"><?php esc_html_e( 'Delete', 'dev-nahian-car' ); ?></a>
    </div>
</div>

<div class="dn-view-card">
    <div class="dn-view-header">
        <div class="dn-view-title-wrap">
            <h3><?php echo esc_html( $txn['title'] ); ?></h3>
            <span class="dn-view-subtitle"><?php echo esc_html( ! empty( $txn['reference_code'] ) ? 'REF: ' . $txn['reference_code'] : 'REF: N/A' ); ?></span>
        </div>
        <div>
            <span class="dev-nahian-status-pill <?php echo esc_attr( $type_key ); ?>" style="font-size: 13px; padding: 5px 12px;">
                &bull; <?php echo esc_html( ucfirst( $type_key ) ); ?>
            </span>
        </div>
    </div>

    <div class="dn-view-grid">
        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Financial Amount', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value" style="font-size: 20px; color: <?php echo ( 'expense' === $type_key ) ? '#dc2626' : 'var(--teal)'; ?>;">
                <?php echo esc_html( $sign . ' ' . $currency . ' ' . number_format_i18n( (float) $txn['amount'], 2 ) ); ?>
            </span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Ledger Category', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><?php echo esc_html( ucfirst( $txn['category'] ) ); ?></span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Transaction Date', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><?php echo esc_html( $txn['txn_date'] ); ?></span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'System Entry ID', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><code>#<?php echo esc_html( $txn['id'] ); ?></code></span>
        </div>
    </div>

    <div style="margin-top: 20px;">
        <span class="dn-view-label" style="display: block; margin-bottom: 6px;"><?php esc_html_e( 'Operational Notes & Audit Reference', 'dev-nahian-car' ); ?></span>
        <div class="dn-view-notes-box">
            <?php echo esc_html( ! empty( $txn['notes'] ) ? $txn['notes'] : __( 'No additional notes or receipts recorded for this transaction.', 'dev-nahian-car' ) ); ?>
        </div>
    </div>
</div>
<?php
/**
 * Finances Module: All Transactions List Table (`finance-all.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$transactions = $wpdb->get_results( "SELECT * FROM {$table_finances} ORDER BY id DESC", ARRAY_A );
$txn_count    = is_array( $transactions ) ? count( $transactions ) : 0;
?>
<div style="margin-bottom: 12px;">
    <span class="dn-fin-chip">
        <?php printf( esc_html__( '%s Transactions Recorded', 'dev-nahian-car' ), esc_html( number_format_i18n( $txn_count ) ) ); ?>
    </span>
</div>

<!-- 3 KPI Financial Summary Cards -->
<div class="dn-fin-metric-grid">
    <div class="dn-fin-metric-card">
        <span class="dn-fin-metric-label"><?php esc_html_e( 'Total Invoiced Income', 'dev-nahian-car' ); ?></span>
        <strong class="dn-fin-metric-val" style="color: var(--teal);"><?php echo esc_html( $currency . ' ' . number_format_i18n( $total_income, 2 ) ); ?></strong>
    </div>
    <div class="dn-fin-metric-card">
        <span class="dn-fin-metric-label"><?php esc_html_e( 'Total Operational Expenses', 'dev-nahian-car' ); ?></span>
        <strong class="dn-fin-metric-val" style="color: #dc2626;"><?php echo esc_html( $currency . ' ' . number_format_i18n( $total_expenses, 2 ) ); ?></strong>
    </div>
    <div class="dn-fin-metric-card">
        <span class="dn-fin-metric-label"><?php esc_html_e( 'Net Operating Margin', 'dev-nahian-car' ); ?></span>
        <strong class="dn-fin-metric-val" style="color: var(--dark);"><?php echo esc_html( $currency . ' ' . number_format_i18n( $net_profit, 2 ) ); ?></strong>
    </div>
</div>

<table class="wp-list-table widefat fixed striped dev-nahian-datatable" style="width:100%; border-radius:8px; overflow:hidden;">
    <thead>
        <tr>
            <th style="width: 90px;"><?php esc_html_e( 'Entry ID', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Transaction Description', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Category', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Type', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Amount', 'dev-nahian-car' ); ?></th>
            <th><?php esc_html_e( 'Date', 'dev-nahian-car' ); ?></th>
            <th style="text-align: right; width: 170px;"><?php esc_html_e( 'Actions', 'dev-nahian-car' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! empty( $transactions ) ) : ?>
            <?php foreach ( $transactions as $t ) : 
                $type_key   = sanitize_key( $t['type'] );
                $view_url   = add_query_arg( array( 'action' => 'view', 'id' => $t['id'] ), $base );
                $edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $t['id'] ), $base );
                $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $t['id'] ), $base ), 'dev_nahian_delete_txn_' . $t['id'] );
                $sign       = ( 'expense' === $type_key ) ? '-' : '+';
            ?>
                <tr>
                    <td><code>#<?php echo esc_html( $t['id'] ); ?></code></td>
                    <td>
                        <strong><?php echo esc_html( $t['title'] ); ?></strong>
                        <?php if ( ! empty( $t['reference_code'] ) ) : ?>
                            <span style="font-size: 11px; color: var(--muted); display: block; font-family: monospace;"><?php echo esc_html( $t['reference_code'] ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html( ucfirst( $t['category'] ) ); ?></td>
                    <td><span class="dev-nahian-status-pill <?php echo esc_attr( $type_key ); ?>">&bull; <?php echo esc_html( ucfirst( $type_key ) ); ?></span></td>
                    <td><strong style="color: <?php echo ( 'expense' === $type_key ) ? '#dc2626' : 'var(--teal)'; ?>;"><?php echo esc_html( $sign . ' ' . $currency . ' ' . number_format_i18n( (float) $t['amount'], 2 ) ); ?></strong></td>
                    <td><?php echo esc_html( $t['txn_date'] ); ?></td>
                    <td style="text-align: right;">
                        <div class="dev-nahian-btn-group">
                            <a href="<?php echo esc_url( $view_url ); ?>" class="dev-nahian-btn dev-nahian-btn-view" title="<?php esc_attr_e( 'View Dossier', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'view' ); ?> <?php esc_html_e( 'View', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="dev-nahian-btn dev-nahian-btn-edit" title="<?php esc_attr_e( 'Edit Entry', 'dev-nahian-car' ); ?>">
                                <?php echo dev_nahian_get_icon( 'edit' ); ?> <?php esc_html_e( 'Edit', 'dev-nahian-car' ); ?>
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="dev-nahian-btn dev-nahian-btn-delete" title="<?php esc_attr_e( 'Delete Entry', 'dev-nahian-car' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Remove this ledger entry permanently?', 'dev-nahian-car' ); ?>');">
                                <?php echo dev_nahian_get_icon( 'delete' ); ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:#64748b;"><?php esc_html_e( 'No financial income or expense entries logged yet.', 'dev-nahian-car' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
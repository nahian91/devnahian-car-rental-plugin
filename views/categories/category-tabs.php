<?php
/**
 * Categories Module: Sub-Navigation Header Bar (`category-tabs.php`)
 * Provides taxonomy status filtering, category item counts, and view controls.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_categories = isset( $table_categories ) ? $table_categories : $wpdb->prefix . 'dev_nahian_categories';

// Safe base URL fallback.
$cat_base = ! empty( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'categories',
	),
	admin_url( 'admin.php' )
);

$current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$current_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$is_form_view   = ( 'add' === $current_subtab || 'edit' === $current_action );

// -----------------------------------------------------------------------------
// Live Counts for Sub-Nav Status Filters (Single Optimized Query)
// -----------------------------------------------------------------------------
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$counts = $wpdb->get_row(
	"SELECT
		COUNT(id) AS all_count,
		SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count,
		SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_count
	FROM {$table_categories}",
	ARRAY_A
);

$filter_tabs = array(
	'all'      => array(
		'label' => __( 'All Categories', 'dev-nahian-car' ),
		'count' => (int) ( $counts['all_count'] ?? 0 ),
	),
	'active'   => array(
		'label' => __( 'Active', 'dev-nahian-car' ),
		'count' => (int) ( $counts['active_count'] ?? 0 ),
	),
	'inactive' => array(
		'label' => __( 'Inactive', 'dev-nahian-car' ),
		'count' => (int) ( $counts['inactive_count'] ?? 0 ),
	),
);
?>

<style>
	/* =========================================================================
	   Categories Sub-Navigation & Filter Bar Scoped Styling
	   ========================================================================= */
	.dnf-cat-toolbar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 16px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		margin-bottom: 6px;
		flex-wrap: wrap;
		gap: 12px;
	}
	.dnf-cat-toolbar-left {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	.dnf-subnav-link {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 8px 14px;
		border-radius: var(--dnf-radius-md, 10px);
		font-size: 13px;
		font-weight: 600;
		text-decoration: none;
		color: var(--dnf-text-secondary, #475569);
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		transition: all 0.15s ease-in-out;
		line-height: 1.2;
		box-sizing: border-box;
	}
	.dnf-subnav-link:hover {
		background: var(--dnf-bg-subtle, #f8fafc);
		color: var(--dnf-text-primary, #0f172a);
		border-color: var(--dnf-border-hover, #cbd5e1);
		transform: translateY(-1px);
	}
	.dnf-subnav-link.active {
		background: var(--dnf-primary, #0f172a);
		color: #ffffff !important;
		border-color: var(--dnf-primary, #0f172a);
		box-shadow: 0 2px 4px rgba(15, 23, 42, 0.08);
	}
	.dnf-subnav-link .dnf-count-chip {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-size: 11px;
		font-weight: 700;
		color: var(--dnf-text-secondary, #475569);
		background: var(--dnf-bg-subtle, #f1f5f9);
		border: 1px solid var(--dnf-border, #e2e8f0);
		padding: 2px 8px;
		border-radius: var(--dnf-radius-pill, 9999px);
		font-feature-settings: "tnum";
		line-height: 1;
		transition: all 0.15s ease-in-out;
	}
	.dnf-subnav-link:hover .dnf-count-chip {
		border-color: var(--dnf-border-hover, #cbd5e1);
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-subnav-link.active .dnf-count-chip {
		background: rgba(255, 255, 255, 0.18);
		color: #ffffff;
		border-color: transparent;
	}
</style>

<div class="dnf-cat-toolbar">
	<div class="dnf-cat-toolbar-left">
		<?php
		foreach ( $filter_tabs as $tab_key => $tab_data ) :
			$tab_url   = add_query_arg( 'subtab', $tab_key, remove_query_arg( array( 'action', 'id' ), $cat_base ) );
			$is_active = ( ! $is_form_view && $current_subtab === $tab_key );
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" class="dnf-subnav-link <?php echo $is_active ? 'active' : ''; ?>">
				<span><?php echo esc_html( $tab_data['label'] ); ?></span>
				<span class="dnf-count-chip"><?php echo esc_html( (string) number_format_i18n( $tab_data['count'] ) ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</div>
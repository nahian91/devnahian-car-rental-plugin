<?php
/**
 * Admin View Tab: Global Extras, Locations & Specifications (`extras.php`)
 * Synchronized with Fleet Cars Registry workstation fields and booking manifests.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dev-nahian-car' ), 403 );
}

global $wpdb;
$table_extras = isset( $table_extras ) ? $table_extras : $wpdb->prefix . 'dev_nahian_extras';
$currency     = (string) get_option( 'dev_nahian_currency', 'USD' );

$base = add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'extras',
	),
	admin_url( 'admin.php' )
);

// Permitted dashicon classes whitelist.
$allowed_icons = array(
	'dashicons-location',
	'dashicons-location-alt',
	'dashicons-admin-site',
	'dashicons-admin-site-alt',
	'dashicons-airplane',
	'dashicons-car',
	'dashicons-admin-tools',
	'dashicons-performance',
	'dashicons-dashboard',
	'dashicons-gauge',
	'dashicons-star-filled',
	'dashicons-star-half',
	'dashicons-star-empty',
	'dashicons-awards',
	'dashicons-shield',
	'dashicons-shield-alt',
	'dashicons-unlock',
	'dashicons-lock',
	'dashicons-key',
	'dashicons-cart',
	'dashicons-money-alt',
	'dashicons-tickets-alt',
	'dashicons-clock',
	'dashicons-calendar-alt',
	'dashicons-smartphone',
	'dashicons-phone',
	'dashicons-email-alt',
	'dashicons-rest-api',
	'dashicons-cloud',
	'dashicons-thumbs-up',
	'dashicons-heart',
	'dashicons-yes-alt',
	'dashicons-controls-play',
	'dashicons-controls-volumeon',
	'dashicons-lightbulb',
	'dashicons-visibility',
	'dashicons-flag',
	'dashicons-randomize',
	'dashicons-image-filter',
	'dashicons-marker',
);

// =========================================================================
// 1. SAVE HANDLER (Process BEFORE Output)
// =========================================================================
if ( isset( $_POST['dev_nahian_save_extras_ui'] ) ) {
	check_admin_referer( 'dev_nahian_extras_ui_verify', 'dev_nahian_extras_ui_nonce' );

	// 1. Locations with Delivery Radius Fees.
	$locations = array();
	if ( ! empty( $_POST['locations'] ) && is_array( $_POST['locations'] ) ) {
		foreach ( (array) $_POST['locations'] as $loc ) {
			if ( ! is_array( $loc ) ) {
				continue;
			}
			$address = isset( $loc['address'] ) ? sanitize_text_field( wp_unslash( (string) $loc['address'] ) ) : '';
			$raw_fee = isset( $loc['fee'] ) ? (float) sanitize_text_field( wp_unslash( (string) $loc['fee'] ) ) : 0.00;
			$fee     = (float) number_format( max( 0.0, $raw_fee ), 2, '.', '' );
			$icon    = isset( $loc['icon'] ) ? sanitize_text_field( wp_unslash( (string) $loc['icon'] ) ) : 'dashicons-location';

			if ( ! in_array( $icon, $allowed_icons, true ) ) {
				$icon = 'dashicons-location';
			}

			if ( '' !== $address ) {
				$locations[] = array(
					'address' => $address,
					'fee'     => $fee,
					'icon'    => $icon,
				);
			}
		}
	}
	update_option( 'dev_nahian_car_extras_locations', $locations, false );

	// 2. Technical Specifications.
	$specs = array();
	if ( ! empty( $_POST['specs'] ) && is_array( $_POST['specs'] ) ) {
		foreach ( (array) $_POST['specs'] as $spec ) {
			if ( ! is_array( $spec ) ) {
				continue;
			}
			$label = isset( $spec['label'] ) ? sanitize_text_field( wp_unslash( (string) $spec['label'] ) ) : '';
			$value = isset( $spec['value'] ) ? sanitize_text_field( wp_unslash( (string) $spec['value'] ) ) : '';
			$icon  = isset( $spec['icon'] ) ? sanitize_text_field( wp_unslash( (string) $spec['icon'] ) ) : 'dashicons-admin-tools';

			if ( ! in_array( $icon, $allowed_icons, true ) ) {
				$icon = 'dashicons-admin-tools';
			}

			if ( '' !== $label || '' !== $value ) {
				$specs[] = array(
					'label' => $label,
					'value' => $value,
					'icon'  => $icon,
				);
			}
		}
	}
	update_option( 'dev_nahian_car_extras_specs', $specs, false );

	// 3. Comfort Amenities.
	$amenities = array();
	if ( ! empty( $_POST['amenities'] ) && is_array( $_POST['amenities'] ) ) {
		foreach ( (array) $_POST['amenities'] as $amenity ) {
			if ( ! is_array( $amenity ) ) {
				continue;
			}
			$title = isset( $amenity['title'] ) ? sanitize_text_field( wp_unslash( (string) $amenity['title'] ) ) : '';
			$icon  = isset( $amenity['icon'] ) ? sanitize_text_field( wp_unslash( (string) $amenity['icon'] ) ) : 'dashicons-star-filled';

			if ( ! in_array( $icon, $allowed_icons, true ) ) {
				$icon = 'dashicons-star-filled';
			}

			if ( '' !== $title ) {
				$amenities[] = array(
					'title' => $title,
					'icon'  => $icon,
				);
			}
		}
	}
	update_option( 'dev_nahian_car_extras_amenities', $amenities, false );

	// 4. Insurance Plans.
	$insurance = array();
	if ( ! empty( $_POST['insurance'] ) && is_array( $_POST['insurance'] ) ) {
		foreach ( (array) $_POST['insurance'] as $ins ) {
			if ( ! is_array( $ins ) ) {
				continue;
			}
			$title     = isset( $ins['title'] ) ? sanitize_text_field( wp_unslash( (string) $ins['title'] ) ) : '';
			$desc      = isset( $ins['desc'] ) ? sanitize_text_field( wp_unslash( (string) $ins['desc'] ) ) : '';
			$raw_price = isset( $ins['price'] ) ? (float) sanitize_text_field( wp_unslash( (string) $ins['price'] ) ) : 0.00;
			$price     = (float) number_format( max( 0.0, $raw_price ), 2, '.', '' );
			$enabled   = isset( $ins['enabled'] ) ? 1 : 0;

			if ( '' !== $title ) {
				$insurance[] = array(
					'title'   => $title,
					'desc'    => $desc,
					'price'   => $price,
					'enabled' => $enabled,
				);
			}
		}
	}
	update_option( 'dev_nahian_car_extras_insurance', $insurance, false );

	// 5. Cross-Border Travel Clearance.
	$border = array();
	if ( ! empty( $_POST['border'] ) && is_array( $_POST['border'] ) ) {
		foreach ( (array) $_POST['border'] as $b ) {
			if ( ! is_array( $b ) ) {
				continue;
			}
			$dest      = isset( $b['destination'] ) ? sanitize_text_field( wp_unslash( (string) $b['destination'] ) ) : '';
			$raw_price = isset( $b['price'] ) ? (float) sanitize_text_field( wp_unslash( (string) $b['price'] ) ) : 0.00;
			$price     = (float) number_format( max( 0.0, $raw_price ), 2, '.', '' );
			$enabled   = isset( $b['enabled'] ) ? 1 : 0;

			if ( '' !== $dest ) {
				$border[] = array(
					'destination' => $dest,
					'price'       => $price,
					'enabled'     => $enabled,
				);
			}
		}
	}
	update_option( 'dev_nahian_car_extras_border', $border, false );

	// 6. Paid Rental Add-ons.
	$paid_extras = array();
	if ( ! empty( $_POST['paid_extras'] ) && is_array( $_POST['paid_extras'] ) ) {
		foreach ( (array) $_POST['paid_extras'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$name      = isset( $item['name'] ) ? sanitize_text_field( wp_unslash( (string) $item['name'] ) ) : '';
			$raw_price = isset( $item['price'] ) ? (float) sanitize_text_field( wp_unslash( (string) $item['price'] ) ) : 0.00;
			$price     = (float) number_format( max( 0.0, $raw_price ), 2, '.', '' );
			$type      = isset( $item['type'] ) ? sanitize_key( wp_unslash( (string) $item['type'] ) ) : 'daily';
			$mandatory = isset( $item['mandatory'] ) ? 1 : 0;
			$max_qty   = isset( $item['max_qty'] ) ? max( 1, absint( wp_unslash( (string) $item['max_qty'] ) ) ) : 1;
			$icon      = isset( $item['icon'] ) ? sanitize_text_field( wp_unslash( (string) $item['icon'] ) ) : 'dashicons-cart';

			if ( ! in_array( $icon, $allowed_icons, true ) ) {
				$icon = 'dashicons-cart';
			}

			if ( '' !== $name ) {
				$paid_extras[] = array(
					'name'      => $name,
					'price'     => $price,
					'type'      => $type,
					'mandatory' => $mandatory,
					'max_qty'   => $max_qty,
					'icon'      => $icon,
				);
			}
		}
	}
	update_option( 'dev_nahian_car_paid_extras', $paid_extras, false );

	// 7. Policy Rules.
	$policy_rules = array(
		'min_driver_age'  => isset( $_POST['policy_min_age'] ) ? max( 18, min( 75, absint( wp_unslash( (string) $_POST['policy_min_age'] ) ) ) ) : 25,
		'require_license' => isset( $_POST['policy_license'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['policy_license'] ) ) : 'Standard Class C',
		'supercar_tier'   => isset( $_POST['policy_supercar_age'] ) ? max( 18, min( 75, absint( wp_unslash( (string) $_POST['policy_supercar_age'] ) ) ) ) : 30,
	);
	update_option( 'dev_nahian_car_policy_rules', $policy_rules, false );

	wp_safe_redirect( add_query_arg( array( 'saved' => '1' ), $base ) );
	exit;
}

// =========================================================================
// 2. FETCH STORED VALUES
// =========================================================================
$locations    = (array) get_option( 'dev_nahian_car_extras_locations', array() );
$specs        = (array) get_option( 'dev_nahian_car_extras_specs', array() );
$amenities    = (array) get_option( 'dev_nahian_car_extras_amenities', array() );
$insurance    = (array) get_option( 'dev_nahian_car_extras_insurance', array() );
$border       = (array) get_option( 'dev_nahian_car_extras_border', array() );
$paid_extras  = (array) get_option( 'dev_nahian_car_paid_extras', array() );
$policy_rules = (array) get_option(
	'dev_nahian_car_policy_rules',
	array(
		'min_driver_age'  => 25,
		'require_license' => 'Standard Class C',
		'supercar_tier'   => 30,
	)
);
?>

<style>
	/* Scoped Global Extras Design Tokens & Scrollbar Prevention */
	.dnf-extras-wrap,
	.dnf-extras-wrap * {
		box-sizing: border-box !important;
	}

	.dnf-extras-wrap {
		display: flex;
		flex-direction: column;
		gap: 20px;
		width: 100%;
		max-width: 100%;
		overflow-x: clip;
	}

	/* Top Executive Header Panel */
	.dnf-extras-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--dnf-bg-card, #ffffff);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 20px 24px;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.04));
		flex-wrap: wrap;
		gap: 16px;
		width: 100%;
	}
	.dnf-extras-title-area {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.dnf-badge-row {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	.dnf-extras-heading {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
		letter-spacing: -0.02em;
	}

	/* Card Module */
	.dnf-card-section {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 24px 26px;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
		width: 100%;
	}

	.dnf-card-title {
		display: flex;
		align-items: center;
		justify-content: space-between;
		font-size: 13.5px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-primary, #0f172a);
		margin: 0 0 18px 0;
		padding-bottom: 12px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-card-title-left {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-card-title svg {
		color: var(--dnf-accent-teal, #0d9488);
	}

	/* Repeater Rows */
	.dnf-row-list {
		display: flex;
		flex-direction: column;
		gap: 10px;
		margin-bottom: 16px;
		width: 100%;
	}
	.dnf-item-row {
		display: flex;
		align-items: center;
		gap: 10px;
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 10px 14px;
		transition: border-color 0.15s ease;
		width: 100%;
	}
	.dnf-item-row:hover {
		border-color: var(--dnf-border-hover, #cbd5e1);
	}

	.dnf-drag-handle {
		color: var(--dnf-text-tertiary, #94a3b8);
		cursor: grab;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 2px;
		flex-shrink: 0;
	}

	/* Icon Picker Trigger */
	.dnf-icon-picker-btn {
		width: 38px;
		height: 38px;
		min-width: 38px;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-sm, 6px);
		display: flex;
		align-items: center;
		justify-content: center;
		background: #ffffff;
		color: var(--dnf-text-primary, #0f172a);
		cursor: pointer;
		padding: 0;
		transition: all 0.15s ease;
		flex-shrink: 0;
	}
	.dnf-icon-picker-btn:hover {
		border-color: var(--dnf-accent-teal, #0d9488);
		color: var(--dnf-accent-teal, #0d9488);
	}
	.dnf-icon-picker-btn .dashicons {
		font-size: 19px;
		width: 19px;
		height: 19px;
		line-height: 19px;
	}

	/* Row Field Dimensions */
	.dnf-input-label {
		width: 220px;
		max-width: 35%;
		flex-shrink: 1;
	}
	.dnf-input-price {
		width: 130px;
		flex-shrink: 0;
	}
	.dnf-input-qty {
		width: 70px;
		flex-shrink: 0;
	}

	/* Remove Action Button */
	.dnf-btn-remove {
		background: none;
		border: none;
		color: var(--dnf-danger, #dc2626);
		cursor: pointer;
		padding: 6px;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: var(--dnf-radius-sm, 6px);
		transition: all 0.15s ease;
		flex-shrink: 0;
	}
	.dnf-btn-remove:hover {
		background: var(--dnf-danger-bg, #fef2f2);
	}

	/* Notice Overrides */
	.dnf-extras-wrap .notice {
		margin: 0 !important;
		border-radius: var(--dnf-radius-md, 8px);
		border-left-width: 4px !important;
		box-shadow: var(--dnf-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.03));
	}

	/* Modal Backdrop & Picker */
	.dnf-modal-backdrop {
		display: none;
		position: fixed;
		top: 0; left: 0; width: 100%; height: 100%;
		background: rgba(15, 23, 42, 0.6);
		backdrop-filter: blur(2px);
		z-index: 100000;
		align-items: center;
		justify-content: center;
	}
	.dnf-modal-backdrop.is-open {
		display: flex;
	}
	.dnf-modal-box {
		background: #ffffff;
		border-radius: var(--dnf-radius-lg, 12px);
		width: 580px;
		max-width: 90%;
		max-height: 80vh;
		box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
		display: flex;
		flex-direction: column;
		overflow: hidden;
	}
	.dnf-modal-header {
		padding: 16px 22px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.dnf-modal-header h4 {
		margin: 0;
		font-size: 15px;
		font-weight: 800;
		color: var(--dnf-text-primary, #0f172a);
	}
	.dnf-modal-body {
		padding: 22px;
		overflow-y: auto;
		display: grid;
		grid-template-columns: repeat(8, 1fr);
		gap: 10px;
	}
	@media (max-width: 540px) {
		.dnf-modal-body {
			grid-template-columns: repeat(4, 1fr);
		}
	}
	.dnf-icon-option {
		width: 46px;
		height: 46px;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-sm, 6px);
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		background: #ffffff;
		transition: all 0.15s ease;
	}
	.dnf-icon-option:hover {
		border-color: var(--dnf-accent-teal, #0d9488);
		background: #f0fdfa;
		color: var(--dnf-accent-teal, #0d9488);
		transform: scale(1.06);
	}
	.dnf-icon-option .dashicons {
		font-size: 22px;
		width: 22px;
		height: 22px;
		line-height: 22px;
	}
</style>

<div class="dnf-extras-wrap">

	<!-- Executive Header Panel -->
	<div class="dnf-extras-header">
		<div class="dnf-extras-title-area">
			<div class="dnf-badge-row">
				<span class="dnf-status-chip gold">
					<span class="dnf-radar-dot"></span>
					<?php esc_html_e( 'Telematics Configuration Active', 'dev-nahian-car' ); ?>
				</span>
				<span class="dnf-dot-divider">&bull;</span>
				<span class="dnf-hud-subtext"><?php esc_html_e( 'Global Staging Hubs & Specification Fields', 'dev-nahian-car' ); ?></span>
			</div>
			<h2 class="dnf-extras-heading"><?php esc_html_e( 'Fleet Global Extras & Logistics Settings', 'dev-nahian-car' ); ?></h2>
		</div>
	</div>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><strong><?php esc_html_e( 'Settings Synchronized:', 'dev-nahian-car' ); ?></strong> <?php esc_html_e( 'All global staging hubs, specs, insurance, cross-border authorizations, add-ons, and policies updated successfully.', 'dev-nahian-car' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="" id="dnfExtrasForm">
		<?php wp_nonce_field( 'dev_nahian_extras_ui_verify', 'dev_nahian_extras_ui_nonce' ); ?>
		<input type="hidden" name="dev_nahian_save_extras_ui" value="1">

		<!-- 1. LOCATIONS & STAGING HUBS -->
		<div class="dnf-card-section">
			<h3 class="dnf-card-title">
				<span class="dnf-card-title-left">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"></path><circle cx="12" cy="10" r="3"></circle></svg>
					<?php esc_html_e( 'Staging Hub Locations & Delivery Fees', 'dev-nahian-car' ); ?>
				</span>
			</h3>

			<div class="dnf-row-list" id="dnf-locations-list">
				<?php if ( ! empty( $locations ) ) : ?>
					<?php foreach ( $locations as $index => $loc ) :
						$address_val = is_array( $loc ) ? ( $loc['address'] ?? '' ) : $loc;
						$fee_val     = is_array( $loc ) ? ( $loc['fee'] ?? 0.00 ) : 0.00;
						$icon_val    = is_array( $loc ) ? ( $loc['icon'] ?? 'dashicons-location' ) : 'dashicons-location';
						?>
						<div class="dnf-item-row">
							<div class="dnf-drag-handle" title="<?php esc_attr_e( 'Reorder Hub', 'dev-nahian-car' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
							</div>
							<button type="button" class="dnf-icon-picker-btn" onclick="dnfOpenIconPicker(this);" title="<?php esc_attr_e( 'Change Icon', 'dev-nahian-car' ); ?>">
								<span class="dashicons <?php echo esc_attr( $icon_val ); ?>"></span>
							</button>
							<input type="hidden" name="locations[<?php echo esc_attr( (string) $index ); ?>][icon]" class="dnf-row-icon-input" value="<?php echo esc_attr( $icon_val ); ?>">
							<input type="text" name="locations[<?php echo esc_attr( (string) $index ); ?>][address]" class="dnf-input" value="<?php echo esc_attr( $address_val ); ?>" placeholder="<?php esc_attr_e( 'Hub address, bay, or terminal zone...', 'dev-nahian-car' ); ?>" style="flex-grow:1;">
							<input type="number" step="0.01" min="0" name="locations[<?php echo esc_attr( (string) $index ); ?>][fee]" class="dnf-input dnf-input-price" value="<?php echo esc_attr( (string) $fee_val ); ?>" placeholder="<?php printf( esc_attr__( 'Fee (%s)', 'dev-nahian-car' ), esc_attr( $currency ) ); ?>">
							<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);" title="<?php esc_attr_e( 'Delete Location Hub', 'dev-nahian-car' ); ?>">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<button type="button" class="dnf-btn dnf-btn-secondary dnf-btn-sm" onclick="dnfAddLocationRow();">
				<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Location Hub', 'dev-nahian-car' ); ?></span>
			</button>
		</div>

		<!-- 2. TECHNICAL SPECIFICATIONS -->
		<div class="dnf-card-section">
			<h3 class="dnf-card-title">
				<span class="dnf-card-title-left">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
					<?php esc_html_e( 'Technical Attributes & Specification Fields', 'dev-nahian-car' ); ?>
				</span>
			</h3>

			<div class="dnf-row-list" id="dnf-specs-list">
				<?php if ( ! empty( $specs ) ) : ?>
					<?php foreach ( $specs as $i => $s ) :
						$spec_icon = ! empty( $s['icon'] ) ? $s['icon'] : 'dashicons-admin-tools';
						?>
						<div class="dnf-item-row">
							<div class="dnf-drag-handle">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
							</div>
							<button type="button" class="dnf-icon-picker-btn" onclick="dnfOpenIconPicker(this);" title="<?php esc_attr_e( 'Change Icon', 'dev-nahian-car' ); ?>">
								<span class="dashicons <?php echo esc_attr( $spec_icon ); ?>"></span>
							</button>
							<input type="hidden" name="specs[<?php echo esc_attr( (string) $i ); ?>][icon]" class="dnf-row-icon-input" value="<?php echo esc_attr( $spec_icon ); ?>">
							<input type="text" name="specs[<?php echo esc_attr( (string) $i ); ?>][label]" class="dnf-input dnf-input-label" value="<?php echo esc_attr( (string) $s['label'] ); ?>" placeholder="<?php esc_attr_e( 'Specification label', 'dev-nahian-car' ); ?>">
							<input type="text" name="specs[<?php echo esc_attr( (string) $i ); ?>][value]" class="dnf-input" value="<?php echo esc_attr( (string) $s['value'] ); ?>" placeholder="<?php esc_attr_e( 'Default Value', 'dev-nahian-car' ); ?>" style="flex-grow:1;">
							<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);" title="<?php esc_attr_e( 'Delete Specification Field', 'dev-nahian-car' ); ?>">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<button type="button" class="dnf-btn dnf-btn-secondary dnf-btn-sm" onclick="dnfAddSpecRow();">
				<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Specification Field', 'dev-nahian-car' ); ?></span>
			</button>
		</div>

		<!-- 3. COMFORT AMENITIES -->
		<div class="dnf-card-section">
			<h3 class="dnf-card-title">
				<span class="dnf-card-title-left">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
					<?php esc_html_e( 'Cabin Comfort & Convenience Amenities', 'dev-nahian-car' ); ?>
				</span>
			</h3>

			<div class="dnf-row-list" id="dnf-amenities-list">
				<?php if ( ! empty( $amenities ) ) : ?>
					<?php foreach ( $amenities as $j => $a ) :
						$amenity_icon = ! empty( $a['icon'] ) ? $a['icon'] : 'dashicons-star-filled';
						?>
						<div class="dnf-item-row">
							<div class="dnf-drag-handle">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
							</div>
							<button type="button" class="dnf-icon-picker-btn" onclick="dnfOpenIconPicker(this);" title="<?php esc_attr_e( 'Change Icon', 'dev-nahian-car' ); ?>">
								<span class="dashicons <?php echo esc_attr( $amenity_icon ); ?>"></span>
							</button>
							<input type="hidden" name="amenities[<?php echo esc_attr( (string) $j ); ?>][icon]" class="dnf-row-icon-input" value="<?php echo esc_attr( $amenity_icon ); ?>">
							<input type="text" name="amenities[<?php echo esc_attr( (string) $j ); ?>][title]" class="dnf-input" value="<?php echo esc_attr( (string) $a['title'] ); ?>" placeholder="<?php esc_attr_e( 'Amenity title (e.g. Bang & Olufsen Audio)', 'dev-nahian-car' ); ?>" style="flex-grow:1;">
							<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);" title="<?php esc_attr_e( 'Delete Amenity', 'dev-nahian-car' ); ?>">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<button type="button" class="dnf-btn dnf-btn-secondary dnf-btn-sm" onclick="dnfAddAmenityRow();">
				<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Amenity Option', 'dev-nahian-car' ); ?></span>
			</button>
		</div>

		<!-- 4. INSURANCE & PROTECTION PLANS -->
		<div class="dnf-card-section">
			<h3 class="dnf-card-title">
				<span class="dnf-card-title-left">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
					<?php esc_html_e( 'Insurance & Damage Protection Packages', 'dev-nahian-car' ); ?>
				</span>
			</h3>

			<div class="dnf-row-list" id="dnf-insurance-list">
				<?php if ( ! empty( $insurance ) ) : ?>
					<?php foreach ( $insurance as $ins_idx => $ins_item ) :
						$is_checked = ! empty( $ins_item['enabled'] );
						?>
						<div class="dnf-item-row" style="align-items: center;">
							<div class="dnf-drag-handle">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
							</div>
							<label class="dnf-switch">
								<input type="checkbox" name="insurance[<?php echo esc_attr( (string) $ins_idx ); ?>][enabled]" value="1" <?php checked( $is_checked, 1 ); ?>>
								<span class="dnf-slider"></span>
							</label>
							<div style="display: flex; flex-direction: column; gap: 4px; flex-grow: 1;">
								<input type="text" name="insurance[<?php echo esc_attr( (string) $ins_idx ); ?>][title]" class="dnf-input" value="<?php echo esc_attr( (string) ( $ins_item['title'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Plan Title (e.g. Zero-Deductible Elite Shield)', 'dev-nahian-car' ); ?>" style="font-weight:700;">
								<input type="text" name="insurance[<?php echo esc_attr( (string) $ins_idx ); ?>][desc]" class="dnf-input" value="<?php echo esc_attr( (string) ( $ins_item['desc'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Coverage scope, glass/tire clause, deposit waiver...', 'dev-nahian-car' ); ?>" style="font-size:12px; height:34px;">
							</div>
							<input type="number" step="0.01" min="0" name="insurance[<?php echo esc_attr( (string) $ins_idx ); ?>][price]" class="dnf-input dnf-input-price" value="<?php echo esc_attr( (string) ( $ins_item['price'] ?? 0 ) ); ?>" placeholder="0.00">
							<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);" title="<?php esc_attr_e( 'Delete Plan', 'dev-nahian-car' ); ?>">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<button type="button" class="dnf-btn dnf-btn-secondary dnf-btn-sm" onclick="dnfAddInsuranceRow();">
				<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Insurance Package', 'dev-nahian-car' ); ?></span>
			</button>
		</div>

		<!-- 5. CROSS-BORDER CLEARANCES -->
		<div class="dnf-card-section">
			<h3 class="dnf-card-title">
				<span class="dnf-card-title-left">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
					<?php esc_html_e( 'Cross-Border Travel Clearance Authorizations', 'dev-nahian-car' ); ?>
				</span>
			</h3>

			<div class="dnf-row-list" id="dnf-border-list">
				<?php if ( ! empty( $border ) ) : ?>
					<?php foreach ( $border as $b_idx => $b_item ) :
						$b_checked = ! empty( $b_item['enabled'] );
						?>
						<div class="dnf-item-row" style="align-items: center;">
							<div class="dnf-drag-handle">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
							</div>
							<label class="dnf-switch">
								<input type="checkbox" name="border[<?php echo esc_attr( (string) $b_idx ); ?>][enabled]" value="1" <?php checked( $b_checked, 1 ); ?>>
								<span class="dnf-slider"></span>
							</label>
							<div style="flex-grow: 1;">
								<input type="text" name="border[<?php echo esc_attr( (string) $b_idx ); ?>][destination]" class="dnf-input" value="<?php echo esc_attr( (string) ( $b_item['destination'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Country / Jurisdiction Name (e.g. France / Monaco)', 'dev-nahian-car' ); ?>">
							</div>
							<input type="number" step="0.01" min="0" name="border[<?php echo esc_attr( (string) $b_idx ); ?>][price]" class="dnf-input dnf-input-price" value="<?php echo esc_attr( (string) ( $b_item['price'] ?? 0 ) ); ?>" placeholder="Fee">
							<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);" title="<?php esc_attr_e( 'Delete Destination', 'dev-nahian-car' ); ?>">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<button type="button" class="dnf-btn dnf-btn-secondary dnf-btn-sm" onclick="dnfAddBorderRow();">
				<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Destination Clearance', 'dev-nahian-car' ); ?></span>
			</button>
		</div>

		<!-- 6. PAID ADD-ONS & OPTIONS -->
		<div class="dnf-card-section">
			<h3 class="dnf-card-title">
				<span class="dnf-card-title-left">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
					<?php esc_html_e( 'Paid Rental Add-Ons (Quantities & Mandatory Flags)', 'dev-nahian-car' ); ?>
				</span>
			</h3>

			<div class="dnf-row-list" id="dnf-paid-extras-list">
				<?php if ( ! empty( $paid_extras ) ) : ?>
					<?php foreach ( $paid_extras as $k => $pe ) :
						$paid_icon    = ! empty( $pe['icon'] ) ? $pe['icon'] : 'dashicons-cart';
						$is_mandatory = ! empty( $pe['mandatory'] );
						$max_qty      = max( 1, absint( $pe['max_qty'] ?? 1 ) );
						?>
						<div class="dnf-item-row" style="align-items: center;">
							<div class="dnf-drag-handle">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
							</div>
							<button type="button" class="dnf-icon-picker-btn" onclick="dnfOpenIconPicker(this);" title="<?php esc_attr_e( 'Change Icon', 'dev-nahian-car' ); ?>">
								<span class="dashicons <?php echo esc_attr( $paid_icon ); ?>"></span>
							</button>
							<input type="hidden" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][icon]" class="dnf-row-icon-input" value="<?php echo esc_attr( $paid_icon ); ?>">

							<input type="text" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][name]" class="dnf-input" value="<?php echo esc_attr( (string) $pe['name'] ); ?>" placeholder="<?php esc_attr_e( 'Service name (e.g. Mobile Starlink Terminal)', 'dev-nahian-car' ); ?>" style="flex-grow:1;">

							<input type="number" step="0.01" min="0" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][price]" class="dnf-input dnf-input-price" value="<?php echo esc_attr( (string) $pe['price'] ); ?>" placeholder="<?php printf( esc_attr__( 'Price (%s)', 'dev-nahian-car' ), esc_attr( $currency ) ); ?>">

							<div class="dnf-select-wrapper" style="width: 130px; flex-shrink: 0;">
								<select name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][type]" class="dnf-select">
									<option value="daily" <?php selected( $pe['type'] ?? 'daily', 'daily' ); ?>><?php esc_html_e( 'Per Day', 'dev-nahian-car' ); ?></option>
									<option value="fixed" <?php selected( $pe['type'] ?? '', 'fixed' ); ?>><?php esc_html_e( 'Fixed Fee', 'dev-nahian-car' ); ?></option>
								</select>
							</div>

							<div style="display:flex; align-items:center; gap:6px;">
								<span style="font-size:11px; font-weight:700; color:var(--dnf-text-secondary, #475569);"><?php esc_html_e( 'Max:', 'dev-nahian-car' ); ?></span>
								<input type="number" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][max_qty]" class="dnf-input dnf-input-qty" value="<?php echo esc_attr( (string) $max_qty ); ?>" min="1" max="10">
							</div>

							<label class="dnf-switch-label-wrap" title="<?php esc_attr_e( 'Check if automatically billed on all bookings', 'dev-nahian-car' ); ?>" style="margin: 0; padding: 0; border: none; background: transparent;">
								<label class="dnf-switch">
									<input type="checkbox" name="paid_extras[<?php echo esc_attr( (string) $k ); ?>][mandatory]" value="1" <?php checked( $is_mandatory, 1 ); ?>>
									<span class="dnf-slider"></span>
								</label>
								<span style="font-size:11px; font-weight:700; color:var(--dnf-danger, #dc2626); white-space:nowrap;"><?php esc_html_e( 'Mandatory', 'dev-nahian-car' ); ?></span>
							</label>

							<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);" title="<?php esc_attr_e( 'Delete Add-on', 'dev-nahian-car' ); ?>">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<button type="button" class="dnf-btn dnf-btn-secondary dnf-btn-sm" onclick="dnfAddPaidExtraRow();">
				<?php echo dev_nahian_get_icon( 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Paid Service / Equipment', 'dev-nahian-car' ); ?></span>
			</button>
		</div>

		<!-- 7. AGE RESTRICTIONS & POLICIES -->
		<div class="dnf-card-section">
			<h3 class="dnf-card-title">
				<span class="dnf-card-title-left">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><circle cx="9" cy="10" r="2"></circle><line x1="15" y1="8" x2="17" y2="8"></line><line x1="15" y1="12" x2="17" y2="12"></line><line x1="7" y1="16" x2="17" y2="16"></line></svg>
					<?php esc_html_e( 'Driver Age Restrictions & License Verification Policies', 'dev-nahian-car' ); ?>
				</span>
			</h3>

			<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:18px;">
				<div class="dnf-form-group">
					<label class="dnf-label" for="policy_min_age"><?php esc_html_e( 'Standard Minimum Driver Age', 'dev-nahian-car' ); ?></label>
					<input type="number" id="policy_min_age" name="policy_min_age" class="dnf-input" value="<?php echo esc_attr( (string) ( $policy_rules['min_driver_age'] ?? 25 ) ); ?>" min="18" max="75">
				</div>
				<div class="dnf-form-group">
					<label class="dnf-label" for="policy_supercar_age"><?php esc_html_e( 'Supercar Tier Minimum Age', 'dev-nahian-car' ); ?></label>
					<input type="number" id="policy_supercar_age" name="policy_supercar_age" class="dnf-input" value="<?php echo esc_attr( (string) ( $policy_rules['supercar_tier'] ?? 30 ) ); ?>" min="18" max="75">
				</div>
				<div class="dnf-form-group">
					<label class="dnf-label" for="policy_license"><?php esc_html_e( 'Mandatory License Endorsement', 'dev-nahian-car' ); ?></label>
					<input type="text" id="policy_license" name="policy_license" class="dnf-input" value="<?php echo esc_attr( (string) ( $policy_rules['require_license'] ?? 'Standard Class C' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Clean Abstract & Class C', 'dev-nahian-car' ); ?>">
				</div>
			</div>
		</div>

		<div style="margin-top: 10px;">
			<button type="submit" class="dnf-btn dnf-btn-primary" style="height:44px; padding:0 28px; font-size:14px;">
				<?php esc_html_e( 'Save All Extras & Telematics Options', 'dev-nahian-car' ); ?>
			</button>
		</div>
	</form>
</div>

<!-- DASHICONS POPUP MODAL -->
<div class="dnf-modal-backdrop" id="dnfIconModal" onclick="dnfCloseIconPicker(event);">
	<div class="dnf-modal-box" onclick="event.stopPropagation();">
		<div class="dnf-modal-header">
			<h4><?php esc_html_e( 'Select Dashicon Identifier', 'dev-nahian-car' ); ?></h4>
			<button type="button" class="dnf-btn-remove" onclick="dnfCloseIconModal();" style="font-size:20px;">&times;</button>
		</div>
		<div class="dnf-modal-body">
			<?php foreach ( $allowed_icons as $icon_name ) : ?>
				<div class="dnf-icon-option" onclick="dnfSelectIcon('<?php echo esc_js( $icon_name ); ?>');" title="<?php echo esc_attr( $icon_name ); ?>">
					<span class="dashicons <?php echo esc_attr( $icon_name ); ?>"></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<script>
(function() {
	'use strict';
	var currentIconTriggerBtn = null;

	window.dnfOpenIconPicker = function(btn) {
		currentIconTriggerBtn = btn;
		var modal = document.getElementById('dnfIconModal');
		if (modal) modal.classList.add('is-open');
	};

	window.dnfCloseIconModal = function() {
		var modal = document.getElementById('dnfIconModal');
		if (modal) modal.classList.remove('is-open');
		currentIconTriggerBtn = null;
	};

	window.dnfCloseIconPicker = function(e) {
		if (e.target.id === 'dnfIconModal') {
			dnfCloseIconModal();
		}
	};

	window.dnfSelectIcon = function(iconClass) {
		if (currentIconTriggerBtn) {
			var iconElem = currentIconTriggerBtn.querySelector('.dashicons');
			if (iconElem) {
				iconElem.className = 'dashicons ' + iconClass;
			}
			var row = currentIconTriggerBtn.closest('.dnf-item-row');
			if (row) {
				var hiddenInput = row.querySelector('.dnf-row-icon-input');
				if (hiddenInput) {
					hiddenInput.value = iconClass;
				}
			}
		}
		dnfCloseIconModal();
	};

	window.dnfRemoveRow = function(button) {
		var row = button.closest('.dnf-item-row');
		if (row) {
			row.remove();
		}
	};

	window.dnfAddLocationRow = function() {
		var container = document.getElementById('dnf-locations-list');
		var idx = Date.now();
		var html = '' +
			'<div class="dnf-item-row">' +
				'<div class="dnf-drag-handle">' +
					'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>' +
				'</div>' +
				'<button type="button" class="dnf-icon-picker-btn" onclick="dnfOpenIconPicker(this);">' +
					'<span class="dashicons dashicons-location"></span>' +
				'</button>' +
				'<input type="hidden" name="locations[' + idx + '][icon]" class="dnf-row-icon-input" value="dashicons-location">' +
				'<input type="text" name="locations[' + idx + '][address]" class="dnf-input" placeholder="<?php echo esc_js( __( 'Hub address or terminal zone...', 'dev-nahian-car' ) ); ?>" style="flex-grow:1;">' +
				'<input type="number" step="0.01" min="0" name="locations[' + idx + '][fee]" class="dnf-input dnf-input-price" placeholder="<?php printf( esc_js( __( 'Fee (%s)', 'dev-nahian-car' ) ), esc_js( $currency ) ); ?>">' +
				'<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);">' +
					'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
				'</button>' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
		container.lastElementChild.querySelector('.dnf-input').focus();
	};

	window.dnfAddSpecRow = function() {
		var container = document.getElementById('dnf-specs-list');
		var idx = Date.now();
		var html = '' +
			'<div class="dnf-item-row">' +
				'<div class="dnf-drag-handle">' +
					'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>' +
				'</div>' +
				'<button type="button" class="dnf-icon-picker-btn" onclick="dnfOpenIconPicker(this);">' +
					'<span class="dashicons dashicons-admin-tools"></span>' +
				'</button>' +
				'<input type="hidden" name="specs[' + idx + '][icon]" class="dnf-row-icon-input" value="dashicons-admin-tools">' +
				'<input type="text" name="specs[' + idx + '][label]" class="dnf-input dnf-input-label" placeholder="<?php echo esc_js( __( 'Specification label', 'dev-nahian-car' ) ); ?>">' +
				'<input type="text" name="specs[' + idx + '][value]" class="dnf-input" placeholder="<?php echo esc_js( __( 'Default Value', 'dev-nahian-car' ) ); ?>" style="flex-grow:1;">' +
				'<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);">' +
					'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
				'</button>' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
		container.lastElementChild.querySelector('.dnf-input-label').focus();
	};

	window.dnfAddAmenityRow = function() {
		var container = document.getElementById('dnf-amenities-list');
		var idx = Date.now();
		var html = '' +
			'<div class="dnf-item-row">' +
				'<div class="dnf-drag-handle">' +
					'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>' +
				'</div>' +
				'<button type="button" class="dnf-icon-picker-btn" onclick="dnfOpenIconPicker(this);">' +
					'<span class="dashicons dashicons-star-filled"></span>' +
				'</button>' +
				'<input type="hidden" name="amenities[' + idx + '][icon]" class="dnf-row-icon-input" value="dashicons-star-filled">' +
				'<input type="text" name="amenities[' + idx + '][title]" class="dnf-input" placeholder="<?php echo esc_js( __( 'Amenity title', 'dev-nahian-car' ) ); ?>" style="flex-grow:1;">' +
				'<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);">' +
					'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
				'</button>' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
		container.lastElementChild.querySelector('.dnf-input').focus();
	};

	window.dnfAddInsuranceRow = function() {
		var container = document.getElementById('dnf-insurance-list');
		var idx = Date.now();
		var html = '' +
			'<div class="dnf-item-row" style="align-items: center;">' +
				'<div class="dnf-drag-handle">' +
					'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>' +
				'</div>' +
				'<label class="dnf-switch">' +
					'<input type="checkbox" name="insurance[' + idx + '][enabled]" value="1" checked>' +
					'<span class="dnf-slider"></span>' +
				'</label>' +
				'<div style="display: flex; flex-direction: column; gap: 4px; flex-grow: 1;">' +
					'<input type="text" name="insurance[' + idx + '][title]" class="dnf-input" placeholder="<?php echo esc_js( __( 'Plan Title', 'dev-nahian-car' ) ); ?>" style="font-weight:700;">' +
					'<input type="text" name="insurance[' + idx + '][desc]" class="dnf-input" placeholder="<?php echo esc_js( __( 'Plan description...', 'dev-nahian-car' ) ); ?>" style="font-size:12px; height:34px;">' +
				'</div>' +
				'<input type="number" step="0.01" min="0" name="insurance[' + idx + '][price]" class="dnf-input dnf-input-price" placeholder="0.00">' +
				'<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);">' +
					'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
				'</button>' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
		container.lastElementChild.querySelector('.dnf-input').focus();
	};

	window.dnfAddBorderRow = function() {
		var container = document.getElementById('dnf-border-list');
		var idx = Date.now();
		var html = '' +
			'<div class="dnf-item-row" style="align-items: center;">' +
				'<div class="dnf-drag-handle">' +
					'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>' +
				'</div>' +
				'<label class="dnf-switch">' +
					'<input type="checkbox" name="border[' + idx + '][enabled]" value="1" checked>' +
					'<span class="dnf-slider"></span>' +
				'</label>' +
				'<div style="flex-grow: 1;">' +
					'<input type="text" name="border[' + idx + '][destination]" class="dnf-input" placeholder="<?php echo esc_js( __( 'Crossing Destination', 'dev-nahian-car' ) ); ?>">' +
				'</div>' +
				'<input type="number" step="0.01" min="0" name="border[' + idx + '][price]" class="dnf-input dnf-input-price" placeholder="0.00">' +
				'<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);">' +
					'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
				'</button>' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
		container.lastElementChild.querySelector('.dnf-input').focus();
	};

	window.dnfAddPaidExtraRow = function() {
		var container = document.getElementById('dnf-paid-extras-list');
		var idx = Date.now();
		var html = '' +
			'<div class="dnf-item-row" style="align-items: center;">' +
				'<div class="dnf-drag-handle">' +
					'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>' +
				'</div>' +
				'<button type="button" class="dnf-icon-picker-btn" onclick="dnfOpenIconPicker(this);">' +
					'<span class="dashicons dashicons-cart"></span>' +
				'</button>' +
				'<input type="hidden" name="paid_extras[' + idx + '][icon]" class="dnf-row-icon-input" value="dashicons-cart">' +
				'<input type="text" name="paid_extras[' + idx + '][name]" class="dnf-input" placeholder="<?php echo esc_js( __( 'Service name', 'dev-nahian-car' ) ); ?>" style="flex-grow:1;">' +
				'<input type="number" step="0.01" min="0" name="paid_extras[' + idx + '][price]" class="dnf-input dnf-input-price" placeholder="<?php printf( esc_js( __( 'Price (%s)', 'dev-nahian-car' ) ), esc_js( $currency ) ); ?>">' +
				'<div class="dnf-select-wrapper" style="width: 130px; flex-shrink: 0;">' +
					'<select name="paid_extras[' + idx + '][type]" class="dnf-select">' +
						'<option value="daily"><?php echo esc_js( __( 'Per Day', 'dev-nahian-car' ) ); ?></option>' +
						'<option value="fixed"><?php echo esc_js( __( 'Fixed Fee', 'dev-nahian-car' ) ); ?></option>' +
					'</select>' +
				'</div>' +
				'<div style="display:flex; align-items:center; gap:6px;">' +
					'<span style="font-size:11px; font-weight:700; color:var(--dnf-text-secondary, #475569);"><?php echo esc_js( __( 'Max:', 'dev-nahian-car' ) ); ?></span>' +
					'<input type="number" name="paid_extras[' + idx + '][max_qty]" class="dnf-input dnf-input-qty" value="1" min="1" max="10">' +
				'</div>' +
				'<label class="dnf-switch-label-wrap" style="margin:0; padding:0; border:none; background:transparent;">' +
					'<label class="dnf-switch">' +
						'<input type="checkbox" name="paid_extras[' + idx + '][mandatory]" value="1">' +
						'<span class="dnf-slider"></span>' +
					'</label>' +
					'<span style="font-size:11px; font-weight:700; color:var(--dnf-danger, #dc2626); white-space:nowrap;"><?php echo esc_js( __( 'Mandatory', 'dev-nahian-car' ) ); ?></span>' +
				'</label>' +
				'<button type="button" class="dnf-btn-remove" onclick="dnfRemoveRow(this);">' +
					'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
				'</button>' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
		container.lastElementChild.querySelector('.dnf-input').focus();
	};
})();
</script>
<?php
/**
 * Cars Module: Registration & Editor Workstation (`cars-add-edit.php`)
 * Fully integrated with all vehicle specs, powertrain parameters, insurance plans,
 * borders, seasonal rates, and modern UI form controls.
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
$table_cars       = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$table_categories = isset( $table_categories ) ? $table_categories : $wpdb->prefix . 'dev_nahian_categories';

$base = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'cars',
	),
	admin_url( 'admin.php' )
);

$id       = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$currency = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

// -----------------------------------------------------------------------------
// 1. Process Save Action (POST Submission)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_save_car_submit'] ) ) {
	check_admin_referer( 'dev_nahian_car_save_verify', 'dev_nahian_car_nonce' );

	$car_id = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;

	$raw_price   = isset( $_POST['price'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['price'] ) ) : 0.00;
	$raw_deposit = isset( $_POST['deposit'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['deposit'] ) ) : 0.00;

	$core_data = array(
		'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		'brand'        => isset( $_POST['brand'] ) ? sanitize_text_field( wp_unslash( $_POST['brand'] ) ) : '',
		'year'         => isset( $_POST['year'] ) ? absint( wp_unslash( $_POST['year'] ) ) : (int) gmdate( 'Y' ),
		'vin'          => isset( $_POST['vin'] ) ? sanitize_text_field( wp_unslash( $_POST['vin'] ) ) : '',
		'plate'        => isset( $_POST['plate'] ) ? sanitize_text_field( wp_unslash( $_POST['plate'] ) ) : '',
		'category'     => isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'sports-gt',
		'color'        => isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : '',
		'status'       => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'ready',
		'location'     => isset( $_POST['default_pickup'] ) ? sanitize_text_field( wp_unslash( $_POST['default_pickup'] ) ) : '',
		'horsepower'   => isset( $_POST['horsepower'] ) ? absint( wp_unslash( $_POST['horsepower'] ) ) : 0,
		'acceleration' => isset( $_POST['acceleration'] ) ? sanitize_text_field( wp_unslash( $_POST['acceleration'] ) ) : '',
		'speed'        => isset( $_POST['speed'] ) ? sanitize_text_field( wp_unslash( $_POST['speed'] ) ) : '',
		'drivetrain'   => isset( $_POST['drivetrain'] ) ? sanitize_text_field( wp_unslash( $_POST['drivetrain'] ) ) : 'AWD',
		'transmission' => isset( $_POST['transmission'] ) ? sanitize_key( wp_unslash( $_POST['transmission'] ) ) : 'automatic',
		'fuel'         => isset( $_POST['fuel'] ) ? sanitize_key( wp_unslash( $_POST['fuel'] ) ) : 'gasoline',
		'seats'        => isset( $_POST['seats'] ) ? absint( wp_unslash( $_POST['seats'] ) ) : 2,
		'doors'        => isset( $_POST['doors'] ) ? absint( wp_unslash( $_POST['doors'] ) ) : 4,
		'bags'         => isset( $_POST['bags'] ) ? absint( wp_unslash( $_POST['bags'] ) ) : 2,
		'price'        => (float) number_format( max( 0.0, $raw_price ), 2, '.', '' ),
		'deposit'      => (float) number_format( max( 0.0, $raw_deposit ), 2, '.', '' ),
		'odometer'     => isset( $_POST['odometer'] ) ? absint( wp_unslash( $_POST['odometer'] ) ) : 0,
		'fuel_level'   => isset( $_POST['fuel_level'] ) ? min( 100, absint( wp_unslash( $_POST['fuel_level'] ) ) ) : 100,
		'image'        => isset( $_POST['featured_image'] ) ? esc_url_raw( wp_unslash( $_POST['featured_image'] ) ) : '',
		'notes'        => isset( $_POST['vehicle_description'] ) ? wp_kses_post( wp_unslash( $_POST['vehicle_description'] ) ) : '',
	);

	$meta_data = array(
		'default_pickup'   => isset( $_POST['default_pickup'] ) ? sanitize_text_field( wp_unslash( $_POST['default_pickup'] ) ) : '',
		'default_dropoff'  => isset( $_POST['default_dropoff'] ) ? sanitize_text_field( wp_unslash( $_POST['default_dropoff'] ) ) : '',
		'min_booking_days' => isset( $_POST['min_booking_days'] ) ? max( 1, absint( wp_unslash( $_POST['min_booking_days'] ) ) ) : 1,
		'technical_specs'  => array(),
		'amenities'        => array(),
		'insurance'        => array(),
		'border_crossing'  => array(),
		'seasonal_pricing' => array(),
	);

	if ( ! empty( $_POST['specs'] ) && is_array( $_POST['specs'] ) ) {
		foreach ( (array) $_POST['specs'] as $k => $v ) {
			$meta_data['technical_specs'][ sanitize_text_field( wp_unslash( (string) $k ) ) ] = sanitize_text_field( wp_unslash( (string) $v ) );
		}
	}

	if ( ! empty( $_POST['amenities'] ) && is_array( $_POST['amenities'] ) ) {
		foreach ( (array) $_POST['amenities'] as $amenity_item ) {
			$meta_data['amenities'][] = sanitize_text_field( wp_unslash( (string) $amenity_item ) );
		}
	}

	if ( ! empty( $_POST['insurance'] ) && is_array( $_POST['insurance'] ) ) {
		foreach ( (array) $_POST['insurance'] as $ins_key => $ins_val ) {
			if ( is_array( $ins_val ) ) {
				$meta_data['insurance'][ sanitize_key( (string) $ins_key ) ] = array(
					'enabled' => isset( $ins_val['enabled'] ) ? 1 : 0,
					'price'   => isset( $ins_val['price'] ) ? (float) sanitize_text_field( wp_unslash( (string) $ins_val['price'] ) ) : 0.00,
				);
			}
		}
	}

	if ( ! empty( $_POST['border'] ) && is_array( $_POST['border'] ) ) {
		foreach ( (array) $_POST['border'] as $b_key => $b_val ) {
			if ( is_array( $b_val ) ) {
				$meta_data['border_crossing'][ sanitize_key( (string) $b_key ) ] = array(
					'enabled' => isset( $b_val['enabled'] ) ? 1 : 0,
					'price'   => isset( $b_val['price'] ) ? (float) sanitize_text_field( wp_unslash( (string) $b_val['price'] ) ) : 0.00,
				);
			}
		}
	}

	if ( ! empty( $_POST['seasonal'] ) && is_array( $_POST['seasonal'] ) ) {
		foreach ( (array) $_POST['seasonal'] as $s_item ) {
			if ( is_array( $s_item ) && ! empty( $s_item['name'] ) && isset( $s_item['rate'] ) ) {
				$meta_data['seasonal_pricing'][] = array(
					'name'       => sanitize_text_field( wp_unslash( (string) $s_item['name'] ) ),
					'start_date' => sanitize_text_field( wp_unslash( (string) ( $s_item['start_date'] ?? '' ) ) ),
					'end_date'   => sanitize_text_field( wp_unslash( (string) ( $s_item['end_date'] ?? '' ) ) ),
					'rate'       => (float) sanitize_text_field( wp_unslash( (string) $s_item['rate'] ) ),
				);
			}
		}
	}

	if ( $car_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $table_cars, $core_data, array( 'id' => $car_id ) );
		update_option( "dev_nahian_car_meta_{$car_id}", $meta_data, false );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'dev-nahian-fleet-manager',
					'tab'     => 'cars',
					'action'  => 'edit',
					'id'      => $car_id,
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	} else {
		$core_data['created_at'] = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert( $table_cars, $core_data );
		$new_id   = $wpdb->insert_id;

		if ( false !== $inserted && $new_id ) {
			update_option( "dev_nahian_car_meta_{$new_id}", $meta_data, false );
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'dev-nahian-fleet-manager',
						'tab'   => 'cars',
						'saved' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}
}

// -----------------------------------------------------------------------------
// 2. Fetch Active Record & Metadata
// -----------------------------------------------------------------------------
$car  = array();
$meta = array();
if ( $id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$car  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cars} WHERE id = %d", $id ), ARRAY_A );
	$meta = get_option( "dev_nahian_car_meta_{$id}", array() );
}

$car_name       = (string) ( $car['name'] ?? '' );
$car_brand      = (string) ( $car['brand'] ?? '' );
$car_year       = (int) ( $car['year'] ?? gmdate( 'Y' ) );
$car_vin        = (string) ( $car['vin'] ?? '' );
$car_plate      = (string) ( $car['plate'] ?? '' );
$car_color      = (string) ( $car['color'] ?? '' );
$car_cat        = (string) ( $car['category'] ?? 'sports-gt' );
$car_status     = (string) ( $car['status'] ?? 'ready' );
$car_hp         = (int) ( $car['horsepower'] ?? 0 );
$car_accel      = (string) ( $car['acceleration'] ?? '' );
$car_speed      = (string) ( $car['speed'] ?? '' );
$car_drivetrain = (string) ( $car['drivetrain'] ?? 'AWD' );
$car_trans      = (string) ( $car['transmission'] ?? 'automatic' );
$car_fuel       = (string) ( $car['fuel'] ?? 'gasoline' );
$car_seats      = (int) ( $car['seats'] ?? 2 );
$car_doors      = (int) ( $car['doors'] ?? 4 );
$car_bags       = (int) ( $car['bags'] ?? 2 );
$car_odo        = (int) ( $car['odometer'] ?? 0 );
$car_fuel_lvl   = (int) ( $car['fuel_level'] ?? 100 );
$car_price      = (string) ( $car['price'] ?? '0.00' );
$car_deposit    = (string) ( $car['deposit'] ?? '0.00' );
$car_img        = (string) ( $car['image'] ?? '' );
$car_notes      = (string) ( $car['notes'] ?? '' );

$car_min_days   = (int) ( $meta['min_booking_days'] ?? 1 );
$car_specs      = (array) ( $meta['technical_specs'] ?? array() );
$car_amenities  = (array) ( $meta['amenities'] ?? array() );
$car_insurances = (array) ( $meta['insurance'] ?? array() );
$car_borders    = (array) ( $meta['border_crossing'] ?? array() );
$car_seasons    = (array) ( $meta['seasonal_pricing'] ?? array() );
$car_pickup     = (string) ( $meta['default_pickup'] ?? ( $car['location'] ?? '' ) );
$car_dropoff    = (string) ( $meta['default_dropoff'] ?? '' );

// Global Extras Fallbacks.
$global_locations = (array) get_option( 'dev_nahian_car_extras_locations', array() );
$global_specs     = (array) get_option( 'dev_nahian_car_extras_specs', array() );
$global_amenities = (array) get_option( 'dev_nahian_car_extras_amenities', array() );
$global_insurance = (array) get_option( 'dev_nahian_car_extras_insurance', array() );
$global_borders   = (array) get_option( 'dev_nahian_car_extras_border', array() );

if ( empty( $available_cats ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$available_cats = $wpdb->get_results( "SELECT slug, name FROM {$table_categories} ORDER BY name ASC", ARRAY_A );
}
?>

<style>
	/* Two-Column Editor Grid Layout */
	.dnf-editor-grid {
		display: grid;
		grid-template-columns: 1fr 380px;
		gap: 24px;
		align-items: start;
	}
	@media (max-width: 1080px) {
		.dnf-editor-grid {
			grid-template-columns: 1fr;
		}
	}

	.dnf-card-module {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 26px 28px;
		margin-bottom: 24px;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
	}
	.dnf-card-module:last-child {
		margin-bottom: 0;
	}

	.dnf-module-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 20px;
		padding-bottom: 14px;
		border-bottom: 1px solid var(--dnf-border, #e2e8f0);
	}
	.dnf-module-title {
		font-size: 13.5px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--dnf-text-primary, #0f172a);
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-module-title svg {
		color: var(--dnf-accent-teal, #0d9488);
	}

	/* Modular Form Field Groups */
	.dnf-form-group {
		display: flex;
		flex-direction: column;
		gap: 6px;
		margin-bottom: 16px;
	}
	.dnf-form-group:last-child {
		margin-bottom: 0;
	}

	/* Tile Checkbox Row */
	.dnf-tile-checkbox {
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-md, 8px);
		padding: 14px 16px;
		margin-bottom: 12px;
		display: flex;
		align-items: center;
		gap: 16px;
		transition: border-color 0.15s ease;
	}
	.dnf-tile-checkbox:hover {
		border-color: var(--dnf-border-hover, #cbd5e1);
	}

	/* Cover Image Preview Surface */
	.dnf-cover-uploader {
		width: 100%;
		height: 200px;
		background: var(--dnf-bg-subtle, #f8fafc);
		border: 1.5px dashed var(--dnf-border-hover, #cbd5e1);
		border-radius: var(--dnf-radius-md, 8px);
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		margin-bottom: 14px;
		position: relative;
	}
	.dnf-cover-uploader img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.dnf-cover-empty-text {
		color: var(--dnf-text-tertiary, #94a3b8);
		font-size: 13px;
		font-weight: 600;
	}
</style>

<form method="post" action="" id="dnfCarForm">
	<?php wp_nonce_field( 'dev_nahian_car_save_verify', 'dev_nahian_car_nonce' ); ?>
	<input type="hidden" name="dev_nahian_save_car_submit" value="1">
	<input type="hidden" name="car_id" value="<?php echo esc_attr( (string) $id ); ?>">

	<div class="dnf-editor-grid">
		<!-- ===================================================================
			 MAIN COLUMN: SPECS, LOGISTICS, AMENITIES, RATES
			 =================================================================== -->
		<div class="dnf-main-col">

			<!-- 1. Identity & Build Data -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 17h14v-5l-2-6H7L5 12v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
						<?php esc_html_e( '1. Vehicle Identity & Chassis Designation', 'dev-nahian-car' ); ?>
					</h3>
				</div>

				<div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:16px;">
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_name"><?php esc_html_e( 'Model Designation', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span></label>
						<input type="text" id="car_name" name="name" class="dnf-input" value="<?php echo esc_attr( $car_name ); ?>" placeholder="e.g. Taycan Turbo S" required>
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_brand"><?php esc_html_e( 'Make / Brand', 'dev-nahian-car' ); ?></label>
						<input type="text" id="car_brand" name="brand" class="dnf-input" value="<?php echo esc_attr( $car_brand ); ?>" placeholder="e.g. Porsche">
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_year"><?php esc_html_e( 'Model Year', 'dev-nahian-car' ); ?></label>
						<input type="number" id="car_year" name="year" class="dnf-input" value="<?php echo esc_attr( (string) $car_year ); ?>" min="1990" max="<?php echo esc_attr( (string) ( (int) gmdate( 'Y' ) + 2 ) ); ?>">
					</div>
				</div>

				<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_vin"><?php esc_html_e( 'VIN Number', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span></label>
						<input type="text" id="car_vin" name="vin" class="dnf-input" value="<?php echo esc_attr( $car_vin ); ?>" placeholder="WP0AB2A1..." required>
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_plate"><?php esc_html_e( 'License Plate #', 'dev-nahian-car' ); ?></label>
						<input type="text" id="car_plate" name="plate" class="dnf-input" value="<?php echo esc_attr( $car_plate ); ?>" placeholder="FL-982-GT">
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_color"><?php esc_html_e( 'Color / Exterior Finish', 'dev-nahian-car' ); ?></label>
						<input type="text" id="car_color" name="color" class="dnf-input" value="<?php echo esc_attr( $car_color ); ?>" placeholder="e.g. Volcano Grey">
					</div>
				</div>
			</div>

			<!-- 2. Performance & Powertrain Specs -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
						<?php esc_html_e( '2. Powertrain & Performance Metrics', 'dev-nahian-car' ); ?>
					</h3>
				</div>

				<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_hp"><?php esc_html_e( 'Horsepower (HP)', 'dev-nahian-car' ); ?></label>
						<input type="number" id="car_hp" name="horsepower" class="dnf-input" value="<?php echo esc_attr( (string) $car_hp ); ?>" placeholder="e.g. 750">
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_accel"><?php esc_html_e( '0-60 MPH (Sec)', 'dev-nahian-car' ); ?></label>
						<input type="text" id="car_accel" name="acceleration" class="dnf-input" value="<?php echo esc_attr( $car_accel ); ?>" placeholder="e.g. 2.6s">
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_speed"><?php esc_html_e( 'Top Speed', 'dev-nahian-car' ); ?></label>
						<input type="text" id="car_speed" name="speed" class="dnf-input" value="<?php echo esc_attr( $car_speed ); ?>" placeholder="e.g. 162 MPH">
					</div>
				</div>

				<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_drivetrain"><?php esc_html_e( 'Drivetrain', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="car_drivetrain" name="drivetrain" class="dnf-select">
								<option value="AWD" <?php selected( $car_drivetrain, 'AWD' ); ?>>AWD (All-Wheel Drive)</option>
								<option value="RWD" <?php selected( $car_drivetrain, 'RWD' ); ?>>RWD (Rear-Wheel Drive)</option>
								<option value="FWD" <?php selected( $car_drivetrain, 'FWD' ); ?>>FWD (Front-Wheel Drive)</option>
								<option value="4WD" <?php selected( $car_drivetrain, '4WD' ); ?>>4WD (Four-Wheel Drive)</option>
							</select>
						</div>
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_trans"><?php esc_html_e( 'Transmission', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="car_trans" name="transmission" class="dnf-select">
								<option value="automatic" <?php selected( $car_trans, 'automatic' ); ?>><?php esc_html_e( 'Automatic Dual-Clutch', 'dev-nahian-car' ); ?></option>
								<option value="manual" <?php selected( $car_trans, 'manual' ); ?>><?php esc_html_e( 'Manual 6-Speed', 'dev-nahian-car' ); ?></option>
								<option value="single-speed" <?php selected( $car_trans, 'single-speed' ); ?>><?php esc_html_e( 'Direct Drive (Electric)', 'dev-nahian-car' ); ?></option>
							</select>
						</div>
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_fuel"><?php esc_html_e( 'Energy / Fuel Source', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="car_fuel" name="fuel" class="dnf-select">
								<option value="electric" <?php selected( $car_fuel, 'electric' ); ?>><?php esc_html_e( '100% Electric (BEV)', 'dev-nahian-car' ); ?></option>
								<option value="gasoline" <?php selected( $car_fuel, 'gasoline' ); ?>><?php esc_html_e( 'Premium Gasoline', 'dev-nahian-car' ); ?></option>
								<option value="hybrid" <?php selected( $car_fuel, 'hybrid' ); ?>><?php esc_html_e( 'Plug-in Hybrid (PHEV)', 'dev-nahian-car' ); ?></option>
								<option value="diesel" <?php selected( $car_fuel, 'diesel' ); ?>><?php esc_html_e( 'Clean Diesel', 'dev-nahian-car' ); ?></option>
							</select>
						</div>
					</div>
				</div>

				<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:16px;">
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_seats"><?php esc_html_e( 'Seats', 'dev-nahian-car' ); ?></label>
						<input type="number" id="car_seats" name="seats" class="dnf-input" value="<?php echo esc_attr( (string) $car_seats ); ?>" min="1" max="15">
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_doors"><?php esc_html_e( 'Doors', 'dev-nahian-car' ); ?></label>
						<input type="number" id="car_doors" name="doors" class="dnf-input" value="<?php echo esc_attr( (string) $car_doors ); ?>" min="2" max="6">
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_bags"><?php esc_html_e( 'Luggage Bags', 'dev-nahian-car' ); ?></label>
						<input type="number" id="car_bags" name="bags" class="dnf-input" value="<?php echo esc_attr( (string) $car_bags ); ?>" min="0" max="10">
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="car_odo"><?php esc_html_e( 'Odometer (Miles)', 'dev-nahian-car' ); ?></label>
						<input type="number" id="car_odo" name="odometer" class="dnf-input" value="<?php echo esc_attr( (string) $car_odo ); ?>" min="0">
					</div>
				</div>

				<?php if ( ! empty( $global_specs ) ) : ?>
					<div style="margin-top:16px; padding-top:16px; border-top:1px dashed var(--dnf-border, #e2e8f0);">
						<span class="dnf-label" style="margin-bottom:12px;"><?php esc_html_e( 'Configured Technical Specifications', 'dev-nahian-car' ); ?></span>
						<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
							<?php
							foreach ( $global_specs as $s_key => $s_item ) :
								$s_label = is_array( $s_item ) ? ( $s_item['title'] ?? '' ) : (string) $s_item;
								if ( empty( $s_label ) ) {
									continue;
								}
								$spec_val = $car_specs[ $s_label ] ?? '';
								?>
								<div class="dnf-form-group">
									<label class="dnf-label"><?php echo esc_html( $s_label ); ?></label>
									<input type="text" name="specs[<?php echo esc_attr( $s_label ); ?>]" class="dnf-input" value="<?php echo esc_attr( $spec_val ); ?>" placeholder="<?php esc_attr_e( 'Value', 'dev-nahian-car' ); ?>">
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- 3. Staging Hubs & Location Availability -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"></path><circle cx="12" cy="10" r="3"></circle></svg>
						<?php esc_html_e( '3. Staging Terminal & Location Availability', 'dev-nahian-car' ); ?>
					</h3>
				</div>
				<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
					<div class="dnf-form-group">
						<label class="dnf-label" for="default_pickup"><?php esc_html_e( 'Primary Staging Terminal (Pick Up)', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="default_pickup" name="default_pickup" class="dnf-select">
								<option value=""><?php esc_html_e( '-- Choose Staging Hub --', 'dev-nahian-car' ); ?></option>
								<?php
								foreach ( $global_locations as $loc ) :
									$loc_name = is_array( $loc ) ? ( $loc['address'] ?? '' ) : $loc;
									if ( empty( $loc_name ) ) {
										continue;
									}
									?>
									<option value="<?php echo esc_attr( (string) $loc_name ); ?>" <?php selected( $car_pickup, $loc_name ); ?>>
										<?php echo esc_html( (string) $loc_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="dnf-form-group">
						<label class="dnf-label" for="default_dropoff"><?php esc_html_e( 'Default Drop Off Terminal', 'dev-nahian-car' ); ?></label>
						<div class="dnf-select-wrapper">
							<select id="default_dropoff" name="default_dropoff" class="dnf-select">
								<option value=""><?php esc_html_e( '-- Same as Pickup Terminal --', 'dev-nahian-car' ); ?></option>
								<?php
								foreach ( $global_locations as $loc ) :
									$loc_name = is_array( $loc ) ? ( $loc['address'] ?? '' ) : $loc;
									if ( empty( $loc_name ) ) {
										continue;
									}
									?>
									<option value="<?php echo esc_attr( (string) $loc_name ); ?>" <?php selected( $car_dropoff, $loc_name ); ?>>
										<?php echo esc_html( (string) $loc_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- 4. Included Amenities -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
						<?php esc_html_e( '4. Included Cabin & Comfort Amenities', 'dev-nahian-car' ); ?>
					</h3>
				</div>
				<?php if ( ! empty( $global_amenities ) ) : ?>
					<div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
						<?php
						foreach ( $global_amenities as $g_amenity ) :
							$amenity_title = is_array( $g_amenity ) ? ( $g_amenity['title'] ?? '' ) : $g_amenity;
							if ( empty( $amenity_title ) ) {
								continue;
							}
							$is_checked = in_array( $amenity_title, $car_amenities, true );
							?>
							<label class="dnf-switch-label-wrap" style="padding:10px 14px; cursor:pointer;">
								<span style="font-size:13px; font-weight:700; color:var(--dnf-text-primary, #0f172a);">
									<?php echo esc_html( (string) $amenity_title ); ?>
								</span>
								<label class="dnf-switch">
									<input type="checkbox" name="amenities[]" value="<?php echo esc_attr( (string) $amenity_title ); ?>" <?php checked( $is_checked ); ?>>
									<span class="dnf-slider"></span>
								</label>
							</label>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p style="font-size:12.5px; color:var(--dnf-text-secondary, #64748b); margin:0;"><?php esc_html_e( 'No cabin amenities configured in settings extras.', 'dev-nahian-car' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- 5. Insurance & Coverage -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
						<?php esc_html_e( '5. Insurance & Protection Plans', 'dev-nahian-car' ); ?>
					</h3>
				</div>
				<?php if ( ! empty( $global_insurance ) ) : ?>
					<?php
					foreach ( $global_insurance as $ins_key => $ins_item ) :
						$ins_title = $ins_item['title'] ?? '';
						if ( empty( $ins_title ) ) {
							continue;
						}
						$slug_key   = sanitize_key( (string) $ins_key );
						$is_enabled = $car_insurances[ $slug_key ]['enabled'] ?? ( $ins_item['enabled'] ?? 1 );
						$ins_price  = $car_insurances[ $slug_key ]['price'] ?? ( $ins_item['price'] ?? 0 );
						?>
						<div class="dnf-tile-checkbox">
							<label class="dnf-switch">
								<input type="checkbox" name="insurance[<?php echo esc_attr( $slug_key ); ?>][enabled]" value="1" <?php checked( $is_enabled, 1 ); ?>>
								<span class="dnf-slider"></span>
							</label>
							<div style="flex-grow:1;">
								<strong style="display:block; font-size:13.5px; font-weight:700; color:var(--dnf-text-primary, #0f172a);"><?php echo esc_html( (string) $ins_title ); ?></strong>
								<?php if ( ! empty( $ins_item['desc'] ) ) : ?>
									<span style="font-size:12px; color:var(--dnf-text-secondary, #64748b);"><?php echo esc_html( (string) $ins_item['desc'] ); ?></span>
								<?php endif; ?>
							</div>
							<div style="width:130px; flex-shrink:0;">
								<input type="number" step="0.01" name="insurance[<?php echo esc_attr( $slug_key ); ?>][price]" class="dnf-input" value="<?php echo esc_attr( (string) $ins_price ); ?>" placeholder="0.00">
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p style="font-size:12.5px; color:var(--dnf-text-secondary, #64748b); margin:0;"><?php esc_html_e( 'No insurance plans configured globally in settings.', 'dev-nahian-car' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- 6. Cross-Border Authorizations -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
						<?php esc_html_e( '6. Cross-Border Transit Clearances', 'dev-nahian-car' ); ?>
					</h3>
				</div>
				<?php if ( ! empty( $global_borders ) ) : ?>
					<?php
					foreach ( $global_borders as $b_key => $b_item ) :
						$dest = $b_item['destination'] ?? '';
						if ( empty( $dest ) ) {
							continue;
						}
						$b_slug   = sanitize_key( (string) $b_key );
						$b_active = $car_borders[ $b_slug ]['enabled'] ?? ( $b_item['enabled'] ?? 0 );
						$b_fee    = $car_borders[ $b_slug ]['price'] ?? ( $b_item['price'] ?? 0 );
						?>
						<div class="dnf-tile-checkbox">
							<label class="dnf-switch">
								<input type="checkbox" name="border[<?php echo esc_attr( $b_slug ); ?>][enabled]" value="1" <?php checked( $b_active, 1 ); ?>>
								<span class="dnf-slider"></span>
							</label>
							<div style="flex-grow:1;">
								<strong style="display:block; font-size:13.5px; font-weight:700; color:var(--dnf-text-primary, #0f172a);"><?php echo esc_html( (string) $dest ); ?></strong>
							</div>
							<div style="width:130px; flex-shrink:0;">
								<input type="number" step="0.01" name="border[<?php echo esc_attr( $b_slug ); ?>][price]" class="dnf-input" value="<?php echo esc_attr( (string) $b_fee ); ?>" placeholder="Fee">
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p style="font-size:12.5px; color:var(--dnf-text-secondary, #64748b); margin:0;"><?php esc_html_e( 'No cross-border destinations configured globally.', 'dev-nahian-car' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- 7. Seasonal Rates -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
						<?php esc_html_e( '7. Dynamic Seasonal Rates & Surcharges', 'dev-nahian-car' ); ?>
					</h3>
					<button type="button" class="dnf-btn dnf-btn-secondary dnf-btn-sm" onclick="dnfAddSeasonRow();">
						+ <?php esc_html_e( 'Add Rate Window', 'dev-nahian-car' ); ?>
					</button>
				</div>
				<div id="dnf-seasonal-list" style="display:flex; flex-direction:column; gap:10px;">
					<?php if ( ! empty( $car_seasons ) ) : ?>
						<?php foreach ( $car_seasons as $s_idx => $season ) : ?>
							<div style="display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr 36px; gap:10px; align-items:center;">
								<input type="text" name="seasonal[<?php echo esc_attr( (string) $s_idx ); ?>][name]" class="dnf-input" value="<?php echo esc_attr( (string) ( $season['name'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Window Name (e.g. Summer)', 'dev-nahian-car' ); ?>">
								<input type="date" name="seasonal[<?php echo esc_attr( (string) $s_idx ); ?>][start_date]" class="dnf-input" value="<?php echo esc_attr( (string) ( $season['start_date'] ?? '' ) ); ?>">
								<input type="date" name="seasonal[<?php echo esc_attr( (string) $s_idx ); ?>][end_date]" class="dnf-input" value="<?php echo esc_attr( (string) ( $season['end_date'] ?? '' ) ); ?>">
								<input type="number" step="0.01" name="seasonal[<?php echo esc_attr( (string) $s_idx ); ?>][rate]" class="dnf-input" value="<?php echo esc_attr( (string) ( $season['rate'] ?? '' ) ); ?>" placeholder="Daily Rate">
								<button type="button" class="dnf-btn dnf-btn-danger dnf-btn-sm" onclick="this.parentElement.remove();" title="<?php esc_attr_e( 'Delete Window', 'dev-nahian-car' ); ?>">
									&times;
								</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<!-- 8. Editorial & Logistics Notes -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
						<?php esc_html_e( '8. Vehicle Overview & Dispatch Description', 'dev-nahian-car' ); ?>
					</h3>
				</div>
				<div class="dnf-form-group">
					<label class="dnf-label" for="min_booking_days"><?php esc_html_e( 'Minimum Reservation Period (Days)', 'dev-nahian-car' ); ?></label>
					<input type="number" id="min_booking_days" name="min_booking_days" class="dnf-input" value="<?php echo esc_attr( (string) $car_min_days ); ?>" min="1" max="90" style="max-width:200px;">
				</div>
				<div class="dnf-form-group" style="margin-top:20px;">
					<label class="dnf-label"><?php esc_html_e( 'Public Customer Facing Description', 'dev-nahian-car' ); ?></label>
					<?php
					wp_editor(
						$car_notes,
						'vehicle_description',
						array(
							'media_buttons' => true,
							'textarea_rows' => 8,
							'teeny'         => true,
						)
					);
					?>
				</div>
			</div>
		</div>

		<!-- ===================================================================
			 SIDEBAR DOCK: PRICING, CLASSIFICATION, STATUS & MEDIA
			 =================================================================== -->
		<div class="dnf-side-col">
			<!-- Tariff & Operational State Box -->
			<div class="dnf-card-module" style="border-top: 3px solid var(--dnf-primary, #0f172a);">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
						<?php esc_html_e( 'Tariff & Telematics Status', 'dev-nahian-car' ); ?>
					</h3>
				</div>

				<div class="dnf-form-group">
					<label class="dnf-label" for="car_price">
						<?php
						printf(
							/* translators: %s: currency symbol */
							esc_html__( 'Base Daily Rate (%s)', 'dev-nahian-car' ),
							esc_html( $currency )
						);
						?>
						<span style="color:var(--dnf-danger, #dc2626);">*</span>
					</label>
					<input type="number" step="0.01" id="car_price" name="price" class="dnf-input" value="<?php echo esc_attr( (string) $car_price ); ?>" required style="font-size:16px; font-weight:800; color:var(--dnf-text-primary, #0f172a);">
				</div>

				<div class="dnf-form-group">
					<label class="dnf-label" for="car_deposit">
						<?php
						printf(
							/* translators: %s: currency symbol */
							esc_html__( 'Security Deposit Hold (%s)', 'dev-nahian-car' ),
							esc_html( $currency )
						);
						?>
					</label>
					<input type="number" step="0.01" id="car_deposit" name="deposit" class="dnf-input" value="<?php echo esc_attr( (string) $car_deposit ); ?>">
				</div>

				<div class="dnf-form-group">
					<label class="dnf-label" for="car_category"><?php esc_html_e( 'Fleet Classification', 'dev-nahian-car' ); ?></label>
					<div class="dnf-select-wrapper">
						<select id="car_category" name="category" class="dnf-select">
							<?php foreach ( $available_cats as $cat ) : ?>
								<option value="<?php echo esc_attr( (string) $cat['slug'] ); ?>" <?php selected( $car_cat, $cat['slug'] ); ?>>
									<?php echo esc_html( (string) $cat['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="dnf-form-group">
					<label class="dnf-label" for="car_status"><?php esc_html_e( 'Operational Status', 'dev-nahian-car' ); ?></label>
					<div class="dnf-select-wrapper">
						<select id="car_status" name="status" class="dnf-select">
							<option value="ready" <?php selected( $car_status, 'ready' ); ?>><?php esc_html_e( 'Ready • Available', 'dev-nahian-car' ); ?></option>
							<option value="dispatched" <?php selected( $car_status, 'dispatched' ); ?>><?php esc_html_e( 'Active Dispatched', 'dev-nahian-car' ); ?></option>
							<option value="service" <?php selected( $car_status, 'service' ); ?>><?php esc_html_e( 'In Workshop / Maint', 'dev-nahian-car' ); ?></option>
							<option value="retired" <?php selected( $car_status, 'retired' ); ?>><?php esc_html_e( 'Retired Chassis', 'dev-nahian-car' ); ?></option>
						</select>
					</div>
				</div>

				<div class="dnf-form-group">
					<label class="dnf-label" for="car_fuel_lvl"><?php esc_html_e( 'Starting Fuel / Charge Level (%)', 'dev-nahian-car' ); ?></label>
					<input type="number" id="car_fuel_lvl" name="fuel_level" class="dnf-input" value="<?php echo esc_attr( (string) $car_fuel_lvl ); ?>" min="0" max="100">
				</div>

				<p style="margin:24px 0 0 0;">
					<button type="submit" class="dnf-btn dnf-btn-primary" style="width:100%; height:44px; font-size:14px;">
						<?php echo $id > 0 ? esc_html__( 'Update Chassis Record', 'dev-nahian-car' ) : esc_html__( 'Register Fleet Vehicle', 'dev-nahian-car' ); ?>
					</button>
				</p>
			</div>

			<!-- Cover Asset Media Box -->
			<div class="dnf-card-module">
				<div class="dnf-module-header">
					<h3 class="dnf-module-title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
						<?php esc_html_e( 'Chassis Primary Visual', 'dev-nahian-car' ); ?>
					</h3>
				</div>
				<div class="dnf-cover-uploader" id="dnf-featured-preview">
					<?php if ( ! empty( $car_img ) ) : ?>
						<img src="<?php echo esc_url( $car_img ); ?>" alt="<?php esc_attr_e( 'Cover Image', 'dev-nahian-car' ); ?>">
					<?php else : ?>
						<span class="dnf-cover-empty-text"><?php esc_html_e( 'No photo attached', 'dev-nahian-car' ); ?></span>
					<?php endif; ?>
				</div>
				<input type="hidden" name="featured_image" id="dnf-featured-input" value="<?php echo esc_url( $car_img ); ?>">
				<div style="display:flex; gap:8px;">
					<button type="button" class="dnf-btn dnf-btn-secondary" style="flex:1;" onclick="dnfUploadCoverImage();">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
						<?php esc_html_e( 'Select Visual', 'dev-nahian-car' ); ?>
					</button>
					<button type="button" class="dnf-btn dnf-btn-danger dnf-btn-sm" onclick="dnfClearCoverImage();" title="<?php esc_attr_e( 'Remove Visual', 'dev-nahian-car' ); ?>">
						&times;
					</button>
				</div>
			</div>
		</div>
	</div>
</form>

<script>
function dnfUploadCoverImage() {
	var frame = wp.media({
		title: <?php echo wp_json_encode( __( 'Select Vehicle Cover Visual', 'dev-nahian-car' ) ); ?>,
		button: { text: <?php echo wp_json_encode( __( 'Set As Primary Cover', 'dev-nahian-car' ) ); ?> },
		multiple: false
	});

	frame.on('select', function() {
		var attachment = frame.state().get('selection').first().toJSON();
		var inputEl = document.getElementById('dnf-featured-input');
		var previewEl = document.getElementById('dnf-featured-preview');

		if (inputEl && previewEl) {
			inputEl.value = attachment.url;
			previewEl.innerHTML = '<img src="' + attachment.url + '" alt="" style="width:100%; height:100%; object-fit:cover;">';
		}
	});

	frame.open();
}

function dnfClearCoverImage() {
	var inputEl = document.getElementById('dnf-featured-input');
	var previewEl = document.getElementById('dnf-featured-preview');
	if (inputEl) inputEl.value = '';
	if (previewEl) previewEl.innerHTML = '<span class="dnf-cover-empty-text"><?php echo esc_js( __( 'No photo attached', 'dev-nahian-car' ) ); ?></span>';
}

function dnfAddSeasonRow() {
	var container = document.getElementById('dnf-seasonal-list');
	if (!container) return;
	var idx = Date.now();
	var html = '' +
		'<div style="display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr 36px; gap:10px; align-items:center;">' +
			'<input type="text" name="seasonal[' + idx + '][name]" class="dnf-input" placeholder="<?php echo esc_js( __( 'Season Name', 'dev-nahian-car' ) ); ?>">' +
			'<input type="date" name="seasonal[' + idx + '][start_date]" class="dnf-input">' +
			'<input type="date" name="seasonal[' + idx + '][end_date]" class="dnf-input">' +
			'<input type="number" step="0.01" name="seasonal[' + idx + '][rate]" class="dnf-input" placeholder="0.00">' +
			'<button type="button" class="dnf-btn dnf-btn-danger dnf-btn-sm" onclick="this.parentElement.remove();">&times;</button>' +
		'</div>';
	container.insertAdjacentHTML('beforeend', html);
}
</script>
<?php
/**
 * Inspections Module: Create & Edit Workstation (`inspection-add-edit.php`)
 * Handles vehicle condition audits, odometer/fuel logs, telemetry sync, and modern form UI.
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

// Ensure variables have robust fallbacks.
$id             = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_insp     = isset( $table_insp ) ? $table_insp : $wpdb->prefix . 'dev_nahian_inspections';
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'dev_nahian_bookings';
$table_cars     = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$base           = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'inspections',
	),
	admin_url( 'admin.php' )
);

if ( empty( $active_bookings ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$active_bookings = $wpdb->get_results( "SELECT booking_code, car_name, customer_name FROM {$table_bookings} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// -----------------------------------------------------------------------------
// 1. Process Save Action (Create POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_add_insp_submit'] ) ) {
	check_admin_referer( 'dev_nahian_add_insp_verify', 'dev_nahian_insp_nonce' );

	$booking_code = isset( $_POST['booking_code'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_code'] ) ) : '';
	$car_name     = '';
	$car_id       = 0;

	if ( '' !== $booking_code ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$booking_row = $wpdb->get_row( $wpdb->prepare( "SELECT car_id, car_name FROM {$table_bookings} WHERE booking_code = %s", $booking_code ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! empty( $booking_row ) ) {
			$car_name = (string) $booking_row['car_name'];
			$car_id   = absint( $booking_row['car_id'] ?? 0 );
		}
	}

	$fuel_level = isset( $_POST['fuel_level'] ) ? min( 100, max( 0, absint( wp_unslash( $_POST['fuel_level'] ) ) ) ) : 100;
	$odometer   = isset( $_POST['odometer'] ) ? absint( wp_unslash( $_POST['odometer'] ) ) : 0;
	$insp_type  = isset( $_POST['inspection_type'] ) ? sanitize_key( wp_unslash( $_POST['inspection_type'] ) ) : 'checkout';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$table_insp,
		array(
			'booking_code'    => $booking_code,
			'car_name'        => '' !== $car_name ? $car_name : sanitize_text_field( wp_unslash( $_POST['manual_car_name'] ?? __( 'General Fleet Asset', 'dev-nahian-car' ) ) ),
			'inspector_name'  => isset( $_POST['inspector_name'] ) ? sanitize_text_field( wp_unslash( $_POST['inspector_name'] ) ) : '',
			'inspection_type' => $insp_type,
			'fuel_level'      => $fuel_level,
			'odometer'        => $odometer,
			'condition_notes' => isset( $_POST['condition_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['condition_notes'] ) ) : '',
			'inspected_at'    => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
	);

	if ( false !== $inserted ) {
		// Synchronize telemetry to car asset if odometer updated.
		if ( $car_id > 0 && $odometer > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_cars} SET mileage = GREATEST(COALESCE(mileage, 0), %d) WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$odometer,
					$car_id
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'dev-nahian-fleet-manager',
					'tab'   => 'inspections',
					'saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

// -----------------------------------------------------------------------------
// 2. Process Edit Action (Update POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_edit_insp_submit'] ) && $id > 0 ) {
	check_admin_referer( 'dev_nahian_edit_insp_' . $id, 'dev_nahian_insp_nonce' );

	$booking_code = isset( $_POST['booking_code'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_code'] ) ) : '';
	$car_name     = '';
	$car_id       = 0;

	if ( '' !== $booking_code ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$booking_row = $wpdb->get_row( $wpdb->prepare( "SELECT car_id, car_name FROM {$table_bookings} WHERE booking_code = %s", $booking_code ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! empty( $booking_row ) ) {
			$car_name = (string) $booking_row['car_name'];
			$car_id   = absint( $booking_row['car_id'] ?? 0 );
		}
	}

	$fuel_level = isset( $_POST['fuel_level'] ) ? min( 100, max( 0, absint( wp_unslash( $_POST['fuel_level'] ) ) ) ) : 100;
	$odometer   = isset( $_POST['odometer'] ) ? absint( wp_unslash( $_POST['odometer'] ) ) : 0;
	$insp_type  = isset( $_POST['inspection_type'] ) ? sanitize_key( wp_unslash( $_POST['inspection_type'] ) ) : 'checkout';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$updated = $wpdb->update(
		$table_insp,
		array(
			'booking_code'    => $booking_code,
			'car_name'        => '' !== $car_name ? $car_name : sanitize_text_field( wp_unslash( $_POST['manual_car_name'] ?? __( 'General Fleet Asset', 'dev-nahian-car' ) ) ),
			'inspector_name'  => isset( $_POST['inspector_name'] ) ? sanitize_text_field( wp_unslash( $_POST['inspector_name'] ) ) : '',
			'inspection_type' => $insp_type,
			'fuel_level'      => $fuel_level,
			'odometer'        => $odometer,
			'condition_notes' => isset( $_POST['condition_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['condition_notes'] ) ) : '',
		),
		array( 'id' => $id ),
		array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' ),
		array( '%d' )
	);

	if ( false !== $updated ) {
		if ( $car_id > 0 && $odometer > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_cars} SET mileage = GREATEST(COALESCE(mileage, 0), %d) WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$odometer,
					$car_id
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'dev-nahian-fleet-manager',
					'tab'     => 'inspections',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

// -----------------------------------------------------------------------------
// 3. Load Inspection Report for Form Rendering
// -----------------------------------------------------------------------------
$is_edit = ( 'edit' === $action && $id > 0 );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$insp = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_insp} WHERE id = %d", $id ), ARRAY_A ) : array();

$selected_booking = (string) ( $insp['booking_code'] ?? '' );
$insp_type_val    = sanitize_key( (string) ( $insp['inspection_type'] ?? 'checkout' ) );
$inspector_name   = (string) ( $insp['inspector_name'] ?? '' );
$fuel_val         = absint( $insp['fuel_level'] ?? 100 );
$odometer_val     = absint( $insp['odometer'] ?? 0 );
$condition_notes  = (string) ( $insp['condition_notes'] ?? '' );
?>

<style>
	/* Scoped Inspection Form Surface */
	.dnf-insp-form-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 32px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
		max-width: 920px;
	}
	.dnf-insp-form-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px;
	}
	@media (max-width: 960px) {
		.dnf-insp-form-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 640px) {
		.dnf-insp-form-grid {
			grid-template-columns: 1fr;
		}
	}
	.col-span-full {
		grid-column: 1 / -1;
	}

	.dnf-insp-section-title {
		grid-column: 1 / -1;
		font-size: 11px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: var(--dnf-accent-teal, #0d9488);
		padding-top: 18px;
		margin-top: 4px;
		border-top: 1px solid var(--dnf-border, #e2e8f0);
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.dnf-insp-section-title:first-of-type {
		border-top: none;
		padding-top: 0;
		margin-top: 0;
	}
</style>

<!-- Toolbar / Subnav Header -->
<div class="dnf-insp-toolbar" style="margin-bottom: 20px;">
	<div class="dnf-insp-toolbar-left">
		<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
			<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Cancel & Back to Condition Log', 'dev-nahian-car' ); ?></span>
		</a>
		<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
			<?php
			echo $is_edit
				? sprintf(
					/* translators: %d: inspection record ID */
					esc_html__( 'Edit Inspection Report #%d', 'dev-nahian-car' ),
					absint( $id )
				)
				: esc_html__( 'Record Walkaround Inspection Report', 'dev-nahian-car' );
			?>
		</h2>
	</div>
</div>

<!-- Form Surface -->
<div class="dnf-insp-form-card">
	<form method="post" action="" id="dnfInspForm">
		<?php
		if ( $is_edit ) {
			wp_nonce_field( 'dev_nahian_edit_insp_' . $id, 'dev_nahian_insp_nonce' );
			echo '<input type="hidden" name="dev_nahian_edit_insp_submit" value="1">';
		} else {
			wp_nonce_field( 'dev_nahian_add_insp_verify', 'dev_nahian_insp_nonce' );
			echo '<input type="hidden" name="dev_nahian_add_insp_submit" value="1">';
		}
		?>

		<div class="dnf-insp-form-grid">
			<!-- Section 1: Booking & Asset Assignment -->
			<div class="dnf-insp-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
				<?php esc_html_e( '1. Booking Reservation & Manifest Link', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="booking_code">
					<?php esc_html_e( 'Target Reservation Code', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<div class="dnf-select-wrapper">
					<select id="booking_code" name="booking_code" class="dnf-select" required>
						<option value=""><?php esc_html_e( '-- Choose Associated Manifest --', 'dev-nahian-car' ); ?></option>
						<?php if ( ! empty( $active_bookings ) ) : ?>
							<?php foreach ( $active_bookings as $b ) : ?>
								<option value="<?php echo esc_attr( (string) $b['booking_code'] ); ?>" <?php selected( $selected_booking, $b['booking_code'] ); ?>>
									<?php echo esc_html( '#' . $b['booking_code'] . ' — ' . $b['customer_name'] . ' (' . $b['car_name'] . ')' ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="inspection_type">
					<?php esc_html_e( 'Inspection Handover Phase', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="inspection_type" name="inspection_type" class="dnf-select">
						<option value="checkout" <?php selected( $insp_type_val, 'checkout' ); ?>><?php esc_html_e( 'Pre-Rental Handover • Check-Out', 'dev-nahian-car' ); ?></option>
						<option value="checkin" <?php selected( $insp_type_val, 'checkin' ); ?>><?php esc_html_e( 'Post-Rental Return • Check-In', 'dev-nahian-car' ); ?></option>
						<option value="spot_check" <?php selected( $insp_type_val, 'spot_check' ); ?>><?php esc_html_e( 'Fleet Spot Audit • Interim Check', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="inspector_name">
					<?php esc_html_e( 'Inspector / Agent Name', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="text" id="inspector_name" name="inspector_name" class="dnf-input" value="<?php echo esc_attr( $inspector_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. Jonathan Sterling', 'dev-nahian-car' ); ?>" required>
			</div>

			<!-- Section 2: Telemetry & Condition Metrics -->
			<div class="dnf-insp-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
				<?php esc_html_e( '2. Telemetry & Energy Metrics', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="fuel_level">
					<?php esc_html_e( 'Fuel / Battery Level (%)', 'dev-nahian-car' ); ?>
				</label>
				<input type="number" id="fuel_level" name="fuel_level" class="dnf-input" value="<?php echo esc_attr( (string) $fuel_val ); ?>" min="0" max="100">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="odometer">
					<?php esc_html_e( 'Odometer Reading (mi/km)', 'dev-nahian-car' ); ?>
				</label>
				<input type="number" id="odometer" name="odometer" class="dnf-input" value="<?php echo esc_attr( (string) $odometer_val ); ?>" placeholder="12500" min="0">
			</div>

			<!-- Section 3: Walkaround Notes & Damage Log -->
			<div class="dnf-insp-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
				<?php esc_html_e( '3. Body Panels, Glass, Wheels & Damage Log', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group col-span-full" style="margin-bottom:0;">
				<label class="dnf-label" for="condition_notes">
					<?php esc_html_e( 'Condition Log, Scratch Details & Wheel Scuffs', 'dev-nahian-car' ); ?>
				</label>
				<textarea id="condition_notes" name="condition_notes" class="dnf-textarea" placeholder="<?php esc_attr_e( 'Front splitter pristine, rear passenger alloy has 2cm curb rash, windshield free of chips, interior detailing verified clean...', 'dev-nahian-car' ); ?>"><?php echo esc_textarea( $condition_notes ); ?></textarea>
			</div>
		</div>

		<div style="margin-top: 32px; display: flex; gap: 12px; align-items: center;">
			<button type="submit" class="dnf-btn dnf-btn-primary">
				<?php echo $is_edit ? esc_html__( 'Save Audit Changes', 'dev-nahian-car' ) : esc_html__( 'Submit Inspection Report', 'dev-nahian-car' ); ?>
			</button>
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?>
			</a>
		</div>
	</form>
</div>
<?php
/**
 * Maintenance Module: Create & Edit Workstation (`maint-add-edit.php`)
 * Handles maintenance ticket creation, service logs, automated car status sync, and modern form UI.
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
$id          = isset( $id ) && $id > 0 ? absint( $id ) : ( isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0 );
$table_maint = isset( $table_maint ) ? $table_maint : $wpdb->prefix . 'dev_nahian_maintenance';
$table_cars  = isset( $table_cars ) ? $table_cars : $wpdb->prefix . 'dev_nahian_cars';
$base        = isset( $base ) ? $base : add_query_arg(
	array(
		'page' => 'dev-nahian-fleet-manager',
		'tab'  => 'maintenance',
	),
	admin_url( 'admin.php' )
);
$currency    = isset( $currency ) ? $currency : (string) get_option( 'dev_nahian_currency', 'USD' );

if ( empty( $fleet_cars ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$fleet_cars = $wpdb->get_results( "SELECT id, name, vin FROM {$table_cars} ORDER BY name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// -----------------------------------------------------------------------------
// 1. Process Save Action (Create POST)
// -----------------------------------------------------------------------------
if ( isset( $_POST['dev_nahian_add_maint_submit'] ) ) {
	check_admin_referer( 'dev_nahian_add_maint_verify', 'dev_nahian_maint_nonce' );

	$car_id       = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;
	$car_name     = '';
	$maint_status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'scheduled';

	if ( $car_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$car_name = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
	}

	$service_date = ! empty( $_POST['service_date'] ) ? sanitize_text_field( wp_unslash( $_POST['service_date'] ) ) : current_time( 'Y-m-d' );
	$raw_cost     = isset( $_POST['cost'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['cost'] ) ) : 0.00;
	$cost         = (float) number_format( max( 0.0, $raw_cost ), 2, '.', '' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$table_maint,
		array(
			'car_id'       => $car_id,
			'car_name'     => $car_name,
			'service_type' => isset( $_POST['service_type'] ) ? sanitize_text_field( wp_unslash( $_POST['service_type'] ) ) : '',
			'cost'         => $cost,
			'service_date' => $service_date,
			'status'       => $maint_status,
			'notes'        => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			'created_at'   => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
	);

	if ( false !== $inserted ) {
		// Synchronize Vehicle Status with Maintenance Ticket.
		if ( $car_id > 0 ) {
			if ( 'in_workshop' === $maint_status || 'in_progress' === $maint_status ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update( $table_cars, array( 'status' => 'maintenance' ), array( 'id' => $car_id ), array( '%s' ), array( '%d' ) );
			} elseif ( 'completed' === $maint_status ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update( $table_cars, array( 'status' => 'ready' ), array( 'id' => $car_id ), array( '%s' ), array( '%d' ) );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'dev-nahian-fleet-manager',
					'tab'   => 'maintenance',
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
if ( isset( $_POST['dev_nahian_edit_maint_submit'] ) && $id > 0 ) {
	check_admin_referer( 'dev_nahian_edit_maint_' . $id, 'dev_nahian_maint_nonce' );

	$car_id       = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;
	$car_name     = '';
	$maint_status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'scheduled';

	if ( $car_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$car_name = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) );
	}

	$service_date = ! empty( $_POST['service_date'] ) ? sanitize_text_field( wp_unslash( $_POST['service_date'] ) ) : current_time( 'Y-m-d' );
	$raw_cost     = isset( $_POST['cost'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['cost'] ) ) : 0.00;
	$cost         = (float) number_format( max( 0.0, $raw_cost ), 2, '.', '' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$updated = $wpdb->update(
		$table_maint,
		array(
			'car_id'       => $car_id,
			'car_name'     => $car_name,
			'service_type' => isset( $_POST['service_type'] ) ? sanitize_text_field( wp_unslash( $_POST['service_type'] ) ) : '',
			'cost'         => $cost,
			'service_date' => $service_date,
			'status'       => $maint_status,
			'notes'        => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
		),
		array( 'id' => $id ),
		array( '%d', '%s', '%s', '%f', '%s', '%s', '%s' ),
		array( '%d' )
	);

	if ( false !== $updated ) {
		// Synchronize Vehicle Status with Maintenance Ticket.
		if ( $car_id > 0 ) {
			if ( 'in_workshop' === $maint_status || 'in_progress' === $maint_status ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update( $table_cars, array( 'status' => 'maintenance' ), array( 'id' => $car_id ), array( '%s' ), array( '%d' ) );
			} elseif ( 'completed' === $maint_status ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update( $table_cars, array( 'status' => 'ready' ), array( 'id' => $car_id ), array( '%s' ), array( '%d' ) );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'dev-nahian-fleet-manager',
					'tab'     => 'maintenance',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

// -----------------------------------------------------------------------------
// 3. Load Maintenance Ticket for Form Rendering
// -----------------------------------------------------------------------------
$is_edit = ( 'edit' === $action && $id > 0 );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$maint = $is_edit ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_maint} WHERE id = %d", $id ), ARRAY_A ) : array();

$maint_car_id = absint( $maint['car_id'] ?? 0 );
$service_type = (string) ( $maint['service_type'] ?? '' );
$cost_val     = isset( $maint['cost'] ) ? (string) $maint['cost'] : '';
$service_date = (string) ( $maint['service_date'] ?? current_time( 'Y-m-d' ) );
$status_val   = sanitize_key( (string) ( $maint['status'] ?? 'scheduled' ) );
$maint_notes  = (string) ( $maint['notes'] ?? '' );
?>

<style>
	/* Scoped Maintenance Form Surface */
	.dnf-maint-form-card {
		background: #ffffff;
		border: 1px solid var(--dnf-border, #e2e8f0);
		border-radius: var(--dnf-radius-lg, 12px);
		padding: 32px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
		max-width: 920px;
	}
	.dnf-maint-form-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px;
	}
	@media (max-width: 960px) {
		.dnf-maint-form-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 640px) {
		.dnf-maint-form-grid {
			grid-template-columns: 1fr;
		}
	}
	.col-span-full {
		grid-column: 1 / -1;
	}

	.dnf-maint-section-title {
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
	.dnf-maint-section-title:first-of-type {
		border-top: none;
		padding-top: 0;
		margin-top: 0;
	}
</style>

<!-- Toolbar / Subnav Header -->
<div class="dnf-maint-toolbar" style="margin-bottom: 20px;">
	<div class="dnf-maint-toolbar-left">
		<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
			<?php echo dev_nahian_get_icon( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Cancel & Back to Service Ledger', 'dev-nahian-car' ); ?></span>
		</a>
		<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--dnf-text-primary, #0f172a);">
			<?php
			echo $is_edit
				? sprintf(
					/* translators: %d: ticket ID */
					esc_html__( 'Edit Maintenance Record #%d', 'dev-nahian-car' ),
					absint( $id )
				)
				: esc_html__( 'Schedule Workshop Service Ticket', 'dev-nahian-car' );
			?>
		</h2>
	</div>
</div>

<!-- Form Surface -->
<div class="dnf-maint-form-card">
	<form method="post" action="" id="dnfMaintForm">
		<?php
		if ( $is_edit ) {
			wp_nonce_field( 'dev_nahian_edit_maint_' . $id, 'dev_nahian_maint_nonce' );
			echo '<input type="hidden" name="dev_nahian_edit_maint_submit" value="1">';
		} else {
			wp_nonce_field( 'dev_nahian_add_maint_verify', 'dev_nahian_maint_nonce' );
			echo '<input type="hidden" name="dev_nahian_add_maint_submit" value="1">';
		}
		?>

		<div class="dnf-maint-form-grid">
			<!-- Section 1: Service & Asset Allocation -->
			<div class="dnf-maint-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
				<?php esc_html_e( '1. Service Scope & Fleet Chassis Allocation', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="car_id">
					<?php esc_html_e( 'Target Vehicle Chassis', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<div class="dnf-select-wrapper">
					<select id="car_id" name="car_id" class="dnf-select" required>
						<option value=""><?php esc_html_e( '-- Choose Target Chassis --', 'dev-nahian-car' ); ?></option>
						<?php if ( ! empty( $fleet_cars ) ) : ?>
							<?php foreach ( $fleet_cars as $car ) : ?>
								<option value="<?php echo esc_attr( (string) $car['id'] ); ?>" <?php selected( $maint_car_id, absint( $car['id'] ) ); ?>>
									<?php echo esc_html( (string) $car['name'] . ( ! empty( $car['vin'] ) ? ' (VIN: ' . substr( (string) $car['vin'], -6 ) . ')' : '' ) ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="service_type">
					<?php esc_html_e( 'Service Procedure / Ticket Title', 'dev-nahian-car' ); ?> <span style="color:var(--dnf-danger, #dc2626);">*</span>
				</label>
				<input type="text" id="service_type" name="service_type" class="dnf-input" value="<?php echo esc_attr( $service_type ); ?>" placeholder="<?php esc_attr_e( 'e.g. 10k Oil Flush & Brake Rotors', 'dev-nahian-car' ); ?>" required>
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="cost">
					<?php
					printf(
						/* translators: %s: currency code */
						esc_html__( 'Workshop Service Cost (%s)', 'dev-nahian-car' ),
						esc_html( $currency )
					);
					?>
				</label>
				<input type="number" step="0.01" min="0" id="cost" name="cost" class="dnf-input" value="<?php echo esc_attr( $cost_val ); ?>" placeholder="0.00">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="service_date">
					<?php esc_html_e( 'Scheduled Service Date', 'dev-nahian-car' ); ?>
				</label>
				<input type="date" id="service_date" name="service_date" class="dnf-input" value="<?php echo esc_attr( $service_date ); ?>">
			</div>

			<div class="dnf-form-group">
				<label class="dnf-label" for="status">
					<?php esc_html_e( 'Workshop Service Status', 'dev-nahian-car' ); ?>
				</label>
				<div class="dnf-select-wrapper">
					<select id="status" name="status" class="dnf-select">
						<option value="scheduled" <?php selected( $status_val, 'scheduled' ); ?>><?php esc_html_e( 'Scheduled • In Queue', 'dev-nahian-car' ); ?></option>
						<option value="in_workshop" <?php selected( $status_val, 'in_workshop' ); ?>><?php esc_html_e( 'In Workshop • Vehicle Grounded', 'dev-nahian-car' ); ?></option>
						<option value="completed" <?php selected( $status_val, 'completed' ); ?>><?php esc_html_e( 'Completed & Verified • Vehicle Cleared', 'dev-nahian-car' ); ?></option>
						<option value="cancelled" <?php selected( $status_val, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled Ticket', 'dev-nahian-car' ); ?></option>
					</select>
				</div>
			</div>

			<!-- Section 2: Mechanic Diagnostics & Parts Details -->
			<div class="dnf-maint-section-title">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
				<?php esc_html_e( '2. Mechanic Work Notes, Diagnostic Codes & Replaced Parts', 'dev-nahian-car' ); ?>
			</div>

			<div class="dnf-form-group col-span-full" style="margin-bottom:0;">
				<label class="dnf-label" for="notes">
					<?php esc_html_e( 'Detailed Diagnosis, Replaced Parts & Fluid Specs', 'dev-nahian-car' ); ?>
				</label>
				<textarea id="notes" name="notes" class="dnf-textarea" placeholder="<?php esc_attr_e( 'Installed OEM Brembo front ceramic brake pads, Motul RBF 660 fluid flush completed, tire tread measured at 7.5mm, telemetry health verified...', 'dev-nahian-car' ); ?>"><?php echo esc_textarea( $maint_notes ); ?></textarea>
			</div>
		</div>

		<div style="margin-top: 32px; display: flex; gap: 12px; align-items: center;">
			<button type="submit" class="dnf-btn dnf-btn-primary">
				<?php echo $is_edit ? esc_html__( 'Save Ticket Changes', 'dev-nahian-car' ) : esc_html__( 'Log Maintenance Ticket', 'dev-nahian-car' ); ?>
			</button>
			<a href="<?php echo esc_url( $base ); ?>" class="dnf-btn dnf-btn-secondary">
				<?php esc_html_e( 'Cancel', 'dev-nahian-car' ); ?>
			</a>
		</div>
	</form>
</div>
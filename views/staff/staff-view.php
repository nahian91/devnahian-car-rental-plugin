<?php
/**
 * Staff Module: Single Personnel Dossier View (`staff-view.php`)
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $id ), ARRAY_A );

if ( ! $staff ) :
    ?>
    <div class="notice notice-error" style="margin: 0 0 20px 0;"><p><?php esc_html_e( 'Staff profile not found or already deleted.', 'dev-nahian-car' ); ?></p></div>
    <p><a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">&larr; <?php esc_html_e( 'Back to Staff Directory', 'dev-nahian-car' ); ?></a></p>
    <?php
    return;
endif;

$status_key = sanitize_key( $staff['status'] );
$role_key   = sanitize_key( $staff['role_type'] );
$edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $staff['id'] ), $base );
$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $staff['id'] ), $base ), 'dev_nahian_delete_staff_' . $staff['id'] );
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

<div class="dn-staff-bar">
    <div class="dn-staff-bar-left">
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">&larr; <?php esc_html_e( 'Back to Staff Directory', 'dev-nahian-car' ); ?></a>
        <h2 class="dn-staff-title"><?php printf( esc_html__( 'Staff Dossier #%d', 'dev-nahian-car' ), esc_html( $staff['id'] ) ); ?></h2>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a;"><?php esc_html_e( 'Edit Profile', 'dev-nahian-car' ); ?></a>
        <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Remove this staff member permanently?', 'dev-nahian-car' ); ?>');" style="line-height:28px;"><?php esc_html_e( 'Delete', 'dev-nahian-car' ); ?></a>
    </div>
</div>

<div class="dn-view-card">
    <div class="dn-view-header">
        <div class="dn-view-title-wrap">
            <h3><?php echo esc_html( $staff['name'] ); ?></h3>
            <span class="dn-view-subtitle"><?php echo esc_html( 'STAFF CODE: ' . $staff['staff_code'] ); ?></span>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <span class="dev-nahian-role-badge" style="font-size: 12px; padding: 4px 10px;"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $role_key ) ) ); ?></span>
            <span class="dev-nahian-status-pill <?php echo esc_attr( $status_key ); ?>" style="font-size: 13px; padding: 5px 12px;">
                &bull; <?php echo esc_html( ucfirst( str_replace( '_', ' ', $status_key ) ) ); ?>
            </span>
        </div>
    </div>

    <div class="dn-view-grid">
        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Contact Email Address', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><?php echo esc_html( $staff['email'] ); ?></span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Phone Number', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><?php echo esc_html( ! empty( $staff['phone'] ) ? $staff['phone'] : 'N/A' ); ?></span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Driver / Chauffeur License No.', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value" style="font-family: monospace;"><?php echo esc_html( ! empty( $staff['license_no'] ) ? $staff['license_no'] : 'No DL Registered' ); ?></span>
        </div>

        <div class="dn-view-field-group">
            <span class="dn-view-label"><?php esc_html_e( 'Registration Date', 'dev-nahian-car' ); ?></span>
            <span class="dn-view-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $staff['created_at'] ) ) ); ?></span>
        </div>
    </div>

    <div style="margin-top: 20px;">
        <span class="dn-view-label" style="display: block; margin-bottom: 6px;"><?php esc_html_e( 'Credentials & Operational Notes', 'dev-nahian-car' ); ?></span>
        <div class="dn-view-notes-box">
            <?php echo esc_html( ! empty( $staff['notes'] ) ? $staff['notes'] : __( 'No credentials or operational notes recorded for this staff member.', 'dev-nahian-car' ) ); ?>
        </div>
    </div>
</div>
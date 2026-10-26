<?php
/**
 * Cars Module: Executive Dossier View (`cars-view.php`)
 * Redesigned to executive SaaS UI/UX standards with refined metrics,
 * structured attribute grids, and professional visual hierarchy.
 *
 * @package Dev_Nahian_Fleet_Manager
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$car        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cars} WHERE id = %d", $id ), ARRAY_A );
$meta       = get_option( "dev_nahian_car_meta_{$id}", array() );
$status_key = sanitize_key( $car['status'] ?? 'ready' );
$specs      = $meta['technical_specs'] ?? array();
$amenities  = $meta['amenities'] ?? array();
?>
<style>
    .dn-dossier-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    @media (max-width: 900px) {
        .dn-dossier-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .dn-dossier-stat-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .dn-dossier-stat-box span {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }
    .dn-dossier-stat-box strong {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
    }
    .dn-dossier-stat-box code {
        font-size: 13px;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
        color: #334155;
    }
</style>

<div class="dev-nahian-subnav-bar">
    <div>
        <a href="<?php echo esc_url( $base ); ?>" class="button button-secondary">
            <span class="dashicons dashicons-arrow-left-alt" style="vertical-align:middle; line-height:26px;"></span> <?php esc_html_e( 'Back to Fleet', 'dev-nahian-car' ); ?>
        </a>
        <h2 style="display:inline-block; vertical-align:middle; margin-left:8px;" class="dev-nahian-subnav-title"><?php echo esc_html( $car['name'] ); ?> &mdash; Executive Dossier</h2>
    </div>
    <div>
        <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $id ), $base ) ); ?>" class="button button-primary" style="background:#0f172a; border-color:#0f172a; font-weight:700;">
            <?php esc_html_e( 'Edit Vehicle', 'dev-nahian-car' ); ?>
        </a>
    </div>
</div>

<div class="dn-editor-layout">
    <!-- MAIN CONTENT COLUMN -->
    <div class="dn-main-col">
        <div class="dn-card-module">
            <div class="dn-module-header">
                <h3 class="dn-module-title"><span class="dashicons dashicons-car"></span> <?php esc_html_e( 'Asset Overview & Specifications', 'dev-nahian-car' ); ?></h3>
                <span class="dn-badge <?php echo esc_attr( $status_key ); ?>">&bull; <?php echo esc_html( ucfirst( $status_key ) ); ?></span>
            </div>

            <!-- Metric Highlight Grid -->
            <div class="dn-dossier-grid">
                <div class="dn-dossier-stat-box">
                    <span><?php esc_html_e( 'Brand / Make', 'dev-nahian-car' ); ?></span>
                    <strong><?php echo esc_html( ! empty( $car['brand'] ) ? $car['brand'] : 'N/A' ); ?></strong>
                </div>
                <div class="dn-dossier-stat-box">
                    <span><?php esc_html_e( 'Model Year', 'dev-nahian-car' ); ?></span>
                    <strong><?php echo esc_html( $car['year'] ); ?></strong>
                </div>
                <div class="dn-dossier-stat-box">
                    <span><?php esc_html_e( 'License Plate', 'dev-nahian-car' ); ?></span>
                    <div><code><?php echo esc_html( ! empty( $car['plate'] ) ? $car['plate'] : 'N/A' ); ?></code></div>
                </div>
                <div class="dn-dossier-stat-box">
                    <span><?php esc_html_e( 'VIN Number', 'dev-nahian-car' ); ?></span>
                    <div><code><?php echo esc_html( $car['vin'] ); ?></code></div>
                </div>
                <div class="dn-dossier-stat-box">
                    <span><?php esc_html_e( 'Base Daily Rate', 'dev-nahian-car' ); ?></span>
                    <strong style="color: #0d9488;"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $car['price'], 2 ) ); ?></strong>
                </div>
                <div class="dn-dossier-stat-box">
                    <span><?php esc_html_e( 'Security Deposit', 'dev-nahian-car' ); ?></span>
                    <strong><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $car['deposit'], 2 ) ); ?></strong>
                </div>
            </div>

            <?php if ( ! empty( $specs ) ) : ?>
                <h4 style="margin:24px 0 12px; font-size:12.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#475569;"><?php esc_html_e( 'Technical Attributes', 'dev-nahian-car' ); ?></h4>
                <ul style="margin:0 0 24px 0; padding:0; list-style:none; display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <?php foreach ( $specs as $s_label => $s_val ) : ?>
                        <li style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px; font-size:13px; display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:#64748b; font-weight:600;"><?php echo esc_html( $s_label ); ?></span>
                            <strong style="color:#0f172a;"><?php echo esc_html( $s_val ); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( ! empty( $amenities ) ) : ?>
                <h4 style="margin:24px 0 12px; font-size:12.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#475569;"><?php esc_html_e( 'Included Amenities', 'dev-nahian-car' ); ?></h4>
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px;">
                    <?php foreach ( $amenities as $amenity ) : ?>
                        <span style="background:#f0fdfa; color:#0d9488; border:1px solid #ccfbf1; padding:6px 12px; border-radius:6px; font-size:12.5px; font-weight:700;">✓ <?php echo esc_html( $amenity ); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h4 style="margin:24px 0 12px; font-size:12.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#475569;"><?php esc_html_e( 'Editorial Notes', 'dev-nahian-car' ); ?></h4>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:16px; border-radius:8px; font-size:13.5px; line-height:1.6; color:#334155;">
                <?php echo ! empty( $car['notes'] ) ? wp_kses_post( $car['notes'] ) : '<em style="color:#94a3b8;">No editorial description provided for this vehicle.</em>'; ?>
            </div>
        </div>
    </div>

    <!-- SIDEBAR DOCK -->
    <div class="dn-side-col">
        <div class="dn-card-module">
            <div class="dn-module-header">
                <h3 class="dn-module-title"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Featured Cover Photo', 'dev-nahian-car' ); ?></h3>
            </div>
            <?php if ( ! empty( $car['image'] ) ) : ?>
                <div style="border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #0f172a;">
                    <img src="<?php echo esc_url( $car['image'] ); ?>" alt="<?php echo esc_attr( $car['name'] ); ?>" style="width:100%; height:220px; object-fit:cover; display:block;">
                </div>
            <?php else : ?>
                <div style="height: 160px; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 13px;">
                    <?php esc_html_e( 'No photo attached.', 'dev-nahian-car' ); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
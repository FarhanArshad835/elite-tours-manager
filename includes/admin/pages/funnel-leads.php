<?php
/**
 * Admin: Funnel Leads
 * Displays the et_funnel_leads option (captured by the experience funnel
 * contact form) as a table. Lets admins clear all leads and export CSV.
 */
defined( 'ABSPATH' ) || exit;

// ── Clear leads (POST) ──────────────────────────────────────────────────────
add_action( 'admin_post_etm_clear_funnel_leads', function () {
    if ( ! current_user_can( 'manage_options' ) )       wp_die( 'Unauthorised' );
    check_admin_referer( 'etm_clear_funnel_leads' );
    delete_option( 'et_funnel_leads' );
    wp_safe_redirect( add_query_arg( 'cleared', '1', menu_page_url( 'et-funnel-leads', false ) ) );
    exit;
} );

// ── Export CSV (GET) ────────────────────────────────────────────────────────
add_action( 'admin_post_etm_export_funnel_leads', function () {
    if ( ! current_user_can( 'manage_options' ) )       wp_die( 'Unauthorised' );
    check_admin_referer( 'etm_export_funnel_leads' );

    $leads = get_option( 'et_funnel_leads', [] );
    if ( ! is_array( $leads ) ) $leads = [];

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="elite-tours-leads-' . gmdate( 'Y-m-d' ) . '.csv"' );

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, [ 'Time', 'Experience', 'Page URL', 'Name', 'Email', 'Phone', 'Message' ] );
    foreach ( array_reverse( $leads ) as $lead ) {
        fputcsv( $out, [
            $lead['time']       ?? '',
            $lead['experience'] ?? '',
            $lead['page_url']   ?? '',
            $lead['name']       ?? '',
            $lead['email']      ?? '',
            $lead['phone']      ?? '',
            $lead['message']    ?? '',
        ] );
    }
    fclose( $out );
    exit;
} );

// ── Render ──────────────────────────────────────────────────────────────────
function etm_funnel_leads_page(): void {
    $leads = get_option( 'et_funnel_leads', [] );
    if ( ! is_array( $leads ) ) $leads = [];
    $leads = array_reverse( $leads ); // newest first
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title"><?php echo etm_lucide( 'inbox', 22 ); ?> Funnel Leads
            <span style="font-size:14px;font-weight:400;color:#666;margin-left:10px;">
                (<?php echo count( $leads ); ?> total)
            </span>
        </h1>

        <?php if ( isset( $_GET['cleared'] ) ) : ?>
            <div class="etm-notice etm-notice--success">All leads cleared.</div>
        <?php endif; ?>

        <p class="etm-page-desc">
            Captured from the experience-page contact form. Newest first. The list is capped at the most recent 200 leads.
            Always also sent by email to your Site Settings contact address — this view is the durable record.
        </p>

        <?php if ( empty( $leads ) ) : ?>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:60px;text-align:center;color:#666;">
                <p style="font-size:16px;margin:0;">No leads yet.</p>
                <p style="font-size:13px;margin-top:8px;">Submissions from /experiences/{slug}/ contact forms will appear here.</p>
            </div>
        <?php else : ?>
            <div style="margin:14px 0;display:flex;gap:10px;">
                <form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                    <input type="hidden" name="action" value="etm_export_funnel_leads">
                    <?php wp_nonce_field( 'etm_export_funnel_leads' ); ?>
                    <button type="submit" class="button">Export CSV</button>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                      style="display:inline;"
                      onsubmit="return confirm('Permanently clear all funnel leads? This cannot be undone.');">
                    <input type="hidden" name="action" value="etm_clear_funnel_leads">
                    <?php wp_nonce_field( 'etm_clear_funnel_leads' ); ?>
                    <button type="submit" class="button button-link-delete">Clear all leads</button>
                </form>
            </div>

            <table class="wp-list-table widefat striped" style="margin-top:8px;">
                <thead>
                    <tr>
                        <th style="width:140px;">Time</th>
                        <th style="width:180px;">Experience</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th style="width:130px;">Phone</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $leads as $lead ) : ?>
                    <tr>
                        <td>
                            <?php echo esc_html( mysql2date( 'j M Y, H:i', $lead['time'] ?? '' ) ); ?>
                        </td>
                        <td>
                            <?php if ( ! empty( $lead['page_url'] ) ) : ?>
                                <a href="<?php echo esc_url( $lead['page_url'] ); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html( $lead['experience'] ?: '—' ); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html( $lead['experience'] ?: '—' ); ?>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html( $lead['name'] ?? '' ); ?></strong></td>
                        <td>
                            <?php if ( ! empty( $lead['email'] ) ) : ?>
                                <a href="mailto:<?php echo esc_attr( $lead['email'] ); ?>"><?php echo esc_html( $lead['email'] ); ?></a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( ! empty( $lead['phone'] ) ) : ?>
                                <a href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', $lead['phone'] ) ); ?>"><?php echo esc_html( $lead['phone'] ); ?></a>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:420px;white-space:pre-wrap;"><?php echo esc_html( $lead['message'] ?? '' ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

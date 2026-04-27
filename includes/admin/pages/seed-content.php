<?php
/**
 * Admin: Seed Experience Content
 *
 * One-click bulk-populate the three experience CPT posts (Bespoke Private
 * Tour of Ireland, Trace Your Irish Heritage, Ireland's Craft Distilleries)
 * with their full editorial content, plus the supporting images. Idempotent
 * — safe to re-run on any environment (local, staging, live).
 */
defined( 'ABSPATH' ) || exit;

// ── Run seeders (POST) ──────────────────────────────────────────────────────
add_action( 'admin_post_etm_run_seeders', function () {
    if ( ! current_user_can( 'manage_options' ) )       wp_die( 'Unauthorised' );
    check_admin_referer( 'etm_run_seeders' );

    require_once ETM_PATH . 'includes/seeders/class-experience-seeder.php';
    $seeder = new ETM_Experience_Seeder();
    $log    = $seeder->run();

    set_transient( 'etm_seeder_log_' . get_current_user_id(), $log, 5 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( 'ran', '1', menu_page_url( 'et-seed-content', false ) ) );
    exit;
} );

// ── Render ──────────────────────────────────────────────────────────────────
function etm_seed_content_page(): void {
    $log_key = 'etm_seeder_log_' . get_current_user_id();
    $log     = get_transient( $log_key );
    $just_ran = isset( $_GET['ran'] ) && $_GET['ran'] === '1';
    if ( $just_ran && $log ) {
        delete_transient( $log_key );
    }
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">🌱 Seed Experience Content</h1>

        <div style="max-width:780px;margin-top:14px;">

            <p style="font-size:14px;line-height:1.6;color:#3c434a;">
                Populates the three experience pages
                — <strong>Bespoke Private Tour of Ireland</strong>,
                <strong>Trace Your Irish Heritage</strong>, and
                <strong>Ireland's Craft Distilleries</strong> —
                with their complete editorial content (60+ meta fields per
                experience) and the supporting images bundled with this plugin.
            </p>

            <p style="font-size:14px;line-height:1.6;color:#3c434a;">
                Use this on a fresh environment (live, staging, a new local clone)
                to bring the experience pages to the same state they have on the
                primary development site. The seeder is <strong>idempotent</strong>
                — running it twice will not create duplicates; it updates existing
                posts and reuses already-imported images.
            </p>

            <div style="background:#fff7e6;border-left:4px solid #f0b849;padding:12px 16px;margin:18px 0;font-size:13px;line-height:1.5;">
                <strong>Heads up:</strong> Running the seeder will overwrite any
                manual edits you have made to those three experience posts'
                meta fields. Featured images, hero copy, highlights, story copy,
                pillars, process steps, CTA copy and similar links will all be
                reset to the bundled content. Other posts and pages are untouched.
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:22px 0;">
                <?php wp_nonce_field( 'etm_run_seeders' ); ?>
                <input type="hidden" name="action" value="etm_run_seeders">
                <button type="submit" class="button button-primary button-hero"
                        onclick="return confirm('Run the seeder? This will overwrite content on the 3 experience posts.');">
                    🌱 Run Seeders
                </button>
            </form>

            <?php if ( $just_ran && is_array( $log ) ) : ?>
                <h2 style="margin-top:30px;font-size:16px;">Run log</h2>
                <pre style="background:#1d2327;color:#9ad36a;padding:18px 20px;border-radius:6px;overflow:auto;max-height:520px;font-size:12px;line-height:1.55;font-family:Consolas,Monaco,monospace;white-space:pre-wrap;"><?php
                    foreach ( $log as $line ) {
                        echo esc_html( $line ) . "\n";
                    }
                ?></pre>

                <p style="margin-top:14px;">
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=experience' ) ); ?>" class="button">
                        Open Experiences
                    </a>
                    <a href="<?php echo esc_url( home_url( '/experiences/bespoke-private-tour-of-ireland/' ) ); ?>" class="button" target="_blank">
                        View Bespoke page
                    </a>
                </p>
            <?php elseif ( $just_ran ) : ?>
                <div class="notice notice-warning" style="margin-top:20px;">
                    <p>The seeder ran but no log was captured (transient may have expired). Try again — the log is stored briefly after each run.</p>
                </div>
            <?php endif; ?>

            <h2 style="margin-top:40px;font-size:16px;">What gets created</h2>
            <ul style="font-size:13px;line-height:1.7;color:#3c434a;list-style:disc;margin-left:22px;">
                <li><strong>3 experience CPT posts</strong> — published, with permalinks at <code>/experiences/&lt;slug&gt;/</code>.</li>
                <li><strong>~150 meta-field values</strong> across the three posts (hero, highlights, story, pillars, process, CTA, similar).</li>
                <li><strong>18 image attachments</strong> uploaded to the Media Library (tagged with <code>_etm_seed_source</code> so they aren't re-imported on subsequent runs).</li>
                <li><strong>Featured images, story plates, pillar cards, process visuals, CTA portrait</strong> — all wired to the right meta fields.</li>
                <li><strong>Cross-links</strong> between the three experiences via the Similar section.</li>
            </ul>
        </div>
    </div>
    <?php
}

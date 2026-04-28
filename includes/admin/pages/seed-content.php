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
    <?php
    if ( ! defined( 'ETM_SEEDER_VERSION' ) ) {
        require_once ETM_PATH . 'includes/seeders/class-experience-seeder.php';
    }
    ?>
    <?php
    // Last-run indicator: track if/when the seeder was last successfully run
    // and which version it was on. Three states:
    //   - never run  -> amber "Not yet run" notice
    //   - up to date -> green "Last run X · matches current vN" badge
    //   - outdated   -> amber "Ran at v(old). Current is v(N) — run again"
    $last_run         = get_option( 'etm_seeder_last_run', null );
    $current_version  = (int) ETM_SEEDER_VERSION;
    $run_state        = 'never';      // never | current | outdated
    $last_time        = 0;
    $last_version     = 0;
    if ( is_array( $last_run ) && ! empty( $last_run['time'] ) ) {
        $last_time    = (int) $last_run['time'];
        $last_version = (int) ( $last_run['version'] ?? 0 );
        $run_state    = ( $last_version === $current_version ) ? 'current' : 'outdated';
    }
    $time_human = $last_time
        ? sprintf( '%s ago (%s)',
            human_time_diff( $last_time, time() ),
            wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_time )
          )
        : '';
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">
            <?php echo etm_lucide( 'sprout', 22 ); ?> Seed Experience Content
            <span style="font-size:13px;font-weight:500;color:#6b7280;background:#eef2f7;padding:3px 10px;border-radius:999px;margin-left:10px;vertical-align:middle;">Seeder v<?php echo $current_version; ?></span>
        </h1>

        <?php if ( $run_state === 'current' ) : ?>
            <div style="background:#ecfdf5;border-left:4px solid #10b981;padding:12px 16px;margin:14px 0;font-size:13px;line-height:1.5;display:flex;align-items:center;gap:10px;">
                <span style="color:#047857;flex-shrink:0;"><?php echo etm_lucide( 'check-circle', 18 ); ?></span>
                <span style="color:#064e3b;"><strong>Seeder has been run.</strong> Last run <?php echo esc_html( $time_human ); ?> on Seeder v<?php echo (int) $last_version; ?> — matches the current version. Re-running will refresh content from the bundled defaults.</span>
            </div>
        <?php elseif ( $run_state === 'outdated' ) : ?>
            <div style="background:#fff7e6;border-left:4px solid #f0b849;padding:12px 16px;margin:14px 0;font-size:13px;line-height:1.5;display:flex;align-items:center;gap:10px;">
                <span style="color:#92400e;flex-shrink:0;"><?php echo etm_lucide( 'sprout', 18 ); ?></span>
                <span style="color:#78350f;"><strong>Seeder is out of date.</strong> Last run <?php echo esc_html( $time_human ); ?> on <strong>Seeder v<?php echo (int) $last_version; ?></strong>. The current version is <strong>v<?php echo $current_version; ?></strong> — run again to apply newer seed data.</span>
            </div>
        <?php else : ?>
            <div style="background:#fff7e6;border-left:4px solid #f0b849;padding:12px 16px;margin:14px 0;font-size:13px;line-height:1.5;display:flex;align-items:center;gap:10px;">
                <span style="color:#92400e;flex-shrink:0;"><?php echo etm_lucide( 'sprout', 18 ); ?></span>
                <span style="color:#78350f;"><strong>Seeder has not been run on this site yet.</strong> Click <em>Run Seeders</em> below to populate the experience pages, regions, hotels, key experiences and homepage from the bundled defaults.</span>
            </div>
        <?php endif; ?>

        <div style="max-width:780px;margin-top:14px;">

            <p style="font-size:14px;line-height:1.6;color:#3c434a;">
                Populates the site with its complete editorial content from a
                fresh environment.
            </p>
            <ul style="font-size:14px;line-height:1.7;color:#3c434a;list-style:disc;margin-left:22px;">
                <li><strong>5 experience pages</strong> — The Signature Ireland Journey (11–15 days), The Essence of Ireland (6–10 days), Bespoke Private Tour (umbrella), Trace Your Irish Heritage, Ireland's Craft Distilleries (~250 meta-field values across the five).</li>
                <li><strong>11 regions of Ireland</strong> — Dublin, Cork & Kinsale, Kerry & Dingle, South & West, Galway, Connemara, Mayo & Ashford, Sligo, Donegal, Derry & Causeway, Belfast — each with a hi-res hero, eyebrow, blurb, 3 key highlights, and a tour-product link. Rendered on the Experiences page.</li>
                <li><strong>22 key experiences</strong> — the named items the client called out: Midleton Distillery, Old Head of Kinsale, Ring of Kerry, Slea Head Drive, Foxy John's, Cliffs of Moher (via Doolin), Galway, Connemara, Ashford Castle, Giant's Causeway, Black Taxi Tour, Titanic Quarter, etc. Rendered as a featured grid below the regions on /experiences/.</li>
                <li><strong>22 hotels</strong> — Ashford / Dromoland / Ballynahinch / Lough Eske / Glenlo Abbey / Abbeyglen, Shelbourne / Merrion / Merchant / Hayfield Manor / Hawthorn / Bushmills / Europa / Westport / Derry City, Sheen Falls / Aghadoe Heights / Europe / Harvey's Point / Kinsale curated / Fishing Lodges / Private Estates. Grouped by Castle / Boutique / Coastal & Scenic. Image IDs left at 0 — client to provide hotel exteriors in a follow-up phase.</li>
                <li><strong>0 sample itineraries</strong> (et_itineraries cleared) — the legacy Itineraries admin and front-end sections were removed. The renamed Sample Itineraries CPT (Signature + Essence) is now the source of truth.</li>
                <li><strong>31 image attachments</strong> — uploaded from the bundled <code>seed-data/images/</code> folder into the Media Library and wired to the right meta fields. Includes hi-res hero shots (Gap of Dunloe, Kylemore Abbey, coastal road fog, whiskey casks warehouse, copper still, Irish Whiskey Museum) plus 4 region heroes (Dublin, Galway, Sligo, Belfast — bundled for upcoming regional pages) and the 18 original story/pillar/process images.</li>
                <li><strong>Homepage settings</strong> — hero, intro, offer blocks, process steps, testimonials, founder CTA and section visibility.</li>
                <li><strong>Homepage images</strong> — full-bleed hero photo, intro section image, Bespoke + Golf offer images, founder portrait. All wired into <em>Homepage</em> screen automatically.</li>
                <li><strong>Experience cards array</strong> — the 3 cards used on the homepage Experiences grid.</li>
                <li><strong>Experience filters</strong> — type and duration taxonomies (Bespoke / Photography / Culinary / Golf, etc.).</li>
            </ul>

            <p style="font-size:14px;line-height:1.6;color:#3c434a;">
                Use this on a fresh environment (live, staging, a new local clone)
                to bring everything to the same state as the primary development
                site. The seeder is <strong>idempotent</strong> — running it twice
                will not create duplicates; it updates existing posts and reuses
                already-imported images.
            </p>

            <div style="background:#fff7e6;border-left:4px solid #f0b849;padding:12px 16px;margin:18px 0;font-size:13px;line-height:1.5;">
                <strong>Heads up — this overwrites:</strong>
                Running the seeder will overwrite any manual edits made on this
                site to: the 3 experience posts' meta fields, the
                <em>Homepage</em> screen settings, the <em>Experiences</em> cards
                array, and the type/duration taxonomies. Site Settings (logo,
                phone, address, social URLs), Hotels, Itineraries, and any other
                pages or posts are untouched.
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:22px 0;">
                <?php wp_nonce_field( 'etm_run_seeders' ); ?>
                <input type="hidden" name="action" value="etm_run_seeders">
                <button type="submit" class="button button-primary button-hero"
                        onclick="return confirm('Run the seeder? This will overwrite content on the 2 tour-product posts (Signature Journey, Essence Experience) and refresh homepage / region / hotel data.');">
                    <?php echo etm_lucide( 'sprout', 16 ); ?> Run Seeders
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
                    <a href="<?php echo esc_url( home_url( '/experiences/signature-ireland-journey/' ) ); ?>" class="button" target="_blank">
                        View Signature Journey
                    </a>
                    <a href="<?php echo esc_url( home_url( '/experiences/essence-of-ireland/' ) ); ?>" class="button" target="_blank">
                        View Essence Experience
                    </a>
                </p>
            <?php elseif ( $just_ran ) : ?>
                <div class="notice notice-warning" style="margin-top:20px;">
                    <p>The seeder ran but no log was captured (transient may have expired). Try again — the log is stored briefly after each run.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php
}

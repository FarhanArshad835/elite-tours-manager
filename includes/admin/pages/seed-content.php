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
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">
            <?php echo etm_lucide( 'sprout', 22 ); ?> Seed Experience Content
            <span style="font-size:13px;font-weight:500;color:#6b7280;background:#eef2f7;padding:3px 10px;border-radius:999px;margin-left:10px;vertical-align:middle;">Seeder v<?php echo (int) ETM_SEEDER_VERSION; ?></span>
        </h1>

        <div style="max-width:780px;margin-top:14px;">

            <p style="font-size:14px;line-height:1.6;color:#3c434a;">
                Populates the site with its complete editorial content from a
                fresh environment.
            </p>
            <ul style="font-size:14px;line-height:1.7;color:#3c434a;list-style:disc;margin-left:22px;">
                <li><strong>5 experience pages</strong> — The Signature Ireland Journey (11–15 days), The Essence of Ireland (6–10 days), Bespoke Private Tour (umbrella), Trace Your Irish Heritage, Ireland's Craft Distilleries (~250 meta-field values across the five).</li>
                <li><strong>11 regions of Ireland</strong> — Dublin, Cork & Kinsale, Kerry & Dingle, South & West, Galway, Connemara, Mayo & Ashford, Sligo, Donegal, Derry & Causeway, Belfast — each with a hi-res hero, eyebrow, blurb, 3 key highlights, and a tour-product link. Rendered on the Experiences page.</li>
                <li><strong>22 hotels</strong> — Ashford / Dromoland / Ballynahinch / Lough Eske / Glenlo Abbey / Abbeyglen, Shelbourne / Merrion / Merchant / Hayfield Manor / Hawthorn / Bushmills / Europa / Westport / Derry City, Sheen Falls / Aghadoe Heights / Europe / Harvey's Point / Kinsale curated / Fishing Lodges / Private Estates. Grouped by Castle / Boutique / Coastal & Scenic. Image IDs left at 0 — client to provide hotel exteriors in a follow-up phase.</li>
                <li><strong>6 sample itineraries</strong> — 3 bespoke (Classic Signature 13-day, Essence South & West 8-day, Heritage Trace 5–8-day) and 3 golf (Wild Atlantic Links 8-day, Royal Tour 10-day, Connoisseur's Week 6-day). Region-level summaries, not day-by-day. Render on /bespoke-tours/ and /golf-tours/.</li>
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

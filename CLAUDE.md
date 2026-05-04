# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository scope

This repo tracks **only** the custom theme at `wp-content/themes/tipni/` (TipniJinak). WordPress core, other plugins, `wp-config.php`, and `wp-content/uploads/` are intentionally `.gitignore`d. The site is the Czech sports-tipping platform "Tipni Jinak" / helpyourteam.cz.

## Build, run, test

There is **no build pipeline** — no `package.json`, no `composer.json`, no bundler. PHP, CSS, and JS files in the theme are served as-is by WordPress. Editing a file is the build.

Cache busting in [wp-content/themes/tipni/functions.php:13](wp-content/themes/tipni/functions.php#L13) uses `define('TIPNIJINAK_VERSION', time())` — this re-versions every asset on every request. Fine for development but should be pinned to a static version string before any production-style deploy.

Runtime requirements (from [wp-content/themes/tipni/README.md](wp-content/themes/tipni/README.md)):
- WordPress 5.8+, PHP 7.4+
- **ACF PRO** plugin (required — almost every template reads `get_field(...)`)
- **WooCommerce** (cart/checkout integration; theme calls `WC()` and `wc_get_cart_url()` directly in [header.php](wp-content/themes/tipni/header.php))

There is no automated test suite. Verification is manual: load the affected page in a WordPress install with the theme activated and matching ACF/WooCommerce setup.

## Architecture

### Domain model (custom post types + taxonomies)

Registered in [functions.php:141-329](wp-content/themes/tipni/functions.php#L141-L329):

- **`soutez`** (competition) — top-level betting contest, has a `je_hlavni` flag for "main competition"
- **`kolo`** (round) — a betting round; each round has matches and a state (`planovano`/`otevreno`/`probihajici`/`uzavreno`)
- **`zapas`** (match) — single match with home/away team, score, status, odds
- **`tym`** (team) — team metadata + logo
- **`user_tip`** — legacy CPT, mostly superseded by the custom DB tables (see below)

Taxonomies: **`liga`** (applies to `zapas` + `tym`), **`typ-souteze`** (applies to `soutez`).

Czech naming conventions are intentional — labels, slugs, and field keys are Czech. Don't translate them when refactoring; they are referenced by URL slug and ACF field key.

### ACF fields

Field groups live as JSON in [wp-content/themes/tipni/acf-json/](wp-content/themes/tipni/acf-json/) (e.g. `group_soutez.json`, `group_zapas.json`, `group_kurzove_hladiny.json`). ACF auto-syncs these on the admin side via the load/save points registered in [functions.php:1207-1222](wp-content/themes/tipni/functions.php#L1207-L1222). Edit the JSON or edit fields in the WP admin and ACF will rewrite the JSON — both flows work.

Key non-obvious fields:
- `kola_souteze` on `soutez` and `souteze_kola` on `kolo` form a **bilateral relationship** that is auto-synced on save by `tipnijinak_sync_soutez_kola_relationship` hooked to `acf/save_post` ([functions.php:2786-2920](wp-content/themes/tipni/functions.php#L2786-L2920)). When you change one side it rewrites the other; do not "fix" this by removing one direction.
- `kurzove_hladiny` (odds tiers) is a global ACF options-page repeater. Points awarded per correct tip are computed from match odds via `tipnijinak_get_points_by_odds()` ([functions.php:2224](wp-content/themes/tipni/functions.php#L2224)) — wrong tips subtract the same amount.

### Tips and points: custom DB tables

Two non-WP tables, created in [functions.php:1285-1322](wp-content/themes/tipni/functions.php#L1285-L1322) via `register_activation_hook`:

- `{prefix}_tipnijinak_tips` — one row per (user_id, match_id), stores the tip char (`1`/`X`/`2`)
- `{prefix}_tipnijinak_points` — points awarded after match evaluation

If the tables are missing on an existing site, `tipnijinak_create_tables_manually()` ([functions.php:2926](wp-content/themes/tipni/functions.php#L2926)) plus the admin "DB Manager" page and an AJAX endpoint (`wp_ajax_tipnijinak_create_tables`) can recreate them without re-activating the theme.

The constant `TIPNIJINAK_USE_DB_TIPS` at [functions.php:2216](wp-content/themes/tipni/functions.php#L2216) is a kept-for-compat switch — tips are read/written through these tables by default.

### Single-competition template flow

The single competition page is the primary frontend surface and uses a fan-out pattern:

1. [single-soutez.php](wp-content/themes/tipni/single-soutez.php) calls `tipnijinak_prepare_competition_data()` ([inc/template-functions.php:18](wp-content/themes/tipni/inc/template-functions.php#L18)) once.
2. That helper resolves the competition ID via `get_queried_object()` (NOT `get_the_ID()` — there's a comment explaining the `?kolo=` query var collides with the `kolo` CPT and corrupts `get_the_ID()`), then assembles rounds, current round, leaderboard, user points/ranking, access flags, etc.
3. The resulting array is `extract()`-ed and passed to four tab partials in [template-parts/competition/](wp-content/themes/tipni/template-parts/competition/): `prizes-tab`, `betting-tab`, `results-tab`, `ranking-tab`.

If you add a new piece of competition state, add it to `tipnijinak_prepare_competition_data()` rather than re-querying inside a tab partial.

### AJAX surface

Almost all dynamic interactions go through `admin-ajax.php`. Handlers live in [functions.php](wp-content/themes/tipni/functions.php) and use the nonce action `tipnijinak_tips_nonce` (or feature-specific nonces for registration/login). Notable endpoints:

- Auth/profile: `tipnijinak_ajax_login`, `process_registration`, `update_user_profile`, `update_user_club`, `search_clubs`, `create_new_club`
- Tipping: `tipnijinak_save_tips`, `tipnijinak_get_round_content`, `tipnijinak_get_points_by_odds`
- WooCommerce bridge: `create_woo_order` (creates an order from registration data)

Most pair an authed and `_nopriv_` action because parts of the registration/checkout flow run for guests.

### WooCommerce integration

Lives in [inc/shop/](wp-content/themes/tipni/inc/shop/):
- `shop-functions.php` — general shop helpers
- `woocommerce-hooks.php` — wrapper unhooking, billing-phone-required, selective WC asset dequeue
- `class-tipnijinak-checkout-blocks.php` — block-checkout extension

The theme overrides `archive-product.php` at the theme root **and** at `woocommerce/archive-product.php` — they're identical copies; WooCommerce prefers the `woocommerce/` location.

### Admin tooling

Loaded only when `is_admin()` ([functions.php:23-28](wp-content/themes/tipni/functions.php#L23-L28)):

- [inc/admin/class-match-import.php](wp-content/themes/tipni/inc/admin/class-match-import.php) — CSV import for matches and results, with batched AJAX processing (`Tipnijinak_Match_Import` class, ~83KB — the bulk of admin logic)
- [inc/admin/match-auto-evaluate.php](wp-content/themes/tipni/inc/admin/match-auto-evaluate.php) — auto-evaluate user tips when a match status flips to `ukonceny`
- [inc/admin/kolo-validation.php](wp-content/themes/tipni/inc/admin/kolo-validation.php) — validation rules for `kolo` posts
- [inc/admin/admin-columns.php](wp-content/themes/tipni/inc/admin/admin-columns.php) — extra columns in the post list tables

Match evaluation flow: `tipnijinak_evaluate_match($match_id)` ([functions.php:1621](wp-content/themes/tipni/functions.php#L1621)) — reads tips for that match, compares to the final result (`1`/`X`/`2`), looks up points via the odds tiers, writes to the points table. Triggered automatically by the match-auto-evaluate hook or manually from the admin "Vyhodnocení tipů" page.

## Conventions

- **Function prefix**: all theme functions use `tipnijinak_` — keep this when adding new ones to avoid collisions with plugins.
- **Czech in user-facing strings, English in code identifiers** — except domain CPT/field slugs which stay Czech (`soutez`, `kolo`, `zapas`, `tym`, `liga`, `je_hlavni`, `kurzove_hladiny`, …).
- **Asset enqueuing is conditional**: scripts/styles for login, profile, single-soutez, registration are enqueued only on their respective pages (`is_page('prihlaseni')`, `is_singular('soutez')`, etc.) — see [functions.php:48-81](wp-content/themes/tipni/functions.php#L48-L81). Add new feature scripts the same way rather than loading them globally.
- **No comments-only "removed code" markers**: there are several blocks of commented-out debug code in the templates (e.g. the admin debug overlays in [single-soutez.php](wp-content/themes/tipni/single-soutez.php) and [inc/template-functions.php:33-44](wp-content/themes/tipni/inc/template-functions.php#L33-L44)). They're intentional debug toggles, not dead code — leave them unless explicitly cleaning up.

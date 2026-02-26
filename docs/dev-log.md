# HK Card Compare — Development Log

> **Purpose:** Track all development work across sessions. Written for both human developers and AI agents to quickly understand project history, current state, and what to do next.
>
> **How to use this log:**
> - Each session gets a dated entry under [Session Log](#session-log).
> - The [Current State](#current-state) section is always kept up to date.
> - The [Architecture Quick Reference](#architecture-quick-reference) section gives any new agent instant context.
> - When adding a new entry, update both the session log AND the current state section.

---

## Table of Contents

1. [Current State](#current-state)
2. [Architecture Quick Reference](#architecture-quick-reference)
3. [Session Log](#session-log)
   - [2026-02-10 — Session 1: Initial Plugin Build](#2026-02-10--session-1-initial-plugin-build)
   - [2026-02-10 — Session 2: Frontend zh-HK, Layout & Programme List Updates](#2026-02-10--session-2-frontend-zh-hk-layout--programme-list-updates)
   - [2026-02-11 — Session 3: UX/UI Psychology-Driven Redesign](#2026-02-11--session-3-uxui-psychology-driven-redesign)
   - [2026-02-25 — Session 4: Shortcode Fixes, Name Standardisation, Airline/Hotel Filters](#2026-02-25--session-4-shortcode-fixes-name-standardisation-airlinehotel-filters)
   - [2026-02-26 — Session 5: Thematic Filters, Tie-Breaker Sort, Points System Fix](#2026-02-26--session-5-thematic-filters-tie-breaker-sort-points-system-fix)
4. [Known Issues & Technical Debt](#known-issues--technical-debt)
5. [What's Next](#whats-next)

---

## Current State

| Item                  | Status                                                  |
|-----------------------|---------------------------------------------------------|
| **Branch**            | `claude/code-plugin-E74dL`                              |
| **Plugin Version**    | 1.0.11                                                  |
| **Phase**             | Phase 1 complete. Phase 2 in progress (UX & filtering). |
| **Files**             | 18 PHP/CSS/JS files in `hk-card-compare/`               |
| **Database Tables**   | 3 custom tables (created on activation)                 |
| **Shortcodes**        | `[cc_comparison]`, `[cc_suggest]`, `[cc_card]` — all functional |
| **Admin Pages**       | Card edit (8 tabs), Points Systems, Analytics            |
| **Frontend**          | Mobile-first CSS, AJAX filters, 15 filter chips, click tracking |
| **Tests**             | None yet                                                |
| **Production Deploy** | Live on flyasia.co                                      |

---

## Architecture Quick Reference

### File Structure

```
hk-card-compare/
├── hk-card-compare.php              ← Main entry point, activation hooks, DB table creation
├── includes/
│   ├── class-card-post-type.php     ← CPT 'card' registration
│   ├── class-card-taxonomy.php      ← card_bank, card_network taxonomies
│   ├── class-card-meta.php          ← All meta field definitions + save handler
│   ├── class-points-system.php      ← Points CRUD, conversion rates, auto-calculation
│   ├── class-click-tracker.php      ← AJAX click tracking + analytics queries
│   └── class-schema-output.php      ← JSON-LD schema + meta descriptions
├── admin/
│   ├── class-card-admin.php         ← Tabbed edit screen (6 tabs), eligibility sidebar
│   ├── class-points-admin.php       ← Points Systems CRUD page
│   ├── class-analytics-admin.php    ← Click analytics dashboard
│   ├── css/admin.css
│   └── js/admin.js
├── public/
│   ├── class-card-shortcodes.php    ← [cc_suggest] + [cc_comparison] + AJAX filter handler
│   ├── class-card-display.php       ← All card rendering (suggest, listing, expanded)
│   ├── css/public.css               ← Mobile-first responsive styles
│   └── js/public.js                 ← Click tracking, filters, toggle, expand/collapse
└── templates/
    ├── single-card.php
    ├── card-listing-item.php
    └── card-comparison.php
```

### Database Tables

| Table                            | Purpose                                     |
|----------------------------------|---------------------------------------------|
| `{prefix}_card_points_systems`   | Points system definitions (e.g. AE MR, Citi ThankYou) |
| `{prefix}_card_points_conversion`| Conversion rates per system (cash, miles, hotels)      |
| `{prefix}_card_clicks`           | Affiliate link click tracking                          |

### Key Classes

| Class                    | File                          | Responsibility                          |
|--------------------------|-------------------------------|-----------------------------------------|
| `HKCC_Card_Post_Type`   | includes/class-card-post-type.php | CPT registration                    |
| `HKCC_Card_Taxonomy`    | includes/class-card-taxonomy.php  | Taxonomy registration               |
| `HKCC_Card_Meta`        | includes/class-card-meta.php      | Field definitions, save logic        |
| `HKCC_Points_System`    | includes/class-points-system.php  | Points CRUD + auto-calculation       |
| `HKCC_Click_Tracker`    | includes/class-click-tracker.php  | Click recording + analytics queries  |
| `HKCC_Schema_Output`    | includes/class-schema-output.php  | JSON-LD + meta description output    |
| `HKCC_Card_Admin`       | admin/class-card-admin.php        | Tabbed edit UI + expiry warnings     |
| `HKCC_Points_Admin`     | admin/class-points-admin.php      | Points Systems admin page            |
| `HKCC_Analytics_Admin`  | admin/class-analytics-admin.php   | Analytics dashboard                  |
| `HKCC_Card_Shortcodes`  | public/class-card-shortcodes.php  | Shortcode rendering + AJAX handler   |
| `HKCC_Card_Display`     | public/class-card-display.php     | Card HTML rendering helpers          |

### Key Design Decisions

1. **Dual-field pattern** — Every sortable attribute has a `_display` (human-readable string) and `_sortable` (numeric) field. Display fields are shown to users; sortable fields are used for ordering and filtering.
2. **Points auto-calculation** — When a card uses a points system, saving the card auto-generates `_cash_sortable`, `_miles_display`, and other reward-type display values from the conversion table.
3. **Mobile-first** — CSS is written mobile-first with a single `@media (min-width: 768px)` breakpoint for desktop adaptation.
4. **AJAX filtering** — Filter state is collected client-side and sent to `wp_ajax_hkcc_filter_cards`. The server returns rendered HTML which replaces the card list.
5. **sendBeacon for tracking** — Click tracking uses `navigator.sendBeacon` for reliability (doesn't block link navigation).

---

## Session Log

### 2026-02-10 — Session 1: Initial Plugin Build

**Branch:** `claude/code-plugin-E74dL`
**Commit:** `8443ce3` — `feat: Implement HK Card Compare WordPress plugin`

**What was done:**

Built the entire plugin from scratch based on the README.md specification. This covers Phase 1 of the development plan (core functionality).

**Files created (18 total):**

- `hk-card-compare.php` — Main plugin file with activation hook that creates 3 DB tables via `dbDelta`
- `includes/class-card-post-type.php` — Registers `card` CPT with Traditional Chinese labels, 600x380 image size
- `includes/class-card-taxonomy.php` — Registers `card_bank` (發卡銀行) and `card_network` (結算機構) taxonomies
- `includes/class-card-meta.php` — Defines all meta fields across 7 groups (basic, fees, rewards, welcome, benefits, featured, eligibility), handles sanitisation and save
- `includes/class-points-system.php` — Full CRUD for points systems table, conversion rate management, auto-calculation engine (points → cash %, miles/dollar, hotel points)
- `includes/class-click-tracker.php` — AJAX handler for click recording, query methods for analytics (top cards, top sources, recent clicks)
- `includes/class-schema-output.php` — FinancialProduct JSON-LD on single card pages, auto-generated meta descriptions
- `admin/class-card-admin.php` — Tabbed meta box (Basic Info, Fees, Rewards, Welcome Offer, Benefits, Featured), eligibility sidebar meta box, welcome offer expiry warnings
- `admin/class-points-admin.php` — Points Systems submenu page with add/edit/delete, dynamic conversion rate rows
- `admin/class-analytics-admin.php` — Analytics submenu with date range filters, source URL filtering
- `admin/css/admin.css` — Tab navigation, field layouts, conversion table styles
- `admin/js/admin.js` — Tab switching, cash/points toggle, dynamic add/remove conversion rows
- `public/class-card-shortcodes.php` — `[cc_comparison]` and `[cc_suggest]` shortcode handlers, AJAX filter endpoint, filter rendering
- `public/class-card-display.php` — Asset enqueuing, `render_suggest_card()`, `render_listing_card()`, expanded details, featured parameter resolution with miles/cash view switching
- `public/css/public.css` — Mobile-first responsive styles, filter accordion, card layout, 2-col desktop grid
- `public/js/public.js` — Click tracking (sendBeacon), filter toggle, clear all, AJAX filtering with 300ms debounce, expand/collapse
- `templates/single-card.php`, `card-listing-item.php`, `card-comparison.php` — Overridable templates

**Also created:**

- `docs/shortcode-guide.md` — Comprehensive shortcode documentation with 20+ ready-to-use examples
- `docs/dev-log.md` — This file

**Decisions made:**

- Used static class methods with `::init()` pattern (no singleton) for simplicity
- Used `wp_cache_get/set` in spec but deferred implementation of the caching wrapper — raw `get_posts()` is used currently
- The AJAX filter handler returns pre-rendered HTML (not JSON data) to keep client JS simple
- Templates are provided but the shortcodes work standalone without theme template overrides
- PublishPress integration is via the `publishpress_revisions_post_types` filter hook

### 2026-02-10 — Session 2: Frontend zh-HK, Layout & Programme List Updates

**Branch:** `claude/code-plugin-E74dL`
**Commit:** `5da92fb` — `fix: Frontend zh-HK only, single-column layout, 不適用 for zero rebates, full airline/hotel list`

**What was done:**

Four changes based on user feedback:

**1. Frontend language → zh-HK only (admin stays English)**

All user-facing frontend text changed to Traditional Chinese:

| Before (English)     | After (zh-HK)       | Location                     |
|---------------------|---------------------|------------------------------|
| Apply Now →         | 立即申請 →           | All card buttons              |
| View Details ▼      | 查看詳情 ▼           | Collapsed card button         |
| Hide Details ▲      | 收起詳情 ▲           | Expanded card button (JS)     |
| Filters             | 篩選條件             | Filter header                 |
| (X active)          | (X 個篩選)           | Active filter count (JS)      |
| Clear All           | 清除所有篩選          | Clear filters button          |
| Show rebates as:    | 顯示回贈方式：        | Toggle label                  |
| Miles / Cash        | 飛行里數 / 現金回贈    | Toggle options                |
| Showing X cards     | 共 X 張信用卡         | Card count                    |
| N/A                 | 不適用               | Empty featured param value    |

**2. Desktop layout → single column (n rows × 1 col)**

- Removed 2-column card grid and sidebar filter layout
- Both `[cc_comparison]` and `[cc_suggest]` now render cards in a single column stack
- Desktop: max-width 800px, centered, filters row at top (flex-wrap inline)
- Mobile: unchanged (already single column)

**3. Zero-rebate items → 不適用**

- `class-points-system.php`: Auto-calculation now stores `不適用` when earning rate = 0
- `class-card-display.php`: `get_reward_display()` checks for 0-rate and returns `不適用`
- Featured params fallback changed from `N/A` to `不適用`
- Prevents weird display like "網上繳費: HK$1 = 0 points"

**4. Complete airline/hotel programme list**

Based on [FlyAsia article](https://www.flyasia.co/2025/credit-card-reward-system/), expanded from 3 airlines + 2 hotels to:

**Airlines (15):** Asia Miles, Avios, Emirates Skywards, Etihad Guest, Flying Blue, KrisFlyer, Qantas FF, Virgin Atlantic FC, Finnair Plus, Enrich, Infinity MileageLands, Royal Orchid Plus, Qatar Privilege Club, 鳳凰知音, Aeroplan

**Hotels (3):** Marriott Bonvoy, Hilton Honors, IHG Rewards

Updated in: `class-card-admin.php` (checkboxes), `class-points-admin.php` (reward type dropdown), `admin/js/admin.js` (dynamic row JS)

**Files modified (8):**

- `public/class-card-display.php` — zh-HK buttons, 不適用 fallback logic
- `public/class-card-shortcodes.php` — zh-HK filter labels, toggle, card count
- `public/css/public.css` — Single column desktop layout
- `public/js/public.js` — zh-HK button text in expand/collapse
- `admin/class-card-admin.php` — Full airline (15) + hotel (3) checkbox lists
- `admin/class-points-admin.php` — Expanded reward type dropdown (19 options)
- `admin/js/admin.js` — Matching reward type options for dynamic rows
- `includes/class-points-system.php` — 不適用 handling for 0-rate auto-calculation

### 2026-02-11 — Session 3: UX/UI Psychology-Driven Redesign

**Branch:** `claude/code-plugin-E74dL`
**Commit:** (this commit)

**What was done:**

Psychology-driven UX/UI overhaul: users want to see perks over fees. The entire card display hierarchy was restructured to emphasise rewards and de-emphasise costs.

**1. Expanded details reordered (perks first, fees last)**

| Before                                        | After                                          |
|-----------------------------------------------|------------------------------------------------|
| Bank/Network → **Fees** → Rewards → Welcome → Benefits | Bank/Network → **Rewards** → Welcome → Benefits → **Fees** |

Each section now has a distinct CSS class (`hkcc-section-rewards`, `hkcc-section-welcome`, `hkcc-section-benefits`, `hkcc-section-fees`) enabling targeted visual treatment.

**2. Visual hierarchy via colour-coded sections**

| Section     | Background       | Accent Colour  | Typography       |
|-------------|-----------------|----------------|------------------|
| 回贈 (Rewards)  | Teal tint `#e0f2f1` | `#00796b`    | Bold values, 15px |
| 迎新優惠 (Welcome) | Purple tint `#f3e5f5` | `#6a1b9a` | Left border accent |
| 福利 (Benefits)   | Blue tint `#e3f2fd`  | `#1565c0`    | Checkmark icon    |
| 費用 (Fees)       | Grey `#fafafa`       | `#888`       | Muted, 13px, 0.85 opacity |

**3. Badges on collapsed cards**

- `免年費` (green pill badge) — shown when `annual_fee_sortable ≤ 0`
- `迎新` (purple pill badge) — shown when welcome offer exists

**4. Welcome offer preview on collapsed cards**

A compact purple-tinted preview bar shows the first ~57 chars of the welcome offer description, letting users spot welcome offers without expanding.

**5. CTA button redesign**

- Changed from blue `.hkcc-btn-primary` to orange `.hkcc-btn-cta` (`#e65100`)
- Added box-shadow glow, hover lift effect, larger font (15px bold)
- Higher contrast against the card background = draws the eye

**6. Premium card image treatment**

- Card images now have `box-shadow: 0 4px 16px rgba(0,0,0,0.12)` for depth
- Subtle `scale(1.02)` on card hover for tactile feedback

**7. Desktop layout: horizontal card grid**

On desktop (≥768px), collapsed cards now use CSS Grid with image on the left (200px column) and text/actions on the right, making better use of horizontal space.

**8. CSS variables system**

Introduced `:root` CSS variables for consistent theming: `--hkcc-accent`, `--hkcc-cta`, `--hkcc-reward`, `--hkcc-welcome`, `--hkcc-benefit`, `--hkcc-fee-text`, `--hkcc-shadow`, etc.

**Files modified (2):**

- `public/class-card-display.php` — Reordered `render_expanded_details()`, added badges, welcome preview, icon spans, CTA class change
- `public/css/public.css` — Complete overhaul: CSS variables, colour-coded sections, badge styles, CTA button, card shadows, desktop grid layout

### 2026-02-25 — Session 4: Shortcode Fixes, Name Standardisation, Airline/Hotel Filters

**Branch:** `claude/code-plugin-E74dL`
**Version:** 1.0.5 → 1.0.6

**What was done:**

Seven changes across frontend, backend, and shortcode systems.

**1. Button styling override for Flatsome theme**

Flatsome theme applies aggressive CSS to buttons inside `.entry-content`. Simple `!important` is not enough — theme uses equally specific selectors. Solution evolved through multiple iterations:
- Added `all: unset !important` to `.hkcc-btn` to reset all inherited properties
- Scoped selectors to `.hkcc-comparison`, `.hkcc-spotlight`, `.hkcc-single-card` wrappers
- Target both `a.hkcc-btn` and `button.hkcc-btn` plus `:link`/`:visited` states

**2. Moved points system section after 福利 (benefits)**

In `render_expanded_details()`, swapped section order so the points system info (積分系統, transferable airlines/hotels) appears after benefits instead of before.

**3. Standardised airline/hotel program names**

Changed naming convention from English-first (e.g., "Asia Miles (亞洲萬里通)") to Chinese-first (e.g., "國泰航空 Asia Miles"). Updated all 17 airlines + 4 hotels in both admin and frontend. Added migration maps for backward compatibility with old saved values.

**Airlines (17):** 國泰航空 Asia Miles, 英國航空 BA Avios, 芬蘭航空 Finnair Plus, 馬來西亞航空 Enrich, 澳洲航空 Qantas FF, 新加坡航空 KrisFlyer, 長榮航空 無限萬哩遊, 阿聯酋航空 Skywards, 維珍航空 Virgin Flying Club, 阿堤哈德航空 Etihad Guest Miles, KLM Flying Blue, 卡塔爾航空 Qatar Privilege Club, 泰國航空 Royal Orchid Plus, 中國國航 鳳凰知音, 加拿大航空 Aeroplan, 蓮花航空 Lotusmiles, 土耳其航空 Miles & Smiles

**Hotels (4):** 萬豪 Marriott Bonvoy, 希爾頓 Hilton Honors, 洲際 IHG Reward Club, 雅高 Accor ALL

**4. Fixed multiline shortcode parsing**

WordPress shortcode regex fails on multiline attributes. Added `the_content` filter at priority 5 to collapse newlines within `[cc_suggest]`, `[cc_comparison]`, `[cc_card]` tags before WordPress parses them.

**5. Added `[cc_suggest metric="..."]` with zero-value filtering**

Added `get_metric_map()` to resolve metric names to meta keys:
- `cashback_local` → `local_retail_cash_sortable` (DESC)
- `cashback_overseas` → `overseas_retail_cash_sortable` (DESC)
- `asia_miles_local` → `local_retail_miles_sortable` (ASC)
- `lounge_access` → `lounge_access_sortable` (DESC)
- `annual_fee_low` → `annual_fee_sortable` (ASC)

Metric-based queries automatically filter out cards with 0 or missing values.

**6. Default recommendation sort for `[cc_suggest]`**

When no sort/metric specified, `[cc_suggest]` now uses `recommendation_sort()`: affiliate-link cards first → overseas miles ASC → pure-cash by overseas % DESC.

**7. Added `airline` and `hotel` parameters to shortcodes**

Both `[cc_suggest]` and `[cc_comparison]` now accept `airline="..."` and `hotel="..."` attributes. Uses `LIKE` meta_query on serialized `transferable_airlines`/`transferable_hotels` arrays. Comma-separated values use AND logic.

**8. New `[cc_card]` spotlight shortcode**

Added `[cc_card slug="..." view="miles"]` shortcode for embedding a single card inline within blog posts. Shows tagline, card face, name, short welcome, 4-pack, apply button, long welcome bubble, blog/apply buttons, footnotes.

**9. Updated shortcode guide**

Rewrote `docs/shortcode-guide.md` with full documentation for all three shortcodes, airline/hotel parameters, metric filtering, and ready-to-use examples.

**Files modified (9):**

- `public/css/public.css` — Button override styles, spotlight padding
- `public/class-card-display.php` — Section reorder, migration maps
- `public/class-card-shortcodes.php` — `[cc_card]`, airline/hotel params, metric map, recommendation sort, multiline fix
- `admin/class-card-admin.php` — Standardised airline/hotel checkboxes + migration map
- `admin/class-points-admin.php` — Added Lotusmiles, Miles & Smiles, Accor to reward dropdown
- `hk-card-compare.php` — Version bump
- `docs/shortcode-guide.md` — Full rewrite

---

### 2026-02-26 — Session 5: Thematic Filters, Tie-Breaker Sort, Points System Fix

**Branch:** `claude/code-plugin-E74dL`
**Version:** 1.0.6 → 1.0.11

**What was done:**

Major additions: thematic filter chips, sort tie-breaker logic, points system bug fix, and frontend cleanup.

**1. Button styling — triple-class specificity hack (v1.0.7)**

Previous wrapper-based selectors still failed on published pages (worked on preview). Root cause: Flatsome theme CSS loads AFTER plugin CSS; at equal specificity + `!important`, the last-loaded wins.

Final solution: triple-class specificity `.hkcc-btn.hkcc-btn.hkcc-btn` gives specificity 0-3-0, beating theme selectors (0-1-1 to 0-2-1). Also removed old conflicting `.hkcc-card-actions .hkcc-btn { flex: 1 }` rule. After `all: unset`, flex properties must be explicitly restored with `flex: 1 1 0% !important`.

**2. Desktop toolbar rearrangement (v1.0.7)**

Changed `.hkcc-toolbar-body` from `display: flex` to `display: block` on desktop for cleaner stacked layout. Chip sections got category headings.

**3. Nine new thematic filter chips (v1.0.7)**

Added to `[cc_comparison]` toolbar, organised into two sections:

**卡片特色 (Card Features):**

| Chip | Value | Meta Query |
|------|-------|------------|
| 永久免年費 | `free_annual_fee` | `annual_fee_sortable = 0` |
| 可用貴賓室 | `has_lounge` | `lounge_access_sortable >= 1` |
| 免費旅遊保險 | `has_travel_insurance` | `has_travel_insurance = 1` |
| 純現金回贈卡 | `cashback_only` | `points_system_id = 0 OR '' OR NOT EXISTS` |
| 食飯卡 | `good_dining` | `local_dining_cash_sortable > 0 OR local_dining_points != ''` |
| 超市買餸卡 | `good_supermarket` | `designated_supermarket_cash_sortable > 0 OR designated_supermarket_points != ''` |

**里程 / 酒店計劃 (Programs):**

| Chip | Value | Meta Query |
|------|-------|------------|
| Asia Miles | `has_asia_miles` | `transferable_airlines LIKE 'Asia Miles'` |
| Avios 系列 | `has_avios` | `transferable_airlines LIKE 'Avios' OR 'Qatar' OR 'Finnair'` |
| Virgin Atlantic | `has_virgin` | `transferable_airlines LIKE 'Virgin'` |
| KrisFlyer | `has_krisflyer` | `transferable_airlines LIKE 'KrisFlyer'` |
| Marriott Bonvoy | `has_marriott` | `transferable_hotels LIKE 'Marriott'` |
| Hilton Honors | `has_hilton` | `transferable_hotels LIKE 'Hilton'` |

No JS changes needed — existing `.hkcc-filter-chip:checked` collection handles new chips automatically.

**4. `[cc_suggest]` affiliate-only (v1.0.7)**

Added `meta_query` to `shortcode_suggest()` requiring `affiliate_link != ''`. Cards without an application link are no longer shown in `[cc_suggest]` output.

**5. Sort tie-breaker logic (v1.0.7)**

New `tie_breaker_sort()` function. When sorting by a specific field (e.g., annual fee), cards with equal values now use recommendation logic as tie-breaker (affiliate first → overseas miles ASC → cash DESC) instead of falling back to publish date.

Applied in all three sort locations: `shortcode_suggest()`, `shortcode_comparison()`, and `ajax_filter_cards()`.

**6. Points system fix — dynamic miles reward type (v1.0.7)**

**Root cause:** Both `auto_calculate_rebates()` and `live_calc_display()` hardcoded `$vpp['asia_miles']` for the miles conversion rate. Systems using different reward types (e.g., `'avios'` for 大新英航) would get 0 → display "不適用".

**Fix:** Both functions now dynamically find the first non-`cash` reward type from the conversions table. Works for any airline program. Cards using affected systems need to be re-saved to regenerate `_miles_display` and `_miles_sortable` meta values.

**Files modified:**
- `includes/class-points-system.php` — `auto_calculate_rebates()` dynamic miles type
- `public/class-card-display.php` — `live_calc_display()` dynamic miles type

**7. Removed bank/network filter UI from frontend (v1.0.11)**

Bank (發卡機構) and network (結算機構) filter chips were removed from the frontend toolbar. The Flatsome theme's aggressive button/element styling made the collapsible toggle unworkable despite multiple approaches (CSS `!important`, inline styles, `all: unset`, `<button>` → `<div>` conversion).

Bank/network filtering still works via shortcode attributes (`bank="hsbc"`, `network="visa"`) and the AJAX handler. The `render_filters()` PHP method is retained for potential future use.

**Removed:** All `.hkcc-filter-groups-*` CSS rules, HTML section in `shortcode_comparison()`, JS toggle handler.

**Files modified across all changes in this session (6):**

- `public/css/public.css` — Triple-class button hack, filter chip sections, desktop toolbar, removed filter groups
- `public/class-card-shortcodes.php` — Thematic chips HTML, AJAX handler cases, affiliate-only filter, tie-breaker sort, removed filter groups HTML
- `public/class-card-display.php` — Dynamic miles VPP lookup
- `public/js/public.js` — Removed filter groups toggle handler
- `includes/class-points-system.php` — Dynamic miles reward type in auto-calculation
- `hk-card-compare.php` — Version bumps (1.0.7 → 1.0.11)

---

## Known Issues & Technical Debt

| #  | Issue                                         | Priority | Notes                                      |
|----|-----------------------------------------------|----------|--------------------------------------------|
| 1  | No query caching layer                        | Medium   | Spec calls for `wp_cache_get/set` wrapper. Add when traffic warrants it. |
| 2  | No unit/integration tests                     | Medium   | Should add PHPUnit tests for meta save, points calculation, shortcode output. |
| 3  | AJAX filter sends filters as JSON string      | Low      | Works but could use proper array serialisation for cleaner backend parsing. |
| 4  | ~~No i18n `.pot` file generated~~              | Resolved | Frontend now hardcoded zh-HK. Admin stays English. No i18n needed for now. |
| 5  | Templates not auto-loaded from theme          | Low      | Need to add `locate_template()` fallback so themes can override templates. |
| 6  | Flatsome theme CSS specificity wars           | Ongoing  | Theme loads CSS after plugin. Must use triple-class hack or `all: unset !important`. Document any new overrides needed. |
| 7  | Dining/supermarket sortable fields for cash-only cards | Medium | `auto_calculate_rebates()` only runs for points-system cards. Cash-only cards need manual `_cash_sortable` entry, or admin UI needs auto-save for direct cash fields. |
| 8  | Re-save cards after points system changes     | Info     | After fixing dynamic miles type, all cards using non-Asia-Miles systems (e.g., Avios) need a re-save to regenerate `_miles_display`/`_miles_sortable`. |

---

## What's Next

Per the README spec, remaining phases are:

### Phase 2: UX & Filtering (Weeks 7–10)
- [x] Flatsome theme integration & styling polish (triple-class hack, `all: unset`)
- [x] Thematic filter chips (15 total: features + airline/hotel programs)
- [x] Recommendation sort & tie-breaker logic
- [ ] Filter state persistence (localStorage / URL params)
- [ ] Loading states & animations
- [ ] Error handling improvements

### Phase 3: PublishPress & Scheduling (Weeks 11–13)
- [ ] Test PublishPress scheduled revision workflow end-to-end
- [ ] Enhance analytics with chart visualisations
- [ ] Date range export functionality

### Phase 4: SEO & Optimization (Weeks 14–15)
- [ ] Implement `get_cards_cached()` query caching wrapper
- [ ] Image optimisation audit (WebP, srcset)
- [ ] AJAX debounce tuning
- [ ] Performance profiling under load

### Future Enhancements (Post-Phase 4)
- Smart recommendations (spending input → best card)
- Side-by-side comparison view
- Spending calculator
- CSV import / bulk edit
- User reviews & ratings

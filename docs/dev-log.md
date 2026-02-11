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
4. [Known Issues & Technical Debt](#known-issues--technical-debt)
   - [2026-02-11 — Session 3: UX/UI Psychology-Driven Redesign](#2026-02-11--session-3-uxui-psychology-driven-redesign)
5. [What's Next](#whats-next)

---

## Current State

| Item                  | Status                                                  |
|-----------------------|---------------------------------------------------------|
| **Branch**            | `claude/code-plugin-E74dL`                              |
| **Plugin Version**    | 1.0.0                                                   |
| **Phase**             | Phase 1 complete (core functionality). Phase 2–4 pending.|
| **Files**             | 18 PHP/CSS/JS files in `hk-card-compare/`               |
| **Database Tables**   | 3 custom tables (created on activation)                 |
| **Shortcodes**        | `[cc_comparison]`, `[cc_suggest]` — both functional     |
| **Admin Pages**       | Card edit (tabbed), Points Systems, Analytics            |
| **Frontend**          | Mobile-first CSS, AJAX filters, click tracking          |
| **Tests**             | None yet                                                |
| **Production Deploy** | Not yet deployed                                        |

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

---

## Known Issues & Technical Debt

| #  | Issue                                         | Priority | Notes                                      |
|----|-----------------------------------------------|----------|--------------------------------------------|
| 1  | No query caching layer                        | Medium   | Spec calls for `wp_cache_get/set` wrapper. Add when traffic warrants it. |
| 2  | No unit/integration tests                     | Medium   | Should add PHPUnit tests for meta save, points calculation, shortcode output. |
| 3  | AJAX filter sends filters as JSON string      | Low      | Works but could use proper array serialisation for cleaner backend parsing. |
| 4  | ~~No i18n `.pot` file generated~~              | Resolved | Frontend now hardcoded zh-HK. Admin stays English. No i18n needed for now. |
| 5  | Templates not auto-loaded from theme          | Low      | Need to add `locate_template()` fallback so themes can override templates. |

---

## What's Next

Per the README spec, remaining phases are:

### Phase 2: UX & Filtering (Weeks 7–10)
- [ ] Filter state persistence (localStorage / URL params)
- [ ] Loading states & animations
- [ ] Flatsome theme integration & styling polish
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

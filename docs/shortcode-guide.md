# HK Card Compare — Shortcode Guide

> Last updated: 2026-02-10

This document covers every shortcode provided by the HK Card Compare plugin, including all parameters, defaults, and ready-to-use examples you can paste directly into your WordPress pages or posts.

---

## Table of Contents

1. [`[cc_comparison]` — Full Card Listing Page](#1-cc_comparison--full-card-listing-page)
2. [`[cc_suggest]` — Blog Post Card Recommendations](#2-cc_suggest--blog-post-card-recommendations)
3. [Parameter Reference Tables](#3-parameter-reference-tables)
4. [Ready-to-Use Shortcodes](#4-ready-to-use-shortcodes)
5. [Tips & Notes](#5-tips--notes)

---

## 1. `[cc_comparison]` — Full Card Listing Page

Displays a filterable, expandable card listing with miles/cash toggle. Designed for dedicated comparison pages.

### Syntax

```
[cc_comparison
  category=""
  bank=""
  network=""
  filters="bank,network,annual_fee"
  default_sort="local_retail_cash_sortable"
  default_order="desc"
  show_toggle="true"
  default_view="miles"
]
```

### Parameters

| Parameter       | Type    | Default                        | Description                                                        |
|-----------------|---------|--------------------------------|--------------------------------------------------------------------|
| `category`      | string  | _(empty — show all)_           | Filter by `card_network` taxonomy slug(s). Comma-separated.        |
| `bank`          | string  | _(empty — show all)_           | Filter by `card_bank` taxonomy slug(s). Comma-separated.           |
| `network`       | string  | _(empty — show all)_           | Filter by `card_network` taxonomy slug(s). Comma-separated.        |
| `filters`       | string  | `bank,network,annual_fee`      | Which filter groups to show. See [Filter Keys](#filter-keys).      |
| `default_sort`  | string  | `local_retail_cash_sortable`   | Meta key to sort by on initial load.                               |
| `default_order` | string  | `desc`                         | Sort order: `asc` or `desc`.                                      |
| `show_toggle`   | string  | `true`                         | Show the Miles/Cash toggle. Set `false` to hide.                   |
| `default_view`  | string  | `miles`                        | Initial view mode: `miles` or `cash`.                              |

### Filter Keys

Use these values in the `filters` parameter (comma-separated):

| Key              | Filter Label  | Description                                      |
|------------------|---------------|--------------------------------------------------|
| `bank`           | 發卡銀行       | Checkbox list of card_bank taxonomy terms         |
| `network`        | 結算機構       | Checkbox list of card_network taxonomy terms      |
| `annual_fee`     | 年費           | Radio: 任何 / 永久免年費 / 首年免年費               |
| `min_income`     | 最低收入       | Radio: 任何 / <50k / 50k–100k / >100k             |
| `lounge_access`  | 機場貴賓室     | Radio: 任何 / 有 / 無                              |
| `points_system`  | 回贈類型       | Radio: 任何 / 現金回贈 / 積分系統                   |

### Sortable Meta Keys

Use these in `default_sort`:

| Meta Key                             | Description             |
|--------------------------------------|-------------------------|
| `local_retail_cash_sortable`         | Local retail cashback % |
| `overseas_retail_cash_sortable`      | Overseas cashback %     |
| `online_hkd_cash_sortable`           | Online HKD cashback %   |
| `online_fx_cash_sortable`            | Online FX cashback %    |
| `local_dining_cash_sortable`         | Dining cashback %       |
| `annual_fee_sortable`                | Annual fee (HKD)        |
| `fx_fee_sortable`                    | FX fee (%)              |
| `lounge_access_sortable`             | Lounge visits/year      |
| `min_income_sortable`                | Min income (HKD)        |
| `welcome_cooling_period_sortable`    | Cooling period (months) |

---

## 2. `[cc_suggest]` — Blog Post Card Recommendations

Displays 3–5 recommended cards in a compact grid or carousel. Ideal for embedding at the end of blog posts.

### Syntax

```
[cc_suggest
  category=""
  bank=""
  metric=""
  sort=""
  order="desc"
  limit="5"
  exclude=""
  layout="grid"
]
```

### Parameters

| Parameter  | Type    | Default            | Description                                                             |
|------------|---------|--------------------|-------------------------------------------------------------------------|
| `category` | string  | _(empty)_          | Filter by `card_network` taxonomy slug(s). Comma-separated.             |
| `bank`     | string  | _(empty)_          | Filter by `card_bank` taxonomy slug(s). Comma-separated.                |
| `metric`   | string  | _(empty)_          | Pre-defined sorting metric. See [Metric Options](#metric-options).      |
| `sort`     | string  | _(empty)_          | Raw meta key to sort by. Overrides `metric`.                            |
| `order`    | string  | `desc`             | Sort order: `asc` or `desc`.                                           |
| `limit`    | int     | `5`                | Number of cards to display (1–10 recommended).                          |
| `exclude`  | string  | _(empty)_          | Card post IDs to exclude. Comma-separated.                              |
| `layout`   | string  | `grid`             | Layout style: `grid` or `carousel`.                                     |

### Metric Options

| Metric Value        | Sorts By                              | Order |
|---------------------|---------------------------------------|-------|
| `cashback_local`    | `local_retail_cash_sortable`          | DESC  |
| `cashback_overseas` | `overseas_retail_cash_sortable`       | DESC  |
| `asia_miles_local`  | Local retail → Asia Miles (calculated)| DESC  |
| `lounge_access`     | `lounge_access_sortable`              | DESC  |
| `annual_fee_low`    | `annual_fee_sortable`                 | ASC   |

---

## 3. Parameter Reference Tables

### Taxonomy Slugs (for `bank`, `network`, `category`)

These depend on what you've created in WordPress. Common examples:

**Banks (`card_bank`):**
`hsbc`, `citi`, `standard-chartered`, `dbs`, `hang-seng`, `boc`, `bea`

**Networks (`card_network`):**
`visa`, `mastercard`, `unionpay`, `american-express`

> Tip: Check your actual slugs at **Cards → 發卡銀行** and **Cards → 結算機構** in WordPress admin.

---

## 4. Ready-to-Use Shortcodes

Copy and paste these directly into your WordPress pages/posts.

### Comparison Pages

#### Show All Cards (default filters)
```
[cc_comparison]
```

#### Best Cashback Cards — sorted by local retail cashback
```
[cc_comparison default_sort="local_retail_cash_sortable" default_order="desc" default_view="cash" filters="bank,network,annual_fee,min_income"]
```

#### Best Miles Cards — sorted by local spending, miles view
```
[cc_comparison default_sort="local_retail_cash_sortable" default_order="desc" default_view="miles" filters="bank,network,annual_fee,min_income"]
```

#### All HSBC Cards
```
[cc_comparison bank="hsbc" filters="network,annual_fee"]
```

#### All Citi Cards
```
[cc_comparison bank="citi" filters="network,annual_fee"]
```

#### Mastercard Only
```
[cc_comparison network="mastercard" filters="bank,annual_fee,min_income"]
```

#### Visa Only
```
[cc_comparison network="visa" filters="bank,annual_fee,min_income"]
```

#### Budget-Friendly Cards — sorted by lowest annual fee
```
[cc_comparison default_sort="annual_fee_sortable" default_order="asc" filters="bank,annual_fee,min_income"]
```

#### Cards with Airport Lounge Access
```
[cc_comparison filters="bank,network,annual_fee,lounge_access"]
```

#### Full Filters (all filter options visible)
```
[cc_comparison filters="bank,network,annual_fee,min_income,lounge_access,points_system"]
```

#### Cash-only view, no toggle
```
[cc_comparison show_toggle="false" default_view="cash"]
```

---

### Blog Post Footers (`[cc_suggest]`)

#### Generic: Top 5 cards
```
[cc_suggest]
```

#### Best Local Cashback (for general spending articles)
```
[cc_suggest metric="cashback_local" limit="5"]
```

#### Best Overseas Cashback (for travel spending articles)
```
[cc_suggest metric="cashback_overseas" limit="5"]
```

#### Best Asia Miles Cards (for miles/points articles)
```
[cc_suggest metric="asia_miles_local" limit="5"]
```

#### Cheapest Annual Fee (for budget articles)
```
[cc_suggest metric="annual_fee_low" limit="5"]
```

#### Best Lounge Access Cards (for airport lounge articles)
```
[cc_suggest metric="lounge_access" limit="3"]
```

#### Mastercard Promotions (for Mastercard-specific posts)
```
[cc_suggest category="mastercard" limit="5"]
```

#### Visa Promotions
```
[cc_suggest category="visa" limit="5"]
```

#### HSBC Cards Only
```
[cc_suggest bank="hsbc" limit="3"]
```

#### Citi Cards Only
```
[cc_suggest bank="citi" limit="3"]
```

#### Top 3 Cards, Exclude Specific Cards
```
[cc_suggest limit="3" exclude="123,456"]
```
> Replace `123,456` with actual card post IDs.

#### HSBC + Mastercard Combo
```
[cc_suggest bank="hsbc" category="mastercard" limit="3"]
```

---

## 5. Tips & Notes

- **Taxonomy slugs are case-sensitive** — use lowercase slugs as they appear in WordPress (e.g., `hsbc` not `HSBC`).
- **Multiple values** — use commas with no spaces: `bank="hsbc,citi"`.
- **The Miles/Cash toggle** only appears when at least one card on the page uses a points system with Asia Miles conversion. If all cards are direct-cash, the toggle is hidden automatically.
- **AJAX filtering** — filter changes on `[cc_comparison]` pages happen in real-time without page reload. There's a 300ms debounce so rapid clicks don't fire excessive requests.
- **Expand/collapse** — each card in `[cc_comparison]` has a "View Details" button that expands to show full fee, reward, welcome offer, and benefit information.
- **Click tracking** — every "Apply Now" button click is tracked automatically. View reports at **Cards → Analytics** in WordPress admin.
- **Featured parameters** — the 4 values shown on collapsed cards are configured per-card in the admin edit screen under the "Featured" tab.
- **Mobile** — filters show as a collapsible accordion on mobile. On desktop (768px+), filters appear as a fixed sidebar on the left.

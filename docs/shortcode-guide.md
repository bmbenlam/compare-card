# HK Card Compare — Shortcode Guide

> Last updated: 2026-02-20

This document covers every shortcode provided by the HK Card Compare plugin, including all parameters, defaults, and ready-to-use examples you can paste directly into your WordPress pages or posts.

---

## Table of Contents

1. [`[cc_comparison]` — Full Card Listing Page](#1-cc_comparison--full-card-listing-page)
2. [`[cc_suggest]` — Blog Post Card Recommendations](#2-cc_suggest--blog-post-card-recommendations)
3. [`[cc_card]` — Single Card Spotlight](#3-cc_card--single-card-spotlight)
4. [Parameter Reference Tables](#4-parameter-reference-tables)
5. [Ready-to-Use Shortcodes](#5-ready-to-use-shortcodes)
6. [Tips & Notes](#6-tips--notes)

---

## 1. `[cc_comparison]` — Full Card Listing Page

Displays a filterable, expandable card listing with miles/cash toggle. Designed for dedicated comparison pages.

### Syntax

```
[cc_comparison
  category=""
  bank=""
  network=""
  airline=""
  hotel=""
  filters="bank,network"
  default_sort=""
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
| `airline`       | string  | _(empty — show all)_           | Filter by transferable airline program. Partial name match, comma-separated (AND logic). |
| `hotel`         | string  | _(empty — show all)_           | Filter by transferable hotel program. Partial name match, comma-separated (AND logic). |
| `filters`       | string  | `bank,network`                 | Which filter groups to show. See [Filter Keys](#filter-keys).      |
| `default_sort`  | string  | _(empty — recommendation)_     | Meta key to sort by on initial load. Empty = recommendation sort.  |
| `default_order` | string  | `desc`                         | Sort order: `asc` or `desc`.                                      |
| `show_toggle`   | string  | `true`                         | Show the Miles/Cash toggle. Set `false` to hide.                   |
| `default_view`  | string  | `miles`                        | Initial view mode: `miles` or `cash`.                              |

### Filter Keys

Use these values in the `filters` parameter (comma-separated):

| Key              | Filter Label  | Description                                      |
|------------------|---------------|--------------------------------------------------|
| `bank`           | 發卡銀行       | Checkbox list of card_bank taxonomy terms         |
| `network`        | 結算機構       | Checkbox list of card_network taxonomy terms      |

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

### Recommendation Sort (Default)

When no `default_sort` is specified, cards are sorted by a built-in recommendation algorithm:

1. Cards **with** an affiliate link appear first, then cards **without**.
2. Within each group, cards with miles data appear before pure-cash cards.
3. Miles cards are sorted by overseas HK$/mile **ascending** (lower = better).
4. Pure-cash cards are sorted by overseas cashback % **descending** (higher = better).

---

## 2. `[cc_suggest]` — Blog Post Card Recommendations

Displays 3–5 recommended cards in a compact grid. Ideal for embedding at the end of blog posts.

### Syntax

```
[cc_suggest
  category=""
  bank=""
  airline=""
  hotel=""
  metric=""
  sort=""
  order="desc"
  limit="5"
  exclude=""
  layout="grid"
]
```

### Parameters

| Parameter  | Type    | Default                | Description                                                             |
|------------|---------|------------------------|-------------------------------------------------------------------------|
| `category` | string  | _(empty)_              | Filter by `card_network` taxonomy slug(s). Comma-separated.             |
| `bank`     | string  | _(empty)_              | Filter by `card_bank` taxonomy slug(s). Comma-separated.                |
| `airline`  | string  | _(empty)_              | Filter by transferable airline program. Partial name match, comma-separated (AND logic). |
| `hotel`    | string  | _(empty)_              | Filter by transferable hotel program. Partial name match, comma-separated (AND logic). |
| `metric`   | string  | _(empty)_              | Pre-defined sorting metric. See [Metric Options](#metric-options).      |
| `sort`     | string  | _(empty)_              | Raw meta key to sort by. Overrides `metric`.                            |
| `order`    | string  | _(empty — auto)_       | Sort order: `asc` or `desc`. Auto-set by metric if omitted.            |
| `limit`    | int     | `5`                    | Number of cards to display (1–10 recommended).                          |
| `exclude`  | string  | _(empty)_              | Card post IDs to exclude. Comma-separated.                              |
| `layout`   | string  | `grid`                 | Layout style: `grid` or `carousel`.                                     |

### Metric Options

When a metric is used, cards with `0` or missing values for that field are **automatically filtered out**.

| Metric Value        | Sorts By                              | Order |
|---------------------|---------------------------------------|-------|
| `cashback_local`    | `local_retail_cash_sortable`          | DESC  |
| `cashback_overseas` | `overseas_retail_cash_sortable`       | DESC  |
| `asia_miles_local`  | `local_retail_miles_sortable`         | ASC   |
| `lounge_access`     | `lounge_access_sortable`              | DESC  |
| `annual_fee_low`    | `annual_fee_sortable`                 | ASC   |

### Default Behaviour (No Sort/Metric)

When neither `sort` nor `metric` is specified, `[cc_suggest]` uses the same **recommendation sort** algorithm as `[cc_comparison]` (affiliate-link cards first → miles ascending → cash descending).

---

## 3. `[cc_card]` — Single Card Spotlight

Displays a single card in a spotlight format. Shows: tagline, card face, name, short welcome offer, 4-pack featured values, apply button, full welcome offer details, blog + apply buttons, and footnotes. **No** rewards table, perks, issuer info, or expand/collapse toggle.

### Syntax

```
[cc_card id="123"]
[cc_card slug="card-slug"]
[cc_card slug="card-slug" view="cash"]
```

### Parameters

| Parameter | Type    | Default   | Description                                           |
|-----------|---------|-----------|-------------------------------------------------------|
| `id`      | int     | `0`       | Card post ID. Takes priority over `slug`.             |
| `slug`    | string  | _(empty)_ | Card post slug (URL-safe name).                       |
| `view`    | string  | `miles`   | Display mode: `miles` or `cash`.                      |

### What It Shows

1. **Tagline bookmark** (if set)
2. **Card face image** (clickable → affiliate link)
3. **Card name**
4. **Short welcome offer preview** (supports line breaks)
5. **4-pack featured parameters** (with numbered footnotes)
6. **Apply button** (if affiliate link exists)
7. **Full welcome offer** — expiry date, rich text description, cooling period
8. **Blog + Apply buttons**
9. **Footnotes** (deduplicated)

---

## 4. Parameter Reference Tables

### Taxonomy Slugs (for `bank`, `network`, `category`)

These depend on what you've created in WordPress. Common examples:

**Banks (`card_bank`):**
`hsbc`, `citi`, `standard-chartered`, `dbs`, `hang-seng`, `boc`, `bea`, `primecredit`

**Networks (`card_network`):**
`visa`, `mastercard`, `unionpay`, `american-express`

> Tip: Check your actual slugs at **Cards → 發卡銀行** and **Cards → 結算機構** in WordPress admin.

### Airline Program Names (for `airline` parameter)

The `airline` parameter matches against the card's "可轉換航空里程" field using **substring matching**. You can use any portion of the standardized name.

| Full Standardized Name               | Example Short Match Values         |
|---------------------------------------|------------------------------------|
| 國泰航空 Asia Miles                    | `Asia Miles`, `國泰`              |
| 英國航空 BA Avios                      | `Avios`, `英國航空`               |
| 芬蘭航空 Finnair Plus                  | `Finnair`                         |
| 馬來西亞航空 Enrich Frequent Flyer      | `Enrich`                          |
| 澳洲航空 Qantas Frequent Flyer        | `Qantas`                          |
| 新加坡航空 KrisFlyer                   | `KrisFlyer`, `新加坡`             |
| 長榮航空 無限萬哩遊                     | `長榮`, `萬哩遊`                  |
| 阿聯酋航空 Skywards                    | `Skywards`, `阿聯酋`              |
| 維珍航空 Virgin Flying Club            | `Virgin`                          |
| 阿堤哈德航空 Etihad Guest Miles        | `Etihad`                          |
| KLM Flying Blue                       | `Flying Blue`, `KLM`              |
| 卡塔爾航空 Qatar Privilege Club        | `Qatar`, `卡塔爾`                 |
| 泰國航空 Royal Orchid Plus             | `Royal Orchid`                    |
| 中國國航 鳳凰知音                      | `鳳凰知音`, `中國國航`             |
| 加拿大航空 Aeroplan                    | `Aeroplan`                        |
| 越南航空 Lotusmiles                    | `Lotusmiles`, `越南`              |
| 土耳其航空 Miles & Smiles              | `Miles & Smiles`, `土耳其`        |

### Hotel Program Names (for `hotel` parameter)

| Full Standardized Name               | Example Short Match Values         |
|---------------------------------------|------------------------------------|
| 萬豪 Marriott Bonvoy                  | `Marriott`, `萬豪`                |
| 希爾頓 Hilton Honors                  | `Hilton`, `希爾頓`                |
| 洲際 IHG Reward Club                  | `IHG`, `洲際`                     |
| 雅高 Accor Live Limitless             | `Accor`, `雅高`                   |

> **Multiple values = AND logic.** `airline="Avios,Qatar"` shows only cards that support **both** BA Avios AND Qatar Privilege Club.

---

## 5. Ready-to-Use Shortcodes

Copy and paste these directly into your WordPress pages/posts.

### Comparison Pages

#### Show All Cards (recommendation sort)
```
[cc_comparison]
```

#### Best Cashback Cards — sorted by local retail cashback
```
[cc_comparison default_sort="local_retail_cash_sortable" default_order="desc" default_view="cash"]
```

#### Best Miles Cards — sorted by local spending, miles view
```
[cc_comparison default_sort="local_retail_cash_sortable" default_order="desc" default_view="miles"]
```

#### All HSBC Cards
```
[cc_comparison bank="hsbc"]
```

#### All Citi Cards
```
[cc_comparison bank="citi"]
```

#### Mastercard Only
```
[cc_comparison network="mastercard"]
```

#### Visa Only
```
[cc_comparison network="visa"]
```

#### Budget-Friendly Cards — sorted by lowest annual fee
```
[cc_comparison default_sort="annual_fee_sortable" default_order="asc"]
```

#### Cards that transfer to Asia Miles
```
[cc_comparison airline="Asia Miles"]
```

#### Cards that transfer to both BA Avios AND Qatar
```
[cc_comparison airline="Avios,Qatar"]
```

#### Cards that transfer to Marriott Bonvoy
```
[cc_comparison hotel="Marriott"]
```

#### Cash-only view, no toggle
```
[cc_comparison show_toggle="false" default_view="cash"]
```

---

### Blog Post Footers (`[cc_suggest]`)

#### Generic: Top 5 cards (recommendation sort)
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

#### Mastercard Promotions
```
[cc_suggest category="mastercard" limit="5"]
```

#### HSBC Cards Only
```
[cc_suggest bank="hsbc" limit="3"]
```

#### Citi Cards Only
```
[cc_suggest bank="citi" limit="3"]
```

#### Cards that transfer to BA Avios & Qatar Privilege Club
```
[cc_suggest airline="Avios,Qatar" limit="5"]
```

#### Cards that transfer to Marriott Bonvoy
```
[cc_suggest hotel="Marriott" limit="3"]
```

#### Cards that transfer to KrisFlyer
```
[cc_suggest airline="KrisFlyer" limit="5"]
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

### Single Card Spotlight (`[cc_card]`)

#### By post ID
```
[cc_card id="123"]
```

#### By slug
```
[cc_card slug="hsbc-visa-signature"]
```

#### Cash view
```
[cc_card slug="hsbc-visa-signature" view="cash"]
```

---

## 6. Tips & Notes

- **Multiline shortcodes are supported.** You can break attributes across multiple lines for readability — the plugin automatically collapses them before parsing.
- **Taxonomy slugs are case-sensitive** — use lowercase slugs as they appear in WordPress (e.g., `hsbc` not `HSBC`).
- **Multiple values** — use commas with no spaces: `bank="hsbc,citi"`.
- **Airline/hotel matching** — the `airline` and `hotel` parameters use substring matching on the serialized meta field. Partial names work (e.g., `Avios` matches `英國航空 BA Avios`). Multiple comma-separated values use **AND** logic (card must support all listed programs).
- **The Miles/Cash toggle** only appears when at least one card on the page uses a points system with Asia Miles conversion. If all cards are direct-cash, the toggle is hidden automatically.
- **AJAX filtering** — filter changes on `[cc_comparison]` pages happen in real-time without page reload. There's a 300ms debounce so rapid clicks don't fire excessive requests.
- **Expand/collapse** — each card in `[cc_comparison]` and `[cc_suggest]` has a "查看詳情" button that expands to show full fee, reward, welcome offer, and benefit information.
- **Click tracking** — every "立即申請" button click is tracked automatically. View reports at **Cards → Analytics** in WordPress admin.
- **Featured parameters** — the 4 values shown on collapsed cards are configured per-card in the admin edit screen under the "Featured" tab.
- **Mobile** — filters show as a collapsible accordion on mobile. On desktop (768px+), the toolbar expands by default.
- **Recommendation sort** — when no sort is specified, both `[cc_comparison]` and `[cc_suggest]` use a smart default: affiliate cards first, then sorted by overseas miles (ascending) or overseas cash (descending).
- **Metric filtering** — when using `metric` in `[cc_suggest]`, cards with 0 or unknown values for that metric are automatically excluded from results.

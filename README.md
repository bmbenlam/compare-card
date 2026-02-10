# WordPress Credit Card Comparison Plugin - Complete Feature Specification

## Project Overview

**Plugin Name:** HK Card Compare (or your preferred name)
**WordPress Version:** 6.9.1+
**PHP Version:** 8.0+
**Database:** MySQL 5.7+
**Theme:** Flatsome
**Required Plugin:** PublishPress Revisions (Free version)
**Existing Infrastructure:** Cloudflare CDN

**Target Audience:** Hong Kong travel & personal finance blog
**Primary Language:** Traditional Chinese (香港中文 - 書面語)
**Traffic:** 65% mobile, 35% desktop

---

## 1. Database Schema

### 1.1 Custom Post Type: `card`

```php
Post Type: 'card'
Supports: title, editor, thumbnail, revisions, custom-fields
Hierarchical: false
Public: true
Show in REST: true
Menu Icon: 'dashicons-money-alt'
```

### 1.2 Custom Taxonomies

```php
Taxonomy 1: 'card_bank'
- Labels: 發卡銀行
- Hierarchical: false
- Examples: HSBC, 花旗銀行, 渣打銀行, DBS, 恒生銀行

Taxonomy 2: 'card_network'
- Labels: 結算機構
- Hierarchical: false
- Examples: Visa, Mastercard, UnionPay, American Express
```

### 1.3 Custom Database Tables

**Table 1: `{prefix}_card_points_systems`**
```sql
CREATE TABLE {prefix}_card_points_systems (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  system_name VARCHAR(100) NOT NULL,
  system_name_en VARCHAR(100),
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY system_name (system_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Table 2: `{prefix}_card_points_conversion`**
```sql
CREATE TABLE {prefix}_card_points_conversion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  system_id BIGINT UNSIGNED NOT NULL,
  reward_type VARCHAR(50) NOT NULL,
  points_required INT UNSIGNED NOT NULL,
  reward_value DECIMAL(10,2) NOT NULL,
  reward_currency VARCHAR(10) DEFAULT 'HKD',
  effective_date DATE,
  expiry_date DATE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (system_id) REFERENCES {prefix}_card_points_systems(id) ON DELETE CASCADE,
  KEY system_reward (system_id, reward_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- reward_type examples: 'cash', 'asia_miles', 'ba_avios', 'marriott_bonvoy', 'hilton_honor'
```

**Table 3: `{prefix}_card_clicks`**
```sql
CREATE TABLE {prefix}_card_clicks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  card_id BIGINT UNSIGNED NOT NULL,
  source_url VARCHAR(500),
  clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY card_id (card_id),
  KEY clicked_at (clicked_at),
  KEY source_card (source_url(191), card_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 1.4 Post Meta Fields

All stored in `{prefix}_postmeta` with post_type = 'card'

**Basic Information:**
```
meta_key: tagline
meta_value: string (max 200 chars)

meta_key: affiliate_link
meta_value: URL string

meta_key: blog_post_link
meta_value: URL string (internal post/page ID or full URL)
```

**Payment Details:**
```
meta_key: late_fee_display
meta_value: string (e.g., "HK$300")

meta_key: late_fee_sortable
meta_value: int (e.g., 300)

meta_key: interest_free_period_display
meta_value: string (e.g., "58 日")

meta_key: interest_free_period_sortable
meta_value: int (e.g., 58)
```

**Eligibility:**
```
meta_key: min_age_display
meta_value: string (e.g., "18 歲或以上")

meta_key: min_age_sortable
meta_value: int (e.g., 18)

meta_key: min_income_display
meta_value: string (e.g., "HK$96,000 / 沒有公開")

meta_key: min_income_sortable
meta_value: int (e.g., 96000, use 0 for "沒有公開")
```

**Fees:**
```
meta_key: annual_fee_display
meta_value: string (e.g., "HK$2,200 / 永久免年費")

meta_key: annual_fee_sortable
meta_value: int (e.g., 2200, use 0 for free)

meta_key: annual_fee_waiver
meta_value: string (e.g., "新客免首年年費")

meta_key: fx_fee_display
meta_value: string (e.g., "1.95%")

meta_key: fx_fee_sortable
meta_value: float (e.g., 1.95)

meta_key: cross_border_fee_display
meta_value: string (e.g., "1%")

meta_key: cross_border_fee_sortable
meta_value: float (e.g., 1.0)
```

**Rewards - Points System (if applicable):**
```
meta_key: points_system_id
meta_value: int (references {prefix}_card_points_systems.id, 0 if direct cash)

meta_key: local_retail_points
meta_value: string (e.g., "HK$1 = 3 MR 積分")

meta_key: overseas_retail_points
meta_value: string (e.g., "HK$1 = 9 MR 積分")

meta_key: online_hkd_points
meta_value: string

meta_key: online_fx_points
meta_value: string

meta_key: local_dining_points
meta_value: string

meta_key: online_bill_payment_points
meta_value: string

meta_key: payme_reload_points
meta_value: string

meta_key: alipay_reload_points
meta_value: string

meta_key: wechat_reload_points
meta_value: string

meta_key: octopus_reload_points
meta_value: string
```

**Rewards - Direct Cash (if points_system_id = 0):**
```
meta_key: local_retail_cash_display
meta_value: string (e.g., "1%，不設上限")

meta_key: local_retail_cash_sortable
meta_value: float (e.g., 1.0)

meta_key: overseas_retail_cash_display
meta_value: string (e.g., "4%，每季上限回 HK$500")

meta_key: overseas_retail_cash_sortable
meta_value: float (e.g., 4.0)

-- Repeat pattern for:
-- online_hkd_cash_*
-- online_fx_cash_*
-- local_dining_cash_*
-- online_bill_payment_cash_*
-- payme_reload_cash_*
-- alipay_reload_cash_*
-- wechat_reload_cash_*
-- octopus_reload_cash_*
```

**Welcome Offer:**
```
meta_key: welcome_cooling_period_display
meta_value: string (e.g., "12 個月")

meta_key: welcome_cooling_period_sortable
meta_value: int (e.g., 12)

meta_key: welcome_offer_description
meta_value: longtext (HTML allowed, for complex offers)

meta_key: welcome_offer_expiry
meta_value: date (YYYY-MM-DD format)
```

**Redemption:**
```
meta_key: redemption_types
meta_value: serialized array (e.g., ['statement_credit', 'points_system'])

meta_key: statement_credit_requirement
meta_value: string (e.g., "手動／達 HK$50 或以上時自動存入戶口")

meta_key: points_system_name
meta_value: string (e.g., "Thank You Rewards", "獎賞錢")

meta_key: points_redemption_fee_display
meta_value: string (e.g., "HK$0 / 每次 HK$400")

meta_key: points_redemption_fee_sortable
meta_value: int (e.g., 0)

meta_key: transferable_airlines
meta_value: serialized array of airline names

meta_key: transferable_hotels
meta_value: serialized array of hotel programs
```

**Benefits:**
```
meta_key: lounge_access_display
meta_value: string (e.g., "每年 3 次，沒有簽賬要求")

meta_key: lounge_access_sortable
meta_value: int (e.g., 3, number of visits)

meta_key: travel_insurance
meta_value: string (e.g., "沒有額外要求 / 需憑卡繳付機票、酒店等費用")
```

**Featured Parameters (Collapsed View):**
```
meta_key: featured_param_1
meta_value: string (field key, e.g., "local_retail_cash_display" or "local_retail_points")

meta_key: featured_param_2
meta_value: string

meta_key: featured_param_3
meta_value: string

meta_key: featured_param_4
meta_value: string
```

---

## 2. Admin Interface Specifications

### 2.1 Menu Structure

```
WordPress Admin Menu:
├── Cards (main menu, icon: dashicons-money-alt)
│   ├── All Cards
│   ├── Add New
│   ├── 發卡銀行 (card_bank taxonomy)
│   ├── 結算機構 (card_network taxonomy)
│   ├── Points Systems (submenu)
│   └── Analytics (submenu)
```

### 2.2 Add/Edit Card Screen

**Layout: Tabbed Interface**

```
┌─────────────────────────────────────────────────┐
│ Add New Card                                    │
├─────────────────────────────────────────────────┤
│ Card Name: [________________________]           │
│                                                 │
│ [Basic Info] [Fees] [Rewards] [Welcome] [Benefits] [Featured]
├─────────────────────────────────────────────────┤
│ (Tab content appears here)                      │
└─────────────────────────────────────────────────┘

Sidebar (WordPress default):
├── Featured Image (Card Face)
│   └── Recommended: 600x380px, <500KB, JPG/PNG
├── 發卡銀行 (checkbox list)
├── 結算機構 (checkbox list)
└── Publish (standard WordPress)
```

**Tab 1: Basic Info**
```
Tagline: [_________________________________]
(Max 200 characters)

Affiliate Link: [_________________________________]
(Must be valid URL, opens in new tab)

Blog Post Link: [_________________________________]
(Internal link picker OR external URL)
```

**Tab 2: Fees**
```
Annual Fee:
├── Display: [_________________________________]
└── Sortable Value: [________] (HKD, numbers only)

Annual Fee Waiver: [_________________________________]

Foreign Exchange Fee:
├── Display: [_________________________________]
└── Sortable Value: [________] (%, decimal allowed)

Cross-Border Fee:
├── Display: [_________________________________]
└── Sortable Value: [________] (%, decimal allowed)

Late Fee:
├── Display: [_________________________________]
└── Sortable Value: [________] (HKD, numbers only)

Interest-Free Period:
├── Display: [_________________________________]
└── Sortable Value: [________] (days, numbers only)
```

**Tab 3: Rewards**
```
Rewards Type:
⚪ Direct Cash Rebates
⚪ Points System

[IF Direct Cash Rebates selected:]
┌─────────────────────────────────────────┐
│ Local Retail Spending:                  │
│ ├── Display: [______________________]   │
│ └── Sortable: [____] %                  │
│                                         │
│ Overseas Retail Spending:               │
│ ├── Display: [______________________]   │
│ └── Sortable: [____] %                  │
│                                         │
│ (Repeat for all 8 transaction types)   │
└─────────────────────────────────────────┘

[IF Points System selected:]
┌─────────────────────────────────────────┐
│ Select Points System: [Dropdown ▼]      │
│ (Shows: AE MR Points, Citi ThankYou, etc)│
│                                         │
│ Local Retail Spending:                  │
│ [HK$1 = ___ points]                     │
│                                         │
│ Overseas Retail Spending:               │
│ [HK$1 = ___ points]                     │
│                                         │
│ (Repeat for all 8 transaction types)   │
│                                         │
│ Auto-calculated rebate values:          │
│ (Read-only, shows calculated cash/miles)│
└─────────────────────────────────────────┘

Redemption Options:
☐ Statement Credit
  └── Requirement: [____________________]
☐ Points System
  ├── System Name: [____________________]
  ├── Redemption Fee Display: [_________]
  └── Redemption Fee Sortable: [____] HKD

Transferable Airlines: (multi-select)
☐ Asia Miles  ☐ BA Avios  ☐ Virgin Points
☐ [Add custom airline]

Transferable Hotels: (multi-select)
☐ Marriott Bonvoy  ☐ Hilton Honors
☐ [Add custom hotel program]
```

**Tab 4: Welcome Offer**
```
Cooling Period:
├── Display: [_________________________________]
└── Sortable Value: [________] (months, numbers only)

Welcome Offer Description:
[Rich text editor - WordPress TinyMCE]
(Supports tables, bullet points, formatting)

Offer Expiry Date: [YYYY-MM-DD] (date picker)
⚠️ Warning appears 7 days before expiry
```

**Tab 5: Benefits**
```
Free Lounge Access:
├── Display: [_________________________________]
└── Sortable Value: [________] (number of visits per year)

Travel Insurance:
[_________________________________]

(Future: Additional benefits can be added here)
```

**Tab 6: Featured Parameters**
```
Choose 4 parameters to feature on collapsed card view:

Slot 1: [Dropdown of all available parameters ▼]
Slot 2: [Dropdown of all available parameters ▼]
Slot 3: [Dropdown of all available parameters ▼]
Slot 4: [Dropdown of all available parameters ▼]

Preview:
┌─────────────────────────────────┐
│ [Card Face]                     │
│ Card Name                       │
│ Tagline                         │
├─────────────────────────────────┤
│ Slot 1: Value                   │
│ Slot 2: Value                   │
│ Slot 3: Value                   │
│ Slot 4: Value                   │
└─────────────────────────────────┘
```

**Eligibility Section (in sidebar meta box):**
```
┌─────────────────────────────────┐
│ Eligibility Requirements        │
├─────────────────────────────────┤
│ Minimum Age:                    │
│ ├── Display: [______________]   │
│ └── Sortable: [____] (years)    │
│                                 │
│ Minimum Income:                 │
│ ├── Display: [______________]   │
│ └── Sortable: [____] (HKD)      │
│     (Use 0 for "沒有公開")        │
└─────────────────────────────────┘
```

### 2.3 Points Systems Submenu

**URL:** `/wp-admin/admin.php?page=card-points-systems`

```
┌─────────────────────────────────────────────────┐
│ Points Systems                  [Add New System]│
├─────────────────────────────────────────────────┤
│                                                 │
│ AE Membership Rewards            [Edit] [×]     │
│ ├── Cash: 15,000 points = HK$50                 │
│ ├── Asia Miles: 18,000 points = 1,000 miles     │
│ ├── Marriott: 9,000 points = 1,000 points       │
│ └── Hilton: 6,000 points = 1,250 points         │
│                                                 │
│ Citi Thank You Points           [Edit] [×]      │
│ ├── Cash: 10,000 points = HK$50                 │
│ ├── Asia Miles: 10,000 points = 1,000 miles     │
│ └── ...                                         │
└─────────────────────────────────────────────────┘

[Add/Edit System Modal:]
┌─────────────────────────────────────────────────┐
│ System Name (Chinese): [___________________]    │
│ System Name (English): [___________________]    │
│                                                 │
│ Conversion Rates:                               │
│ ┌─────────────────────────────────────────────┐ │
│ │ Reward Type    Points   Value    Currency  │ │
│ ├─────────────────────────────────────────────┤ │
│ │ Cash          15000      50       HKD [×]   │ │
│ │ Asia Miles    18000    1000      miles [×]  │ │
│ │ [+ Add Conversion]                          │ │
│ └─────────────────────────────────────────────┘ │
│                                                 │
│ [Save] [Cancel]                                 │
└─────────────────────────────────────────────────┘
```

### 2.4 Analytics Submenu

**URL:** `/wp-admin/admin.php?page=card-analytics`

```
┌─────────────────────────────────────────────────┐
│ Card Analytics                                  │
├─────────────────────────────────────────────────┤
│ Date Range: [Last 7 days ▼] [Last 30 days ▼]   │
│            [Last 90 days ▼]                     │
│                                                 │
│ Filter by Source: [All Pages ▼]                 │
│                                                 │
│ ┌─────────────────────────────────────────────┐ │
│ │ Top Cards by Clicks                         │ │
│ ├─────────────────────────────────────────────┤ │
│ │ Card Name         Clicks    Click Rate      │ │
│ ├─────────────────────────────────────────────┤ │
│ │ HSBC Red          1,234     12.3%           │ │
│ │ Citi Rewards        987      9.8%           │ │
│ │ DBS Black           654      6.5%           │ │
│ │ ...                                         │ │
│ └─────────────────────────────────────────────┘ │
│                                                 │
│ ┌─────────────────────────────────────────────┐ │
│ │ Top Source Pages                            │ │
│ ├─────────────────────────────────────────────┤ │
│ │ Source Page              Total Clicks       │ │
│ ├─────────────────────────────────────────────┤ │
│ │ /blog/mastercard-promo     345              │ │
│ │ /cards/best-miles          234              │ │
│ │ /blog/travel-tips          123              │ │
│ └─────────────────────────────────────────────┘ │
│                                                 │
│ ┌─────────────────────────────────────────────┐ │
│ │ Recent Clicks (Last 100)                    │ │
│ ├─────────────────────────────────────────────┤ │
│ │ Time        Card             Source Page    │ │
│ ├─────────────────────────────────────────────┤ │
│ │ 2:34 PM     HSBC Red         /blog/post1    │ │
│ │ 2:30 PM     Citi Rewards     /cards/list    │ │
│ │ ...                                         │ │
│ └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
```

---

## 3. Frontend Specifications

### 3.1 Listing Page Layout (Mobile-First)

**Shortcode:** `[cc_comparison]`

```
┌─────────────────────────────────────────────────┐
│ 🔍 Filters (2 active) [Show ▼]                  │ ← Collapsed
└─────────────────────────────────────────────────┘
     OR (when expanded)
┌─────────────────────────────────────────────────┐
│ 🔍 Filters [Hide ▲]                             │
├─────────────────────────────────────────────────┤
│ ▼ 發卡銀行                                       │
│   ☑ HSBC                                        │
│   ☐ Citi                                        │
│   ☐ Standard Chartered                          │
│                                                 │
│ ▼ 結算機構                                       │
│   ☑ Mastercard                                  │
│   ☐ Visa                                        │
│                                                 │
│ ▼ 年費                                          │
│   ☐ 永久免年費                                   │
│   ☐ 首年免年費                                   │
│   ☐ 任何                                        │
│                                                 │
│ [Clear All]                                     │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Show rebates as: ⚪ Miles ⚪ Cash                │ ← Global toggle
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Showing 12 cards                                │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ "Your everyday spending companion"              │ ← Tagline
│ [Card Face Image 600x380]                       │
│ HSBC Red Card                                   │ ← Card Name
├─────────────────────────────────────────────────┤
│ 本地零售: 1% cashback                           │ ← Featured #1
│ 海外零售: 4% cashback                           │ ← Featured #2
│ 免費貴賓室: 每年3次                              │ ← Featured #3
│ 年費: HK$2,000 (免首年)                         │ ← Featured #4
├─────────────────────────────────────────────────┤
│ [View Details ▼]          [Apply Now →]         │
└─────────────────────────────────────────────────┘
     ↓ When "View Details" clicked
┌─────────────────────────────────────────────────┐
│ "Your everyday spending companion"              │
│ [Card Face Image]                               │
│ HSBC Red Card                    [Hide ▲]       │
├─────────────────────────────────────────────────┤
│ 發卡銀行: HSBC                                   │
│ 結算機構: Mastercard                             │
│                                                 │
│ 費用                                            │
│ ├─ 年費: HK$2,000 (新客免首年年費)               │
│ ├─ 外幣兌換手續費: 1.95%                         │
│ └─ 跨境結算手續費: 1%                            │
│                                                 │
│ 回贈                                            │
│ ├─ 本地零售簽賬: 1% 現金回贈                     │
│ ├─ 海外零售簽賬: 4%，每季上限回 HK$500            │
│ ├─ 網上港幣簽賬: 0.5%                           │
│ └─ ...                                          │
│                                                 │
│ 迎新優惠 (至 2026-03-31)                         │
│ └─ HK$800 現金回贈 (冷河期: 12 個月)              │
│                                                 │
│ 福利                                            │
│ ├─ 免費使用機場貴賓室: 每年3次                    │
│ └─ 免費旅遊保險: 需憑卡繳付機票                   │
│                                                 │
│ [了解更多 →] [Apply Now →]                      │
│ (links to blog post) (affiliate link)           │
└─────────────────────────────────────────────────┘

(Next card)
┌─────────────────────────────────────────────────┐
│ Citi Rewards Card                               │
│ ...                                             │
└─────────────────────────────────────────────────┘
```

**Desktop Adaptation:**
- Same structure, but cards can be 2-column grid
- Filter sidebar on left (always visible, not accordion)
- Global miles/cash toggle at top right

### 3.2 Blog Post Footer

**Shortcode:** `[cc_suggest]`

```
┌─────────────────────────────────────────────────┐
│ 相關信用卡推薦                                    │ ← Section title
└─────────────────────────────────────────────────┘

(Mobile: Vertical stack)
┌───────────────────┐
│ [Card Face]       │
│ HSBC Red Card     │
│ "Everyday saving" │
│ [View Details →]  │
└───────────────────┘
┌───────────────────┐
│ [Card Face]       │
│ Citi Rewards      │
│ ...               │
└───────────────────┘

(Desktop: Horizontal scroll or 3-5 column grid)
┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐
│ Card 1 │ │ Card 2 │ │ Card 3 │ │ Card 4 │
└────────┘ └────────┘ └────────┘ └────────┘
```

---

## 4. Shortcode Specifications

### 4.1 `[cc_suggest]` - Footer Suggestions

**Purpose:** Show 3-5 recommended cards in blog posts

**Parameters:**
```php
[cc_suggest 
  category="mastercard,visa"        // Multiple taxonomies (comma-separated)
  bank="hsbc"                       // Filter by bank
  metric="asia_miles_local"         // What to optimize for
  sort="local_spending_rebate_cash" // Field to sort by (use display name)
  order="desc"                      // asc or desc
  limit="5"                         // Number of cards (default: 5)
  exclude="123,456"                 // Card IDs to exclude
  layout="grid"                     // grid or carousel (default: grid)
]
```

**Default Behavior:**
- `limit`: 5
- `order`: desc
- `layout`: grid

**Metric Options:**
```
User-friendly names map to field keys:
- "asia_miles_local" → local_retail rebate calculated to Asia Miles
- "cashback_local" → local_retail_cash_sortable
- "cashback_overseas" → overseas_retail_cash_sortable
- "lounge_access" → lounge_access_sortable
- "annual_fee_low" → annual_fee_sortable ASC
```

**Example Usage:**
```
Blog post about Mastercard promotions:
[cc_suggest category="mastercard" limit="5"]

Blog post about Asia Miles:
[cc_suggest metric="asia_miles_local" order="desc" limit="5"]

Blog post about budget cards:
[cc_suggest sort="annual_fee" order="asc" limit="3"]
```

### 4.2 `[cc_comparison]` - Full Listing Page

**Purpose:** Display filterable, expandable card listing

**Parameters:**
```php
[cc_comparison
  category="airline_miles"          // Filter by taxonomy term
  bank="hsbc,citi"                  // Filter by bank (multiple)
  network="mastercard"              // Filter by card network
  filters="bank,network,annual_fee,min_income" // Which filters to show
  default_sort="local_rebate_cash"  // Initial sort field
  default_order="desc"              // Initial sort order
  show_toggle="true"                // Show miles/cash toggle (default: true)
  default_view="miles"              // Default: miles or cash (default: miles)
]
```

**Default Behavior:**
- `filters`: "bank,network,annual_fee"
- `default_sort`: "local_retail_cash_sortable"
- `default_order`: "desc"
- `show_toggle`: true
- `default_view`: "miles"

**Filter Options:**
```
Available filter keys:
- bank (發卡銀行 taxonomy)
- network (結算機構 taxonomy)
- annual_fee (永久免年費 / 首年免年費 / 任何)
- min_income (custom ranges: <50000, 50000-100000, >100000)
- lounge_access (有 / 無)
- points_system (現金回贈 / 積分系統)
```

**Example Usage:**
```
Best miles cards page:
[cc_comparison category="airline_miles" 
               filters="bank,network,annual_fee,min_income"
               default_sort="local_rebate_miles"]

All HSBC cards:
[cc_comparison bank="hsbc" 
               filters="network,annual_fee"]

Budget-friendly cards:
[cc_comparison filters="annual_fee,min_income"
               default_sort="annual_fee"
               default_order="asc"]
```

---

## 5. Points Conversion System

### 5.1 Auto-Calculation Logic

**When a card uses Points System:**

```
Input (by editor):
- points_system_id: 1 (AE MR Points)
- local_retail_points: "HK$1 = 3 MR 積分"

Backend extracts:
- earning_rate: 3 points per HK$1

Backend fetches from conversion table:
- Cash: 15000 points = HK$50
  → 1 point = HK$0.00333
  → 3 points per HK$1 = 0.01 = 1% cashback
  
- Asia Miles: 18000 points = 1000 miles
  → 1 point = 0.0556 miles
  → 3 points per HK$1 = 0.167 miles
  → HK$6 = 1 mile

- Marriott: 9000 points = 1000 points
  → 1 point = 0.111 Marriott points
  → 3 points per HK$1 = 0.333 Marriott points
  → HK$3 = 1 Marriott point

Auto-generated meta fields:
- local_retail_cash_sortable: 1.0
- local_retail_miles_display: "HK$6/里"
- local_retail_marriott_display: "HK$3/分"
```

**Frontend Display (when toggle = Miles):**
```
本地零售簽賬: HK$6/里 (Asia Miles)
```

**Frontend Display (when toggle = Cash):**
```
本地零售簽賬: 1% 現金回贈
```

### 5.2 Fallback Behavior

**If card has points_system_id but no Asia Miles conversion:**
- Show only cash rebate
- Hide miles toggle for this card

**If card has points_system_id = 0 (direct cash):**
- Show cash values
- Hide miles toggle

---

## 6. Miles/Cash Toggle System

### 6.1 Toggle Placement

**Global Toggle (affects all cards):**
```html
<div class="rebate-toggle-global">
  Show rebates as: 
  <input type="radio" name="view_mode" value="miles" checked> Miles
  <input type="radio" name="view_mode" value="cash"> Cash
</div>
```

**Per-Card Override (future feature, not Phase 1):**
- User can override global setting per card
- Saved in localStorage

### 6.2 Toggle Behavior

**When "Miles" selected:**
1. Check if card has `points_system_id > 0`
2. Check if Asia Miles conversion exists
3. If yes: Display miles values (e.g., "HK$6/里")
4. If no: Fall back to cash display

**When "Cash" selected:**
1. Display cash rebate values
2. Works for both points-based and direct-cash cards

**Hide Toggle Condition:**
- If card has `points_system_id = 0` AND no other cards on page have points systems
- Toggle becomes irrelevant

---

## 7. Click Tracking System

### 7.1 Tracking Implementation

**Frontend JavaScript:**
```javascript
// On affiliate link click
document.querySelectorAll('.card-apply-link').forEach(link => {
  link.addEventListener('click', function(e) {
    const cardId = this.dataset.cardId;
    const sourceUrl = window.location.href;
    
    // AJAX call to backend
    fetch(ajaxurl, {
      method: 'POST',
      body: new URLSearchParams({
        action: 'track_card_click',
        card_id: cardId,
        source_url: sourceUrl
      })
    });
    
    // Continue with link opening in new tab
  });
});
```

**Backend Handler:**
```php
add_action('wp_ajax_track_card_click', 'handle_card_click_tracking');
add_action('wp_ajax_nopriv_track_card_click', 'handle_card_click_tracking');

function handle_card_click_tracking() {
    global $wpdb;
    
    $card_id = intval($_POST['card_id']);
    $source_url = esc_url($_POST['source_url']);
    
    $wpdb->insert(
        $wpdb->prefix . 'card_clicks',
        array(
            'card_id' => $card_id,
            'source_url' => $source_url,
            'clicked_at' => current_time('mysql')
        )
    );
    
    wp_send_json_success();
}
```

### 7.2 Analytics Queries

**Top Cards by Clicks:**
```sql
SELECT 
    c.card_id,
    p.post_title,
    COUNT(*) as click_count,
    (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM wp_card_clicks WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))) as click_rate
FROM wp_card_clicks c
JOIN wp_posts p ON c.card_id = p.ID
WHERE c.clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY c.card_id
ORDER BY click_count DESC
LIMIT 10;
```

**Top Source Pages:**
```sql
SELECT 
    source_url,
    COUNT(*) as total_clicks
FROM wp_card_clicks
WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY source_url
ORDER BY total_clicks DESC
LIMIT 10;
```

---

## 8. PublishPress Integration

### 8.1 Enable Custom Post Type Support

```php
function register_card_post_type() {
    $args = array(
        'label' => '信用卡',
        'public' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'revisions', 'custom-fields'),
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'cards'),
        'capability_type' => 'post',
        // CRITICAL for PublishPress
        'revisions' => true,
    );
    
    register_post_type('card', $args);
    
    // Tell PublishPress to support 'card' post type
    add_filter('publishpress_revisions_post_types', function($post_types) {
        $post_types[] = 'card';
        return $post_types;
    });
}
add_action('init', 'register_card_post_type');
```

### 8.2 Scheduled Update Workflow

**Editor Workflow:**
1. Edit card (e.g., Card ID 123)
2. Change "迎新禮物" field
3. Click "Save as Revision" (PublishPress button)
4. Set "Publish on: 2026-03-01 00:00"
5. Revision is queued

**On scheduled date:**
- PublishPress auto-publishes the revision
- Card post is updated with new values
- Old version is saved in revision history

### 8.3 Expiry Warning System

**Custom Admin Notice:**
```php
add_action('admin_notices', 'card_expiry_warnings');

function card_expiry_warnings() {
    if (get_current_screen()->post_type !== 'card') return;
    
    global $wpdb;
    $expiring_soon = $wpdb->get_results("
        SELECT p.ID, p.post_title, pm.meta_value as expiry
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'card'
        AND pm.meta_key = 'welcome_offer_expiry'
        AND pm.meta_value BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    
    if ($expiring_soon) {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>迎新優惠即將到期：</strong></p><ul>';
        foreach ($expiring_soon as $card) {
            echo '<li>' . esc_html($card->post_title) . ' - 到期日: ' . esc_html($card->expiry) . '</li>';
        }
        echo '</ul></div>';
    }
}
```

---

## 9. Schema.org Implementation

### 9.1 FinancialProduct Schema

**Output on single card pages:**
```php
function output_card_schema($post_id) {
    $card_name = get_the_title($post_id);
    $bank = get_the_terms($post_id, 'card_bank')[0]->name;
    $network = get_the_terms($post_id, 'card_network')[0]->name;
    $annual_fee = get_post_meta($post_id, 'annual_fee_display', true);
    $image = get_the_post_thumbnail_url($post_id, 'full');
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'FinancialProduct',
        'name' => $card_name,
        'category' => 'CreditCard',
        'provider' => array(
            '@type' => 'BankOrCreditUnion',
            'name' => $bank
        ),
        'image' => $image,
        'url' => get_permalink($post_id),
        'feesAndCommissionsSpecification' => $annual_fee,
        'brand' => $network
    );
    
    echo '<script type="application/ld+json">' . json_encode($schema) . '</script>';
}
```

### 9.2 Auto-Generated Meta Description

```php
function generate_card_meta_description($post_id) {
    $card_name = get_the_title($post_id);
    $bank = get_the_terms($post_id, 'card_bank')[0]->name;
    $tagline = get_post_meta($post_id, 'tagline', true);
    $annual_fee = get_post_meta($post_id, 'annual_fee_display', true);
    $local_rebate = get_post_meta($post_id, 'local_retail_cash_display', true);
    
    $description = sprintf(
        '%s %s - %s。年費：%s，本地簽賬回贈：%s。立即申請！',
        $bank,
        $card_name,
        $tagline,
        $annual_fee,
        $local_rebate
    );
    
    // Truncate to 160 characters
    if (strlen($description) > 160) {
        $description = mb_substr($description, 0, 157) . '...';
    }
    
    return $description;
}

add_action('wp_head', function() {
    if (is_singular('card')) {
        $description = generate_card_meta_description(get_the_ID());
        echo '<meta name="description" content="' . esc_attr($description) . '">';
    }
});
```

---

## 10. Technical Requirements

### 10.1 Performance Optimization

**Query Caching:**
```php
// Cache card queries for 1 hour
function get_cards_cached($args) {
    $cache_key = 'cards_' . md5(serialize($args));
    $cards = wp_cache_get($cache_key, 'card_queries');
    
    if (false === $cards) {
        $cards = new WP_Query($args);
        wp_cache_set($cache_key, $cards, 'card_queries', 3600);
    }
    
    return $cards;
}
```

**Image Optimization:**
- Featured images automatically compressed via Cloudflare
- Lazy loading: `loading="lazy"` attribute on all card images
- WebP support: Serve WebP if browser supports

**AJAX Filtering:**
- Filter changes don't reload page
- Use REST API endpoints for real-time filtering
- Debounce filter inputs (300ms delay)

### 10.2 Security

**Nonce Verification:**
```php
// All AJAX handlers must verify nonce
if (!wp_verify_nonce($_POST['nonce'], 'card_action_nonce')) {
    wp_send_json_error('Invalid nonce');
}
```

**Sanitization:**
```php
// All user inputs sanitized
$card_id = intval($_POST['card_id']);
$source_url = esc_url($_POST['source_url']);
$display_value = sanitize_text_field($_POST['display_value']);
```

**Capability Checks:**
```php
// Only editors and admins can manage cards
if (!current_user_can('edit_posts')) {
    wp_die('Unauthorized');
}
```

### 10.3 Mobile Optimization

**Responsive Images:**
```php
// Use srcset for card images
the_post_thumbnail('card-thumb', array(
    'sizes' => '(max-width: 600px) 100vw, 600px',
    'srcset' => '...'
));
```

**Touch-Friendly UI:**
- Minimum tap target: 44x44px
- Swipe gestures for carousel layout
- Bottom sheet for filters on mobile

### 10.4 Browser Compatibility

**Minimum Support:**
- Chrome 90+
- Safari 14+
- Firefox 88+
- Edge 90+
- Mobile: iOS 14+, Android 10+

**Polyfills:**
- Fetch API polyfill for older browsers
- IntersectionObserver polyfill for lazy loading

### 10.5 Accessibility (Basic)

While WCAG 2.1 full compliance is not required, implement:
- Alt text on all card images
- ARIA labels on buttons
- Keyboard-accessible filter controls
- Focus indicators on interactive elements

---

## 11. Development Phases

### Phase 1: Core Functionality (4-6 weeks)

**Week 1-2: Database & Admin Foundation**
- [ ] Create custom database tables
- [ ] Register 'card' custom post type
- [ ] Register taxonomies (card_bank, card_network)
- [ ] Build Points Systems admin page
- [ ] Implement all post meta fields

**Week 3-4: Admin UI**
- [ ] Tabbed edit screen
- [ ] Featured parameters selection
- [ ] Points system dropdown integration
- [ ] Auto-calculation for points → rebates
- [ ] Dual field system (display + sortable)

**Week 5-6: Basic Frontend**
- [ ] `[cc_suggest]` shortcode
- [ ] `[cc_comparison]` shortcode
- [ ] Basic listing layout (no filters yet)
- [ ] Expandable card details
- [ ] Click tracking JavaScript

### Phase 2: UX & Filtering (3-4 weeks)

**Week 7-8: Filters & Toggle**
- [ ] Accordion filter UI (mobile)
- [ ] Real-time AJAX filtering
- [ ] Miles/Cash global toggle
- [ ] Filter state persistence

**Week 9-10: Polish**
- [ ] Mobile-first styling (Flatsome theme integration)
- [ ] Desktop layout adaptation
- [ ] Loading states & animations
- [ ] Error handling

### Phase 3: PublishPress & Scheduling (2-3 weeks)

**Week 11-12: Integration**
- [ ] PublishPress custom post type support
- [ ] Expiry date warnings
- [ ] Scheduled revision workflow testing

**Week 13: Admin Tools**
- [ ] Analytics dashboard
- [ ] Click tracking reports
- [ ] Date range filters

### Phase 4: SEO & Optimization (1-2 weeks)

**Week 14: SEO**
- [ ] Schema.org implementation
- [ ] Auto-generated meta descriptions
- [ ] Breadcrumb integration

**Week 15: Performance**
- [ ] Query caching
- [ ] Image optimization checks
- [ ] AJAX debouncing
- [ ] Final testing

**Total Timeline: 15 weeks**

---

## 12. Testing Checklist

### Admin Testing
- [ ] Create card with all fields filled
- [ ] Create card with minimal required fields
- [ ] Create Points System with multiple conversions
- [ ] Assign card to Points System
- [ ] Verify auto-calculation of rebates
- [ ] Test featured parameters selection
- [ ] Schedule card update via PublishPress
- [ ] View analytics dashboard

### Frontend Testing
- [ ] `[cc_suggest]` displays 5 cards correctly
- [ ] `[cc_comparison]` shows all cards
- [ ] Filters work in real-time
- [ ] Miles/Cash toggle switches values
- [ ] Expand/collapse card details
- [ ] Click "Apply Now" triggers tracking
- [ ] All links open in new tab
- [ ] Mobile responsive layout
- [ ] Desktop 2-column grid

### Edge Cases
- [ ] Card with no points system (direct cash)
- [ ] Card with points but no Asia Miles conversion
- [ ] Card with empty featured parameter (shows N/A)
- [ ] Filter with zero results (show message)
- [ ] Expired welcome offer (show warning)
- [ ] Very long card name (text truncation)
- [ ] Very long bank name in filter (scrollable)

### Browser Testing
- [ ] Chrome (desktop & mobile)
- [ ] Safari (desktop & mobile)
- [ ] Firefox
- [ ] Edge

---

## 13. Code Standards

**Naming Conventions:**
- Functions: `card_get_rebate_value()`
- Files: `class-card-admin.php`
- CSS classes: `.card-listing-item`
- JavaScript: `cardClickTracking()`

**File Structure:**
```
hk-card-compare/
├── hk-card-compare.php (main plugin file)
├── includes/
│   ├── class-card-post-type.php
│   ├── class-card-taxonomy.php
│   ├── class-points-system.php
│   ├── class-card-meta.php
│   ├── class-click-tracker.php
│   └── class-schema-output.php
├── admin/
│   ├── class-card-admin.php
│   ├── class-points-admin.php
│   ├── class-analytics-admin.php
│   ├── css/
│   └── js/
├── public/
│   ├── class-card-shortcodes.php
│   ├── class-card-display.php
│   ├── css/
│   └── js/
└── templates/
    ├── single-card.php
    ├── card-listing-item.php
    └── card-comparison.php
```

**WordPress Coding Standards:**
- Follow WordPress PHP Coding Standards
- Use WordPress sanitization/escaping functions
- Use WP_Query instead of raw SQL where possible
- Add inline documentation (PHPDoc)

---

## 14. Future Enhancements (Not in Phase 1-4)

**Documented for future reference:**

1. **Smart Recommendations**
   - Monthly spending input
   - Primary use dropdown
   - Auto-calculate best value cards
   - User profile system (optional)

2. **Advanced Comparison**
   - Side-by-side comparison (desktop only)
   - "Save for later" functionality
   - Share comparison link
   - Print comparison view

3. **Calculation Tools**
   - "Input spending → See rebate" calculator
   - Annual value estimator
   - Break-even analysis (annual fee vs rebate)

4. **Admin Enhancements**
   - Bulk edit cards
   - Card templates (copy from existing)
   - CSV import
   - Duplicate card function

5. **User Features**
   - User reviews/ratings
   - Card application tracking
   - Email alerts for promotions
   - Personalized recommendations

---

## 15. Deliverables Summary

This specification provides:

✅ **Complete database schema** (3 custom tables, all meta fields)
✅ **Detailed admin UI wireframes** (Points Systems, Analytics, Edit Screen)
✅ **Frontend layout specifications** (mobile-first, expandable cards)
✅ **Shortcode documentation** (parameters, examples, defaults)
✅ **Points conversion logic** (auto-calculation, fallbacks)
✅ **Click tracking implementation** (JavaScript + PHP)
✅ **PublishPress integration** (custom post type support)
✅ **Schema.org markup** (FinancialProduct schema)
✅ **Performance guidelines** (caching, optimization)
✅ **Development timeline** (15-week phased approach)
✅ **Testing checklist** (admin, frontend, edge cases)
✅ **Code structure** (file organization, naming conventions)

**Ready for handoff to Claude Code or development team.**

---

## Questions for Developer/Claude Code

Before starting development, please confirm:

1. **WordPress Environment Setup:** Do you need installation instructions?
2. **Development vs. Production:** Will you build on local/staging first?
3. **Plugin Slug:** Confirm final plugin name/slug
4. **Text Domain:** For translations (e.g., 'hk-card-compare')
5. **Minimum PHP Version:** 8.0+ acceptable?
6. **Database Prefix:** Use standard `{$wpdb->prefix}`?
7. **Asset Enqueuing:** Use wp_enqueue_script/style best practices?
8. **REST API:** Should filters use REST endpoints or AJAX?

Please proceed with development based on this specification. Flag any ambiguities or technical blockers as you encounter them.

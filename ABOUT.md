# 🚀 QuickB2B — Smart Bulk Ordering for Shopify

> **Built for Wholesale. Designed for Speed. Loved by Everyone.**

---

## 🧠 Philosophy

QuickB2B was born from a simple question: **"Why is B2B ordering so painful on Shopify?"**

Wholesale customers don't browse — they **re-order**. They know exactly what they want, in what quantities, and they want it done in 30 seconds flat. Yet most Shopify stores make them click through product pages one by one, filling a cart like a retail shopper.

QuickB2B flips the model:

| Traditional B2B                      | QuickB2B Way                                     |
| :----------------------------------- | :----------------------------------------------- |
| Browse → Product Page → Add → Repeat | **One table. Type quantities. Done.**            |
| Cart-based (fails on private stores) | **Draft Orders (always works)**                  |
| Manual quoting via email/phone       | **Self-serve CSV upload + instant invoice**      |
| "Call for pricing"                   | **Live prices, auto currency, stock visibility** |

### Our Core Beliefs

1. **Speed > Everything** — A wholesale order should take seconds, not minutes
2. **Merchant Control** — You decide what customers see, not the other way around
3. **Reliability First** — Draft Orders beat Cart API every time. No failed checkouts.
4. **Dead Simple UX** — If a warehouse manager can't figure it out in 5 seconds, we've failed
5. **One Click Setup** — No developer needed. Install → Create Page → Live.

---

## 👩‍💼 For Merchants (Store Owners)

### Why QuickB2B?

You run a wholesale or B2B store. Your customers are retailers, restaurants, institutions — they order in bulk, they re-order the same SKUs every week, and they don't have time to browse.

QuickB2B gives them a **spreadsheet-like ordering table** right on your storefront. They type quantities, upload a CSV from their ERP, and place an order in one click. No cart. No checkout drama. No "can you send me a quote?" emails at 11 PM.

### ✨ Key Features

#### 📄 One-Click Setup

Hit **"Create Page"** in the dashboard. QuickB2B:

- Creates an optimized ordering page on your store
- Adds it to your main navigation menu automatically
- The page auto-redirects to the bulk order table

Zero code. Zero theme edits. Done in 10 seconds.

---

#### 📊 Smart Bulk Order Table

Your entire product catalog in one searchable, paginated table:

- **Grouped by product** — variants nested under parent products (clean & organized)
- **Real-time search** — filter by product name, SKU, tag, or collection
- **Pagination** — 50/100/200/500 products per page (handles 100k+ catalogs)
- **Live stock pills** — 🟢 In Stock / 🟠 Low Stock / 🔴 Out of Stock
- **Auto currency** — Detects your store currency (35+ supported)

---

#### 📤 CSV Upload (The Killer Feature)

Your customers already have purchase orders in Excel. Let them upload directly:

1. Download the sample CSV from the Quick Order page
2. Fill in SKU, product name, or variant ID + quantity + tag
3. Upload → products are matched automatically
4. Quantities populate the table instantly

Supports: SKU matching, product name matching (all variants), GID matching, tag-based bulk add.

---

#### 📧 Draft Orders + Email Invoices

Unlike "Add to Cart" (which fails on password-protected stores), Draft Orders:

- ✅ Work on **password-protected / private stores**
- ✅ Send an **invoice email** to the customer automatically
- ✅ Appear in your Shopify Admin → Draft Orders
- ✅ You review, adjust pricing, and send the final invoice

**Large orders?** QuickB2B automatically splits orders exceeding 499 line items into multiple draft orders with a 60-second delay between each (Shopify rate limit safe).

---

#### 🔢 Min/Max Quantity Control

Set global limits per variant:

- **Minimum Qty** — e.g., "Customer must order at least 12 units per SKU"
- **Maximum Qty** — e.g., "No more than 500 units per SKU"

Enforced on the page (HTML validation) **and** on the server (cannot be bypassed).

---

#### 🖼️ Product Images

Toggle product thumbnails (100×100, lazy-loaded, CDN-optimized) on the ordering table. Helpful for customers who recognize products visually.

⚠️ Images require a one-time catalog refresh after enabling.

---

#### 🏷️ Tag-Based Hiding

Hide specific products from the Quick Order page by tag:

- `clearance`, `discontinued`, `wholesale-only`, `internal-use`
- Comma-separated in Settings → instantly applied

---

#### 👁️ Column Visibility

Toggle columns on/off per your preference:

- **SKU Column** — Hide if customers don't need SKUs
- **Stock Column** — Hide if you don't want to expose inventory counts
- **Product Images** — On/Off with catalog refresh

---

#### 🔄 Auto-Syncing Catalog

- Uses Shopify's **Bulk Operation API** (GraphQL) — handles 100,000+ products
- Progress tracking with real-time polling
- Cache stored as JSON for instant loading
- Refresh any time from Settings or Dashboard

---

#### 🛡️ GDPR Compliant

All 3 mandatory GDPR webhooks implemented:

- `shop/redact` — Permanent data deletion (48h after uninstall)
- `customers/redact` — Customer data purge
- `customers/data_request` — Customer data export

App Store ready. ✅

---

### 📦 Plan & Pricing

| Plan       | Price           | Features                                                                                                                                   |
| :--------- | :-------------- | :----------------------------------------------------------------------------------------------------------------------------------------- |
| **Pro**    | **$9.99/month** | Everything: bulk table, CSV upload, draft orders, email invoices, images, min/max qty, stock control, currency detection, priority support |
| Free Trial | 7 days          | Full Pro features, no credit card required                                                                                                 |

Cancel anytime. No lock-in.

---

### ⚠️ Before Uninstalling

Use the **"Delete Page"** button in Dashboard or Plan page to clean up:

- Removes the Quick Order page from your store
- Removes the navigation menu link
- Then uninstall safely

_(Shopify revokes API access on uninstall, so the app can't clean up after itself — do this first!)_

---

## 🧑‍💼 For Customers (Wholesale Buyers)

### Your Ordering Experience

You're a restaurant manager, retailer, or institution buyer. You order from this store every week. Here's how QuickB2B makes your life easy:

### 🏃‍♂️ Quick Order Flow

#### Method 1: Type & Go

1. Open the Quick Order page
2. Search for products (or scroll through)
3. Type quantities next to each variant
4. Click **"Draft Order"** → enter your email → Done!
5. Check your email for the invoice link

**Time: ~30 seconds for a 50-line order.**

---

#### Method 2: CSV Upload (Power Users)

1. Download the sample CSV
2. Column 1 = SKU or product name | Column 3 = Quantity (optional, defaults to 1) | Column 4 = Tag (optional)
3. Upload → quantities auto-fill
4. Click **"Draft Order"** → enter email → Done!

**Time: ~10 seconds. Ideal for weekly re-orders.**

---

#### Method 3: Select All

1. Click **"Select All"** — populates min qty for every product
2. Adjust quantities as needed
3. Place order

**Time: ~5 seconds if you want everything.**

---

### 📊 Understanding the Table

| Column                | What It Shows                                           |
| :-------------------- | :------------------------------------------------------ |
| **Image** (optional)  | Product thumbnail (100×100)                             |
| **Product / Variant** | Product name (bold header) + variant names below        |
| **SKU** (optional)    | Stock Keeping Unit code                                 |
| **Price**             | Live price in store currency (₹, $, €, £, etc.)         |
| **Stock** (optional)  | 🟢 In Stock (qty) / 🟠 Low (<10) / 🔴 Out / ∞ Unlimited |
| **Qty**               | Your order quantity (type or upload)                    |

---

### 🔍 Search & Filter

- **All** — Search across name, SKU, tags, collections
- **Product Name** — Search by title only
- **SKU** — Find by SKU code
- **Tag** — Filter by product tag
- **Collection** — Filter by collection name

Results update as you type.

---

### 🛒 Three Ways to Order

| Method              | Best For                  | Limit                         | Works On                      |
| :------------------ | :------------------------ | :---------------------------- | :---------------------------- |
| **📧 Draft Order**  | Everyone. Most reliable.  | 500 items/order (auto-splits) | ✅ All stores (incl. private) |
| **📦 Bulk to Cart** | Stores with AJAX cart API | 50 items/batch                | ⚠️ Most stores                |
| **🔗 Quick Add**    | Small orders, fastest     | 200 items                     | ✅ Public stores              |

**Recommendation**: Always use **Draft Order**. It's the most reliable and sends you an invoice email.

---

### 📧 What Happens After You Order?

1. **Draft Order**: You get an email with an invoice link. The merchant reviews and finalizes the order. You pay from the invoice.
2. **Bulk to Cart**: Items go to your Shopify cart. Proceed to checkout normally.
3. **Quick Add**: Redirects to cart with items pre-filled.

---

### ❓ Customer FAQ

**Q: Can I save my cart for later?**
A: Draft Orders are saved in the merchant's admin. Your quantities are remembered on the page until you clear them.

**Q: What if a product is out of stock?**
A: OOS items are hidden by default. You won't see products with zero inventory (unless the merchant allows backorders).

**Q: Can I upload my purchase order from Excel?**
A: Yes! Download the sample CSV, match the format, upload. Products are matched by SKU, name, or ID.

**Q: Is my email shared?**
A: Only with the merchant whose store you're ordering from. We don't store it.

**Q: What if I need to order 500+ items?**
A: QuickB2B automatically splits large orders into multiple draft orders. You'll get separate invoices.

---

## 🏗️ Tech Stack (For Developers)

| Layer                     | Technology                                                  |
| :------------------------ | :---------------------------------------------------------- |
| **Backend**               | Laravel 12 + PHP 8.4                                        |
| **Shopify SDK**           | kyon147/laravel-shopify v26.1                               |
| **API**                   | Shopify GraphQL (2026-04) + Bulk Operations                 |
| **Database**              | MySQL (JSON columns for flexible settings)                  |
| **Queue**                 | Database driver (jobs: catalog refresh, draft orders, GDPR) |
| **Frontend (Admin)**      | Polaris Web Components (Shopify App Bridge)                 |
| **Frontend (Storefront)** | Vanilla JS IIFE + Liquid template                           |
| **Tunneling**             | Cloudflare Tunnel / ngrok (dev)                             |

---

## 📁 Project Structure

```
app/
  Http/
    Controllers/
      QuickOrderController.php    # Storefront API + draft orders
      SettingsController.php      # Per-shop settings CRUD
      SetupController.php         # One-click page + menu setup
      PageController.php          # Page CRUD (title, delete, sync, menu)
    Middleware/
      AuthWebhookGdpr.php         # HMAC verification for GDPR webhooks
  Jobs/
    RefreshProductCacheJob.php    # Bulk Operation API → JSON cache
    CreateDraftOrderJob.php       # Chunked draft order creation
    AppUninstalledJob.php         # Storage cleanup on uninstall
    GdprShopRedactJob.php         # GDPR: permanent data deletion
    GdprCustomerRedactJob.php     # GDPR: customer data purge
    GdprCustomerDataRequestJob.php # GDPR: data export request
  Models/
    QuickOrderPage.php            # Tracks created Shopify page
    QuickOrderSetting.php         # JSON settings with defaults merge
  Services/
    ShopifyGraphQL.php            # All GraphQL operations (pages, menus, products, draft orders)
resources/views/
  welcome.blade.php               # Admin Dashboard
  settings/index.blade.php        # Settings page
  billing/plans.blade.php         # Plan & billing page
  help/index.blade.php            # Help & FAQ page
  quick-order/
    proxy.blade.php               # Storefront Quick Order page (Liquid)
    partials/
      scripts.blade.php           # Vanilla JS (IIFE) — table, cart, CSV, search, pagination
      styles.blade.php            # Scoped CSS — mobile responsive
  components/
    nav-menu.blade.php            # Admin navigation
```

---

## 🔒 Security & Compliance

- ✅ Shopify HMAC signature verification on all proxy requests
- ✅ GDPR webhooks: `shop/redact`, `customers/redact`, `customers/data_request`
- ✅ CSRF protection with selective exemptions for API/webhook routes
- ✅ Server-side validation on all quantity inputs (cannot bypass client limits)
- ✅ App Bridge session tokens on all admin POST requests
- ✅ Billable middleware — storefront & admin locked behind active subscription

---

## 🛣️ Roadmap (Future)

- [ ] Order History — customers see past orders + re-order in one click
- [ ] Multi-location inventory support
- [ ] Tiered pricing (quantity breaks)
- [ ] Customer-specific catalogs
- [ ] PDF invoice generation
- [ ] Multi-language support (i18n)
- [ ] Webhooks for order status updates
- [ ] Analytics dashboard (orders/week, top products, etc.)

---

## 📞 Support

- **Documentation**: Help page inside the app (`/help`)
- **Email**: `pixiestoresupport@gmail.com`
- **Setup Guide**: Dashboard → Create Page → Done!

---

<p align="center">
  <strong>QuickB2B</strong> — Built with ❤️ for wholesale merchants everywhere.
  <br>
  <sub>Ship faster. Sell smarter. Stress less.</sub>
</p>

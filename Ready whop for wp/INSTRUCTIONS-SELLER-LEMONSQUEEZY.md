# Whop Checkout — Seller Guide (Lemon Squeezy)

**For you** — the person selling Whop Checkout.  
Use **Lemon Squeezy** to receive payments and deliver the ZIP (same approach as whopwoocommerce.com).

Instructions stay **in this folder**, not inside the plugin ZIP.

---

## Two different "Whop" roles (don't mix them up)

| Who | Platform | Purpose |
|-----|----------|---------|
| **You (plugin seller)** | **Lemon Squeezy** | Buyers pay $170 for the plugin; you get paid in PKR |
| **Your buyer (store owner)** | **Whop API** | Their customers pay on WooCommerce; money goes to *their* Whop account |

---

## Folder layout

```
Ready whop for wp/
├── whop-checkout-5.0.1.zip              ← Upload to Lemon Squeezy
├── whop-gateway-wc/                     ← Source (same as ZIP contents)
├── INSTRUCTIONS-BUYER-SETUP.md          ← Send/link to buyers
├── INSTRUCTIONS-SELLER-LEMONSQUEEZY.md  ← This file
├── INSTRUCTIONS-SELLER-WHOP.md          ← Old Whop-seller guide (optional)
├── AUDIT-REPORT.md
└── README.md
```

**Rule:** Upload **`whop-checkout-5.0.1.zip`** to Lemon Squeezy only. Do **not** zip instruction files with the plugin.

---

## Step 1 — Customize the plugin (once)

Before your first sale, edit the plugin header in `whop-gateway-wc/whop-gateway-wc.php`:

```php
 * Author:            Your Brand
```

Change to your business name. Rebuild the ZIP after editing (see Step 7).

---

## Step 2 — Create Lemon Squeezy account & payouts

1. Sign up at [lemonsqueezy.com](https://www.lemonsqueezy.com)
2. Complete identity verification
3. **Settings → Payouts** → link **Pakistani bank (PKR)** or PayPal
4. Optional: **Settings → Stores** → set store name (e.g. WhopCheckout)

Pakistan is on Lemon Squeezy's supported bank payout list. See `funnel-site/PAKISTAN-PAYMENTS.md` for fee and schedule details.

---

## Step 3 — Create the product

1. **Products → New product**
2. **Product type:** Standard digital product
3. **Pricing:** One-time · **$170 USD**
4. **Files:** Upload **`whop-checkout-5.0.1.zip`**
5. **License keys:** Enable if you want auto-generated keys (optional; plugin does not phone home)

### Product name
```
Whop Checkout
```

### Description (paste into Lemon Squeezy)
```
Whop Checkout adds Whop card & crypto payments to any WooCommerce store.

WHAT YOU GET
• WordPress plugin (GPL)
• 5-step Setup Wizard — no coding
• Auto webhook registration
• Sandbox testing mode
• Webhook-verified orders (secure)
• Lifetime plugin access
• Email support

REQUIREMENTS
• WordPress 6+ and WooCommerce 7+
• HTTPS on your live site
• Your own Whop business account (free to create)
• Classic WooCommerce checkout

SETUP TIME: About 5 minutes using the built-in wizard.

Payments from YOUR customers go to YOUR Whop account — not ours.

After purchase, download the plugin ZIP from your Lemon Squeezy receipt email.
```

6. **Publish** the product
7. **Share → Copy checkout URL** (looks like `https://yourstore.lemonsqueezy.com/checkout/buy/...`)

---

## Step 4 — Connect the funnel site

Edit `funnel-site/config.js`:

```js
paymentProvider: 'Lemon Squeezy',
plan: {
  label: 'Lifetime',
  price: '$170',
  period: 'one-time',
  checkoutUrl: 'PASTE-YOUR-LEMON-SQUEEZY-CHECKOUT-URL-HERE',
  note: 'Pay once · Keep forever · Instant download',
},
```

Deploy `funnel-site/` to **WhopCheckout.com** (Netlify Drop, Cloudflare Pages, etc.).

---

## Step 5 — Test before going live

1. Lemon Squeezy **Test mode** → make a test purchase
2. Confirm ZIP downloads and email arrives
3. Install ZIP on a test WordPress site
4. Run full sandbox checkout with your Whop test keys
5. Switch Lemon Squeezy to **Live mode**

---

## Step 6 — Welcome email (optional customization)

Lemon Squeezy sends order confirmation automatically. You can add a **confirmation email** in the product settings:

**Subject:** Your Whop Checkout download + setup steps

---

Hi,

Thanks for purchasing **Whop Checkout**!

**1. Download the plugin**  
Use the download link in your Lemon Squeezy receipt (or your account dashboard).

**2. Install on WordPress**  
Plugins → Add New → Upload Plugin → Activate

**3. Run Setup Wizard**  
WooCommerce → Whop Checkout → Setup Wizard

**4. Full setup guide**  
[Link to INSTRUCTIONS-BUYER-SETUP.md on your site or Notion]

**Quick steps:**
• Whop.com → Settings → API Keys → create key + copy Company ID  
• Wizard Step 2: paste API key + Company ID  
• Wizard Step 3: Auto-register webhook  
• Wizard Step 5: sandbox test → turn sandbox OFF for live sales

**Need help?** Reply to hello@whopcheckout.com with your site URL and a screenshot of Whop Checkout → Health (no API keys in screenshots).

— WhopCheckout.com

---

## Step 7 — Rebuild the ZIP (after code changes)

From Terminal on Mac:

```bash
cd "/Users/lapteck/Desktop/My whop/Ready whop for wp"
rm -f whop-checkout-5.0.1.zip
zip -r whop-checkout-5.0.1.zip whop-gateway-wc \
  -x "*.DS_Store" \
  -x "*/__MACOSX/*"
```

Upload the new ZIP to Lemon Squeezy (replace file on product). Email existing buyers if you ship a major update.

---

## Step 8 — Pre-launch checklist

- [ ] Author name updated in plugin header
- [ ] Fresh ZIP built (`whop-checkout-5.0.1.zip`)
- [ ] Test install on clean WordPress + WooCommerce
- [ ] Complete sandbox order end-to-end
- [ ] Lemon Squeezy product live at **$170 lifetime**
- [ ] Checkout URL in `funnel-site/config.js`
- [ ] Funnel deployed to WhopCheckout.com
- [ ] Test purchase in LS test mode
- [ ] Buyer setup guide shared (link in LS email or your site)
- [ ] Support email ready (hello@whopcheckout.com)

---

## Step 9 — Support playbook

When a buyer writes in, ask for:

1. WordPress version
2. WooCommerce version
3. Plugin version (5.0.1)
4. Screenshot of **WooCommerce → Whop Checkout → Health**
5. Whether sandbox is on or off
6. One affected order number (not API keys)

### Common fixes

| Symptom | Fix |
|---------|-----|
| Pending orders | Webhook secret + HTTPS + firewall |
| No payment method at checkout | Enable gateway; complete wizard |
| API 401 | Regenerate Whop API key; check Company ID |
| Blocks checkout | Use classic checkout |
| Can't download ZIP | Resend from Lemon Squeezy order; check spam |

Point buyers to **INSTRUCTIONS-BUYER-SETUP.md** for full steps.

---

## What you do NOT need

- OAuth / Connect server
- Whop product for selling the plugin (unless you want a second channel)
- License validation server
- GitHub/Vercel/Railway for this edition

---

## Business model reminder

| Role | Who | Gets paid via |
|------|-----|---------------|
| **You** | Sell the plugin | Lemon Squeezy → your PK bank |
| **Buyer** | Runs WooCommerce store | Their Whop account |
| **Buyer's customers** | Shop on buyer's site | Buyer's Whop checkout |

---

## Updating the product later

1. Bump version in `whop-gateway-wc.php` and `readme.txt`.
2. Fix bugs, rebuild ZIP with new version number.
3. Upload new ZIP to Lemon Squeezy product.
4. Email existing buyers with changelog (per your lifetime license terms).

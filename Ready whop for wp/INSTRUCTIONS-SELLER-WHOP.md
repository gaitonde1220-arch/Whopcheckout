# Whop Checkout — Seller Guide (List, Deliver, Support)

**For you** — the person selling Whop Checkout on Whop.  
Instructions and marketing copy stay **in this folder**, not inside the plugin ZIP.

---

## Folder layout

```
Ready whop for wp/
├── whop-checkout-5.0.1.zip       ← Give this to buyers ONLY
├── whop-gateway-wc/              ← Source (same as ZIP contents)
├── INSTRUCTIONS-BUYER-SETUP.md   ← Send/link to buyers
├── INSTRUCTIONS-SELLER-WHOP.md   ← This file
├── AUDIT-REPORT.md               ← Security & QA audit
└── README.md                     ← Quick overview
```

**Rule:** Upload **`whop-checkout-5.0.1.zip`** to Whop. Do **not** zip the instruction files with the plugin.

---

## Step 1 — Customize the plugin (once)

Before your first sale, edit the plugin header in `whop-gateway-wc/whop-gateway-wc.php`:

```php
 * Author:            Your Brand
```

Change to your business name. Rebuild the ZIP after editing (see Step 6).

Optional: add your support URL as default in settings or in buyer email.

---

## Step 2 — Create the Whop product

1. Log in to [Whop](https://whop.com) → your business dashboard.
2. **Products → Create product** (or edit existing).
3. Use the copy below.

### Product name
```
Whop Checkout
```

### Headline
```
Accept Whop payments on WooCommerce in 5 minutes
```

### Description (paste)
```
Whop Checkout adds Whop card & crypto payments to any WooCommerce store.

WHAT YOU GET
• WordPress plugin (GPL)
• 5-step Setup Wizard — no coding
• Auto webhook registration
• Sandbox testing mode
• Webhook-verified orders (secure)
• 12 months of updates
• Email support

REQUIREMENTS
• WordPress 6+ and WooCommerce 7+
• HTTPS on your live site
• Your own Whop business account (free to create)
• Classic WooCommerce checkout

SETUP TIME: About 5 minutes using the built-in wizard.

Payments go to YOUR Whop account — not ours.

After purchase, download the plugin ZIP and follow INSTRUCTIONS-BUYER-SETUP.md (linked in your welcome email).
```

### Pricing
```
$79.99 / year
```

### URL slug (example)
```
whop-checkout-pro
```

---

## Step 3 — Deliver the ZIP to buyers

Whop’s product form may not have a file field on the first screen. Use one of these:

### Option A — Whop Files app (recommended)

1. Publish the product.
2. Add a **Files** app or module to the product/membership.
3. Upload **`whop-checkout-5.0.1.zip`**.
4. Buyers download from their Whop portal after purchase.

### Option B — Welcome email

1. Attach **`whop-checkout-5.0.1.zip`** or host it on a private link.
2. Send the email template below after each purchase (or automate via Whop email).

### Option C — Your own site

Sell on Whop, deliver from a password-protected page on your WordPress site using WooCommerce downloadable product — only if you already run that workflow.

---

## Step 4 — Welcome email template

**Subject:** Your Whop Checkout download + setup steps

---

Hi,

Thanks for purchasing **Whop Checkout**!

**1. Download the plugin**  
[Link to Whop Files or attached `whop-checkout-5.0.1.zip`]

**2. Install on WordPress**  
Plugins → Add New → Upload Plugin → Activate

**3. Run Setup Wizard**  
WooCommerce → Whop Checkout → Setup Wizard

**4. Full setup guide**  
Follow the step-by-step guide: INSTRUCTIONS-BUYER-SETUP.md (PDF or link you provide)

**Quick steps:**
• Whop.com → Settings → API Keys → create key + copy Company ID  
• Wizard Step 2: paste API key + Company ID  
• Wizard Step 3: Auto-register webhook  
• Wizard Step 5: sandbox test → turn sandbox OFF for live sales

**Optional license key:** `WCHECK-2026-XXXXXX` — enter under WooCommerce → Settings → Payments → Whop Checkout (support ID only; plugin works without it)

**Need help?** Reply with your site URL and a screenshot of Whop Checkout → Health (no API keys in screenshots).

— [Your brand]

---

## Step 5 — Optional license keys

The plugin does **not** phone home. License keys are for **your** support tracking only.

Example format: `WCHECK-2026-A7K9M2`

Generate one per buyer and include in the welcome email. Buyer enters it under payment settings if they want.

---

## Step 6 — Rebuild the ZIP (after code changes)

From Terminal on Mac:

```bash
cd "/Users/lapteck/Desktop/My whop/Ready whop for wp"
rm -f whop-checkout-5.0.1.zip
zip -r whop-checkout-5.0.1.zip whop-gateway-wc \
  -x "*.DS_Store" \
  -x "*/__MACOSX/*"
```

Verify the ZIP contains **`whop-gateway-wc/`** as the root folder (WordPress expects folder name = plugin slug).

**Do not include:** `docs/`, `INSTRUCTIONS-*.md`, `AUDIT-REPORT.md`, or this seller guide.

---

## Step 7 — Pre-launch checklist

- [ ] Author name updated in plugin header
- [ ] Fresh ZIP built (`whop-checkout-5.0.1.zip`)
- [ ] Test install on clean WordPress + WooCommerce
- [ ] Complete sandbox order end-to-end
- [ ] Whop product live at target price
- [ ] ZIP uploaded to Files app or email template ready
- [ ] Buyer setup guide shared (PDF, Notion, or email link)
- [ ] Support email/channel ready

---

## Step 8 — Support playbook

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

Point buyers to **INSTRUCTIONS-BUYER-SETUP.md** for full steps.

---

## What you do NOT need

- OAuth / Connect server
- Extra subdomain or hosting for auth
- License validation server
- GitHub/Vercel/Railway for this edition

Buyers use **their own** Whop API keys; payments go to **their** Whop account.

---

## Business model reminder

| Role | Who |
|------|-----|
| **You (seller)** | Sell the plugin on Whop |
| **Buyer** | Installs on their WordPress store |
| **Buyer’s customers** | Pay via Whop checkout; funds go to buyer’s Whop company |

You are not processing their payments — you are selling software.

---

## Updating the product later

1. Bump version in `whop-gateway-wc.php` and `readme.txt`.
2. Fix bugs, rebuild ZIP with new version number.
3. Upload new ZIP to Whop Files.
4. Email existing buyers with changelog + new download link (per your license terms).

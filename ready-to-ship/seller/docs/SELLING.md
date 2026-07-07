# Selling Whop Checkout

You can sell this plugin on your own website using Whop for **one-time** or **subscription** purchases.

## How the business model works

```
YOUR SITE                         CUSTOMER'S STORE
─────────                         ────────────────
You sell the plugin ZIP      →    They install the plugin
Whop charges them                    They add THEIR Whop API keys
You deliver download + docs          Their shoppers pay THEM via Whop
```

- **Your revenue** = plugin sales on your site (via your Whop account).
- **Customer revenue** = their WooCommerce sales (via their own Whop account).
- The plugin does **not** route their store payments through your Whop account.

## Option A — One-time purchase

1. In Whop, create a **one-time** product/plan (e.g. $49 lifetime).
2. On your sales page, use Whop checkout (embed or link).
3. After payment, deliver:
   - Plugin ZIP (`whop-gateway-wc.zip`)
   - Link to `docs/SETUP.md`
   - Support email or Discord

**Delivery methods:**
- Whop automated file delivery (if configured on the product)
- Email automation (Zapier, Make, or manual)
- Members-only download page

## Option B — Subscription (annual/monthly)

1. In Whop, create a **renewal** plan (e.g. $19/month or $99/year).
2. Include in the subscription:
   - Plugin download
   - Updates for the subscription period
   - Support channel
3. When subscription ends, your policy can pause updates (honor GPL: provide GPL source on request).

## Option C — Tiered licensing

| Tier | Price | Includes |
|------|-------|----------|
| Single site | One-time | 1 production site |
| Agency | Annual | Unlimited client sites |
| Bundle | One-time | Plugin + setup call |

Enforcement is a **business policy** (support/updates), not DRM in the plugin. The plugin stays GPL — do not add obfuscated phone-home malware.

## What to include in your customer package

Zip the plugin folder (exclude `.git`, `docs/SELLING.md` if you prefer):

```
whop-gateway-wc/
├── whop-gateway-wc.php
├── includes/
├── assets/
├── readme.txt
└── docs/SETUP.md
```

Add a **LICENSE.txt** (GPL-2.0) and your **README for buyers** with support terms.

## Rebranding for resale (optional)

You may rebrand for your agency:

- Plugin name and description in `whop-gateway-wc.php`
- Author URI to your site
- CSS class prefixes
- Your logo in assets

Keep GPL license and credit if required by your fork's upstream.

## Marketing copy (example)

> **Whop Checkout** — accept cards and crypto on any WooCommerce store. Automatic checkout per order, webhook-verified payments, sandbox testing, HPOS ready. One-time install on unlimited sites (your policy) / per-site license (your policy).

## Support checklist for buyers

1. Whop company account + API key
2. Webhook URL on their domain
3. HTTPS required
4. Classic WooCommerce checkout
5. Sandbox test before live

## Legal notes

- This plugin integrates with Whop; you are not Whop unless officially partnered.
- Use accurate marketing — do not claim official Whop endorsement without approval.
- GPL allows selling the plugin; source must remain available to recipients under GPL terms.

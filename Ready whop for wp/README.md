# Ready Whop for WP — Whop Checkout v5.0.1

**Wizard-only edition.** No OAuth. No Connect server. Upload-ready for Lemon Squeezy.

---

## What's in this folder

| File / folder | Purpose |
|---------------|---------|
| **`whop-checkout-5.0.1.zip`** | **Upload to Lemon Squeezy / send to buyers** |
| `whop-gateway-wc/` | Plugin source (same as ZIP) |
| **`INSTRUCTIONS-BUYER-SETUP.md`** | Step-by-step guide for buyers (keep outside ZIP) |
| **`INSTRUCTIONS-SELLER-LEMONSQUEEZY.md`** | Listing, delivery, email, support (**use this**) |
| `INSTRUCTIONS-SELLER-WHOP.md` | Legacy Whop-seller guide (optional) |
| **`AUDIT-REPORT.md`** | Full A–Z security & technical audit |
| `WHOP-PRODUCT-COPY.md` | Short product listing snippets |
| `BUYER-EMAIL-TEMPLATE.md` | Short welcome email |

---

## Quick start (seller)

1. Set **Author** in `whop-gateway-wc/whop-gateway-wc.php` to your brand.
2. Create **$170 lifetime** product on [Lemon Squeezy](https://www.lemonsqueezy.com).
3. Upload **`whop-checkout-5.0.1.zip`** as the deliverable file.
4. Paste checkout URL into **`funnel-site/config.js`** and deploy WhopCheckout.com.
5. Follow **`INSTRUCTIONS-SELLER-LEMONSQUEEZY.md`** for full setup.

---

## Quick start (buyer)

1. Install **`whop-checkout-5.0.1.zip`** in WordPress.
2. Go to **WooCommerce → Whop Checkout → Setup Wizard**.
3. Follow **`INSTRUCTIONS-BUYER-SETUP.md`**.

---

## Audit status

v5.0.1 passed full audit — see **`AUDIT-REPORT.md`**.

- Webhook-verified payments only
- Secrets encrypted at rest
- No Connect/OAuth dependencies

---

## Product listing (Lemon Squeezy)

| Field | Value |
|-------|--------|
| Name | Whop Checkout |
| Price | $170 lifetime (one-time) |
| Deliverable | `whop-checkout-5.0.1.zip` only |
| Sales funnel | `funnel-site/` → WhopCheckout.com |

---

## Ship checklist

- [ ] Author name updated in plugin
- [ ] Test install on clean WordPress + WooCommerce
- [ ] Lemon Squeezy product live at $170
- [ ] Checkout URL in funnel `config.js`
- [ ] Funnel deployed to WhopCheckout.com
- [ ] PK bank or PayPal linked in Lemon Squeezy
- [ ] Buyer setup guide shared with customers

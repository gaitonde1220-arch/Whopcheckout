# Whop Product Setup (Your Sales Site)

Create these products on **your** Whop account to sell the plugin.

## Tier 1 — Starter (one-time)

| Field | Value |
|-------|--------|
| Name | Whop Checkout — Starter |
| Price | $49–79 one-time |
| Delivery | ZIP file + license key (manual or email) |

**Includes:**
- Plugin download
- Buyer portal link
- 7-day email support
- Manual setup wizard (no Connect service)

**License key format:** `WGWC-START-{random}` — manual path only or trial Connect

---

## Tier 2 — Pro (annual)

| Field | Value |
|-------|--------|
| Name | Whop Checkout — Pro |
| Price | $99–149/year |
| Billing | Renewal plan |

**Includes:**
- Everything in Starter
- **Connect your Whop** (one-click setup)
- Plugin updates for 12 months
- 30-day priority support

**License key format:** `WGWC-PRO-{random}` — enables Connect service

---

## Tier 3 — Agency (annual)

| Field | Value |
|-------|--------|
| Name | Whop Checkout — Agency |
| Price | $199–299/year |
| Billing | Renewal plan |

**Includes:**
- Everything in Pro
- Unlimited client site installs (policy)
- White-label buyer docs (optional)
- Priority support

**License key format:** `WGWC-AGENCY-{random}`

---

## Add-on — Done-for-you setup

| Field | Value |
|-------|--------|
| Name | Whop Checkout Setup Service |
| Price | $99 one-time |

You install + Connect + sandbox test on client's store.

---

## Delivery checklist after each sale

1. Send ZIP (`whop-gateway-wc.zip`)
2. Send license key matching tier
3. Link to buyer portal (host BUYER-PORTAL.md on your site)
4. Link to setup video
5. Add license key to Connect service allowlist (Pro/Agency)

---

## Connect service license allowlist

Add keys to `connect-service/.env`:

```
LICENSE_KEYS=WGWC-PRO-xxx,WGWC-AGENCY-yyy
```

Or use `LICENSE_KEYS=*` for development only.

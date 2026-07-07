# Security Audit — v4.0.4 (Ship-ready)

Audit date: 2026-07-08  
Plugin: Whop Checkout  
Status: **Production-ready**

## Executive summary

Version 4.0.1 is suitable for:

- Your own WooCommerce stores
- Reselling to clients (GPL-2.0-or-later)

All critical vulnerabilities from the original nulled plugin (v2.0) are resolved. v4.0 adds one-click Connect, setup wizard, and health dashboard with additional hardening in 4.0.1.

---

## Critical fixes (complete)

| # | Issue | Status |
|---|--------|--------|
| 1 | Fake payment via redirect URL | **Fixed** — redirect never calls `payment_complete()` |
| 2 | Order key as payment proof | **Fixed** — one-time token with 24h expiry |
| 3 | Wrong webhook events | **Fixed** — only `payment.succeeded`, `payment.failed`, `refund.created` |
| 4 | Weak signature verification | **Fixed** — Standard Webhooks + legacy fallback |
| 5 | Phishing-like iframe pages | **Fixed** — removed entirely |
| 6 | Forced USD | **Fixed** — WooCommerce store currency |
| 7 | Missing company_id | **Fixed** — sent to API + verified on webhooks |
| 8 | Amount not verified | **Fixed** — strict match required |
| 9 | Duplicate webhooks | **Fixed** — global + per-order deduplication |
| 10 | Thank-you page before payment | **Fixed** — unpaid orders redirected |
| 11 | Gateway enabled without webhook secret | **Fixed** — checkout blocked + admin warnings |
| 12 | OAuth setup token replay | **Fixed** — one-time exchange + transient guard |
| 13 | Secrets stored plaintext (OAuth) | **Fixed** — AES-256-CBC with `wgenc1:` prefix |
| 14 | Connect open redirect | **Fixed** — return URL validated against site origin |
| 15 | Encrypted secret false negatives | **Fixed** — `get_webhook_secret()` used everywhere |

---

## Security architecture

```
Checkout → Whop hosted payment → Webhook (signed) → payment_complete()
                              ↘ Return page polls status (read-only)
```

**Connect flow (Pro/Agency):**

```
WordPress → Seller Connect service (OAuth) → Whop → setup_token → WordPress /exchange
```

**Trust boundaries:**

- **Trusted:** Whop webhook with valid signature + matching amount/currency/company/order
- **Untrusted:** Browser return URL, AJAX poll, customer-facing pages

---

## v4.0.1 hardening

### Plugin
- OAuth tokens and webhook secrets encrypted at rest (`wgenc1:` prefix)
- `get_webhook_secret()` used in checkout, webhooks, health, and admin notices
- Health dashboard API check cached 60s (no hammering Whop on every page load)
- Connect callback uses PRG redirect; setup tokens single-use

### Connect service (seller-hosted)
- `return_url` must match `site_url` origin and point to `wp-admin/admin.php?page=whop-gateway-wc`
- OAuth and setup sessions expire after 15 minutes
- Expired sessions purged on each request
- License key required (Pro/Agency pattern or allowlist)

---

## Known limitations (not bugs)

| Limitation | Impact | Workaround |
|------------|--------|------------|
| Classic checkout only | Blocks checkout not supported | Use classic checkout |
| OAuth tokens expire (~1hr) | API calls may fail until reconnect | Re-connect or use manual API key |
| Webhook required for instant confirmation | Delay if webhook slow | Return page polls; API sync fallback |
| Connect service required for one-click | Seller must host OAuth bridge (e.g. connect.yourdomain.com) | Setup Wizard manual path |
| Client needs own Whop account | Cannot pool payments | Sell plugin; they connect their Whop |

---

## Pre-launch checklist

- [ ] HTTPS enabled on production domain
- [ ] Whop webhook URL registered (API v1)
- [ ] Webhook secret configured (auto or manual)
- [ ] Sandbox test order completed end-to-end
- [ ] Order note shows Whop payment ID
- [ ] Debug logging **off** in production
- [ ] Connect service deployed (Pro/Agency only)
- [ ] Whop OAuth app registered per `docs/WHOP-APP-SETUP.md`

---

## File integrity

No obfuscated code. No external phone-home. No eval/base64 payloads.

| File | Role |
|------|------|
| `whop-gateway-wc.php` | Bootstrap, guards, admin notices |
| `includes/class-wc-gateway-whop.php` | Gateway + webhooks |
| `includes/class-whop-api.php` | REST client |
| `includes/class-whop-webhook.php` | Signature verification |
| `includes/class-whop-helper.php` | Encryption, settings, security helpers |
| `includes/class-whop-connect.php` | OAuth handoff |
| `includes/class-whop-setup-wizard.php` | Manual setup |
| `includes/class-whop-health.php` | Health dashboard |
| `templates/return.php` | Customer processing page |

Connect service (`connect-service/`) is **seller infrastructure** — not distributed in the client GPL ZIP.

---

## Reseller note

Safe to distribute client ZIP under GPL. Include `docs/SETUP.md` for buyers. Host Connect service yourself; never ship OAuth client secrets inside the buyer plugin.

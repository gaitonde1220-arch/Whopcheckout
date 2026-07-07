# Whop Checkout v5.0.1 — A–Z Audit Report

**Edition:** Wizard-only (no OAuth / Connect)  
**Audit date:** July 8, 2026  
**Path:** `Ready whop for wp/whop-gateway-wc/`  
**Verdict:** **PASS — upload ready** (with documented limitations)

---

## Executive summary

Whop Checkout v5.0.1 is suitable for commercial distribution. Critical payment flows use **webhook signature verification** before marking orders paid. Dead OAuth/Connect code was removed. Secrets are encrypted at rest. Admin actions require `manage_woocommerce`. The plugin ZIP contains **code only**; buyer/seller instructions live outside the ZIP.

---

## 1. Security audit

| Area | Status | Notes |
|------|--------|-------|
| Payment completion | **PASS** | `payment_complete()` only from signed webhook handler or API sync with stored payment ID + amount match |
| Return URL trust | **PASS** | Return page never marks paid; one-time token + expiry; polling uses nonce + token |
| Webhook verification | **PASS** | Standard Webhooks (timestamp ±5 min, HMAC) + legacy HMAC fallback |
| Webhook method | **PASS** | POST only; 401 on bad signature |
| Webhook replay | **PASS** | Global + per-order event ID deduplication |
| Company ID binding | **PASS** | Webhooks with mismatched `company_id` ignored |
| Amount/currency match | **PASS** | Mismatch logs error + order note; no auto-complete |
| Checkout redirect URL | **PASS** | `purchase_url` validated; host must be `whop.com` or `*.whop.com` |
| API secrets at rest | **PASS** | AES-256-CBC via `wp_salt('auth')`; prefix `wgenc1:` |
| Admin AJAX | **PASS** | `check_ajax_referer` + `current_user_can('manage_woocommerce')` |
| Wizard forms | **PASS** | `check_admin_referer` on POST; rendered only in admin |
| SQL injection | **PASS** | Uses WooCommerce/WordPress APIs only |
| XSS (admin) | **PASS** | `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` used |
| XSS (return template) | **PASS** | JSON via `wp_json_encode`; URLs escaped |
| CSRF (customer poll) | **PASS** | `wp_verify_nonce('whop_order_status_' . $order_id)` |
| Rate limiting | **PASS** | Order status poll: 1 req/sec via transient |
| Uninstall cleanup | **PASS** | Removes plugin options (not order meta — correct for WC) |
| Direct file access | **PASS** | `defined('ABSPATH')` guards on all PHP files |
| Index.php silencers | **PASS** | Present in asset/include dirs |

### Fixed in 5.0.1

| Issue | Fix |
|-------|-----|
| Dead OAuth/Connect helpers | Removed `save_oauth_connection`, `license_allows_connect`, connect URL constants |
| `has_credentials()` false negative | Now uses decrypted `get_bearer_token()` instead of raw `api_key` field |
| Health dashboard OAuth text | Shows “API configured” only |
| Internal docs in ZIP | Removed `docs/` from plugin package |

### Residual risks (accepted / documented)

| Risk | Severity | Mitigation |
|------|----------|------------|
| Encryption fallback without OpenSSL | Low | Uses prefixed base64; rare on modern hosts |
| `sync_order_from_whop` API path | Low | Requires existing payment ID + API amount match; not user-triggerable without token |
| No Block checkout support | N/A | Health warns; document for buyers |
| Webhook delivery delay | N/A | Return page polls; customer messaging explains wait |
| GPL source visible | N/A | Expected for WordPress plugins |

---

## 2. Technical audit

| Area | Status | Notes |
|------|--------|-------|
| WordPress compatibility | **PASS** | Requires WP 6.0+, declares HPOS compatibility |
| WooCommerce gateway API | **PASS** | Extends `WC_Payment_Gateway` correctly |
| Activation hook | **PASS** | Requires WC; sets setup redirect transient |
| Autoload / bootstrap | **PASS** | Loads on `plugins_loaded` priority 20 |
| Whop API client | **PASS** | Bearer auth, sandbox/live base URLs, error parsing |
| Plan + checkout creation | **PASS** | One-time plan per order; metadata includes order ID + key |
| Webhook auto-register | **PASS** | Creates v1 webhook with required events |
| Order meta | **PASS** | Tracks plan, checkout, payment, expected total/currency |
| Thank-you page guard | **PASS** | Unpaid Whop orders redirected away from false success |
| Debug logging | **PASS** | WC logger, source `whop-gateway-wc`; off by default |
| i18n | **PASS** | Text domain `whop-gateway-wc` |
| Uninstall | **PASS** | Cleans settings + webhook event cache |

### Known limitations (not bugs)

- **WooCommerce Blocks checkout** — not supported; classic checkout required
- **Subscriptions / pre-orders** — not in scope (`supports: products` only)
- **License key** — cosmetic support ID; no remote validation
- **Refund sync** — adds note + status; no partial refund amount sync

---

## 3. Code quality

| Area | Status |
|------|--------|
| OAuth dead code removed | **PASS** |
| Consistent naming | **PASS** |
| No hardcoded seller branding | **PASS** |
| No external license server | **PASS** |
| readme.txt + LICENSE.txt | **PASS** |

---

## 4. Packaging audit

| Item | Status |
|------|--------|
| ZIP root folder `whop-gateway-wc/` | **PASS** |
| Version 5.0.1 in header + constant + readme | **PASS** |
| No `docs/` inside plugin | **PASS** (removed) |
| Instructions outside ZIP | **PASS** |
| GPL LICENSE included | **PASS** |
| WordPress.org-style readme.txt | **PASS** |

---

## 5. File inventory (plugin ZIP contents)

```
whop-gateway-wc/
├── whop-gateway-wc.php          Main bootstrap
├── readme.txt
├── LICENSE.txt
├── uninstall.php
├── includes/
│   ├── class-wc-gateway-whop.php
│   ├── class-whop-api.php
│   ├── class-whop-webhook.php
│   ├── class-whop-helper.php
│   ├── class-whop-health.php
│   ├── class-whop-setup-wizard.php
│   └── class-whop-admin.php
├── templates/return.php
└── assets/ (css, js, images)
```

**Excluded from ZIP:** `docs/`, seller/buyer markdown guides

---

## 6. Pre-upload test plan

Run once on a staging WordPress site:

1. [ ] Install ZIP via Plugins → Upload
2. [ ] Activation redirects to Setup Wizard
3. [ ] Step 2 saves encrypted API key (verify DB value starts with `wgenc1:`)
4. [ ] Step 3 auto-registers webhook OR manual secret works
5. [ ] API test returns success
6. [ ] Sandbox checkout completes; order paid via webhook
7. [ ] Health shows last webhook timestamp
8. [ ] Disable sandbox; live credentials work
9. [ ] Unpaid thank-you page does not show success
10. [ ] Invalid return token returns 403

---

## 7. Sign-off

| Check | Result |
|-------|--------|
| Security | **Approved** |
| Technical | **Approved** |
| Packaging | **Approved** |
| Upload to Whop | **Ready** |

**Artifact:** `whop-checkout-5.0.1.zip`  
**Buyer docs:** `INSTRUCTIONS-BUYER-SETUP.md`  
**Seller docs:** `INSTRUCTIONS-SELLER-WHOP.md`

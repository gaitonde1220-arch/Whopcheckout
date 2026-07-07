# Whop Checkout — Store Setup Guide (v4.0.4)

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+ with OpenSSL
- **HTTPS** on production (required for Whop webhooks)
- **Classic WooCommerce checkout** (Block checkout not supported)

## Installation

1. Upload `whop-gateway-wc` to `wp-content/plugins/`.
2. Activate **Whop Checkout**.
3. Open **WooCommerce → Whop Checkout**.

## Setup paths

### Option A — One-click Connect (Pro / Agency license)

1. Enter your **license key** (`WGWC-PRO-*` or `WGWC-AGENCY-*`).
2. Enter **Connect service URL** from your plugin seller (e.g. `https://connect.yourdomain.com`).
3. Click **Save connect settings**, then **Connect your Whop**.
4. Authorize in Whop — webhook and credentials are configured automatically.
5. Run sandbox test (Step 5 in Setup Wizard).

### Option B — Manual Setup Wizard (Starter)

1. Open **Setup Wizard** tab.
2. Enter API key + Company ID from Whop → Settings → API Keys.
3. **Step 3:** Auto-register webhook or paste webhook secret manually.
4. Verify connection and complete sandbox test.

## Whop dashboard (manual path)

### Webhook URL

```
https://YOUR-DOMAIN.com/?wc-api=whop_webhook
```

Events (API v1): `payment.succeeded`, `payment.failed`, `refund.created`

## Plugin settings

| Setting | Required | Notes |
|---------|----------|-------|
| Enable | Yes (after testing) | Shows warnings if misconfigured |
| API Key / OAuth | Yes | Manual key or Connect |
| Company ID | Yes | Must match webhook company |
| Webhook Secret | **Yes** | Checkout blocked without it |
| Sandbox | Yes for testing | Disable for live |
| Debug logging | No in production | WooCommerce → Status → Logs |

## Test procedure

1. Enable **Sandbox Mode**.
2. Place a test order using **Whop Checkout** at checkout.
3. Complete payment on Whop hosted checkout.
4. Confirm order is paid with Whop payment ID in order notes.

## Go live

1. Disable Sandbox Mode.
2. Turn off debug logging.
3. Run one small real transaction.

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Order stays pending | Webhook not delivered | Check HTTPS, firewall, caching, secret |
| Invalid signature | Wrong secret | Re-copy secret; exclude webhook URL from cache |
| Connect fails | Wrong Connect URL or license | Verify URL + Pro/Agency license |
| Gateway missing at checkout | Not configured | Complete Connect or Setup Wizard |

## For resellers

Give clients this file + `docs/AUDIT.md`. Each client needs their **own** Whop account and credentials.

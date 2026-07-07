# White-Label Guide (Sellers)

This plugin is **not tied to any single brand or domain**. Use it on any site you sell to.

## What buyers see

- Plugin name: **Whop Checkout**
- No hardcoded seller domain in the client ZIP
- **Connect service URL** is entered by the buyer (or pre-filled by you)

## Branding you should customize

| Item | Where | Example |
|------|--------|---------|
| Plugin author | `whop-gateway-wc.php` header | `Your Brand` |
| Support URL | WooCommerce → Whop Checkout settings | `https://yourdomain.com/support` |
| License keys | Your Whop product / license system | `WGWC-PRO-XXXX` |
| Connect subdomain | Your DNS + Connect service deploy | `https://connect.yourdomain.com` |
| Marketing copy | `docs/marketing/` | Replace placeholders |

## Pre-fill Connect URL for all buyers (optional)

Add to a small must-use plugin on **your** demo site, or document for buyers:

```php
add_filter( 'whop_gw_connect_url_default', function () {
    return 'https://connect.yourdomain.com';
} );
```

Or pre-fill Connect URL and bridge secret for all buyers:

```php
add_filter( 'whop_gw_connect_url_default', fn () => 'https://connect.yourdomain.com' );
add_filter( 'whop_gw_connect_bridge_secret', fn () => 'your-shared-bridge-secret' );
```

Set the same `CONNECT_BRIDGE_SECRET` in your Connect service `.env`.

## Tiers

| Tier | Connect | Setup |
|------|---------|--------|
| **Starter** | No | Manual Setup Wizard (API key + webhook) |
| **Pro / Agency** | Yes | One-click OAuth via **your** Connect service |

## Seller infrastructure (keep private)

- `connect-service/` — deploy on your server; never put OAuth secrets in the client ZIP
- `docs/WHOP-APP-SETUP.md` — register Whop app under your developer account
- `docs/marketing/` — your sales page, video script, buyer portal copy

## Client ZIP (ship to buyers)

Use `ready-to-ship/client/whop-gateway-wc-*.zip` — includes plugin + `SETUP.md` + `AUDIT.md` only.

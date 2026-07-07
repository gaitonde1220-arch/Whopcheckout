# Whop Checkout Connect Service

OAuth bridge for the **Whop Checkout** WooCommerce plugin. Keeps Whop OAuth secrets on **your** server — not in the GPL plugin ZIP.

## Setup

1. Register a Whop App — see [../docs/WHOP-APP-SETUP.md](../docs/WHOP-APP-SETUP.md)
2. Copy `.env.example` to `.env`
3. `npm install`
4. `npm start`

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/start` | Begin OAuth (requires `site_url`, `return_url`, `license_key`) |
| GET | `/callback` | Whop OAuth callback |
| POST | `/exchange` | Plugin exchanges one-time `setup_token` for credentials |
| GET | `/health` | Health check |

## Deploy

Deploy to Railway, Render, Fly.io, or your own VPS. Set env vars from `.env.example`.

Use a dedicated subdomain, for example:

```
https://connect.yourdomain.com
```

Tell buyers to enter that URL in **WooCommerce → Whop Checkout → Connect service URL**, or set a default with the WordPress filter `whop_gw_connect_url_default`.

## License validation

Set `LICENSE_KEYS=WGWC-PRO-xxx,WGWC-AGENCY-yyy` or `LICENSE_KEYS=*` for development.

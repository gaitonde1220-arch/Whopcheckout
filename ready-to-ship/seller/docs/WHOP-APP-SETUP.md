# Register Whop OAuth App — One-Time Setup

Complete these steps in **your** Whop Developer Dashboard before deploying the Connect service.

Replace `yourdomain.com` with your actual domain.

## 1. Create the app

1. Go to [Whop Developer Dashboard](https://whop.com/dashboard/developer)
2. Click **Create app**
3. Name: `Whop Checkout` (or your brand name)

## 2. OAuth settings

| Setting | Value |
|---------|--------|
| Redirect URI (production) | `https://connect.yourdomain.com/callback` |
| Redirect URI (local dev) | `http://localhost:8787/callback` |

## 3. Required permissions

Add and justify these permissions:

- `developer:manage_webhook` — auto-create store webhooks
- `oauth:token_exchange` — OAuth flow
- Plan/checkout permissions for payment creation
- Company read scopes as required by Whop for your app type

## 4. Copy credentials to Connect service

Add to `connect-service/.env`:

```
WHOP_CLIENT_ID=app_xxxxxxxx
WHOP_CLIENT_SECRET=your_secret
WHOP_APP_API_KEY=apik_xxxxxxxx
CONNECT_BASE_URL=https://connect.yourdomain.com
```

## 5. App install link

After publishing, your install URL will be:

```
https://whop.com/apps/app_XXXXXXXXX/install
```

## 6. Sandbox testing

Set in `.env` for development:

```
WHOP_SANDBOX=true
```

Use sandbox OAuth endpoints until production is verified.

## 7. Submit for review (optional)

If listing publicly on Whop App marketplace, follow Whop's app review process. Until approved, use a direct install link for Pro/Agency buyers.

## Verification checklist

- [ ] OAuth redirect matches Connect service `/callback`
- [ ] Webhook creation works with a test company
- [ ] License keys validated on `/start`
- [ ] Plugin receives credentials via `/exchange`
- [ ] Production `.env` deployed to `https://connect.yourdomain.com`
- [ ] Buyers can save your Connect URL in the plugin admin

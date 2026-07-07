# Whop Checkout — Buyer Setup Guide (Step by Step)

**Plugin version:** 5.0.1  
**Edition:** Wizard-only (no OAuth, no Connect server)

This guide is for **store owners** who purchased Whop Checkout. Keep this file on your computer — it is **not** inside the plugin ZIP.

---

## Before you start

You need:

| Requirement | Details |
|-------------|---------|
| WordPress | 6.0 or newer |
| WooCommerce | 7.0 or newer |
| PHP | 7.4 or newer |
| HTTPS | Required on your **live** site (webhooks will not work on plain HTTP) |
| Whop account | Free to create at [whop.com](https://whop.com) |
| Checkout type | **Classic** WooCommerce checkout (not Blocks-only) |

**Time needed:** about 5–10 minutes for first setup.

---

## Step 1 — Install the plugin

1. Log in to your WordPress admin (`yoursite.com/wp-admin`).
2. Go to **Plugins → Add New**.
3. Click **Upload Plugin**.
4. Choose **`whop-checkout-5.0.1.zip`** (from your purchase email or Whop Files).
5. Click **Install Now**, then **Activate**.

If WooCommerce is not installed, activation will fail. Install WooCommerce first, then activate Whop Checkout again.

After activation, WordPress may redirect you to **WooCommerce → Whop Checkout → Setup Wizard** automatically.

---

## Step 2 — Open the Setup Wizard

1. In WordPress admin, go to **WooCommerce → Whop Checkout**.
2. Open the **Setup Wizard** tab (default).
3. You will see **5 steps** at the top.

---

## Step 3 — Step 1: Requirements

The wizard shows a health dashboard:

- **HTTPS** — must show Ready on production
- **WooCommerce** — must show Active
- **Checkout** — should show Classic checkout (warning if Blocks detected)
- **Credentials** — Not configured yet (normal)
- **Webhook secret** — Missing yet (normal)

Fix any **red/fail** items before continuing. Click **Continue**.

---

## Step 4 — Create Whop API credentials

Do this in a separate browser tab on [whop.com](https://whop.com):

1. Log in to your Whop dashboard.
2. Open your **business/company** (the one that should receive payments).
3. Go to **Settings → API Keys** (or Developer → API Keys).
4. Click **Create API key** (company-scoped key).
5. Copy and save:
   - **API Key** — starts with `apik_`
   - **Company ID** — starts with `biz_` or `comp_`

Keep these private. Never share them in public tickets or screenshots.

---

## Step 5 — Step 2: Enter API credentials

Back in WordPress:

1. In **Step 2 — API credentials**, paste your **API Key**.
2. Paste your **Company ID**.
3. Click **Save and continue**.

The API key is stored encrypted in your WordPress database. Leaving the password field blank on a later edit keeps the existing key.

---

## Step 6 — Step 3: Register webhook

Webhooks tell WooCommerce when a customer has **actually paid**. Without a webhook secret, orders will stay “pending” forever.

### Option A — Auto-register (recommended)

1. On **Step 3**, note your **Webhook URL** (looks like `https://yoursite.com/?wc-api=whop_webhook`).
2. Click **Auto-register webhook via API**.
3. Wait for the success message: “Webhook secret is configured.”

### Option B — Manual (if auto-register fails)

1. In Whop, go to **Developer → Webhooks**.
2. Create a webhook:
   - **URL:** your store webhook URL from the wizard
   - **API version:** v1
   - **Events:** `payment.succeeded`, `payment.failed`, `refund.created`
3. Copy the **signing secret** (starts with `ws_`).
4. In WordPress: **WooCommerce → Settings → Payments → Whop Checkout**.
5. Paste the secret in **Webhook Secret** and save.

Click **Continue** in the wizard.

---

## Step 7 — Step 4: Verify connection

1. Click **Test API connection** — you should see “API connection successful.”
2. Review the health dashboard:
   - Credentials → API configured
   - Webhook secret → Set
   - Whop API → success message

Click **Continue**.

---

## Step 8 — Step 5: Sandbox test

Always test before accepting real money.

1. Check **Sandbox mode enabled (for testing)**.
2. Check **Enable Whop Checkout gateway**.
3. Click **Save and finish**.

### Place a test order

1. On your store front, add a product to cart.
2. Go to checkout (classic checkout page).
3. Select **Pay with Card or Crypto** (or your custom title).
4. Complete payment on Whop’s hosted checkout (sandbox).
5. You will return to a “Confirming your payment” page, then the thank-you page when the webhook fires.

### Confirm in WooCommerce

1. Go to **WooCommerce → Orders**.
2. Open the test order.
3. Status should be **Processing** or **Completed** (paid).
4. Order notes should mention **Payment confirmed via Whop webhook** with a payment ID.

Check **Whop Checkout → Health** — **Last webhook** should show a recent time.

5. Back in Step 5, check **I completed a successful sandbox test order** and save.

---

## Step 9 — Go live

1. **WooCommerce → Whop Checkout → Setup Wizard → Step 5**
2. **Uncheck** Sandbox mode.
3. Save.

Or: **WooCommerce → Settings → Payments → Whop Checkout** → disable Sandbox Mode.

Your store now accepts **live** Whop payments. Money goes to **your** Whop company account.

---

## Optional settings

Under **WooCommerce → Settings → Payments → Whop Checkout**:

| Setting | Purpose |
|---------|---------|
| Title / Description | What customers see at checkout |
| License key | Optional support ID from your seller (does not affect payments) |
| Support URL | Optional link for your team |
| Debug logging | Logs to WooCommerce → Status → Logs (source: `whop-gateway-wc`) |

---

## Troubleshooting

### Orders stay “Pending” after payment

- **Webhook secret missing** — redo Step 3.
- **HTTPS** — site must use SSL in production.
- **Firewall** — allow POST to `/?wc-api=whop_webhook`.
- **Wrong company** — Company ID in plugin must match the Whop account that received the payment.
- Check **Health → Last webhook** — if “None received yet,” Whop is not reaching your site.

### “Whop Checkout is not configured” at checkout

- Run the Setup Wizard through Step 2 and 3.
- Ensure the gateway is **enabled** in Step 5 or payment settings.

### Payment option does not appear

- Gateway must be enabled.
- Credentials + webhook secret must be set (`has_credentials` check).
- Block checkout: switch to classic checkout or use a classic checkout shortcode/page.

### API test fails (401/403)

- Regenerate API key in Whop.
- Confirm Company ID matches the key’s company.
- If sandbox is ON, use sandbox-capable keys; if OFF, use live keys.

### Customer paid but order shows amount mismatch note

- Rare currency/rounding issue — review order manually in WooCommerce.
- Contact support with order number and Whop payment ID.

### Return page says “session invalid or expired”

- Customer waited too long (token expires after 24 hours).
- If they were charged, look up the order in WooCommerce by email; webhook may still complete it.

---

## Webhook URL reference

```
https://YOUR-DOMAIN.com/?wc-api=whop_webhook
```

Replace `YOUR-DOMAIN.com` with your live site domain. Must be **HTTPS**.

---

## Security notes for store owners

- Only **shop managers** with WooCommerce access can see API settings.
- Payments are marked paid **only** after Whop sends a **signed webhook** (or verified API sync with matching amount).
- Customers cannot fake a “paid” status by manipulating the return URL.
- Turn off **Debug logging** on production unless troubleshooting.

---

## Getting help

Contact the seller who sold you the plugin. Include:

- WordPress + WooCommerce versions
- Plugin version (5.0.1)
- Screenshot of **Whop Checkout → Health**
- Order number (no API keys in screenshots)

---

## Quick checklist

- [ ] Plugin installed and activated
- [ ] API Key + Company ID saved in wizard
- [ ] Webhook registered (auto or manual)
- [ ] API test passed
- [ ] Sandbox test order paid successfully
- [ ] Sandbox mode turned OFF for live sales
- [ ] HTTPS active on production site

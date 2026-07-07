# Getting paid in Pakistan — Lemon Squeezy seller guide

You sell the **Whop Checkout plugin** on **Lemon Squeezy** (same model as whopwoocommerce.com). Lemon Squeezy is the Merchant of Record — they handle cards, tax, and file delivery. You receive payouts to a Pakistani bank or PayPal.

**Important:** Buyers still connect **their own Whop account** inside the plugin. Lemon Squeezy is only for **selling the plugin to them**, not for their store payments.

---

## How the stack works

| Layer | What | Why |
|-------|------|-----|
| **Marketing** | Static funnel (`funnel-site/`) | Fast landing page at WhopCheckout.com |
| **Checkout + delivery** | **Lemon Squeezy** | One-time $170, instant ZIP download |
| **Payout to you** | **Lemon Squeezy → PK bank or PayPal** | Pakistan is supported (PKR bank payouts) |

```
Buyer on WhopCheckout.com
    ↓ clicks Get Lifetime — $170
Lemon Squeezy checkout (cards, PayPal, etc.)
    ↓ purchase confirmed
Buyer gets download email + license from Lemon Squeezy
    ↓ your share minus LS fees
Your Lemon Squeezy balance
    ↓ payout (1st & 15th of month, $50 minimum)
Your Pakistani bank (PKR) or PayPal
```

---

## Pakistan support (confirmed)

Lemon Squeezy lists **Pakistan** for bank payouts. You can also use PayPal.

- **Bank payout currency:** Set **PKR** in payout settings so USD sales convert at mid-market rate.
- **Payout schedule:** Twice monthly (1st and 15th). Sales are held ~13 days before becoming available.
- **Minimum payout:** $50 — smaller balances roll to the next cycle.
- **Fees:** Platform fee on each sale + ~1% on international bank payouts (confirm in dashboard).

Docs: [Supported countries](https://docs.lemonsqueezy.com/help/getting-started/supported-countries) · [Getting paid](https://docs.lemonsqueezy.com/help/getting-started/getting-paid)

---

## Step 1 — Create your Lemon Squeezy store

1. Sign up at [lemonsqueezy.com](https://www.lemonsqueezy.com)
2. Complete identity verification (KYC)
3. **Settings → Payouts** → add Pakistani **bank account (PKR)** or PayPal
4. Optional: custom domain for checkout (e.g. `checkout.whopcheckout.com`)

---

## Step 2 — Create the product ($170 lifetime)

1. **Products → New product**
2. Type: **Digital product** (single payment)
3. Name: **Whop Checkout**
4. Price: **$170 USD** · One-time
5. Upload **`whop-checkout-5.0.1.zip`** as the deliverable file
6. Enable **Generate license keys** (optional — for your support tracking)
7. Publish and copy the **checkout URL**

Paste that URL into `funnel-site/config.js` → `plan.checkoutUrl`.

---

## Step 3 — Wire the funnel

Edit `funnel-site/config.js`:

```js
paymentProvider: 'Lemon Squeezy',
plan: {
  checkoutUrl: 'https://YOUR-STORE.lemonsqueezy.com/checkout/buy/YOUR-PRODUCT-ID',
  // ...
},
```

Deploy the funnel to WhopCheckout.com (Netlify, Cloudflare Pages, etc.).

Full seller steps: **`Ready whop for wp/INSTRUCTIONS-SELLER-LEMONSQUEEZY.md`**

---

## What Lemon Squeezy handles for you

| Task | Lemon Squeezy |
|------|----------------|
| Card / PayPal processing | ✅ |
| VAT / sales tax (MoR) | ✅ |
| Invoice to buyer | ✅ |
| ZIP delivery after payment | ✅ |
| Refund workflow | ✅ |

You focus on support and plugin updates.

---

## Fee example ($170 sale)

Approximate (confirm in dashboard):

- Lemon Squeezy fee: ~5% + $0.50 on digital products (varies by payment method)
- On $170: you keep roughly **$160–162** before payout conversion
- International bank payout: +1% when funds are sent to your PK account

---

## What you do NOT need

| Not required | Why |
|--------------|-----|
| US LLC | Lemon Squeezy pays Pakistan directly |
| Whop product for *your* sales | Competitor uses LS for plugin sales |
| Stripe account | Lemon Squeezy is MoR |
| WordPress store to sell plugin | Funnel → Lemon Squeezy link is enough |

---

## Tax & compliance (your responsibility)

- Lemon Squeezy handles **buyer-side** tax in many regions as Merchant of Record
- Keep Lemon Squeezy payout statements for your **local tax records** in Pakistan
- Consult a local accountant if you are unsure about business registration

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Pakistan not in payout list | Use PayPal payout; contact LS support |
| Payout below $50 | Waits until next cycle crosses threshold |
| Buyer didn't get ZIP | Resend from LS order dashboard; check spam |
| Wrong checkout URL on site | Update `config.js` and redeploy funnel |

Support: Lemon Squeezy help in dashboard · [docs.lemonsqueezy.com](https://docs.lemonsqueezy.com)

---

## Launch checklist

- [ ] Lemon Squeezy store verified
- [ ] PK bank or PayPal linked (PKR selected for bank)
- [ ] Product live at **$170 lifetime** with ZIP attached
- [ ] Checkout URL in `funnel-site/config.js`
- [ ] Funnel deployed to WhopCheckout.com
- [ ] Test purchase (use LS test mode first)
- [ ] Welcome email / buyer guide linked (`INSTRUCTIONS-BUYER-SETUP.md`)

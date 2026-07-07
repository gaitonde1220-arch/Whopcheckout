# WhopCheckout.com — Sales funnel

Competitor-style long-form landing page with **Whop orange** branding.

**Sales checkout:** [Lemon Squeezy](https://www.lemonsqueezy.com) (same as whopwoocommerce.com).  
**Plugin purpose:** Lets store owners accept **Whop** payments on WooCommerce.

## Sections (same flow as whopwoocommerce.com)

1. Hero + Get Started
2. Demo video (add URL in `config.js`)
3. Problem — Whop embeds / plan-id pain
4. Solution — dynamic checkout
5. Why Choose — 4 benefits
6. How It Works — 5 steps
7. Perfect For — 4 audiences
8. Pricing — Lifetime $170
9. Final CTA + footer

## Config

Edit `config.js`:

| Field | Purpose |
|-------|---------|
| `plan.checkoutUrl` | Your Lemon Squeezy checkout link |
| `paymentProvider` | Shown in pricing footer (default: Lemon Squeezy) |
| `videoEmbedUrl` | YouTube/Vimeo embed for demo |
| `supportEmail` | Footer + mailto links |

After creating your product in Lemon Squeezy: **Products → Share → Copy checkout URL**.

## Preview

```bash
cd funnel-site && python3 -m http.server 8080
```

Open http://localhost:8080

---

## Deploy (free options)

### Option A — Netlify Drop (easiest, no coding)

1. Go to https://app.netlify.com/drop
2. Drag the entire **`funnel-site`** folder onto the page
3. You get a URL like `random-name.netlify.app`
4. Add your custom domain in Netlify settings (optional)

### Option B — Cloudflare Pages

1. Create free Cloudflare account
2. Pages → Create project → Direct Upload
3. Upload `funnel-site` folder
4. Connect domain

### Option C — GitHub Pages

1. Push `funnel-site` to a GitHub repo
2. Settings → Pages → deploy from branch
3. Set root to `/` or copy files to repo root

### Option D — Your existing hosting

Upload `index.html`, `styles.css`, and `config.js` to any web host (Hostinger, Namecheap, etc.) via FTP or cPanel File Manager.

---

## Custom domain example

| Purpose | Domain |
|---------|--------|
| Funnel (marketing) | `whopcheckout.com` |
| Checkout | `yourstore.lemonsqueezy.com` (Lemon Squeezy hosts this) |
| Support email | `hello@whopcheckout.com` |

---

## Do NOT put the plugin ZIP on this site

Deliver the ZIP through **Lemon Squeezy** as the product file. The funnel only sells — Lemon Squeezy delivers after payment.

See **`PAKISTAN-PAYMENTS.md`** for payouts to Pakistan.  
See **`../Ready whop for wp/INSTRUCTIONS-SELLER-LEMONSQUEEZY.md`** for full product setup.

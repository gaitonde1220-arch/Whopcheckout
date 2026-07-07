# Setup Video Script (~5 minutes)

Use this script for Loom/YouTube buyer onboarding.

---

## Intro (0:00–0:30)

"Hi, this is how to set up Whop Checkout on your store in under five minutes. You'll need WordPress, WooCommerce, HTTPS on your live site, and a Whop business account."

---

## Install (0:30–1:00)

1. WordPress → Plugins → Add New → Upload Plugin
2. Choose `whop-gateway-wc.zip` → Install → Activate
3. Confirm WooCommerce is active

---

## Connect your Whop (1:00–2:30) — Pro/Agency

1. Go to **WooCommerce → Whop Checkout**
2. Paste your **license key** from the purchase email
3. Click **Connect your Whop**
4. Log in to Whop when prompted
5. Click **Approve** to grant permissions
6. You're redirected back — status shows **Connected**

"No API keys. No webhook URLs. The plugin handles that automatically."

---

## Sandbox test (2:30–4:00)

1. Confirm **Sandbox mode** is ON
2. WooCommerce → Settings → Payments → enable **Whop Checkout**
3. Add a product to cart → checkout → select Whop
4. Complete payment on Whop's checkout page
5. Order should show **Processing** with a Whop payment ID in notes

---

## Go live (4:00–4:45)

1. Whop Checkout → turn **Sandbox mode OFF**
2. Save settings
3. Place one small real test order
4. Confirm payment in WooCommerce and Whop dashboard

---

## Manual setup fallback (4:45–5:00)

"If Connect doesn't work for your setup, open **Setup Wizard** for step-by-step manual API configuration."

---

## Outro

"Need help? Email support@yourdomain.com with your order ID and license key."

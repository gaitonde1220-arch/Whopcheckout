=== Whop Checkout ===
Contributors: whop-gateway
Tags: woocommerce, payment gateway, whop, checkout, crypto
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 9.9
Stable tag: 4.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-ready Whop payment gateway with one-click Connect, setup wizard, and webhook-verified orders.

== Description ==

**Whop Checkout** connects your WooCommerce store to Whop payments with one-click setup and webhook-verified orders.

= Highlights =

* **Connect your Whop** — one-click OAuth setup (Pro/Agency license)
* **Setup Wizard** — guided manual configuration fallback
* **Connection Health** — live status dashboard
* **Webhook-verified** — orders marked paid only after signed Whop webhooks
* **Sandbox mode** — test before going live
* **HPOS compatible**

== Installation ==

1. Upload and activate the plugin.
2. Go to **WooCommerce → Whop Checkout**.
3. Enter license key (Pro/Agency) and click **Connect your Whop**, OR run the **Setup Wizard**.
4. Complete a sandbox test order, then disable sandbox for live sales.

== Changelog ==

= 4.0.4 =
* Rebrand to Whop Checkout (display name; internal slugs unchanged)

= 4.0.3 =
* Fix missing ENCRYPT_PREFIX constant (fatal on OAuth save)
* Connect CSRF protection via WordPress connect_nonce
* Connect /exchange bridge HMAC authentication
* Strict Whop checkout URL host validation
* Encrypt manual API keys and webhook secrets on save
* payment.failed sets order status to failed
* Updated buyer SETUP.md for v4

= 4.0.2 =
* White-label ready: no hardcoded seller domain or brand
* Connect service URL is configurable per site (or via `whop_gw_connect_url_default` filter)
* Connect tab includes license key + Connect service URL fields

= 4.0.1 =
* Encrypt OAuth tokens and webhook secrets at rest
* Fix webhook secret detection for encrypted values across checkout and admin
* Cache health API checks (60s) to reduce API load
* Harden Connect service: return URL validation, session expiry, cleanup

= 4.0.0 =
* One-click Connect via seller-hosted OAuth bridge (Pro/Agency)
* 5-step Setup Wizard with auto webhook registration
* Connection Health dashboard
* Admin hub under WooCommerce → Whop Checkout
* License key support for Pro/Agency tiers
* Connect service package included for self-hosting

= 3.1.0 =
* Security hardening release

== Upgrade Notice ==

= 4.0.0 =
Major feature release. Deploy Connect service for one-click setup, or use Setup Wizard for manual path.

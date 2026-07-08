# WhopCheckout.com — SEO launch checklist

Use this after connecting **whopcheckout.com** to Netlify.

## 1. Domain & deploy

- [ ] Add custom domain `whopcheckout.com` in Netlify → Domain settings
- [ ] Enable HTTPS (Netlify auto-provisions Let's Encrypt)
- [ ] Confirm `www` redirects to apex (see `_redirects`)
- [ ] Push latest `funnel-site/` so `sitemap.xml`, `robots.txt`, `og-image.png` are live

## 2. Google Search Console

1. Go to [Google Search Console](https://search.google.com/search-console)
2. Add property: `https://whopcheckout.com`
3. Verify via DNS (recommended) or Netlify HTML tag
4. Submit sitemap: `https://whopcheckout.com/sitemap.xml`
5. Use **URL Inspection** → Request indexing for homepage

## 3. Bing Webmaster Tools

1. [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. Add site and submit the same sitemap URL

## 4. Rich results validation

Test these URLs (replace with your live domain):

- [Rich Results Test](https://search.google.com/test/rich-results) — FAQ, Product, SoftwareApplication
- [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/) — OG image 1200×630
- [Twitter Card Validator](https://cards-dev.twitter.com/validator) — large image card

## 5. Performance (ranking signal)

Run [PageSpeed Insights](https://pagespeed.web.dev/) on mobile and desktop.

Target: LCP under 2.5s, CLS under 0.1.

## 6. Content & keywords (ongoing)

Primary terms to rank for:

- Whop WooCommerce plugin
- Whop payment gateway WooCommerce
- Whop checkout WordPress
- dynamic Whop checkout

Optional later:

- Blog posts: setup guides, Whop vs manual plan IDs, high-risk WooCommerce payments
- YouTube video title/description linking back to whopcheckout.com
- Backlinks from WordPress communities, Whop seller forums

## 7. Do not do

- Fake reviews or `aggregateRating` schema without real reviews
- Keyword stuffing or hidden text
- Duplicate content on multiple domains without canonical tags

## 8. Monthly maintenance

- [ ] Check Search Console for crawl errors and coverage
- [ ] Update `sitemap.xml` `<lastmod>` when you change the page
- [ ] Refresh OG image if pricing or branding changes

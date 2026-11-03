=== Lead Recorder – Lead Source Tracking for Calls and Forms ===
Contributors: leadrecorder, tomgalland21
Tags: lead tracking, call tracking, conversion tracking, woocommerce, google ads
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lead tracking for WordPress. See which ad, search or post produced every phone click, form submission and booking on your site.

== Description ==

Adds your [Lead Recorder](https://www.leadrecorder.com) tracking snippet to every page on your WordPress site. No theme edits, no tag manager.

Lead Recorder shows you where every lead came from. It matches each phone click, form submission and booking to the ad, search or post that produced it.

Most tracking setups only record form submissions. On a lot of local service websites the phone is the front door: someone lands from a Google Ads campaign, reads a page, taps the number and calls. No form, no thank you page, nothing for a conversion tag to fire on. Lead Recorder records that tap and the source behind it.

= What it does =

Once you paste in your tracking key, the plugin enqueues a single tracking script on every page view. That's it, the tracking itself happens in Lead Recorder.

The plugin stores only the tracking key extracted from your snippet and builds the script tag itself. It never renders raw pasted markup back onto your site.

= What gets tracked =

* Phone number clicks
* Form submissions
* Booking and calendar link clicks
* Email address clicks
* The page journey before each enquiry

Each one is matched back to its source: Google Ads campaign, organic search, referral, social or direct.

= WooCommerce purchase tracking =

Running WooCommerce? Add a store key from your Lead Recorder dashboard (Integrations > WooCommerce > Generate store key) under Settings > Lead Recorder, and every paid order from your online checkout shows up in Lead Recorder with the ad, search or post that brought the buyer, and how much they spent.

* Works with both the classic checkout and the block checkout.
* The order total comes from WooCommerce on your server, not the buyer's browser.
* Sent in the background after payment, so checkout is never slowed down. If the tracking script is blocked, checkout works as normal and the order shows as Unattributed.
* No customer details are sent: no name, email, address or products.
* Compatible with High-Performance Order Storage (HPOS).

Orders created in wp-admin, subscription renewals and imported orders aren't sent. Refunds aren't subtracted.

= What gets sent =

The script loads from www.leadrecorder.com and sends page views and lead events (form submissions, phone clicks, booking-link clicks) to Lead Recorder so they show in your dashboard.

Nothing is sent until an admin enters a tracking key. Without one, the plugin loads nothing.

No cookies are used, so there's no consent banner requirement for this plugin's own operation.

== External services ==

This plugin connects to Lead Recorder (https://www.leadrecorder.com), the lead tracking service the site owner has an account with. It only does so once an administrator has entered a tracking key under Settings > Lead Recorder.

What it loads: a single JavaScript file served from www.leadrecorder.com, enqueued on every page view once a tracking key has been entered.

What that script does: once loaded in a visitor's browser, it sends page view and lead conversion events (form submissions, phone number clicks and booking link clicks) to Lead Recorder's servers, so the site owner can see them in their Lead Recorder dashboard. No data is sent anywhere unless an administrator has entered a key, since without one the plugin loads nothing.

WooCommerce purchases: only if WooCommerce is active and an administrator has also entered a store key. When an order from the online checkout is paid (or marked Processing or Completed), your server sends one request to https://www.leadrecorder.com/api/woocommerce/purchase, in the background via Action Scheduler. It contains the order total, currency, order date, order ID, a link to the order in wp-admin, your tracking key and the anonymous session and visitor IDs the tracking script created. It contains no customer details: no name, email address, postal address or products. The request is signed with your store key so Lead Recorder can confirm it came from your store. This is so the site owner can see which sources lead to sales, and how much revenue each one drove.

Terms of Service: https://www.leadrecorder.com/terms
Privacy Policy: https://www.leadrecorder.com/privacy

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/lead-recorder`, or install it directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **Settings → Lead Recorder**.
4. Paste your tracking snippet (or just the key) from your [Lead Recorder dashboard](https://www.leadrecorder.com) and save.

== Frequently Asked Questions ==

= Do I need a paid Lead Recorder plan? =

Lead Recorder offers a 14-day free trial, after which a paid plan is required to keep tracking leads. Plans start at the Business tier (A$39/mo, or A$390/yr).

= Does this track phone calls? =

It records the tap on a phone number, and the source that brought that visitor to your site. It can't tell you whether the call connected or how long it lasted, since that needs a call tracking number rather than a website script.

= Does this plugin set cookies? =

No. See the Lead Recorder privacy policy for details on how the tracking script itself handles data.

= Will it work with my forms plugin? =

It listens for form submissions generically rather than integrating with a specific plugin, so Contact Form 7, Gravity Forms, WPForms, Elementor forms and most others work without extra setup.

= What happens if I don't enter a tracking key? =

Nothing is loaded on your site. The plugin only enqueues the tracking script once a valid key has been saved.

= How do I track WooCommerce orders? =

In your Lead Recorder dashboard, open Integrations, find WooCommerce and click Generate store key. Paste the key into Settings > Lead Recorder > Store key. The field only appears when WooCommerce is active. Full details: https://www.leadrecorder.com/docs/woocommerce-purchase-tracking

= Why does Lead Recorder show fewer orders than WooCommerce? =

Only paid orders from your online checkout are sent. Orders created in wp-admin, subscription renewals and imported orders are left out, and refunds aren't subtracted.

= Does it slow my site down? =

The script is loaded with `defer` so it doesn't block page rendering.

== Screenshots ==

1. The Lead Recorder settings screen under Settings → Lead Recorder.

== Changelog ==

= 1.1.0 =
* New: WooCommerce purchase tracking. Paid orders are sent to Lead Recorder with the source that drove them, for both the classic and block checkout.
* New: Store key setting, shown when WooCommerce is active.
* Declares compatibility with WooCommerce HPOS and the block checkout.
* Adds suggested privacy policy text under Settings > Privacy.

= 1.0.1 =
* Enqueue the tracking script via `wp_enqueue_script` instead of printing a raw `<script>` tag.
* Add External services section to this readme.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Adds WooCommerce purchase tracking. Nothing changes until you add a store key.

= 1.0.1 =
Tracking script is now properly enqueued; no action needed.
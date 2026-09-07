=== Lead Recorder – Lead Source Tracking for Calls and Forms ===
Contributors: leadrecorder, tomgalland21
Tags: lead tracking, call tracking, form tracking, conversion tracking, google ads
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
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

= What gets sent =

The script loads from www.leadrecorder.com and sends page views and lead events (form submissions, phone clicks, booking-link clicks) to Lead Recorder so they show in your dashboard.

Nothing is sent until an admin enters a tracking key. Without one, the plugin loads nothing.

No cookies are used, so there's no consent banner requirement for this plugin's own operation.

== External services ==

This plugin connects to Lead Recorder (https://www.leadrecorder.com), the lead tracking service the site owner has an account with. It only does so once an administrator has entered a tracking key under Settings > Lead Recorder.

What it loads: a single JavaScript file served from www.leadrecorder.com, enqueued on every page view once a tracking key has been entered.

What that script does: once loaded in a visitor's browser, it sends page view and lead conversion events (form submissions, phone number clicks and booking link clicks) to Lead Recorder's servers, so the site owner can see them in their Lead Recorder dashboard. No data is sent anywhere unless an administrator has entered a key, since without one the plugin loads nothing.

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

= Does it slow my site down? =

The script is loaded with `defer` so it doesn't block page rendering.

== Screenshots ==

1. The Lead Recorder settings screen under Settings → Lead Recorder.

== Changelog ==

= 1.0.1 =
* Enqueue the tracking script via `wp_enqueue_script` instead of printing a raw `<script>` tag.
* Add External services section to this readme.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
Tracking script is now properly enqueued; no action needed.
=== Lead Recorder ===
Contributors: leadrecorder, tomgalland21
Tags: leads, call tracking, form tracking, analytics, conversion tracking
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track leads, phone calls, form submissions and booking clicks on your WordPress site with one snippet from Lead Recorder.

== Description ==

Adds your [Lead Recorder](https://www.leadrecorder.com) tracking snippet to every page on your WordPress site. No theme edits, no tag manager.

Lead Recorder shows you where every lead came from. It matches each phone click, form submission and booking to the ad, search or post that produced it.

= What it does =

Once you paste in your tracking key, the plugin enqueues a single tracking script on every page view. That's it — the tracking itself happens in Lead Recorder.

The plugin stores only the tracking key extracted from your snippet and builds the script tag itself. It never renders raw pasted markup back onto your site.

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

No, the free tier works.

= Does this plugin set cookies? =

No. See the Lead Recorder privacy policy for details on how the tracking script itself handles data.

= What happens if I don't enter a tracking key? =

Nothing is loaded on your site. The plugin only enqueues the tracking script once a valid key has been saved.

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

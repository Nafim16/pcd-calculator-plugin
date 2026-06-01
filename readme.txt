=== PCD Pricing Calculator ===
Contributors: ByteBlazeIT
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Embeds the PCD simple/complex pricing calculator and stores quote submissions in WordPress.

== Installation ==

1. Upload the `pcd-pricing-calculator` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Add shortcode `[pcd_calculator]` to any page (or Elementor Shortcode widget)

== Admin ==

Go to **PCD Quotes** in the WordPress admin to view, inspect, and delete submissions.

**PCD Quotes → Email notifications** — enable or disable admin emails for new submissions, and set one or more recipient addresses (comma-separated). Defaults to the WordPress admin email.

== Notes ==

* Submissions are stored in the database table `wp_pcd_quote_submissions` (prefix may vary).
* Full submission data is saved in the `payload_json` column.
* Calculator type is stored as `simple` or `complex`.

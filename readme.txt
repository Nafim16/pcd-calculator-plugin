=== PCD Pricing Calculator ===
Contributors: ByteBlazeIT
Tags: calculator, pricing, quotes, survey, architectural drawings
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embeds a combined Simple and Complex PCD pricing calculator on your site, stores quote submissions in WordPress, and gives admins tools to manage pricing, submissions, and email alerts.

== Description ==

PCD Pricing Calculator is a WordPress plugin for architectural / survey pricing quotes. Visitors use a single shortcode embed to switch between **Simple** and **Complex** calculator modes, build a quote, and submit their contact details. Submissions are saved in your WordPress database—no external webhook or third-party form service required.

**Simple calculator** — Streamlined flow for measured survey, topographical survey, existing drawing prep (floor / elevations / sections by property size band), and proposed drawing types (extensions, conversions, dormers, etc.).

**Complex calculator** — Full breakdown for measured and topographical surveys (sqm + location multipliers), existing drawings (quantities, complexity flags, repetition), and proposed drawings (scope flags, clear-instructions discount). Supports “price on request” when complexity limits are exceeded.

Both modes share contact fields (name, email, optional phone, required address), location-based survey pricing, and submit through the WordPress REST API.

== Features ==

* **Shortcode embed** — `[pcd_calculator]` on any page or in Elementor’s Shortcode widget
* **Dual mode UI** — Simple and Complex calculators in one interface (Simple is the default)
* **WordPress storage** — Submissions saved to a custom database table with indexed columns plus full JSON payload
* **Admin submissions list** — Sortable list with ID, date, type, name, email, phone, address, location, size, total, and actions
* **View submission details** — Rich admin detail page with contact, property, services, pricing breakdown, and raw JSON
* **Delete submissions** — Nonce-protected delete from list or detail view
* **Drawing pricing settings** — Admin-configurable per-drawing fees for Simple and Complex calculators (survey formulas remain in the calculator logic)
* **Email notifications** — Optional admin email when a new quote is submitted (comma-separated recipients)
* **REST API submit endpoint** — `POST /wp-json/pcd-calculator/v1/submit` with WordPress REST nonce (CSRF protection)
* **Security hardening** — Rate limiting (10 submissions per IP per 15 minutes), honeypot field, field length limits, sanitized admin output (XSS protection)

== Installation ==

1. Upload the `pcd-pricing-calculator` folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins** in WordPress admin
3. On activation, the plugin creates the `wp_pcd_quote_submissions` table (table prefix may vary)
4. Add the shortcode `[pcd_calculator]` to a page or post

== Usage ==

= Front end =

Place `[pcd_calculator]` on the page where clients should get a quote. The plugin injects calculator configuration (REST URL, nonce, drawing prices) and loads the calculator asset.

Visitors complete the form and click **Submit Quote Request**. Data is sent to your site via REST and stored in WordPress.

= Admin: PCD Quotes =

After activation, a **PCD Quotes** menu appears in the admin sidebar.

* **All submissions** — Browse, view details, and delete quote requests
* **Drawing pricing** — Edit Simple existing (floor / elevations / sections per size band), Simple proposed types, Complex existing drawing types, and Complex proposed affected drawing types
* **Email notifications** — Enable or disable new-quote emails and set recipient address(es)

== Admin ==

= Submissions list =

Columns: ID, Submitted, Type (Simple/Complex), Name, Email, Phone, Address, Location, Size (sqm), Total (or “Price on request”), Actions (View details | Delete).

= View submission =

Shows a summary hero (name, type, date, estimated total), contact and property cards, full service and pricing sections per calculator mode, and an expandable raw JSON block for developers.

= Drawing pricing =

Controls **drawing prep fees only**. Measured survey, topographical survey, location multipliers, and complexity logic are built into the calculator JavaScript. Saved prices are passed to the front end via `window.pcdCalculator.pricing` and applied by `pcd-pricing-bridge.js`.

= Email notifications =

When enabled, each successful submission triggers `wp_mail()` to the configured address(es) with submission ID, type, contact details, location, size, total, and a link to view the submission in admin. Delivery depends on your host’s mail setup; use an SMTP plugin if emails do not arrive.

== Data storage ==

Submissions are stored in `{prefix}pcd_quote_submissions` with:

* Indexed columns: calculator mode, contact fields, location label, property sqm, grand total, quote-on-request flag, submitted date, IP address
* `payload_json`: complete submission JSON (services, pricing breakdown, meta, timestamps, etc.)

Calculator mode values: `simple` or `complex`.

== Security ==

* Admin pages and settings require `manage_options`
* Delete actions use WordPress nonces
* Public submit requires a valid `wp_rest` nonce (issued on pages that render the shortcode)
* Rate limiting and honeypot reduce spam and abuse
* User-submitted content is sanitized on save and escaped in admin views

For production sites, keep WordPress and PHP updated and consider additional anti-spam (e.g. CAPTCHA) if you receive bot traffic.

== Changelog ==

= 1.1.2 =
* Version bump
* Security: stored XSS fixes in admin detail view, rate limiting, honeypot, field length limits
* Email notifications for new submissions
* Drawing pricing admin and front-end bridge
* Combined Simple/Complex calculator with REST submit

= 1.0.0 =
* Initial release: shortcode embed, submissions storage, admin list and detail views

== Frequently Asked Questions ==

= Does this replace Make.com or Zapier? =

Yes. Submissions are stored locally in WordPress. No external webhook URL is required.

= Can I use only Simple or only Complex? =

The embed includes both modes with a toggle. Simple is the default when the page loads.

= Why are my notification emails not sending? =

WordPress `wp_mail()` depends on server configuration. Install and configure an SMTP plugin if needed, and confirm the recipient under **PCD Quotes → Email notifications**.

= Can visitors see other people’s submissions? =

No. Submissions are only visible in the WordPress admin to users with `manage_options`.

== Upgrade Notice ==

= 1.1.2 =
Recommended update: includes security hardening and email notifications. Re-upload the plugin folder or update via your deployment process.

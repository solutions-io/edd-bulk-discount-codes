=== EDD Bulk Discount Codes ===
Contributors: solutionsio
Tags: easy-digital-downloads, edd, discount, coupon, bulk
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate Easy Digital Downloads discount codes in bulk from a simple admin page.

== Description ==

Adds **Downloads → Bulk Discount Codes** to the WordPress admin. Fill in a campaign name, choose how many codes you want, set the discount type, amount, max uses and expiration, and click Generate. Codes are created in EDD and shown in a copy-friendly textarea or downloadable as CSV.

Free, open-source alternative to paid generator add-ons.

* Up to 1,000 codes per run
* Percent or flat-rate discounts
* Optional code prefix for batch tracking
* Configurable code length, max uses, expiration
* CSV download of the last batch
* Fully translatable
* One file, no settings page, no tracking

== Installation ==

1. Upload to `/wp-content/plugins/` or install via the plugin uploader.
2. Activate.
3. Make sure Easy Digital Downloads is active.
4. Go to **Downloads → Bulk Discount Codes**.

== Frequently Asked Questions ==

= Does this require Easy Digital Downloads? =

Yes. The plugin uses `edd_store_discount()` to create the codes.

= Why is the limit 1,000 per run? =

To stay inside typical PHP execution time limits. For larger batches, run multiple times with different prefixes.

= Where do the codes go? =

Straight into EDD's discounts table. Find them under **Downloads → Discounts**.

== Changelog ==

= 1.1.0 =
* CSV download of the most recent batch.
* Translation support.
* Form repopulation on validation error.
* Security and WPCS hardening (`wp_unslash`, `nocache_headers`).

= 1.0.0 =
* Initial release.

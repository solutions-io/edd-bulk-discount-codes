# EDD Bulk Discount Codes

A small WordPress plugin that adds a bulk discount code generator to [Easy Digital Downloads](https://easydigitaldownloads.com/). Free and open-source alternative to paid generator add-ons.

![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue) ![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)

## What it does

Adds **Downloads → Bulk Discount Codes** to the WordPress admin. Generate up to 1,000 EDD discount codes in one click, copy them from a textarea, or download as CSV.

- Percent or flat-rate discounts
- Optional code prefix for batch tracking
- Configurable code length, max uses, expiration
- Single-use-per-customer toggle
- CSV download of the last batch
- Translatable, one file, no settings page, no tracking

## Install

Download the latest release zip and upload via **Plugins → Add New → Upload Plugin**, or clone into `wp-content/plugins/`:

```bash
cd wp-content/plugins
git clone https://github.com/solutions-io/edd-bulk-discount-codes.git
```

Activate, then go to **Downloads → Bulk Discount Codes**.

Requires WordPress 6.0+, PHP 7.4+, and Easy Digital Downloads.

## Contributing

PRs welcome. Please keep the plugin a single file unless a feature really requires splitting it — simplicity is the point.

## License

GPL-2.0-or-later. Built and maintained by [solutions.io](https://solutions.io).

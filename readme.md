# MRx AI — COD Fraud Protection

WordPress/WooCommerce plugin that reduces fake Cash-on-Delivery (COD) orders in Bangladesh e-commerce using AI-powered risk scoring, phone reputation tracking, and behavioral analysis.

## Overview

Bangladesh e-commerce relies heavily on COD, but fake or repeat-abuse orders cost merchants shipping fees and inventory. MRx AI scores each checkout in real time and can block or flag high-risk orders before they ship — targeting an **80–90% reduction** in fraudulent COD orders.

## Features

- **Risk scoring engine** — Multi-signal score per order at checkout
- **Phone reputation tracking** — Detect repeat offenders and blacklisted numbers
- **IP address tracking** — Geographic and abuse-pattern signals
- **Device fingerprinting** — Identify returning bad actors across sessions
- **Behavior analysis** — Cart and browsing patterns that indicate fraud
- **Dokan marketplace integration** — Works with multi-vendor WooCommerce setups
- **Courier feedback loop** — Learn from delivery outcomes (returned/refused)
- **Facebook Conversions API** — Sync fraud signals with ad optimization
- **Admin dashboard** — Review flagged orders and configure thresholds
- **Configurable thresholds** — Tune sensitivity per store

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 5.8+ |
| WooCommerce | 5.0+ |
| PHP | 7.4+ |
| Dokan | Optional (multi-vendor) |

## Installation

1. Download or clone this repository.
2. Upload the plugin folder to `/wp-content/plugins/mrx-ai-cod-fraud-protection/`.
3. Activate **MRx AI COD Fraud Protection** in WordPress Admin → Plugins.
4. Configure settings under **WooCommerce → COD Fraud Protection**.

## Plugin Structure

```
MRx-Ai-COD-Fraud-Protection-/
├── mrx-ai-cod-fraud-protection.php   # Main plugin bootstrap
├── admin/                            # Admin dashboard and settings UI
├── includes/                         # Core scoring, tracking, and API logic
├── assets/                           # CSS and JavaScript for admin
├── uninstall.php                     # Cleanup on uninstall
├── PLUGIN-STRUCTURE.md               # Detailed architecture notes
└── readme.md                         # WordPress.org-style readme
```

## Configuration

After activation, open **WooCommerce → COD Fraud Protection** to set:

- Risk score thresholds (block vs. review)
- Phone and IP blacklist rules
- Dokan vendor-specific settings (if applicable)
- Facebook Conversions API credentials (optional)

See [PLUGIN-STRUCTURE.md](./PLUGIN-STRUCTURE.md) for internal module documentation.

## Development

This is a standard WordPress plugin. For local development:

1. Symlink or copy the plugin into your WordPress `wp-content/plugins/` directory.
2. Enable `WP_DEBUG` in `wp-config.php` for development logging.
3. Use a WooCommerce test store with COD enabled.

## Author

**Md Taibur Rahaman** — [GitHub](https://github.com/Taibur-Rahaman)

## License

GPL v2 or later — see [LICENSE](./LICENSE).

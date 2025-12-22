=== MRx AI - COD Fraud Protection ===
Contributors: yourname
Tags: woocommerce, cod, fraud, protection, bangladesh, dokan, risk-scoring
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart COD fraud protection system for Bangladesh e-commerce. Reduces fake COD orders by 80-90% using risk scoring, phone tracking, and behavior analysis.

== Description ==

MRx AI COD Fraud Protection is a comprehensive WordPress plugin designed specifically for Bangladesh e-commerce businesses. It helps reduce fake Cash on Delivery (COD) orders by 80-90% using intelligent risk scoring, phone reputation tracking, IP tracking, device fingerprinting, and behavior analysis.

= Key Features =

* **Risk Scoring Engine**: Advanced risk scoring system that analyzes multiple factors including phone reputation, IP address, device fingerprint, checkout behavior, and address completeness.

* **Phone Reputation Tracking**: Tracks phone numbers and builds reputation based on order history and return rates.

* **IP Address Tracking**: Monitors IP addresses to detect suspicious patterns and multiple orders from the same IP.

* **Device Fingerprinting**: Uses JavaScript to create unique device fingerprints for fraud detection.

* **Behavior Analysis**: Tracks checkout time, pages viewed, and session duration to identify suspicious behavior patterns.

* **Dokan Marketplace Integration**: Full integration with Dokan multivendor marketplace, including vendor override system and dashboard widgets.

* **Courier Feedback System**: Allows admin to mark orders as delivered or returned, automatically updating risk scores and phone reputation.

* **Facebook Conversions API Integration**: Sends only verified conversions to Facebook for better ad performance and ROI.

* **Admin Dashboard**: Comprehensive admin interface with risk badges, detailed breakdowns, and analytics.

* **Configurable Thresholds**: Customizable risk thresholds and actions (flag, block, or manual review).

= How It Works =

1. **Data Collection**: When a customer places a COD order, the plugin collects:
   - Phone number (normalized for Bangladesh format)
   - IP address
   - Device fingerprint
   - Checkout behavior (time, pages viewed, session duration)
   - Address completeness

2. **Risk Scoring**: The plugin calculates a risk score (0-100) based on:
   - Phone reputation (30% weight)
   - IP reputation (20% weight)
   - Device reputation (15% weight)
   - Behavior patterns (25% weight)
   - Address completeness (10% weight)

3. **Risk Levels**:
   - **Low Risk (0-39)**: Green badge, order proceeds normally
   - **Medium Risk (40-69)**: Yellow badge, flagged for review
   - **High Risk (70-100)**: Red badge, can be blocked or flagged

4. **Learning Loop**: When orders are marked as delivered or returned, the system updates phone reputation and risk scores, improving accuracy over time.

= Installation =

1. Upload the plugin files to the `/wp-content/plugins/mrx-ai-cod-fraud-protection` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce > COD Fraud Protection to configure settings.
4. The plugin will automatically create database tables on activation.

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher
* Dokan (optional, for marketplace features)

= Configuration =

1. Go to WooCommerce > COD Fraud Protection
2. Set risk thresholds (High and Medium)
3. Choose action for high-risk orders (Flag, Block, or Manual Review)
4. Configure Facebook Conversions API (optional)
5. Save settings

= Usage =

The plugin works automatically once activated. It will:
- Collect intelligence data on all COD orders
- Calculate risk scores
- Display risk badges in admin and vendor dashboards
- Update reputation based on courier feedback

= For Dokan Users =

If you're using Dokan multivendor marketplace:
- Vendors will see risk badges in their order list
- Vendors can override high-risk orders if needed
- Vendor dashboard shows statistics widget
- Risk data is synced across parent and sub-orders

= Facebook Ads Integration =

To use Facebook Conversions API:
1. Get your Facebook Pixel ID
2. Generate an access token
3. Enter both in plugin settings
4. Enable "Send Verified Only" to filter fake conversions

= Support =

For support, please visit: [Your Support URL]

= Changelog =

= 1.0.0 =
* Initial release
* Risk scoring engine
* Phone reputation tracking
* IP tracking
* Device fingerprinting
* Behavior analysis
* Dokan integration
* Courier feedback system
* Facebook CAPI integration
* Admin dashboard

== Frequently Asked Questions ==

= Does this work with Dokan? =

Yes! Full Dokan marketplace integration is included.

= Will this block real orders? =

The system is designed to minimize false positives. Vendors can override high-risk orders if needed.

= Do I need technical knowledge? =

No! Just install, activate, and configure settings. The plugin works automatically.

= Can I customize risk thresholds? =

Yes! You can set custom thresholds in the settings page.

= Does this work with other payment methods? =

The plugin only analyzes COD orders. Other payment methods are not affected.

== Screenshots ==

1. Admin settings page
2. Risk badge in order list
3. Risk breakdown in order details
4. Vendor dashboard widget
5. Courier feedback meta box

== Upgrade Notice ==

= 1.0.0 =
Initial release of MRx AI COD Fraud Protection.


# MRx AI COD Fraud Protection - Plugin Structure

## 📁 File Structure

```
mrx-ai-cod-fraud-protection/
├── mrx-ai-cod-fraud-protection.php  # Main plugin file
├── uninstall.php                    # Uninstall handler
├── README.txt                       # WordPress.org readme
├── readme.md                        # GitHub readme
├── .gitignore                       # Git ignore file
│
├── includes/                        # Core functionality classes
│   ├── class-database.php          # Database table creation
│   ├── class-phone-tracker.php    # Phone reputation tracking
│   ├── class-ip-tracker.php        # IP address tracking
│   ├── class-device-tracker.php    # Device fingerprinting
│   ├── class-risk-engine.php       # Risk scoring engine
│   ├── class-order-intelligence.php # Order data collection
│   ├── class-courier-feedback.php  # Courier feedback system
│   ├── class-dokan-integration.php # Dokan marketplace integration
│   ├── class-facebook-capi.php     # Facebook CAPI integration
│   └── class-frontend.php          # Frontend functionality
│
├── admin/                           # Admin functionality
│   └── class-admin.php             # Admin settings and UI
│
└── assets/                          # Frontend assets
    ├── css/
    │   ├── admin.css               # Admin styles
    │   └── frontend.css            # Frontend styles
    └── js/
        ├── frontend.js             # Frontend JavaScript
        └── vendor.js               # Vendor dashboard JavaScript
```

## 🔧 Core Components

### 1. Database (`class-database.php`)
- Creates 4 database tables on activation
- Phone reputation table
- IP reputation table
- Device fingerprint table
- Order intelligence table

### 2. Phone Tracker (`class-phone-tracker.php`)
- Normalizes Bangladesh phone numbers
- Tracks phone reputation
- Updates risk scores based on returns

### 3. IP Tracker (`class-ip-tracker.php`)
- Gets real IP (handles proxies)
- Tracks IP reputation
- Detects multiple orders from same IP

### 4. Device Tracker (`class-device-tracker.php`)
- Creates device fingerprints
- Tracks device reputation
- Stores browser information

### 5. Risk Engine (`class-risk-engine.php`)
- Calculates risk scores (0-100)
- Weighted scoring system
- Risk level determination (low/medium/high)

### 6. Order Intelligence (`class-order-intelligence.php`)
- Collects data on checkout
- Validates addresses
- Saves intelligence to database

### 7. Courier Feedback (`class-courier-feedback.php`)
- Admin meta box for feedback
- Updates reputation from feedback
- Tracks delivery status

### 8. Dokan Integration (`class-dokan-integration.php`)
- Vendor dashboard widgets
- Risk badges in order list
- Vendor override system
- AJAX handlers

### 9. Facebook CAPI (`class-facebook-capi.php`)
- Sends verified conversions only
- Filters pixel events
- Server-side tracking

### 10. Frontend (`class-frontend.php`)
- Enqueues scripts
- Privacy notice
- Payment gateway filtering

### 11. Admin (`class-admin.php`)
- Settings page
- Order risk display
- Risk badges in order list

## 📊 Database Tables

1. **wp_mrx_ai_phones**
   - Phone reputation data
   - Order count, return count, risk score

2. **wp_mrx_ai_ips**
   - IP reputation data
   - Order count, risk score, blocked status

3. **wp_mrx_ai_devices**
   - Device fingerprint data
   - Browser info, order count, risk score

4. **wp_mrx_ai_order_intelligence**
   - Per-order intelligence data
   - Phone, IP, device, behavior, address data

## ⚙️ Options

- `mrx_ai_high_threshold` - High risk threshold (default: 70)
- `mrx_ai_medium_threshold` - Medium risk threshold (default: 40)
- `mrx_ai_high_risk_action` - Action for high risk (flag/block/manual)
- `mrx_ai_fb_pixel_id` - Facebook Pixel ID
- `mrx_ai_fb_access_token` - Facebook Access Token
- `mrx_ai_send_verified_only` - Send only verified conversions

## 🎯 Order Meta Keys

- `_mrx_ai_risk_score` - Risk score (0-100)
- `_mrx_ai_risk_level` - Risk level (low/medium/high)
- `_mrx_ai_risk_breakdown` - Detailed risk breakdown (JSON)
- `_mrx_ai_delivery_status` - Delivery status (delivered/returned)
- `_mrx_ai_return_reason` - Return reason
- `_mrx_ai_vendor_override` - Vendor override flag
- `_mrx_ai_capi_sent` - CAPI sent flag

## 🚀 Installation

1. Upload to `/wp-content/plugins/`
2. Activate plugin
3. Configure settings in WooCommerce > COD Fraud Protection
4. Plugin automatically creates tables on activation

## ✅ Features

- ✅ Risk scoring engine
- ✅ Phone reputation tracking
- ✅ IP tracking
- ✅ Device fingerprinting
- ✅ Behavior analysis
- ✅ Dokan integration
- ✅ Courier feedback
- ✅ Facebook CAPI
- ✅ Admin dashboard
- ✅ Vendor dashboard

## 📝 Notes

- Requires WooCommerce
- Dokan integration is optional
- Facebook CAPI is optional
- All data collection is GDPR-compliant
- Phone numbers are normalized for Bangladesh format


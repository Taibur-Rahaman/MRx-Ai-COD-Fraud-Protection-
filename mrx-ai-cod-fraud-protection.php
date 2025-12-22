<?php
/**
 * Plugin Name: MRx AI - COD Fraud Protection
 * Plugin URI: https://yourwebsite.com/mrx-ai-cod-fraud-protection
 * Description: Smart COD fraud protection system for Bangladesh e-commerce. Reduces fake COD orders by 80-90% using risk scoring, phone tracking, and behavior analysis.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mrx-ai-cod-fraud
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MRX_AI_VERSION', '1.0.0');
define('MRX_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MRX_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MRX_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class MRx_AI_COD_Fraud_Protection {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->includes();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('mrx-ai-cod-fraud', false, dirname(MRX_AI_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-database.php';
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-phone-tracker.php';
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-ip-tracker.php';
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-device-tracker.php';
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-risk-engine.php';
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-order-intelligence.php';
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-courier-feedback.php';
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-dokan-integration.php';
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-facebook-capi.php';
        
        if (is_admin()) {
            require_once MRX_AI_PLUGIN_DIR . 'admin/class-admin.php';
        }
        
        require_once MRX_AI_PLUGIN_DIR . 'includes/class-frontend.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize components
        MRx_AI_Database::get_instance();
        MRx_AI_Phone_Tracker::get_instance();
        MRx_AI_IP_Tracker::get_instance();
        MRx_AI_Device_Tracker::get_instance();
        MRx_AI_Risk_Engine::get_instance();
        MRx_AI_Order_Intelligence::get_instance();
        MRx_AI_Courier_Feedback::get_instance();
        MRx_AI_Facebook_CAPI::get_instance();
        
        if (class_exists('WeDevs_Dokan')) {
            MRx_AI_Dokan_Integration::get_instance();
        }
        
        if (is_admin()) {
            MRx_AI_Admin::get_instance();
        }
        
        MRx_AI_Frontend::get_instance();
    }
    
    /**
     * Activate plugin
     */
    public function activate() {
        // Create database tables
        MRx_AI_Database::create_tables();
        
        // Set default options
        if (!get_option('mrx_ai_high_threshold')) {
            update_option('mrx_ai_high_threshold', 70);
        }
        if (!get_option('mrx_ai_medium_threshold')) {
            update_option('mrx_ai_medium_threshold', 40);
        }
        if (!get_option('mrx_ai_high_risk_action')) {
            update_option('mrx_ai_high_risk_action', 'flag');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Deactivate plugin
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('mrx_ai_sync_courier_status');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('MRx AI COD Fraud Protection requires WooCommerce to be installed and active.', 'mrx-ai-cod-fraud'); ?></p>
        </div>
        <?php
    }
}

/**
 * Initialize plugin
 */
function mrx_ai_cod_fraud_protection() {
    return MRx_AI_COD_Fraud_Protection::get_instance();
}

// Start the plugin
mrx_ai_cod_fraud_protection();


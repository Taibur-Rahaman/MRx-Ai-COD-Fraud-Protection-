<?php
/**
 * Frontend functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('woocommerce_available_payment_gateways', array($this, 'filter_payment_gateways'));
        add_action('woocommerce_checkout_before_order_review', array($this, 'privacy_notice'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_checkout()) {
            wp_enqueue_script('mrx-ai-frontend', MRX_AI_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), MRX_AI_VERSION, true);
            wp_localize_script('mrx-ai-frontend', 'mrxAiFrontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mrx_ai_frontend_nonce')
            ));
        }
    }
    
    /**
     * Filter payment gateways
     */
    public function filter_payment_gateways($available_gateways) {
        if (!is_checkout()) {
            return $available_gateways;
        }
        
        $high_risk_action = get_option('mrx_ai_high_risk_action', 'flag');
        
        if ($high_risk_action === 'block') {
            // Note: Risk score calculated after order creation, so this is simplified
            // In production, you'd calculate risk during checkout
            $risk_score = 0; // Placeholder
            
            $high_threshold = get_option('mrx_ai_high_threshold', 70);
            
            if ($risk_score >= $high_threshold) {
                unset($available_gateways['cod']);
                wc_add_notice(
                    __('Cash on Delivery is not available for this order. Please use another payment method.', 'mrx-ai-cod-fraud'),
                    'error'
                );
            }
        }
        
        return $available_gateways;
    }
    
    /**
     * Privacy notice
     */
    public function privacy_notice() {
        ?>
        <div class="mrx-ai-privacy-notice" style="background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-left: 4px solid #0073aa;">
            <h4><?php _e('Fraud Protection Notice', 'mrx-ai-cod-fraud'); ?></h4>
            <p style="margin: 0; font-size: 13px;">
                <?php _e('We collect order data (phone, address, IP) for fraud prevention purposes only. This helps us protect both you and our business from fake orders.', 'mrx-ai-cod-fraud'); ?>
                <?php if (get_privacy_policy_url()) : ?>
                    <a href="<?php echo esc_url(get_privacy_policy_url()); ?>"><?php _e('Privacy Policy', 'mrx-ai-cod-fraud'); ?></a>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}


<?php
/**
 * Order intelligence collection
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Order_Intelligence {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('woocommerce_checkout_order_processed', array($this, 'collect_intelligence'), 10, 1);
        add_action('woocommerce_checkout_order_processed', array($this, 'calculate_order_risk'), 20, 1);
    }
    
    /**
     * Collect intelligence data
     */
    public function collect_intelligence($order_id) {
        $order = wc_get_order($order_id);
        
        // Only for COD orders
        if ($order->get_payment_method() !== 'cod') {
            return;
        }
        
        global $wpdb;
        
        // Get data from POST
        $phone = isset($_POST['billing_phone']) ? $_POST['billing_phone'] : $order->get_billing_phone();
        $ip = MRx_AI_IP_Tracker::get_real_ip();
        $device_hash = isset($_POST['mrx_ai_device_hash']) ? sanitize_text_field($_POST['mrx_ai_device_hash']) : '';
        $device_data = isset($_POST['mrx_ai_device_data']) ? sanitize_text_field($_POST['mrx_ai_device_data']) : '';
        $checkout_time = isset($_POST['mrx_ai_checkout_time']) ? intval($_POST['mrx_ai_checkout_time']) : 0;
        $pages_viewed = isset($_POST['mrx_ai_pages_viewed']) ? intval($_POST['mrx_ai_pages_viewed']) : 0;
        $session_duration = isset($_POST['mrx_ai_session_duration']) ? intval($_POST['mrx_ai_session_duration']) : 0;
        
        // Validate address
        $address_validation = self::validate_address(
            $order->get_billing_address_1(),
            $order->get_billing_address_2(),
            $order->get_billing_city(),
            $order->get_billing_postcode()
        );
        
        // Collect risk factors
        $risk_factors = array();
        
        if ($checkout_time > 0 && $checkout_time < 60) {
            $risk_factors[] = 'quick_checkout';
        }
        if ($pages_viewed > 0 && $pages_viewed < 3) {
            $risk_factors[] = 'low_pages_viewed';
        }
        if ($address_validation['completeness'] < 60) {
            $risk_factors[] = 'incomplete_address';
        }
        
        // Update reputations
        if ($phone) {
            MRx_AI_Phone_Tracker::update_reputation($phone, $order_id, false);
        }
        MRx_AI_IP_Tracker::update_reputation($ip, $order_id);
        if ($device_hash) {
            MRx_AI_Device_Tracker::update_reputation($device_hash, $device_data, $order_id);
        }
        
        // Save order intelligence
        $table = $wpdb->prefix . 'mrx_ai_order_intelligence';
        $wpdb->insert($table, array(
            'order_id' => $order_id,
            'phone' => MRx_AI_Phone_Tracker::normalize_phone($phone),
            'ip_address' => $ip,
            'device_hash' => $device_hash,
            'checkout_time' => $checkout_time,
            'pages_viewed' => $pages_viewed,
            'session_duration' => $session_duration,
            'address_completeness' => $address_validation['completeness'],
            'risk_factors' => json_encode($risk_factors)
        ));
    }
    
    /**
     * Calculate order risk
     */
    public function calculate_order_risk($order_id) {
        $order = wc_get_order($order_id);
        
        // Only for COD orders
        if ($order->get_payment_method() !== 'cod') {
            return;
        }
        
        $risk_engine = MRx_AI_Risk_Engine::get_instance();
        $risk_score = $risk_engine->calculate_risk_score($order_id);
        $risk_level = $risk_engine->get_risk_level($risk_score);
        $risk_breakdown = $risk_engine->get_risk_breakdown($order_id);
        
        // Save to order meta
        $order->update_meta_data('_mrx_ai_risk_score', $risk_score);
        $order->update_meta_data('_mrx_ai_risk_level', $risk_level);
        $order->update_meta_data('_mrx_ai_risk_breakdown', json_encode($risk_breakdown));
        $order->save();
    }
    
    /**
     * Validate address (Bangladesh style)
     */
    public static function validate_address($address_1, $address_2, $city, $postcode) {
        $completeness = 0;
        $max_score = 100;
        
        // Address line 1 (required)
        if (!empty($address_1) && strlen($address_1) > 10) {
            $completeness += 30;
        }
        
        // Address line 2 (optional but good)
        if (!empty($address_2)) {
            $completeness += 20;
        }
        
        // City/Area (required)
        if (!empty($city) && strlen($city) > 2) {
            $completeness += 30;
            
            // Check if valid BD area
            $bd_areas = self::get_bd_areas();
            if (in_array(strtolower($city), array_map('strtolower', $bd_areas))) {
                $completeness += 10; // Bonus for valid area
            }
        }
        
        // Postcode (optional)
        if (!empty($postcode) && strlen($postcode) >= 4) {
            $completeness += 10;
        }
        
        return array(
            'completeness' => min($completeness, $max_score),
            'is_complete' => $completeness >= 60
        );
    }
    
    /**
     * Get Bangladesh areas
     */
    private static function get_bd_areas() {
        return array(
            'Dhaka', 'Gulshan', 'Dhanmondi', 'Uttara', 'Banani', 'Mohakhali',
            'Mirpur', 'Wari', 'Old Dhaka', 'Motijheel', 'Farmgate', 'Tejgaon',
            'Chittagong', 'Sylhet', 'Rajshahi', 'Khulna', 'Barisal', 'Rangpur'
        );
    }
}


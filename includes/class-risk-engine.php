<?php
/**
 * Risk scoring engine
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Risk_Engine {
    
    private static $instance = null;
    private $order_id;
    private $order;
    private $weights;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Default weights (can be configured)
        $this->weights = array(
            'phone' => 30,
            'ip' => 20,
            'device' => 15,
            'behavior' => 25,
            'address' => 10
        );
    }
    
    /**
     * Calculate risk score for order
     */
    public function calculate_risk_score($order_id) {
        $this->order_id = $order_id;
        $this->order = wc_get_order($order_id);
        
        if (!$this->order) {
            return 0;
        }
        
        $phone_risk = $this->calculate_phone_risk();
        $ip_risk = $this->calculate_ip_risk();
        $device_risk = $this->calculate_device_risk();
        $behavior_risk = $this->calculate_behavior_risk();
        $address_risk = $this->calculate_address_risk();
        
        // Weighted sum
        $total_risk = (
            ($phone_risk * $this->weights['phone']) +
            ($ip_risk * $this->weights['ip']) +
            ($device_risk * $this->weights['device']) +
            ($behavior_risk * $this->weights['behavior']) +
            ($address_risk * $this->weights['address'])
        ) / 100;
        
        return min(100, max(0, round($total_risk)));
    }
    
    /**
     * Calculate phone risk
     */
    private function calculate_phone_risk() {
        $phone = MRx_AI_Phone_Tracker::normalize_phone($this->order->get_billing_phone());
        if (!$phone) {
            return 80; // Invalid phone = high risk
        }
        
        $reputation = MRx_AI_Phone_Tracker::get_reputation($phone);
        
        if ($reputation['order_count'] === 0) {
            return 40; // New phone = medium risk
        }
        
        return $reputation['risk_score'];
    }
    
    /**
     * Calculate IP risk
     */
    private function calculate_ip_risk() {
        $ip = MRx_AI_IP_Tracker::get_real_ip();
        $reputation = MRx_AI_IP_Tracker::get_reputation($ip);
        
        if ($reputation['blocked']) {
            return 100; // Blocked IP = max risk
        }
        
        if ($reputation['order_count'] === 0) {
            return 20; // New IP = low risk
        } elseif ($reputation['order_count'] === 1) {
            return 30; // 1 order = low-medium risk
        } elseif ($reputation['order_count'] <= 3) {
            return 50; // 2-3 orders = medium risk
        } else {
            return min(100, $reputation['order_count'] * 15); // 4+ orders = high risk
        }
    }
    
    /**
     * Calculate device risk
     */
    private function calculate_device_risk() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mrx_ai_order_intelligence';
        $intelligence = $wpdb->get_row($wpdb->prepare(
            "SELECT device_hash FROM $table WHERE order_id = %d",
            $this->order_id
        ));
        
        if (!$intelligence || !$intelligence->device_hash) {
            return 30; // No device data = medium-low risk
        }
        
        $reputation = MRx_AI_Device_Tracker::get_reputation($intelligence->device_hash);
        
        if ($reputation['order_count'] === 0) {
            return 25; // New device = low-medium risk
        }
        
        return min(100, $reputation['risk_score']);
    }
    
    /**
     * Calculate behavior risk
     */
    private function calculate_behavior_risk() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mrx_ai_order_intelligence';
        $intelligence = $wpdb->get_row($wpdb->prepare(
            "SELECT checkout_time, pages_viewed, session_duration FROM $table WHERE order_id = %d",
            $this->order_id
        ));
        
        if (!$intelligence) {
            return 50; // No behavior data = medium risk
        }
        
        $risk_score = 0;
        $factors = 0;
        
        // Checkout time risk
        if ($intelligence->checkout_time > 0) {
            $factors++;
            if ($intelligence->checkout_time < 60) {
                $risk_score += 80; // Very quick checkout = high risk
            } elseif ($intelligence->checkout_time < 120) {
                $risk_score += 50; // Quick checkout = medium risk
            } else {
                $risk_score += 20; // Normal checkout = low risk
            }
        }
        
        // Pages viewed risk
        if ($intelligence->pages_viewed > 0) {
            $factors++;
            if ($intelligence->pages_viewed < 3) {
                $risk_score += 70; // Low pages = high risk
            } elseif ($intelligence->pages_viewed < 5) {
                $risk_score += 40; // Medium pages = medium risk
            } else {
                $risk_score += 15; // Many pages = low risk
            }
        }
        
        // Session duration risk
        if ($intelligence->session_duration > 0) {
            $factors++;
            if ($intelligence->session_duration < 120) {
                $risk_score += 75; // Very short session = high risk
            } elseif ($intelligence->session_duration < 300) {
                $risk_score += 45; // Short session = medium risk
            } else {
                $risk_score += 20; // Normal session = low risk
            }
        }
        
        if ($factors === 0) {
            return 50; // No data = medium risk
        }
        
        return min(100, round($risk_score / $factors));
    }
    
    /**
     * Calculate address risk
     */
    private function calculate_address_risk() {
        $validation = MRx_AI_Order_Intelligence::validate_address(
            $this->order->get_billing_address_1(),
            $this->order->get_billing_address_2(),
            $this->order->get_billing_city(),
            $this->order->get_billing_postcode()
        );
        
        $completeness = $validation['completeness'];
        
        // Invert completeness to get risk (low completeness = high risk)
        return 100 - $completeness;
    }
    
    /**
     * Get risk level
     */
    public function get_risk_level($risk_score) {
        $high_threshold = get_option('mrx_ai_high_threshold', 70);
        $medium_threshold = get_option('mrx_ai_medium_threshold', 40);
        
        if ($risk_score >= $high_threshold) {
            return 'high';
        } elseif ($risk_score >= $medium_threshold) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Get risk breakdown
     */
    public function get_risk_breakdown($order_id) {
        $this->order_id = $order_id;
        $this->order = wc_get_order($order_id);
        
        $total_score = $this->calculate_risk_score($order_id);
        
        return array(
            'total_score' => $total_score,
            'phone_risk' => $this->calculate_phone_risk(),
            'ip_risk' => $this->calculate_ip_risk(),
            'device_risk' => $this->calculate_device_risk(),
            'behavior_risk' => $this->calculate_behavior_risk(),
            'address_risk' => $this->calculate_address_risk(),
            'risk_level' => $this->get_risk_level($total_score)
        );
    }
}


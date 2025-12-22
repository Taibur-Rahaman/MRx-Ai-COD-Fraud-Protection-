<?php
/**
 * Phone number tracking and reputation
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Phone_Tracker {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor
    }
    
    /**
     * Normalize phone number (Bangladesh format)
     */
    public static function normalize_phone($phone) {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove country code if present
        if (substr($phone, 0, 3) === '880') {
            $phone = '0' . substr($phone, 3);
        }
        
        // Ensure starts with 0
        if (substr($phone, 0, 1) !== '0') {
            $phone = '0' . $phone;
        }
        
        // Validate BD phone format (11 digits starting with 01)
        if (strlen($phone) === 11 && substr($phone, 0, 2) === '01') {
            return $phone;
        }
        
        return false;
    }
    
    /**
     * Get phone reputation
     */
    public static function get_reputation($phone) {
        global $wpdb;
        
        $phone = self::normalize_phone($phone);
        if (!$phone) {
            return false;
        }
        
        $table = $wpdb->prefix . 'mrx_ai_phones';
        
        $reputation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE phone = %s",
            $phone
        ));
        
        if (!$reputation) {
            // Create new record
            $wpdb->insert($table, array(
                'phone' => $phone,
                'order_count' => 0,
                'return_count' => 0,
                'risk_score' => 0,
                'last_order_date' => current_time('mysql')
            ));
            
            return array(
                'phone' => $phone,
                'order_count' => 0,
                'return_count' => 0,
                'risk_score' => 0,
                'return_rate' => 0
            );
        }
        
        $return_rate = $reputation->order_count > 0 
            ? ($reputation->return_count / $reputation->order_count) * 100 
            : 0;
        
        return array(
            'phone' => $reputation->phone,
            'order_count' => $reputation->order_count,
            'return_count' => $reputation->return_count,
            'risk_score' => $reputation->risk_score,
            'return_rate' => $return_rate,
            'last_order_date' => $reputation->last_order_date
        );
    }
    
    /**
     * Update phone reputation
     */
    public static function update_reputation($phone, $order_id, $is_returned = false) {
        global $wpdb;
        
        $phone = self::normalize_phone($phone);
        if (!$phone) {
            return false;
        }
        
        $table = $wpdb->prefix . 'mrx_ai_phones';
        
        // Get current reputation
        $reputation = self::get_reputation($phone);
        
        // Update counts
        $order_count = $reputation['order_count'] + 1;
        $return_count = $is_returned 
            ? $reputation['return_count'] + 1 
            : $reputation['return_count'];
        
        // Calculate risk score
        $return_rate = ($return_count / $order_count) * 100;
        $risk_score = min(100, $return_rate * 2); // Max 100
        
        // Update database
        $wpdb->replace($table, array(
            'phone' => $phone,
            'order_count' => $order_count,
            'return_count' => $return_count,
            'risk_score' => $risk_score,
            'last_order_date' => current_time('mysql')
        ));
        
        return true;
    }
}


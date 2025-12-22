<?php
/**
 * Device fingerprinting
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Device_Tracker {
    
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
     * Get device hash
     */
    public static function get_device_hash($device_data) {
        return hash('sha256', $device_data);
    }
    
    /**
     * Get device reputation
     */
    public static function get_reputation($device_hash) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mrx_ai_devices';
        
        $reputation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE device_hash = %s",
            $device_hash
        ));
        
        if (!$reputation) {
            return array(
                'device_hash' => $device_hash,
                'order_count' => 0,
                'risk_score' => 0
            );
        }
        
        return array(
            'device_hash' => $reputation->device_hash,
            'order_count' => $reputation->order_count,
            'risk_score' => $reputation->risk_score
        );
    }
    
    /**
     * Update device reputation
     */
    public static function update_reputation($device_hash, $device_data, $order_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mrx_ai_devices';
        
        $reputation = self::get_reputation($device_hash);
        
        $order_count = $reputation['order_count'] + 1;
        $risk_score = min(100, $order_count * 8);
        
        $wpdb->replace($table, array(
            'device_hash' => $device_hash,
            'browser_info' => $device_data,
            'order_count' => $order_count,
            'risk_score' => $risk_score,
            'last_order_date' => current_time('mysql')
        ));
        
        return true;
    }
}


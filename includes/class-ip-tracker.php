<?php
/**
 * IP address tracking and reputation
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_IP_Tracker {
    
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
     * Get real IP address (handle proxies)
     */
    public static function get_real_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle comma-separated IPs (proxies)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Get IP reputation
     */
    public static function get_reputation($ip) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mrx_ai_ips';
        
        $reputation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE ip_address = %s",
            $ip
        ));
        
        if (!$reputation) {
            // Create new record
            $wpdb->insert($table, array(
                'ip_address' => $ip,
                'order_count' => 0,
                'risk_score' => 0,
                'last_order_date' => current_time('mysql')
            ));
            
            return array(
                'ip_address' => $ip,
                'order_count' => 0,
                'risk_score' => 0,
                'blocked' => 0
            );
        }
        
        return array(
            'ip_address' => $reputation->ip_address,
            'order_count' => $reputation->order_count,
            'risk_score' => $reputation->risk_score,
            'blocked' => $reputation->blocked
        );
    }
    
    /**
     * Update IP reputation
     */
    public static function update_reputation($ip, $order_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mrx_ai_ips';
        
        $reputation = self::get_reputation($ip);
        
        $order_count = $reputation['order_count'] + 1;
        
        // Calculate risk: More orders from same IP = higher risk
        $risk_score = min(100, $order_count * 10);
        
        $wpdb->replace($table, array(
            'ip_address' => $ip,
            'order_count' => $order_count,
            'risk_score' => $risk_score,
            'last_order_date' => current_time('mysql')
        ));
        
        return true;
    }
}


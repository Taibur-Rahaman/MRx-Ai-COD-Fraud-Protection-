<?php
/**
 * Database handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Database {
    
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
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Phone reputation table
        $table_phones = $wpdb->prefix . 'mrx_ai_phones';
        $sql_phones = "CREATE TABLE $table_phones (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL,
            order_count INT DEFAULT 0,
            return_count INT DEFAULT 0,
            risk_score INT DEFAULT 0,
            last_order_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY phone (phone),
            KEY risk_score (risk_score)
        ) $charset_collate;";
        
        // IP reputation table
        $table_ips = $wpdb->prefix . 'mrx_ai_ips';
        $sql_ips = "CREATE TABLE $table_ips (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            order_count INT DEFAULT 0,
            risk_score INT DEFAULT 0,
            blocked TINYINT DEFAULT 0,
            last_order_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY ip_address (ip_address),
            KEY risk_score (risk_score)
        ) $charset_collate;";
        
        // Device fingerprint table
        $table_devices = $wpdb->prefix . 'mrx_ai_devices';
        $sql_devices = "CREATE TABLE $table_devices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            device_hash VARCHAR(64) NOT NULL,
            browser_info TEXT,
            order_count INT DEFAULT 0,
            risk_score INT DEFAULT 0,
            last_order_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY device_hash (device_hash),
            KEY risk_score (risk_score)
        ) $charset_collate;";
        
        // Order intelligence table
        $table_intelligence = $wpdb->prefix . 'mrx_ai_order_intelligence';
        $sql_intelligence = "CREATE TABLE $table_intelligence (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            phone VARCHAR(20),
            ip_address VARCHAR(45),
            device_hash VARCHAR(64),
            checkout_time INT DEFAULT 0,
            pages_viewed INT DEFAULT 0,
            session_duration INT DEFAULT 0,
            address_completeness INT DEFAULT 0,
            risk_factors TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY order_id (order_id),
            KEY phone (phone),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_phones);
        dbDelta($sql_ips);
        dbDelta($sql_devices);
        dbDelta($sql_intelligence);
    }
}


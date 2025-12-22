<?php
/**
 * Facebook Conversions API integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Facebook_CAPI {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('woocommerce_order_status_completed', array($this, 'send_capi_conversion'), 10, 1);
        add_filter('woocommerce_facebook_pixel_event_params', array($this, 'filter_pixel_events'), 10, 2);
    }
    
    /**
     * Check if order is verified
     */
    private function is_order_verified($order_id) {
        $order = wc_get_order($order_id);
        
        // Check 1: Delivered successfully
        $delivery_status = $order->get_meta('_mrx_ai_delivery_status');
        if ($delivery_status === 'delivered') {
            return true;
        }
        
        // Check 2: Vendor approved
        if ($order->get_meta('_mrx_ai_vendor_override') === 'yes') {
            $override_outcome = $order->get_meta('_mrx_ai_override_outcome');
            if ($override_outcome === 'success') {
                return true;
            }
        }
        
        // Check 3: High order value
        if ($order->get_total() > 5000) {
            return true;
        }
        
        // Check 4: Previous successful orders
        $phone = MRx_AI_Phone_Tracker::normalize_phone($order->get_billing_phone());
        if ($phone) {
            $reputation = MRx_AI_Phone_Tracker::get_reputation($phone);
            if ($reputation['order_count'] > 0) {
                $success_rate = (($reputation['order_count'] - $reputation['return_count']) / $reputation['order_count']) * 100;
                if ($success_rate >= 80) {
                    return true;
                }
            }
        }
        
        // Check 5: Low risk score
        $risk_score = $order->get_meta('_mrx_ai_risk_score');
        if ($risk_score && $risk_score < 30) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Send CAPI conversion
     */
    public function send_capi_conversion($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order->get_payment_method() !== 'cod') {
            return;
        }
        
        // Only if verified
        if (!get_option('mrx_ai_send_verified_only', '1') || !$this->is_order_verified($order_id)) {
            return;
        }
        
        // Don't send if already sent
        if ($order->get_meta('_mrx_ai_capi_sent') === 'yes') {
            return;
        }
        
        $pixel_id = get_option('mrx_ai_fb_pixel_id');
        $access_token = get_option('mrx_ai_fb_access_token');
        
        if (!$pixel_id || !$access_token) {
            return;
        }
        
        $event_data = $this->prepare_capi_event($order);
        $response = $this->send_to_facebook_capi($pixel_id, $access_token, $event_data);
        
        if ($response['success']) {
            $order->update_meta_data('_mrx_ai_capi_sent', 'yes');
            $order->update_meta_data('_mrx_ai_capi_sent_date', current_time('mysql'));
            $order->save();
        }
    }
    
    /**
     * Prepare CAPI event
     */
    private function prepare_capi_event($order) {
        $email = $order->get_billing_email();
        $phone = MRx_AI_Phone_Tracker::normalize_phone($order->get_billing_phone());
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $city = $order->get_billing_city();
        $country = $order->get_billing_country();
        
        $hashed_email = hash('sha256', strtolower(trim($email)));
        $hashed_phone = $phone ? hash('sha256', $phone) : null;
        
        $ip = MRx_AI_IP_Tracker::get_real_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $event = array(
            'event_name' => 'Purchase',
            'event_time' => time(),
            'event_id' => 'mrx_ai_' . $order->get_id(),
            'event_source_url' => $order->get_checkout_order_received_url(),
            'action_source' => 'website',
            'user_data' => array(
                'em' => array($hashed_email),
                'ph' => $hashed_phone ? array($hashed_phone) : null,
                'fn' => $first_name ? array(hash('sha256', strtolower($first_name))) : null,
                'ln' => $last_name ? array(hash('sha256', strtolower($last_name))) : null,
                'ct' => $city ? array(hash('sha256', strtolower($city))) : null,
                'country' => $country ? array(hash('sha256', strtolower($country))) : null,
                'client_ip_address' => $ip,
                'client_user_agent' => $user_agent
            ),
            'custom_data' => array(
                'currency' => get_woocommerce_currency(),
                'value' => (float) $order->get_total(),
                'content_ids' => array(),
                'content_type' => 'product',
                'contents' => array()
            )
        );
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $event['custom_data']['content_ids'][] = (string) $product_id;
            
            $event['custom_data']['contents'][] = array(
                'id' => (string) $product_id,
                'quantity' => $item->get_quantity(),
                'item_price' => (float) $item->get_total()
            );
        }
        
        return $event;
    }
    
    /**
     * Send to Facebook CAPI
     */
    private function send_to_facebook_capi($pixel_id, $access_token, $event_data) {
        $url = "https://graph.facebook.com/v18.0/{$pixel_id}/events";
        
        $body = array(
            'data' => array($event_data),
            'access_token' => $access_token
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['events_received']) && $response_body['events_received'] > 0) {
            return array(
                'success' => true,
                'response' => $response_body
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Facebook API error',
            'response' => $response_body
        );
    }
    
    /**
     * Filter pixel events
     */
    public function filter_pixel_events($params, $event_name) {
        if ($event_name !== 'Purchase') {
            return $params;
        }
        
        $order_id = isset($params['content_ids'][0]) ? $params['content_ids'][0] : null;
        
        if (!$order_id) {
            return $params;
        }
        
        if (!get_option('mrx_ai_send_verified_only', '1') || !$this->is_order_verified($order_id)) {
            return array(); // Don't send unverified events
        }
        
        return $params;
    }
}


<?php
/**
 * Courier feedback system
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Courier_Feedback {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_feedback_meta_box'));
        add_action('save_post', array($this, 'save_feedback'));
        add_action('woocommerce_order_status_changed', array($this, 'track_status_change'), 10, 4);
    }
    
    /**
     * Add courier feedback meta box
     */
    public function add_feedback_meta_box() {
        add_meta_box(
            'mrx-ai-courier-feedback',
            __('Courier Feedback', 'mrx-ai-cod-fraud'),
            array($this, 'render_feedback_meta_box'),
            'shop_order',
            'side',
            'default'
        );
    }
    
    /**
     * Render feedback meta box
     */
    public function render_feedback_meta_box($post) {
        $order = wc_get_order($post->ID);
        
        if ($order->get_payment_method() !== 'cod') {
            echo '<p>' . __('This order is not COD.', 'mrx-ai-cod-fraud') . '</p>';
            return;
        }
        
        $delivery_status = $order->get_meta('_mrx_ai_delivery_status');
        $return_reason = $order->get_meta('_mrx_ai_return_reason');
        
        wp_nonce_field('mrx_ai_courier_feedback', 'mrx_ai_courier_feedback_nonce');
        ?>
        <div class="mrx-ai-courier-feedback">
            <p>
                <label>
                    <input type="radio" name="mrx_ai_delivery_status" value="delivered" 
                           <?php checked($delivery_status, 'delivered'); ?>>
                    <?php _e('Delivered Successfully', 'mrx-ai-cod-fraud'); ?>
                </label>
            </p>
            <p>
                <label>
                    <input type="radio" name="mrx_ai_delivery_status" value="returned" 
                           <?php checked($delivery_status, 'returned'); ?>>
                    <?php _e('Returned', 'mrx-ai-cod-fraud'); ?>
                </label>
            </p>
            <p>
                <label><?php _e('Return Reason (if returned):', 'mrx-ai-cod-fraud'); ?></label><br>
                <select name="mrx_ai_return_reason" style="width: 100%;">
                    <option value=""><?php _e('Select reason...', 'mrx-ai-cod-fraud'); ?></option>
                    <option value="unreachable" <?php selected($return_reason, 'unreachable'); ?>><?php _e('Phone Unreachable', 'mrx-ai-cod-fraud'); ?></option>
                    <option value="wrong_number" <?php selected($return_reason, 'wrong_number'); ?>><?php _e('Wrong Phone Number', 'mrx-ai-cod-fraud'); ?></option>
                    <option value="address_issue" <?php selected($return_reason, 'address_issue'); ?>><?php _e('Address Issue', 'mrx-ai-cod-fraud'); ?></option>
                    <option value="incomplete_address" <?php selected($return_reason, 'incomplete_address'); ?>><?php _e('Incomplete Address', 'mrx-ai-cod-fraud'); ?></option>
                    <option value="refused" <?php selected($return_reason, 'refused'); ?>><?php _e('Customer Refused', 'mrx-ai-cod-fraud'); ?></option>
                    <option value="other" <?php selected($return_reason, 'other'); ?>><?php _e('Other', 'mrx-ai-cod-fraud'); ?></option>
                </select>
            </p>
            <p class="description">
                <?php _e('Mark delivery status to update risk scores and phone reputation.', 'mrx-ai-cod-fraud'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save courier feedback
     */
    public function save_feedback($post_id) {
        if (get_post_type($post_id) !== 'shop_order') {
            return;
        }
        
        if (!isset($_POST['mrx_ai_courier_feedback_nonce']) || 
            !wp_verify_nonce($_POST['mrx_ai_courier_feedback_nonce'], 'mrx_ai_courier_feedback')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $order = wc_get_order($post_id);
        $old_status = $order->get_meta('_mrx_ai_delivery_status');
        
        $delivery_status = isset($_POST['mrx_ai_delivery_status']) ? sanitize_text_field($_POST['mrx_ai_delivery_status']) : '';
        $return_reason = isset($_POST['mrx_ai_return_reason']) ? sanitize_text_field($_POST['mrx_ai_return_reason']) : '';
        
        $order->update_meta_data('_mrx_ai_delivery_status', $delivery_status);
        $order->update_meta_data('_mrx_ai_return_reason', $return_reason);
        $order->save();
        
        if ($old_status !== $delivery_status) {
            $this->update_reputation_from_feedback($order, $delivery_status, $return_reason);
            
            $note = __('Courier feedback updated: ', 'mrx-ai-cod-fraud') . ucfirst($delivery_status);
            if ($return_reason) {
                $note .= ' - ' . __('Reason: ', 'mrx-ai-cod-fraud') . ucfirst(str_replace('_', ' ', $return_reason));
            }
            $order->add_order_note($note);
        }
    }
    
    /**
     * Update reputation from feedback
     */
    private function update_reputation_from_feedback($order, $delivery_status, $return_reason) {
        $phone = MRx_AI_Phone_Tracker::normalize_phone($order->get_billing_phone());
        
        if (!$phone) {
            return;
        }
        
        $is_returned = ($delivery_status === 'returned');
        MRx_AI_Phone_Tracker::update_reputation($phone, $order->get_id(), $is_returned);
        
        if ($is_returned) {
            $risk_adjustment = $this->get_risk_adjustment_from_reason($return_reason);
            
            global $wpdb;
            $table = $wpdb->prefix . 'mrx_ai_phones';
            
            $reputation = MRx_AI_Phone_Tracker::get_reputation($phone);
            $new_risk_score = min(100, $reputation['risk_score'] + $risk_adjustment);
            
            $wpdb->update(
                $table,
                array('risk_score' => $new_risk_score),
                array('phone' => $phone)
            );
        } else {
            // Delivered successfully - reduce risk score
            global $wpdb;
            $table = $wpdb->prefix . 'mrx_ai_phones';
            
            $reputation = MRx_AI_Phone_Tracker::get_reputation($phone);
            $new_risk_score = max(0, $reputation['risk_score'] - 10);
            
            $wpdb->update(
                $table,
                array('risk_score' => $new_risk_score),
                array('phone' => $phone)
            );
        }
    }
    
    /**
     * Get risk adjustment from reason
     */
    private function get_risk_adjustment_from_reason($reason) {
        $adjustments = array(
            'unreachable' => 20,
            'wrong_number' => 25,
            'address_issue' => 15,
            'incomplete_address' => 15,
            'refused' => 10,
            'other' => 5
        );
        
        return isset($adjustments[$reason]) ? $adjustments[$reason] : 10;
    }
    
    /**
     * Track status change
     */
    public function track_status_change($order_id, $old_status, $new_status, $order) {
        if ($order->get_payment_method() !== 'cod') {
            return;
        }
        
        // If order cancelled (returned), update risk score
        if ($new_status === 'cancelled') {
            $phone = MRx_AI_Phone_Tracker::normalize_phone($order->get_billing_phone());
            if ($phone) {
                MRx_AI_Phone_Tracker::update_reputation($phone, $order_id, true);
            }
        }
    }
}


<?php
/**
 * Dokan marketplace integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Dokan_Integration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('dokan_order_list_table_after_order_status', array($this, 'add_risk_badge'), 10, 2);
        add_action('dokan_new_order_added', array($this, 'handle_dokan_order'), 10, 2);
        add_action('dokan_dashboard_content_before', array($this, 'dashboard_widget'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_vendor_scripts'));
        add_action('wp_ajax_mrx_ai_get_risk_details', array($this, 'get_risk_details_ajax'));
        add_action('wp_ajax_mrx_ai_vendor_override', array($this, 'vendor_override_ajax'));
    }
    
    /**
     * Add risk badge to vendor order list
     */
    public function add_risk_badge($order, $vendor_id) {
        if ($order->get_payment_method() !== 'cod') {
            return;
        }
        
        $risk_level = $order->get_meta('_mrx_ai_risk_level');
        $risk_score = $order->get_meta('_mrx_ai_risk_score');
        $vendor_override = $order->get_meta('_mrx_ai_vendor_override');
        
        if (!$risk_level) {
            return;
        }
        
        if ($vendor_override === 'yes') {
            echo '<span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px;">';
            echo '✓ ' . __('VENDOR APPROVED', 'mrx-ai-cod-fraud');
            echo '</span>';
            return;
        }
        
        $colors = array(
            'high' => '#dc3545',
            'medium' => '#ffc107',
            'low' => '#28a745'
        );
        
        $color = isset($colors[$risk_level]) ? $colors[$risk_level] : '#6c757d';
        
        echo '<span style="background: ' . esc_attr($color) . '; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px; cursor: pointer;" ';
        echo 'onclick="mrxAiShowRiskDetails(' . esc_js($order->get_id()) . ')" ';
        echo 'title="' . esc_attr__('Click for details', 'mrx-ai-cod-fraud') . '">';
        echo strtoupper($risk_level) . ' ' . __('RISK', 'mrx-ai-cod-fraud') . ' (' . $risk_score . ')';
        echo '</span>';
        
        if ($risk_level === 'high') {
            echo '<button type="button" class="button button-small mrx-ai-override-btn" ';
            echo 'data-order-id="' . esc_attr($order->get_id()) . '" ';
            echo 'style="margin-left: 5px; background: #17a2b8; color: white; border: none;">';
            echo __('Approve Anyway', 'mrx-ai-cod-fraud');
            echo '</button>';
        }
    }
    
    /**
     * Handle Dokan order split
     */
    public function handle_dokan_order($order_id, $vendor_id) {
        $order = wc_get_order($order_id);
        $parent_order_id = $order->get_meta('_dokan_parent_order_id');
        
        if ($parent_order_id) {
            $parent_order = wc_get_order($parent_order_id);
            
            $risk_score = $parent_order->get_meta('_mrx_ai_risk_score');
            $risk_level = $parent_order->get_meta('_mrx_ai_risk_level');
            
            $order->update_meta_data('_mrx_ai_risk_score', $risk_score);
            $order->update_meta_data('_mrx_ai_risk_level', $risk_level);
            $order->save();
        }
    }
    
    /**
     * Dashboard widget
     */
    public function dashboard_widget() {
        if (!function_exists('dokan_is_user_seller') || !dokan_is_user_seller(get_current_user_id())) {
            return;
        }
        
        $vendor_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_dokan_vendor_id',
                    'value' => $vendor_id
                ),
                array(
                    'key' => '_payment_method',
                    'value' => 'cod'
                )
            )
        );
        
        $orders = get_posts($args);
        
        $total_cod = count($orders);
        $high_risk = 0;
        $overrides = 0;
        $completed = 0;
        
        foreach ($orders as $order_post) {
            $order = wc_get_order($order_post->ID);
            $risk_level = $order->get_meta('_mrx_ai_risk_level');
            
            if ($risk_level === 'high') {
                $high_risk++;
            }
            
            if ($order->get_meta('_mrx_ai_vendor_override') === 'yes') {
                $overrides++;
            }
            
            if ($order->get_status() === 'completed') {
                $completed++;
            }
        }
        
        $success_rate = $total_cod > 0 ? round(($completed / $total_cod) * 100, 1) : 0;
        ?>
        <div class="dokan-dashboard-widget mrx-ai-widget" style="background: white; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <h3><?php _e('COD Fraud Protection Statistics', 'mrx-ai-cod-fraud'); ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="text-align: center; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                    <div style="font-size: 32px; font-weight: bold; color: #0073aa;"><?php echo $total_cod; ?></div>
                    <div style="color: #666; margin-top: 5px;"><?php _e('Total COD Orders', 'mrx-ai-cod-fraud'); ?></div>
                </div>
                <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 5px;">
                    <div style="font-size: 32px; font-weight: bold; color: #dc3545;"><?php echo $high_risk; ?></div>
                    <div style="color: #666; margin-top: 5px;"><?php _e('High Risk Orders', 'mrx-ai-cod-fraud'); ?></div>
                </div>
                <div style="text-align: center; padding: 15px; background: #d1ecf1; border-radius: 5px;">
                    <div style="font-size: 32px; font-weight: bold; color: #17a2b8;"><?php echo $overrides; ?></div>
                    <div style="color: #666; margin-top: 5px;"><?php _e('Vendor Overrides', 'mrx-ai-cod-fraud'); ?></div>
                </div>
                <div style="text-align: center; padding: 15px; background: #d4edda; border-radius: 5px;">
                    <div style="font-size: 32px; font-weight: bold; color: #28a745;"><?php echo $success_rate; ?>%</div>
                    <div style="color: #666; margin-top: 5px;"><?php _e('Success Rate', 'mrx-ai-cod-fraud'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue vendor scripts
     */
    public function enqueue_vendor_scripts() {
        if (function_exists('dokan_is_user_seller') && dokan_is_user_seller(get_current_user_id())) {
            wp_enqueue_script('mrx-ai-vendor', MRX_AI_PLUGIN_URL . 'assets/js/vendor.js', array('jquery'), MRX_AI_VERSION, true);
            wp_localize_script('mrx-ai-vendor', 'mrxAiAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mrx_ai_nonce')
            ));
        }
    }
    
    /**
     * Get risk details AJAX
     */
    public function get_risk_details_ajax() {
        check_ajax_referer('mrx_ai_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (function_exists('dokan_get_vendor_by_order')) {
            $vendor_id = dokan_get_vendor_by_order($order_id);
            if ($vendor_id !== get_current_user_id()) {
                wp_send_json_error(array('message' => __('Unauthorized', 'mrx-ai-cod-fraud')));
            }
        }
        
        $risk_score = $order->get_meta('_mrx_ai_risk_score');
        $risk_level = $order->get_meta('_mrx_ai_risk_level');
        $risk_breakdown = json_decode($order->get_meta('_mrx_ai_risk_breakdown'), true);
        
        global $wpdb;
        $table = $wpdb->prefix . 'mrx_ai_order_intelligence';
        $intelligence = $wpdb->get_row($wpdb->prepare(
            "SELECT risk_factors FROM $table WHERE order_id = %d",
            $order_id
        ));
        
        $risk_factors = array();
        if ($intelligence && $intelligence->risk_factors) {
            $risk_factors = json_decode($intelligence->risk_factors, true);
        }
        
        $colors = array(
            'high' => '#dc3545',
            'medium' => '#ffc107',
            'low' => '#28a745'
        );
        
        wp_send_json_success(array(
            'risk_score' => $risk_score,
            'risk_level' => $risk_level,
            'color' => isset($colors[$risk_level]) ? $colors[$risk_level] : '#6c757d',
            'phone_risk' => isset($risk_breakdown['phone_risk']) ? $risk_breakdown['phone_risk'] : 0,
            'ip_risk' => isset($risk_breakdown['ip_risk']) ? $risk_breakdown['ip_risk'] : 0,
            'device_risk' => isset($risk_breakdown['device_risk']) ? $risk_breakdown['device_risk'] : 0,
            'behavior_risk' => isset($risk_breakdown['behavior_risk']) ? $risk_breakdown['behavior_risk'] : 0,
            'address_risk' => isset($risk_breakdown['address_risk']) ? $risk_breakdown['address_risk'] : 0,
            'risk_factors' => $risk_factors
        ));
    }
    
    /**
     * Vendor override AJAX
     */
    public function vendor_override_ajax() {
        check_ajax_referer('mrx_ai_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (function_exists('dokan_get_vendor_by_order')) {
            $vendor_id = dokan_get_vendor_by_order($order_id);
            if ($vendor_id !== get_current_user_id()) {
                wp_send_json_error(array('message' => __('Unauthorized', 'mrx-ai-cod-fraud')));
            }
        }
        
        $order->update_meta_data('_mrx_ai_vendor_override', 'yes');
        $order->update_meta_data('_mrx_ai_vendor_override_by', get_current_user_id());
        $order->update_meta_data('_mrx_ai_vendor_override_date', current_time('mysql'));
        $order->save();
        
        $order->add_order_note(__('Vendor approved high-risk COD order. Risk score: ', 'mrx-ai-cod-fraud') . $order->get_meta('_mrx_ai_risk_score'));
        
        wp_send_json_success(array('message' => __('Order approved successfully', 'mrx-ai-cod-fraud')));
    }
}


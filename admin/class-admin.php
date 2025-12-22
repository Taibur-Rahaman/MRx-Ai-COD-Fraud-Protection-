<?php
/**
 * Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRx_AI_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'show_order_risk_info'));
        add_filter('manage_shop_order_posts_custom_column', array($this, 'add_risk_badge_column'), 10, 2);
    }
    
    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('COD Fraud Protection', 'mrx-ai-cod-fraud'),
            __('COD Fraud Protection', 'mrx-ai-cod-fraud'),
            'manage_woocommerce',
            'mrx-ai-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['mrx_ai_save_settings'])) {
            check_admin_referer('mrx_ai_settings');
            
            update_option('mrx_ai_high_threshold', intval($_POST['high_threshold']));
            update_option('mrx_ai_medium_threshold', intval($_POST['medium_threshold']));
            update_option('mrx_ai_high_risk_action', sanitize_text_field($_POST['high_risk_action']));
            update_option('mrx_ai_fb_pixel_id', sanitize_text_field($_POST['fb_pixel_id']));
            update_option('mrx_ai_fb_access_token', sanitize_text_field($_POST['fb_access_token']));
            update_option('mrx_ai_send_verified_only', isset($_POST['send_verified_only']) ? '1' : '0');
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'mrx-ai-cod-fraud') . '</p></div>';
        }
        
        $high_threshold = get_option('mrx_ai_high_threshold', 70);
        $medium_threshold = get_option('mrx_ai_medium_threshold', 40);
        $high_risk_action = get_option('mrx_ai_high_risk_action', 'flag');
        $fb_pixel_id = get_option('mrx_ai_fb_pixel_id', '');
        $fb_access_token = get_option('mrx_ai_fb_access_token', '');
        $send_verified_only = get_option('mrx_ai_send_verified_only', '1');
        ?>
        <div class="wrap">
            <h1><?php _e('COD Fraud Protection Settings', 'mrx-ai-cod-fraud'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('mrx_ai_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('High Risk Threshold', 'mrx-ai-cod-fraud'); ?></th>
                        <td>
                            <input type="number" name="high_threshold" value="<?php echo esc_attr($high_threshold); ?>" min="0" max="100">
                            <p class="description"><?php _e('Orders with risk score >= this will be marked as HIGH RISK', 'mrx-ai-cod-fraud'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Medium Risk Threshold', 'mrx-ai-cod-fraud'); ?></th>
                        <td>
                            <input type="number" name="medium_threshold" value="<?php echo esc_attr($medium_threshold); ?>" min="0" max="100">
                            <p class="description"><?php _e('Orders with risk score >= this will be marked as MEDIUM RISK', 'mrx-ai-cod-fraud'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('High Risk Action', 'mrx-ai-cod-fraud'); ?></th>
                        <td>
                            <select name="high_risk_action">
                                <option value="flag" <?php selected($high_risk_action, 'flag'); ?>><?php _e('Flag Only', 'mrx-ai-cod-fraud'); ?></option>
                                <option value="block" <?php selected($high_risk_action, 'block'); ?>><?php _e('Block COD', 'mrx-ai-cod-fraud'); ?></option>
                                <option value="manual" <?php selected($high_risk_action, 'manual'); ?>><?php _e('Manual Review', 'mrx-ai-cod-fraud'); ?></option>
                            </select>
                            <p class="description"><?php _e('What to do when order is HIGH RISK', 'mrx-ai-cod-fraud'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Facebook Conversions API (CAPI) Settings', 'mrx-ai-cod-fraud'); ?></h2>
                <p class="description"><?php _e('Send only verified conversions to Facebook for better ad performance.', 'mrx-ai-cod-fraud'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Facebook Pixel ID', 'mrx-ai-cod-fraud'); ?></th>
                        <td>
                            <input type="text" name="fb_pixel_id" value="<?php echo esc_attr($fb_pixel_id); ?>" placeholder="1234567890123456">
                            <p class="description"><?php _e('Your Facebook Pixel ID', 'mrx-ai-cod-fraud'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Access Token', 'mrx-ai-cod-fraud'); ?></th>
                        <td>
                            <input type="password" name="fb_access_token" value="<?php echo esc_attr($fb_access_token); ?>" placeholder="Your access token">
                            <p class="description">
                                <a href="https://developers.facebook.com/docs/marketing-api/conversions-api/get-started" target="_blank">
                                    <?php _e('How to get access token', 'mrx-ai-cod-fraud'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Send Verified Only', 'mrx-ai-cod-fraud'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="send_verified_only" value="1" <?php checked($send_verified_only, '1'); ?>>
                                <?php _e('Only send verified conversions to Facebook', 'mrx-ai-cod-fraud'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Unverified orders (fake/returned) will not be sent to Facebook.', 'mrx-ai-cod-fraud'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Settings', 'mrx-ai-cod-fraud'), 'primary', 'mrx_ai_save_settings'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Show order risk info
     */
    public function show_order_risk_info($order) {
        if ($order->get_payment_method() !== 'cod') {
            return;
        }
        
        $risk_score = $order->get_meta('_mrx_ai_risk_score');
        $risk_level = $order->get_meta('_mrx_ai_risk_level');
        $risk_breakdown = json_decode($order->get_meta('_mrx_ai_risk_breakdown'), true);
        
        if (!$risk_score) {
            return;
        }
        
        $colors = array(
            'high' => '#dc3545',
            'medium' => '#ffc107',
            'low' => '#28a745'
        );
        
        $color = isset($colors[$risk_level]) ? $colors[$risk_level] : '#6c757d';
        ?>
        <div class="mrx-ai-risk-info" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid <?php echo esc_attr($color); ?>;">
            <h3 style="margin-top: 0;"><?php _e('COD Fraud Risk Assessment', 'mrx-ai-cod-fraud'); ?></h3>
            <p>
                <strong><?php _e('Risk Score:', 'mrx-ai-cod-fraud'); ?></strong> 
                <span style="font-size: 24px; font-weight: bold; color: <?php echo esc_attr($color); ?>;">
                    <?php echo esc_html($risk_score); ?>/100
                </span>
                <span style="background: <?php echo esc_attr($color); ?>; color: white; padding: 3px 8px; border-radius: 3px; margin-left: 10px;">
                    <?php echo strtoupper($risk_level); ?> <?php _e('RISK', 'mrx-ai-cod-fraud'); ?>
                </span>
            </p>
            
            <?php if ($risk_breakdown) : ?>
            <h4><?php _e('Risk Breakdown:', 'mrx-ai-cod-fraud'); ?></h4>
            <ul>
                <li><?php _e('Phone Risk:', 'mrx-ai-cod-fraud'); ?> <?php echo isset($risk_breakdown['phone_risk']) ? esc_html($risk_breakdown['phone_risk']) : 0; ?></li>
                <li><?php _e('IP Risk:', 'mrx-ai-cod-fraud'); ?> <?php echo isset($risk_breakdown['ip_risk']) ? esc_html($risk_breakdown['ip_risk']) : 0; ?></li>
                <li><?php _e('Device Risk:', 'mrx-ai-cod-fraud'); ?> <?php echo isset($risk_breakdown['device_risk']) ? esc_html($risk_breakdown['device_risk']) : 0; ?></li>
                <li><?php _e('Behavior Risk:', 'mrx-ai-cod-fraud'); ?> <?php echo isset($risk_breakdown['behavior_risk']) ? esc_html($risk_breakdown['behavior_risk']) : 0; ?></li>
                <li><?php _e('Address Risk:', 'mrx-ai-cod-fraud'); ?> <?php echo isset($risk_breakdown['address_risk']) ? esc_html($risk_breakdown['address_risk']) : 0; ?></li>
            </ul>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Add risk badge to order list
     */
    public function add_risk_badge_column($column, $post_id) {
        if ($column === 'order_status') {
            $order = wc_get_order($post_id);
            
            if ($order->get_payment_method() === 'cod') {
                $risk_level = $order->get_meta('_mrx_ai_risk_level');
                $risk_score = $order->get_meta('_mrx_ai_risk_score');
                
                if ($risk_level) {
                    $colors = array(
                        'high' => '#dc3545',
                        'medium' => '#ffc107',
                        'low' => '#28a745'
                    );
                    
                    $color = isset($colors[$risk_level]) ? $colors[$risk_level] : '#6c757d';
                    
                    echo '<br><span style="background: ' . esc_attr($color) . '; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold;">';
                    echo strtoupper($risk_level) . ' ' . __('RISK', 'mrx-ai-cod-fraud') . ' (' . esc_html($risk_score) . ')';
                    echo '</span>';
                }
            }
        }
    }
}


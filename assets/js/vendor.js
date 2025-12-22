/**
 * Vendor dashboard JavaScript for MRx AI COD Fraud Protection
 */

(function($) {
    'use strict';
    
    /**
     * Show risk details modal
     */
    window.mrxAiShowRiskDetails = function(orderId) {
        $.ajax({
            url: mrxAiAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mrx_ai_get_risk_details',
                order_id: orderId,
                nonce: mrxAiAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showModal(response.data);
                }
            }
        });
    };
    
    /**
     * Show modal with risk details
     */
    function showModal(data) {
        const modal = `
            <div id="mrx-ai-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; padding: 20px; border-radius: 5px; max-width: 500px; width: 90%;">
                    <h2>Risk Assessment Details</h2>
                    <p><strong>Risk Score:</strong> ${data.risk_score}/100</p>
                    <p><strong>Risk Level:</strong> <span style="color: ${data.color};">${data.risk_level.toUpperCase()}</span></p>
                    <h3>Risk Breakdown:</h3>
                    <ul>
                        <li>Phone Risk: ${data.phone_risk}</li>
                        <li>IP Risk: ${data.ip_risk}</li>
                        <li>Device Risk: ${data.device_risk}</li>
                        <li>Behavior Risk: ${data.behavior_risk}</li>
                        <li>Address Risk: ${data.address_risk}</li>
                    </ul>
                    <h3>Why This Order is Risky:</h3>
                    <ul>
                        ${data.risk_factors.map(factor => `<li>${factor}</li>`).join('')}
                    </ul>
                    <button onclick="$('#mrx-ai-modal').remove()" class="button">Close</button>
                </div>
            </div>
        `;
        
        $('body').append(modal);
    }
    
    /**
     * Handle override button
     */
    $(document).on('click', '.mrx-ai-override-btn', function() {
        const orderId = $(this).data('order-id');
        
        if (!confirm('Are you sure you want to approve this high-risk order? You will be responsible for any losses.')) {
            return;
        }
        
        $.ajax({
            url: mrxAiAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mrx_ai_vendor_override',
                order_id: orderId,
                nonce: mrxAiAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
})(jQuery);


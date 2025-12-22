/**
 * Frontend JavaScript for MRx AI COD Fraud Protection
 */

(function($) {
    'use strict';
    
    // Track checkout behavior
    let mrxAiBehavior = {
        sessionStart: Date.now(),
        pagesViewed: 1,
        checkoutStart: null,
        checkoutTime: 0
    };
    
    // Track page views
    $(document).ready(function() {
        // Increment pages viewed
        if (sessionStorage.getItem('mrx_ai_pages_viewed')) {
            mrxAiBehavior.pagesViewed = parseInt(sessionStorage.getItem('mrx_ai_pages_viewed')) + 1;
        }
        sessionStorage.setItem('mrx_ai_pages_viewed', mrxAiBehavior.pagesViewed);
        
        // Track checkout start
        if ($('body').hasClass('woocommerce-checkout')) {
            mrxAiBehavior.checkoutStart = Date.now();
            sessionStorage.setItem('mrx_ai_checkout_start', mrxAiBehavior.checkoutStart);
            
            // Collect device fingerprint
            collectDeviceFingerprint();
        }
        
        // Calculate checkout time on form submit
        $('#place_order').on('click', function() {
            const checkoutStart = parseInt(sessionStorage.getItem('mrx_ai_checkout_start') || mrxAiBehavior.checkoutStart);
            const checkoutTime = Math.floor((Date.now() - checkoutStart) / 1000); // seconds
            
            // Add hidden fields
            $('<input>').attr({
                type: 'hidden',
                name: 'mrx_ai_checkout_time',
                value: checkoutTime
            }).appendTo('#checkout');
            
            $('<input>').attr({
                type: 'hidden',
                name: 'mrx_ai_pages_viewed',
                value: mrxAiBehavior.pagesViewed
            }).appendTo('#checkout');
            
            const sessionDuration = Math.floor((Date.now() - mrxAiBehavior.sessionStart) / 1000);
            $('<input>').attr({
                type: 'hidden',
                name: 'mrx_ai_session_duration',
                value: sessionDuration
            }).appendTo('#checkout');
        });
    });
    
    /**
     * Collect device fingerprint
     */
    function collectDeviceFingerprint() {
        const fingerprint = {
            userAgent: navigator.userAgent,
            screenWidth: screen.width,
            screenHeight: screen.height,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            platform: navigator.platform,
            cookieEnabled: navigator.cookieEnabled,
            doNotTrack: navigator.doNotTrack
        };
        
        // Create hash
        const fingerprintString = JSON.stringify(fingerprint);
        const hash = simpleHash(fingerprintString);
        
        // Store in sessionStorage
        sessionStorage.setItem('mrx_ai_device_hash', hash);
        sessionStorage.setItem('mrx_ai_device_data', fingerprintString);
        
        // Add hidden fields to checkout form
        $('<input>').attr({
            type: 'hidden',
            name: 'mrx_ai_device_hash',
            value: hash
        }).appendTo('#checkout');
        
        $('<input>').attr({
            type: 'hidden',
            name: 'mrx_ai_device_data',
            value: fingerprintString
        }).appendTo('#checkout');
        
        return hash;
    }
    
    /**
     * Simple hash function
     */
    function simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return Math.abs(hash).toString(16);
    }
    
})(jQuery);


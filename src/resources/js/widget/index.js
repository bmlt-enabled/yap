/**
 * Yap WebRTC Dial Widget
 *
 * This is the entry point for the embeddable dial widget.
 * It can be loaded on external websites to allow browser-based calling.
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import DialWidget from './DialWidget';

// Export for programmatic use
export { DialWidget };

// Global initialization function
window.YapDialWidget = {
    /**
     * Initialize the dial widget in a container element
     *
     * @param {HTMLElement|string} container - DOM element or selector
     * @param {Object} options - Configuration options
     * @param {string} options.apiUrl - The base URL of your Yap installation
     * @param {string} [options.serviceBodyId] - Optional service body ID to route calls
     * @param {string} [options.title] - Custom title for the widget
     * @param {boolean} [options.showLocationInput=true] - Show location input field
     * @param {boolean} [options.showSearchType=false] - Show search type selector
     * @param {string} [options.defaultSearchType='helpline'] - Default search type
     * @param {Object} [options.styles] - Custom styles to override defaults
     * @param {Function} [options.onCallStart] - Callback when call starts
     * @param {Function} [options.onCallEnd] - Callback when call ends
     * @param {Function} [options.onError] - Callback on error
     */
    init: function(container, options = {}) {
        // Get container element
        let containerEl = container;
        if (typeof container === 'string') {
            containerEl = document.querySelector(container);
        }

        if (!containerEl) {
            console.error('YapDialWidget: Container element not found');
            return null;
        }

        // Validate required options
        if (!options.apiUrl) {
            console.error('YapDialWidget: apiUrl is required');
            return null;
        }

        // Create React root and render
        const root = createRoot(containerEl);
        root.render(
            <DialWidget
                apiUrl={options.apiUrl}
                serviceBodyId={options.serviceBodyId}
                title={options.title}
                showLocationInput={options.showLocationInput !== false}
                showSearchType={options.showSearchType || false}
                defaultSearchType={options.defaultSearchType || 'helpline'}
                customStyles={options.styles || {}}
                onCallStart={options.onCallStart || (() => {})}
                onCallEnd={options.onCallEnd || (() => {})}
                onError={options.onError || (() => {})}
            />
        );

        return {
            destroy: () => {
                root.unmount();
            }
        };
    },

    /**
     * Check if WebRTC is supported in this browser
     */
    isSupported: function() {
        return !!(
            navigator.mediaDevices &&
            navigator.mediaDevices.getUserMedia &&
            window.RTCPeerConnection
        );
    }
};

// Auto-initialize widgets with data attributes
document.addEventListener('DOMContentLoaded', function() {
    const widgets = document.querySelectorAll('[data-yap-widget]');

    widgets.forEach(function(el) {
        const apiUrl = el.dataset.yapApiUrl || el.dataset.apiUrl;
        if (!apiUrl) {
            console.error('YapDialWidget: data-yap-api-url is required');
            return;
        }

        window.YapDialWidget.init(el, {
            apiUrl: apiUrl,
            serviceBodyId: el.dataset.yapServiceBody || el.dataset.serviceBodyId,
            title: el.dataset.yapTitle || el.dataset.title,
            showLocationInput: el.dataset.yapShowLocation !== 'false',
            showSearchType: el.dataset.yapShowSearchType === 'true',
            defaultSearchType: el.dataset.yapSearchType || 'helpline',
        });
    });
});

// Log version
console.log('YapDialWidget loaded');

/**
 * Yap Communication Widget
 *
 * This is the entry point for the embeddable dial and chat widgets.
 * It can be loaded on external websites to allow browser-based calling and chat.
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import DialWidget from './DialWidget';
import ChatWidget from './ChatWidget';
import CommunicationWidget from './CommunicationWidget';

// Export for programmatic use
export { DialWidget, ChatWidget, CommunicationWidget };

// Helper to get container element
function getContainerElement(container) {
    if (typeof container === 'string') {
        return document.querySelector(container);
    }
    return container;
}

// Global initialization functions
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
        const containerEl = getContainerElement(container);

        if (!containerEl) {
            console.error('YapDialWidget: Container element not found');
            return null;
        }

        if (!options.apiUrl) {
            console.error('YapDialWidget: apiUrl is required');
            return null;
        }

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

        return { destroy: () => root.unmount() };
    },

    isSupported: function() {
        return !!(
            navigator.mediaDevices &&
            navigator.mediaDevices.getUserMedia &&
            window.RTCPeerConnection
        );
    }
};

// Chat widget initialization
window.YapChatWidget = {
    /**
     * Initialize the chat widget in a container element
     *
     * @param {HTMLElement|string} container - DOM element or selector
     * @param {Object} options - Configuration options
     * @param {string} options.apiUrl - The base URL of your Yap installation
     * @param {string} [options.title] - Custom title for the widget
     * @param {Object} [options.styles] - Custom styles to override defaults
     * @param {Function} [options.onSessionStart] - Callback when session starts
     * @param {Function} [options.onSessionEnd] - Callback when session ends
     * @param {Function} [options.onError] - Callback on error
     */
    init: function(container, options = {}) {
        const containerEl = getContainerElement(container);

        if (!containerEl) {
            console.error('YapChatWidget: Container element not found');
            return null;
        }

        if (!options.apiUrl) {
            console.error('YapChatWidget: apiUrl is required');
            return null;
        }

        const root = createRoot(containerEl);
        root.render(
            <ChatWidget
                apiUrl={options.apiUrl}
                title={options.title}
                customStyles={options.styles || {}}
                onSessionStart={options.onSessionStart || (() => {})}
                onSessionEnd={options.onSessionEnd || (() => {})}
                onError={options.onError || (() => {})}
            />
        );

        return { destroy: () => root.unmount() };
    }
};

// Combined communication widget initialization
window.YapWidget = {
    /**
     * Initialize the combined communication widget with Call and Chat tabs
     *
     * @param {HTMLElement|string} container - DOM element or selector
     * @param {Object} options - Configuration options
     * @param {string} options.apiUrl - The base URL of your Yap installation
     * @param {string} [options.serviceBodyId] - Optional service body ID
     * @param {string} [options.title] - Custom title for the widget
     * @param {string} [options.defaultTab='call'] - Default tab ('call' or 'chat')
     * @param {boolean} [options.showCallTab=true] - Show call tab
     * @param {boolean} [options.showChatTab=true] - Show chat tab
     * @param {boolean} [options.showLocationInput=true] - Show location input
     * @param {boolean} [options.showSearchType=false] - Show search type selector
     * @param {Object} [options.styles] - Custom styles
     * @param {Function} [options.onCallStart] - Callback when call starts
     * @param {Function} [options.onCallEnd] - Callback when call ends
     * @param {Function} [options.onChatStart] - Callback when chat session starts
     * @param {Function} [options.onChatEnd] - Callback when chat session ends
     * @param {Function} [options.onError] - Callback on error
     */
    init: function(container, options = {}) {
        const containerEl = getContainerElement(container);

        if (!containerEl) {
            console.error('YapWidget: Container element not found');
            return null;
        }

        if (!options.apiUrl) {
            console.error('YapWidget: apiUrl is required');
            return null;
        }

        const root = createRoot(containerEl);
        root.render(
            <CommunicationWidget
                apiUrl={options.apiUrl}
                serviceBodyId={options.serviceBodyId}
                title={options.title}
                defaultTab={options.defaultTab || 'call'}
                showCallTab={options.showCallTab !== false}
                showChatTab={options.showChatTab !== false}
                showLocationInput={options.showLocationInput !== false}
                showSearchType={options.showSearchType || false}
                customStyles={options.styles || {}}
                onCallStart={options.onCallStart || (() => {})}
                onCallEnd={options.onCallEnd || (() => {})}
                onChatStart={options.onChatStart || (() => {})}
                onChatEnd={options.onChatEnd || (() => {})}
                onError={options.onError || (() => {})}
            />
        );

        return { destroy: () => root.unmount() };
    },

    TABS: CommunicationWidget.TABS,

    isWebRTCSupported: function() {
        return !!(
            navigator.mediaDevices &&
            navigator.mediaDevices.getUserMedia &&
            window.RTCPeerConnection
        );
    }
};

// Auto-initialize widgets with data attributes
document.addEventListener('DOMContentLoaded', function() {
    // Combined widgets (data-yap-widget without type, or data-yap-widget="combined")
    document.querySelectorAll('[data-yap-widget]').forEach(function(el) {
        const apiUrl = el.dataset.yapApiUrl || el.dataset.apiUrl;
        if (!apiUrl) {
            console.error('YapWidget: data-yap-api-url is required');
            return;
        }

        const widgetType = el.dataset.yapWidget || 'combined';

        if (widgetType === 'dial' || widgetType === 'call') {
            window.YapDialWidget.init(el, {
                apiUrl: apiUrl,
                serviceBodyId: el.dataset.yapServiceBody || el.dataset.serviceBodyId,
                title: el.dataset.yapTitle || el.dataset.title,
                showLocationInput: el.dataset.yapShowLocation !== 'false',
                showSearchType: el.dataset.yapShowSearchType === 'true',
                defaultSearchType: el.dataset.yapSearchType || 'helpline',
            });
        } else if (widgetType === 'chat') {
            window.YapChatWidget.init(el, {
                apiUrl: apiUrl,
                title: el.dataset.yapTitle || el.dataset.title,
            });
        } else {
            // Default to combined widget
            window.YapWidget.init(el, {
                apiUrl: apiUrl,
                serviceBodyId: el.dataset.yapServiceBody || el.dataset.serviceBodyId,
                title: el.dataset.yapTitle || el.dataset.title,
                defaultTab: el.dataset.yapDefaultTab || 'call',
                showCallTab: el.dataset.yapShowCall !== 'false',
                showChatTab: el.dataset.yapShowChat !== 'false',
                showLocationInput: el.dataset.yapShowLocation !== 'false',
                showSearchType: el.dataset.yapShowSearchType === 'true',
            });
        }
    });
});

// Log version
console.log('YapWidget loaded');

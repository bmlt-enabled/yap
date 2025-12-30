/**
 * Yap WebRTC Dial Widget Loader
 *
 * This is a lightweight loader script that external websites can include
 * to easily embed the Yap dial widget on their pages.
 *
 * Usage:
 *
 * 1. Simple embed with data attributes:
 *    <div id="yap-widget" data-yap-widget data-yap-api-url="https://your-yap-server.com"></div>
 *    <script src="https://your-yap-server.com/widget-loader.js"></script>
 *
 * 2. Programmatic initialization:
 *    <div id="my-widget"></div>
 *    <script src="https://your-yap-server.com/widget-loader.js"></script>
 *    <script>
 *      YapWidget.load({
 *        container: '#my-widget',
 *        apiUrl: 'https://your-yap-server.com',
 *        serviceBodyId: '123',
 *        title: 'NA Helpline'
 *      });
 *    </script>
 */

(function() {
    'use strict';

    // Get the script URL to determine the base URL
    var scripts = document.getElementsByTagName('script');
    var currentScript = scripts[scripts.length - 1];
    var scriptUrl = currentScript.src;
    var baseUrl = scriptUrl.substring(0, scriptUrl.lastIndexOf('/'));

    // Configuration
    var config = {
        widgetScriptUrl: baseUrl + '/js/dial-widget.js',
        cssUrl: null, // Optional CSS URL
        loaded: false,
        loading: false,
        queue: [],
    };

    // Check WebRTC support
    function isWebRTCSupported() {
        return !!(
            navigator.mediaDevices &&
            navigator.mediaDevices.getUserMedia &&
            window.RTCPeerConnection
        );
    }

    // Load the widget script
    function loadWidgetScript(callback) {
        if (config.loaded) {
            callback();
            return;
        }

        if (config.loading) {
            config.queue.push(callback);
            return;
        }

        config.loading = true;

        var script = document.createElement('script');
        script.src = config.widgetScriptUrl;
        script.async = true;

        script.onload = function() {
            config.loaded = true;
            config.loading = false;
            callback();

            // Process queued callbacks
            while (config.queue.length > 0) {
                var queuedCallback = config.queue.shift();
                queuedCallback();
            }
        };

        script.onerror = function() {
            config.loading = false;
            console.error('YapWidget: Failed to load widget script');
        };

        document.head.appendChild(script);

        // Load optional CSS
        if (config.cssUrl) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = config.cssUrl;
            document.head.appendChild(link);
        }
    }

    // Initialize a widget
    function initWidget(options) {
        if (!isWebRTCSupported()) {
            console.warn('YapWidget: WebRTC is not supported in this browser');
            if (options.container) {
                var containerEl = typeof options.container === 'string'
                    ? document.querySelector(options.container)
                    : options.container;
                if (containerEl) {
                    containerEl.innerHTML = '<div style="padding: 20px; text-align: center; color: #666; font-family: sans-serif;">Web calling is not supported in this browser. Please use a modern browser like Chrome, Firefox, or Safari.</div>';
                }
            }
            return null;
        }

        loadWidgetScript(function() {
            if (window.YapDialWidget && window.YapDialWidget.init) {
                window.YapDialWidget.init(options.container, {
                    apiUrl: options.apiUrl,
                    serviceBodyId: options.serviceBodyId,
                    title: options.title,
                    showLocationInput: options.showLocationInput,
                    showSearchType: options.showSearchType,
                    defaultSearchType: options.defaultSearchType,
                    styles: options.styles,
                    onCallStart: options.onCallStart,
                    onCallEnd: options.onCallEnd,
                    onError: options.onError,
                });
            }
        });
    }

    // Auto-initialize widgets with data attributes on DOMContentLoaded
    function autoInit() {
        var widgets = document.querySelectorAll('[data-yap-widget]');

        widgets.forEach(function(el) {
            var apiUrl = el.getAttribute('data-yap-api-url') || el.getAttribute('data-api-url');
            if (!apiUrl) {
                // Try to infer from the loader script URL
                apiUrl = baseUrl;
            }

            if (!apiUrl) {
                console.error('YapWidget: data-yap-api-url is required');
                return;
            }

            initWidget({
                container: el,
                apiUrl: apiUrl,
                serviceBodyId: el.getAttribute('data-yap-service-body') || el.getAttribute('data-service-body-id'),
                title: el.getAttribute('data-yap-title') || el.getAttribute('data-title'),
                showLocationInput: el.getAttribute('data-yap-show-location') !== 'false',
                showSearchType: el.getAttribute('data-yap-show-search-type') === 'true',
                defaultSearchType: el.getAttribute('data-yap-search-type') || 'helpline',
            });
        });
    }

    // Public API
    window.YapWidget = {
        /**
         * Load and initialize the widget
         * @param {Object} options - Configuration options
         */
        load: function(options) {
            if (!options.apiUrl) {
                options.apiUrl = baseUrl;
            }
            initWidget(options);
        },

        /**
         * Check if WebRTC is supported
         * @returns {boolean}
         */
        isSupported: isWebRTCSupported,

        /**
         * Get the base URL
         * @returns {string}
         */
        getBaseUrl: function() {
            return baseUrl;
        },

        /**
         * Preload the widget script without rendering
         * @param {Function} callback - Called when script is loaded
         */
        preload: function(callback) {
            loadWidgetScript(callback || function() {});
        }
    };

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        // DOM already loaded
        autoInit();
    }

    console.log('YapWidget loader ready');
})();

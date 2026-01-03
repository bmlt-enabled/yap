import React, { useState, useEffect } from 'react';
import DialWidget from './DialWidget';
import ChatWidget from './ChatWidget';

// Tab types
const TABS = {
    CALL: 'call',
    CHAT: 'chat',
};

// Default tab styles
const defaultTabStyles = {
    tabContainer: {
        display: 'flex',
        borderBottom: '1px solid #e0e0e0',
        backgroundColor: '#f8f9fa',
    },
    tab: {
        flex: 1,
        padding: '12px 16px',
        fontSize: '14px',
        fontWeight: '500',
        border: 'none',
        backgroundColor: 'transparent',
        cursor: 'pointer',
        transition: 'all 0.2s',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '6px',
        color: '#666',
    },
    tabActive: {
        backgroundColor: '#fff',
        color: '#333',
        borderBottom: '2px solid #0084ff',
        marginBottom: '-1px',
    },
    widgetContainer: {
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        maxWidth: '360px',
        backgroundColor: '#ffffff',
        borderRadius: '12px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
        border: '1px solid #e0e0e0',
        overflow: 'hidden',
    },
    contentContainer: {
        // Container for the tab content
    },
};

// Phone icon for tab
const PhoneTabIcon = () => (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
    </svg>
);

// Chat icon for tab
const ChatTabIcon = () => (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </svg>
);

export default function CommunicationWidget({
    apiUrl,
    serviceBodyId = null,
    title = 'Get Help',
    defaultTab = TABS.CALL,
    showCallTab = true,
    showChatTab = true,
    showLocationInput = true,
    showSearchType = false,
    customStyles = {},
    dialWidgetStyles = {},
    chatWidgetStyles = {},
    onCallStart = () => {},
    onCallEnd = () => {},
    onChatStart = () => {},
    onChatEnd = () => {},
    onError = () => {},
}) {
    const [activeTab, setActiveTab] = useState(defaultTab);
    const [callEnabled, setCallEnabled] = useState(null);
    const [chatEnabled, setChatEnabled] = useState(null);

    // Merge styles
    const styles = { ...defaultTabStyles, ...customStyles };

    // Check what features are available
    useEffect(() => {
        const checkFeatures = async () => {
            // Check if call (WebRTC) is enabled
            if (showCallTab) {
                try {
                    const response = await fetch(`${apiUrl}/api/v1/webrtc/config`);
                    setCallEnabled(response.ok);
                } catch {
                    setCallEnabled(false);
                }
            } else {
                setCallEnabled(false);
            }

            // Check if chat is enabled
            if (showChatTab) {
                try {
                    const response = await fetch(`${apiUrl}/api/v1/webchat/config`);
                    setChatEnabled(response.ok);
                } catch {
                    setChatEnabled(false);
                }
            } else {
                setChatEnabled(false);
            }
        };

        checkFeatures();
    }, [apiUrl, showCallTab, showChatTab]);

    // Determine which tabs to show and default tab
    useEffect(() => {
        if (callEnabled === null || chatEnabled === null) return;

        // If current tab is not available, switch to available one
        if (activeTab === TABS.CALL && !callEnabled && chatEnabled) {
            setActiveTab(TABS.CHAT);
        } else if (activeTab === TABS.CHAT && !chatEnabled && callEnabled) {
            setActiveTab(TABS.CALL);
        }
    }, [callEnabled, chatEnabled, activeTab]);

    // Loading state
    if (callEnabled === null || chatEnabled === null) {
        return (
            <div style={styles.widgetContainer}>
                <div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
                    Loading...
                </div>
            </div>
        );
    }

    // No features available
    if (!callEnabled && !chatEnabled) {
        return (
            <div style={styles.widgetContainer}>
                <div style={{ padding: '20px', textAlign: 'center' }}>
                    <h3 style={{ margin: '0 0 8px', color: '#333' }}>{title}</h3>
                    <p style={{ color: '#666', margin: 0 }}>
                        Service is currently unavailable. Please try again later.
                    </p>
                </div>
            </div>
        );
    }

    // Only one feature available - don't show tabs
    if (callEnabled && !chatEnabled) {
        return (
            <DialWidget
                apiUrl={apiUrl}
                serviceBodyId={serviceBodyId}
                title={title}
                showLocationInput={showLocationInput}
                showSearchType={showSearchType}
                customStyles={dialWidgetStyles}
                onCallStart={onCallStart}
                onCallEnd={onCallEnd}
                onError={onError}
            />
        );
    }

    if (chatEnabled && !callEnabled) {
        return (
            <ChatWidget
                apiUrl={apiUrl}
                title={title}
                customStyles={chatWidgetStyles}
                onSessionStart={onChatStart}
                onSessionEnd={onChatEnd}
                onError={onError}
            />
        );
    }

    // Both features available - show tabs
    return (
        <div style={styles.widgetContainer} className="yap-communication-widget">
            <div style={styles.tabContainer}>
                <button
                    style={{
                        ...styles.tab,
                        ...(activeTab === TABS.CALL ? styles.tabActive : {}),
                    }}
                    onClick={() => setActiveTab(TABS.CALL)}
                >
                    <PhoneTabIcon />
                    Call
                </button>
                <button
                    style={{
                        ...styles.tab,
                        ...(activeTab === TABS.CHAT ? styles.tabActive : {}),
                    }}
                    onClick={() => setActiveTab(TABS.CHAT)}
                >
                    <ChatTabIcon />
                    Chat
                </button>
            </div>
            <div style={styles.contentContainer}>
                {activeTab === TABS.CALL ? (
                    <DialWidget
                        apiUrl={apiUrl}
                        serviceBodyId={serviceBodyId}
                        title={title}
                        showLocationInput={showLocationInput}
                        showSearchType={showSearchType}
                        customStyles={{
                            ...dialWidgetStyles,
                            container: {
                                ...(dialWidgetStyles.container || {}),
                                boxShadow: 'none',
                                border: 'none',
                                borderRadius: 0,
                            },
                        }}
                        onCallStart={onCallStart}
                        onCallEnd={onCallEnd}
                        onError={onError}
                    />
                ) : (
                    <ChatWidget
                        apiUrl={apiUrl}
                        title={title}
                        customStyles={{
                            ...chatWidgetStyles,
                            container: {
                                ...(chatWidgetStyles.container || {}),
                                boxShadow: 'none',
                                border: 'none',
                                borderRadius: 0,
                                height: '450px',
                            },
                        }}
                        onSessionStart={onChatStart}
                        onSessionEnd={onChatEnd}
                        onError={onError}
                    />
                )}
            </div>
        </div>
    );
}

// Export tab constants for external use
CommunicationWidget.TABS = TABS;

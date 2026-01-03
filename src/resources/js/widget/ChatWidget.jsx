import React, { useState, useEffect, useCallback, useRef } from 'react';

// Helper to safely parse JSON from responses (handles HTML error pages)
const safeJsonParse = async (response) => {
    const text = await response.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        // If it's not JSON, it's likely an HTML error page
        if (response.status === 429) {
            return { error: 'Too many requests. Please wait a moment and try again.' };
        }
        if (response.status === 404) {
            return { error: 'Service not found. Please check the API URL.' };
        }
        if (response.status >= 500) {
            return { error: 'Server error. Please try again later.' };
        }
        return { error: `Request failed (${response.status})` };
    }
};

// Chat states
const CHAT_STATES = {
    INITIALIZING: 'initializing',
    MENU: 'menu',                    // Main menu with options
    LOCATION_INPUT: 'location_input', // Getting location for either option
    MEETING_SEARCH: 'meeting_search', // Searching for meetings
    MEETING_RESULTS: 'meeting_results', // Showing meeting results
    CONNECTING: 'connecting',        // Connecting to volunteer
    PENDING: 'pending',              // Waiting for volunteer
    ACTIVE: 'active',                // Active chat with volunteer
    CLOSED: 'closed',
    ERROR: 'error',
    DISABLED: 'disabled'
};

// User intent
const USER_INTENT = {
    FIND_MEETINGS: 'find_meetings',
    TALK_TO_SOMEONE: 'talk_to_someone',
};

// Generate or retrieve client ID
const getClientId = () => {
    const storageKey = 'yap_webchat_client_id';
    let clientId = sessionStorage.getItem(storageKey);
    if (!clientId) {
        clientId = 'webchat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        sessionStorage.setItem(storageKey, clientId);
    }
    return clientId;
};

// Store/retrieve session ID
const getStoredSessionId = () => sessionStorage.getItem('yap_webchat_session_id');
const setStoredSessionId = (sessionId) => {
    if (sessionId) {
        sessionStorage.setItem('yap_webchat_session_id', sessionId);
    } else {
        sessionStorage.removeItem('yap_webchat_session_id');
    }
};

// Default styles
const defaultStyles = {
    container: {
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        maxWidth: '400px',
        height: '550px',
        display: 'flex',
        flexDirection: 'column',
        backgroundColor: '#ffffff',
        borderRadius: '12px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
        border: '1px solid #e0e0e0',
        overflow: 'hidden',
    },
    header: {
        padding: '16px',
        backgroundColor: '#f8f9fa',
        borderBottom: '1px solid #e0e0e0',
    },
    title: {
        fontSize: '16px',
        fontWeight: '600',
        color: '#333',
        margin: 0,
    },
    subtitle: {
        fontSize: '12px',
        color: '#666',
        margin: '4px 0 0 0',
    },
    content: {
        flex: 1,
        overflowY: 'auto',
        padding: '20px',
        display: 'flex',
        flexDirection: 'column',
    },
    menuContainer: {
        flex: 1,
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        gap: '16px',
    },
    menuOption: {
        padding: '20px',
        borderRadius: '12px',
        border: '2px solid #e0e0e0',
        backgroundColor: '#fff',
        cursor: 'pointer',
        transition: 'all 0.2s',
        textAlign: 'left',
        display: 'flex',
        alignItems: 'center',
        gap: '16px',
    },
    menuOptionHover: {
        borderColor: '#22c55e',
        backgroundColor: '#f0fdf4',
    },
    menuIcon: {
        width: '48px',
        height: '48px',
        borderRadius: '12px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        flexShrink: 0,
    },
    menuIconMeetings: {
        backgroundColor: '#dbeafe',
        color: '#2563eb',
    },
    menuIconChat: {
        backgroundColor: '#dcfce7',
        color: '#16a34a',
    },
    menuTitle: {
        fontSize: '16px',
        fontWeight: '600',
        color: '#333',
        margin: 0,
    },
    menuDescription: {
        fontSize: '13px',
        color: '#666',
        margin: '4px 0 0 0',
    },
    messagesContainer: {
        flex: 1,
        overflowY: 'auto',
        padding: '16px',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px',
    },
    message: {
        maxWidth: '80%',
        padding: '10px 14px',
        borderRadius: '16px',
        fontSize: '14px',
        lineHeight: '1.4',
        wordBreak: 'break-word',
    },
    userMessage: {
        alignSelf: 'flex-end',
        backgroundColor: '#0084ff',
        color: '#fff',
        borderBottomRightRadius: '4px',
    },
    volunteerMessage: {
        alignSelf: 'flex-start',
        backgroundColor: '#e9ecef',
        color: '#333',
        borderBottomLeftRadius: '4px',
    },
    systemMessage: {
        alignSelf: 'center',
        backgroundColor: 'transparent',
        color: '#666',
        fontSize: '12px',
        fontStyle: 'italic',
        padding: '4px 8px',
    },
    inputArea: {
        padding: '12px 16px',
        borderTop: '1px solid #e0e0e0',
        backgroundColor: '#fff',
    },
    inputRow: {
        display: 'flex',
        gap: '8px',
    },
    textInput: {
        flex: 1,
        padding: '10px 14px',
        fontSize: '14px',
        border: '1px solid #ddd',
        borderRadius: '20px',
        outline: 'none',
        resize: 'none',
        minHeight: '40px',
        maxHeight: '100px',
    },
    sendButton: {
        padding: '10px 16px',
        fontSize: '14px',
        fontWeight: '600',
        backgroundColor: '#0084ff',
        color: '#fff',
        border: 'none',
        borderRadius: '20px',
        cursor: 'pointer',
        transition: 'background-color 0.2s',
    },
    sendButtonDisabled: {
        backgroundColor: '#ccc',
        cursor: 'not-allowed',
    },
    form: {
        display: 'flex',
        flexDirection: 'column',
        gap: '16px',
    },
    label: {
        display: 'block',
        fontSize: '14px',
        fontWeight: '500',
        color: '#333',
        marginBottom: '6px',
    },
    input: {
        width: '100%',
        padding: '12px',
        fontSize: '14px',
        border: '1px solid #ddd',
        borderRadius: '8px',
        boxSizing: 'border-box',
        outline: 'none',
    },
    textarea: {
        width: '100%',
        padding: '12px',
        fontSize: '14px',
        border: '1px solid #ddd',
        borderRadius: '8px',
        boxSizing: 'border-box',
        outline: 'none',
        minHeight: '80px',
        resize: 'vertical',
    },
    button: {
        width: '100%',
        padding: '14px',
        fontSize: '16px',
        fontWeight: '600',
        border: 'none',
        borderRadius: '8px',
        cursor: 'pointer',
        transition: 'all 0.2s',
    },
    primaryButton: {
        backgroundColor: '#22c55e',
        color: '#fff',
    },
    secondaryButton: {
        backgroundColor: '#f3f4f6',
        color: '#333',
        border: '1px solid #ddd',
    },
    status: {
        textAlign: 'center',
        padding: '20px',
        color: '#666',
    },
    statusPending: {
        backgroundColor: '#fef3c7',
        color: '#92400e',
        padding: '12px',
        borderRadius: '8px',
        margin: '8px 0',
        fontSize: '13px',
        textAlign: 'center',
    },
    errorMessage: {
        backgroundColor: '#fef2f2',
        border: '1px solid #fecaca',
        borderRadius: '8px',
        padding: '12px',
        marginBottom: '16px',
        fontSize: '14px',
        color: '#991b1b',
    },
    timestamp: {
        fontSize: '10px',
        color: '#999',
        marginTop: '4px',
    },
    endChatButton: {
        fontSize: '12px',
        color: '#666',
        background: 'none',
        border: 'none',
        cursor: 'pointer',
        textDecoration: 'underline',
        padding: '8px',
        marginTop: '8px',
    },
    meetingCard: {
        backgroundColor: '#f8f9fa',
        borderRadius: '8px',
        padding: '12px',
        marginBottom: '12px',
        border: '1px solid #e0e0e0',
    },
    meetingName: {
        fontSize: '15px',
        fontWeight: '600',
        color: '#333',
        margin: '0 0 6px 0',
    },
    meetingDetail: {
        fontSize: '13px',
        color: '#666',
        margin: '4px 0',
        display: 'flex',
        alignItems: 'center',
        gap: '6px',
    },
    meetingFormats: {
        display: 'flex',
        flexWrap: 'wrap',
        gap: '4px',
        marginTop: '8px',
    },
    formatTag: {
        backgroundColor: '#e0e0e0',
        color: '#333',
        fontSize: '11px',
        padding: '2px 6px',
        borderRadius: '4px',
    },
    backButton: {
        display: 'flex',
        alignItems: 'center',
        gap: '4px',
        fontSize: '14px',
        color: '#666',
        background: 'none',
        border: 'none',
        cursor: 'pointer',
        padding: '0',
        marginBottom: '16px',
    },
    loadingSpinner: {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        flex: 1,
        gap: '12px',
    },
    noResults: {
        textAlign: 'center',
        padding: '20px',
        color: '#666',
    },
};

// Icons
const SendIcon = () => (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
    </svg>
);

const CalendarIcon = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
        <line x1="16" y1="2" x2="16" y2="6"/>
        <line x1="8" y1="2" x2="8" y2="6"/>
        <line x1="3" y1="10" x2="21" y2="10"/>
    </svg>
);

const ChatIcon = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </svg>
);

const BackIcon = () => (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
);

const LocationIcon = () => (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
        <circle cx="12" cy="10" r="3"/>
    </svg>
);

const ClockIcon = () => (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
    </svg>
);

export default function ChatWidget({
    apiUrl,
    title = 'How can we help?',
    customStyles = {},
    onSessionStart = () => {},
    onSessionEnd = () => {},
    onError = () => {},
}) {
    const [state, setState] = useState(CHAT_STATES.INITIALIZING);
    const [intent, setIntent] = useState(null);
    const [messages, setMessages] = useState([]);
    const [inputMessage, setInputMessage] = useState('');
    const [location, setLocation] = useState('');
    const [initialMessage, setInitialMessage] = useState('');
    const [sessionId, setSessionId] = useState(null);
    const [errorMessage, setErrorMessage] = useState('');
    const [config, setConfig] = useState(null);
    const [lastMessageTime, setLastMessageTime] = useState(null);
    const [meetings, setMeetings] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [hoveredOption, setHoveredOption] = useState(null);

    const messagesEndRef = useRef(null);
    const pollIntervalRef = useRef(null);
    const clientId = useRef(getClientId());

    // Merge styles
    const styles = { ...defaultStyles, ...customStyles };

    // Scroll to bottom of messages
    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    // Initialize widget
    const initialize = useCallback(async () => {
        try {
            const configResponse = await fetch(`${apiUrl}/api/v1/webchat/config`);
            const configData = await safeJsonParse(configResponse);
            if (!configResponse.ok) {
                if (configResponse.status === 403) {
                    setState(CHAT_STATES.DISABLED);
                    return;
                }
                throw new Error(configData.error || 'Failed to load configuration');
            }
            setConfig(configData);

            // Check for existing session
            const existingSessionId = getStoredSessionId();
            if (existingSessionId) {
                const sessionResponse = await fetch(
                    `${apiUrl}/api/v1/webchat/session/${existingSessionId}/messages`
                );
                if (sessionResponse.ok) {
                    const sessionData = await safeJsonParse(sessionResponse);
                    if (sessionData.success && sessionData.status !== 'closed') {
                        setSessionId(existingSessionId);
                        setMessages(sessionData.messages || []);
                        setState(sessionData.status === 'active' ? CHAT_STATES.ACTIVE : CHAT_STATES.PENDING);
                        setIntent(USER_INTENT.TALK_TO_SOMEONE);
                        return;
                    }
                }
                // Session not found or closed, clear it
                setStoredSessionId(null);
            }

            setState(CHAT_STATES.MENU);
        } catch (error) {
            console.error('Failed to initialize chat:', error);
            setErrorMessage(error.message || 'Failed to initialize chat');
            setState(CHAT_STATES.ERROR);
            onError(error);
        }
    }, [apiUrl, onError]);

    // Start polling for new messages
    const startPolling = useCallback(() => {
        if (pollIntervalRef.current) return;

        pollIntervalRef.current = setInterval(async () => {
            if (!sessionId) return;

            try {
                const url = lastMessageTime
                    ? `${apiUrl}/api/v1/webchat/session/${sessionId}/messages?since=${encodeURIComponent(lastMessageTime)}`
                    : `${apiUrl}/api/v1/webchat/session/${sessionId}/messages`;

                const response = await fetch(url);
                const data = await safeJsonParse(response);

                if (!response.ok) {
                    if (response.status === 404) {
                        stopPolling();
                        setState(CHAT_STATES.CLOSED);
                        setStoredSessionId(null);
                    }
                    return;
                }
                if (data.success) {
                    if (data.status === 'closed') {
                        stopPolling();
                        setState(CHAT_STATES.CLOSED);
                        setStoredSessionId(null);
                        onSessionEnd();
                    } else if (data.status === 'active' && state === CHAT_STATES.PENDING) {
                        setState(CHAT_STATES.ACTIVE);
                    }

                    if (data.messages && data.messages.length > 0) {
                        setMessages(prev => {
                            const existingIds = new Set(prev.map(m => m.id));
                            const newMessages = data.messages.filter(m => !existingIds.has(m.id));
                            if (newMessages.length > 0) {
                                setLastMessageTime(newMessages[newMessages.length - 1].timestamp);
                                return [...prev, ...newMessages];
                            }
                            return prev;
                        });
                    }
                }
            } catch (error) {
                console.error('Polling error:', error);
            }
        }, 2000);
    }, [apiUrl, sessionId, lastMessageTime, state, onSessionEnd]);

    const stopPolling = () => {
        if (pollIntervalRef.current) {
            clearInterval(pollIntervalRef.current);
            pollIntervalRef.current = null;
        }
    };

    // Initialize on mount
    useEffect(() => {
        initialize();
        return () => stopPolling();
    }, [initialize]);

    // Start/stop polling based on session state
    useEffect(() => {
        if (sessionId && (state === CHAT_STATES.PENDING || state === CHAT_STATES.ACTIVE)) {
            startPolling();
        } else {
            stopPolling();
        }
    }, [sessionId, state, startPolling]);

    // Handle menu option selection
    const handleMenuSelect = (selectedIntent) => {
        setIntent(selectedIntent);
        setErrorMessage('');
        setState(CHAT_STATES.LOCATION_INPUT);
    };

    // Search for meetings
    const handleMeetingSearch = async () => {
        if (!location.trim()) return;

        setIsSearching(true);
        setErrorMessage('');

        try {
            const response = await fetch(
                `${apiUrl}/api/v1/webchat/meetings?location=${encodeURIComponent(location.trim())}`
            );

            const data = await safeJsonParse(response);

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Failed to search for meetings');
            }

            setMeetings(data.meetings || []);
            setState(CHAT_STATES.MEETING_RESULTS);
        } catch (error) {
            console.error('Meeting search failed:', error);
            setErrorMessage(error.message);
        } finally {
            setIsSearching(false);
        }
    };

    // Start a new chat session
    const handleStartChat = async () => {
        if (!location.trim()) return;

        setState(CHAT_STATES.CONNECTING);
        setErrorMessage('');

        const message = initialMessage.trim() || 'I need to talk to someone.';

        try {
            const response = await fetch(`${apiUrl}/api/v1/webchat/session`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    client_id: clientId.current,
                    location: location.trim(),
                    message: message,
                }),
            });

            const data = await safeJsonParse(response);

            if (!data.success) {
                throw new Error(data.message || data.error || 'Failed to start chat');
            }

            setSessionId(data.session_id);
            setStoredSessionId(data.session_id);
            setMessages([{
                id: 'initial',
                content: message,
                sender: 'user',
                timestamp: new Date().toISOString(),
            }]);
            setState(CHAT_STATES.PENDING);
            setInitialMessage('');
            onSessionStart(data.session_id);
        } catch (error) {
            console.error('Failed to start chat:', error);
            setErrorMessage(error.message);
            setState(CHAT_STATES.LOCATION_INPUT);
            onError(error);
        }
    };

    // Send a message
    const handleSendMessage = async () => {
        if (!inputMessage.trim() || !sessionId) return;

        const messageText = inputMessage.trim();
        setInputMessage('');

        // Optimistically add message
        const tempId = 'temp_' + Date.now();
        const tempMessage = {
            id: tempId,
            content: messageText,
            sender: 'user',
            timestamp: new Date().toISOString(),
        };
        setMessages(prev => [...prev, tempMessage]);

        try {
            const response = await fetch(`${apiUrl}/api/v1/webchat/session/${sessionId}/message`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: messageText }),
            });

            const data = await safeJsonParse(response);

            if (!data.success) {
                setMessages(prev => prev.filter(m => m.id !== tempId));
                if (data.error === 'session_closed' || data.error === 'session_timeout') {
                    setState(CHAT_STATES.CLOSED);
                    setStoredSessionId(null);
                    stopPolling();
                }
                throw new Error(data.error || 'Failed to send message');
            }
        } catch (error) {
            console.error('Failed to send message:', error);
        }
    };

    // End the chat session
    const handleEndChat = async () => {
        if (!sessionId) return;

        try {
            await fetch(`${apiUrl}/api/v1/webchat/session/${sessionId}/close`, {
                method: 'POST',
            });
        } catch (error) {
            console.error('Failed to close session:', error);
        }

        stopPolling();
        setState(CHAT_STATES.CLOSED);
        setStoredSessionId(null);
        onSessionEnd();
    };

    // Go back to menu
    const handleBackToMenu = () => {
        setIntent(null);
        setLocation('');
        setInitialMessage('');
        setMeetings([]);
        setErrorMessage('');
        setState(CHAT_STATES.MENU);
    };

    // Start new chat after closed
    const handleNewChat = () => {
        setMessages([]);
        setSessionId(null);
        setLocation('');
        setInitialMessage('');
        setLastMessageTime(null);
        setIntent(null);
        setState(CHAT_STATES.MENU);
    };

    // Format timestamp
    const formatTime = (timestamp) => {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    // Format day of week
    const getDayName = (dayNum) => {
        const days = ['', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[parseInt(dayNum)] || '';
    };

    // Format meeting time
    const formatMeetingTime = (time) => {
        if (!time) return '';
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    };

    // Render message
    const renderMessage = (message) => {
        let messageStyle = { ...styles.message };
        if (message.sender === 'user') {
            messageStyle = { ...messageStyle, ...styles.userMessage };
        } else if (message.sender === 'volunteer') {
            messageStyle = { ...messageStyle, ...styles.volunteerMessage };
        } else {
            messageStyle = { ...messageStyle, ...styles.systemMessage };
        }

        return (
            <div key={message.id} style={messageStyle}>
                {message.sender === 'volunteer' && message.sender_name && (
                    <div style={{ fontSize: '11px', fontWeight: '600', marginBottom: '4px' }}>
                        {message.sender_name}
                    </div>
                )}
                <div>{message.content}</div>
                {message.sender !== 'system' && (
                    <div style={styles.timestamp}>{formatTime(message.timestamp)}</div>
                )}
            </div>
        );
    };

    // Render meeting card
    const renderMeetingCard = (meeting, index) => (
        <div key={index} style={styles.meetingCard}>
            <h4 style={styles.meetingName}>{meeting.meeting_name}</h4>
            <div style={styles.meetingDetail}>
                <ClockIcon />
                <span>{getDayName(meeting.weekday_tinyint)} at {formatMeetingTime(meeting.start_time)}</span>
            </div>
            {meeting.location_text && (
                <div style={styles.meetingDetail}>
                    <LocationIcon />
                    <span>{meeting.location_text}</span>
                </div>
            )}
            {meeting.location_street && (
                <div style={{ ...styles.meetingDetail, marginLeft: '20px' }}>
                    <span>{meeting.location_street}{meeting.location_city_subsection ? `, ${meeting.location_city_subsection}` : ''}</span>
                </div>
            )}
            {meeting.formats && (
                <div style={styles.meetingFormats}>
                    {meeting.formats.split(',').map((format, i) => (
                        <span key={i} style={styles.formatTag}>{format.trim()}</span>
                    ))}
                </div>
            )}
        </div>
    );

    // Render based on state
    const renderContent = () => {
        switch (state) {
            case CHAT_STATES.INITIALIZING:
                return (
                    <div style={styles.loadingSpinner}>
                        <p>Loading...</p>
                    </div>
                );

            case CHAT_STATES.DISABLED:
                return (
                    <div style={styles.errorMessage}>
                        <p>Chat is not available at this time.</p>
                    </div>
                );

            case CHAT_STATES.ERROR:
                return (
                    <div style={styles.content}>
                        <div style={styles.errorMessage}>
                            <p>{errorMessage}</p>
                        </div>
                        <button
                            style={{ ...styles.button, ...styles.primaryButton }}
                            onClick={() => initialize()}
                        >
                            Try Again
                        </button>
                    </div>
                );

            case CHAT_STATES.MENU:
                return (
                    <div style={styles.content}>
                        <div style={styles.menuContainer}>
                            <div
                                style={{
                                    ...styles.menuOption,
                                    ...(hoveredOption === 'meetings' ? styles.menuOptionHover : {}),
                                }}
                                onClick={() => handleMenuSelect(USER_INTENT.FIND_MEETINGS)}
                                onMouseEnter={() => setHoveredOption('meetings')}
                                onMouseLeave={() => setHoveredOption(null)}
                            >
                                <div style={{ ...styles.menuIcon, ...styles.menuIconMeetings }}>
                                    <CalendarIcon />
                                </div>
                                <div>
                                    <h4 style={styles.menuTitle}>Find Meetings</h4>
                                    <p style={styles.menuDescription}>
                                        Search for meetings near you
                                    </p>
                                </div>
                            </div>

                            <div
                                style={{
                                    ...styles.menuOption,
                                    ...(hoveredOption === 'chat' ? styles.menuOptionHover : {}),
                                }}
                                onClick={() => handleMenuSelect(USER_INTENT.TALK_TO_SOMEONE)}
                                onMouseEnter={() => setHoveredOption('chat')}
                                onMouseLeave={() => setHoveredOption(null)}
                            >
                                <div style={{ ...styles.menuIcon, ...styles.menuIconChat }}>
                                    <ChatIcon />
                                </div>
                                <div>
                                    <h4 style={styles.menuTitle}>Talk to Someone</h4>
                                    <p style={styles.menuDescription}>
                                        Chat with a volunteer now
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                );

            case CHAT_STATES.LOCATION_INPUT:
                return (
                    <div style={styles.content}>
                        <button style={styles.backButton} onClick={handleBackToMenu}>
                            <BackIcon /> Back
                        </button>

                        {errorMessage && (
                            <div style={styles.errorMessage}>
                                <p style={{ margin: 0 }}>{errorMessage}</p>
                            </div>
                        )}

                        <div style={styles.form}>
                            <div>
                                <label style={styles.label}>Your Location</label>
                                <input
                                    type="text"
                                    placeholder="City, State or Zip Code"
                                    value={location}
                                    onChange={(e) => setLocation(e.target.value)}
                                    style={styles.input}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && location.trim()) {
                                            if (intent === USER_INTENT.FIND_MEETINGS) {
                                                handleMeetingSearch();
                                            }
                                        }
                                    }}
                                />
                            </div>

                            {intent === USER_INTENT.TALK_TO_SOMEONE && (
                                <div>
                                    <label style={styles.label}>What's on your mind? (optional)</label>
                                    <textarea
                                        placeholder="Tell us how we can help..."
                                        value={initialMessage}
                                        onChange={(e) => setInitialMessage(e.target.value)}
                                        style={styles.textarea}
                                    />
                                </div>
                            )}

                            <button
                                style={{
                                    ...styles.button,
                                    ...styles.primaryButton,
                                    ...(!location.trim() || isSearching ? { opacity: 0.5, cursor: 'not-allowed' } : {}),
                                }}
                                onClick={intent === USER_INTENT.FIND_MEETINGS ? handleMeetingSearch : handleStartChat}
                                disabled={!location.trim() || isSearching}
                            >
                                {isSearching ? 'Searching...' :
                                 intent === USER_INTENT.FIND_MEETINGS ? 'Search Meetings' : 'Connect with Volunteer'}
                            </button>
                        </div>
                    </div>
                );

            case CHAT_STATES.MEETING_SEARCH:
                return (
                    <div style={styles.loadingSpinner}>
                        <p>Searching for meetings...</p>
                    </div>
                );

            case CHAT_STATES.MEETING_RESULTS:
                return (
                    <div style={{ ...styles.content, overflow: 'hidden' }}>
                        <button style={styles.backButton} onClick={handleBackToMenu}>
                            <BackIcon /> Back
                        </button>

                        <h4 style={{ margin: '0 0 12px', color: '#333', flexShrink: 0 }}>
                            Meetings near {location}
                        </h4>

                        {meetings.length === 0 ? (
                            <div style={styles.noResults}>
                                <p>No meetings found near this location.</p>
                                <button
                                    style={{ ...styles.button, ...styles.secondaryButton, marginTop: '12px' }}
                                    onClick={() => {
                                        setState(CHAT_STATES.LOCATION_INPUT);
                                        setIntent(USER_INTENT.FIND_MEETINGS);
                                    }}
                                >
                                    Try Different Location
                                </button>
                            </div>
                        ) : (
                            <>
                                <div style={{
                                    flex: 1,
                                    overflowY: 'auto',
                                    minHeight: 0,
                                    marginRight: '-20px',
                                    paddingRight: '20px',
                                }}>
                                    {meetings.map(renderMeetingCard)}
                                </div>
                                <div style={{ marginTop: '16px', borderTop: '1px solid #e0e0e0', paddingTop: '16px', flexShrink: 0 }}>
                                    <button
                                        style={{ ...styles.button, ...styles.secondaryButton }}
                                        onClick={() => {
                                            setIntent(USER_INTENT.TALK_TO_SOMEONE);
                                            setState(CHAT_STATES.LOCATION_INPUT);
                                        }}
                                    >
                                        Need to talk to someone?
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                );

            case CHAT_STATES.CONNECTING:
                return (
                    <div style={styles.loadingSpinner}>
                        <p>Connecting to volunteers...</p>
                    </div>
                );

            case CHAT_STATES.PENDING:
            case CHAT_STATES.ACTIVE:
                return (
                    <>
                        {state === CHAT_STATES.PENDING && (
                            <div style={styles.statusPending}>
                                Waiting for a volunteer to respond...
                            </div>
                        )}
                        <div style={styles.messagesContainer}>
                            {messages.map(renderMessage)}
                            <div ref={messagesEndRef} />
                        </div>
                        <div style={styles.inputArea}>
                            <div style={styles.inputRow}>
                                <input
                                    type="text"
                                    placeholder="Type a message..."
                                    value={inputMessage}
                                    onChange={(e) => setInputMessage(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && !e.shiftKey) {
                                            e.preventDefault();
                                            handleSendMessage();
                                        }
                                    }}
                                    style={styles.textInput}
                                    disabled={state === CHAT_STATES.PENDING}
                                />
                                <button
                                    style={{
                                        ...styles.sendButton,
                                        ...((!inputMessage.trim() || state === CHAT_STATES.PENDING) ? styles.sendButtonDisabled : {}),
                                    }}
                                    onClick={handleSendMessage}
                                    disabled={!inputMessage.trim() || state === CHAT_STATES.PENDING}
                                >
                                    <SendIcon />
                                </button>
                            </div>
                            <button
                                style={styles.endChatButton}
                                onClick={handleEndChat}
                            >
                                End Chat
                            </button>
                        </div>
                    </>
                );

            case CHAT_STATES.CLOSED:
                return (
                    <div style={styles.content}>
                        <div style={{ textAlign: 'center', flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
                            <h4 style={{ margin: '0 0 8px', color: '#333' }}>Chat Ended</h4>
                            <p style={{ fontSize: '14px', color: '#666', margin: '0 0 20px' }}>
                                Thank you for reaching out.
                            </p>
                            <button
                                style={{ ...styles.button, ...styles.primaryButton }}
                                onClick={handleNewChat}
                            >
                                Start Over
                            </button>
                        </div>
                    </div>
                );

            default:
                return null;
        }
    };

    // Get subtitle based on state
    const getSubtitle = () => {
        if (state === CHAT_STATES.ACTIVE) return 'Connected with a volunteer';
        if (state === CHAT_STATES.PENDING) return 'Connecting...';
        if (state === CHAT_STATES.MEETING_RESULTS) return 'Meeting search results';
        if (intent === USER_INTENT.FIND_MEETINGS) return 'Find meetings near you';
        if (intent === USER_INTENT.TALK_TO_SOMEONE) return 'Chat with a volunteer';
        return 'Choose an option below';
    };

    return (
        <div style={styles.container} className="yap-chat-widget">
            <div style={styles.header}>
                <h3 style={styles.title}>{config?.title || title}</h3>
                <p style={styles.subtitle}>{getSubtitle()}</p>
            </div>
            {renderContent()}
        </div>
    );
}

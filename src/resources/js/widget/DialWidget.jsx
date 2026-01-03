import React, { useState, useEffect, useCallback, useRef } from 'react';
import { Device } from '@twilio/voice-sdk';

// Widget states
const STATES = {
    INITIALIZING: 'initializing',
    READY: 'ready',
    CONNECTING: 'connecting',
    CONNECTED: 'connected',
    ERROR: 'error',
    DISABLED: 'disabled'
};

// Default styles that can be overridden
const defaultStyles = {
    container: {
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        maxWidth: '320px',
        padding: '20px',
        backgroundColor: '#ffffff',
        borderRadius: '12px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
        border: '1px solid #e0e0e0',
    },
    header: {
        textAlign: 'center',
        marginBottom: '16px',
    },
    title: {
        fontSize: '18px',
        fontWeight: '600',
        color: '#333',
        margin: '0 0 4px 0',
    },
    subtitle: {
        fontSize: '14px',
        color: '#666',
        margin: 0,
    },
    inputGroup: {
        marginBottom: '16px',
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
        transition: 'border-color 0.2s',
    },
    select: {
        width: '100%',
        padding: '12px',
        fontSize: '14px',
        border: '1px solid #ddd',
        borderRadius: '8px',
        boxSizing: 'border-box',
        outline: 'none',
        backgroundColor: '#fff',
        cursor: 'pointer',
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
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '8px',
    },
    callButton: {
        backgroundColor: '#22c55e',
        color: '#fff',
    },
    callButtonHover: {
        backgroundColor: '#16a34a',
    },
    hangupButton: {
        backgroundColor: '#ef4444',
        color: '#fff',
    },
    hangupButtonHover: {
        backgroundColor: '#dc2626',
    },
    disabledButton: {
        backgroundColor: '#ccc',
        color: '#666',
        cursor: 'not-allowed',
    },
    status: {
        textAlign: 'center',
        marginTop: '12px',
        fontSize: '14px',
    },
    statusConnecting: {
        color: '#f59e0b',
    },
    statusConnected: {
        color: '#22c55e',
    },
    statusError: {
        color: '#ef4444',
    },
    controls: {
        display: 'flex',
        gap: '12px',
        marginTop: '12px',
    },
    controlButton: {
        flex: 1,
        padding: '10px',
        fontSize: '14px',
        fontWeight: '500',
        border: '1px solid #ddd',
        borderRadius: '8px',
        backgroundColor: '#fff',
        cursor: 'pointer',
        transition: 'all 0.2s',
    },
    controlButtonActive: {
        backgroundColor: '#f3f4f6',
        borderColor: '#333',
    },
    timer: {
        textAlign: 'center',
        fontSize: '24px',
        fontWeight: '600',
        color: '#333',
        margin: '16px 0',
    },
    errorMessage: {
        backgroundColor: '#fef2f2',
        border: '1px solid #fecaca',
        borderRadius: '8px',
        padding: '12px',
        marginTop: '12px',
        fontSize: '14px',
        color: '#991b1b',
    },
    keypad: {
        display: 'grid',
        gridTemplateColumns: 'repeat(3, 1fr)',
        gap: '8px',
        marginTop: '12px',
        padding: '12px',
        backgroundColor: '#f9fafb',
        borderRadius: '8px',
    },
    keypadButton: {
        padding: '12px',
        fontSize: '18px',
        fontWeight: '600',
        border: '1px solid #e5e7eb',
        borderRadius: '8px',
        backgroundColor: '#fff',
        cursor: 'pointer',
        transition: 'all 0.15s',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        minHeight: '50px',
    },
    keypadButtonPressed: {
        backgroundColor: '#e5e7eb',
        transform: 'scale(0.95)',
    },
    keypadSubtext: {
        fontSize: '9px',
        fontWeight: '400',
        color: '#6b7280',
        marginTop: '2px',
        letterSpacing: '1px',
    },
};

// Phone icon SVG
const PhoneIcon = ({ calling = false }) => (
    <svg
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
        style={{ transform: calling ? 'rotate(135deg)' : 'none', transition: 'transform 0.2s' }}
    >
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
    </svg>
);

// Mute icon SVG
const MuteIcon = ({ muted }) => (
    <svg
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
    >
        {muted ? (
            <>
                <line x1="1" y1="1" x2="23" y2="23" />
                <path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6" />
                <path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2a7 7 0 0 1-.11 1.23" />
                <line x1="12" y1="19" x2="12" y2="23" />
                <line x1="8" y1="23" x2="16" y2="23" />
            </>
        ) : (
            <>
                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z" />
                <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                <line x1="12" y1="19" x2="12" y2="23" />
                <line x1="8" y1="23" x2="16" y2="23" />
            </>
        )}
    </svg>
);

// Keypad icon SVG
const KeypadIcon = () => (
    <svg
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
    >
        <circle cx="4" cy="4" r="1.5" fill="currentColor" />
        <circle cx="12" cy="4" r="1.5" fill="currentColor" />
        <circle cx="20" cy="4" r="1.5" fill="currentColor" />
        <circle cx="4" cy="12" r="1.5" fill="currentColor" />
        <circle cx="12" cy="12" r="1.5" fill="currentColor" />
        <circle cx="20" cy="12" r="1.5" fill="currentColor" />
        <circle cx="4" cy="20" r="1.5" fill="currentColor" />
        <circle cx="12" cy="20" r="1.5" fill="currentColor" />
        <circle cx="20" cy="20" r="1.5" fill="currentColor" />
    </svg>
);

// DTMF keypad data
const KEYPAD_KEYS = [
    { digit: '1', letters: '' },
    { digit: '2', letters: 'ABC' },
    { digit: '3', letters: 'DEF' },
    { digit: '4', letters: 'GHI' },
    { digit: '5', letters: 'JKL' },
    { digit: '6', letters: 'MNO' },
    { digit: '7', letters: 'PQRS' },
    { digit: '8', letters: 'TUV' },
    { digit: '9', letters: 'WXYZ' },
    { digit: '*', letters: '' },
    { digit: '0', letters: '+' },
    { digit: '#', letters: '' },
];

// Format call duration
const formatDuration = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

export default function DialWidget({
    apiUrl,
    serviceBodyId = null,
    title = 'Helpline',
    showLocationInput = true,
    showSearchType = false,
    defaultSearchType = 'helpline',
    customStyles = {},
    onCallStart = () => {},
    onCallEnd = () => {},
    onError = () => {},
}) {
    const [state, setState] = useState(STATES.INITIALIZING);
    const [device, setDevice] = useState(null);
    const [call, setCall] = useState(null);
    const [location, setLocation] = useState('');
    const [searchType, setSearchType] = useState(defaultSearchType);
    const [isMuted, setIsMuted] = useState(false);
    const [duration, setDuration] = useState(0);
    const [errorMessage, setErrorMessage] = useState('');
    const [isHovering, setIsHovering] = useState(false);
    const [config, setConfig] = useState(null);
    const [showKeypad, setShowKeypad] = useState(false);
    const [pressedKey, setPressedKey] = useState(null);

    const timerRef = useRef(null);
    const deviceRef = useRef(null);

    // Merge custom styles with defaults
    const styles = {
        ...defaultStyles,
        ...customStyles,
    };

    // Initialize Twilio Device
    const initializeDevice = useCallback(async () => {
        try {
            // First, check if WebRTC is enabled and get config
            const configResponse = await fetch(`${apiUrl}/api/v1/webrtc/config`);
            if (!configResponse.ok) {
                const error = await configResponse.json();
                throw new Error(error.error || 'WebRTC is not available');
            }
            const configData = await configResponse.json();
            setConfig(configData);

            // Get access token
            const tokenResponse = await fetch(`${apiUrl}/api/v1/webrtc/token`);
            if (!tokenResponse.ok) {
                const error = await tokenResponse.json();
                throw new Error(error.error || 'Failed to get access token');
            }
            const tokenData = await tokenResponse.json();

            // Create and register device
            const twilioDevice = new Device(tokenData.token, {
                codecPreferences: ['opus', 'pcmu'],
                enableRingingState: true,
            });

            twilioDevice.on('registered', () => {
                setState(STATES.READY);
            });

            twilioDevice.on('error', (error) => {
                console.error('Twilio Device error:', error);
                setErrorMessage(error.message || 'An error occurred');
                setState(STATES.ERROR);
                onError(error);
            });

            twilioDevice.on('tokenWillExpire', async () => {
                // Refresh token before it expires
                try {
                    const response = await fetch(`${apiUrl}/api/v1/webrtc/token`);
                    const data = await response.json();
                    twilioDevice.updateToken(data.token);
                } catch (e) {
                    console.error('Failed to refresh token:', e);
                }
            });

            await twilioDevice.register();
            deviceRef.current = twilioDevice;
            setDevice(twilioDevice);
        } catch (error) {
            console.error('Failed to initialize device:', error);
            setErrorMessage(error.message || 'Failed to initialize. Please try again.');
            setState(STATES.ERROR);
            onError(error);
        }
    }, [apiUrl, onError]);

    // Initialize on mount
    useEffect(() => {
        initializeDevice();

        return () => {
            if (deviceRef.current) {
                deviceRef.current.destroy();
            }
            if (timerRef.current) {
                clearInterval(timerRef.current);
            }
        };
    }, [initializeDevice]);

    // Handle making a call
    const handleCall = async () => {
        if (!device || state !== STATES.READY) return;

        try {
            setState(STATES.CONNECTING);
            setErrorMessage('');

            const params = {
                searchType: searchType,
            };

            if (serviceBodyId) {
                params.serviceBodyId = serviceBodyId;
            }

            if (location.trim()) {
                params.location = location.trim();
            }

            const newCall = await device.connect({ params });

            newCall.on('accept', () => {
                setState(STATES.CONNECTED);
                setDuration(0);
                timerRef.current = setInterval(() => {
                    setDuration(d => d + 1);
                }, 1000);
                onCallStart();
            });

            newCall.on('disconnect', () => {
                setState(STATES.READY);
                if (timerRef.current) {
                    clearInterval(timerRef.current);
                    timerRef.current = null;
                }
                setCall(null);
                setIsMuted(false);
                setShowKeypad(false);
                onCallEnd({ duration });
            });

            newCall.on('cancel', () => {
                setState(STATES.READY);
                setCall(null);
            });

            newCall.on('error', (error) => {
                console.error('Call error:', error);
                setErrorMessage(error.message || 'Call failed');
                setState(STATES.ERROR);
                onError(error);
            });

            setCall(newCall);
        } catch (error) {
            console.error('Failed to make call:', error);
            setErrorMessage(error.message || 'Failed to connect call');
            setState(STATES.ERROR);
            onError(error);
        }
    };

    // Handle hanging up
    const handleHangup = () => {
        if (call) {
            call.disconnect();
        }
    };

    // Handle mute toggle
    const handleMuteToggle = () => {
        if (call) {
            if (isMuted) {
                call.mute(false);
            } else {
                call.mute(true);
            }
            setIsMuted(!isMuted);
        }
    };

    // Handle keypad toggle
    const handleKeypadToggle = () => {
        setShowKeypad(!showKeypad);
    };

    // Handle DTMF digit press
    const handleDtmfPress = (digit) => {
        if (call) {
            call.sendDigits(digit);
            setPressedKey(digit);
            // Visual feedback - clear after 150ms
            setTimeout(() => setPressedKey(null), 150);
        }
    };

    // Handle retry after error
    const handleRetry = () => {
        setState(STATES.INITIALIZING);
        setErrorMessage('');
        initializeDevice();
    };

    // Render based on state
    const renderContent = () => {
        switch (state) {
            case STATES.INITIALIZING:
                return (
                    <div style={styles.status}>
                        <p>Initializing...</p>
                    </div>
                );

            case STATES.DISABLED:
                return (
                    <div style={styles.errorMessage}>
                        <p>Web calling is not available at this time.</p>
                    </div>
                );

            case STATES.ERROR:
                return (
                    <>
                        <div style={styles.errorMessage}>
                            <p>{errorMessage}</p>
                        </div>
                        <button
                            style={{
                                ...styles.button,
                                ...styles.callButton,
                                marginTop: '12px',
                            }}
                            onClick={handleRetry}
                        >
                            Try Again
                        </button>
                    </>
                );

            case STATES.READY:
                return (
                    <>
                        {showLocationInput && (
                            <div style={styles.inputGroup}>
                                <label style={styles.label}>Your Location</label>
                                <input
                                    type="text"
                                    placeholder="City, State or Zip Code"
                                    value={location}
                                    onChange={(e) => setLocation(e.target.value)}
                                    style={styles.input}
                                />
                            </div>
                        )}

                        {showSearchType && (
                            <div style={styles.inputGroup}>
                                <label style={styles.label}>I need help with</label>
                                <select
                                    value={searchType}
                                    onChange={(e) => setSearchType(e.target.value)}
                                    style={styles.select}
                                >
                                    <option value="helpline">Talk to someone</option>
                                    <option value="meeting">Find a meeting</option>
                                    <option value="jft">Just For Today</option>
                                    <option value="spad">Spiritual Principle A Day</option>
                                </select>
                            </div>
                        )}

                        <button
                            style={{
                                ...styles.button,
                                ...styles.callButton,
                                ...(isHovering ? styles.callButtonHover : {}),
                            }}
                            onClick={handleCall}
                            onMouseEnter={() => setIsHovering(true)}
                            onMouseLeave={() => setIsHovering(false)}
                        >
                            <PhoneIcon />
                            Call Now
                        </button>
                    </>
                );

            case STATES.CONNECTING:
                return (
                    <>
                        <div style={{ ...styles.status, ...styles.statusConnecting }}>
                            <p>Connecting...</p>
                        </div>
                        <button
                            style={{
                                ...styles.button,
                                ...styles.hangupButton,
                            }}
                            onClick={handleHangup}
                        >
                            <PhoneIcon calling />
                            Cancel
                        </button>
                    </>
                );

            case STATES.CONNECTED:
                return (
                    <>
                        <div style={styles.timer}>
                            {formatDuration(duration)}
                        </div>
                        <div style={{ ...styles.status, ...styles.statusConnected }}>
                            <p>Connected</p>
                        </div>
                        <div style={styles.controls}>
                            <button
                                style={{
                                    ...styles.controlButton,
                                    ...(isMuted ? styles.controlButtonActive : {}),
                                }}
                                onClick={handleMuteToggle}
                            >
                                <MuteIcon muted={isMuted} />
                                {isMuted ? ' Unmute' : ' Mute'}
                            </button>
                            <button
                                style={{
                                    ...styles.controlButton,
                                    ...(showKeypad ? styles.controlButtonActive : {}),
                                }}
                                onClick={handleKeypadToggle}
                            >
                                <KeypadIcon />
                                {' Keypad'}
                            </button>
                        </div>
                        {showKeypad && (
                            <div style={styles.keypad}>
                                {KEYPAD_KEYS.map(({ digit, letters }) => (
                                    <button
                                        key={digit}
                                        style={{
                                            ...styles.keypadButton,
                                            ...(pressedKey === digit ? styles.keypadButtonPressed : {}),
                                        }}
                                        onClick={() => handleDtmfPress(digit)}
                                    >
                                        <span>{digit}</span>
                                        {letters && <span style={styles.keypadSubtext}>{letters}</span>}
                                    </button>
                                ))}
                            </div>
                        )}
                        <button
                            style={{
                                ...styles.button,
                                ...styles.hangupButton,
                                marginTop: '12px',
                                ...(isHovering ? styles.hangupButtonHover : {}),
                            }}
                            onClick={handleHangup}
                            onMouseEnter={() => setIsHovering(true)}
                            onMouseLeave={() => setIsHovering(false)}
                        >
                            <PhoneIcon calling />
                            End Call
                        </button>
                    </>
                );

            default:
                return null;
        }
    };

    return (
        <div style={styles.container} className="yap-dial-widget">
            <div style={styles.header}>
                <h3 style={styles.title}>{config?.title || title}</h3>
                <p style={styles.subtitle}>Click to call from your browser</p>
            </div>
            {renderContent()}
        </div>
    );
}

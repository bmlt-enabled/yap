import { useContext, createContext } from 'react';

/**
 * @typedef {Object} SessionContextValue
 * @property {Object|null} session - The session object or null.
 * @property {function(Object|null):void} setSession - Function to set the session.
 * @property {function():void} openChangePassword - Function to open change password dialog.
 */

/** @type {React.Context<SessionContextValue>} */
export const SessionContext = createContext({
    session: {},
    setSession: () => {},
    openChangePassword: () => {},
});

/**
 * Custom hook to access the SessionContext.
 * @returns {SessionContextValue} The session context value.
 */
export function useSession() {
    return useContext(SessionContext);
}

import React, { createContext, useContext, useState, useEffect } from 'react';
import localizationsManager from '../utils/localizations';

// Create the context
const LocalizationContext = createContext();

// Custom hook to use the localization context
export const useLocalization = () => {
    const context = useContext(LocalizationContext);
    if (!context) {
        throw new Error('useLocalization must be used within a LocalizationProvider');
    }
    return context;
};

// Provider component
export const LocalizationProvider = ({ children }) => {
    const [localizations, setLocalizations] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Initialize localizations on component mount
    useEffect(() => {
        const initLocalizations = async () => {
            try {
                setLoading(true);
                setError(null);
                const data = await localizationsManager.fetchLocalizations();
                setLocalizations(data);
            } catch (err) {
                console.error('Failed to load localizations:', err);
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };

        initLocalizations();
    }, []);

    // Get a localized word
    const getWord = (key) => {
        if (!localizations) {
            return key; // Fallback to key if localizations not loaded
        }
        return localizations[key] || key;
    };

    // Get all localizations
    const getAllWords = () => {
        return localizations || {};
    };

    // Check if localizations are loaded
    const isLoaded = () => {
        return localizations !== null;
    };

    // Refresh localizations (useful if language changes)
    const refreshLocalizations = async () => {
        try {
            setLoading(true);
            setError(null);
            const data = await localizationsManager.fetchLocalizations();
            setLocalizations(data);
        } catch (err) {
            console.error('Failed to refresh localizations:', err);
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const value = {
        localizations,
        loading,
        error,
        getWord,
        getAllWords,
        isLoaded,
        refreshLocalizations
    };

    return (
        <LocalizationContext.Provider value={value}>
            {children}
        </LocalizationContext.Provider>
    );
};

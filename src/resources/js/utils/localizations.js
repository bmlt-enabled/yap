import apiClient from '../services/api';

/**
 * Localizations utility for fetching and using localization data
 */
class LocalizationsManager {
    constructor() {
        this.localizations = null;
    }

    /**
     * Fetch localizations for the current session language from the API
     * @returns {Promise<Object>} Promise that resolves to the localizations data
     */
    async fetchLocalizations() {
        try {
            const response = await apiClient.get('/api/v1/settings/localizations');
            this.localizations = response.data;
            return this.localizations;
        } catch (error) {
            console.error('Error fetching localizations:', error);
            throw error;
        }
    }

    /**
     * Get a localized word/phrase
     * @param {string} key - The localization key
     * @returns {string} The localized text
     */
    getWord(key) {
        if (!this.localizations) {
            console.warn('Localizations not loaded. Call fetchLocalizations() first.');
            return key;
        }

        return this.localizations[key] || key;
    }

    /**
     * Get all localization words
     * @returns {Object} All localizations for the current session language
     */
    getAllWords() {
        if (!this.localizations) {
            console.warn('Localizations not loaded. Call fetchLocalizations() first.');
            return {};
        }

        return this.localizations;
    }

    /**
     * Check if localizations are loaded
     * @returns {boolean} True if localizations are loaded
     */
    isLoaded() {
        return this.localizations !== null;
    }
}

// Create a singleton instance
const localizationsManager = new LocalizationsManager();

// Export for use in other modules
export default localizationsManager;

// Example usage:
/*
// Initialize and fetch localizations for current session language
async function initLocalizations() {
    try {
        await localizationsManager.fetchLocalizations();
        
        // Use localized words
        console.log(localizationsManager.getWord('volunteers')); // "Volunteers" (or localized version)
        console.log(localizationsManager.getWord('settings')); // "Settings" (or localized version)
        console.log(localizationsManager.getWord('add_volunteer')); // "Add Volunteer" (or localized version)
        
        // Get all words
        const allWords = localizationsManager.getAllWords();
        console.log(allWords);
        
        // Check if loaded
        if (localizationsManager.isLoaded()) {
            console.log('Localizations loaded successfully');
        }
        
    } catch (error) {
        console.error('Failed to initialize localizations:', error);
    }
}

// Call this when your app starts
initLocalizations();
*/

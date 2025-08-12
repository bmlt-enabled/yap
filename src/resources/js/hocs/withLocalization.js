import React from 'react';
import { useLocalization } from '../contexts/LocalizationContext';

/**
 * Higher-Order Component that provides localization functionality
 * @param {React.Component} WrappedComponent - The component to wrap
 * @returns {React.Component} Component with localization props
 */
export const withLocalization = (WrappedComponent) => {
    const WithLocalization = (props) => {
        const localization = useLocalization();
        
        return <WrappedComponent {...props} {...localization} />;
    };

    WithLocalization.displayName = `withLocalization(${WrappedComponent.displayName || WrappedComponent.name})`;
    
    return WithLocalization;
};

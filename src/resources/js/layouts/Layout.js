import * as React from 'react';
import { Outlet, Navigate } from 'react-router-dom';
import { DashboardLayout } from '@toolpad/core/DashboardLayout';
import { PageContainer } from '@toolpad/core/PageContainer';
import { useSession } from '../SessionContext';
import { useEffect, useState } from "react";
import apiClient from "../services/api";
import CustomToolbarActions from "../components/CustomToolbarActions";

export default function Layout() {
    const { session, setSession } = useSession();
    const [isValidating, setIsValidating] = useState(true);

    useEffect(() => {
        const validateSession = async () => {
            if (!session) {
                setIsValidating(false);
                return;
            }

            setIsValidating(true);
            try {
                const response = await apiClient.get('/api/v1/user');
                if (response.status !== 200) {
                    setSession(null);
                    localStorage.removeItem('session');
                }
            } catch (error) {
                if (error.response && (error.response.status === 401 || error.response.status === 419)) {
                    setSession(null);
                    localStorage.removeItem('session');
                }
            } finally {
                setIsValidating(false);
            }
        };

        validateSession();
    }, [session, setSession]);

    if (isValidating) {
        return <div>Validating session...</div>;
    }

    if (!session) {
        const redirectTo = `/${baseUrl}/login`;
        return <Navigate to={redirectTo} replace />
    }

    return (
        <DashboardLayout
            slots={{
                toolbarActions: CustomToolbarActions,
            }}
        >
            <PageContainer>
                <Outlet />
            </PageContainer>
        </DashboardLayout>
    );
}

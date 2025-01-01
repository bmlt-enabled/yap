import * as React from 'react';
import { Outlet, Navigate, useLocation } from 'react-router-dom';
import { DashboardLayout } from '@toolpad/core/DashboardLayout';
import { PageContainer } from '@toolpad/core/PageContainer';
import { useSession } from '../SessionContext';

export default function Layout() {
    const { session } = useSession();

    console.log("Layout:" + JSON.parse(session))

    if (!session) {
        // const redirectTo = `/${baseUrl}/login?callbackUrl=${encodeURIComponent(location.pathname)}`;
        const redirectTo = `/${baseUrl}/login`;
        return <Navigate to={redirectTo} replace />
    }

    return (
        <DashboardLayout>
            <PageContainer>
                <Outlet />
            </PageContainer>
        </DashboardLayout>
    );
}

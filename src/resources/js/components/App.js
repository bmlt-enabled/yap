import React from 'react';
import ReactDOM from 'react-dom/client';
import DashboardIcon from '@mui/icons-material/Dashboard';
import SettingsIcon from '@mui/icons-material/Settings';
import PeopleIcon from '@mui/icons-material/People';
import AccountTreeIcon from '@mui/icons-material/AccountTree';
import AssessmentIcon from '@mui/icons-material/Assessment';
import CalendarMonthIcon from '@mui/icons-material/CalendarMonth';
import VolunteerActivismIcon from '@mui/icons-material/VolunteerActivism';
import Diversity1Icon from '@mui/icons-material/Diversity1';
import {
    createBrowserRouter,
    Outlet, RouterProvider,
    useNavigate,
} from "react-router-dom";
import Layout from "../layouts/Layout"
import ServiceBodies from "../pages/ServiceBodies";
import Schedules from "../pages/Schedules";
import Dashboard from "../pages/Dashboard";
import Reports from "../pages/Reports";
import Volunteers from "../pages/Volunteers";
import Groups from "../pages/Groups";
import Users from "../pages/Users";
import Settings from "../pages/Settings";
import {AppProvider} from "@toolpad/core";
import LoginPage from "../pages/Login";
import {SessionContext} from "../SessionContext"
import ErrorBoundary from "./ErrorBoundary";
import { LocalizationProvider } from "../contexts/LocalizationContext";
import ChangePasswordDialog from "../dialogs/ChangePasswordDialog";

export default function App() {
    const [session, setSession] = React.useState(() => {
        const storedSession = localStorage.getItem('session');
        return storedSession ? JSON.parse(storedSession) : null;
    });
    const [showPasswordDialog, setShowPasswordDialog] = React.useState(false);
    const navigate = useNavigate();

    const signIn = React.useCallback(() => {
        navigate(`/${baseUrl}/login`);
    }, [navigate]);

    const signOut = React.useCallback(() => {
        setSession(null);
        localStorage.removeItem('session')
        navigate(`/${baseUrl}/login`);
    }, [navigate]);

    const sessionContextValue = React.useMemo(
        () => ({
            session,
            setSession,
            openChangePassword: () => setShowPasswordDialog(true)
        }),
        [session, setSession],
    );

    const branding = {
        title: 'Yap',
        homeUrl: 'dashboard',
        logo: '',
    };

    const navigation = [
        {
            segment: `${baseUrl}/dashboard`,
            title: "Dashboard",
            icon: <DashboardIcon />
        },
        {
            segment: `${baseUrl}/reports`,
            title: "Reports",
            icon: <AssessmentIcon />
        },
        {
            segment: `${baseUrl}/serviceBodies`,
            title: "Service Bodies",
            icon: <AccountTreeIcon />
        },
        {
            segment: `${baseUrl}/schedule`,
            title: "Schedules",
            icon: <CalendarMonthIcon />
        },
        {
            segment: `${baseUrl}/settings`,
            title: 'Settings',
            icon: <SettingsIcon />,
        },
        {
            segment: `${baseUrl}/volunteers`,
            title: 'Volunteers',
            icon: <VolunteerActivismIcon />,
        },
        {
            segment: `${baseUrl}/groups`,
            title: 'Groups',
            icon: <Diversity1Icon />,
        },
        {
            segment: `${baseUrl}/users`,
            title: 'Users',
            icon: <PeopleIcon />,
        },
    ];

    const authentication = React.useMemo(() => ({
        signIn,
        signOut,
    }), [signIn, signOut]);

    return (
        <SessionContext.Provider value={sessionContextValue}>
            <LocalizationProvider>
                <AppProvider
                    branding={branding}
                    authentication={authentication}
                    session={session}
                    navigation={navigation}
                >
                    <Outlet/>
                    <ChangePasswordDialog
                        open={showPasswordDialog}
                        onClose={() => setShowPasswordDialog(false)}
                        username={session?.user?.username}
                    />
                </AppProvider>
            </LocalizationProvider>
        </SessionContext.Provider>
    );
}

if (document.getElementById('root')) {
    const getPath = (path) => `/${baseUrl}${path}`;

    const router = createBrowserRouter([
        {
            Component: App,
            children: [
                {
                    path: `/${baseUrl}`,
                    Component: Layout,
                    children: [
                        {
                            path: getPath('dashboard'),
                            Component: Dashboard,
                        },
                        {
                            path: getPath('reports'),
                            Component: Reports,
                        },
                        {
                            path: getPath('serviceBodies'),
                            Component: ServiceBodies,
                        },
                        {
                            path: getPath('settings'),
                            Component: Settings,
                        },
                        {
                            path: getPath('schedule'),
                            Component: Schedules,
                        },
                        {
                            path: getPath('volunteers'),
                            Component: Volunteers,
                        },
                        {
                            path: getPath('groups'),
                            Component: Groups,
                        },
                        {
                            path: getPath('users'),
                            Component: Users,
                        },
                    ],
                },
                {
                    path: `/${baseUrl}/login`,
                    Component: LoginPage,
                }
            ],
        },
    ]);

    ReactDOM.createRoot(document.getElementById("root")).render(
        <React.StrictMode>
            <RouterProvider router={router}/>
        </React.StrictMode>
    )
}

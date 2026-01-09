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
    useLocation,
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
    const location = useLocation();

    const signIn = React.useCallback(() => {
        navigate('/login');
    }, [navigate]);

    const signOut = React.useCallback(() => {
        setSession(null);
        localStorage.removeItem('session')
        navigate('/login');
    }, [navigate]);

    // Create a custom router object for Toolpad that uses React Router
    const router = React.useMemo(() => ({
        pathname: location.pathname,
        searchParams: new URLSearchParams(location.search),
        navigate: (url) => navigate(url),
    }), [location, navigate]);

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
        homeUrl: '/dashboard',
        logo: '',
    };

    const navigation = [
        {
            segment: 'dashboard',
            title: "Dashboard",
            icon: <DashboardIcon />
        },
        {
            segment: 'reports',
            title: "Reports",
            icon: <AssessmentIcon />
        },
        {
            segment: 'serviceBodies',
            title: "Service Bodies",
            icon: <AccountTreeIcon />
        },
        {
            segment: 'schedule',
            title: "Schedules",
            icon: <CalendarMonthIcon />
        },
        {
            segment: 'settings',
            title: 'Settings',
            icon: <SettingsIcon />,
        },
        {
            segment: 'volunteers',
            title: 'Volunteers',
            icon: <VolunteerActivismIcon />,
        },
        {
            segment: 'groups',
            title: 'Groups',
            icon: <Diversity1Icon />,
        },
        {
            segment: 'users',
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
                    router={router}
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
    // Combine rootUrl (e.g., /yap-sezf) with baseUrl (e.g., admin) for the full base path
    const fullBasePath = rootUrl ? `${rootUrl}/${baseUrl}` : `/${baseUrl}`;

    const router = createBrowserRouter([
        {
            Component: App,
            children: [
                {
                    path: '/',
                    Component: Layout,
                    children: [
                        {
                            path: 'dashboard',
                            Component: Dashboard,
                        },
                        {
                            path: 'reports',
                            Component: Reports,
                        },
                        {
                            path: 'serviceBodies',
                            Component: ServiceBodies,
                        },
                        {
                            path: 'settings',
                            Component: Settings,
                        },
                        {
                            path: 'schedule',
                            Component: Schedules,
                        },
                        {
                            path: 'volunteers',
                            Component: Volunteers,
                        },
                        {
                            path: 'groups',
                            Component: Groups,
                        },
                        {
                            path: 'users',
                            Component: Users,
                        },
                    ],
                },
                {
                    path: '/login',
                    Component: LoginPage,
                }
            ],
        },
    ], {
        basename: fullBasePath,
    });

    ReactDOM.createRoot(document.getElementById("root")).render(
        <React.StrictMode>
            <RouterProvider router={router}/>
        </React.StrictMode>
    )
}

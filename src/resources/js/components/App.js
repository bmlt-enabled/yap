import React from 'react';
import ReactDOM from 'react-dom/client';
import {createTheme, Button, ThemeProvider} from "@mui/material";
import DashboardIcon from '@mui/icons-material/Dashboard';
import SettingsIcon from '@mui/icons-material/Settings';
import PeopleIcon from '@mui/icons-material/People';
import AccountTreeIcon from '@mui/icons-material/AccountTree';
import AssessmentIcon from '@mui/icons-material/Assessment';
import CalendarMonthIcon from '@mui/icons-material/CalendarMonth';
import VolunteerActivismIcon from '@mui/icons-material/VolunteerActivism';
import Diversity1Icon from '@mui/icons-material/Diversity1';
import {
    BrowserRouter,
    createBrowserRouter,
    Outlet,
    Route,
    Router,
    RouterProvider,
    Routes,
    useLocation
} from "react-router-dom";
import Layout from "../layouts/dashboard"
import ServiceBodies from "../pages/ServiceBodies";
import Schedules from "../pages/Schedules";
import Dashboard from "../pages/Dashboard";
import Reports from "../pages/Reports";
import Volunteers from "../pages/Volunteers";
import Groups from "../pages/Groups";
import Users from "../pages/Users";
import Settings from "../pages/Settings";
import Login from "../pages/Login";
import {AppProvider, DashboardLayout} from "@toolpad/core";
import * as PropTypes from "prop-types";
import {Navigation} from "react-router-dom";

export default function App() {
    const themeOptions = createTheme({
        palette: {
            type: 'dark',
            primary: {
                main: '#5893df',
            },
            secondary: {
                main: '#2ec5d3',
            },
            background: {
                default: '#192231',
                paper: '#f8f9fa',
            },
        },
    });

    const branding = {
        title: 'Yap',
        homeUrl: 'dashboard',
        logo: '',
    };

    const navigation = [
        {
            kind: 'header',
            title: 'Main items',
        },
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
            title: "Schedule",
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

    return (
        <AppProvider
            branding={branding}
            navigation={navigation}>
            <Outlet/>
        </AppProvider>
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
                            path: getPath('login'),
                            Component: Login,
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
                        }
                    ],
                },
            ],
        },
    ]);

    ReactDOM.createRoot(document.getElementById("root")).render(
        <React.StrictMode>
            <RouterProvider router={router}/>
        </React.StrictMode>
    )
}

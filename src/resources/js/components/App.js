import React from 'react';
import ReactDOM from 'react-dom/client';
import {createTheme, Button, ThemeProvider} from "@mui/material";
import MenuBar from './MenuBar';
import {BrowserRouter, Route, Routes, useLocation} from "react-router-dom";
import ServiceBodies from "../pages/ServiceBodies";
import Schedules from "../pages/Schedules";
import Home from "../pages/Home";
import Reports from "../pages/Reports";
import Volunteers from "../pages/Volunteers";
import Groups from "../pages/Groups";
import Users from "../pages/Users";
import Settings from "../pages/Settings";
import Login from "../pages/Login";

function App() {
    const location = useLocation();
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
                paper: '#24344d',
            },
        },
    });

    return (
        <div className="App">
            <ThemeProvider theme={themeOptions}>
                <MenuBar currentRoute={location.pathname}/>
                <header className="App-header">
                    <Routes>
                        <Route path={`${baseUrl}/`} element={<Home/>} />
                        <Route path={`${baseUrl}/login`} element={<Login />} />
                        <Route path={`${baseUrl}/reports`} element={<Reports/>} />
                        <Route path={`${baseUrl}/serviceBodies`} element={<ServiceBodies/>} />
                        <Route path={`${baseUrl}/schedules`} element={<Schedules/>} />
                        <Route path={`${baseUrl}/volunteers`} element={<Volunteers/>} />
                        <Route path={`${baseUrl}/settings`} element={<Settings/>} />
                        <Route path={`${baseUrl}/groups`} element={<Groups/>} />
                        <Route path={`${baseUrl}/users`} element={<Users/>} />
                    </Routes>
                </header>
            </ThemeProvider>
        </div>
    );
}

export default App;

if (document.getElementById('root')) {
    const root = ReactDOM.createRoot(document.getElementById("root"));

    root.render(
        <React.StrictMode>
            <BrowserRouter>
                <App/>
            </BrowserRouter>
        </React.StrictMode>
    )
}

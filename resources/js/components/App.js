import React from 'react';
import ReactDOM from 'react-dom/client';
import {createTheme, Button, ThemeProvider} from "@mui/material";
import MenuBar from './MenuBar';
import {BrowserRouter, Route, Routes, useLocation} from "react-router-dom";
import ServiceBodies from "./ServiceBodies";
import Schedules from "./Schedules";
import Home from "./Home";
import Reports from "./Reports";
import Volunteers from "./Volunteers";
import Groups from "./Groups";
import Users from "./Users";

function App() {
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
                <MenuBar/>
                <header className="App-header">
                    <Routes>
                        <Route path={`${baseUrl}/`} element={<Home/>} />
                        <Route path={`${baseUrl}/reports`} element={<Reports/>} />
                        <Route path={`${baseUrl}/serviceBodies`} element={<ServiceBodies/>} />
                        <Route path={`${baseUrl}/schedules`} element={<Schedules/>} />
                        <Route path={`${baseUrl}/volunteers`} element={<Volunteers/>} />
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

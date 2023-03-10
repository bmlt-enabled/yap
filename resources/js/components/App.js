import React from 'react';
import ReactDOM from 'react-dom/client';
import {createTheme, Button, ThemeProvider} from "@mui/material";
import MenuBar from './MenuBar';
import {BrowserRouter, Route, Routes, useLocation} from "react-router-dom";
import ServiceBodies from "./ServiceBodies";
import Schedules from "./Schedules";

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
                <MenuBar basePath={window.location.pathname}/>
                <header className="App-header">
                    <Routes>
                        <Route path={`${window.location.pathname}/serviceBodies`} element={<ServiceBodies/>} />
                        <Route path={`${window.location.pathname}/schedules`} element={<Schedules/>} />
                    </Routes>
                </header>
                <div className="row justify-content-center">
                </div>
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

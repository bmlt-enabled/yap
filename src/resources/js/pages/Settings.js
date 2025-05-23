// src/resources/js/pages/Settings.js
import React, { useEffect, useState } from "react";
import {
    Button,
    Card,
    CardContent,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
} from "@mui/material";
import apiClient from "../services/api";

function Settings() {
    const [settings, setSettings] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        setLoading(true)
        const fetchSettings = async () => {
            let response = await apiClient.get('/api/v1/settings')

            setSettings(response.data.settings)
            setLoading(false);
        };

        fetchSettings();
    }, []);

    if (loading) {
        return <div>Loading...</div>;
    }

    const clearCache = async () => {
        try {
            const response = await fetch(`${rootUrl}/api/v1/cache`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')// Example for Laravel CSRF
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Handle successful response, e.g., show a success message
            console.log("Cache cleared successfully");
            alert("Cache cleared successfully");

        } catch (error) {
            console.error("Error clearing cache:", error);
            alert("Error clearing cache. See console for details.");
        }
    };

    return (
        <Card className="card">
            <CardContent>
                <Button onClick={clearCache} variant="contained" color="warning">Clear Database Cache</Button>
                <TableContainer>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableCell>Setting</TableCell>
                                <TableCell>Value</TableCell>
                                <TableCell>Current Source</TableCell>
                                <TableCell>Default</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {settings.map((setting) => (
                                <TableRow key={setting.key}>
                                    <TableCell>{setting.key} <a href={setting.docs} target="_blank" rel="noopener noreferrer">📖</a></TableCell>
                                    <TableCell>{setting.value !== null ? setting.value.toString() : ""}</TableCell>
                                    <TableCell>{setting.source}</TableCell>
                                    <TableCell>{JSON.stringify(setting.default)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableContainer>
            </CardContent>
        </Card>
    );
}

export default Settings;

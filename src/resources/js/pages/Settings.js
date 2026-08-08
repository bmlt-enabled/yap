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
    Box,
    Typography,
    CircularProgress,
} from "@mui/material";
import SettingsIcon from '@mui/icons-material/Settings';
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
        return (
            <Box sx={{ p: 3, display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '200px' }}>
                <CircularProgress />
            </Box>
        );
    }

    const clearCache = async () => {
        try {
            await apiClient.post('/api/v1/cache');
            alert("Cache cleared successfully");
        } catch (error) {
            console.error("Error clearing cache:", error);
            alert("Error clearing cache. See console for details.");
        }
    };

    return (
        <Box sx={{ p: 3 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
                <SettingsIcon sx={{ fontSize: 40, mr: 2, color: 'primary.main' }} />
                <Typography variant="h4" fontWeight={600}>
                    Settings
                </Typography>
            </Box>
            <Card>
                <CardContent>
                    <Box sx={{ mb: 3 }}>
                        <Button onClick={clearCache} variant="contained" color="warning">Clear Database Cache</Button>
                    </Box>
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
                                        <TableCell>
                                            {setting.key}
                                            {setting.docs ? (
                                                <a href={setting.docs} target="_blank" rel="noopener noreferrer"> 📖</a>
                                            ) : null}
                                        </TableCell>
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
        </Box>
    );
}

export default Settings;

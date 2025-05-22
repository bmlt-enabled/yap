import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    InputLabel,
    MenuItem,
    Select,
    TextField,
    Typography,
    Box,
    Paper
} from "@mui/material";
import React, {useEffect, useState} from "react";
import apiClient from "../services/api";

export function ServiceBodyConfigurationDialog({ open, onClose, serviceBodyId, serviceBodyName }) {
    const [allowlist, setAllowlist] = useState([]);
    const [selectedField, setSelectedField] = useState('');
    const [customFields, setCustomFields] = useState([]);
    const [loading, setLoading] = useState(true);

    const getAllowlist = async () => {
        try {
            const response = await apiClient.get(`${rootUrl}/api/v1/settings/allowlist`);
            setAllowlist(response.data);
        } catch (error) {
            console.error("Error fetching allowlist:", error);
        }
    };

    const getCurrentConfiguration = async () => {
        try {
            const response = await apiClient.get(`${rootUrl}/api/v1/settings/serviceBody/${serviceBodyId}`);
            setCustomFields(response.data);
        } catch (error) {
            console.error("Error fetching current configuration:", error);
        }
        setLoading(false);
    };

    const handleFieldSelect = (event) => {
        setSelectedField(event.target.value);
    };

    const addField = () => {
        if (!selectedField) return;
        
        const field = allowlist.find(f => f.setting === selectedField);
        if (!field) return;

        setCustomFields([...customFields, {
            setting: selectedField,
            value: typeof field.default === 'object' ? JSON.stringify(field.default) : field.default,
            default: field.default
        }]);
        setSelectedField('');
    };

    const handleFieldChange = (index, value) => {
        const newFields = [...customFields];
        newFields[index].value = value;
        setCustomFields(newFields);
    };

    const removeField = (index) => {
        const newFields = [...customFields];
        newFields.splice(index, 1);
        setCustomFields(newFields);
    };

    const saveConfiguration = async () => {
        try {
            await apiClient.post(`${rootUrl}/api/v1/settings/serviceBody/${serviceBodyId}`, {
                fields: customFields
            });
            onClose();
        } catch (error) {
            console.error("Error saving configuration:", error);
        }
    };

    useEffect(() => {
        if (open) {
            setLoading(true);
            Promise.all([getAllowlist(), getCurrentConfiguration()]);
        }
    }, [open, serviceBodyId]);

    if (loading) {
        return <Dialog onClose={onClose} open={open}><DialogContent>Loading...</DialogContent></Dialog>;
    }

    return (
        <Dialog fullWidth open={open} onClose={onClose}>
            <DialogTitle>Configure {serviceBodyName} ({serviceBodyId})</DialogTitle>
            <DialogContent>
                <Paper sx={{ p: 2, mb: 2, bgcolor: 'background.paper', border: 1, borderColor: 'divider' }}>
                    <Typography variant="h6" gutterBottom>Configuration Precedence</Typography>
                    <Typography variant="body2" component="ol" sx={{ pl: 2 }}>
                        <li>Querystring parameters (highest priority)</li>
                        <li>Session overrides</li>
                        <li>Service body overrides</li>
                        <li>Config.php</li>
                        <li>Factory defaults (lowest priority)</li>
                    </Typography>
                    <Typography variant="body2" sx={{ mt: 1 }}>
                        For more details, see the{' '}
                        <a href="https://yap.bmlt.app/general/configuration-precedence/" target="_blank" rel="noopener noreferrer">
                            Configuration Precedence documentation
                        </a>.
                    </Typography>
                </Paper>

                <FormControl fullWidth sx={{ mb: 2 }}>
                    <InputLabel>Select Setting</InputLabel>
                    <Select
                        value={selectedField}
                        onChange={handleFieldSelect}
                        label="Select Setting"
                    >
                        {allowlist.map((field) => (
                            <MenuItem key={field.setting} value={field.setting}>
                                {field.setting}
                            </MenuItem>
                        ))}
                    </Select>
                </FormControl>

                <Button variant="contained" onClick={addField} sx={{ mb: 2 }}>
                    Add Field
                </Button>

                {customFields.map((field, index) => (
                    <Box key={index} sx={{ display: 'flex', gap: 1, mb: 2 }}>
                        <Box sx={{ flex: 1 }}>
                            <Typography variant="subtitle2" sx={{ mb: 0.5 }}>
                                {field.setting}
                            </Typography>
                            <TextField
                                fullWidth
                                placeholder="Enter value"
                                value={field.value}
                                onChange={(e) => handleFieldChange(index, e.target.value)}
                                helperText={`Default: ${JSON.stringify(allowlist.find(f => f.setting === field.setting)?.default)}`}
                            />
                        </Box>
                        <Button 
                            color="error" 
                            onClick={() => removeField(index)}
                            sx={{ minWidth: '40px' }}
                        >
                            ×
                        </Button>
                    </Box>
                ))}
            </DialogContent>
            <DialogActions>
                <Button color="error" onClick={onClose}>Close</Button>
                <Button color="primary" onClick={saveConfiguration}>Save Changes</Button>
            </DialogActions>
        </Dialog>
    );
} 
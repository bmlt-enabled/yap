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
            const response = await apiClient(`${rootUrl}/api/v1/settings/allowlist`);
            setAllowlist(response.data);
        } catch (error) {
            console.error("Error fetching allowlist:", error);
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
            value: field.default,
            default: field.default
        }]);
        setSelectedField('');
    };

    const handleFieldChange = (index, value) => {
        const newFields = [...customFields];
        newFields[index].value = value;
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
            getAllowlist();
        }
    }, [open]);

    if (loading) {
        return <Dialog onClose={onClose} open={open}><DialogContent>Loading...</DialogContent></Dialog>;
    }

    return (
        <Dialog fullWidth open={open} onClose={onClose}>
            <DialogTitle>Configure {serviceBodyName} ({serviceBodyId})</DialogTitle>
            <DialogContent>
                <Typography variant="body2">
                    For more details, see the{' '}
                    <a href="https://yap.bmlt.app/general/configuration-precedence/" target="_blank" rel="noopener noreferrer">
                        Configuration Precedence documentation
                    </a>.
                </Typography>

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
                    <TextField
                        key={index}
                        fullWidth
                        label={field.setting}
                        value={field.value}
                        onChange={(e) => handleFieldChange(index, e.target.value)}
                        sx={{ mb: 2 }}
                        helperText={`Default: ${JSON.stringify(field.default)}`}
                    />
                ))}
            </DialogContent>
            <DialogActions>
                <Button color="error" onClick={onClose}>Close</Button>
                <Button color="primary" onClick={saveConfiguration}>Save Changes</Button>
            </DialogActions>
        </Dialog>
    );
} 
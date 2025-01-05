import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle, InputLabel, MenuItem, Select, TextField
} from "@mui/material";
import React, {useEffect, useState} from "react";
import apiClient from "../services/api";
import {defaultCallHandlingData} from "../models/CallHandlingModel";

export function CallHandlingDialog({ open, onClose, serviceBodyId }) {
    const [callHandlingData, setCallHandlingData] = useState(defaultCallHandlingData)
    const [loading, setLoading] = useState(true);

    const getCallHandlingData = async() => {
        setLoading(true)
        if (serviceBodyId) {
            try {
                const response = await apiClient(`${rootUrl}/api/v1/callHandling?serviceBodyId=${serviceBodyId}`);
                const responseData = await response.data.data[0]
                console.log(responseData)
                setCallHandlingData({ ...defaultCallHandlingData, ...responseData })
            } catch (error) {
                console.error("Error fetching data:", error);
            }
        }
        setLoading(false)
    }

    const saveCallHandlineData = async () => {
        try {
            const response = await apiClient.post(`${rootUrl}/api/v1/callHandling?serviceBodyId=${serviceBodyId}`, callHandlingData);
            console.log("Save successful:", response.data);
            onClose(); // Close the dialog after successful save
        } catch (error) {
            console.error("Error saving call handling data:", error);
            // Handle error, e.g., show an error message
        }
    };

    const handleChange = (event) => {
        setCallHandlingData({
            ...callHandlingData,
            [event.target.name]: event.target.value,
        });
    };

    useEffect(() => {
        getCallHandlingData()
    }, [serviceBodyId])

    if (loading) {
        return <Dialog onClose={onClose} open={open}><DialogContent>Loading...</DialogContent></Dialog>; // Show loading indicator
    }

    return (
        <Dialog fullWidth open={open} onClose={() => onClose()}>
            <DialogTitle>Service Body Call Handling ({serviceBodyId})</DialogTitle>
            <DialogContent>
                <InputLabel id="volunteer_routing_label">Helpline Routing:</InputLabel>
                <Select labelId="volunteer_routing_label"
                        id="volunteer_routing"
                        variant="outlined"
                        value={callHandlingData.volunteer_routing}
                        inputProps={{ name: "volunteer_routing" }}
                        onChange={handleChange}
                        fullWidth>
                    <MenuItem value="helpline_field">Helpline Field Number</MenuItem>
                    <MenuItem value="volunteers">Volunteers</MenuItem>
                    <MenuItem value="volunteers_redirect">Volunteers Redirect</MenuItem>
                    <MenuItem value="volunteers_and_sms">Volunteers and SMS</MenuItem>
                </Select>

                <InputLabel htmlFor="forced_caller_id">Forced Caller Id (Must Be A Verified Twilio Number):</InputLabel>
                <TextField
                    id="forced_caller_id"
                    value={callHandlingData.forced_caller_id}
                    onChange={handleChange}
                    fullWidth
                />
            </DialogContent>
            <DialogActions>
                <Button color="error" onClick={() => onClose()}>Close</Button>
                <Button color="primary" onClick={saveCallHandlineData}>Save Changes</Button>
            </DialogActions>
        </Dialog>
    );
}

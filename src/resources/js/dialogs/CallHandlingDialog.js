import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle, InputLabel, MenuItem, Select, Switch, TextField
} from "@mui/material";
import React, {useEffect, useState} from "react";
import apiClient from "../services/api";
import {defaultCallHandlingData} from "../models/CallHandlingModel";
import { VOLUNTEER_ROUTING_OPTIONS } from "../constants"; // Adjust path as needed

export function CallHandlingDialog({ open, onClose, serviceBodyId }) {
    const [callHandlingData, setCallHandlingData] = useState(defaultCallHandlingData)
    const [loading, setLoading] = useState(true);

    const getCallHandlingData = async() => {
        setLoading(true)
        if (serviceBodyId) {
            try {
                const response = await apiClient(`${rootUrl}/api/v1/callHandling?serviceBodyId=${serviceBodyId}`);
                const responseData = await response.data;
                if (responseData && responseData.data && Array.isArray(responseData.data) && responseData.data.length > 0) {
                    setCallHandlingData({ ...defaultCallHandlingData, ...responseData.data[0] });
                } else {
                    setCallHandlingData(defaultCallHandlingData);
                }
            } catch (error) {
                console.error("Error fetching data:", error);
            }
        }
        setLoading(false)
    }

    const saveCallHandlingData = async () => {
        try {
            const response = await apiClient.post(`${rootUrl}/api/v1/callHandling?serviceBodyId=${serviceBodyId}`, callHandlingData);
            console.log("Save successful:", response.data);
            onClose(); // Close the dialog after successful save
        } catch (error) {
            console.error("Error saving call handling data:", error);
        }
    };

    const handleChange = (event) => {
        let value = event.target.checked ?? event.target.value
        console.log(event.target.name + " " + value)
        setCallHandlingData({
            ...callHandlingData,
            [event.target.name]: value,
        });
    };

    const shouldShowField = (fieldName, allowedValues) => {
        return allowedValues.includes(callHandlingData[fieldName]);
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
                        inputProps={{name: "volunteer_routing"}}
                        onChange={handleChange}
                        fullWidth>
                    <MenuItem value="helpline_field">Helpline Field Number</MenuItem>
                    <MenuItem value="volunteers">Volunteers</MenuItem>
                    <MenuItem value="volunteers_redirect">Volunteers Redirect</MenuItem>
                    <MenuItem value="volunteers_and_sms">Volunteers and SMS</MenuItem>
                </Select>

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.HELPLINE_FIELD,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS]) && (
                    <>
                        <InputLabel htmlFor="forced_caller_id">Forced Caller Id (Must Be A Verified Twilio
                            Number):</InputLabel>
                        <TextField
                            id="forced_caller_id"
                            value={callHandlingData.forced_caller_id}
                            onChange={handleChange}
                            fullWidth
                        />
                    </>
                )}

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_REDIRECT,
                    ]) && (
                    <>
                        <InputLabel htmlFor="volunteers_redirect_id">Volunteers Redirect Id:</InputLabel>
                        <TextField
                            id="volunteers_redirect_id"
                            value={callHandlingData.volunteers_redirect_id}
                            onChange={handleChange}
                            fullWidth
                        />
                    </>
                )}

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS
                    ]) && (
                    <>
                        <InputLabel htmlFor="call_timeout">Call Timeout (default: 20 seconds):</InputLabel>
                        <TextField
                            id="call_timeout"
                            value={callHandlingData.call_timeout}
                            onChange={handleChange}
                            fullWidth
                        />
                    </>
                )}

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS
                    ]) && (
                    <>
                        <InputLabel htmlFor="gender_routing">Gender Routing:</InputLabel>
                        <Switch
                            id="gender_routing"
                            checked={Boolean(callHandlingData.gender_routing)}
                            inputProps={{ name: "gender_routing" }}
                            onChange={handleChange}
                        />
                    </>
                )}
            </DialogContent>
            <DialogActions>
                <Button color="error" onClick={() => onClose()}>Close</Button>
                <Button color="primary" onClick={saveCallHandlingData}>Save Changes</Button>
            </DialogActions>
        </Dialog>
    );
}

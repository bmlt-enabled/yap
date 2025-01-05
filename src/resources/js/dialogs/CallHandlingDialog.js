import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle, InputLabel, Link, MenuItem, Select, Switch, TextField, Typography
} from "@mui/material";
import React, {useEffect, useState} from "react";
import apiClient from "../services/api";
import {defaultCallHandlingData} from "../models/CallHandlingModel";
import {CALL_STRATEGY, SMS_STRATEGY, VOLUNTEER_ROUTING_OPTIONS} from "../constants"; // Adjust path as needed

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
                <div>
                    <InputLabel htmlFor="volunteer_routing">Helpline Routing:</InputLabel>
                    <Select label="Helpline Routing"
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
                </div>

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.HELPLINE_FIELD,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS]) && (
                    <>
                        <InputLabel htmlFor="forced_caller_id">Forced Caller Id (Must be Twilio Verified):</InputLabel>
                        <TextField
                            id="forced_caller_id"
                            value={callHandlingData.forced_caller_id ?? ""}
                            onChange={handleChange}
                            fullWidth
                        />
                    </>
                )}

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_REDIRECT,
                    ]) && (
                    <div>
                        <InputLabel htmlFor="volunteers_redirect_id">Volunteers Redirect Id:</InputLabel>
                        <TextField
                            id="volunteers_redirect_id"
                            label="Volunteers Redirect Id"
                            value={callHandlingData.volunteers_redirect_id}
                            onChange={handleChange}
                            fullWidth
                        />
                    </div>
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
                            inputProps={{name: "gender_routing"}}
                            onChange={handleChange}
                        />
                    </>
                )}

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS
                    ]) && (
                    <>
                        <InputLabel id="call_strategy_label">Call Strategy:</InputLabel>
                        <Select labelId="call_strategy_label"
                                id="call_strategy"
                                variant="outlined"
                                value={callHandlingData.call_strategy}
                                inputProps={{name: "call_strategy"}}
                                onChange={handleChange}
                                fullWidth>
                            <MenuItem value={CALL_STRATEGY.LINEAR_LOOP_FOREVER}>Linear Loop Forever</MenuItem>
                            <MenuItem value={CALL_STRATEGY.LINEAR_CYCLE_ONCE_THEN_VOICEMAIL}>Linear Cycle Once, Then
                                Voicemail</MenuItem>
                            <MenuItem value={CALL_STRATEGY.RANDOM_LOOP_FOREVER}>Random Loop Forever</MenuItem>
                            <MenuItem value={CALL_STRATEGY.BLASTING_THEN_VOICEMAIL}>Blasting, Then Voicemail</MenuItem>
                            <MenuItem value={CALL_STRATEGY.RANDOM_LOOP_ONCE_THEN_VOICEMAIL}>Random Loop Once, Then
                                Voicemail</MenuItem>
                        </Select>
                    </>
                )}

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS
                    ]) && (
                    <>
                        <InputLabel htmlFor="volunteer_sms_notification">Inbound call SMS to Volunteer
                            Options:</InputLabel>
                        <Select id="volunteer_sms_notification"
                                variant="outlined"
                                value={callHandlingData.volunteer_sms_notification}
                                inputProps={{name: "volunteer_sms_notification"}}
                                onChange={handleChange}
                                fullWidth>
                            <MenuItem value="no_sms">No SMS</MenuItem>
                            <MenuItem value="send_sms">Send SMS to Volunteer</MenuItem>
                        </Select>
                    </>
                )}

                {shouldShowField("volunteer_routing",
                    [
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS
                    ]) && (
                    <>
                        <InputLabel htmlFor="sms_strategy">SMS Strategy:</InputLabel>
                        <Select id="sms_strategy"
                                variant="outlined"
                                value={callHandlingData.sms_strategy}
                                inputProps={{name: "sms_strategy"}}
                                onChange={handleChange}
                                fullWidth>
                            <MenuItem value={SMS_STRATEGY.RANDOM}>Random</MenuItem>
                            <MenuItem value={SMS_STRATEGY.BLAST}>Blast</MenuItem>
                        </Select>
                    </>
                )}

                {((shouldShowField("volunteer_routing", [VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS])
                        && shouldShowField("call_strategy", [
                            CALL_STRATEGY.RANDOM_LOOP_ONCE_THEN_VOICEMAIL,
                            CALL_STRATEGY.BLASTING_THEN_VOICEMAIL,
                            CALL_STRATEGY.LINEAR_CYCLE_ONCE_THEN_VOICEMAIL,
                        ]))
                    || (shouldShowField("volunteer_routing", [VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS])
                        && shouldShowField("sms_strategy", [
                            SMS_STRATEGY.BLAST,
                            SMS_STRATEGY.RANDOM
                        ]))) && (
                    <>
                        <InputLabel htmlFor="primary_contact">Primary Contact Number (typically the
                            Chair/Coordinator):</InputLabel>
                        <TextField
                            id="primary_contact"
                            value={callHandlingData.primary_contact ?? ""}
                            onChange={handleChange}
                            fullWidth
                        />
                    </>
                )}

                {(shouldShowField("volunteer_routing", [VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS])
                        && shouldShowField("call_strategy", [
                            CALL_STRATEGY.RANDOM_LOOP_ONCE_THEN_VOICEMAIL,
                            CALL_STRATEGY.BLASTING_THEN_VOICEMAIL,
                            CALL_STRATEGY.LINEAR_CYCLE_ONCE_THEN_VOICEMAIL,
                        ]))
                    && (
                        <>
                            <InputLabel htmlFor="primary_contact_email">Primary Contact Email (typically the
                                Chair/Coordinator)</InputLabel>
                            <TextField
                                id="primary_contact_email"
                                value={callHandlingData.primary_contact_email ?? ""}
                                onChange={handleChange}
                                fullWidth
                            />
                        </>
                    )}

                {shouldShowField("volunteer_routing", [
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS, VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS])
                    && (
                        <>
                            <InputLabel htmlFor="moh">Music On Hold (<a target="_blank"
                                                                        href="https://yap.bmlt.app/helpline/music-on-hold/">info</a>):</InputLabel>
                            <TextField
                                id="moh"
                                value={callHandlingData.moh ?? ""}
                                onChange={handleChange}
                                fullWidth
                            />
                        </>
                    )}

                {shouldShowField("volunteer_routing", [
                        VOLUNTEER_ROUTING_OPTIONS.HELPLINE_FIELD,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS,
                        VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS_AND_SMS,])
                    && (
                        <>
                            <InputLabel htmlFor="override_en_US_greeting">Recorded Greeting (URL to any MP3): (<Button
                                href={callHandlingData.override_en_US_greeting}>Play</Button>)</InputLabel>
                            <Typography variant="body2">This setting is not usable with <a
                                href="https://bmlt.app/yap/#configurationprecedence" target="_blank">Configuration
                                Precedence</a>, like the options under "Configure" button. If you want to use
                                configuration
                                overrides, use the setting "en_US_greeting" (or the equivalent language code you want to
                                set).
                            </Typography>
                            <TextField
                                id="override_en_US_greeting"
                                value={callHandlingData.override_en_US_greeting ?? ""}
                                onChange={handleChange}
                                fullWidth
                            />
                        </>
                    )}

                {(shouldShowField("volunteer_routing", [VOLUNTEER_ROUTING_OPTIONS.VOLUNTEERS])
                        && shouldShowField("call_strategy", [
                            CALL_STRATEGY.RANDOM_LOOP_ONCE_THEN_VOICEMAIL,
                            CALL_STRATEGY.BLASTING_THEN_VOICEMAIL,
                            CALL_STRATEGY.LINEAR_CYCLE_ONCE_THEN_VOICEMAIL,
                        ]))
                    && (
                        <>
                            <InputLabel htmlFor="override_en_US_voicemail_greeting">Voicemail Greeting (URL to any MP3): (<Button
                                href={callHandlingData.override_en_US_voicemail_greeting}>Play</Button>)</InputLabel>
                            <Typography variant="body2">This setting is not usable with <a
                                href="https://bmlt.app/yap/#configurationprecedence" target="_blank">Configuration
                                Precedence</a>, like the options under "Configure" button. If you want to use configuration
                                overrides, use the setting "en_US_voicemail_greeting" (or the equivalent language code you want
                                to set).
                            </Typography>
                            <TextField
                                id="override_en_US_voicemail_greeting"
                                value={callHandlingData.override_en_US_voicemail_greeting ?? ""}
                                onChange={handleChange}
                                fullWidth
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

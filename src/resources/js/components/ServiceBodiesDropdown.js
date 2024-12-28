import React, {useEffect, useState} from "react";
import {FormControl, InputLabel, MenuItem, Select} from "@mui/material";
import apiClient from "../services/api";

function ServiceBodiesDropdown(props) {
    const [loading, setLoading] = useState(false);
    const [list, setList] = useState([]);

    const getServiceBodies = async () => {
        setLoading(true)
        let response = await apiClient(`/api/v1/callHandling/routingEnabled`)
        let responseData = await response.data
        setList(responseData)
        setLoading(false)
    }

    useEffect(() => {
        getServiceBodies()
    }, [])

    return (
        !loading && list.length > 0 ?
            <FormControl fullWidth>
                <InputLabel id="demo-simple-select-label">Service Body</InputLabel>
                <Select
                    labelId="demo-simple-select-label"
                    id="demo-simple-select"
                    onChange={(e)=>{props.handleChange(e.target.value)}}
                    defaultValue={0}>
                    <MenuItem key={0} value={0}>-= Select a Service Body =-</MenuItem>
                    {list.map(item => (
                        <MenuItem key={item.id} value={item.id}>{item.name} ({item.id}) / {item.parent_name} ({item.parent_id})</MenuItem>
                    ))}
                </Select>
            </FormControl> : "Loading..."
    )
}

export default ServiceBodiesDropdown;

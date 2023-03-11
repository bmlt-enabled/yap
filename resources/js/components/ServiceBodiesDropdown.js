import React, {useEffect, useState} from "react";
import {FormControl, InputLabel, MenuItem, Select} from "@mui/material";

function ServiceBodiesDropdown() {
    const [loading, setLoading] = useState(false);
    const [list, setList] = useState([]);

    const getServiceBodies = async () => {
        setLoading(true)
        let response = await fetch(`${rootUrl}/api/v1/rootServer/servicebodies`)
        let responseData = await response.json()
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
                    // label="servicebody"
                    // onChange={handleChange}
                >
                    {list.map(item => (
                        <MenuItem value={item.id}>{item.name} ({item.id}) / {item.parent_name} ({item.parent_id})</MenuItem>
                    ))}
                </Select>
            </FormControl> : "Loading..."
    )
}

export default ServiceBodiesDropdown;

import React, {useEffect, useState} from "react";
import {Button, Table, TableBody, TableCell, TableContainer, TableHead, TableRow} from "@mui/material";

function ServiceBodies()
{
    const [loading, setLoading] = useState(false);
    const [list, setList] = useState([]);

    const getServiceBodies = async() => {
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
        <div>
            {list.length > 0 ?
            <TableContainer>
                <Table sx={{ minWidth: 650 }} size="small" aria-label="a dense table">
                    <TableHead>
                        <TableRow>
                            <TableCell>Service Bodies</TableCell>
                            <TableCell>Helpline</TableCell>
                            <TableCell>Action</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {list.map((item) => (
                            <TableRow
                                key={item.id}
                                sx={{ '&:last-child td, &:last-child th': { border: 0 } }}
                            >
                                <TableCell component="th" scope="row">
                                    {item.name} ({item.id})
                                </TableCell>
                                <TableCell>{item.helpline}</TableCell>
                                <TableCell>
                                    <Button variant="contained" size="small">Call Handling</Button>&nbsp;
                                    <Button variant="contained" size="small" color="success">Configure</Button>&nbsp;
                                    <Button variant="contained" size="small">Voicemail</Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer> : "Loading..."}
        </div>
    )
}

export default ServiceBodies;

import React, {useEffect, useState} from "react";
import {Button, Table, TableBody, TableCell, TableContainer, TableHead, TableRow} from "@mui/material";
import apiClient from "../services/api";
import {CallHandlingDialog} from "../dialogs/CallHandlingDialog";
import {useDialogs} from "@toolpad/core";

function ServiceBodies()
{
    const [loading, setLoading] = useState(false);
    const [list, setList] = useState([]);
    const [openDialogState, setOpenDialogState] = useState(false)
    const [serviceBodyId, setServiceBodyId] = useState();

    const getServiceBodies = async() => {
        setLoading(true)
        let response = await apiClient(`${rootUrl}/api/v1/rootServer/serviceBodies/user`)
        let responseData = await response.data
        setList(responseData)
        setLoading(false)
    }

    const openCallHandlingDialog = (serviceBodyId) => {
        setServiceBodyId(serviceBodyId)
        setOpenDialogState(true); // Set local state to open
    };

    const closeCallHandlingDialog = () => {
        setServiceBodyId(null)
        setOpenDialogState(false); // Set local state to close
    };

    useEffect(() => {
        getServiceBodies()
    }, [])

    return (
        <div>
            {list.length > 0 ?
            <TableContainer>
                <CallHandlingDialog open={openDialogState} onClose={closeCallHandlingDialog} serviceBodyId={serviceBodyId} />
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
                                sx={{ '&:last-child td, &:last-child th': { border: 0 } }}>
                                <TableCell component="th" scope="row">
                                    {item.name} ({item.id})
                                </TableCell>
                                <TableCell>{item.helpline}</TableCell>
                                <TableCell>
                                    <Button variant="contained" size="small" onClick={() => openCallHandlingDialog(item.id)}>Call Handling</Button>&nbsp;
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

import React, {useEffect, useState} from "react";
import {Button, Table, TableBody, TableCell, TableContainer, TableHead, TableRow} from "@mui/material";
import apiClient from "../services/api";
import {CallHandlingDialog} from "../dialogs/CallHandlingDialog";
import {ServiceBodyConfigurationDialog} from "../dialogs/ServiceBodyConfigurationDialog";
import {VoicemailDialog} from "../dialogs/VoicemailDialog";

function ServiceBodies()
{
    const [loading, setLoading] = useState(false);
    const [list, setList] = useState([]);
    const [openCallHandlingDialog, setOpenCallHandlingDialog] = useState(false);
    const [openConfigDialog, setOpenConfigDialog] = useState(false);
    const [openVoicemailDialog, setOpenVoicemailDialog] = useState(false);
    const [selectedServiceBody, setSelectedServiceBody] = useState(null);

    const getServiceBodies = async() => {
        setLoading(true)
        let response = await apiClient(`${rootUrl}/api/v1/rootServer/serviceBodies/user`)
        let responseData = await response.data
        setList(responseData)
        setLoading(false)
    }

    const handleCallHandlingClick = (serviceBody) => {
        setSelectedServiceBody(serviceBody);
        setOpenCallHandlingDialog(true);
    };

    const handleConfigClick = (serviceBody) => {
        setSelectedServiceBody(serviceBody);
        setOpenConfigDialog(true);
    };

    const handleVoicemailClick = (serviceBody) => {
        setSelectedServiceBody(serviceBody);
        setOpenVoicemailDialog(true);
    };

    const closeCallHandlingDialog = () => {
        setSelectedServiceBody(null);
        setOpenCallHandlingDialog(false);
    };

    const closeConfigDialog = () => {
        setSelectedServiceBody(null);
        setOpenConfigDialog(false);
    };

    const closeVoicemailDialog = () => {
        setSelectedServiceBody(null);
        setOpenVoicemailDialog(false);
    };

    useEffect(() => {
        getServiceBodies()
    }, [])

    return (
        <div>
            {list.length > 0 ?
            <TableContainer>
                <CallHandlingDialog 
                    open={openCallHandlingDialog} 
                    onClose={closeCallHandlingDialog} 
                    serviceBodyId={selectedServiceBody?.id} 
                />
                <ServiceBodyConfigurationDialog
                    open={openConfigDialog}
                    onClose={closeConfigDialog}
                    serviceBodyId={selectedServiceBody?.id}
                    serviceBodyName={selectedServiceBody?.name}
                />
                <VoicemailDialog
                    open={openVoicemailDialog}
                    onClose={closeVoicemailDialog}
                    serviceBodyId={selectedServiceBody?.id}
                    serviceBodyName={selectedServiceBody?.name}
                />
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
                                    <Button 
                                        variant="contained" 
                                        size="small" 
                                        onClick={() => handleCallHandlingClick(item)}
                                    >
                                        Call Handling
                                    </Button>&nbsp;
                                    <Button 
                                        variant="contained" 
                                        size="small" 
                                        color="success"
                                        onClick={() => handleConfigClick(item)}
                                    >
                                        Configure
                                    </Button>&nbsp;
                                    <Button 
                                        variant="contained" 
                                        size="small" 
                                        color="warning"
                                        onClick={() => handleVoicemailClick(item)}
                                    >
                                        Voicemail
                                    </Button>
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

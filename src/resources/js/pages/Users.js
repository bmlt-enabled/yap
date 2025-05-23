import React, {useEffect, useState} from "react";
import {Button, Card, Table, TableBody, TableCell, TableContainer, TableHead, TableRow} from "@mui/material";
import apiClient from "../services/api";

function Users() {
    const [loading, setLoading] = useState(false);
    const [list, setList] = useState([]);

    const getUsers = async() => {
        setLoading(true)
        let response = await apiClient(`/api/v1/users`)
        let responseData = await response.data
        setList(responseData)
        setLoading(false)
    }

    useEffect(() => {
        getUsers()
    }, [])

    return (
        !loading && list.length > 0 ?
            <TableContainer>
                <Table sx={{ minWidth: 650 }} size="small" aria-label="a dense table">
                    <TableHead>
                        <TableRow>
                            <TableCell>Username</TableCell>
                            <TableCell>Name</TableCell>
                            <TableCell>Service Bodies</TableCell>
                            <TableCell>Permissions</TableCell>
                            <TableCell>Admin</TableCell>
                            <TableCell>Date Created</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {list.map((item) => (
                            <TableRow
                                key={item.username}
                                sx={{ '&:last-child td, &:last-child th': { border: 0 } }}>
                                <TableCell component="th" scope="row">
                                    {item.username}
                                </TableCell>
                                <TableCell>{item.name}</TableCell>
                                <TableCell>{item.service_bodies}</TableCell>
                                <TableCell>{item.permissions}</TableCell>
                                <TableCell>{item.is_admin}</TableCell>
                                <TableCell>{item.created_on}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer> : "Loading..."
    )
}

export default Users;

import apiClient from "../services/api";
import {useEffect, useState} from "react";
import {Card, CardContent} from "@mui/material";

function Dashboard() {
    const [username, setUsername] = useState('');

    const getUser = async () => {
        apiClient.get('/api/v1/user', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        }).then((response) => {
            setUsername(response.data.username)
        }).catch((error) => {
            console.error('Error fetching user data:', error);
        })
    };

    useEffect(() => {
        getUser()
    }, [])

    return (
        <Card className="card">
            <CardContent>
                <h1>Home</h1>
                <p>Username: { username }</p>
                Api Docs: <a href={'/api/v1/documentation'}>Docs</a>
            </CardContent>
        </Card>
    )
}

export default Dashboard;

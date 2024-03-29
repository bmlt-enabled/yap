import {useState} from 'react';
import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Toolbar from '@mui/material/Toolbar';
import Container from '@mui/material/Container';
import Button from '@mui/material/Button';
import {Link, useNavigate} from "react-router-dom";
import {Divider, Drawer, IconButton, ListItemButton, Typography} from "@mui/material";
import {Menu, Close} from "@mui/icons-material";

function MenuBar() {
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);


    const toggleDrawer = (open) => (event) => {
        if (event.type === 'keydown' && (event.key === 'Tab' || event.key === 'Shift')) {
            return;
        }

        setIsDrawerOpen(open)
    };

    return (
        <AppBar position="static">
            <Container maxWidth="xl">
                <Toolbar disableGutters>
                    <IconButton
                        edge="start"
                        color="inherit"
                        aria-label="menu"
                        sx={{ mr: 2, display: { xs: 'block', sm: 'none'}}}
                        onClick={() => setIsDrawerOpen(true)}>
                        <Menu />
                    </IconButton>
                    <Drawer open={isDrawerOpen} onClose={() => setIsDrawerOpen(false)}>
                        <Box sx={{
                            p: 2,
                            height: 1,
                        }}>
                            <IconButton sx={{mb: 2, color: "white"}} onClick={toggleDrawer(false)}>
                                <Close />
                            </IconButton>
                            <Divider sx={{mb: 2}} />
                            <Box sx={{mb: 2}}>
                                <Link to="/" style={{textDecoration:'none'}}>
                                    <ListItemButton
                                        onClick={toggleDrawer(false)}>Home</ListItemButton>
                                </Link>
                            </Box>
                        </Box>
                    </Drawer>
                    <Box>
                        <Typography>Yap</Typography>
                    </Box>
                    <Box sx={{ flexGrow: 1, display: { xs: 'none', sm: 'flex' } }}>
                        <Link to={`${baseUrl}/`} style={{textDecoration:'none'}}>
                            <Button color="secondary" variant="text">Home</Button>
                        </Link>
                        <Link to={`${baseUrl}/reports`} style={{textDecoration:'none'}}>
                            <Button color="secondary" variant="text">Reports</Button>
                        </Link>
                        <Link to={`${baseUrl}/serviceBodies`} style={{textDecoration:'none'}}>
                            <Button color="secondary" variant="text">Service Bodies</Button>
                        </Link>
                        <Link to={`${baseUrl}/schedules`} style={{textDecoration:'none'}}>
                            <Button color="secondary" variant="text">Schedules</Button>
                        </Link>
                        <Link to={`${baseUrl}/volunteers`} style={{textDecoration:'none'}}>
                            <Button color="secondary" variant="text">Volunteers</Button>
                        </Link>
                        <Link to={`${baseUrl}/groups`} style={{textDecoration:'none'}}>
                            <Button color="secondary" variant="text">Groups</Button>
                        </Link>
                        <Link to={`${baseUrl}/users`} style={{textDecoration:'none'}}>
                            <Button color="secondary" variant="text">Users</Button>
                        </Link>
                    </Box>
                </Toolbar>
            </Container>
        </AppBar>
    );
}
export default MenuBar;

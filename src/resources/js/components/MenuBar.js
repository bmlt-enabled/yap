import {useState} from 'react';
import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Toolbar from '@mui/material/Toolbar';
import Container from '@mui/material/Container';
import Button from '@mui/material/Button';
import {Link, useNavigate} from "react-router-dom";
import {Divider, Drawer, IconButton, ListItemButton, Typography} from "@mui/material";
import {Menu, Close} from "@mui/icons-material";
import { styled } from '@mui/material/styles';
import MenuBarItem from "./MenuBarItem";



function MenuBar({ currentRoute }) {
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
                        <MenuBarItem currentRoute={currentRoute} pageName={"Home"} url={"/"} />
                        <MenuBarItem currentRoute={currentRoute} pageName={"Report"} url={"/reports"} />
                        <MenuBarItem currentRoute={currentRoute} pageName={"Service Bodies"} url={"/serviceBodies"} />
                        <MenuBarItem currentRoute={currentRoute} pageName={"Schedules"} url={"/schedules"} />
                        <MenuBarItem currentRoute={currentRoute} pageName={"Settings"} url={"/settings"} />
                        <MenuBarItem currentRoute={currentRoute} pageName={"Volunteers"} url={"/volunteers"} />
                        <MenuBarItem currentRoute={currentRoute} pageName={"Groups"} url={"/groups"} />
                        <MenuBarItem currentRoute={currentRoute} pageName={"Users"} url={"/users"} />
                    </Box>
                </Toolbar>
            </Container>
        </AppBar>
    );
}
export default MenuBar;

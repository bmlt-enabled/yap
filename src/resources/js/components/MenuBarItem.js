import {Link} from "react-router-dom";
import {styled} from "@mui/material/styles";
import Button from "@mui/material/Button";

const StyledButton = styled(Button)(({ theme }) => ({
    // Default styles for all buttons
    color: theme.palette.secondary.contrastText, // Ensure text color contrasts with background
    '&:hover': {
        backgroundColor: theme.palette.secondary.dark, // Darken on hover
    },

    // Contained style
    '&.MuiButton-contained': {
        backgroundColor: theme.palette.secondary.main,
        '&:hover': {
            backgroundColor: theme.palette.secondary.dark, // Darken contained button on hover
        },
    },

    // Styles when active based on currentRoute
    '&.Mui-selected': {
        backgroundColor: theme.palette.primary.main,
        color: theme.palette.primary.contrastText,
        '&:hover': {
            backgroundColor: theme.palette.primary.dark,
        },
    },
}));

function MenuBarItem({ currentRoute, url, pageName }) {
    return (
        <Link to={`${baseUrl}${url}`} style={{textDecoration:'none'}}>
            <StyledButton
                color="secondary"
                variant={currentRoute === `${baseUrl}${url}` ? 'contained' : 'text'}
                className={currentRoute === `${baseUrl}${url}` ? 'Mui-selected' : ''}
            >
                {pageName}
            </StyledButton>
        </Link>
    )
}

export default MenuBarItem;

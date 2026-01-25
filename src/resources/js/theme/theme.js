import { createTheme } from '@mui/material/styles';

// Shared component overrides
const componentOverrides = {
    MuiCard: {
        styleOverrides: {
            root: {
                borderRadius: 12,
                transition: 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out',
                '&:hover': {
                    transform: 'translateY(-4px)',
                },
            },
        },
    },
    MuiButton: {
        styleOverrides: {
            root: {
                borderRadius: 8,
                textTransform: 'none',
                fontWeight: 600,
            },
        },
    },
    MuiDialog: {
        styleOverrides: {
            paper: {
                borderRadius: 12,
            },
        },
    },
    MuiTableRow: {
        styleOverrides: {
            root: {
                '&:hover': {
                    backgroundColor: 'rgba(41, 98, 255, 0.04)',
                },
            },
        },
    },
    MuiTextField: {
        styleOverrides: {
            root: {
                '& .MuiOutlinedInput-root': {
                    borderRadius: 8,
                },
            },
        },
    },
    MuiSelect: {
        styleOverrides: {
            root: {
                borderRadius: 8,
            },
        },
    },
    MuiPaper: {
        styleOverrides: {
            rounded: {
                borderRadius: 12,
            },
        },
    },
    MuiChip: {
        styleOverrides: {
            root: {
                fontWeight: 500,
            },
        },
    },
};

// Create theme with CSS variables and color schemes for light/dark mode switching
const theme = createTheme({
    cssVariables: {
        colorSchemeSelector: 'data-toolpad-color-scheme',
    },
    colorSchemes: {
        light: {
            palette: {
                primary: {
                    main: '#2962ff',
                },
                secondary: {
                    main: '#00b0ff',
                },
                background: {
                    default: '#f4f6f8',
                    paper: '#ffffff',
                },
            },
        },
        dark: {
            palette: {
                primary: {
                    main: '#82b1ff',
                },
                secondary: {
                    main: '#40c4ff',
                },
                background: {
                    default: '#121212',
                    paper: '#1e1e1e',
                },
            },
        },
    },
    typography: {
        fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
    },
    shape: {
        borderRadius: 8,
    },
    components: componentOverrides,
});

export default theme;

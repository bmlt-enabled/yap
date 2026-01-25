import { createTheme } from '@mui/material/styles';

// Shared component overrides for both light and dark modes
const componentOverrides = {
    MuiCard: {
        styleOverrides: {
            root: {
                borderRadius: 12,
                boxShadow: '0 2px 8px rgba(0, 0, 0, 0.08)',
                transition: 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out',
                '&:hover': {
                    transform: 'translateY(-4px)',
                    boxShadow: '0 8px 24px rgba(0, 0, 0, 0.12)',
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
                boxShadow: '0 24px 48px rgba(0, 0, 0, 0.2)',
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

// Light theme
const lightTheme = createTheme({
    palette: {
        mode: 'light',
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
    typography: {
        fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
    },
    shape: {
        borderRadius: 8,
    },
    components: {
        ...componentOverrides,
        MuiTableHead: {
            styleOverrides: {
                root: {
                    '& .MuiTableCell-head': {
                        backgroundColor: '#f8f9fa',
                        fontWeight: 600,
                        color: '#495057',
                    },
                },
            },
        },
        MuiTableCell: {
            styleOverrides: {
                root: {
                    borderColor: '#e9ecef',
                },
            },
        },
    },
});

// Dark theme
const darkTheme = createTheme({
    palette: {
        mode: 'dark',
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
    typography: {
        fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
    },
    shape: {
        borderRadius: 8,
    },
    components: {
        ...componentOverrides,
        MuiTableHead: {
            styleOverrides: {
                root: {
                    '& .MuiTableCell-head': {
                        backgroundColor: '#2d2d2d',
                        fontWeight: 600,
                        color: '#e0e0e0',
                    },
                },
            },
        },
        MuiTableCell: {
            styleOverrides: {
                root: {
                    borderColor: '#424242',
                },
            },
        },
        MuiCard: {
            styleOverrides: {
                root: {
                    borderRadius: 12,
                    boxShadow: '0 2px 8px rgba(0, 0, 0, 0.3)',
                    transition: 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out',
                    '&:hover': {
                        transform: 'translateY(-4px)',
                        boxShadow: '0 8px 24px rgba(0, 0, 0, 0.4)',
                    },
                },
            },
        },
    },
});

// Export theme object for Toolpad with both light and dark schemes
const theme = {
    light: lightTheme,
    dark: darkTheme,
};

export default theme;

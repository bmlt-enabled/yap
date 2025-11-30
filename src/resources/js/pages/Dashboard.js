import apiClient from "../services/api";
import {useEffect, useState} from "react";
import {
    Card,
    CardContent,
    Typography,
    Box,
    Grid,
    Button,
    Chip,
    Avatar,
    Paper,
    Stack,
    Divider
} from "@mui/material";
import {
    Description as DocumentationIcon,
    Code as ApiIcon,
    AccountCircle as UserIcon,
    CheckCircle as CheckIcon,
    Info as InfoIcon,
    Warning as WarningIcon,
    Error as ErrorIcon
} from "@mui/icons-material";

function Dashboard() {
    const [name, setName] = useState('');
    const [version, setVersion] = useState('');
    const [versionStatus, setVersionStatus] = useState('unknown');
    const [versionMessage, setVersionMessage] = useState('');
    const [latestVersion, setLatestVersion] = useState('');
    const [systemStatus, setSystemStatus] = useState('checking');
    const [systemMessage, setSystemMessage] = useState('Checking system status...');

    const getUser = async () => {
        apiClient.get('/api/v1/user', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        }).then((response) => {
            setName(response.data.name)
        }).catch((error) => {
            console.error('Error fetching user data:', error);
        })
    };

    const getVersion = async () => {
        try {
            const response = await apiClient.get('/api/v1/version');
            if (response.data) {
                if (response.data.version) {
                    setVersion(response.data.version);
                }
                if (response.data.status) {
                    setVersionStatus(response.data.status);
                }
                if (response.data.message) {
                    setVersionMessage(response.data.message);
                }
                if (response.data.latest_version) {
                    setLatestVersion(response.data.latest_version);
                }
            }
        } catch (error) {
            console.error('Error fetching version:', error);
            setVersion('5.0.0'); // Fallback version
            setVersionStatus('unknown');
        }
    };

    const getUpgradeAdvisorStatus = async () => {
        try {
            const response = await apiClient.get('/api/v1/upgrade');
            if (response.data) {
                // Check for error messages (status: false)
                if (response.data.status === false && response.data.message) {
                    setSystemStatus('error');
                    setSystemMessage(response.data.message);
                }
                // Check for warnings
                else if (response.data.warnings) {
                    setSystemStatus('warning');
                    setSystemMessage(response.data.warnings);
                }
                // All good
                else if (response.data.status === true) {
                    setSystemStatus('healthy');
                    setSystemMessage('All systems operational');
                }
            }
        } catch (error) {
            console.error('Error fetching upgrade advisor status:', error);
            setSystemStatus('error');
            setSystemMessage('Unable to check system status');
        }
    };

    useEffect(() => {
        getUser();
        getVersion();
        getUpgradeAdvisorStatus();
    }, [])

    return (
        <Box sx={{ p: 3 }}>
            {/* Welcome Header */}
            <Paper
                elevation={0}
                sx={{
                    p: 4,
                    mb: 4,
                    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    color: 'white',
                    borderRadius: 2
                }}
            >
                <Stack direction="row" spacing={2} alignItems="center">
                    <Avatar sx={{ width: 64, height: 64, bgcolor: 'rgba(255,255,255,0.2)' }}>
                        <UserIcon sx={{ fontSize: 40 }} />
                    </Avatar>
                    <Box>
                        <Typography variant="h4" fontWeight="bold" gutterBottom>
                            Welcome back, {name || 'User'}!
                        </Typography>
                        <Typography variant="body1" sx={{ opacity: 0.9 }}>
                            Manage your Yap Phone System configuration and view reports
                        </Typography>
                    </Box>
                </Stack>
            </Paper>

            {/* Info Cards - 2x2 Grid */}
            <Grid container spacing={3}>
                {/* Version Card */}
                <Grid item xs={12} md={6}>
                    <Card
                        sx={{
                            height: '100%',
                            transition: 'transform 0.2s, box-shadow 0.2s',
                            '&:hover': {
                                transform: 'translateY(-4px)',
                                boxShadow: 4
                            }
                        }}
                    >
                        <CardContent>
                            <Stack spacing={2}>
                                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                                    <InfoIcon color="primary" sx={{ fontSize: 40 }} />
                                    {versionStatus === 'current' && (
                                        <Chip
                                            label="Latest"
                                            color="success"
                                            size="small"
                                            icon={<CheckIcon />}
                                        />
                                    )}
                                    {versionStatus === 'pre-release' && (
                                        <Chip
                                            label="Pre-Release"
                                            color="warning"
                                            size="small"
                                            icon={<WarningIcon />}
                                        />
                                    )}
                                    {versionStatus === 'update-available' && (
                                        <Chip
                                            label="Update Available"
                                            color="info"
                                            size="small"
                                            icon={<InfoIcon />}
                                        />
                                    )}
                                </Box>
                                <Typography variant="h6" fontWeight="600">
                                    System Version
                                </Typography>
                                <Typography variant="h3" fontWeight="bold" color="primary">
                                    v{version}
                                </Typography>
                                <Typography variant="body2" color="text.secondary">
                                    {versionMessage || 'Loading version information...'}
                                </Typography>
                                {latestVersion && versionStatus === 'update-available' && (
                                    <Button
                                        variant="outlined"
                                        size="small"
                                        href="https://github.com/bmlt-enabled/yap/releases/latest"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Download v{latestVersion}
                                    </Button>
                                )}
                            </Stack>
                        </CardContent>
                    </Card>
                </Grid>

                {/* System Status Card */}
                <Grid item xs={12} md={6}>
                    <Card
                        sx={{
                            height: '100%',
                            transition: 'transform 0.2s, box-shadow 0.2s',
                            '&:hover': {
                                transform: 'translateY(-4px)',
                                boxShadow: 4
                            }
                        }}
                    >
                        <CardContent>
                            <Stack spacing={2}>
                                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                                    {systemStatus === 'healthy' && <CheckIcon color="success" sx={{ fontSize: 40 }} />}
                                    {systemStatus === 'warning' && <WarningIcon color="warning" sx={{ fontSize: 40 }} />}
                                    {systemStatus === 'error' && <ErrorIcon color="error" sx={{ fontSize: 40 }} />}
                                    {systemStatus === 'checking' && <InfoIcon color="action" sx={{ fontSize: 40 }} />}

                                    {systemStatus === 'healthy' && (
                                        <Chip
                                            label="Healthy"
                                            color="success"
                                            size="small"
                                            icon={<CheckIcon />}
                                        />
                                    )}
                                    {systemStatus === 'warning' && (
                                        <Chip
                                            label="Warning"
                                            color="warning"
                                            size="small"
                                            icon={<WarningIcon />}
                                        />
                                    )}
                                    {systemStatus === 'error' && (
                                        <Chip
                                            label="Error"
                                            color="error"
                                            size="small"
                                            icon={<ErrorIcon />}
                                        />
                                    )}
                                </Box>
                                <Typography variant="h6" fontWeight="600">
                                    System Status
                                </Typography>
                                <Typography
                                    variant="h6"
                                    fontWeight="bold"
                                    color={
                                        systemStatus === 'healthy' ? 'success.main' :
                                        systemStatus === 'warning' ? 'warning.main' :
                                        systemStatus === 'error' ? 'error.main' : 'text.secondary'
                                    }
                                >
                                    {systemStatus === 'healthy' ? 'All Systems Operational' :
                                     systemStatus === 'checking' ? 'Checking...' : 'Issues Detected'}
                                </Typography>
                                <Typography variant="body2" color="text.secondary">
                                    {systemMessage}
                                </Typography>
                            </Stack>
                        </CardContent>
                    </Card>
                </Grid>

                {/* Documentation Card */}
                <Grid item xs={12} md={6}>
                    <Card
                        sx={{
                            height: '100%',
                            transition: 'transform 0.2s, box-shadow 0.2s',
                            '&:hover': {
                                transform: 'translateY(-4px)',
                                boxShadow: 4
                            }
                        }}
                    >
                        <CardContent>
                            <Stack spacing={2}>
                                <DocumentationIcon color="primary" sx={{ fontSize: 40 }} />
                                <Typography variant="h6" fontWeight="600">
                                    Documentation
                                </Typography>
                                <Typography variant="body2" color="text.secondary">
                                    Learn how to configure and use Yap for your organization
                                </Typography>
                                <Button
                                    variant="contained"
                                    href="https://yap.bmlt.app"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    startIcon={<DocumentationIcon />}
                                    fullWidth
                                >
                                    View Documentation
                                </Button>
                            </Stack>
                        </CardContent>
                    </Card>
                </Grid>

                {/* API Documentation Card */}
                <Grid item xs={12} md={6}>
                    <Card
                        sx={{
                            height: '100%',
                            transition: 'transform 0.2s, box-shadow 0.2s',
                            '&:hover': {
                                transform: 'translateY(-4px)',
                                boxShadow: 4
                            }
                        }}
                    >
                        <CardContent>
                            <Stack spacing={2}>
                                <ApiIcon color="primary" sx={{ fontSize: 40 }} />
                                <Typography variant="h6" fontWeight="600">
                                    API Documentation
                                </Typography>
                                <Typography variant="body2" color="text.secondary">
                                    Explore the REST API endpoints and integration options
                                </Typography>
                                <Button
                                    variant="outlined"
                                    href="/api/v1/documentation"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    startIcon={<ApiIcon />}
                                    fullWidth
                                >
                                    API Reference
                                </Button>
                            </Stack>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            {/* Quick Start Guide */}
            <Card sx={{ mt: 4 }}>
                <CardContent>
                    <Typography variant="h5" fontWeight="600" gutterBottom>
                        Quick Start Guide
                    </Typography>
                    <Divider sx={{ my: 2 }} />
                    <Grid container spacing={2}>
                        <Grid item xs={12} md={6}>
                            <Typography variant="h6" color="primary" gutterBottom>
                                Getting Started
                            </Typography>
                            <Stack spacing={1}>
                                <Typography variant="body2">
                                    • Configure your service bodies and volunteers
                                </Typography>
                                <Typography variant="body2">
                                    • Set up call handling preferences
                                </Typography>
                                <Typography variant="body2">
                                    • Configure SMS messaging options
                                </Typography>
                                <Typography variant="body2">
                                    • Test your configuration
                                </Typography>
                            </Stack>
                        </Grid>
                        <Grid item xs={12} md={6}>
                            <Typography variant="h6" color="primary" gutterBottom>
                                Need Help?
                            </Typography>
                            <Stack spacing={1}>
                                <Typography variant="body2">
                                    • Check the <a href="https://yap.bmlt.app" target="_blank" rel="noopener noreferrer">documentation</a> for detailed guides
                                </Typography>
                                <Typography variant="body2">
                                    • Review the API docs for integration details
                                </Typography>
                                <Typography variant="body2">
                                    • Contact your system administrator for assistance
                                </Typography>
                            </Stack>
                        </Grid>
                    </Grid>
                </CardContent>
            </Card>
        </Box>
    )
}

export default Dashboard;

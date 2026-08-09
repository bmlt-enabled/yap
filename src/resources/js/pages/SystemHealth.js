import { useCallback, useEffect, useState } from "react";
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    CircularProgress,
    Stack,
    Typography,
} from "@mui/material";
import {
    CheckCircle as CheckIcon,
    Error as ErrorIcon,
    Refresh as RefreshIcon,
    Warning as WarningIcon,
} from "@mui/icons-material";
import apiClient from "../services/api";
import UpgradeChecksList from "../components/UpgradeChecksList";
import { useLocalization } from "../contexts/LocalizationContext";

function deriveStatus(responseData, checks) {
    const hasFail = checks.some((check) => check.status === 'fail');
    const hasWarn = checks.some((check) => check.status === 'warn');

    if (responseData?.status === false || hasFail) {
        return 'error';
    }
    if (responseData?.warnings || hasWarn) {
        return 'warning';
    }
    if (responseData?.status === true) {
        return 'healthy';
    }
    return 'unknown';
}

export default function SystemHealth() {
    const { getWord } = useLocalization();
    const [loading, setLoading] = useState(true);
    const [systemStatus, setSystemStatus] = useState('checking');
    const [systemMessage, setSystemMessage] = useState('');
    const [systemChecks, setSystemChecks] = useState([]);

    const loadUpgradeAdvisor = useCallback(async () => {
        setLoading(true);
        try {
            const response = await apiClient.get('/api/v1/upgrade');
            if (response.data) {
                const checks = Array.isArray(response.data.checks) ? response.data.checks : [];
                setSystemChecks(checks);
                setSystemStatus(deriveStatus(response.data, checks));
                setSystemMessage(
                    response.data.warnings
                    || response.data.message
                    || ''
                );
            }
        } catch (error) {
            console.error('Error fetching upgrade advisor status:', error);
            setSystemStatus('error');
            setSystemMessage('');
            setSystemChecks([]);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadUpgradeAdvisor();
    }, [loadUpgradeAdvisor]);

    const statusIcon = {
        healthy: <CheckIcon color="success" />,
        warning: <WarningIcon color="warning" />,
        error: <ErrorIcon color="error" />,
    }[systemStatus];

    const statusChip = {
        healthy: { label: getWord('healthy') || 'Healthy', color: 'success' },
        warning: { label: getWord('warning') || 'Warning', color: 'warning' },
        error: { label: getWord('error') || 'Error', color: 'error' },
    }[systemStatus];

    return (
        <Box sx={{ p: 3 }}>
            <Stack direction="row" justifyContent="space-between" alignItems="flex-start" sx={{ mb: 3 }}>
                <Box>
                    <Typography variant="h4" fontWeight="bold" gutterBottom>
                        {getWord('system_health') || 'System Health'}
                    </Typography>
                    <Typography variant="body1" color="text.secondary">
                        {getWord('system_health_description')
                            || 'Upgrade advisor checks for configuration, database, Twilio, and PHP requirements.'}
                    </Typography>
                </Box>
                <Button
                    variant="outlined"
                    startIcon={loading ? <CircularProgress size={16} /> : <RefreshIcon />}
                    onClick={loadUpgradeAdvisor}
                    disabled={loading}
                >
                    {getWord('refresh') || 'Refresh'}
                </Button>
            </Stack>

            <Card sx={{ mb: 3 }}>
                <CardContent>
                    <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 2 }}>
                        {statusIcon}
                        <Typography variant="h6" fontWeight="600">
                            {getWord('upgrade_advisor') || 'Upgrade Advisor'}
                        </Typography>
                        {statusChip && (
                            <Chip label={statusChip.label} color={statusChip.color} size="small" />
                        )}
                    </Stack>

                    {systemMessage && (
                        <Alert
                            severity={systemStatus === 'healthy' ? 'success' : systemStatus === 'warning' ? 'warning' : 'error'}
                            sx={{ mb: 2 }}
                        >
                            {systemMessage}
                        </Alert>
                    )}

                    {loading && systemChecks.length === 0 ? (
                        <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
                            <CircularProgress />
                        </Box>
                    ) : (
                        <UpgradeChecksList checks={systemChecks} showStatusChip />
                    )}
                </CardContent>
            </Card>

            <Typography variant="body2" color="text.secondary">
                {getWord('upgrade_advisor_api_hint') || 'You can also open'}{' '}
                <a href="/api/v1/upgrade" target="_blank" rel="noopener noreferrer">
                    /api/v1/upgrade
                </a>{' '}
                {getWord('upgrade_advisor_api_hint_suffix') || 'in a browser for the raw JSON response (no login required).'}
            </Typography>
        </Box>
    );
}

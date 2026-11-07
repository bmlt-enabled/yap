import { Fragment, useCallback, useEffect, useState } from "react";
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    CircularProgress,
    Collapse,
    IconButton,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
} from "@mui/material";
import {
    CheckCircle as CheckIcon,
    Error as ErrorIcon,
    HealthAndSafety as HealthAndSafetyIcon,
    KeyboardArrowDown as ExpandIcon,
    KeyboardArrowUp as CollapseIcon,
} from "@mui/icons-material";
import { Link as RouterLink } from "react-router-dom";
import apiClient from "../services/api";
import { useLocalization } from "../contexts/LocalizationContext";

function formatTime(iso) {
    if (!iso) {
        return '';
    }
    const date = new Date(iso);
    return Number.isNaN(date.getTime()) ? iso : date.toLocaleString();
}

function FailureRow({ failure, getWord }) {
    const [open, setOpen] = useState(false);

    return (
        <Fragment>
            <TableRow hover sx={{ '& > *': { borderBottom: 'unset' } }}>
                <TableCell padding="checkbox">
                    <IconButton
                        size="small"
                        aria-label={getWord('webhook_failures_call_events')}
                        onClick={() => setOpen(!open)}
                    >
                        {open ? <CollapseIcon /> : <ExpandIcon />}
                    </IconButton>
                </TableCell>
                <TableCell sx={{ whiteSpace: 'nowrap' }}>{formatTime(failure.created_at)}</TableCell>
                <TableCell>
                    <Typography variant="body2" fontFamily="monospace">
                        {failure.method} {failure.route}
                    </Typography>
                </TableCell>
                <TableCell>
                    <Chip label={failure.status} color="error" size="small" />
                </TableCell>
                <TableCell>
                    <Typography variant="body2" fontFamily="monospace" sx={{ wordBreak: 'break-all' }}>
                        {failure.callsid || '—'}
                    </Typography>
                </TableCell>
                <TableCell>
                    {failure.exception_class && (
                        <Typography variant="body2" fontWeight="600" sx={{ wordBreak: 'break-word' }}>
                            {failure.exception_class}
                        </Typography>
                    )}
                    <Typography variant="body2" color="text.secondary" sx={{ wordBreak: 'break-word' }}>
                        {failure.message}
                    </Typography>
                </TableCell>
            </TableRow>
            <TableRow>
                <TableCell sx={{ py: 0 }} colSpan={6}>
                    <Collapse in={open} timeout="auto" unmountOnExit>
                        <Box sx={{ py: 2, pl: 6 }}>
                            {failure.location && (
                                <Typography variant="body2" sx={{ mb: 1 }}>
                                    {getWord('webhook_failures_location')}:{' '}
                                    <Box component="span" fontFamily="monospace">{failure.location}</Box>
                                </Typography>
                            )}
                            <Typography variant="subtitle2" gutterBottom>
                                {getWord('webhook_failures_call_events')}
                            </Typography>
                            {failure.events && failure.events.length > 0 ? (
                                <Table size="small">
                                    <TableBody>
                                        {failure.events.map((event, index) => (
                                            <TableRow key={`${event.event_id}-${event.event_time}-${index}`}>
                                                <TableCell sx={{ whiteSpace: 'nowrap' }}>{formatTime(event.event_time)}</TableCell>
                                                <TableCell>{event.event_name || event.event_id}</TableCell>
                                                <TableCell>{event.service_body_id ?? ''}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <Typography variant="body2" color="text.secondary">
                                    {getWord('webhook_failures_no_call_events')}
                                </Typography>
                            )}
                        </Box>
                    </Collapse>
                </TableCell>
            </TableRow>
        </Fragment>
    );
}

/**
 * Twilio webhook 5xx history (bmlt-enabled/yap#1686). The API is top-level
 * admin only, so the card renders nothing for anyone else.
 *
 * compact: counts and the last failure, with a link to System Health
 * (Dashboard). The link has its own label: the Dashboard already has a
 * "View System Health" link, and two links with one name are ambiguous. Otherwise the full list with each call's events.
 */
export default function WebhookFailuresCard({ compact = false, refreshKey = 0 }) {
    const { getWord } = useLocalization();
    const [loading, setLoading] = useState(true);
    const [forbidden, setForbidden] = useState(false);
    const [loadFailed, setLoadFailed] = useState(false);
    const [summary, setSummary] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const response = await apiClient.get('/api/v1/webhookFailures');
            setSummary(response.data);
            setLoadFailed(false);
        } catch (error) {
            if (error?.response?.status === 403) {
                setForbidden(true);
            } else {
                console.error('Error fetching webhook failures:', error);
                setLoadFailed(true);
            }
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load, refreshKey]);

    if (forbidden) {
        return null;
    }

    const last24 = summary?.last_24_hours ?? 0;
    const last7 = summary?.last_7_days ?? 0;
    const failures = Array.isArray(summary?.failures) ? summary.failures : [];
    const retention = (getWord('webhook_failures_retention') || '').replace('%d', summary?.retention_days ?? 30);

    const header = (
        <Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap" useFlexGap sx={{ mb: 2 }}>
            {loading && !summary ? (
                <CircularProgress size={20} />
            ) : last7 > 0 ? (
                <ErrorIcon color="error" />
            ) : (
                <CheckIcon color="success" />
            )}
            <Typography variant="h6" fontWeight="600">
                {getWord('webhook_failures')}
            </Typography>
            {summary && (
                <>
                    <Chip
                        size="small"
                        color={last24 > 0 ? 'error' : 'default'}
                        label={`${getWord('webhook_failures_last_24_hours')}: ${last24}`}
                    />
                    <Chip
                        size="small"
                        color={last7 > 0 ? 'warning' : 'default'}
                        label={`${getWord('webhook_failures_last_7_days')}: ${last7}`}
                    />
                </>
            )}
        </Stack>
    );

    if (compact) {
        return (
            <Card>
                <CardContent>
                    {header}
                    {loadFailed && <Alert severity="error">{getWord('unable_to_check_status')}</Alert>}
                    {summary && (
                        <Typography variant="body2" color="text.secondary">
                            {summary.last_failure_at
                                ? `${getWord('webhook_failures_last_failure')}: ${formatTime(summary.last_failure_at)}`
                                : getWord('webhook_failures_none')}
                        </Typography>
                    )}
                    <Button
                        component={RouterLink}
                        to="/systemHealth"
                        variant="outlined"
                        size="small"
                        startIcon={<HealthAndSafetyIcon />}
                        sx={{ mt: 2 }}
                    >
                        {getWord('webhook_failures_view')}
                    </Button>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card sx={{ mb: 3 }}>
            <CardContent>
                {header}
                <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                    {getWord('webhook_failures_description')} {retention}
                </Typography>
                {loadFailed && <Alert severity="error" sx={{ mb: 2 }}>{getWord('unable_to_check_status')}</Alert>}
                {summary && failures.length === 0 && (
                    <Alert severity="success">{getWord('webhook_failures_none')}</Alert>
                )}
                {failures.length > 0 && (
                    <TableContainer>
                        <Table size="small" aria-label={getWord('webhook_failures')}>
                            <TableHead>
                                <TableRow>
                                    <TableCell padding="checkbox" />
                                    <TableCell>{getWord('webhook_failures_time')}</TableCell>
                                    <TableCell>{getWord('webhook_failures_webhook')}</TableCell>
                                    <TableCell>HTTP</TableCell>
                                    <TableCell>{getWord('webhook_failures_call_sid')}</TableCell>
                                    <TableCell>{getWord('webhook_failures_error')}</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {failures.map((failure) => (
                                    <FailureRow key={failure.id} failure={failure} getWord={getWord} />
                                ))}
                            </TableBody>
                        </Table>
                    </TableContainer>
                )}
            </CardContent>
        </Card>
    );
}

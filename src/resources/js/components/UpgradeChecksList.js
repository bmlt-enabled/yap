import {
    Box,
    Chip,
    Link,
    List,
    ListItem,
    ListItemIcon,
    ListItemText,
    Typography,
} from "@mui/material";
import {
    CheckCircle as CheckIcon,
    Info as InfoIcon,
    Warning as WarningIcon,
    Error as ErrorIcon,
    RemoveCircleOutline as SkipIcon,
    OpenInNew as OpenInNewIcon,
} from "@mui/icons-material";
import { useLocalization } from "../contexts/LocalizationContext";

function getCheckIcon(status) {
    switch (status) {
        case 'pass':
            return <CheckIcon color="success" fontSize="small" />;
        case 'warn':
            return <WarningIcon color="warning" fontSize="small" />;
        case 'fail':
            return <ErrorIcon color="error" fontSize="small" />;
        case 'skip':
            return <SkipIcon color="disabled" fontSize="small" />;
        default:
            return <InfoIcon color="action" fontSize="small" />;
    }
}

function statusChipColor(status) {
    switch (status) {
        case 'pass':
            return 'success';
        case 'warn':
            return 'warning';
        case 'fail':
            return 'error';
        case 'skip':
            return 'default';
        default:
            return 'default';
    }
}

export default function UpgradeChecksList({ checks = [], dense = true, showStatusChip = false }) {
    const { getWord } = useLocalization();

    if (!checks.length) {
        return null;
    }

    return (
        <List dense={dense} sx={{ pt: 0 }}>
            {checks.map((check) => (
                <ListItem key={check.id} disableGutters sx={{ alignItems: 'flex-start', py: 0.5 }}>
                    <ListItemIcon sx={{ minWidth: 32, mt: 0.25 }}>
                        {getCheckIcon(check.status)}
                    </ListItemIcon>
                    <ListItemText
                        primary={
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
                                <Typography component="span" variant="body2" fontWeight={500}>
                                    {check.label}
                                </Typography>
                                {showStatusChip && (
                                    <Chip
                                        label={check.status}
                                        size="small"
                                        color={statusChipColor(check.status)}
                                        variant="outlined"
                                    />
                                )}
                            </Box>
                        }
                        secondary={
                            <>
                                {check.message && (
                                    <Typography component="span" variant="body2" color="text.secondary" display="block">
                                        {check.message}
                                    </Typography>
                                )}
                                {check.url && (
                                    <Link
                                        href={check.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        variant="body2"
                                        sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.5, mt: 0.5 }}
                                    >
                                        {getWord('open_in_twilio_console') || 'Open in Twilio Console'}
                                        <OpenInNewIcon sx={{ fontSize: 14 }} />
                                    </Link>
                                )}
                            </>
                        }
                    />
                </ListItem>
            ))}
        </List>
    );
}

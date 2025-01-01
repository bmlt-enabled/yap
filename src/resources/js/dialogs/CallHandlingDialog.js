import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle} from "@mui/material";

export function CallHandlingDialog({ open, onClose, serviceBodyId }) {
    return (
        <Dialog fullWidth open={open} onClose={() => onClose()}>
            <DialogTitle>Service Body Call Handling ({serviceBodyId})</DialogTitle>
            <DialogContent>I am a custom dialog</DialogContent>
            <DialogActions>
                <Button onClick={() => onClose()}>Close me</Button>
            </DialogActions>
        </Dialog>
    );
}

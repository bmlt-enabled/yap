import { useEffect, useState, useRef } from 'react';
import {
    Card,
    CardContent,
    FormControl,
    Select,
    MenuItem,
    FormControlLabel,
    Switch,
    Button,
    ButtonGroup,
    Modal,
    Box,
    Typography,
    Popover,
    Stack,
    TextField
} from '@mui/material';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import { AdapterDayjs } from '@mui/x-date-pickers/AdapterDayjs';
import CalendarTodayIcon from '@mui/icons-material/CalendarToday';
import apiClient from '../services/api';
import moment from 'moment';
import dayjs from 'dayjs';
import Plotly from 'plotly.js-dist';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-fullscreen';
import { ReactTabulator } from 'react-tabulator';
import 'react-tabulator/lib/styles.css';
import 'react-tabulator/lib/css/tabulator.min.css';
import '../../css/app.css';

function Reports() {
    const [serviceBodyId, setServiceBodyId] = useState(-1);
    const [serviceBodies, setServiceBodies] = useState([]);
    const [recurse, setRecurse] = useState(false);
    const [reportsVisible, setReportsVisible] = useState(false);
    const [isTopLevelAdmin, setIsTopLevelAdmin] = useState(false);
    const [dateRange, setDateRange] = useState([
        dayjs().subtract(29, 'days'),
        dayjs()
    ]);
    const [anchorEl, setAnchorEl] = useState(null);

    // Modal state
    const [modalOpen, setModalOpen] = useState(false);
    const [modalData, setModalData] = useState(null);

    // Table data state
    const [cdrData, setCdrData] = useState([]);
    const [eventsData, setEventsData] = useState([]);

    // Refs for DOM elements
    const metricsRef = useRef(null);
    const metricsMapRef = useRef(null);

    // Refs for table instances (to call methods)
    const cdrTableRef = useRef(null);
    const eventsTableRef = useRef(null);
    const metricsMapInstanceRef = useRef(null);

    useEffect(() => {
        fetchServiceBodies();

        return () => {
            // Cleanup map instance
            try {
                if (metricsMapInstanceRef.current) {
                    metricsMapInstanceRef.current.off();
                    metricsMapInstanceRef.current.remove();
                }
            } catch (error) {
                console.warn('Error destroying map:', error);
            }
        };
    }, []);

    const fetchServiceBodies = async () => {
        try {
            const userResponse = await apiClient.get('/api/v1/user');
            // Check if user is admin (either is_admin flag or permissions bitmask)
            const isAdmin = userResponse.data.is_admin === 1 || userResponse.data.is_admin === true;
            setIsTopLevelAdmin(isAdmin);

            const sbResponse = await apiClient.get('/api/v1/rootServer/serviceBodies/user');
            setServiceBodies(sbResponse.data);
        } catch (error) {
            console.error('Error fetching service bodies:', error);
        }
    };


    const getServiceBodyById = (id) => {
        if (id === 0) return { id: "0", name: "General" };
        return serviceBodies.find(sb => parseInt(sb.id) === parseInt(id)) || {};
    };

    const toCurrentTimezone = (value) => {
        return moment(value).format('YYYY-MM-DD HH:mm:ss');
    };

    const getDateRanges = () => {
        const [start, end] = dateRange;
        const startDate = start ? start.format("YYYY-MM-DD 00:00:00") : dayjs().subtract(29, 'days').format("YYYY-MM-DD 00:00:00");
        const endDate = end ? end.format("YYYY-MM-DD 23:59:59") : dayjs().format("YYYY-MM-DD 23:59:59");
        return `&date_range_start=${startDate}&date_range_end=${endDate}`;
    };

    const handleDateRangeClick = (event) => {
        setAnchorEl(event.currentTarget);
    };

    const handlePopoverClose = () => {
        setAnchorEl(null);
    };

    const applyDateRange = (start, end) => {
        setDateRange([start, end]);
        handlePopoverClose();
    };

    const dateRangeShortcuts = [
        { label: 'Today', start: dayjs(), end: dayjs() },
        { label: 'Yesterday', start: dayjs().subtract(1, 'days'), end: dayjs().subtract(1, 'days') },
        { label: 'Last 7 Days', start: dayjs().subtract(6, 'days'), end: dayjs() },
        { label: 'Last 30 Days', start: dayjs().subtract(29, 'days'), end: dayjs() },
        { label: 'Last 60 Days', start: dayjs().subtract(59, 'days'), end: dayjs() },
        { label: 'Last 90 Days', start: dayjs().subtract(89, 'days'), end: dayjs() },
        { label: 'This Month', start: dayjs().startOf('month'), end: dayjs().endOf('month') },
        { label: 'Last Month', start: dayjs().subtract(1, 'month').startOf('month'), end: dayjs().subtract(1, 'month').endOf('month') }
    ];

    const formatDateRangeDisplay = () => {
        const [start, end] = dateRange;
        if (!start || !end) return 'Select Date Range';
        return `${start.format('MMM D, YYYY')} - ${end.format('MMM D, YYYY')}`;
    };

    const open = Boolean(anchorEl);

    // Fetch CDR data
    const fetchCDRData = async () => {
        if (serviceBodyId <= -1) return;

        try {
            const url = `/api/v1/reports/cdr?service_body_id=${serviceBodyId}&page=1&size=100${getDateRanges()}&recurse=${recurse}`;
            const response = await apiClient.get(url);
            const cdrRecords = response.data.data || [];

            // Extract events from CDR records
            const events = [];
            for (const record of cdrRecords) {
                for (const callEvent of record.call_events) {
                    events.push(callEvent);
                }
            }

            setCdrData(cdrRecords);
            setEventsData(events);
            drawMetricsMap(cdrRecords);
        } catch (error) {
            console.error('Error fetching CDR data:', error);
        }
    };

    // CDR Table columns configuration
    const cdrColumns = [
        { title: "Start Time", field: "start_time", mutator: toCurrentTimezone },
        { title: "End Time", field: "end_time", mutator: toCurrentTimezone },
        { title: "Duration (s)", field: "duration" },
        { title: "From", field: "from_number" },
        { title: "To", field: "to_number" },
        { title: "Type", field: "type_name" },
        {
            title: "Events",
            field: "call_events",
            width: 100,
            hozAlign: "center",
            formatter: () => "🔎",
            cellClick: (_e, cell) => {
                setModalData({
                    callData: cell.getRow().getData(),
                    events: cell.getValue()
                });
                setModalOpen(true);
            }
        }
    ];

    // Events Table columns configuration
    const eventsColumns = [
        { title: "Event Time", field: "event_time", mutator: toCurrentTimezone },
        { title: "Event", field: "event_name", formatter: "textarea" },
        {
            title: "Service Body Id", field: "service_body_id", mutator: (id) => {
                if (isNaN(id)) return id;
                const serviceBody = getServiceBodyById(id);
                return `${serviceBody.name} (${serviceBody.id})`;
            }
        },
        { title: "Metadata", field: "meta", formatter: "textarea" },
        { title: "Parent CallSid", field: "parent_callsid", visible: false, download: true }
    ];

    // Table options for CDR table
    const cdrTableOptions = {
        layout: "fitColumns",
        responsiveLayout: "hide",
        tooltips: true,
        movableColumns: true,
        resizableRows: false,
        printAsHtml: true,
        printHeader: "<h3>Call Detail Records<h3>",
        printFooter: "",
        initialSort: [
            { column: "start_time", dir: "desc" },
        ],
    };

    // Table options for events table
    const eventsTableOptions = {
        initialSort: [
            { column: "event_time", dir: "desc" },
        ],
    };

    const getMetricsData = async () => {
        try {
            const url = `/api/v1/reports/metrics?service_body_id=${serviceBodyId}${getDateRanges()}&recurse=${recurse}`;
            const response = await apiClient.get(url);
            const data = response.data;

            const actions = ['Volunteer (CALL)', 'Meetings (CALL)', 'JFT (CALL)', 'Meetings (SMS)', 'Volunteer (SMS)', 'JFT (SMS)', 'SPAD', 'SPAD (SMS)'];
            const actionsPlots = [1, 2, 3, 19, 20, 21, 23, 24];
            const plots = { "1": [], "2": [], "3": [], "19": [], "20": [], "21": [], "23": [], "24": [] };
            const colors = ['#FF6600', '#87B63A', 'indigo', '#FF6E9B', '#446E9B', 'black', 'purple', 'brown'];

            for (const item of data.metrics) {
                const searchType = JSON.parse(item.data).searchType;
                plots[searchType].push({
                    x: item.timestamp,
                    y: item.counts
                });
            }

            // Update summary badges
            document.getElementById('summary-volunteer-calls').textContent = data.calls?.length || 0;
            document.getElementById('summary-meetingsearch-calls').textContent =
                data.summary.find(s => s.event_id === 2)?.counts || 0;
            document.getElementById('summary-meetingsearch-sms').textContent =
                data.summary.find(s => s.event_id === 19)?.counts || 0;
            document.getElementById('summary-volunteer-sms').textContent =
                data.summary.find(s => s.event_id === 20)?.counts || 0;

            // Calculate missed calls
            let missedCalls = 0;
            const totalCalls = data.calls?.length || 0;
            for (const item of data.calls || []) {
                if (parseInt(item.answered_count) === 0 && parseInt(item.missed_count) > 0) {
                    missedCalls++;
                }
            }
            const missedCallsPct = totalCalls > 0 ? Math.round((missedCalls / totalCalls) * 100) : 0;
            document.getElementById('summary-missedvolunteer-calls').textContent =
                `${missedCalls} (${missedCallsPct}%)`;

            // Build datasets
            const datasets = [];
            for (let a = 0; a < actions.length; a++) {
                const ap = actionsPlots[a];
                if (plots[ap] && plots[ap].length > 0) {
                    const xAgg = plots[ap].map(p => p.x);
                    const yAgg = plots[ap].map(p => p.y);

                    datasets.push({
                        type: 'scatter',
                        mode: 'lines+markers',
                        name: actions[a],
                        x: xAgg,
                        y: yAgg,
                        line: { color: colors[a] }
                    });
                }
            }

            Plotly.newPlot(metricsRef.current, datasets, {
                title: 'Usage Summary',
                xaxis: {
                    title: 'Day',
                    type: 'date'
                },
                yaxis: {
                    title: 'Occurrences'
                }
            });
        } catch (error) {
            console.error('Error fetching metrics:', error);
        }
    };

    const drawMetricsMap = (data) => {
        if (metricsMapInstanceRef.current) {
            metricsMapInstanceRef.current.off();
            metricsMapInstanceRef.current.remove();
        }

        const map = L.map(metricsMapRef.current, {
            fullscreenControl: {
                pseudoFullscreen: false
            }
        }).setView([0, 0], 3);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
        }).addTo(map);

        const bounds = [];
        const meetingsMarker = `${typeof rootUrl !== 'undefined' ? rootUrl : ''}/public/img/green_marker.png`;
        const volunteersMarker = `${typeof rootUrl !== 'undefined' ? rootUrl : ''}/public/img/orange_marker.png`;

        if (data) {
            for (const record of data) {
                for (const callEvent of record.call_events || []) {
                    const meta = JSON.parse(callEvent.meta || '{}');
                    if (meta.coordinates && (callEvent.event_id === 1 || callEvent.event_id === 14)) {
                        const location = meta.coordinates;
                        if (location.location) {
                            const icon = L.icon({
                                iconUrl: parseInt(callEvent.event_id) === 1 ? volunteersMarker : meetingsMarker,
                                iconSize: [32, 32],
                            });

                            const latLng = [location.latitude, location.longitude];
                            const marker = L.marker(latLng, { icon, title: location.location }).addTo(map);
                            marker.bindPopup(location.location);
                            bounds.push(latLng);
                        }
                    }
                }
            }
        }

        const legend = L.control({ position: 'bottomleft' });
        legend.onAdd = () => {
            const div = L.DomUtil.create('div', 'info legend metrics-map-legend');
            div.innerHTML += '<strong>Legend</strong><br/>';
            div.innerHTML += `<img src="${meetingsMarker}" />Meeting Lookup<br/>`;
            div.innerHTML += `<img src="${volunteersMarker}" />Volunteer Lookup`;
            return div;
        };
        legend.addTo(map);

        if (bounds.length > 0) {
            map.fitBounds(bounds);
        }

        // Invalidate size to ensure tiles render correctly
        setTimeout(() => {
            map.invalidateSize();
        }, 100);

        metricsMapInstanceRef.current = map;
    };

    const updateAllReports = async () => {
        if (serviceBodyId <= -1) return;

        setReportsVisible(true);

        try {
            await Promise.all([
                fetchCDRData(),
                getMetricsData()
            ]);
        } catch (error) {
            console.error('Error updating reports:', error);
        }
    };

    useEffect(() => {
        if (serviceBodyId > -1) {
            updateAllReports();
        }
    }, [serviceBodyId, recurse, dateRange]);

    const handlePrint = () => {
        cdrTableRef.current?.print(false, true);
    };

    const handleDownloadRecordsCSV = () => {
        cdrTableRef.current?.download("csv", "yap-records.csv");
    };

    const handleDownloadEventsCSV = () => {
        eventsTableRef.current?.download("csv", "yap-events.csv");
    };

    const handleDownloadXLSX = () => {
        const sheets = {
            "Calls": true,
            "Events": eventsTableRef.current
        };
        cdrTableRef.current?.download("xlsx", "data.xlsx", { sheets });
    };

    const handleDownloadJSON = () => {
        cdrTableRef.current?.download("json", "yap.json");
    };

    const handleCloseModal = () => {
        setModalOpen(false);
        setModalData(null);
    };

    // Modal table columns
    const callDetailModalColumns = [
        { title: "Start Time", field: "start_time", mutator: toCurrentTimezone },
        { title: "End Time", field: "end_time", mutator: toCurrentTimezone },
        { title: "Duration (s)", field: "duration" },
        { title: "From", field: "from_number" },
        { title: "To", field: "to_number" },
        { title: "Type", field: "type_name" },
    ];

    const eventsModalColumns = [
        { title: "Event Time", field: "event_time", mutator: toCurrentTimezone },
        { title: "Event", field: "event_name", formatter: "textarea" },
        {
            title: "Service Body Id", field: "service_body_id", mutator: (id) => {
                if (isNaN(id)) return id;
                const serviceBody = getServiceBodyById(id);
                return `${serviceBody.name} (${serviceBody.id})`;
            }
        },
        { title: "Metadata", field: "meta", formatter: "textarea" }
    ];

    return (
        <Card className="card">
            <CardContent>
                <div style={{ marginBottom: '20px' }}>
                    <div style={{ display: 'flex', gap: '10px', alignItems: 'center', marginBottom: '10px' }}>
                        <FormControl size="small" style={{ minWidth: 300 }}>
                            <Select
                                value={serviceBodyId}
                                onChange={(e) => setServiceBodyId(e.target.value)}
                                displayEmpty
                                MenuProps={{
                                    disablePortal: false,
                                    keepMounted: true,
                                    PaperProps: {
                                        style: {
                                            maxHeight: 400,
                                        },
                                    },
                                }}
                            >
                                <MenuItem value={-1}>-= Select A Service Body =-</MenuItem>
                                {isTopLevelAdmin && <MenuItem value={0}>All</MenuItem>}
                                {serviceBodies.map(sb => (
                                    <MenuItem key={sb.id} value={sb.id}>
                                        {sb.name} ({sb.id}) / {sb.parent_name} ({sb.parent_id})
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>

                        <FormControlLabel
                            control={
                                <Switch
                                    checked={recurse}
                                    onChange={(e) => setRecurse(e.target.checked)}
                                />
                            }
                            label="Recurse"
                        />

                        <TextField
                            size="small"
                            value={formatDateRangeDisplay()}
                            onClick={handleDateRangeClick}
                            slotProps={{
                                input: {
                                    readOnly: true,
                                    startAdornment: <CalendarTodayIcon sx={{ mr: 1, color: 'action.active' }} />,
                                }
                            }}
                            sx={{
                                minWidth: '300px',
                                cursor: 'pointer',
                                '& .MuiInputBase-input': { cursor: 'pointer' }
                            }}
                        />

                        <Popover
                            open={open}
                            anchorEl={anchorEl}
                            onClose={handlePopoverClose}
                            anchorOrigin={{
                                vertical: 'bottom',
                                horizontal: 'left',
                            }}
                        >
                            <Box sx={{ p: 2, display: 'flex', gap: 2 }}>
                                <Stack spacing={1} sx={{ minWidth: '150px' }}>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 'bold' }}>
                                        Quick Select
                                    </Typography>
                                    {dateRangeShortcuts.map((shortcut) => (
                                        <Button
                                            key={shortcut.label}
                                            size="small"
                                            onClick={() => applyDateRange(shortcut.start, shortcut.end)}
                                            sx={{ justifyContent: 'flex-start' }}
                                        >
                                            {shortcut.label}
                                        </Button>
                                    ))}
                                </Stack>
                                <Stack spacing={2}>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 'bold' }}>
                                        Custom Range
                                    </Typography>
                                    <LocalizationProvider dateAdapter={AdapterDayjs}>
                                        <DatePicker
                                            label="Start Date"
                                            value={dateRange[0]}
                                            onChange={(newValue) => setDateRange([newValue, dateRange[1]])}
                                            slotProps={{
                                                textField: { size: 'small' }
                                            }}
                                        />
                                        <DatePicker
                                            label="End Date"
                                            value={dateRange[1]}
                                            onChange={(newValue) => setDateRange([dateRange[0], newValue])}
                                            slotProps={{
                                                textField: { size: 'small' }
                                            }}
                                        />
                                    </LocalizationProvider>
                                    <Button
                                        variant="contained"
                                        onClick={handlePopoverClose}
                                        size="small"
                                    >
                                        Apply
                                    </Button>
                                </Stack>
                            </Box>
                        </Popover>
                    </div>
                </div>

                {reportsVisible && (
                    <>
                        <div id="metrics" ref={metricsRef} style={{ marginBottom: '20px' }}></div>

                        <div id="metrics-summary" style={{ marginBottom: '20px' }}>
                            <ButtonGroup variant="contained" size="small">
                                <Button color="info">
                                    Volunteer Lookups (CALL) <span id="summary-volunteer-calls" style={{ marginLeft: '5px', background: '#f0f0f0', padding: '2px 6px', borderRadius: '3px', color: '#000' }}>0</span>
                                </Button>
                                <Button color="info">
                                    Meeting Lookups (CALL) <span id="summary-meetingsearch-calls" style={{ marginLeft: '5px', background: '#f0f0f0', padding: '2px 6px', borderRadius: '3px', color: '#000' }}>0</span>
                                </Button>
                                <Button color="error">
                                    Missed (CALL) <span id="summary-missedvolunteer-calls" style={{ marginLeft: '5px', background: '#f0f0f0', padding: '2px 6px', borderRadius: '3px', color: '#000' }}>0</span>
                                </Button>
                                <Button color="info">
                                    Volunteer Lookups (SMS) <span id="summary-volunteer-sms" style={{ marginLeft: '5px', background: '#f0f0f0', padding: '2px 6px', borderRadius: '3px', color: '#000' }}>0</span>
                                </Button>
                                <Button color="info">
                                    Meeting Lookups (SMS) <span id="summary-meetingsearch-sms" style={{ marginLeft: '5px', background: '#f0f0f0', padding: '2px 6px', borderRadius: '3px', color: '#000' }}>0</span>
                                </Button>
                            </ButtonGroup>
                        </div>

                        <div
                            ref={metricsMapRef}
                            style={{ height: '400px', width: '100%', marginBottom: '20px' }}
                        ></div>

                        <ButtonGroup variant="contained" size="small" style={{ marginBottom: '10px' }}>
                            <Button onClick={handlePrint} color="warning">Print</Button>
                            <Button onClick={handleDownloadRecordsCSV} color="success">CSV (Records)</Button>
                            <Button onClick={handleDownloadEventsCSV} color="success">CSV (Events)</Button>
                            <Button onClick={handleDownloadXLSX} color="primary">XLSX</Button>
                            <Button onClick={handleDownloadJSON} color="warning">JSON</Button>
                            <Button
                                component="a"
                                href={`${typeof rootUrl !== 'undefined' ? rootUrl : ''}/api/v1/reports/metrics?service_body_id=${serviceBodyId}${getDateRanges()}&recurse=${recurse}`}
                                target="_blank"
                                color="warning"
                            >
                                MetricsJSON
                            </Button>
                            <Button
                                component="a"
                                href={`${typeof rootUrl !== 'undefined' ? rootUrl : ''}/api/v1/reports/mapmetrics?service_body_id=${serviceBodyId}${getDateRanges()}&recurse=${recurse}&format=csv&event_id=14`}
                                target="_blank"
                                color="warning"
                            >
                                POI CSV (Meetings)
                            </Button>
                            <Button
                                component="a"
                                href={`${typeof rootUrl !== 'undefined' ? rootUrl : ''}/api/v1/reports/mapmetrics?service_body_id=${serviceBodyId}${getDateRanges()}&recurse=${recurse}&format=csv&event_id=1`}
                                target="_blank"
                                color="warning"
                            >
                                POI CSV (Volunteers)
                            </Button>
                            <Button onClick={updateAllReports} color="inherit">Refresh</Button>
                        </ButtonGroup>

                        <ReactTabulator
                            onRef={(ref) => (cdrTableRef.current = ref)}
                            columns={cdrColumns}
                            data={cdrData}
                            options={cdrTableOptions}
                        />

                        <div style={{ display: 'none' }}>
                            <ReactTabulator
                                onRef={(ref) => (eventsTableRef.current = ref)}
                                columns={eventsColumns}
                                data={eventsData}
                                options={eventsTableOptions}
                            />
                        </div>
                    </>
                )}

                <Modal
                    open={modalOpen}
                    onClose={handleCloseModal}
                    aria-labelledby="call-events-modal"
                >
                    <Box sx={{
                        position: 'absolute',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                        width: '90%',
                        maxWidth: '1200px',
                        bgcolor: 'background.paper',
                        boxShadow: 24,
                        p: 4,
                        maxHeight: '90vh',
                        overflow: 'auto'
                    }}>
                        <Typography variant="h6" component="h2" gutterBottom>
                            Call Events
                        </Typography>
                        {modalData && (
                            <>
                                <div style={{ marginBottom: '20px' }}>
                                    <ReactTabulator
                                        columns={callDetailModalColumns}
                                        data={[modalData.callData]}
                                        options={{ layout: "fitColumns", tooltips: true }}
                                    />
                                </div>
                                <ReactTabulator
                                    columns={eventsModalColumns}
                                    data={modalData.events}
                                    options={{
                                        layout: "fitColumns",
                                        tooltips: true,
                                        initialSort: [{ column: "event_time", dir: "desc" }]
                                    }}
                                />
                            </>
                        )}
                        <Button onClick={handleCloseModal} variant="contained" style={{ marginTop: '20px' }}>
                            Close
                        </Button>
                    </Box>
                </Modal>
            </CardContent>
        </Card>
    );
}

export default Reports;

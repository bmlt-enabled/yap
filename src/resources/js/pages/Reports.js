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
    Typography
} from '@mui/material';
import apiClient from '../services/api';
import moment from 'moment';
import Plotly from 'plotly.js-dist';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-fullscreen';
import { ReactTabulator } from 'react-tabulator';
import 'react-tabulator/lib/styles.css';
import 'react-tabulator/lib/css/tabulator.min.css';
import 'daterangepicker';
import $ from 'jquery';
import 'daterangepicker/daterangepicker.css';

function Reports() {
    const [serviceBodyId, setServiceBodyId] = useState(-1);
    const [serviceBodies, setServiceBodies] = useState([]);
    const [recurse, setRecurse] = useState(false);
    const [reportsVisible, setReportsVisible] = useState(false);
    const [isTopLevelAdmin, setIsTopLevelAdmin] = useState(false);
    const [dateRange, setDateRange] = useState({
        start: moment().subtract(29, 'days'),
        end: moment()
    });

    // Modal state
    const [modalOpen, setModalOpen] = useState(false);
    const [modalData, setModalData] = useState(null);

    // Table data state
    const [cdrData, setCdrData] = useState([]);
    const [eventsData, setEventsData] = useState([]);

    // Refs for DOM elements
    const dateRangeRef = useRef(null);
    const metricsRef = useRef(null);
    const metricsMapRef = useRef(null);

    // Refs for table instances (to call methods)
    const cdrTableRef = useRef(null);
    const eventsTableRef = useRef(null);
    const metricsMapInstanceRef = useRef(null);

    useEffect(() => {
        fetchServiceBodies();
        initializeDateRangePicker();

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

    const initializeDateRangePicker = () => {
        const start = moment().subtract(29, 'days');
        const end = moment();

        $(dateRangeRef.current).daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'Last 60 Days': [moment().subtract(59, 'days'), moment()],
                'Last 90 Days': [moment().subtract(89, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, (start, end) => {
            setDateRange({ start, end });
            $(dateRangeRef.current).find('span').html(
                start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY')
            );
        });

        $(dateRangeRef.current).find('span').html(
            start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY')
        );
    };

    const getServiceBodyById = (id) => {
        if (id === 0) return { id: "0", name: "General" };
        return serviceBodies.find(sb => parseInt(sb.id) === parseInt(id)) || {};
    };

    const toCurrentTimezone = (value) => {
        return moment(value).format('YYYY-MM-DD HH:mm:ss');
    };

    const getDateRanges = () => {
        const drPicker = $(dateRangeRef.current).data('daterangepicker');
        return `&date_range_start=${drPicker.startDate.format("YYYY-MM-DD 00:00:00")}&date_range_end=${drPicker.endDate.format("YYYY-MM-DD 23:59:59")}`;
    };

    // Fetch CDR data
    const fetchCDRData = async () => {
        if (serviceBodyId <= -1) return;

        try {
            const url = `../api/v1/reports/cdr?service_body_id=${serviceBodyId}&page=1&size=100${getDateRanges()}&recurse=${recurse}`;
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
            const url = `../api/v1/reports/metrics?service_body_id=${serviceBodyId}${getDateRanges()}&recurse=${recurse}`;
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
        const meetingsMarker = '/public/img/green_marker.png';
        const volunteersMarker = '/public/img/orange_marker.png';

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

                        <div
                            ref={dateRangeRef}
                            style={{
                                background: '#fff',
                                cursor: 'pointer',
                                padding: '5px 10px',
                                border: '1px solid #ccc',
                                minWidth: '250px'
                            }}
                        >
                            🗓&nbsp;<span></span> ↓
                        </div>
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
                                href={`../api/v1/reports/metrics?service_body_id=${serviceBodyId}${getDateRanges()}&recurse=${recurse}`}
                                target="_blank"
                                color="warning"
                            >
                                MetricsJSON
                            </Button>
                            <Button
                                component="a"
                                href={`../api/v1/reports/mapmetrics?service_body_id=${serviceBodyId}${getDateRanges()}&recurse=${recurse}&format=csv&event_id=14`}
                                target="_blank"
                                color="warning"
                            >
                                POI CSV (Meetings)
                            </Button>
                            <Button
                                component="a"
                                href={`../api/v1/reports/mapmetrics?service_body_id=${serviceBodyId}${getDateRanges()}&recurse=${recurse}&format=csv&event_id=1`}
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

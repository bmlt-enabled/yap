dayOfTheWeek = {1:"Sunday",2:"Monday",3:"Tuesday",4:"Wednesday",5:"Thursday",6:"Friday",7:"Saturday"};
var groups;
var calendar;
var metrics_map = null;

Array.prototype.getArrayItemByObjectKeyValue = function (key, value) {
    for (var i = 0; i < this.length; i++) {
        if (this[i][key] === value) {
            return this[i];
        }
    }
};

function getDateRanges()
{
    var daterangepicker = $("#reportrange").data('daterangepicker')
    return "&date_range_start=" + daterangepicker.startDate.format("YYYY-MM-DD 00:00:00") + "&date_range_end=" + daterangepicker.endDate.format("YYYY-MM-DD 23:59:59");
}

function recurseReports()
{
    return $("#recursive-reports-switch:checked").length > 0;
}

function initReports(dataLoadedCallback)
{
    $(".page-size-dropdown-item").click(function (e) {
        $(".page-size-dropdown-item").removeClass("active");
        $(e.target).addClass("active");
        var pageSize = parseInt(e.target.text);
        table.setPageSize(pageSize);
    });

    $("#print-table").on("click", function () {
        table.print(false, true);
    });

    $("#download-records-csv").click(function () {
        table.download("csv", "yap-records.csv");
    });

    $("#download-events-csv").click(function () {
        eventsTable.download("csv", "yap-events.csv");
    });

    $("#download-json").click(function () {
        table.download("json", "yap.json");
    });

    $("#download-xlsx").click(function () {
        var sheets = {
            "Calls": true,
            "Events": "#events-table"
        };

        table.download("xlsx", "data.xlsx", {sheets:sheets});
    });

    var eventsTableColumns = [
        {title: "Event Time", field: "event_time", mutator: toCurrentTimezone},
        {title: "Event", field: "event_name", formatter: "textarea"},
        {title: "Service Body Id", field: "service_body_id", mutator: function (id) {
            if (isNaN(id)) {
                return id;
            }
            var service_body = getServiceBodyById(id);
            return service_body['name'] + " (" + service_body['id'] + ")"
        }},
        {title: "Metadata", field: "meta", formatter: "textarea"},
        {title: "Parent CallSid", field: "parent_callsid", visible: false, download: true}
    ];

    var table = new Tabulator("#cdr-table", {
        layout: "fitColumns",
        responsiveLayout: "hide",
        tooltips: true,
        addRowPos: "top",
        history: true,
        pagination: "remote",
        ajaxURL: "../api/v1/reports/cdr",
        ajaxURLGenerator: function (url, config, params) {
            return url + "?service_body_id=" + $("#service_body_id").val() + "&page=1&size=1" + getDateRanges() + "&recurse=" + recurseReports();
        },
        ajaxResponse: function (url, params, response) {
            var events = [];
            for (var i = 0; i < response['data'].length; i++) {
                var callEvents = response['data'][i]['call_events'];
                for (var j = 0; j < callEvents.length; j++) {
                    var callEvent = callEvents[j];
                    events.push(callEvent);
                }
            }

            eventsTable.setData(events);
            return response;
        },
        dataLoaded: function (data) {
            dataLoadedCallback(data)
        },
        movableColumns: true,
        resizableRows: false,
        printAsHtml: true,
        printHeader: "<h3>Call Detail Records<h3>",
        printFooter: "",
        initialSort: [
            {column:"start_time", dir:"desc"},
        ],
        columns: [
            {title:"Start Time", field:"start_time", mutator: toCurrentTimezone },
            {title:"End Time", field:"end_time", mutator: toCurrentTimezone },
            {title:"Duration (s)", field:"duration"},
            {title:"From", field:"from_number"},
            {title:"To", field:"to_number"},
            {title:"Type", field:"type_name"},
            {title:"Events",
                field:"call_events",
                width: 100,
                hozAlign: "center",
                formatter: function() {
                    return "🔎";
                },
                cellClick: function(e, cell) {
                    $("#events-modal-table").html("");
                    $("#callEventsDetails").on('shown.bs.modal', function(e) {
                        console.log(cell.getRow().getData())
                        new Tabulator("#call-detail-modal-table", {
                            layout: "fitColumns",
                            tooltips: true,
                            addRowPos: "top",
                            data: [cell.getRow().getData()],
                            columns: [
                                {title:"Start Time", field:"start_time", mutator: toCurrentTimezone },
                                {title:"End Time", field:"end_time", mutator: toCurrentTimezone },
                                {title:"Duration (s)", field:"duration"},
                                {title:"From", field:"from_number"},
                                {title:"To", field:"to_number"},
                                {title:"Type", field:"type_name"},
                            ],
                        });

                        new Tabulator("#events-modal-table", {
                            layout: "fitColumns",
                            tooltips: true,
                            addRowPos: "top",
                            data: cell.getValue(),
                            columns: eventsTableColumns,
                            initialSort:[
                                {column:"event_time", dir:"desc"},
                            ],
                        });
                    })
                    $("#callEventsDetails").modal("show");
                }
            }
        ],
    });

    var eventsTable = new Tabulator("#events-table", {
        columns: eventsTableColumns,
        initialSort:[
            {column:"event_time", dir:"desc"},
        ],
    });

    return table;
}

function getMetricsData()
{
    $("#metrics").slideUp(function () {
        $.getJSON("../api/v1/reports/metrics?service_body_id=" + $("#service_body_id").val() + getDateRanges() + "&recurse=" + recurseReports(), function (data) {
            var actions = ['Volunteer (CALL)', 'Meetings (CALL)', 'JFT (CALL)', 'Meetings (SMS)', 'Volunteer (SMS)', 'JFT (SMS)', 'SPAD', 'SPAD (SMS)'];
            var actions_plots = [1, 2, 3, 19, 20, 21, 23, 24];
            var plots = {"1": [], "2": [], "3": [], "19": [], "20": [], "21": [], "23": [], "24": []};
            var colors = ['#FF6600', '#87B63A', 'indigo', '#FF6E9B', '#446E9B', 'black', 'purple', 'brown'];
            for (let item of data['metrics']) {
                plots[JSON.parse(item['data'])['searchType']].push({
                    'x': item['timestamp'],
                    'y': item['counts']
                });
            }

            var connectedCalls = 0;
            $("#summary-volunteer-calls").html("0");
            $("#summary-meetingsearch-calls").html("0");
            $("#summary-missedvolunteer-calls").html("0 (0%)");
            $("#summary-meetingsearch-sms").html("0");
            $("#summary-volunteer-sms").html("0");

            for (var item of data['summary']) {
                if (item['event_id'] === 2) {
                    $("#summary-meetingsearch-calls").html(item['counts'])
                } else if (item['event_id'] === 19) {
                    $("#summary-meetingsearch-sms").html(item['counts'])
                } else if (item['event_id'] === 20) {
                    $("#summary-volunteer-sms").html(item['counts'])
                }
            }

            var totalCalls = data['calls'].length;
            var missedCalls = 0;
            $("#summary-volunteer-calls").html(totalCalls)
            for (let item of data['calls']) {
                var answeredCount = parseInt(item['answered_count']);
                var missedCount = parseInt(item['missed_count']);
                if (answeredCount === 0 && missedCount > 0) {
                    missedCalls++;
                }
            }
            var missedCallsPct = Math.round((missedCalls / totalCalls) * 100);
            $("#summary-missedvolunteer-calls").html(missedCalls + " ("+ missedCallsPct + "%)");

            var datasets = [];
            for (var a = 0; a < actions.length; a++) {
                var xAgg = [];
                var yAgg = [];
                var ap = actions_plots[a];
                if (plots[ap] !== undefined) {
                    for (var p = 0; p < plots[ap].length; p++) {
                        xAgg.push(plots[ap][p].x);
                        yAgg.push(plots[ap][p].y);
                    }

                    datasets.push({
                        type: 'scatter',
                        mode: 'lines+markers',
                        name: actions[a],
                        x: xAgg,
                        y: yAgg,
                        line: {color: colors[a]}
                    })
                }
            }

            $("#metrics").slideDown(function () {
                Plotly.newPlot("metrics", datasets, {
                    title: 'Usage Summary',
                    xaxis: {
                        title: 'Day',
                        type: 'date'
                    },
                    yaxis: {
                        title: 'Occurrences'
                    }
                });
            });
        });
    });
}

function drawMetricsMap(data)
{
    if (metrics_map !== null) {
        metrics_map.off();
        metrics_map.remove();
    }

    metrics_map = L.map('metrics-map', {
        fullscreenControl: {
            pseudoFullscreen: false
        }
    }).setView([0, 0], 3);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'}).addTo(metrics_map);
    var bounds = [];

    if (data !== undefined) {
        for (var i = 0; i < data.length; i++) {
            for (var j = 0; j < data[i].call_events.length; j++) {
                var call_events = data[i].call_events[j];
                if (JSON.parse(call_events['meta'])['coordinates'] !== undefined && (call_events['event_id'] === 1 || call_events['event_id'] === 14)) {
                    var location = JSON.parse(call_events['meta'])['coordinates'];
                    if (location['location'] !== null) {
                        var content = location['location'];
                        var myIcon = L.icon({
                            iconUrl: parseInt(call_events['event_id']) === 1 ? volunteersMarker : meetingsMarker,
                            iconSize: [32, 32],
                        });

                        var latLng = [location['latitude'], location['longitude']];
                        var marker = L.marker(latLng, {icon: myIcon, title: content}).addTo(metrics_map);
                        marker.bindPopup(content);
                        bounds.push(latLng);
                    }
                }
            }
        }
    }

    var legend = L.control({position: 'bottomleft'});
    legend.onAdd = function (map) {
        var div = L.DomUtil.create('div', 'info legend');
        div.className = 'metrics-map-legend';
        div.innerHTML += '<strong>Legend</strong><br/>';
        div.innerHTML += '<img src="' + meetingsMarker + '" />Meeting Lookup<br/>';
        div.innerHTML += '<img src="' + volunteersMarker + '" />Volunteer Lookup';
        return div;
    };
    legend.addTo(metrics_map);
    if (bounds.length > 0) {
        metrics_map.fitBounds(bounds);
    }
}

function updateCallRecords()
{
    Tabulator.prototype.findTable("#cdr-table")[0].setData();
}

function updateAllReports()
{
    $("#reports").show();
    getMetricsData();
    drawMetricsMap();
    updateCallRecords();
    $("#metrics-json").attr("href", "../api/v1/reports/metrics?service_body_id=" + $("#service_body_id").val() + getDateRanges() + "&recurse=" + recurseReports());
    $("#meetings-map-metrics-json").attr("href", "../api/v1/reports/mapmetrics?service_body_id=" + $("#service_body_id").val() + getDateRanges() + "&recurse=" + recurseReports() + "&format=csv&event_id=14");
    $("#volunteers-map-metrics-json").attr("href", "../api/v1/reports/mapmetrics?service_body_id=" + $("#service_body_id").val() + getDateRanges() + "&recurse=" + recurseReports() + "&format=csv&event_id=1");
}

function getMetrics(table)
{
    $("#service_body_id").on("change", function (e) {
        updateAllReports();
    });

    $("#recursive-reports-switch").on("change", function (e) {
        updateAllReports();
    });

    $("#refresh-button").on("click", function () {
        updateAllReports();
    });

    getMetricsData();
}

function volunteerPage()
{
    $(function () {
        if ($('select#service_body_id option').length === 2) {
            $('#service_body_id option:nth-child(2)').prop('selected', true)
            $('#service_body_id').change();
        }
    });

    $("#service_body_id").on("change", function () {
        addNewVolunteerDialog($(this).val() > 0);
        addGroupVolunteersDialog($(this).val() >= 0);
        clearVolunteerCards();
        if ($(this).val() > 0) {
            var service_body_id = $(this).val();
            spinnerDialog(true, "Retrieving Volunteers...", function () {
                loadGroups(service_body_id, function () {
                    loadVolunteers(service_body_id, function () {
                        $("#volunteers-download-list-csv").on("click", function () {
                            document.location.href='../api/v1/volunteers/download?service_body_id='+service_body_id+'&fmt=csv';
                        })
                        $("#volunteers-download-list-json").on("click", function () {
                            document.location.href='../api/v1/volunteers/download?service_body_id='+service_body_id+'&fmt=json';
                        })
                        $("#volunteers-download-recursive-list-csv").on("click", function () {
                            document.location.href='../api/v1/volunteers/download?service_body_id='+service_body_id+'&fmt=csv&recurse=true';
                        })
                        $("#volunteers-download-recursive-list-json").on("click", function () {
                            document.location.href='../api/v1/volunteers/download?service_body_id='+service_body_id+'&fmt=json&recurse=true ';
                        })
                        spinnerDialog(false);
                    })
                });
            });
        }
    });

    $("#volunteerCards").sortable({
        "handle":".volunteer-sort-icon"
    });

    for (var hr = 1; hr <= 12; hr++) {
        var hr_value = hr < 10 ? "0" + hr : hr.toString();
        $(".hours_field").append(new Option(hr_value, hr_value));
    }

    for (var min = 0; min <= 59; min++) {
        var min_value = min < 10 ? "0" + min : min.toString();
        $(".minutes_field").append(new Option(min_value, min_value));
    }
}

function schedulePage()
{
    var calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        allDaySlot: false,
        defaultView: 'timeGridWeek',
        nowIndicator: true,
        editable: true,
        firstDay: (new Date()).getDay(),
        themeSystem: 'bootstrap',
        header: {
            left: null,
            center: null,
            right: "timeGridWeek, timeGridDay, listWeek, prev, next"
        },
        height: 'auto',
        validRange: {
            start: moment().startOf('day').format("YYYY-MM-DD"),
            end: moment().add(7, 'days').endOf('day').format("YYYY-MM-DD")
        },
        eventOrder: ['sequence'],
        eventAllow: function (dropLocation, draggedEvent) {
            return moment(dropLocation.start).day() === moment(dropLocation.end).day();
        },
        slotEventOverlap: false,
        plugins: ['timeGrid','list','interaction','bootstrap'],
        bootstrapFontAwesome: false,
        buttonText: {
            prev: "<",
            next: ">"
        }
    });

    calendar.render();

    $(function () {
        if ($('select#service_body_id option').length === 2) {
            $('#service_body_id option:nth-child(2)').prop('selected', true)
            $('#service_body_id').change();
        }
    });

    $('select#service_body_id').change(function () {
        if (parseInt($('select#service_body_id').val()) > 0) {
            for (eventSource of calendar.getEventSources()) {
                eventSource.remove();
            }
            calendar.addEventSource('../api/v1/volunteers/schedule?service_body_id=' + $('select#service_body_id').val());
        }
    })
}

function includeVolunteers()
{
    includeVolunteer({"volunteer_name": ""});
}

function volunteersValidationHandling(phoneNumberField, volunteerCard)
{
    phoneNumberField.addClass("border-danger");
    let volunteerName = $(volunteerCard).find(".volunteerName").val();
    return volunteerName !== "" ? volunteerName : `Empty Volunteer Card ${$(volunteerCard).find("#volunteerSequence").html()}`;
}

function saveVolunteers(data_type, countryCode)
{
    if (countryCode !== "") {
        let badOnes = [];
        let volunteerCards = $(".volunteerCard").not("#volunteerCardTemplate")
        for (let volunteerCard of volunteerCards) {
            let phoneNumberField = $(volunteerCard).find(".volunteerPhoneNumber")
            try {
                if (phoneNumberField.val() === "" || !libphonenumber.parsePhoneNumber(phoneNumberField.val(), countryCode).isValid()) {
                    badOnes.push(volunteersValidationHandling(phoneNumberField, volunteerCard));
                } else {
                    phoneNumberField.removeClass("border-danger")
                }
            } catch (error) {
                badOnes.push(volunteersValidationHandling(phoneNumberField, volunteerCard));
            }
        }

        let alert = $("#volunteer_saved_alert");
        if (badOnes.length > 0) {
            alert.addClass("alert-danger");
            alert.html(`Invalid phone number(s) detected for ${badOnes.join(", ")}.  Must contain correct length and country code preceded by +.`);
            alert.show();
            alert.fadeOut(5000);
            return false;
        } else {
            alert.hide()
            alert.removeClass("alert-danger");
        }
    }

    $("#save-volunteers").addClass('disabled');
    spinnerDialog(true, "Saving Volunteers...", function () {
        var volunteerCards = $("#volunteerCards").children();
        var data = [];
        for (var volunteerCard of volunteerCards) {
            var cards = $(volunteerCard).find(".shiftCard");
            var cardData = [];
            for (var card of cards) {
                cardData.push(JSON.parse($(card).attr("data")));
            }

            $(volunteerCard).find("#volunteer_shift_schedule").val(dataEncoder(cardData));

            var formData = $(volunteerCard).find("#volunteersForm").serializeArray();
            var dataObj = {};
            for (var formItem of formData) {
                dataObj[formItem["name"]] = $(volunteerCard).find("#volunteersForm").find("#" + formItem["name"]).val();
            }

            data.push(dataObj);
        }

        saveToAdminApi(
            $("#service_body_id").val(),
            data,
            data_type,
            data_type === "_YAP_GROUP_VOLUNTEERS_V2_" ? $("#group_id").val() : 0,
            0,
            function (xhr, status) {
                var alert = $("#volunteer_saved_alert");
                if (xhr.responseText === "{}" || xhr.status !== 200) {
                    alert.addClass("alert-danger");
                    alert.html("Could not save.");
                } else {
                    alert.addClass("alert-success");
                    alert.html("Saved.");
                }

                alert.show();
                alert.fadeOut(3000);
                spinnerDialog(false);
                $("#save-volunteers").removeClass('disabled');
            }
        );
    });
}

function saveServiceBodyConfig(service_body_id)
{
    var serviceBodyConfiguration = $("#serviceBodyConfiguration_" + service_body_id);
    serviceBodyConfiguration.modal('hide');
    spinnerDialog(true, "Saving Service Body Configuration...", function () {
        var data = [];
        var formData = serviceBodyConfiguration.find("#serviceBodyConfigurationForm").serializeArray();
        var dataObj = {};
        for (var formItem of formData) {
            dataObj[formItem["name"]] = formItem["value"]
        }

        data.push(dataObj);

        saveToAdminApi(
            service_body_id,
            data,
            '_YAP_CONFIG_V2_',
            0,
            0,
            function (xhr, status) {
                var alert = $("#service_body_saved_alert");
                if (xhr.responseText === "{}" || xhr.status !== 200) {
                    alert.addClass("alert-danger");
                    alert.html("Could not save.");
                } else {
                    alert.addClass("alert-success");
                    alert.html("Saved.");
                }

                alert.show();
                alert.fadeOut(3000);
                spinnerDialog(false);
            }
        );
    });
}

function saveServiceBodyCallHandling(service_body_id)
{
    var serviceBodyCallHandling = $("#serviceBodyCallHandling_" + service_body_id);
    var urls = [
        $(serviceBodyCallHandling).find("#override_en_US_voicemail_greeting").val(),
        $(serviceBodyCallHandling).find("#override_en_US_greeting").val()
    ];

    $(serviceBodyCallHandling).find("#serviceBodyCallHandlingValidation").html("")

    for (url of urls) {
        if (url !== undefined && url.length > 0 && url.indexOf("http") !== 0) {
            $(serviceBodyCallHandling).find("#serviceBodyCallHandlingValidation").html("Please specify a valid URL.");
            return false;
        }
    }

    serviceBodyCallHandling.modal('hide');

    spinnerDialog(true, "Saving Service Body Call Handling...", function () {
        var data = [];
        var formData = serviceBodyCallHandling.find("#serviceBodyCallHandlingForm").serializeArray();
        var dataObj = {};
        for (var formItem of formData) {
            dataObj[formItem["name"]] = formItem["value"]
        }

        data.push(dataObj);

        saveToAdminApi(
            service_body_id,
            data,
            '_YAP_CALL_HANDLING_V2_',
            0,
            0,
            function (xhr, status) {
                var alert = $("#service_body_saved_alert");
                if (xhr.responseText === "{}" || xhr.status !== 200) {
                    alert.addClass("alert-danger");
                    alert.html("Could not save.");
                } else {
                    alert.addClass("alert-success");
                    alert.html("Saved.");
                }

                alert.show();
                alert.fadeOut(3000);
                spinnerDialog(false);
            }
        );
    });
}

function saveGroups(service_body_id, data, id, callback)
{
    $.ajax({
        async: false,
        type: "POST",
        url: "../api/v1/groups"
            + "?service_body_id=" + service_body_id
            + (id !== null && id !== 0 ? "&id=" + id : ""),
        data: JSON.stringify(data),
        dataType: "json",
        contentType: "application/json",
        complete: callback,
        timeout: 60000
    });
}

function saveToAdminApi(service_body_id, data, data_type, parent_id, id, callback)
{
    $.ajax({
        async: false,
        type: "POST",
        url: "../api/v1/config"
            + "?service_body_id=" + service_body_id
            + "&data_type=" + data_type
            + (parent_id !== null && parent_id !== 0 ? "&parent_id=" + parent_id : "")
            + (id !== null && id !== 0 ? "&id=" + id : ""),
        data: JSON.stringify(data),
        dataType: "json",
        contentType: "application/json",
        complete: callback,
        timeout: 60000
    });
}

function usersApi(data, action, callback)
{
    var method;
    var url;
    if (action === "save") {
        url = "../api/v1/users";
        method = "POST"
    } else if (action === "edit" || action === "profile") {
        url = "../api/v1/users/" + data.username;
        method = "PUT"
    } else if (action === "delete") {
        url = "../api/v1/users/" + data;
        method = "DELETE"
    }

    $.ajax({
        async: false,
        type: method,
        url: url,
        data: JSON.stringify(data),
        dataType: "json",
        contentType: "application/json",
        complete: callback,
        timeout: 60000
    });
}

function addNewVolunteerDialog(isVisible)
{
    isVisible ? $("#newVolunteerDialog").show() : $("#newVolunteerDialog").hide();
}

function addGroupVolunteersDialog(isVisible)
{
    if (isVisible) {
        $("#include-group").show();
        $("#manage-groups").show();
    } else {
        $("#include-group").hide();
        $("#manage-groups").hide();
    }
}

function clearVolunteerCards()
{
    $("#volunteerCards").children().remove()
}

function loadVolunteers(serviceBodyId, callback)
{
    $.getJSON("../api/v1/volunteers?service_body_id=" + serviceBodyId, function (data) {
        if (!$.isEmptyObject(data)) {
            for (item of data['data']) {
                if (item.hasOwnProperty('volunteer_name')) {
                    includeVolunteer(item)
                } else if (item.hasOwnProperty('group_id')) {
                    includeGroup(item)
                }
            }
        }
        callback();
    });
}

function loadGroupVolunteers(group_id, callback)
{
    $.getJSON("../api/v1/groups/volunteers?group_id=" + group_id, function (data) {
        if (!$.isEmptyObject(data)) {
            for (item of data) {
                includeVolunteer(item);
            }
        }
        callback();
    });
}

function onGroupServiceBodyChange(callback)
{
    if (groups !== undefined) {
        groups = undefined
        $("#group_id").html("");
    }

    if (parseInt(document.getElementById("service_body_id").value) === 0) {
        $("#groupsForm").hide();
        addNewVolunteerDialog(false);
        return;
    }

    spinnerDialog(true, "Loading Groups...", function () {
        loadGroups(document.getElementById("service_body_id").value + "&manage=1", function (data) {
            $("#groupsForm").show()
            if (data.length > 0) {
                $("#group_id").html($('<option>', {value: 0, text: '-= Select A Group =-'}));
                for (var i = 0; i < data.length; i++) {
                    $("#group_id").append($('<option>', {
                        value: data[i].id,
                        text: JSON.parse(data[i].data)[0]['group_name']
                    }));
                }

                $("#group_id").prop("disabled", false);
            } else {
                $("#group_id").prop("disabled", true);
            }

            spinnerDialog(false, null, callback);
        })
    });
}

function loadGroups(service_body_id, callback)
{
    if (groups === undefined) {
        $.getJSON("../api/v1/groups?service_body_id=" + service_body_id, function (data) {
            groups = data;
            callback(data)
        });
    } else {
        callback(groups);
    }
}

function getGroupForId(service_body_id, group_id, callback)
{
    loadGroups(service_body_id, function (data) {
        for (item of data) {
            if (item['id'] === group_id) {
                callback(item);
            }
        }

        callback(null);
    });
}

function includeVolunteer(volunteerData)
{
    var shiftRenderQueue = [];
    var cards = $("#volunteerCards").children();
    var getLastVolunteerCard = 0;
    for (var c = 0; c < cards.length; c++) {
        var currentId = parseInt($(cards[c]).attr("id").replace("volunteerCard_", ""));
        if (currentId > getLastVolunteerCard) {
            getLastVolunteerCard = currentId;
        }
    }

    var volunteerCardTemplate = $("#volunteerCardTemplate").clone();
    var volunteerId = "volunteerCard_" + (++getLastVolunteerCard);
    volunteerCardTemplate.attr("id", volunteerId);
    volunteerCardTemplate.find("#volunteerSequence").html(getLastVolunteerCard);
    volunteerCardTemplate.show();
    for (var key in volunteerData) {
        // Handle checkbox fields
        if (volunteerData[key]) {
            volunteerCardTemplate.find("#" + key).prop('checked', true);
        }

        volunteerCardTemplate.find("#" + key).val(volunteerData[key]);

        if (key.indexOf("volunteer_shift_schedule") > -1) {
            var shiftInfoObj = dataDecoder(volunteerData[key]);
            for (var shiftInfoItem of shiftInfoObj) {
                shiftRenderQueue.push(
                    wrapFunction(renderShift, this, [volunteerId, shiftInfoItem])
                );
            }
        }
    }

    if (volunteerData == null || !volunteerData.hasOwnProperty("volunteer_enabled")) {
        volunteerCardTemplate.addClass("cardDisabled");
    }

    volunteerCardTemplate.appendTo("#volunteerCards");
    while (shiftRenderQueue.length > 0) {
        (shiftRenderQueue.shift())();
    }
}

function manageGroups(e)
{
    location.href='groups.php?service_body_id=' + $("#service_body_id").val();
}

function addUserHandling(action)
{
    var rules = {
        name: {
            minlength: 5,
            required: true
        },
        username: {
            minlength: 5,
            required: true
        },
    };

    if (action === "save") {
        rules['password'] = {
            minlength: 10,
            required: true,
        }
    }

    $("#addUserForm").validate({
        rules: rules,
        highlight: function (element) {
            $(element).closest('.control-group').addClass('text-danger');
        },
        success: function (element) {
            element.closest('.control-group').removeClass('text-danger');
            saveUserData(action);
        }
    }).form();
}

function saveUserData(action)
{
    var userForm = $("#addUserForm");
    var formData = userForm.serializeArray();
    if (formData.getArrayItemByObjectKeyValue("name", "permissions") === undefined) {
        formData.push({"name":"permissions", "value":[]})
    }
    if (formData.getArrayItemByObjectKeyValue("name", "service_bodies") === undefined) {
        formData.push({"name":"service_bodies", "value":[]})
    }
    var dataObj = {};
    for (var formItem of formData) {
        dataObj[formItem["name"]] = userForm.find("#" + formItem["name"]).val();
    }

    $("#addUserModal").modal('hide');
    spinnerDialog(true, "Saving User...", function () {
        usersApi(dataObj, action,function () {
            spinnerDialog(false);
            location.reload();
        });
    });
}

function resetUsersValidation()
{
    var form = $("#addUserForm")
    if (form.data('validator')) {
        form.validate().destroy();
    }
    form.trigger("reset");
    $(".text-danger").removeClass('text-danger');
}

function showAddUsersModal()
{
    resetUsersValidation();
    adminOnlyFields(true, "Add User");
    $("#usersSaveButton").off('click').on('click', function () {
        addUserHandling("save");
    });
    $("#addUserModal").modal('show');
}

function adminOnlyFields(show, title)
{
    if (show) {
        $(".users_username").show();
        $(".users_permissions").show();
        $(".users_service_bodies").show();
    } else {
        $(".users_username").hide();
        $(".users_permissions").hide();
        $(".users_service_bodies").hide();
    }
    $(".users_modal_title").text(title);
}

function editUser(username, name, permissions, service_bodies, type)
{
    resetUsersValidation();
    $("#username").val(username);
    $("#name").val(name);
    if (type !== undefined && type === "profile") {
        adminOnlyFields(false, "Edit Profile");
    } else {
        adminOnlyFields(true, "Edit User");
        $.each(bitwiseSplit(permissions), function (i, e) {
            $("#permissions option[value='" + e + "']").prop("selected", true);
        });

        $.each(service_bodies.split(","), function (i, e) {
            $("#service_bodies option[value='" + e + "']").prop("selected", true);
        });
    }
    $("#usersSaveButton").off('click').on('click', function () {
        addUserHandling(type);
    });
    $("#addUserModal").modal('show');
}

function deleteUserHandling($username)
{
    if (confirm("Are you sure you want to delete this user?")) {
        spinnerDialog(true, "Deleting User...", function () {
            usersApi($username, "delete", function () {
                spinnerDialog(false);
                location.reload();
            });
        });
    }
}

function showGroupsModal()
{
    spinnerDialog(true, "Retrieving Groups...", function () {
        loadGroups($("#service_body_id").val(), function (data) {
            if (!$.isEmptyObject(data)) {
                $("#selected_group_id").find("option").remove();
                $("#selected_group_id").append(new Option("-= Select a Group =-", 0, true, true));
                for (item of data) {
                    var group_info = JSON.parse(item['data'])
                    $("#selected_group_id").append(new Option(group_info[0]['group_name'], item['id'], false, false));
                }

                spinnerDialog(false);
                $("#includeGroupDialog").modal('show');
            } else {
                spinnerDialog(false);
            }
        })
    });
}

function confirmIncludeGroup(e)
{
    includeGroup({"group_id":$("#selected_group_id").val()});
}

function includeGroup(groupData)
{
    var shiftRenderQueue = [];
    var cards = $("#volunteerCards").children();
    var getLastVolunteerCard = 0;
    for (var c = 0; c < cards.length; c++) {
        var currentId = parseInt($(cards[c]).attr("id").replace("volunteerCard_", ""));
        if (currentId > getLastVolunteerCard) {
            getLastVolunteerCard = currentId;
        }
    }

    var groupCardTemplate = $("#groupCardTemplate").clone();
    var volunteerId = "volunteerCard_" + (++getLastVolunteerCard);
    groupCardTemplate.attr("id", volunteerId);
    groupCardTemplate.find("#volunteerSequence").html(getLastVolunteerCard);
    groupCardTemplate.show();
    for (var key in groupData) {
        // Handle checkbox fields
        if (groupData[key]) {
            groupCardTemplate.find("#" + key).prop('checked', true);
        }

        groupCardTemplate.find("#" + key).val(groupData[key]);
    }

    if (groupData == null || !groupData.hasOwnProperty("group_enabled")) {
        groupCardTemplate.addClass("cardDisabled");
    }

    getGroupForId($("#service_body_id").val(), groupData['group_id'], function (data) {
        if (data !== null) {
            var groupInfo = JSON.parse(data['data']);
            groupCardTemplate.find("#group_name").html(groupInfo[0]['group_name'])
        }

        groupCardTemplate.appendTo("#volunteerCards");
        $("#includeGroupDialog").modal('hide');
    })
}

function removeCard(e)
{
    $(e).closest(".card").remove();
}

function checkboxStatusToggle(e)
{
    if (!e.checked) {
        $(e).closest(".card").addClass("cardDisabled");
    } else {
        $(e).closest(".card").removeClass('cardDisabled')
    }
    $(e).val(e.checked);
}

function renderShift(volunteerId, shiftInfoObj)
{
    if (shiftInfoObj !== null) {
        var shiftCardTemplate = $("#shiftCardTemplate").clone();
        var volunter_type = shiftInfoObj["type"] != null ? shiftInfoObj["type"] : "PHONE";
        shiftCardTemplate.find("#shiftDay").html(dayOfTheWeek[shiftInfoObj["day"]] + " (" + volunter_type + ")");
        shiftCardTemplate.attr("data", JSON.stringify(shiftInfoObj));
        shiftCardTemplate.find("#shiftInfo").html(shiftInfoObj["start_time"] + "-" + shiftInfoObj["end_time"] + " " + shiftInfoObj["tz"]);
        shiftCardTemplate.show();
        shiftCardTemplate.css({"display":"inline-block"});
        shiftCardTemplate.appendTo($("#" + volunteerId).find("#shiftsCards"))
    }
}

var wrapFunction = function (fn, context, params) {
    return function () {
        fn.apply(context, params);
    };
};

function addShift(e)
{
    $("#selectShiftDialog").on('shown.bs.modal', function (event) {
        $(".time_zone_selector").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
        $(event.target).find("#shiftVolunteerName").html($(e).closest(".volunteerCard").find("#volunteer_name").val());
        $("#selectShiftDialog").attr({
            "volunteer_id": $(e).closest(".volunteerCard").attr("id"),
            "day_id": $(e).attr("data-shiftid")
        });
        $("#day_of_the_week").prop("disabled", false);
        $(event.target).find("#shift_type").val("PHONE")
    });

    $("#selectShiftDialog").modal("show");
}

function add7DayShifts(e)
{
    $("#selectRepeatShiftDialog").on('shown.bs.modal', function (event) {
        $(".time_zone_selector").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
        $(event.target).find("#shiftVolunteerName").html($(e).closest(".volunteerCard").find("#volunteer_name").val());
        $("#selectRepeatShiftDialog").attr({
            "volunteer_id": $(e).closest(".volunteerCard").attr("id"),
            "day_id": $(e).attr("data-shiftid")
        });
    });

    $("#selectRepeatShiftDialog").modal("show");
}

function add24by7Shifts(e)
{
    $("#selectTimeZoneDialog").on('shown.bs.modal', function (event) {
        $(".time_zone_selector").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
        $(event.target).find("#shiftVolunteerName").html($(e).closest(".volunteerCard").find("#volunteer_name").val());
        $("#selectTimeZoneDialog").attr("data-volunteerid", $(e).closest(".volunteerCard").attr("id"));
    });

    $("#selectTimeZoneDialog").modal("show");
}

function selectTimeZoneFor247Shifts(e)
{
    var volunteerId = $(e).closest("#selectTimeZoneDialog").attr("data-volunteerid");
    var tz = $(e).closest("#selectTimeZoneDialog").find("#time_zone").val();
    if (tz === "" || tz === "null") {
        $("#selectTimeZoneDialogValidation").html("A time zone must be specified.").show().fadeOut(5000);
    } else {
        var type = $(e).closest("#selectTimeZoneDialog").find("#shift_type").val();
        for (var x = 1; x <= 7; x++) {
            var shiftInfoObj = {
                "day": x,
                "tz": tz,
                "start_time": '12:00 AM',
                "end_time": '11:59 PM',
                "type": type
            };

            renderShift(volunteerId, shiftInfoObj);
        }

        $("#selectTimeZoneDialog").modal("hide");
    }
}

function save7DayShifts(e)
{
    var start_time = $("#start_time_hour").val() + ":" + $("#start_time_minute").val() + " " + $("#start_time_division").val();
    var end_time = $("#end_time_hour").val() + ":" + $("#end_time_minute").val() + " " + $("#end_time_division").val();
    if ($(e).closest("#selectRepeatShiftDialog").find("#time_zone").val() === "" || $(e).closest("#selectRepeatShiftDialog").find("#time_zone").val() === "null") {
        $("#selectRepeatShiftDialogValidation").html("A time zone must be specified.").show().fadeOut(5000);
    } else if (Date.parse("01/01/2000 " + start_time) < Date.parse("01/01/2000 " + end_time)) {
        var volunteerId = $(e).closest("#selectRepeatShiftDialog").attr("volunteer_id");
        var tz = $(e).closest("#selectRepeatShiftDialog").find("#time_zone").val();
        var type = $(e).closest("#selectRepeatShiftDialog").find("#shift_type").val();
        for (var x = 1; x <= 7; x++) {
            var shiftInfoObj = {
                "day": x,
                "tz": tz,
                "start_time": start_time,
                "end_time": end_time,
                "type": type
            };

            renderShift(volunteerId, shiftInfoObj);
        }

        $("#selectRepeatShiftDialog").modal("hide");
    } else {
        $("#selectRepeatShiftDialogValidation").html("Shift start time should be before end time.").show().fadeOut(5000);
    }
}

function saveShift(e)
{
    var closestShiftDialog = $(e).closest("#selectShiftDialog");
    var start_time = $(closestShiftDialog).find("#start_time_hour").val() + ":" + $(closestShiftDialog).find("#start_time_minute").val() + " " + $(closestShiftDialog).find("#start_time_division").val();
    var end_time = $(closestShiftDialog).find("#end_time_hour").val() + ":" + $(closestShiftDialog).find("#end_time_minute").val() + " " + $(closestShiftDialog).find("#end_time_division").val();
    if ($(closestShiftDialog).find("#time_zone").val() === "" || $(closestShiftDialog).find("#time_zone").val() === "null") {
        $("#selectShiftDialogValidation").html("A time zone must be specified.").show().fadeOut(5000);
    } else if (Date.parse("01/01/2000 " + start_time) < Date.parse("01/01/2000 " + end_time)) {
        var volunteer_id = $("#selectShiftDialog").attr("volunteer_id");
        var day_id = $("#day_of_the_week").val();
        var time_zone_id = $(closestShiftDialog).find("#time_zone").val();
        var type = $(closestShiftDialog).find("#shift_type").val();
        var shiftInfoObj = {
            "day": day_id,
            "tz": time_zone_id,
            "start_time": start_time,
            "end_time": end_time,
            "type": type
        };

        var pointer = $(closestShiftDialog).attr("pointer");
        removeShift($(`button[pointer=${pointer}]`).closest(".shiftCard"))
        renderShift(volunteer_id, shiftInfoObj);
        $("#selectShiftDialog").modal("hide");
    } else {
        $("#selectShiftDialogValidation").html("Shift start time should be before end time.").show().fadeOut(5000);
    }
}

function generateRandomId() {
    return (Math.random() + 1).toString(36).substring(2);
}

function editShift(e)
{
    var randomId = generateRandomId()
    $(e).attr("pointer", randomId)
    $("#selectShiftDialog").on('shown.bs.modal', function (event) {
        $(".time_zone_selector").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
        $(event.target).find("#shiftVolunteerName").html($(e).closest(".volunteerCard").find("#volunteer_name").val());
        $(event.target).attr("pointer", randomId);
        var closestShiftCard = $(e).closest(".shiftCard");
        var data = JSON.parse($(closestShiftCard).attr("data"));
        $("#selectShiftDialog").attr({
            "volunteer_id": $(e).closest(".volunteerCard").attr("id"),
            "day_id": data.day
        });
        $("#day_of_the_week").val(data.day);
        $("#day_of_the_week").prop("disabled", true);
        var dialog = $("#selectShiftDialog");
        dialog.find("#time_zone").val(data.tz)
        dialog.find("#start_time_hour").val(data.start_time.split(":")[0])
        dialog.find("#start_time_minute").val(data.start_time.split(":")[1].split(" ")[0])
        dialog.find("#start_time_division").val(data.start_time.split(":")[1].split(" ")[1])
        dialog.find("#end_time_hour").val(data.end_time.split(":")[0])
        dialog.find("#end_time_minute").val(data.end_time.split(":")[1].split(" ")[0])
        dialog.find("#end_time_division").val(data.end_time.split(":")[1].split(" ")[1])
        var type = data.type !== "" && data.type !== undefined ? data.type : "PHONE"
        dialog.find("#shift_type").val(type)
    });

    $("#selectShiftDialog").modal("show");
}

function removeShift(e)
{
    $(e).closest(".shiftCard").remove();
}

function removeAllShifts(e)
{
    $(e).closest(".volunteerCard").find(".shiftCard").remove()
}

function toggleCardDetails(e)
{
    var volunteerCard = $(e).closest(".volunteerCard")
    var isCurrentlyShown = volunteerCard.find(".volunteerCardBody").attr("class").indexOf("show") >= 0;
    volunteerCard.find(".volunteerCardBodyToggleButton").html(isCurrentlyShown ? "+" : "-");
    volunteerCard.find(".volunteerCardBody").collapse('toggle');
}

function openServiceBodyConfigure(service_body_id)
{
    spinnerDialog(true, "Retrieving Service Body Configuration...", function () {
        var serviceBodyConfiguration = $("#serviceBodyConfiguration_" + service_body_id);
        var serviceBodyFields = $("#serviceBodyConfigurationFields");
        $.getJSON("../api/v1/config?service_body_id=" + service_body_id, function (data) {
            if (!$.isEmptyObject(data)) {
                clearServiceBodyFields(service_body_id);
                var dataSet = data['data'][0];
                for (var key in dataSet) {
                    if (key !== "serviceBodyConfigurationFields") {
                        addServiceBodyField(service_body_id, key);
                        serviceBodyConfiguration.find("#" + key).val(dataSet[key]);
                    }
                }
            }

            spinnerDialog(false, "", function () {
                serviceBodyConfiguration.modal("show");
            });
        });
    });
}

function addServiceBodyButtonClick(service_body_id)
{
    var configName = $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyConfigurationFields").val();
    addServiceBodyField(service_body_id, configName);
}

function addServiceBodyField(service_body_id, configName)
{
    if (configName != null) {
        var field = $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyConfigurationFields option[value='" + configName + "']")
        field.attr("disabled", "disabled");
        $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyFieldsPlaceholder").append("<div id=\"serviceBodyField_" + configName + "\" class=\"serviceBodyField\"><label for=\"" + configName + "\">" + configName + "</label><div class=\"serviceBodyFieldLine\"><input class=\"form-control form-control-sm serviceBodyFieldInput\" type=\"text\" name=\"" + configName + "\" id=\"" + configName + "\" value=\"" + field.attr("data-default") + "\"> <button class=\"btn btn-sm btn-primary removeFieldButton\" onclick=\"removeServiceBodyField(" + service_body_id + ",'" + configName + "')\">-</button></div></div>");
    }
}

function removeServiceBodyField(service_body_id, configName)
{
    $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyFieldsPlaceholder").find("#serviceBodyField_" + configName).remove();
    $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyConfigurationFields option[value='" + configName + "']").removeAttr("disabled");
}

function clearServiceBodyFields(service_body_id)
{
    $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyFieldsPlaceholder").html("");
}

function openServiceBodyCallHandling(service_body_id)
{
    spinnerDialog(true, "Retrieving Service Body Call Handling...", function () {
        var serviceBodyCallHandling = $("#serviceBodyCallHandling_" + service_body_id);
        $.getJSON("../api/v1/callHandling?service_body_id=" + service_body_id, function (data) {
            if (!$.isEmptyObject(data)) {
                var dataSet = data['data'][0];
                for (var key in dataSet) {
                    if (dataSet[key]) {
                        serviceBodyCallHandling.find("#" + key).prop('checked', true);
                    }
                    serviceBodyCallHandling.find("#" + key).val(dataSet[key]);
                }
            }

            serviceBodyCallHandling.find("select").change(function () {
                var trigger = this.id;
                for (var match of $("#serviceBodyCallHandling_" + service_body_id).find("[data-" + trigger + "]")) {
                    if ($(match).attr("data-" + trigger).split(",").indexOf(this.value) > -1) {
                        $(match).closest(".service_bodies_field_container").show();
                    } else {
                        $(match).closest(".service_bodies_field_container").hide();
                    }
                }

                if (trigger !== "call_strategy" && trigger !== "sms_strategy") {
                    serviceBodyCallHandling.find("#call_strategy").change();
                    serviceBodyCallHandling.find("#sms_strategy").change();
                }
            });

            serviceBodyCallHandling.find("select").change();

            spinnerDialog(false, "", function () {
                serviceBodyCallHandling.modal("show");
            });
        });
    });
}

function deleteVoicemail(callsid)
{
    spinnerDialog(true, "Marking voicemail as deleted...", function () {
        $.ajax({
            async: false,
            type: "POST",
            url: "../api/v1/events/status",
            data: {
                "callsid": callsid,
                "status": 1,
                "event_id": 4 // VOICEMAIL
            },
            complete: function (res) {
                if (res['status'] === 403) {
                    spinnerDialog(false);
                    var alert = $("#voicemail-deleted-alert");
                    alert.addClass("alert-danger");
                    alert.html(res['responseJSON']['error']);
                    alert.show();
                    alert.fadeOut(7000);
                } else {
                    location.reload();
                }
            },
            timeout: 60000
        });
    });

    return false;
}

function groupsPage()
{
    $("#group_id").on("change", function () {
        addNewVolunteerDialog(parseInt($(this).val()) > 0);
        clearVolunteerCards();
        if (parseInt($(this).val()) > 0) {
            spinnerDialog(true, "Retrieving Group Volunteers...", function () {
                loadGroupVolunteers($("#group_id").val(), function () {
                    $("#editGroupButton").show();
                    $("#deleteGroupButton").show();
                    spinnerDialog(false);
                })
            });
        } else {
            $("#editGroupButton").hide();
            $("#deleteGroupButton").hide();
        }
    });

    $("#volunteerCards").sortable({
        "handle":".volunteer-sort-icon"
    });

    for (var hr = 1; hr <= 12; hr++) {
        var hr_value = hr < 10 ? "0" + hr : hr.toString();
        $(".hours_field").append(new Option(hr_value, hr_value));
    }

    for (var min = 0; min <= 59; min++) {
        var min_value = min < 10 ? "0" + min : min.toString();
        $(".minutes_field").append(new Option(min_value, min_value));
    }
}

function addGroup()
{
    $("#group_dialog_message").html("");
    $("#groupEditorHeader").html("Add Group");
    $("#group_name").val("");
    $("#group_shared_service_bodies").val("");
    $("#addGroupDialog").modal('show');

    return false;
}

function deleteGroup()
{
    spinnerDialog(true, "Deleting Group...", function () {
        $.ajax({
            async: false,
            type: "DELETE",
            url: "../api/v1/config/" + $("#group_id").val(),
            contentType: "application/json",
            complete: function () {
                spinnerDialog(false);
                location.reload();
            },
            timeout: 60000
        });
    });

    return false;
}

function editGroup()
{
    $("#group_dialog_message").html("");
    $("#groupEditorHeader").html("Edit Group");
    $("#group_name").val($("#group_id option:selected").text());
    for (var group of groups) {
        if (group['id'] === $("#group_id").val()) {
            var data = JSON.parse(group.data)[0];
            if (data.hasOwnProperty("group_shared_service_bodies")) {
                $("#group_shared_service_bodies").val(data['group_shared_service_bodies']);
                break;
            }
        }
    }

    $("#addGroupDialog").modal('show');

    return false;
}

function confirmGroup()
{
    if ($("#group_name").val() == "") {
        $("#group_dialog_message").html("A name is required.");
        return false;
    }

    $("#addGroupDialog").modal('hide');
    spinnerDialog(true, "Saving Group...", function () {
        var formData = $("#groupEditor").serializeArray();
        var dataObj = {};
        for (var formItem of formData) {
            dataObj[formItem["name"]] = $("#groupEditor").find("#" + formItem["name"]).val();
        }

        saveGroups(
            $("#service_body_id").val(),
            dataObj,
            $("#groupEditorHeader").text().indexOf("Add") !== 0 ? $("#group_id").val() : 0,
            function (xhr, status) {
                var alert = $("#service_body_saved_alert");
                if (xhr.responseText === "{}" || xhr.status !== 200) {
                    alert.addClass("alert-danger");
                    alert.html("Could not save.");
                    $("#addGroupButton").show();
                    spinnerDialog(false);
                } else {
                    spinnerDialog(false, null, function () {
                        onGroupServiceBodyChange(function () {
                            var group_id = xhr.responseJSON["id"];
                            $("#group_id option").removeAttr("selected");
                            $("#group_id option[value='" + group_id + "']").attr("selected", "selected");
                            $("#group_id").trigger('change');

                            alert.addClass("alert-success");
                            alert.html("Saved.");
                            alert.show();
                            alert.fadeOut(3000);
                        })
                    });
                }
            }
        );
    });
}

function checkForConfigFile()
{
    jQuery.getJSON("upgrade-advisor?status-check", function (data) {
        if (!data['status'] && data['message'] !== null) {
            setErrorMessage(data['message'])
        } else {
            window.location.href = 'index.php';
        }
    });
}

function generateConfig(callback)
{
    var bmltRootServer = jQuery("#input_bmlt_root_server").val();
    jQuery.getJSON(bmltRootServer + "/client_interface/jsonp/?switcher=GetServerInfo&callback=?", function (data) {
        if (data == null) {
            setErrorMessage("Root server is incorrect or unavailable.");
            return;
        }

        var configHtml = "&lt;?php<br/>";
        if (parseFloat(data[0]['version']) >= parseFloat("2.14")) {
            var fields = jQuery('[id*="input_"]');
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i];
                configHtml += "static $" + field.id.replace("input_", "") + " = \"" + field.value + "\";<br/>";
            }
        }

        callback(configHtml);
    }).fail(function (error) {
        spinnerDialog(false);
        var alert = $("#wizardAlert");
        alert.html("Root server is incorrect or unavailable.");
        alert.show();
        alert.fadeOut(10000);
        $("#save-volunteers").removeClass('disabled');
    });
}

function setErrorMessage(message)
{
    jQuery("#config-error-message").html(message);
}

function initInstaller()
{
    jQuery(function () {
        jQuery(".wizardForm").on("submit", function (event) {
            event.preventDefault();
            spinnerDialog(true, "Validating configuration", function () {
                generateConfig(function (config) {
                    spinnerDialog(false, '', function () {
                        jQuery("#result").html(config);
                        $("#wizardResultModal").modal('show');
                        checkForConfigFileInterval = setInterval(checkForConfigFile, 3000);
                    });
                });
            });
        });
        jQuery("#wizardResultModal").on('hidden.bs.modal', function () {
            clearInterval(checkForConfigFileInterval);
        });
    });
}

function openUrl(e, id)
{
    window.open($("#" + id).val());
    return false;
}

function spinnerDialog(show, text, callback)
{
    var d = $("#spinnerDialog");
    if (show) {
        d.on('shown.bs.modal', function () {
            d.off();
            if (callback != undefined) {
                callback();
            }
        });
        d.find("#spinnerDialogText").text(text);
        d.modal('show');
    } else {
        d.on('hidden.bs.modal', function () {
            d.off();
            if (callback != undefined) {
                callback();
            }
        });
        setTimeout(function () {
            d.modal('hide');
        }, 500);
    }
}

function dataEncoder(dataObject)
{
    return btoa(JSON.stringify(dataObject));
}

function dataDecoder(dataString)
{
    return JSON.parse(atob(dataString));
}

function dec2bin(dec)
{
    return (dec >>> 0).toString(2);
}

function bin2dec(bin)
{
    return parseInt(bin, 2).toString(10);
}

function bitwiseSplit(x)
{
    let counter = 0;
    let seeds = [];
    let decimal_bits = [];
    let binary_string = "";

    while (true) {
        binary_string = "1" + binary_string.replace("1", "0");
        let decimal_equiv = bin2dec(binary_string)
        if (decimal_equiv <= x) {
            seeds.push(parseInt(decimal_equiv))
        } else {
            break;
        }
    }

    while (x > 0) {
        if ((x & seeds[counter]) === seeds[counter]) {
            decimal_bits.push(seeds[counter])
            x -= seeds[counter];
        }
        counter++;
    }

    return decimal_bits;
}

function toCurrentTimezone(dateTime)
{
    return moment(dateTime, "YYYY-MM-DD hh:mm:ssZ").local().format("YYYY-MM-DD HH:mm:ssZ");
}

function loadCssFile(cssFilePath)
{
    $('head').append(
        $('<link>', {
            rel:  'stylesheet',
            type: 'text/css',
            href: cssFilePath
        })
    );
}

function loadTabulatorTheme()
{
    $(function () {
        loadCssFile($("body").attr("data-theme") === "dark" ? darkTheme : lightTheme)
    });
}

Object.prototype.hasOwnProperty = function (property) {
    return this[property] !== undefined;
};

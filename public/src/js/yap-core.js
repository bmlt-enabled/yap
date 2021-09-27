dayOfTheWeek = {1:"Sunday",2:"Monday",3:"Tuesday",4:"Wednesday",5:"Thursday",6:"Friday",7:"Saturday"};
var groups;
var calendar;
var metrics_map = null;

Array.prototype.getArrayItemByObjectKeyValue = function(key, value) {
    for (var i = 0; i < this.length; i++) {
        if (this[i][key] === value) {
            return this[i];
        }
    }
};

function recurseReports() {
    return $("#recursive-reports-switch:checked").length > 0;
}

function initReports() {
    $(".page-size-dropdown-item").click(function(e) {
        $(".page-size-dropdown-item").removeClass("active");
        $(e.target).addClass("active");
        var pageSize = parseInt(e.target.text);
        table.setPageSize(pageSize);
    });

    $("#print-table").on("click", function(){
        table.print(false, true);
    });

    $("#download-records-csv").click(function(){
        table.download("csv", "yap-records.csv");
    });

    $("#download-events-csv").click(function(){
        eventsTable.download("csv", "yap-events.csv");
    });

    $("#download-json").click(function(){
        table.download("json", "yap.json");
    });

    $("#download-xlsx").click(function() {
        var sheets = {
            "Calls": true,
            "Events": "#events-table"
        };

        table.download("xlsx", "data.xlsx", {sheets:sheets});
    });

    var eventsTableColumns = [
        {title: "Event Time", field: "event_time", mutator: toCurrentTimezone},
        {title: "Event", field: "event_id"},
        {title: "Service Body Id", field: "service_body_id", mutator: function(id) {
            if (isNaN(id)) return id;
            var service_body = getServiceBodyById(id);
            return service_body['name'] + " (" + service_body['id'] + ")"
        }},
        {title: "Metadata", field: "meta"},
        {title: "Parent CallSid", field: "parent_callsid", visible: false, download: true}
    ];

    var table = new Tabulator("#cdr-table", {
        layout: "fitColumns",
        responsiveLayout: "hide",
        tooltips: true,
        addRowPos: "top",
        history: true,
        pagination: "remote",
        paginationSize: 50,
        ajaxURL: "cdr_api.php",
        ajaxURLGenerator: function(url, config, params) {
            return url + "?service_body_id=" + $("#service_body_id").val() + "&page=" + params['page'] + "&size=" + params['size'] + "&recurse=" + recurseReports();
        },
        ajaxResponse: function(url, params, response) {
            var events = [];
            for (var i = 0; i < response['data'].length; i++) {
                var callEvents = response['data'][i]['call_events'];
                for (var j = 0; j < callEvents.length; j++) {
                    var callEvent = callEvents[j];
                    events.push(callEvent);
                }
            }

            $(".subTableHolder").toggle();

            eventsTable.setData(events);
            return response;
        },
        pageLoaded: function(pageno) {
            $(".subTableHolder").hide();
        },
        movableColumns: true,
        resizableRows: true,
        printAsHtml: true,
        printHeader: "<h3>Call Detail Records<h3>",
        printFooter: "",
        rowClick: function(e, row) {
            $("#subTableId_" + row.getData().id).toggle();
        },
        initialSort: [
            {column:"start_time", dir:"desc"},
        ],
        columns: [
            {title:"Start Time", field:"start_time", mutator: toCurrentTimezone },
            {title:"End Time", field:"end_time", mutator: toCurrentTimezone },
            {title:"Duration (seconds)", field:"duration"},
            {title:"From", field:"from_number"},
            {title:"To", field:"to_number"},
            {title:"Call Events", field:"call_events", visible: false, download: true, formatter: function(cell, formatterParams, onRendered) {
                    return JSON.stringify(cell.getValue());
                }}
        ],
        rowFormatter: function(row) {
            //create and style holder elements
            var holderEl = document.createElement("div");
            var tableEl = document.createElement("div");

            holderEl.style.boxSizing = "border-box";
            holderEl.style.padding = "10px 30px 10px 10px";
            holderEl.style.borderTop = "1px solid #333";
            holderEl.style.borderBotom = "1px solid #333";
            holderEl.style.background = "#ddd";
            holderEl.setAttribute('class', 'subTableHolder');
            holderEl.setAttribute('id', 'subTableId_' + row.getData().id);
            tableEl.style.border = "1px solid #333";
            tableEl.setAttribute('class', 'eventsSubtable');
            holderEl.appendChild(tableEl);
            row.getElement().appendChild(holderEl);

            var subTable = new Tabulator(tableEl, {
                layout: "fitColumns",
                data: row.getData().call_events,
                columns: eventsTableColumns
            });
        }
    });

    var eventsTable = new Tabulator("#events-table", {
        columns: eventsTableColumns,
        initialSort:[
            {column:"event_time", dir:"desc"},
        ],
    });

    return table;
}

function getMetricsData() {
    $("#metrics").slideToggle(function() {
        $.getJSON("metric_api.php?service_body_id=" + $("#service_body_id").val() + "&recurse=" + recurseReports(), function (data) {
            var actions = ['Volunteer', 'Meetings', 'Just For Today'];
            var plots = {"1": [], "2": [], "3": []};
            for (var item of data) {
                plots[JSON.parse(item['data'])['searchType']].push({
                    'x': item['timestamp'],
                    'y': item['counts']
                });
            }

            var datasets = [];
            var colors = ['#FF6600', '#87B63A', 'indigo'];
            for (var a = 0; a < actions.length; a++) {
                var xAgg = [];
                var yAgg = [];
                if (plots[a + 1] !== undefined) {
                    for (var p = 0; p < plots[a + 1].length; p++) {
                        xAgg.push(plots[a + 1][p].x);
                        yAgg.push(plots[a + 1][p].y);
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

            $("#metrics").slideToggle(function() {
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

function drawMetricsMap() {
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
    $.getJSON('map_metric_api.php?service_body_id=' + $("#service_body_id").val() + "&recurse=" + recurseReports(), function (data) {
        var bounds = [];

        for (var i = 0; i < data.length; i++) {
            var location = JSON.parse(data[i]['meta'])['coordinates'];
            var content = location['location'];
            var myIcon = L.icon({
                iconUrl: parseInt(data[i]['event_id']) === 1 ? volunteersMarker : meetingsMarker,
                iconSize: [32, 32],
            });

            var latLng = [location['latitude'], location['longitude']];
            var marker = L.marker(latLng, {icon: myIcon, title: content}).addTo(metrics_map);
            marker.bindPopup(content);
            bounds.push(latLng);
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
    });
}

function updateCallRecords(table) {
    table.setData();
}

function updateAllReports(table) {
    getMetricsData();
    updateCallRecords(table);
    drawMetricsMap();
    $("#metrics-json").attr("href", "metric_api.php?service_body_id=" + $("#service_body_id").val() + "&recurse=" + recurseReports());
    $("#map-metrics-json").attr("href", "map_metric_api.php?service_body_id=" + $("#service_body_id").val() + "&recurse=" + recurseReports());
}

function getMetrics(table) {
    $("#service_body_id").on("change", function(e) {
        updateAllReports(table);
    });

    $("#recursive-reports-switch").on("change", function(e) {
        updateAllReports(table);
    });

    $("#refresh-button").on("click", function() {
        getMetricsData();
        updateCallRecords(table);
    });

    getMetricsData();
}

function volunteerPage() {
    $(function() {
        if ($('select#service_body_id option').length === 2) {
            $('#service_body_id option:nth-child(2)').prop('selected', true)
            $('#service_body_id').change();
        }
    });

    $("#service_body_id").on("change", function() {
        addNewVolunteerDialog($(this).val() > 0);
        addGroupVolunteersDialog($(this).val() >= 0);
        clearVolunteerCards();
        if ($(this).val() > 0) {
            var service_body_id = $(this).val();
            spinnerDialog(true, "Retrieving Volunteers...", function () {
                loadGroups(service_body_id, function() {
                    loadVolunteers(service_body_id, function () {
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

function schedulePage() {
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
        eventAllow: function(dropLocation, draggedEvent) {
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

    $(function() {
        if ($('select#service_body_id option').length === 2) {
            $('#service_body_id option:nth-child(2)').prop('selected', true)
            $('#service_body_id').change();
        }
    });

    $('select#service_body_id').change(function() {
        if (parseInt($('select#service_body_id').val()) > 0) {
            for (eventSource of calendar.getEventSources()) {
                eventSource.remove();
            }
            calendar.addEventSource('helpline-schedule.php?service_body_id=' + $('select#service_body_id').val());
        }
    })
}

function includeVolunteers() {
    includeVolunteer({"volunteer_name": ""});
}

function saveVolunteers(data_type) {
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

function saveServiceBodyConfig(service_body_id) {
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
            function(xhr, status) {
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

function saveServiceBodyCallHandling(service_body_id) {
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
            function(xhr, status) {
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

function saveToAdminApi(service_body_id, data, data_type, parent_id, id, callback) {
    $.ajax({
        async: false,
        type: "POST",
        url: "api.php?action=save"
            + "&service_body_id=" + service_body_id
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

function usersApi(data, action, callback) {
    $.ajax({
        async: false,
        type: "POST",
        url: "users_api.php?action=" + action,
        data: JSON.stringify(data),
        dataType: "json",
        contentType: "application/json",
        complete: callback,
        timeout: 60000
    });
}

function loadFromAdminApi(parent_id, service_body_id, data_type, callback) {
    $.getJSON("api.php?service_body_id=" + service_body_id
        + "&data_type=" + data_type
        + (parent_id !== null ? "&parent_id=" + parent_id : ""), function(data) {
        callback(data)
    });
}

function addNewVolunteerDialog(isVisible) {
    isVisible ? $("#newVolunteerDialog").show() : $("#newVolunteerDialog").hide();
}

function addGroupVolunteersDialog(isVisible) {
    if (isVisible) {
        $("#include-group").show();
        $("#manage-groups").show();
    } else {
        $("#include-group").hide();
        $("#manage-groups").hide();
    }
}

function clearVolunteerCards() {
    $("#volunteerCards").children().remove()
}

function loadVolunteers(serviceBodyId, callback) {
    loadFromAdminApi(null, serviceBodyId, '_YAP_VOLUNTEERS_V2_', function(data) {
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

function loadGroupVolunteers(parent_id, service_body_id, callback) {
    loadFromAdminApi($("#group_id").val(), service_body_id,'_YAP_GROUP_VOLUNTEERS_V2_', function(data) {
        if (!$.isEmptyObject(data)) {
            for (item of data['data']) {
                includeVolunteer(item);
            }
        }
        callback();
    });
}

function onGroupServiceBodyChange(e) {
    if (parseInt(e.value) !== 0) {
        document.getElementById("loadGroupsButton").removeAttribute('disabled')
    } else {
        document.getElementById("loadGroupsButton").setAttribute('disabled', '')
    }
}

function loadGroups(service_body_id, callback) {
    if (groups === undefined) {
        $.getJSON("groups_api.php?service_body_id=" + service_body_id, function (data) {
            groups = data;
            callback(data)
        });
    } else {
        callback(groups);
    }
}

function getGroupForId(service_body_id, group_id, callback) {
    loadGroups(service_body_id, function(data) {
        for (item of data) {
            if (item['id'] === group_id) {
                callback(item);
            }
        }

        callback(null);
    });
}

function includeVolunteer(volunteerData) {
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

function manageGroups(e) {
    location.href='groups.php?service_body_id=' + $("#service_body_id").val();
}

function addUserHandling(action) {
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

function saveUserData(action) {
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
        usersApi(dataObj, action,function() {
            spinnerDialog(false);
            location.reload();
        });
    });
}

function resetUsersValidation() {
    var form = $("#addUserForm")
    if (form.data('validator')) {
        form.validate().destroy();
    }
    form.trigger("reset");
    $(".text-danger").removeClass('text-danger');
}

function showAddUsersModal() {
    resetUsersValidation();
    adminOnlyFields(true, "Add User");
    $("#usersSaveButton").off('click').on('click', function() {
        addUserHandling("save");
    });
    $("#addUserModal").modal('show');
}

function adminOnlyFields(show, title) {
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

function editUser(id, username, name, permissions, service_bodies, type) {
    resetUsersValidation();
    $("#id").val(id);
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
    $("#usersSaveButton").off('click').on('click', function() {
        addUserHandling(type);
    });
    $("#addUserModal").modal('show');
}

function deleteUserHandling(id) {
    if (confirm("Are you sure you want to delete this user?")) {
        spinnerDialog(true, "Deleting User...", function () {
            usersApi({id: id}, "delete", function () {
                spinnerDialog(false);
                location.reload();
            });
        });
    }
}

function showGroupsModal() {
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

function confirmIncludeGroup(e) {
    includeGroup({"group_id":$("#selected_group_id").val()});
}

function includeGroup(groupData) {
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

function removeCard(e) {
    $(e).closest(".card").remove();
}

function checkboxStatusToggle(e) {
    if (!e.checked) {
        $(e).closest(".card").addClass("cardDisabled");
    } else {
        $(e).closest(".card").removeClass('cardDisabled')
    }
    $(e).val(e.checked);
}

function renderShift(volunteerId, shiftInfoObj) {
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

var wrapFunction = function(fn, context, params) {
    return function() {
        fn.apply(context, params);
    };
};

function addShift(e) {
    $(".time_zone_selector").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
    $("#shiftVolunteerName").html($(e).closest(".volunteerCard").find("#volunteer_name").val());
    $("#selectShiftDialog").attr({
        "volunteer_id": $(e).closest(".volunteerCard").attr("id"),
        "day_id": $(e).attr("data-shiftid")
    });
    $("#selectShiftDialog").modal("show");
}

function add7DayShifts(e) {
    $(".time_zone_selector").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
    $("#shiftVolunteerName").html($(e).closest(".volunteerCard").find("#volunteer_name").val());
    $("#selectRepeatShiftDialog").attr({
        "volunteer_id": $(e).closest(".volunteerCard").attr("id"),
        "day_id": $(e).attr("data-shiftid")
    });
    $("#selectRepeatShiftDialog").modal("show");
}

function add24by7Shifts(e) {
    $(".time_zone_selector").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
    $("#selectTimeZoneDialog").attr("data-volunteerid", $(e).closest(".volunteerCard").attr("id"));
    $("#selectTimeZoneDialog").modal("show");
}

function selectTimeZoneFor247Shifts(e) {
    var volunteerId = $(e).closest("#selectTimeZoneDialog").attr("data-volunteerid");
    var tz = $(e).closest("#selectTimeZoneDialog").find("#time_zone").val();
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

function save7DayShifts(e) {
    var start_time = $("#start_time_hour").val() + ":" + $("#start_time_minute").val() + " " + $("#start_time_division").val();
    var end_time = $("#end_time_hour").val() + ":" + $("#end_time_minute").val() + " " + $("#end_time_division").val();
    if (Date.parse("01/01/2000 " + start_time) < Date.parse("01/01/2000 " + end_time)) {
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

function saveShift(e) {
    var closestShiftDialog = $(e).closest("#selectShiftDialog");
    var start_time = $(closestShiftDialog).find("#start_time_hour").val() + ":" + $(closestShiftDialog).find("#start_time_minute").val() + " " + $(closestShiftDialog).find("#start_time_division").val();
    var end_time = $(closestShiftDialog).find("#end_time_hour").val() + ":" + $(closestShiftDialog).find("#end_time_minute").val() + " " + $(closestShiftDialog).find("#end_time_division").val();
    if (Date.parse("01/01/2000 " + start_time) < Date.parse("01/01/2000 " + end_time)) {
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

        renderShift(volunteer_id, shiftInfoObj);
        $("#selectShiftDialog").modal("hide");
    } else {
        $("#selectShiftDialogValidation").html("Shift start time should be before end time.").show().fadeOut(5000);
    }
}

function removeShift(e) {
    $(e).closest(".shiftCard").remove();
}

function removeAllShifts(e) {
    $(e).closest(".volunteerCard").find(".shiftCard").remove()
}

function toggleCardDetails(e) {
    var volunteerCard = $(e).closest(".volunteerCard")
    var isCurrentlyShown = volunteerCard.find(".volunteerCardBody").attr("class").indexOf("show") >= 0;
    volunteerCard.find(".volunteerCardBodyToggleButton").html(isCurrentlyShown ? "+" : "-");
    volunteerCard.find(".volunteerCardBody").collapse('toggle');
}

function openServiceBodyConfigure(service_body_id) {
    spinnerDialog(true, "Retrieving Service Body Configuration...", function() {
        var serviceBodyConfiguration = $("#serviceBodyConfiguration_" + service_body_id);
        var serviceBodyFields = $("#serviceBodyConfigurationFields");
        loadFromAdminApi(null, service_body_id, '_YAP_CONFIG_V2_', function(data) {
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

            spinnerDialog(false, "", function() {
                serviceBodyConfiguration.modal("show");
            });
        });
    });
}

function addServiceBodyButtonClick(service_body_id) {
    var configName = $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyConfigurationFields").val();
    addServiceBodyField(service_body_id, configName);
}

function addServiceBodyField(service_body_id, configName) {
    if (configName != null) {
        var field = $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyConfigurationFields option[value='" + configName + "']")
        field.attr("disabled", "disabled");
        $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyFieldsPlaceholder").append("<div id=\"serviceBodyField_" + configName + "\" class=\"serviceBodyField\"><label for=\"" + configName + "\">" + configName + "</label><div class=\"serviceBodyFieldLine\"><input class=\"form-control form-control-sm serviceBodyFieldInput\" type=\"text\" name=\"" + configName + "\" id=\"" + configName + "\" value=\"" + field.attr("data-default") + "\"> <button class=\"btn btn-sm btn-primary removeFieldButton\" onclick=\"removeServiceBodyField(" + service_body_id + ",'" + configName + "')\">-</button></div></div>");
    }
}

function removeServiceBodyField(service_body_id, configName) {
    $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyFieldsPlaceholder").find("#serviceBodyField_" + configName).remove();
    $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyConfigurationFields option[value='" + configName + "']").removeAttr("disabled");
}

function clearServiceBodyFields(service_body_id) {
    $("#serviceBodyConfiguration_" + service_body_id).find("#serviceBodyFieldsPlaceholder").html("");
}

function openServiceBodyCallHandling(service_body_id) {
    spinnerDialog(true, "Retrieving Service Body Call Handling...", function() {
        var serviceBodyCallHandling = $("#serviceBodyCallHandling_" + service_body_id);
        loadFromAdminApi(null, service_body_id, '_YAP_CALL_HANDLING_V2_', function(data) {
            if (!$.isEmptyObject(data)) {
                var dataSet = data['data'][0];
                for (var key in dataSet) {
                    if (dataSet[key]) {
                        serviceBodyCallHandling.find("#" + key).prop('checked', true);
                    }
                    serviceBodyCallHandling.find("#" + key).val(dataSet[key]);
                }
            }

            serviceBodyCallHandling.find("select").change(function() {
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

            spinnerDialog(false, "", function() {
                serviceBodyCallHandling.modal("show");
            });
        });
    });
}

function deleteVoicemail(callsid, callback) {
    $.ajax({
        async: false,
        type: "POST",
        url: "../v1/events/status",
        data: {
            "callsid": callsid,
            "status": 1,
        },
        complete: callback,
        timeout: 60000
    });

    return false;
}

function groupsPage() {
    $("#group_id").on("change", function() {
        addNewVolunteerDialog($(this).val() >= 0);
        clearVolunteerCards();
        if ($(this).val() >= 0) {
            spinnerDialog(true, "Retrieving Group Volunteers...", function () {
                loadGroupVolunteers($("#group_id").val(), $("#service_body_id").val(), function () {
                    $("#editGroupButton").show();
                    spinnerDialog(false);
                })
            });
        } else {
            $("#editGroupButton").hide();
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

function addGroup() {
    $("#group_dialog_message").html("");
    $("#groupEditorHeader").html("Add Group");
    $("#group_name").val("");
    $("#group_shared_service_bodies").val("");
    $("#addGroupDialog").modal('show');
}

function editGroup() {
    $("#group_dialog_message").html("");
    $("#groupEditorHeader").html("Edit Group");

    $("#group_name").val($("#group_id option:selected").text());
    for (group of groups) {
        if (group['id'] == $("#group_id").val()) {
            $("#group_shared_service_bodies").val(JSON.parse(group['shares']));
            break;
        }
    }

    $("#addGroupDialog").modal('show');
}

function confirmGroup() {
    if ($("#group_name").val() == "") {
        $("#group_dialog_message").html("A name is required.");
    }

    $("#addGroupDialog").modal('hide');
    spinnerDialog(true, "Saving Group...", function () {
        var formData = $("#groupEditor").serializeArray();
        var dataObj = {};
        for (var formItem of formData) {
            dataObj[formItem["name"]] = $("#groupEditor").find("#" + formItem["name"]).val();
        }

        saveToAdminApi(
            $("#service_body_id").val(),
            [dataObj],
            '_YAP_GROUPS_V2_',
            0,
            $("#group_id").val(),
            function(xhr, status) {
                var alert = $("#service_body_saved_alert");
                if (xhr.responseText === "{}" || xhr.status !== 200) {
                    alert.addClass("alert-danger");
                    alert.html("Could not save.");
                    $("#addGroupButton").show();
                } else {
                    var new_group_id = xhr.responseJSON['id'];
                    if (new_group_id === $("#group_id").val()) {
                        $("#group_id option:selected").text(dataObj["group_name"]);
                    } else {
                        $("#group_id").append(new Option($("#group_name").val(), new_group_id, true, true));
                        $("#group_id").trigger('change');
                    }
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

function checkForConfigFile() {
    jQuery.getJSON("../upgrade-advisor.php?status-check", function (data) {
        if (!data['status'] && data['message'] !== null) {
            setErrorMessage(data['message'])
        } else {
            window.location.href = 'index.php';
        }
    });
}

function generateConfig(callback) {
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
    }).fail(function(error) {
        spinnerDialog(false);
        var alert = $("#wizardAlert");
        alert.html("Root server is incorrect or unavailable.");
        alert.show();
        alert.fadeOut(10000);
        $("#save-volunteers").removeClass('disabled');
    });
}

function setErrorMessage(message) {
    jQuery("#config-error-message").html(message);
}

function initInstaller() {
    jQuery(function() {
        jQuery(".wizardForm").on("submit", function(event) {
            event.preventDefault();
            spinnerDialog(true, "Validating configuration", function() {
                generateConfig(function(config) {
                    spinnerDialog(false, '', function() {
                        jQuery("#result").html(config);
                        $("#wizardResultModal").modal('show');
                        checkForConfigFileInterval = setInterval(checkForConfigFile, 3000);
                    });
                });
            });
        });
        jQuery("#wizardResultModal").on('hidden.bs.modal', function() {
            clearInterval(checkForConfigFileInterval);
        });
    });
}

function openUrl(e, id) {
    window.open($("#" + id).val());
    return false;
}

function spinnerDialog(show, text, callback) {
    var d = $("#spinnerDialog");
    if (show) {
        d.on('shown.bs.modal', function() {
            d.off();
            if (callback != undefined) callback();
        });
        d.find("#spinnerDialogText").text(text);
        d.modal('show');
    } else {
        d.on('hidden.bs.modal', function() {
            d.off();
            if (callback != undefined) callback();
        });
        setTimeout(function() {
            d.modal('hide');
        }, 500);
    }
}

function dataEncoder(dataObject) {
    return btoa(JSON.stringify(dataObject));
}

function dataDecoder(dataString) {
    return JSON.parse(atob(dataString));
}

function dec2bin(dec) {
    return (dec >>> 0).toString(2);
}

function bin2dec(bin) {
    return parseInt(bin, 2).toString(10);
}

function bitwiseSplit(x) {
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

function toCurrentTimezone(dateTime) {
    return moment(dateTime, "YYYY-MM-DD hh:mm:ssZ").local().format("YYYY-MM-DD HH:mm:ssZ");
}

function loadCssFile(cssFilePath) {
    $('head').append(
        $('<link>', {
            rel:  'stylesheet',
            type: 'text/css',
            href: cssFilePath
        })
    );
}

function loadTabulatorTheme() {
    $(function() {
        loadCssFile($("body").attr("data-theme") === "dark" ? darkTheme : lightTheme)
    });
}

Object.prototype.hasOwnProperty = function(property) {
    return this[property] !== undefined;
};

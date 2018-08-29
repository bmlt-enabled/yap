dayOfTheWeek = {1:"Sunday",2:"Monday",3:"Tuesday",4:"Wednesday",5:"Thursday",6:"Friday",7:"Saturday"};

function volunteerPage() {
    $("#service_body_id").on("change", function() {
        addNewVolunteerDialog($(this).val() > 0);
        clearVolunteerCards();
        if ($(this).val() > 0) {
            var helpline_data_id = $(this).val();
            spinnerDialog(true, "Retrieving Volunteers...", function () {
                loadVolunteers(helpline_data_id, function () {
                    spinnerDialog(false);
                })
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
    $('#calendar').fullCalendar({
        allDaySlot: false,
        defaultView: 'agendaWeek',
        nowIndicator: true,
        firstDay: (new Date()).getDay(),
        themeSystem: 'bootstrap4',
        header: {
            left: null,
            center: null,
            right: "agendaWeek, agendaDay, prev, next"
        },
        height: 'auto',
        validRange: {
            start: moment().startOf('day').format("YYYY-MM-DD"),
            end: moment().add(7, 'days').endOf('day').format("YYYY-MM-DD")
        },
        viewRender: function() {
            $(".fa-chevron-left").html("<");
            $(".fa-chevron-right").html(">");
        }
    });

    $('select#service_body_id').change(function() {
        if (parseInt($('select#service_body_id').val()) > 0) {
            $('#calendar').fullCalendar('removeEventSources');
            $("#calendar").fullCalendar('removeEvents');
            $('#calendar').fullCalendar('addEventSource', '../helpline-schedule.php?service_body_id=' + $('select#service_body_id').val());
        }
    })
}

function addVolunteers() {
    addVolunteer({"volunteer_name": ""});
}

function saveVolunteers() {
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
                dataObj[formItem["name"]] = formItem["value"]
            }

            data.push(dataObj);
        }

        saveToAdminApi(
            $("#service_body_id").val(),
            $("#helpline_data_id").val(),
            data,
            "_YAP_DATA_",
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
    var helpline_data_id = serviceBodyConfiguration.find(".helpline_data_id").val();
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
            helpline_data_id,
            data,
            '_YAP_CONFIG_',
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

function loadServiceBodyConfig(serviceBodyId, callback) {
    loadFromAdminApi(serviceBodyId, '_YAP_CONFIG_', function(data) {
        callback(data);
    });
}

function saveToAdminApi(service_body_id, helpline_data_id, data, data_type, callback) {
    $.ajax({
        async: false,
        type: "POST",
        url: "api.php?action=save&helpline_data_id=" + helpline_data_id
        + "&service_body_id=" + service_body_id + "&data_type=" + data_type,
        data: JSON.stringify({"data": data}),
        dataType: "json",
        contentType: "application/json",
        complete: callback,
        timeout: 60000
    });
}

function loadFromAdminApi(service_body_id, data_type, callback) {
    $.getJSON("api.php?service_body_id=" + service_body_id + "&data_type=" + data_type, function(data) {
        callback(data)
    });
}

function addNewVolunteerDialog(isVisible) {
    isVisible ? $("#newVolunteerDialog").show() : $("#newVolunteerDialog").hide();
}

function clearVolunteerCards() {
    $("#volunteerCards").children().remove()
}

function loadVolunteers(serviceBodyId, callback) {
    loadFromAdminApi(serviceBodyId, '_YAP_DATA_', function(data) {
        var helpline_data_id = 0;
        if (!$.isEmptyObject(data)) {
            helpline_data_id = data["id"];
            for (item of data["data"]) {
                addVolunteer(item)
            }
        }
        $("#helpline_data_id").val(helpline_data_id);
        callback();
    });
}

function addVolunteer(volunteerData) {
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

    volunteerCardTemplate.appendTo("#volunteerCards");
    while (shiftRenderQueue.length > 0) {
        (shiftRenderQueue.shift())();
    }
}

function removeVolunteer(e) {
    $(e).closest(".volunteerCard").remove();
}

function checkboxStatusToggle(e) {
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

function add24by7Shifts(e) {
    $(".time_zone_selector").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
    $("#selectTimeZoneDialog").attr("data-volunteerid", $(e).closest(".volunteerCard").attr("id"));
    $("#selectTimeZoneDialog").modal("show");
}

function selectTimeZoneFor247Shifts(e) {
    var volunteerId = $(e).closest("#selectTimeZoneDialog").attr("data-volunteerid");
    var tz = $(e).closest("#selectTimeZoneDialog").find("#time_zone").val();
    var type = $(e).closest("#selectTimeZoneDialog").find("#type").val();
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

function saveShift(e) {
    var volunteer_id = $("#selectShiftDialog").attr("volunteer_id");
    var day_id = $("#day_of_the_week").val();
    var time_zone_id = $("#time_zone").val();
    var start_time = $("#start_time_hour").val() + ":" + $("#start_time_minute").val() + " " + $("#start_time_division").val();
    var end_time = $("#end_time_hour").val() + ":" + $("#end_time_minute").val() + " " + $("#end_time_division").val();
    var type = $("#type").val();
    var shiftInfoObj = {
        "day": day_id,
        "tz": time_zone_id,
        "start_time": start_time,
        "end_time": end_time,
        "type": type
    };

    renderShift(volunteer_id, shiftInfoObj);
    $("#selectShiftDialog").modal("hide");
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

function serviceBodyConfigure(service_body_id) {
    spinnerDialog(true, "Retrieving Service Body Configuration...", function() {
        var serviceBodyConfiguration = $("#serviceBodyConfiguration_" + service_body_id);
        loadServiceBodyConfig(service_body_id, function(data) {
            if (!$.isEmptyObject(data)) {
                serviceBodyConfiguration.find("#helpline_data_id").val(data["id"]);
                var dataSet = data["data"][0];
                for (var key in dataSet) {
                    if (dataSet[key]) {
                        serviceBodyConfiguration.find("#" + key).prop('checked', true);
                    }
                    serviceBodyConfiguration.find("#" + key).val(dataSet[key]);
                }
            }

            spinnerDialog(false, "", function() {
                serviceBodyConfiguration.modal("show");
            });
        });
    });
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

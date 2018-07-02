dayOfTheWeek = {1:"Sunday",2:"Monday",3:"Tuesday",4:"Wednesday",5:"Thursday",6:"Friday",7:"Saturday"};

function volunteerPage() {
    $("#service_body_id").on("change", function() {
        $("#spinnerDialog").modal('show');
        addNewVolunteerDialog($(this).val() > 0);
        clearVolunteerCards();
        loadVolunteers($(this).val(), function() {
            setTimeout(function() {
                $("#spinnerDialog").modal('hide');
            }, 500);
        })
    });

    $("#volunteerCards").sortable();

    for (var hr = 1; hr <= 12; hr++) {
        var hr_value = hr < 10 ? "0" + hr : hr.toString();
        $(".hours_field").append(new Option(hr_value, hr_value));
    }

    for (var min = 0; min <= 59; min++) {
        var min_value = min < 10 ? "0" + min : min.toString();
        $(".minutes_field").append(new Option(min_value, min_value));
    }
}

function addVolunteers() {
    addVolunteer({
        "volunteer_name": $("#new_volunteer_name").val()
    });

    $("#new_volunteer_name").val("");
}

function saveVolunteers() {
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
        "_YAP_DATA_"
    );

    // TODO: it's not actually do a callback (got stuck earlier on the process)
    $("#volunteer_saved_alert").show();
    $("#volunteer_saved_alert").fadeOut(3000);
}

function saveServiceBodyConfig(service_body_id) {
    var data = [];
    var serviceBodyConfiguration = $("#serviceBodyConfiguration_" + service_body_id);
    var helpline_data_id = serviceBodyConfiguration.find(".helpline_data_id").val();
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
        '_YAP_CONFIG_'
    );

    // TODO: it's not actually do a callback (got stuck earlier on the process)
    $("#service_body_saved_alert").show();
    $("#service_body_saved_alert").fadeOut(3000);
    serviceBodyConfiguration.modal('hide');
}

function loadServiceBodyConfig(serviceBodyId, callback) {
    loadFromAdminApi(serviceBodyId, '_YAP_CONFIG_', function(data) {
        callback(data);
    });
}

function saveToAdminApi(service_body_id, helpline_data_id, data, data_type) {
    $.ajax({
        async: true,
        type: "POST",
        url: "api.php?action=save&helpline_data_id=" + helpline_data_id
        + "&service_body_id=" + service_body_id + "&data_type=" + data_type,
        data: JSON.stringify({"data": data}),
        dataType: "json",
        contentType: "application/json",
        success: function () {

        }
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
    var getLastVolunteerCard = parseInt($("#volunteerCards").children().length);
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
        shiftCardTemplate.find("#shiftDay").html(dayOfTheWeek[shiftInfoObj["day"]]);
        shiftCardTemplate.attr("data", JSON.stringify(shiftInfoObj));
        shiftCardTemplate.find("#shiftInfo").html(shiftInfoObj["start_time"] + "-" + shiftInfoObj["end_time"] + " " + shiftInfoObj["tz"]);
        shiftCardTemplate.show();
        shiftCardTemplate.appendTo($("#" + volunteerId).find("#shiftsCards"))
    }
}

var wrapFunction = function(fn, context, params) {
    return function() {
        fn.apply(context, params);
    };
};

function addShift(e) {
    $("#time_zone").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
    $("#shiftVolunteerName").html("%person%");
    $("#selectShiftDialog").attr({
        "volunteer_id": $(e).closest(".volunteerCard").attr("id"),
        "day_id": $(e).attr("data-shiftid")
    });
    $("#selectShiftDialog").modal("show");
}

function saveShift(e) {
    var volunteer_id = $("#selectShiftDialog").attr("volunteer_id");
    var day_id = $("#day_of_the_week").val();
    var time_zone_id = $("#time_zone").val();
    var start_time = $("#start_time_hour").val() + ":" + $("#start_time_minute").val() + " " + $("#start_time_division").val();
    var end_time = $("#end_time_hour").val() + ":" + $("#end_time_minute").val() + " " + $("#end_time_division").val();
    var shiftInfoObj = {
        "day": day_id,
        "tz": time_zone_id,
        "start_time": start_time,
        "end_time": end_time
    };

    renderShift(volunteer_id, shiftInfoObj);
    $("#selectShiftDialog").modal("hide");
}

function removeShift(e) {
    $(e).closest(".shiftCard").remove();
}

function toggleCardDetails(e) {
    var volunteerCard = $(e).closest(".volunteerCard")
    var isCurrentlyShown = volunteerCard.find(".volunteerCardBody").attr("class").indexOf("show") >= 0;
    volunteerCard.find(".volunteerCardBodyToggleButton").html(isCurrentlyShown ? "+" : "-");
    volunteerCard.find(".volunteerCardBody").collapse('toggle');
}

function serviceBodyConfigure(service_body_id) {
    $("#spinnerDialog").modal('show');
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

        setTimeout(function() {
            $("#spinnerDialog").modal('hide');
            serviceBodyConfiguration.modal("show");
        }, 500);
    });
}

function dataEncoder(dataObject) {
    return btoa(JSON.stringify(dataObject));
}

function dataDecoder(dataString) {
    return JSON.parse(atob(dataString));
}

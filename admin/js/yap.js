$(function() {
    $("#add-volunteer").click(function() {
        addVolunteer({
            "volunteer_name": $("#new_volunteer_name").val()
        });

        $("#new_volunteer_name").val("");
    });

    $("#save-volunteers").click(function() {
        var volunteerCards = $("#volunteerCards").children();
        var data = [];
        for (var volunteerCard of volunteerCards) {
            var formData = $(volunteerCard).find("#volunteersForm").serializeArray();
            var dataObj = {};
            for (var formItem of formData) {
                dataObj[formItem["name"]] = formItem["value"]
            }

            data.push(dataObj);
        }

        $.ajax({
            async: true,
            type: "POST",
            url: "/admin/api.php?action=save&helpline_data_id=" + $("#helpline_data_id").val()
            + "&service_body_id=" + $("#service_body_id").val(),
            data: JSON.stringify({"data": data}),
            dataType: "json",
            contentType: "application/json",
            success: function () {

            }
        });

        $("#volunteer_saved_alert").show();
        $("#volunteer_saved_alert").fadeOut(3000);
    });

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
});

function addNewVolunteerDialog(isVisible) {
    isVisible ? $("#newVolunteerDialog").show() : $("#newVolunteerDialog").hide();
}

function clearVolunteerCards() {
    $("#volunteerCards").children().remove()
}

function loadVolunteers(serviceBodyId, callback) {
    $.getJSON("/admin/api.php?service_body_id=" + serviceBodyId, function(data) {
        var helpline_data_id = 0;
        if (!$.isEmptyObject(data)) {
            helpline_data_id = data["id"];
            for (item of data["data"]) {
                addVolunteer(item)
            }
        }
        $("#helpline_data_id").val(helpline_data_id);
        callback();
    })
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

        if (key.indexOf("day_") > -1) {
            shiftRenderQueue.push(
                wrapFunction(renderShift, this, [volunteerId, key, volunteerData[key]])
            );
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

function volunteerStatusToggle(e) {
    $(e).val(e.checked);
}

function renderShift(volunteerId, dayId, shiftInfo) {
    if (shiftInfo != "") {
        var shiftCardTemplate = $("#shiftCardTemplate").clone();
        var shiftInfoObj = JSON.parse(atob(shiftInfo));
        shiftCardTemplate.find("#shiftInfo").html(shiftInfoObj["start_time"] + "-" + shiftInfoObj["end_time"]);
        shiftCardTemplate.show();
        shiftCardTemplate.appendTo($("#" + volunteerId).find("#shifts_" + dayId))
    }
}

var wrapFunction = function(fn, context, params) {
    return function() {
        fn.apply(context, params);
    };
}

function selectShift(e, day) {
    $("#shiftDayTitle").html(day);
    $("#selectShiftDialog").attr({
        "volunteer_id": $(e).closest(".volunteerCard").attr("id"),
        "day_id": $(e).attr("data-shiftid")
    });
    $("#selectShiftDialog").modal("show");
}

function saveShift(e) {
    var volunteer_id = $("#selectShiftDialog").attr("volunteer_id");
    var day_id = $("#selectShiftDialog").attr("day_id");
    var start_time = $("#start_time_hour").val() + ":" + $("#start_time_minute").val() + " " + $("#start_time_division").val();
    var end_time = $("#end_time_hour").val() + ":" + $("#end_time_minute").val() + " " + $("#end_time_division").val();

    var shiftInfo = btoa(
        JSON.stringify({
            "start_time": start_time,
            "end_time": end_time
        })
    );

    renderShift(volunteer_id, day_id, shiftInfo);
    $("#" + volunteer_id).find("#" + day_id).val(shiftInfo);
    $("#selectShiftDialog").modal("hide");
}

function removeShift(e) {
    $(e).closest(".shiftBody").find(".day_of_the_week_field").val("")
    $(e).closest(".shiftCard").remove();
}

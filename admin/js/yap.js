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
    var getLastVolunteerCard = parseInt($("#volunteerCards").children().length);
    var volunteerCardTemplate = $("#volunteerCardTemplate").clone();
    volunteerCardTemplate.attr("id", "volunteerCard_" + (++getLastVolunteerCard));
    volunteerCardTemplate.find("#volunteerSequence").html(getLastVolunteerCard);
    volunteerCardTemplate.show();
    for (var key in volunteerData) {
        // Handle checkbox fields
        if (volunteerData[key]) {
            volunteerCardTemplate.find("#" + key).prop('checked', true);
        }

        volunteerCardTemplate.find("#" + key).val(volunteerData[key]);
    }
    volunteerCardTemplate.appendTo("#volunteerCards");
}

function removeVolunteer(e) {
    $(e).closest(".volunteerCard").remove();
}

function volunteerStatusToggle(e) {
    $(e).val(e.checked);
}

function selectShift(e, day) {
    $("#shiftDayTitle").html(day);
    $("#selectShiftDialog").attr({
        "volunteer_id": $(e).closest(".volunteerCard").attr("id"),
        "day_id": $(e).attr("id")
    });
    $("#selectShiftDialog").modal("show");
}

function saveShift(e) {
    var volunteer_id = $("#selectShiftDialog").attr("volunteer_id");
    var day_id = $("#selectShiftDialog").attr("day_id");
    $("#" + volunteer_id).find("#" + day_id).val(
        JSON.stringify({
            "start_time" : $("#start_time_hour").val()
            + ":" + $("#start_time_minute").val()
            + " " + $("#start_time_division").val(),
            "end_time" : $("#end_time_hour").val()
            + ":" + $("#end_time_minute").val()
            + " " + $("#end_time_division").val()
        })
    );

    $("#selectShiftDialog").modal("hide");
}

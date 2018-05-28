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